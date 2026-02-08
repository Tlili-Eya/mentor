<?php
// src/Entity/PlanActions.php

namespace App\Entity;

use App\Enum\Statut;
use App\Enum\CategorieSortie;
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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null; 

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;
    
    #[ORM\Column(type: 'string', enumType: Statut::class)]
    private ?Statut $statut = null;

    #[ORM\Column(type: 'string', enumType: CategorieSortie::class, nullable: true)]
    private ?CategorieSortie $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'planActions')]
    private ?SortieAI $sortieAI = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedbackEnseignant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $feedbackDate = null;

    #[ORM\ManyToOne]
    private ?Utilisateur $feedbackAuteur = null;



    public function __construct()
    {
        $this->date = new \DateTime(); // CORRIGÉ : pas d'espace après =
        $this->statut = Statut::EnAttente;
        $this->categorie = CategorieSortie::Pedagogique;
    }

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

    // GETTER/SETTER POUR date (NOUVEAU)
    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getStatut(): ?Statut
    {
        return $this->statut;
    }

    public function setStatut(Statut $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getCategorie(): ?CategorieSortie
    {
        return $this->categorie;
    }

    public function setCategorie(?CategorieSortie $categorie): static
    {
        $this->categorie = $categorie;
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

    public function getFeedbackEnseignant(): ?string
    {
        return $this->feedbackEnseignant;
    }

    public function setFeedbackEnseignant(?string $feedbackEnseignant): static
    {
        $this->feedbackEnseignant = $feedbackEnseignant;
        if ($feedbackEnseignant) {
            $this->feedbackDate = new \DateTime();
        }
        return $this;
    }

    public function getFeedbackDate(): ?\DateTimeInterface
    {
        return $this->feedbackDate;
    }

    public function setFeedbackDate(?\DateTimeInterface $feedbackDate): static
    {
        $this->feedbackDate = $feedbackDate;
        return $this;
    }

    public function getFeedbackAuteur(): ?Utilisateur
    {
        return $this->feedbackAuteur;
    }

    public function setFeedbackAuteur(?Utilisateur $feedbackAuteur): static
    {
        $this->feedbackAuteur = $feedbackAuteur;
        return $this;
    }

    public function getCategorieNom(): string
    {
        return $this->categorie?->value ?? '';
    }
    
    public function getStatutNom(): string
    {
        return $this->statut?->value ?? '';
    }

}