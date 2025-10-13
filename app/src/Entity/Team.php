<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
#[ORM\Table(name: '`team`')]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // ✅ One-to-One relation vers User (manager)
    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'managedTeam')]
    #[ORM\JoinColumn(nullable: true, unique: true, onDelete: 'SET NULL')]
    private ?User $manager = null;

    // ✅ Many-to-Many relation vers User (members)
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'teams')]
    #[ORM\JoinTable(name: 'team_member')]
    private Collection $members;

    public function __construct()
    {
        $this->members = new ArrayCollection();
    }

    // 🔹 Getters & Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getManager(): ?User
    {
        return $this->manager;
    }

    public function setManager(?User $manager): static
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $user): static
    {
        if (!$this->members->contains($user)) {
            $this->members->add($user);
            $user->addTeam($this);
        }

        return $this;
    }

    public function removeMember(User $user): static
    {
        if ($this->members->removeElement($user)) {
            $user->removeTeam($this);
        }

        return $this;
    }
}
