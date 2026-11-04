<?php

namespace App\Entity;

use App\Repository\AuthorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthorRepository::class)]
class Author
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $alias = null;

    #[ORM\ManyToMany(targetEntity: Referencia::class, mappedBy: 'authors')]
    private Collection $publications;

    #[ORM\OneToOne(inversedBy: 'author', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->publications = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAlias(): ?string { return $this->alias; }
    public function setAlias(?string $alias): static { $this->alias = $alias; return $this; }

    public function getPublications(): Collection { return $this->publications; }

    public function addPublication(Referencia $ref): static
    {
        if (!$this->publications->contains($ref)) {
            $this->publications->add($ref);
        }
        return $this;
    }

    public function removePublication(Referencia $ref): static
    {
        $this->publications->removeElement($ref);
        return $this;
    }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function __toString(): string { return $this->name ?? ''; }
}
