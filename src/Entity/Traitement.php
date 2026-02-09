<?php

namespace App\Entity;

use App\Repository\TraitementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TraitementRepository::class)]
class Traitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de traitement est obligatoire.")]
    #[Assert\Choice(
        choices: ['remboursement', 'prolongation_abonnement', 'geste_commercial', 'aucun_traitement'],
        message: "Le type doit être : remboursement, prolongation_abonnement, geste_commercial ou aucun_traitement."
    )]
    private ?string $typetraitement = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(
        min: 10,
        max: 1000,
        minMessage: "La description doit contenir au moins {{ limit }} caractères.",
        maxMessage: "La description ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date du traitement est obligatoire.")]
    private ?\DateTime $datetraitement = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La décision est obligatoire.")]
    private ?string $decision = null;

    #[ORM\OneToOne(mappedBy: 'traitement', cascade: ['persist', 'remove'], orphanRemoval:true)]
    #[Assert\NotNull(message: "Le feedback associé est obligatoire.")]
    private ?Feedback $feedback = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTypetraitement(): ?string
    {
        return $this->typetraitement;
    }

    public function setTypetraitement(string $typetraitement): static
    {
        $this->typetraitement = $typetraitement;

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

    public function getDatetraitement(): ?\DateTime
    {
        return $this->datetraitement;
    }

    public function setDatetraitement(\DateTime $datetraitement): static
    {
        $this->datetraitement = $datetraitement;

        return $this;
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

    public function getFeedback(): ?Feedback
    {
        return $this->feedback;
    }

    public function setFeedback(?Feedback $feedback): static
    {
        // unset the owning side of the relation if necessary
        if ($feedback === null && $this->feedback !== null) {
            $this->feedback->setTraitement(null);
        }

        // set the owning side of the relation if necessary
        if ($feedback !== null && $feedback->getTraitement() !== $this) {
            $feedback->setTraitement($this);
        }

        $this->feedback = $feedback;

        return $this;
    }
}
