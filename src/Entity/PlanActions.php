<?php

namespace App\Entity;
use App\Enum\Statut;
use App\Repository\PlanActionsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanActionsRepository::class)]
class PlanActions
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $decision = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $updatedAt = null;
    
    #[ORM\Column(type: 'string', length: 50, enumType: Statut::class)]
    private Statut $statut;

    #[ORM\ManyToOne(inversedBy: 'planActions')]
    private ?SortieAI $sortieAI = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): static
    {
        $this->decision = $decision;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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
    public function getStatut(): Statut
    {
        return $this->statut;
    }

    public function setStatut(Statut $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getSortieAI(): ?SortieAI
    {
        return $this->sortieAI;
    }

    public function setSortieAI(?SortieAI $sortieAI): static
    {
        $this->sortieAI = $sortieAI;

        return $this;
    }

}
