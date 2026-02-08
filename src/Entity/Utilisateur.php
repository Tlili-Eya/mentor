<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
class Utilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $mdp = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdp_url = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $date_inscription = null;

    /**
     * @var Collection<int, CategorieArticle>
     */
    #[ORM\OneToMany(targetEntity: CategorieArticle::class, mappedBy: 'auteur')]
    private Collection $categorieArticles;

    /**
     * @var Collection<int, ReferenceArticle>
     */
    #[ORM\OneToMany(targetEntity: ReferenceArticle::class, mappedBy: 'auteur')]
    private Collection $referenceArticles;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    /**
     * @var Collection<int, Projet>
     */
    #[ORM\OneToMany(targetEntity: Projet::class, mappedBy: 'utilisateur')]
    private Collection $projets;

    /**
     * @var Collection<int, Projet>
     */
    #[ORM\OneToMany(targetEntity: Projet::class, mappedBy: 'utilisateur')]
    private Collection $projet;

    /**
     * @var Collection<int, Feedback>
     */
    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'utilisateur', orphanRemoval:true)]
    private Collection $feedback;

    /**
     * @var Collection<int, Objectif>
     */
    #[ORM\OneToMany(targetEntity: Objectif::class, mappedBy: 'utilisateur', orphanRemoval:true)]
    private Collection $objectifs;

    public function __construct()
    {
        $this->categorieArticles = new ArrayCollection();
        $this->referenceArticles = new ArrayCollection();
        $this->projets = new ArrayCollection();
        $this->projet = new ArrayCollection();
        $this->feedback = new ArrayCollection();
        $this->objectifs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(string $mdp): static
    {
        $this->mdp = $mdp;

        return $this;
    }

    public function getPdpUrl(): ?string
    {
        return $this->pdp_url;
    }

    public function setPdpUrl(?string $pdp_url): static
    {
        $this->pdp_url = $pdp_url;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->date_inscription;
    }

    public function setDateInscription(?\DateTime $date_inscription): static
    {
        $this->date_inscription = $date_inscription;

        return $this;
    }

    /**
     * @return Collection<int, CategorieArticle>
     */
    public function getCategorieArticles(): Collection
    {
        return $this->categorieArticles;
    }

    public function addCategorieArticle(CategorieArticle $categorieArticle): static
    {
        if (!$this->categorieArticles->contains($categorieArticle)) {
            $this->categorieArticles->add($categorieArticle);
            $categorieArticle->setAuteur($this);
        }

        return $this;
    }

    public function removeCategorieArticle(CategorieArticle $categorieArticle): static
    {
        if ($this->categorieArticles->removeElement($categorieArticle)) {
            // set the owning side to null (unless already changed)
            if ($categorieArticle->getAuteur() === $this) {
                $categorieArticle->setAuteur(null);
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
            $referenceArticle->setAuteur($this);
        }

        return $this;
    }

    public function removeReferenceArticle(ReferenceArticle $referenceArticle): static
    {
        if ($this->referenceArticles->removeElement($referenceArticle)) {
            // set the owning side to null (unless already changed)
            if ($referenceArticle->getAuteur() === $this) {
                $referenceArticle->setAuteur(null);
            }
        }

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Collection<int, Projet>
     */
    public function getProjet(): Collection
    {
        return $this->projet;
    }

    public function addProjet(Projet $projet): static
    {
        if (!$this->projet->contains($projet)) {
            $this->projet->add($projet);
            $projet->setUtilisateur($this);
        }

        return $this;
    }
    public function getPassword(): ?string
    {
        return $this->mdp;
    }

    public function getRoles(): array
    {
        // Convert your 'role' string to Symfony ROLE_ format
        $role = $this->role ? 'ROLE_' . strtoupper($this->role) : 'ROLE_USER';
        return [$role];
    }

    /**
     * Required by UserInterface – usually empty unless you have temporary credentials
     */
    public function eraseCredentials(): void
    {
        // Nothing to do here for now
    }

    public function removeProjet(Projet $projet): static
    {
        if ($this->projet->removeElement($projet)) {
            // set the owning side to null (unless already changed)
            if ($projet->getUtilisateur() === $this) {
                $projet->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Feedback>
     */
    public function getFeedback(): Collection
    {
        return $this->feedback;
    }

    public function addFeedback(Feedback $feedback): static
    {
        if (!$this->feedback->contains($feedback)) {
            $this->feedback->add($feedback);
            $feedback->setUtilisateur($this);
        }

        return $this;
    }

    public function removeFeedback(Feedback $feedback): static
    {
        if ($this->feedback->removeElement($feedback)) {
            // set the owning side to null (unless already changed)
            if ($feedback->getUtilisateur() === $this) {
                $feedback->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Objectif>
     */
    public function getObjectifs(): Collection
    {
        return $this->objectifs;
    }

    public function addObjectif(Objectif $objectif): static
    {
        if (!$this->objectifs->contains($objectif)) {
            $this->objectifs->add($objectif);
            $objectif->setUtilisateur($this);
        }

        return $this;
    }

    public function removeObjectif(Objectif $objectif): static
    {
        if ($this->objectifs->removeElement($objectif)) {
            // set the owning side to null (unless already changed)
            if ($objectif->getUtilisateur() === $this) {
                $objectif->setUtilisateur(null);
            }
        }

        return $this;
    }


}
