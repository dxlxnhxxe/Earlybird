// API fetcher for team-averages
export type ApiTeam = {
  id: number
  name: string
  average_work_time_seconds: number
  average_work_time: string
  members_count: number
}

export type ApiOverview = {
  teams: ApiTeam[]
  since?: string
  until?: string
  period?: string
}

export async function fetchTeamAverages(period: string = 'day'): Promise<ApiOverview> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  const res = await fetch(`${baseUrl}/team-averages?period=${encodeURIComponent(period)}`)
  if (!res.ok) throw new Error('Failed to fetch team averages')
  return res.json()
}
