<?php
// src/Entity/SortieAI.php

namespace App\Entity;

use App\Enum\Cible;
use App\Enum\TypeSortie;
use App\Enum\Criticite;
use App\Enum\CategorieSortie;
use App\Repository\SortieAIRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieAIRepository::class)]
class SortieAI
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 20, enumType: Cible::class, nullable: false)]
    private Cible $cible;

    #[ORM\Column(type: 'string', length: 20, enumType: TypeSortie::class, nullable: false)]
    private TypeSortie $typeSortie;

    #[ORM\Column(type: 'string', length: 20, enumType: Criticite::class, nullable: false)]
    private Criticite $criticite;

    #[ORM\Column(type: 'string', length: 20, enumType: CategorieSortie::class, nullable: false)]
    private CategorieSortie $categorieSortie;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\OneToMany(targetEntity: PlanActions::class, mappedBy: 'sortieAI', orphanRemoval: true)]
    private Collection $planActions;

    #[ORM\ManyToOne(targetEntity: ReferenceArticle::class, inversedBy: 'sortiesAI')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ReferenceArticle $article = null;

    public function __construct()
    {
        $this->planActions = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // GETTERS ET SETTERS (copie depuis ton fichier actuel)
    public function getId(): ?int { return $this->id; }
    public function getCible(): Cible { return $this->cible; }
    public function setCible(Cible $cible): static { $this->cible = $cible; return $this; }
    public function getTypeSortie(): TypeSortie { return $this->typeSortie; }
    public function setTypeSortie(TypeSortie $typeSortie): static { $this->typeSortie = $typeSortie; return $this; }
    public function getCriticite(): Criticite { return $this->criticite; }
    public function setCriticite(Criticite $criticite): static { $this->criticite = $criticite; return $this; }
    public function getCategorieSortie(): CategorieSortie { return $this->categorieSortie; }
    public function setCategorieSortie(CategorieSortie $categorieSortie): static { $this->categorieSortie = $categorieSortie; return $this; }
    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }
    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }
    public function setCreatedAt(\DateTime $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function getPlanActions(): Collection { return $this->planActions; }
    public function addPlanAction(PlanActions $planAction): static {
        if (!$this->planActions->contains($planAction)) {
            $this->planActions->add($planAction);
            $planAction->setSortieAI($this);
        }
        return $this;
    }
    public function removePlanAction(PlanActions $planAction): static {
        if ($this->planActions->removeElement($planAction)) {
            if ($planAction->getSortieAI() === $this) {
                $planAction->setSortieAI(null);
            }
        }
        return $this;
    }
    public function getArticle(): ?ReferenceArticle { return $this->article; }
    public function setArticle(?ReferenceArticle $article): static { $this->article = $article; return $this; }
}