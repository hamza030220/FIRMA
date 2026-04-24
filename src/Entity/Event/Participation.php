<?php

namespace App\Entity\Event;

use App\Entity\User\Utilisateur;
use App\Repository\Event\ParticipationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[ORM\Table(name: 'participations')]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_participation')]
    private ?int $idParticipation = null;

    #[ORM\ManyToOne(targetEntity: Evenement::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'id_evenement', referencedColumnName: 'id_evenement')]
    private ?Evenement $evenement = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_utilisateur', referencedColumnName: 'id')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = 'en_attente';

    #[ORM\Column(name: 'date_inscription', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(name: 'date_annulation', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateAnnulation = null;

    #[ORM\Column(name: 'nombre_accompagnants')]
    #[Assert\PositiveOrZero]
    #[Assert\Range(max: 10, maxMessage: 'Le nombre d\'accompagnants ne peut pas dépasser {{ limit }}')]
    private int $nombreAccompagnants = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(name: 'code_participation', length: 20, nullable: true)]
    private ?string $codeParticipation = null;

    /** @var Collection<int, Accompagnant> */
    #[ORM\OneToMany(targetEntity: Accompagnant::class, mappedBy: 'participation', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $accompagnants;

    public function __construct()
    {
        $this->accompagnants   = new ArrayCollection();
        $this->dateInscription = new \DateTime();
        $this->statut          = 'en_attente';
    }

    // ── Helpers ──

    public function getStatutEnum(): ?StatutParticipation
    {
        return StatutParticipation::tryFrom($this->statut);
    }

    public function getTotalPersonnes(): int
    {
        return 1 + $this->nombreAccompagnants;
    }

    public static function genererCode(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code  = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return 'PART-' . $code;
    }

    // ── Getters / Setters ──

    public function getIdParticipation(): ?int { return $this->idParticipation; }

    public function getEvenement(): ?Evenement { return $this->evenement; }
    public function setEvenement(?Evenement $evenement): static { $this->evenement = $evenement; return $this; }

    public function getUtilisateur(): ?Utilisateur { return $this->utilisateur; }
    public function setUtilisateur(?Utilisateur $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(\DateTimeInterface $d): static { $this->dateInscription = $d; return $this; }

    public function getDateAnnulation(): ?\DateTimeInterface { return $this->dateAnnulation; }
    public function setDateAnnulation(?\DateTimeInterface $d): static { $this->dateAnnulation = $d; return $this; }

    public function getNombreAccompagnants(): int { return $this->nombreAccompagnants; }
    public function setNombreAccompagnants(int $n): static { $this->nombreAccompagnants = $n; return $this; }

    public function getCommentaire(): ?string { return $this->commentaire; }
    public function setCommentaire(?string $commentaire): static { $this->commentaire = $commentaire; return $this; }

    public function getCodeParticipation(): ?string { return $this->codeParticipation; }
    public function setCodeParticipation(?string $code): static { $this->codeParticipation = $code; return $this; }

    /** @return Collection<int, Accompagnant> */
    public function getAccompagnants(): Collection { return $this->accompagnants; }

    public function addAccompagnant(Accompagnant $a): static
    {
        if (!$this->accompagnants->contains($a)) {
            $this->accompagnants->add($a);
            $a->setParticipation($this);
        }
        return $this;
    }

    public function removeAccompagnant(Accompagnant $a): static
    {
        if ($this->accompagnants->removeElement($a) && $a->getParticipation() === $this) {
            $a->setParticipation(null);
        }
        return $this;
    }
}
