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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AuthController extends AbstractController
{
    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $em,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenManagerInterface $refreshTokenManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        $payload = [];

        if (!$email || !$password) {
            $payload = ['error' => 'Email et mot de passe requis'];
            $status = 400;
        } else {
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                $payload = ['error' => 'Utilisateur introuvable'];
                $status = 401;
            } elseif (!$this->verifyPassword($user, $password, $passwordHasher)) {
                // Vérification hybride : hash ou mot de passe en clair
                $payload = ['error' => 'Identifiants invalides'];
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

    /**
     * Vérifie le mot de passe en détectant automatiquement le format
     * - Si le password envoyé ressemble à un hash, comparaison directe
     * - Sinon, vérification avec hachage via passwordHasher
     */
    private function verifyPassword(User $user, string $password, UserPasswordHasherInterface $passwordHasher): bool
    {
        // Détecter si le password ressemble à un hash bcrypt/argon2
        if ($this->looksLikeHash($password)) {
            // Comparaison directe avec le hash stocké
            return $user->getPassword() === $password;
        } else {
            // Vérification normale avec hachage du mot de passe en clair
            return $passwordHasher->isPasswordValid($user, $password);
        }
    }

    /**
     * Détermine si une chaîne ressemble à un hash de mot de passe
     */
    private function looksLikeHash(string $password): bool
    {
        // Patterns pour bcrypt ($2y$), argon2i ($argon2i$), argon2id ($argon2id$)
        return preg_match('/^(\$2[ayb]\$|\$argon2id?\$)/', $password) === 1;
    }
}
