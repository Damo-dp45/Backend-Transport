<?php

namespace App\Entity;

use App\Repository\DetailpersonnelRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: DetailpersonnelRepository::class)]
class Detailpersonnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Trajet', 'read:Personnel'])]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'detailpersonnels')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] // Ou 'cascade: ..'
    #[Groups(['read:Depannage', 'read:Voyage', 'read:Trajet'])]
    private ?Personnel $personnel = null;

    #[ORM\ManyToOne(inversedBy: 'detailpersonnels')] // '#[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]' fait automatiquement
    #[Groups(['read:Personnel'])]
    private ?Depannage $depannage = null;
    #[Groups(['read:Personnel'])]

    #[ORM\ManyToOne(inversedBy: 'detailPersonnels')] // !!
    private ?Voyage $voyage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getPersonnel(): ?Personnel
    {
        return $this->personnel;
    }

    public function setPersonnel(?Personnel $personnel): static
    {
        $this->personnel = $personnel;

        return $this;
    }

    public function getDepannage(): ?Depannage
    {
        return $this->depannage;
    }

    public function setDepannage(?Depannage $depannage): static
    {
        $this->depannage = $depannage;

        return $this;
    }

    public function getVoyage(): ?Voyage
    {
        return $this->voyage;
    }

    public function setVoyage(?Voyage $voyage): static
    {
        $this->voyage = $voyage;

        return $this;
    }
}
