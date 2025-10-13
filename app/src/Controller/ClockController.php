<?php

namespace App\Controller;

use App\Entity\Clock;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ClockController extends AbstractController
{
    // ✅ POST /clocks
    #[Route('/clocks', name: 'clock_create', methods: ['POST'])]
    public function createClock(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['user_id']) || empty($data['type'])) {
            return new JsonResponse(['error' => 'Missing user_id or type'], 400);
        }

        if (!in_array($data['type'], ['arrival', 'departure'])) {
            return new JsonResponse(['error' => 'Type must be "arrival" or "departure"'], 400);
        }

        // Vérifier que l'utilisateur existe
        $user = $em->getRepository(User::class)->find($data['user_id']);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Créer un enregistrement Clock
        $clock = new Clock();
        $clock->setUser($user);
        $clock->setType($data['type']);
        $clock->setTimestamp(new \DateTime());

        $em->persist($clock);
        $em->flush();

        return new JsonResponse([
            'status' => 'Clock recorded successfully',
            'clock' => [
                'id' => $clock->getId(),
                'user' => $user->getFirstname() . ' ' . $user->getLastname(),
                'type' => $clock->getType(),
                'timestamp' => $clock->getTimestamp()->format('Y-m-d H:i:s')
            ]
        ], 201);
    }

    // ✅ GET /users/{id}/clocks
    #[Route('/users/{id}/clocks', name: 'user_clocks', methods: ['GET'])]
    public function getUserClocks(int $id, EntityManagerInterface $em): JsonResponse
    {
        // 1️⃣ Récupérer l’utilisateur
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // 2️⃣ Récupérer les clocks associées
        $clocks = $em->getRepository(Clock::class)->findBy(['user' => $user], ['timestamp' => 'DESC']);

        // 3️⃣ Formater la réponse JSON
        $data = array_map(function (Clock $clock) {
            return [
                'id' => $clock->getId(),
                'timestamp' => $clock->getTimestamp()->format('Y-m-d H:i:s'),
                'type' => $clock->getType(),
            ];
        }, $clocks);

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
            ],
            'clocks' => $data,
        ], 200);
    }
}
