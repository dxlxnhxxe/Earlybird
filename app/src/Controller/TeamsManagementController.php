<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Entity\TeamMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TeamsManagementController extends AbstractController
{
    // ✅ Route POST /teams pour créer une équipe
    #[Route('/teams', name: 'team_create', methods: ['POST'])]
    public function createTeam(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Décoder le corps JSON
        $data = json_decode($request->getContent(), true);

        // Vérifier si le JSON est valide
        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid or missing JSON body'], 400);
        }

        if (empty($data['name']) || empty($data['manager_id'])) {
            return new JsonResponse(['error' => 'Name and manager_id are required'], 400);
        }

        // ✅ Récupérer le manager (One-to-One)
        $manager = $em->getRepository(User::class)->find($data['manager_id']);
        if (!$manager) {
            return new JsonResponse(['error' => 'Manager not found'], 404);
        }

        // ✅ Créer une nouvelle équipe
        $team = new Team();
        $team->setName($data['name']);
        $team->setDescription($data['description'] ?? null);
        $team->setManager($manager);


        // ✅ Ajouter les membres (TeamMember avec start_time/end_time)
        if (!empty($data['members']) && is_array($data['members'])) {
            foreach ($data['members'] as $memberData) {
                if (!isset($memberData['user_id'])) continue;
                $user = $em->getRepository(User::class)->find($memberData['user_id']);
                if ($user) {
                    $teamMember = new TeamMember();
                    $teamMember->setTeam($team);
                    $teamMember->setUser($user);
                    if (isset($memberData['start_time'])) {
                        $teamMember->setStartTime(\DateTime::createFromFormat('H:i', $memberData['start_time']));
                    }
                    if (isset($memberData['end_time'])) {
                        $teamMember->setEndTime(\DateTime::createFromFormat('H:i', $memberData['end_time']));
                    }
                    $team->addMembership($teamMember);
                    $em->persist($teamMember);
                }
            }
        }

        $em->persist($team);
        $em->flush();

        // ✅ Construire la réponse JSON

        $members = [];
        foreach ($team->getMemberships() as $membership) {
            $user = $membership->getUser();
            $members[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'start_time' => $membership->getStartTime() ? $membership->getStartTime()->format('H:i') : null,
                'end_time' => $membership->getEndTime() ? $membership->getEndTime()->format('H:i') : null
            ];
        }

        return new JsonResponse([
            'status' => 'Team created successfully',
            'team' => [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'description' => $team->getDescription(),
                'manager' => [
                    'id' => $manager->getId(),
                    'firstname' => $manager->getFirstname(),
                    'lastname' => $manager->getLastname(),
                    'email' => $manager->getEmail()
                ],
                'members' => $members
            ]
        ], 201);
    }

    // ✅ Route GET /teams pour lister les équipes
    #[Route('/teams', name: 'team_list', methods: ['GET'])]
    public function listTeams(EntityManagerInterface $em): JsonResponse
    {
        $teams = $em->getRepository(Team::class)->findAll();

        $data = [];
        foreach ($teams as $team) {
            $members = [];
            foreach ($team->getMemberships() as $membership) {
                $user = $membership->getUser();
                $members[] = [
                    'id' => $user->getId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                    'start_time' => $membership->getStartTime() ? $membership->getStartTime()->format('H:i') : null,
                    'end_time' => $membership->getEndTime() ? $membership->getEndTime()->format('H:i') : null
                ];
            }

            $manager = $team->getManager();

            $data[] = [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'description' => $team->getDescription(),
                'manager' => $manager ? [
                    'id' => $manager->getId(),
                    'firstname' => $manager->getFirstname(),
                    'lastname' => $manager->getLastname(),
                    'email' => $manager->getEmail()
                ] : null,
                'members' => $members
            ];
        }

        return new JsonResponse($data, 200);
    }

    #[Route('/teams/{id}', name: 'team_detail', methods: ['GET'])]
    public function getTeam(int $id, EntityManagerInterface $em): JsonResponse
    {
        $team = $em->getRepository(Team::class)->find($id);
        if (!$team) {
            return new JsonResponse(['error' => 'Team not found'], 404);
        }
        $members = [];
        foreach ($team->getMemberships() as $membership) {
            $user = $membership->getUser();
            $members[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'start_time' => $membership->getStartTime() ? $membership->getStartTime()->format('H:i') : null,
                'end_time' => $membership->getEndTime() ? $membership->getEndTime()->format('H:i') : null
            ];
        }
        $manager = $team->getManager();
        $data = [
            'id' => $team->getId(),
            'name' => $team->getName(),
            'description' => $team->getDescription(),
            'manager' => $manager ? [
                'id' => $manager->getId(),
                'firstname' => $manager->getFirstname(),
                'lastname' => $manager->getLastname(),
                'email' => $manager->getEmail()
            ] : null,
            'members' => $members
        ];
        return new JsonResponse($data, 200);
    }

    // ✅ Route PUT /teams/{id} pour modifier une équipe
    #[Route('/teams/{id}', name: 'team_update', methods: ['PUT'])]
    public function updateTeam(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $team = $em->getRepository(Team::class)->find($id);

        if (!$team) {
            return new JsonResponse(['error' => 'Team not found'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return new JsonResponse(['error' => 'Invalid or missing JSON body'], 400);
        }

        // ✅ Mise à jour dynamique
        if (isset($data['name'])) {
            $team->setName($data['name']);
        }

        if (isset($data['description'])) {
            $team->setDescription($data['description']);
        }

        if (isset($data['manager_id'])) {
            $manager = $em->getRepository(User::class)->find($data['manager_id']);
            if ($manager) {
                $team->setManager($manager);
            }
        }

        if (isset($data['members']) && is_array($data['members'])) {
            // Remove all existing memberships
            foreach ($team->getMemberships() as $existingMembership) {
                $em->remove($existingMembership);
            }
            // Add new memberships
            foreach ($data['members'] as $memberData) {
                if (!isset($memberData['user_id'])) continue;
                $user = $em->getRepository(User::class)->find($memberData['user_id']);
                if ($user) {
                    $teamMember = new TeamMember();
                    $teamMember->setTeam($team);
                    $teamMember->setUser($user);
                    if (isset($memberData['start_time'])) {
                        $teamMember->setStartTime(\DateTime::createFromFormat('H:i', $memberData['start_time']));
                    }
                    if (isset($memberData['end_time'])) {
                        $teamMember->setEndTime(\DateTime::createFromFormat('H:i', $memberData['end_time']));
                    }
                    $team->addMembership($teamMember);
                    $em->persist($teamMember);
                }
            }
        }

        $em->flush();

        return new JsonResponse(['status' => 'Team updated successfully'], 200);
    }

    // ✅ Route DELETE /teams/{id} pour supprimer une équipe
    #[Route('/teams/{id}', name: 'team_delete', methods: ['DELETE'])]
public function deleteTeam(int $id, EntityManagerInterface $em): JsonResponse
{
    // 🔍 Chercher la team par son ID
    $team = $em->getRepository(Team::class)->find($id);

    if (!$team) {
        return new JsonResponse(['error' => 'Team not found'], 404);
    }

    // 🗑️ Supprimer la team
    $em->remove($team);
    $em->flush();

    return new JsonResponse(['status' => 'Team deleted successfully'], 200);
}
}
