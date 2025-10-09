<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class HomeController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }


    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return new Response('Hello, World!');
    }

    // Ajoutez cette méthode pour gérer la création d'un utilisateur
    #[Route('/user/create', name: 'user_create', methods: ['POST'])]
public function createUser(Request $request, EntityManagerInterface $em): JsonResponse
{
    // Décoder le JSON reçu
    $data = json_decode($request->getContent(), true);

    // Vérifier si le JSON est valide
    if ($data === null) {
        return new JsonResponse(['error' => 'Invalid or missing JSON body'], 400);
    }

    // Vérifier les champs obligatoires
    if (empty($data['email']) || empty($data['password'])) {
        return new JsonResponse(['error' => 'Email and password are required'], 400);
    }

    // Créer et remplir l'entité User
    $user = new User();
    $user->setFirstname($data['firstname'] ?? '')
        ->setLastname($data['lastname'] ?? '')
        ->setEmail($data['email'])
        ->setPhoneNumber($data['phone_number'] ?? '')
        ->setPassword(password_hash($data['password'], PASSWORD_BCRYPT))
        ->setRole($data['role'] ?? 'user')
        ->setCodePin($data['code_pin'] ?? 0);

    $em->persist($user);
    $em->flush();

    return new JsonResponse(['status' => 'User created successfully'], 201);
}

}
