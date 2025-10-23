// Fetch real teams from your backend API
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

export type CreateTeamPayload = {
  name: string
  description?: string
  manager_id?: number
  members?: Array<{
    user_id: number
    start_time?: string
    end_time?: string
  }>
}

export async function createTeam(payload: CreateTeamPayload): Promise<ApiTeam> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/teams`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to create team: ${error}`)
  }
  const data = await res.json()
  return data.team || data
}

export type UpdateTeamPayload = {
  name?: string
  description?: string
  manager_id?: number
  members?: Array<{
    user_id: number
    start_time?: string
    end_time?: string
  }>
}

export async function updateTeam(
  id: number,
  payload: UpdateTeamPayload
): Promise<void> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/teams/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to update team: ${error}`)
  }
}

export async function deleteTeam(id: number): Promise<void> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/teams/${id}`, {
    method: 'DELETE',
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to delete team: ${error}`)
  }
}
