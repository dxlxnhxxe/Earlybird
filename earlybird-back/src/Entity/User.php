<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use App\Entity\Team;
use App\Entity\TeamMember;

/**
 * Simple embeddable Profile value object so the embedded mapping in User works.
 */
#[ORM\Embeddable]

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(name: 'phone_number', length: 255)]
    private ?string $phoneNumber = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $role = 'ROLE_EMPLOYEE';

    #[ORM\Column(name: 'code_pin', nullable: true)]
    private ?int $codePin = null;

    // Un manager peut gérer plusieurs teams
    #[ORM\OneToMany(mappedBy: 'manager', targetEntity: Team::class)]
    private Collection $managedTeams;

    // Un user peut être membre de plusieurs teams (via TeamMember, qui porte
    // aussi start_time/end_time -- une vraie ManyToMany ne pourrait pas stocker ça)
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: TeamMember::class, cascade: ['persist'])]
    private Collection $teamMemberships;

    public function __construct()
    {
        $this->teamMemberships = new ArrayCollection();
        $this->managedTeams = new ArrayCollection();
    }

    // === Getters / Setters ===

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;
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

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role ?? 'ROLE_EMPLOYEE';
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getCodePin(): ?int
    {
        return $this->codePin;
    }

    public function setCodePin(?int $codePin): static
    {
        $this->codePin = $codePin;
        return $this;
    }

    // === Relations avec Team ===

    /** @return Collection<int, Team> */
    public function getManagedTeams(): Collection
    {
        return $this->managedTeams;
    }

    public function addManagedTeam(Team $team): static
    {
        if (!$this->managedTeams->contains($team)) {
            $this->managedTeams->add($team);
            $team->setManager($this);
        }
        return $this;
    }

    /** @return Collection<int, TeamMember> */
    public function getTeamMemberships(): Collection
    {
        return $this->teamMemberships;
    }

    /** @return Collection<int, Team> the teams this user belongs to, derived from their memberships */
    public function getTeams(): Collection
    {
        return new ArrayCollection(
            array_values(array_unique(
                array_map(fn (TeamMember $m) => $m->getTeam(), $this->teamMemberships->toArray()),
                SORT_REGULAR
            ))
        );
    }

    public function addTeamMembership(TeamMember $teamMember): static
    {
        if (!$this->teamMemberships->contains($teamMember)) {
            $this->teamMemberships->add($teamMember);
            $teamMember->setUser($this);
        }
        return $this;
    }

    // === Méthodes requises par UserInterface ===

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        // Convertir les rôles de base de données vers les rôles Symfony
        $role = match($this->role) {
            'admin' => 'ROLE_ADMIN',
            'manager' => 'ROLE_MANAGER',
            'user' => 'ROLE_USER',
            default => 'ROLE_USER' // Rôle par défaut
        };
        
        return array_unique([$role]);
    }

    public function eraseCredentials(): void
    {
        /* This method is intentionally left blank because this User entity
           does not hold any temporary or sensitive credentials (e.g. plain
           text passwords) that need to be cleared after authentication.
           If you later add such transient properties (for example
           $this->plainPassword), clear them here, e.g.:
           $this->plainPassword = null;
        */
    }

    public function getSalt(): ?string
    {
        return null;
    }
}
