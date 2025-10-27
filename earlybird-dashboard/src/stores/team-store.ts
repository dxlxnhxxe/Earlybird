import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { fetchTeams } from '@/features/teams/data/fetch-teams'

export interface Team {
  id: number
  name: string
  description: string | null
  manager: {
    id: number
    firstname: string
    lastname: string
    email: string
  } | null
  members: Array<{
    id: number
    firstname: string
    lastname: string
    email: string
    start_time: string | null
    end_time: string | null
  }>
}

interface TeamState {
  selectedTeam: Team | null
  availableTeams: Team[]
  isAdminTeam: boolean
  isLoading: boolean
  setSelectedTeam: (team: Team | null) => void
  setAvailableTeams: (teams: Team[]) => void
  setIsAdminTeam: (isAdmin: boolean) => void
  initializeTeams: (teams: Team[]) => void
  fetchAndInitializeTeams: () => Promise<void>
  reset: () => void
}

const ADMIN_TEAM: Team = {
  id: -1,
  name: 'Admin Team',
  description: 'Administrative team with access to all data',
  manager: null,
  members: []
}

export const useTeamStore = create<TeamState>()(
  persist(
    (set, get) => ({
      selectedTeam: null,
      availableTeams: [ADMIN_TEAM],
      isAdminTeam: false,
      isLoading: false,
      setSelectedTeam: (team: Team | null) =>
        set(() => ({
          selectedTeam: team,
          isAdminTeam: team?.id === ADMIN_TEAM.id
        })),
      setAvailableTeams: (teams: Team[]) =>
        set(() => ({
          availableTeams: [ADMIN_TEAM, ...teams]
        })),
      setIsAdminTeam: (isAdmin: boolean) =>
        set(() => ({ isAdminTeam: isAdmin })),
      initializeTeams: (teams: Team[]) => {
        const currentState = get()
        set(() => ({
          availableTeams: [ADMIN_TEAM, ...teams],
          // If no team is selected, default to first available team
          selectedTeam: currentState.selectedTeam || ADMIN_TEAM,
          isAdminTeam: (currentState.selectedTeam || ADMIN_TEAM).id === ADMIN_TEAM.id
        }))
      },
      fetchAndInitializeTeams: async () => {
        const currentState = get()
        if (currentState.isLoading) return

        set(() => ({ isLoading: true }))
        try {
          const teams = await fetchTeams()
          const currentState = get()
          set(() => ({
            availableTeams: [ADMIN_TEAM, ...teams],
            selectedTeam: currentState.selectedTeam || ADMIN_TEAM,
            isAdminTeam: (currentState.selectedTeam || ADMIN_TEAM).id === ADMIN_TEAM.id,
            isLoading: false
          }))
        } catch (error) {
          console.error('Failed to fetch teams:', error)
          set(() => ({ isLoading: false }))
        }
      },
      reset: () =>
        set(() => ({
          selectedTeam: null,
          availableTeams: [ADMIN_TEAM],
          isAdminTeam: false,
          isLoading: false
        })),
    }),
    {
      name: 'team-storage',
      partialize: (state: TeamState) => ({
        selectedTeam: state.selectedTeam,
        isAdminTeam: state.isAdminTeam
      }),
    }
  )
)