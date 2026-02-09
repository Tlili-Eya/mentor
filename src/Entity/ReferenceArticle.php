<?php

namespace App\Entity;

use App\Repository\ReferenceArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReferenceArticleRepository::class)]
class ReferenceArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\ManyToOne(inversedBy: 'referenceArticles')]
    private ?CategorieArticle $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'referenceArticles')]
    private ?Utilisateur $auteur = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\Column]
    private bool $published = false;

    /**
     * @var Collection<int, SortieAI>
     */
#[ORM\OneToMany(mappedBy: 'article', targetEntity: SortieAI::class, orphanRemoval:true)]
private Collection $sortiesAI;

    public function __construct()
    {
        $this->sortieAIs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function getCategorie(): ?CategorieArticle
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieArticle $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    public function getAuteur(): ?Utilisateur
    {
        return $this->auteur;
    }

    public function setAuteur(?Utilisateur $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
public function isPublished(): bool  // <-- Retourne bool, pas ?bool
{
    return $this->published;
}

public function setPublished(bool $published): static
{
    $this->published = $published;
    return $this;
}

    /**
     * @return Collection<int, SortieAI>
     */
    public function getSortieAIs(): Collection
    {
        return $this->sortieAIs;
    }

    public function addSortieAI(SortieAI $sortieAI): static
    {
        if (!$this->sortieAIs->contains($sortieAI)) {
            $this->sortieAIs->add($sortieAI);
            $sortieAI->addReferenceArticle($this);
        }

        return $this;
    }

    public function removeSortieAI(SortieAI $sortieAI): static
    {
        if ($this->sortieAIs->removeElement($sortieAI)) {
            $sortieAI->removeReferenceArticle($this);
        }

        return $this;
    }
}
