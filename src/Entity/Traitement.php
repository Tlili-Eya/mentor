<?php

namespace App\Entity;

use App\Repository\TraitementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TraitementRepository::class)]
class Traitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $typetraitement = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $datetraitement = null;

    #[ORM\Column(length: 255)]
    private ?string $decision = null;

    #[ORM\OneToOne(mappedBy: 'traitement', cascade: ['persist', 'remove'], orphanRemoval:true)]
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
