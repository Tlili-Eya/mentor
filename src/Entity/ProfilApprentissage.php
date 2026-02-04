<?php

namespace App\Entity;

use App\Repository\ProfilApprentissageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilApprentissageRepository::class)]
class ProfilApprentissage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $niveauConcentration = null;

    #[ORM\Column(nullable: true)]
    private ?int $tempsMoyenApprentissage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $matieresFortes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $matieresFaibles = null;

    #[ORM\Column(nullable: true)]
    private ?int $vitesseApprentissage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $formatPréféré = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $styleMotivation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typePers = null;

    #[ORM\ManyToOne]
    private ?Utilisateur $Utilisateur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNiveauConcentration(): ?int
    {
        return $this->niveauConcentration;
    }

    public function setNiveauConcentration(?int $niveauConcentration): static
    {
        $this->niveauConcentration = $niveauConcentration;

        return $this;
    }

    public function getTempsMoyenApprentissage(): ?int
    {
        return $this->tempsMoyenApprentissage;
    }

    public function setTempsMoyenApprentissage(?int $tempsMoyenApprentissage): static
    {
        $this->tempsMoyenApprentissage = $tempsMoyenApprentissage;

        return $this;
    }

    public function getMatieresFortes(): ?string
    {
        return $this->matieresFortes;
    }

    public function setMatieresFortes(?string $matieresFortes): static
    {
        $this->matieresFortes = $matieresFortes;

        return $this;
    }

    public function getMatieresFaibles(): ?string
    {
        return $this->matieresFaibles;
    }

    public function setMatieresFaibles(?string $matieresFaibles): static
    {
        $this->matieresFaibles = $matieresFaibles;

        return $this;
    }

    public function getVitesseApprentissage(): ?int
    {
        return $this->vitesseApprentissage;
    }

    public function setVitesseApprentissage(?int $vitesseApprentissage): static
    {
        $this->vitesseApprentissage = $vitesseApprentissage;

        return $this;
    }

    public function getFormatPréféré(): ?string
    {
        return $this->formatPréféré;
    }

    public function setFormatPréféré(?string $formatPréféré): static
    {
        $this->formatPréféré = $formatPréféré;

        return $this;
    }

    public function getStyleMotivation(): ?string
    {
        return $this->styleMotivation;
    }

    public function setStyleMotivation(?string $styleMotivation): static
    {
        $this->styleMotivation = $styleMotivation;

        return $this;
    }

    public function getTypePers(): ?string
    {
        return $this->typePers;
    }

    public function setTypePers(?string $typePers): static
    {
        $this->typePers = $typePers;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->Utilisateur;
    }

    public function setUtilisateur(?Utilisateur $Utilisateur): static
    {
        $this->Utilisateur = $Utilisateur;

        return $this;
    }
}
