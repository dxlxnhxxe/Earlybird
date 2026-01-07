import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'

export interface AuthUser {
  id: number
  firstname: string
  lastname: string
  email: string
  roles: string[]
}

export interface LoginResponse {
  message: string
  token: string
  refresh_token: string
  user: AuthUser
}

export interface RefreshResponse {
  message: string
  token: string
  refresh_token: string
}

/**
 * Authentifie un utilisateur via email et mot de passe
 * @param email - Email de l'utilisateur
 * @param password - Mot de passe de l'utilisateur
 * @returns Promise contenant le token JWT, refresh token et les informations utilisateur
 */
export async function login(email: string, password: string): Promise<LoginResponse> {
  try {
    const response = await axios.post<LoginResponse>(`${API_BASE_URL}/login`, {
      email,
      password,
    })

    return response.data
  } catch (error) {
    // Gestion des erreurs spécifiques du backend
    if (axios.isAxiosError(error) && error.response) {
      const status = error.response.status
      const errorMsg = error.response.data?.error || 'Erreur lors de la connexion'

      if (status === 400) {
        throw new Error(errorMsg)
      } else if (status === 401) {
        // Messages spécifiques pour les erreurs d'authentification
        if (errorMsg.includes('introuvable')) {
          throw new Error('Utilisateur introuvable')
        }
        throw new Error('Email ou mot de passe incorrect')
      } else if (status >= 500) {
        throw new Error('Erreur serveur. Veuillez réessayer plus tard.')
      }
    }

    // Erreur réseau ou autre
    throw new Error('Impossible de se connecter au serveur')
  }
}

/**
 * Rafraîchit le token d'accès avec un refresh token
 * @param refresh_token - Le refresh token
 * @returns Promise contenant le nouveau token JWT et refresh token
 */
export async function refreshToken(refresh_token: string): Promise<RefreshResponse> {
  try {
    const response = await axios.post<RefreshResponse>(`${API_BASE_URL}/token/refresh`, {
      refresh_token,
    })

    return response.data
  } catch (error) {
    if (axios.isAxiosError(error) && error.response) {
      const status = error.response.status

      if (status === 400) {
        throw new Error('Refresh token manquant')
      } else if (status === 401) {
        throw new Error('Refresh token invalide ou expiré')
      }
    }

    throw new Error('Impossible de rafraîchir le token')
  }
}

/**
 * Déconnecte l'utilisateur (côté client)
 */
export async function logout(): Promise<void> {
  // Supprimer le refresh token du localStorage
  localStorage.removeItem('refresh_token_local')

  // Optionnel : appeler un endpoint de déconnexion côté serveur
  // await axios.post('/logout')
}
