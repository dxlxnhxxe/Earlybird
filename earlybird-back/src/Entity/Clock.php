<?php

namespace App\Entity;

use App\Repository\ClockRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\TeamMember;

#[ORM\Entity(repositoryClass: ClockRepository::class)]
#[ORM\Table(name: '`clock`')]
class Clock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\ManyToOne(targetEntity: TeamMember::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?TeamMember $teamMember = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $timestamp = null;

    #[ORM\Column(length: 20)]
    private ?string $type = null; // 'arrival' ou 'departure'

    public function getId(): ?int { return $this->id; }


    public function getTeamMember(): ?TeamMember { return $this->teamMember; }
    public function setTeamMember(TeamMember $teamMember): static { $this->teamMember = $teamMember; return $this; }

    public function getTimestamp(): ?\DateTimeInterface { return $this->timestamp; }
    public function setTimestamp(\DateTimeInterface $timestamp): static { $this->timestamp = $timestamp; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
}
