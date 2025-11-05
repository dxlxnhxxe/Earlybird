<?php

namespace App\Controller;

use OpenApi\Annotations as OA;
use App\Entity\Clock;
use App\Entity\User;
use App\Entity\Team;
use App\Entity\TeamMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ClockController extends AbstractController
{
    /**
     * Create a clock entry
     *
     * @OA\Post(
     *     path="/clocks",
     *     summary="Create a clock entry",
     *     consumes={"application/json"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id", "team_id", "type"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="team_id", type="integer", example=2),
     *             @OA\Property(property="type", type="string", enum={"arrival", "departure"}, example="arrival")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Clock recorded successfully"),
     *     @OA\Response(response=400, description="Missing user_id, team_id or type"),
     *     @OA\Response(response=404, description="User or Team not found")
     * )
     */
    #[Route('/clocks', name: 'clock_create', methods: ['POST'])]
    public function createClock(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['user_id']) || empty($data['team_id']) || empty($data['type'])) {
            return new JsonResponse(['error' => 'Missing user_id, team_id or type'], 400);
        }

        if (!in_array($data['type'], ['arrival', 'departure'])) {
            return new JsonResponse(['error' => 'Type must be "arrival" or "departure"'], 400);
        }

        // Vérifier que l'utilisateur et la team existent
        $user = $em->getRepository(User::class)->find($data['user_id']);
        $team = $em->getRepository(Team::class)->find($data['team_id']);
        if (!$user || !$team) {
            return new JsonResponse(['error' => 'User or Team not found'], 404);
        }

        // Trouver le TeamMember correspondant
        $teamMember = $em->getRepository(TeamMember::class)->findOneBy([
            'user' => $user,
            'team' => $team
        ]);
        if (!$teamMember) {
            return new JsonResponse(['error' => 'User is not a member of this team'], 404);
        }

        //Prevent 2 consecutives arrivals or departures
        $lastClock = $em->getRepository(Clock::class)->findOneBy(
            ['teamMember' => $teamMember],
            ['timestamp' => 'DESC']
        );
        if ($data['type'] === 'departure') {
            if (!$lastClock || $lastClock->getType() !== 'arrival') {
                return new JsonResponse(['error' => 'Cannot Register a departure without a previous arrival'], 400);
            }
        }
        if ($data['type'] === 'arrival' && $lastClock && $lastClock->getType() === 'arrival'){
            return new JsonResponse(['error' => 'Cannot Register an arrival without a previous departure. Please ask your manager for assistant if a mistake was made.'], 400);
        }

        // Créer un enregistrement Clock
        $clock = new Clock();
        $clock->setTeamMember($teamMember);
        $clock->setType($data['type']);
        $clock->setTimestamp(new \DateTime());

        $em->persist($clock);
        $em->flush();

        return new JsonResponse([
            'status' => 'Clock recorded successfully',
            'clock' => [
                'id' => $clock->getId(),
                'user' => $user->getFirstname() . ' ' . $user->getLastname(),
                'team' => $team->getName(),
                'type' => $clock->getType(),
                'timestamp' => $clock->getTimestamp()->format('d-m-Y H:i:s')
            ]
        ], 201);
    }

    /**
     * Get all clocks
     *
     * @OA\Get(
     *     path="/clocks/all",
     *     summary="Get all clock entries",
     *     @OA\Parameter(
     *         name="team_id",
     *         in="query",
     *         description="Filter clocks by team ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of all clocks")
     * )
     */
    #[Route('/clocks/all', name: 'clocks_all', methods: ['GET'])]
    public function getAllClocks(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $teamId = $request->query->get('team_id');

        if ($teamId) {
            // Filter by team - get all team members for this team and their clocks
            $teamMembers = $em->getRepository(TeamMember::class)->findBy(['team' => $teamId]);
            $teamMemberIds = array_map(function($teamMember) {
                return $teamMember->getId();
            }, $teamMembers);

            if (empty($teamMemberIds)) {
                return new JsonResponse(['clocks' => []], 200);
            }

            $clocks = $em->getRepository(Clock::class)->findBy(
                ['teamMember' => $teamMemberIds],
                ['timestamp' => 'DESC']
            );
        } else {
            // Get all clocks (admin view)
            $clocks = $em->getRepository(Clock::class)->findBy([], ['timestamp' => 'DESC']);
        }

        $data = array_map(function (Clock $clock) {
            $teamMember = $clock->getTeamMember();
            $user = $teamMember->getUser();
            $team = $teamMember->getTeam();

            return [
                'id' => $clock->getId(),
                'timestamp' => $clock->getTimestamp()->format('d-m-Y H:i:s'),
                'type' => $clock->getType(),
                'user' => [
                    'id' => $user->getId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'email' => $user->getEmail(),
                ],
                'team' => [
                    'id' => $team->getId(),
                    'name' => $team->getName(),
                ],
            ];
        }, $clocks);

        return new JsonResponse(['clocks' => $data], 200);
    }

    // ✅ GET /clocks?user_id=...&team_id=...
    #[Route('/clocks', name: 'user_team_clocks', methods: ['GET'])]
    public function getUserTeamClocks(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user_id = $request->query->get('user_id');
        $team_id = $request->query->get('team_id');
        if (!$user_id || !$team_id) {
            return new JsonResponse(['error' => 'Missing user_id or team_id query parameter'], 400);
        }
        $user = $em->getRepository(User::class)->find($user_id);
        $team = $em->getRepository(Team::class)->find($team_id);
        if (!$user || !$team) {
            return new JsonResponse(['error' => 'User or Team not found'], 404);
        }
        $teamMember = $em->getRepository(TeamMember::class)->findOneBy([
            'user' => $user,
            'team' => $team
        ]);
        if (!$teamMember) {
            return new JsonResponse(['error' => 'User is not a member of this team'], 404);
        }
        $clocks = $em->getRepository(Clock::class)->findBy(['teamMember' => $teamMember], ['timestamp' => 'DESC']);
        $data = array_map(function (Clock $clock) use ($team) {
            return [
                'id' => $clock->getId(),
                'timestamp' => $clock->getTimestamp()->format('d-m-Y H:i:s'),
                'type' => $clock->getType(),
                'team' => $team->getName(),
            ];
        }, $clocks);
        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
            ],
            'team' => [
                'id' => $team->getId(),
                'name' => $team->getName(),
            ],
            'clocks' => $data,
        ], 200);
    }

    #[Route('/clocks/{id}', name: 'clock_delete', methods: ['DELETE'])]
    public function deleteClock(int $id, EntityManagerInterface $em): JsonResponse
    {
        $clock = $em->getRepository(Clock::class)->find($id);
        if (!$clock) {
            return new JsonResponse(['error' => 'Clock not found'], 404);
        }
        $em->remove($clock);
        $em->flush();
        return new JsonResponse(['status' => 'Clock deleted successfully'], 200);
    }

    #[Route('/clocks/{id}', name: 'clock_update', methods: ['PUT'])]
    public function updateClock(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $clock = $em->getRepository(Clock::class)->find($id);
        if (!$clock) {
            return new JsonResponse(['error' => 'Clock not found'], 404);
        }
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            return new JsonResponse(['error' => 'Invalid or missing JSON body'], 400);
        }
        if (isset($data['type']) && in_array($data['type'], ['arrival', 'departure'])) {
            $clock->setType($data['type']);
        }
        if (isset($data['timestamp'])) {
            $dt = \DateTime::createFromFormat('d-m-Y H:i:s', $data['timestamp']);
            if ($dt) {
                $clock->setTimestamp($dt);
            }
        }
        $em->flush();
        return new JsonResponse(['status' => 'Clock updated successfully'], 200);
    }
}
