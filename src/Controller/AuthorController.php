<?php

namespace App\Controller;

use App\Entity\Author;
use App\Form\AuthorType;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/authors')]
class AuthorController extends AbstractController
{
    #[Route('', name: 'app_author_index', methods: ['GET'])]
    public function index(AuthorRepository $repo): Response
    {
        return $this->render('author/index.html.twig', [
            'authors' => $repo->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/{id}/show', name: 'app_author_show', methods: ['GET'])]
    public function show(Author $author): Response
    {
        return $this->render('author/show.html.twig', ['author' => $author]);
    }

    #[Route('/new', name: 'app_author_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $author = new Author();
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($author);
            $em->flush();
            $this->addFlash('success', 'Author created.');
            return $this->redirectToRoute('app_author_index');
        }

        return $this->render('author/form.html.twig', ['form' => $form, 'author' => $author, 'edit' => false]);
    }

    #[Route('/{id}/edit', name: 'app_author_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Author $author, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(AuthorType::class, $author);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Author updated.');
            return $this->redirectToRoute('app_author_show', ['id' => $author->getId()]);
        }

        return $this->render('author/form.html.twig', ['form' => $form, 'author' => $author, 'edit' => true]);
    }

    #[Route('/{id}/delete', name: 'app_author_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Author $author, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $author->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($author);
            $em->flush();
            $this->addFlash('success', 'Author deleted.');
        }
        return $this->redirectToRoute('app_author_index');
    }
}
