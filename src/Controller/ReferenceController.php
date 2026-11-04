<?php

namespace App\Controller;

use App\Entity\Referencia;
use App\Form\ReferenciaType;
use App\Repository\ReferenciaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/refs')]
class ReferenceController extends AbstractController
{
    #[Route('', name: 'app_reference_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('reference/index.html.twig');
    }

    #[Route('/data', name: 'app_reference_data', methods: ['GET'])]
    public function data(Request $request, ReferenciaRepository $repo): JsonResponse
    {
        $params = $request->query->all();
        $criteria = json_decode($request->query->get('criteria', '{}'), true) ?? [];

        $result = $repo->getDataTableData($params, $criteria);

        $rows = [];
        foreach ($result['data'] as $ref) {
            $rows[] = [
                'id'        => $ref->getId(),
                'title'     => $ref->getTitle(),
                'author'    => $ref->getAuthor() ?? '',
                'type'      => $ref->getType() ?? '',
                'year'      => $ref->getYear() ?? '',
                'journal'   => $ref->getJournal() ?? '',
                'volume'    => $ref->getVolume() ?? '',
                'issue'     => $ref->getIssue() ?? '',
                'pages'     => $ref->getPages() ?? '',
                'publisher' => $ref->getPublisher() ?? '',
                'placePub'  => $ref->getPlacePub() ?? '',
                'bookTitle' => $ref->getBookTitle() ?? '',
                'editor'    => $ref->getEditor() ?? '',
                'school'    => $ref->getSchool() ?? '',
                'url_show'  => $this->generateUrl('app_reference_show', ['id' => $ref->getId()]),
            ];
        }

        return new JsonResponse([
            'draw'            => (int) ($params['draw'] ?? 1),
            'recordsTotal'    => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data'            => $rows,
        ]);
    }

    #[Route('/{id}/show', name: 'app_reference_show', methods: ['GET'])]
    public function show(Referencia $referencia): Response
    {
        return $this->render('reference/show.html.twig', ['ref' => $referencia]);
    }

    #[Route('/new', name: 'app_reference_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ReferenciaRepository $repo,
        SluggerInterface $slugger,
        MailerInterface $mailer,
    ): Response {
        $ref = new Referencia();
        $form = $this->createForm(ReferenciaType::class, $ref);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->assignPreprintNumber($ref, $repo);
            if ($form->get('uploadedFile')->getData() !== null && !$this->handleFileUpload($form, $ref, $slugger)) {
                $this->addFlash('warning', 'No se pudo guardar el archivo adjunto. La referencia fue guardada sin él.');
            }

            if (!$this->isGranted('ROLE_ADMIN')) {
                $user = $this->getUser();
                $ref->setUser($user);
                if ($user->getAuthor()) {
                    $ref->addAuthor($user->getAuthor());
                }
            }

            $em->persist($ref);
            $em->flush();

            $this->sendNewReferenceNotification($ref, $mailer);

            $this->addFlash('success', 'Reference saved.');
            return $this->redirectToRoute('app_reference_show', ['id' => $ref->getId()]);
        }

        return $this->render('reference/form.html.twig', ['form' => $form, 'ref' => $ref, 'edit' => false]);
    }

    #[Route('/{id}/edit', name: 'app_reference_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(
        Request $request,
        Referencia $ref,
        EntityManagerInterface $em,
        ReferenciaRepository $repo,
        SluggerInterface $slugger,
    ): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $ref->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $originalFile = $ref->getFile();
        $form = $this->createForm(ReferenciaType::class, $ref);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->assignPreprintNumber($ref, $repo);
            $hasNewFile = $form->get('uploadedFile')->getData() !== null;
            $uploaded = $hasNewFile && $this->handleFileUpload($form, $ref, $slugger);
            if ($hasNewFile && !$uploaded) {
                $this->addFlash('warning', 'No se pudo guardar el archivo adjunto.');
                $ref->setFile($originalFile);
            } elseif (!$hasNewFile) {
                $ref->setFile($originalFile);
            } else {
                $this->deleteUploadedFile($originalFile, $ref->getType());
            }

            if (!$this->isGranted('ROLE_ADMIN')) {
                $ref->setUser($this->getUser());
                $ref->setRevision(false);
            }

            $em->flush();
            $this->addFlash('success', 'Reference updated.');
            return $this->redirectToRoute('app_reference_show', ['id' => $ref->getId()]);
        }

        return $this->render('reference/form.html.twig', ['form' => $form, 'ref' => $ref, 'edit' => true]);
    }

    #[Route('/{id}/delete', name: 'app_reference_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Referencia $ref, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && $ref->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $ref->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($ref);
            $em->flush();
            $this->addFlash('success', 'Reference deleted.');
        }

        return $this->redirectToRoute('app_reference_index');
    }

    private function sendNewReferenceNotification(Referencia $ref, MailerInterface $mailer): void
    {
        try {
            $url     = $this->generateUrl('app_reference_show', ['id' => $ref->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
            $editUrl = $this->generateUrl('app_reference_edit', ['id' => $ref->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

            $body = $this->renderView('emails/new_reference.txt.twig', [
                'ref'     => $ref,
                'url'     => $url,
                'editUrl' => $editUrl,
            ]);

            $bcc = $this->getParameter('app.notify.bcc');
            $email = (new Email())
                ->from($this->getParameter('app.notify.from'))
                ->to($this->getParameter('app.notify.to'))
                ->subject('Nuevo registro en SRB — ' . mb_substr($ref->getTitle() ?? '(sin título)', 0, 80))
                ->text($body);
            if ($bcc !== '') {
                $email->addBcc($bcc);
            }

            $mailer->send($email);
        } catch (\Throwable) {
            // No interrumpir el flujo si falla el envío
        }
    }

    private function assignPreprintNumber(Referencia $ref, ReferenciaRepository $repo): void
    {
        if ($ref->getType() !== 'preprint') {
            return;
        }
        $year = $ref->getYearPreprint();
        if (!$year || $ref->getReportNumber()) {
            return;
        }
        $ref->setReportNumber($repo->getNextPreprintNumber((int) $year));
    }

    private function deleteUploadedFile(?string $filename, ?string $type): void
    {
        if (!$filename) {
            return;
        }
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/' . ($type ?? 'misc') . '/' . $filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function handleFileUpload($form, Referencia $ref, SluggerInterface $slugger): bool
    {
        $file = $form->get('uploadedFile')->getData();
        if (!$file) {
            return false;
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . bin2hex(random_bytes(8)) . '.' . $file->guessExtension();

        try {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/' . ($ref->getType() ?? 'misc');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $file->move($uploadDir, $newFilename);
            $ref->setFile($newFilename);
            return true;
        } catch (FileException) {
            return false;
        }
    }
}
