<?php

namespace App\Controller;

use OpenApi\Annotations as OA;
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

    /**
     * Create a user
     *
     * @OA\Post(
     *     path="/users",
     *     summary="Create a user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="firstname", type="string"),
     *             @OA\Property(property="lastname", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone_number", type="string"),
     *             @OA\Property(property="password", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="code_pin", type="integer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User created successfully"),
     *     @OA\Response(response=400, description="Email and password are required")
     * )
     */
    #[Route('/users', name: 'user_create', methods: ['POST'])]
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

    /**
     * Delete a user
     *
     * @OA\Delete(
     *     path="/users/{id}",
     *     summary="Delete a user",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User deleted successfully"),
     *     @OA\Response(response=404, description="User to delete not found")
     * )
     */
    #[Route('/users/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user){
            return new JsonResponse(['error' => 'User to delete not found'], 404);
        }
        $em->remove($user);
        $em->flush();
        return new JsonResponse(['status' => 'User deleted successfully'], 200);
    }

    /**
     * List all users
     *
     * @OA\Get(
     *     path="/users",
     *     summary="List all users",
     *     @OA\Response(response=200, description="List of users")
     * )
     */
    #[Route('/users', name: 'user_display', methods: ['GET'])]
    public function displayUser(EntityManagerInterface $em): JsonResponse
    {
        $users = $em->getRepository(User::class)->findAll();
        $data = array_map(function ($user) {
            return [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'phone_number' => $user->getPhoneNumber(),
                'role' => $user->getRole(),
                'code_pin' => $user->getCodePin(),
            ];
        }, $users);
        return new JsonResponse($data, 200);
    }

    /**
     * Show a specific user
     *
     * @OA\Get(
     *     path="/users/{id}",
     *     summary="Show a specific user",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="User details"),
     *     @OA\Response(response=404, description="User not found")
     * )
     */
    #[Route('/users/{id}', name: 'user_show', methods: ['GET'])]
    public function showUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
        $data = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'phone_number' => $user->getPhoneNumber(),
            'role' => $user->getRole(),
            'code_pin' => $user->getCodePin(),
        ];
        return new JsonResponse($data, 200);
    }

    /**
     * Update a user
     *
     * @OA\Put(
     *     path="/users/{id}",
     *     summary="Update a user",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="firstname", type="string"),
     *             @OA\Property(property="lastname", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone_number", type="string"),
     *             @OA\Property(property="role", type="string"),
     *             @OA\Property(property="code_pin", type="integer"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="User updated successfully"),
     *     @OA\Response(response=404, description="User to update not found")
     * )
     */
    #[Route('/users/{id}', name: 'user_update', methods: ['PUT'])]
    public function updateUser(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user){
            return new JsonResponse(['error' => 'User to update not found'], 404);
        }
        $data = json_decode($request->getContent(), true);
        if(!$data){
            return new JsonResponse(['error' => 'Invalid or missing JSON body'], 400);
        }
        if(isset($data['firstname'])) $user->setFirstname($data['firstname']);
        if(isset($data['lastname'])) $user->setLastname($data['lastname']);
        if(isset($data['email'])) $user->setEmail($data['email']);
        if(isset($data['phone_number'])) $user->setPhoneNumber($data['phone_number']);
        if(isset($data['role'])) $user->setRole($data['role']);
        if(isset($data['code_pin'])) $user->setCodePin($data['code_pin']);
        if(isset($data['password'])) $user->setPassword(password_hash($data['password'], PASSWORD_BCRYPT));

        //Perist est optionel puisque $user est deja manage
        $em->flush();
        return new JsonResponse(['status' => 'User updated successfully'], 200);
    }

}
