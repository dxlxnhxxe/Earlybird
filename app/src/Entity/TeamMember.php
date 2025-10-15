<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Team;
use App\Entity\User;

#[ORM\Entity]
#[ORM\Table(name: 'team_member')]
class TeamMember
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private ?User $user = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'time', nullable: true)]
    private ?\DateTimeInterface $endTime = null;

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(?\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;
        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(?\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;
        return $this;
    }
}
