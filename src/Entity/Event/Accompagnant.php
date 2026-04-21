<?php

namespace App\Entity\Event;

use App\Repository\Event\AccompagnantRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AccompagnantRepository::class)]
#[ORM\Table(name: 'accompagnants')]
class Accompagnant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_accompagnant')]
    private ?int $idAccompagnant = null;

    #[ORM\ManyToOne(targetEntity: Participation::class, inversedBy: 'accompagnants')]
    #[ORM\JoinColumn(name: 'id_participation', referencedColumnName: 'id_participation')]
    private ?Participation $participation = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le nom doit contenir au moins {{ limit }} caractères')]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est requis')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères')]
    private ?string $prenom = null;

    #[ORM\Column(name: 'code_accompagnant', length: 20, nullable: true)]
    private ?string $codeAccompagnant = null;

    // ── Getters / Setters ──

    public function getIdAccompagnant(): ?int { return $this->idAccompagnant; }

    public function getParticipation(): ?Participation { return $this->participation; }
    public function setParticipation(?Participation $p): static { $this->participation = $p; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getCodeAccompagnant(): ?string { return $this->codeAccompagnant; }
    public function setCodeAccompagnant(?string $code): static { $this->codeAccompagnant = $code; return $this; }

    public static function genererCode(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code  = '';
        for ($i = 0; $i < 5; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return 'ACC-' . $code;
    }

    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }
}
