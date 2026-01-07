import { create } from 'zustand'
import { getCookie, setCookie, removeCookie } from '@/lib/cookies'

const ACCESS_TOKEN = 'thisisjustarandomstring'

interface AuthUser {
  id?: number
  firstname?: string
  lastname?: string
  email: string
  roles: string[]
  accountNo?: string
  exp?: number
}

interface AuthState {
  auth: {
    user: AuthUser | null
    setUser: (user: AuthUser | null) => void
    accessToken: string
    setAccessToken: (accessToken: string) => void
    resetAccessToken: () => void
    reset: () => void
    getDisplayName: () => string
    getInitials: () => string
    isAuthenticated: () => boolean
    hasRole: (role: string) => boolean
    isAdmin: () => boolean
  }
}

export const useAuthStore = create<AuthState>()((set, get) => {
  const cookieState = getCookie(ACCESS_TOKEN)
  const initToken = cookieState ? JSON.parse(cookieState) : ''
  return {
    auth: {
      user: null,
      setUser: (user) =>
        set((state) => ({ ...state, auth: { ...state.auth, user } })),
      accessToken: initToken,
      setAccessToken: (accessToken) =>
        set((state) => {
          setCookie(ACCESS_TOKEN, JSON.stringify(accessToken))
          return { ...state, auth: { ...state.auth, accessToken } }
        }),
      resetAccessToken: () =>
        set((state) => {
          removeCookie(ACCESS_TOKEN)
          return { ...state, auth: { ...state.auth, accessToken: '' } }
        }),
      reset: () =>
        set((state) => {
          removeCookie(ACCESS_TOKEN)
          return {
            ...state,
            auth: { ...state.auth, user: null, accessToken: '' },
          }
        }),

      getDisplayName: () => {
        const { user } = get().auth
        if (!user) return 'Guest'
        if (user.firstname && user.lastname) {
          return `${user.firstname} ${user.lastname}`
        }
        return user.email
      },
      getInitials: () => {
        const { user } = get().auth
        if (!user) return 'G'
        if (user.firstname && user.lastname) {
          return `${user.firstname[0]}${user.lastname[0]}`.toUpperCase()
        }
        return user.email[0].toUpperCase()
      },
      isAuthenticated: () => {
        const { user, accessToken } = get().auth
        return !!user && !!accessToken
      },
      hasRole: (role: string) => {
        const { user } = get().auth
        if (!user || !user.roles) return false
        return user.roles.includes(role)
      },
      isAdmin: () => {
        return get().auth.hasRole('admin') || get().auth.hasRole('ROLE_ADMIN')
      },
    },
  }
})
