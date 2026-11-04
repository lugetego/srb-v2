<?php

namespace App\Entity;

use App\Repository\ReferenciaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReferenciaRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Referencia
{
    const TYPES = [
        'Article'       => 'article',
        'Book'          => 'book',
        'Book chapter'  => 'incollection',
        'Inproceedings' => 'inproceedings',
        'Preprint'      => 'preprint',
        'Proceedings'   => 'proceedings',
        'Thesis'        => 'thesis',
        'Misc'          => 'misc',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $author = null;

    #[ORM\ManyToMany(targetEntity: Author::class, inversedBy: 'publications')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $authors;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private ?string $title = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $yearPreprint = null;

    #[ORM\Column(length: 4, nullable: true)]
    private ?string $yearPub = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $publication = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $journal = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $issue = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $volume = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $pages = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $corporateAuthor = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $school = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $keywords = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $abst = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $publisher = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $placePub = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $editor = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $issn = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $isbn = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $medium = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $advisor = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $thesisType = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notas = null;

    #[ORM\Column(nullable: true)]
    private ?bool $revision = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $file = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $doi = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $arxiv = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $mathscinet = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $zmath = null;

    #[ORM\Column(length: 250, nullable: true)]
    private ?string $inspires = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $reportNumber = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $msc = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $mrNumber = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bookTitle = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $created = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $modified = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'referencias')]
    private ?User $user = null;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function prePersist(): void
    {
        $this->created = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->modified = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $author): static { $this->author = $author; return $this; }

    public function getAuthors(): Collection { return $this->authors; }

    public function addAuthor(Author $author): static
    {
        if (!$this->authors->contains($author)) {
            $this->authors->add($author);
            $author->addPublication($this);
        }
        return $this;
    }

    public function removeAuthor(Author $author): static
    {
        if ($this->authors->removeElement($author)) {
            $author->removePublication($this);
        }
        return $this;
    }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): static { $this->type = $type; return $this; }

    public function getYearPreprint(): ?string { return $this->yearPreprint; }
    public function setYearPreprint(?string $yearPreprint): static { $this->yearPreprint = $yearPreprint; return $this; }

    public function getYearPub(): ?string { return $this->yearPub; }
    public function setYearPub(?string $yearPub): static { $this->yearPub = $yearPub; return $this; }

    public function getPublication(): ?string { return $this->publication; }
    public function setPublication(?string $publication): static { $this->publication = $publication; return $this; }

    public function getJournal(): ?string { return $this->journal; }
    public function setJournal(?string $journal): static { $this->journal = $journal; return $this; }

    public function getIssue(): ?string { return $this->issue; }
    public function setIssue(?string $issue): static { $this->issue = $issue; return $this; }

    public function getVolume(): ?string { return $this->volume; }
    public function setVolume(?string $volume): static { $this->volume = $volume; return $this; }

    public function getPages(): ?string { return $this->pages; }
    public function setPages(?string $pages): static { $this->pages = $pages; return $this; }

    public function getCorporateAuthor(): ?string { return $this->corporateAuthor; }
    public function setCorporateAuthor(?string $corporateAuthor): static { $this->corporateAuthor = $corporateAuthor; return $this; }

    public function getSchool(): ?string { return $this->school; }
    public function setSchool(?string $school): static { $this->school = $school; return $this; }

    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $address): static { $this->address = $address; return $this; }

    public function getKeywords(): ?string { return $this->keywords; }
    public function setKeywords(?string $keywords): static { $this->keywords = $keywords; return $this; }

    public function getAbst(): ?string { return $this->abst; }
    public function setAbst(?string $abst): static { $this->abst = $abst; return $this; }

    public function getPublisher(): ?string { return $this->publisher; }
    public function setPublisher(?string $publisher): static { $this->publisher = $publisher; return $this; }

    public function getPlacePub(): ?string { return $this->placePub; }
    public function setPlacePub(?string $placePub): static { $this->placePub = $placePub; return $this; }

    public function getEditor(): ?string { return $this->editor; }
    public function setEditor(?string $editor): static { $this->editor = $editor; return $this; }

    public function getIssn(): ?string { return $this->issn; }
    public function setIssn(?string $issn): static { $this->issn = $issn; return $this; }

    public function getIsbn(): ?string { return $this->isbn; }
    public function setIsbn(?string $isbn): static { $this->isbn = $isbn; return $this; }

    public function getMedium(): ?string { return $this->medium; }
    public function setMedium(?string $medium): static { $this->medium = $medium; return $this; }

    public function getAdvisor(): ?string { return $this->advisor; }
    public function setAdvisor(?string $advisor): static { $this->advisor = $advisor; return $this; }

    public function getThesisType(): ?string { return $this->thesisType; }
    public function setThesisType(?string $thesisType): static { $this->thesisType = $thesisType; return $this; }

    public function getNotas(): ?string { return $this->notas; }
    public function setNotas(?string $notas): static { $this->notas = $notas; return $this; }

    public function getRevision(): ?bool { return $this->revision; }
    public function setRevision(?bool $revision): static { $this->revision = $revision; return $this; }

    public function getFile(): ?string { return $this->file; }
    public function setFile(?string $file): static { $this->file = $file; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }

    public function getDoi(): ?string { return $this->doi; }
    public function setDoi(?string $doi): static { $this->doi = $doi; return $this; }

    public function getArxiv(): ?string { return $this->arxiv; }
    public function setArxiv(?string $arxiv): static { $this->arxiv = $arxiv; return $this; }

    public function getMathscinet(): ?string { return $this->mathscinet; }
    public function setMathscinet(?string $mathscinet): static { $this->mathscinet = $mathscinet; return $this; }

    public function getZmath(): ?string { return $this->zmath; }
    public function setZmath(?string $zmath): static { $this->zmath = $zmath; return $this; }

    public function getInspires(): ?string { return $this->inspires; }
    public function setInspires(?string $inspires): static { $this->inspires = $inspires; return $this; }

    public function getReportNumber(): ?string { return $this->reportNumber; }
    public function setReportNumber(?string $reportNumber): static { $this->reportNumber = $reportNumber; return $this; }

    public function getMsc(): ?string { return $this->msc; }
    public function setMsc(?string $msc): static { $this->msc = $msc; return $this; }

    public function getMrNumber(): ?string { return $this->mrNumber; }
    public function setMrNumber(?string $mrNumber): static { $this->mrNumber = $mrNumber; return $this; }

    public function getBookTitle(): ?string { return $this->bookTitle; }
    public function setBookTitle(?string $bookTitle): static { $this->bookTitle = $bookTitle; return $this; }

    public function getCreated(): ?\DateTimeImmutable { return $this->created; }
    public function getModified(): ?\DateTimeImmutable { return $this->modified; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getYear(): ?string
    {
        return $this->type === 'preprint' ? $this->yearPreprint : $this->yearPub;
    }

    public function __toString(): string { return $this->title ?? ''; }
}
