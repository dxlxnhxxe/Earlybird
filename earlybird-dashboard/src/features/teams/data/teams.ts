import { useEffect, useState } from 'react'
import { fetchTeams, type ApiTeam } from './fetch-teams'

export function useTeams() {
  const [teams, setTeams] = useState<ApiTeam[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refetch = () => {
    setLoading(true)
    setError(null)
    fetchTeams()
      .then((data) => {
        setTeams(data)
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : 'Failed to fetch teams')
      })
      .finally(() => {
        setLoading(false)
      })
  }

  useEffect(() => {
    refetch()
  }, [])

  return { teams, loading, error, refetch }
}
