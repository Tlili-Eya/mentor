<?php

namespace App\Entity;

use App\Repository\FeedbackRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FeedbackRepository::class)]
class Feedback
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le message ne peut pas être vide.")]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: "Le message doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le message ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $contenu = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La note est obligatoire.")]
    #[Assert\Range(
        min: 1,
        max: 5,
        notInRangeMessage: "La note doit être entre {{ min }} et {{ max }}."
    )]
    private ?int $note = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: "La date du feedback est obligatoire.")]
    private ?\DateTime $datefeedback = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le type de feedback est obligatoire.")]
    #[Assert\Choice(
        choices: ['suggestion', 'probleme', 'satisfaction'],
        message: "Le type doit être : suggestion, problème ou satisfaction."
    )]
    private ?string $typefeedback = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'état du feedback est obligatoire.")]
    #[Assert\Choice(
        choices: ['en_attente', 'traite', 'rejete'],
        message: "L'état doit être : en_attente, traite ou rejete."
    )]
    private ?string $etatfeedback = null;

    #[ORM\OneToOne(inversedBy: 'feedback', cascade: ['persist', 'remove'])]
    private ?Traitement $traitement = null;

    #[ORM\ManyToOne(inversedBy: 'feedback')]
    #[Assert\NotNull(message: "L'utilisateur est obligatoire.")]
    private ?Utilisateur $utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDatefeedback(): ?\DateTime
    {
        return $this->datefeedback;
    }

    public function setDatefeedback(\DateTime $datefeedback): static
    {
        $this->datefeedback = $datefeedback;

        return $this;
    }

    public function getTypefeedback(): ?string
    {
        return $this->typefeedback;
    }

    public function setTypefeedback(string $typefeedback): static
    {
        $this->typefeedback = $typefeedback;

        return $this;
    }

    public function getEtatfeedback(): ?string
    {
        return $this->etatfeedback;
    }

    public function setEtatfeedback(string $etatfeedback): static
    {
        $this->etatfeedback = $etatfeedback;

        return $this;
    }

    public function getTraitement(): ?Traitement
    {
        return $this->traitement;
    }

    public function setTraitement(?Traitement $traitement): static
    {
        $this->traitement = $traitement;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }
}
