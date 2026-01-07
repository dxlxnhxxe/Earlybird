<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenManagerInterface $refreshTokenManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $payload = [];

        // Validation des champs requis
        if (!$email || !$password) {
            $payload = ['error' => 'Email et mot de passe requis'];
            $status = 400;
        }
        // Validation du format email
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $payload = ['error' => 'Format d\'email invalide'];
            $status = 400;
        }
        // Validation de la longueur du mot de passe
        elseif (strlen($password) < 1 || strlen($password) > 4096) {
            $payload = ['error' => 'Mot de passe invalide'];
            $status = 400;
        } else {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            // Vérification du mot de passe (en clair pour le développement)
            if (!$user || $user->getPassword() !== $password) {
                // Message générique pour ne pas révéler si l'utilisateur existe
                $payload = ['error' => 'Email ou mot de passe incorrect'];
                $status = 401;
            } else {
                // Génération du token JWT
                $accessToken = $jwtManager->create($user);

                // Création d’un nouveau refresh token
                $refreshToken = new RefreshToken();
                $refreshToken->setRefreshToken(bin2hex(random_bytes(32)));
                $refreshToken->setUsername($user->getUserIdentifier());
                $refreshToken->setValid((new \DateTime())->modify('+30 days'));
                $refreshTokenManager->save($refreshToken);

                $payload = [
                    'message' => 'Authentification réussie',
                    'token' => $accessToken,
                    'refresh_token' => $refreshToken->getRefreshToken(),
                    'user' => [
                        'id' => $user->getId(),
                        'firstname' => $user->getFirstname(),
                        'lastname' => $user->getLastname(),
                        'email' => $user->getEmail(),
                        'roles' => $user->getRoles(),
                    ]
                ];
                $status = 200;
            }
        }

        return new JsonResponse($payload, $status);
    }

    #[Route('/token/refresh', name: 'token_refresh', methods: ['POST'])]
    public function refreshToken(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenManagerInterface $refreshTokenManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $refreshTokenString = $data['refresh_token'] ?? null;

        if (!$refreshTokenString) {
            return new JsonResponse(['error' => 'Refresh token manquant'], 400);
        }

        /** @var RefreshToken|null $oldToken */
        $oldToken = $refreshTokenManager->get($refreshTokenString);

        // Récupérer l’utilisateur associé si le token existe
        $user = null;
        if ($oldToken) {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $oldToken->getUsername()]);
        }

        // Si le token est invalide/expiré ou l'utilisateur est introuvable, renvoyer une erreur
        if (!$oldToken || !$oldToken->isValid() || !$user) {
            return new JsonResponse(['error' => 'Refresh token invalide ou expiré ou utilisateur introuvable'], 401);
        }

        // Générer un nouveau token JWT
        $newAccessToken = $jwtManager->create($user);

        // Invalider l’ancien refresh token (single-use)
        $refreshTokenManager->delete($oldToken);

        // Générer un nouveau refresh token
        $newRefreshToken = new RefreshToken();
        $newRefreshToken->setRefreshToken(bin2hex(random_bytes(32)));
        $newRefreshToken->setUsername($user->getUserIdentifier());
        $newRefreshToken->setValid((new \DateTime())->modify('+30 days'));
        $refreshTokenManager->save($newRefreshToken);

        return new JsonResponse([
            'message' => 'Nouveau token généré avec succès',
            'token' => $newAccessToken,
            'refresh_token' => $newRefreshToken->getRefreshToken(),
        ]);
    }
}
