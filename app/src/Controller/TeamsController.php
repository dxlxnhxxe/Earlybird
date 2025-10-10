<?php

namespace App\Controller;

use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class TeamsController
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

        // Vérifier les champs obligatoires
        if (empty($data['name']) || empty($data['manager'])) {
            return new JsonResponse(['error' => 'Name and manager are required'], 400);
        }

        // Créer une nouvelle entité Team
        $team = new Team();
        $team->setName($data['name'])
            ->setDescription($data['description'] ?? '')
            ->setMembers($data['members'] ?? [])
            ->setManager($data['manager']);

        // Enregistrer en base
        $em->persist($team);
        $em->flush();

        return new JsonResponse([
            'status' => 'Team created successfully',
            'team' => [
                'id' => $team->getId(),
                'name' => $team->getName(),
                'description' => $team->getDescription(),
                'members' => $team->getMembers(),
                'manager' => $team->getManager()
            ]
        ], 201);
    }
}
