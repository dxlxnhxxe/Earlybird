// Fetch teams from your backend API
export type ApiTeam = {
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

export async function fetchTeams(): Promise<ApiTeam[]> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/teams`)
  if (!res.ok) throw new Error('Failed to fetch teams')
  return await res.json()
}
