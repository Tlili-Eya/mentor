<?php

namespace App\Entity;

use App\Enum\Cible;
use App\Enum\TypeSortie;
use App\Enum\Criticite;
use App\Enum\CategorieSortie;
use App\Enum\StatutAction;
use App\Repository\SortieAIRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    /**
     * @var Collection<int, PlanActions>
     */
    #[ORM\OneToMany(targetEntity: PlanActions::class, mappedBy: 'sortieAI', orphanRemoval:true)]
    private Collection $planActions;

    /**
     * @var Collection<int, ReferenceArticle>
     */
#[ORM\ManyToOne(targetEntity: ReferenceArticle::class, inversedBy: 'sortiesAI')]
#[ORM\JoinColumn(nullable: false)]
private ReferenceArticle $article;

    public function __construct()
    {
        $this->planActions = new ArrayCollection();
        $this->referenceArticles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCible(): Cible
    {
        return $this->cible;
    }

    public function setCible(Cible $cible): static
    {
        $this->cible = $cible;
        return $this;
    }

    public function getTypeSortie(): TypeSortie
    {
        return $this->typeSortie;
    }

    public function setTypeSortie(TypeSortie $typeSortie): static
    {
        $this->typeSortie = $typeSortie;
        return $this;
    }

    public function getCriticite(): Criticite
    {
        return $this->criticite;
    }

    public function setCriticite(Criticite $criticite): static
    {
        $this->criticite = $criticite;
        return $this;
    }

    public function getCategorieSortie(): CategorieSortie
    {
        return $this->categorieSortie;
    }

    public function setCategorieSortie(CategorieSortie $categorieSortie): static
    {
        $this->categorieSortie = $categorieSortie;
        return $this;
    }

    /**
     * @return Collection<int, PlanActions>
     */
    public function getPlanActions(): Collection
    {
        return $this->planActions;
    }

    public function addPlanAction(PlanActions $planAction): static
    {
        if (!$this->planActions->contains($planAction)) {
            $this->planActions->add($planAction);
            $planAction->setSortieAI($this);
        }

        return $this;
    }

    public function removePlanAction(PlanActions $planAction): static
    {
        if ($this->planActions->removeElement($planAction)) {
            // set the owning side to null (unless already changed)
            if ($planAction->getSortieAI() === $this) {
                $planAction->setSortieAI(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ReferenceArticle>
     */
    public function getReferenceArticles(): Collection
    {
        return $this->referenceArticles;
    }

    public function addReferenceArticle(ReferenceArticle $referenceArticle): static
    {
        if (!$this->referenceArticles->contains($referenceArticle)) {
            $this->referenceArticles->add($referenceArticle);
        }

        return $this;
    }

    public function removeReferenceArticle(ReferenceArticle $referenceArticle): static
    {
        $this->referenceArticles->removeElement($referenceArticle);

        return $this;
    }

}