// Fetch real clocks from your backend API
export type ApiClock = {
  id: number
  timestamp: string
  type: 'arrival' | 'departure'
  user: {
    id: number
    firstname: string
    lastname: string
    email: string
  }
  team: {
    id: number
    name: string
  }
}

export async function fetchClocks(): Promise<ApiClock[]> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/clocks/all`)
  if (!res.ok) throw new Error('Failed to fetch clocks')
  const data = await res.json()
  return data.clocks || []
}

export type CreateClockPayload = {
  user_id: number
  team_id: number
  type: 'arrival' | 'departure'
}

export async function createClock(payload: CreateClockPayload): Promise<ApiClock> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/clocks`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to create clock: ${error}`)
  }
  const data = await res.json()
  return data.clock
}

export type UpdateClockPayload = {
  type?: 'arrival' | 'departure'
  timestamp?: string
}

export async function updateClock(
  id: number,
  payload: UpdateClockPayload
): Promise<void> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/clocks/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to update clock: ${error}`)
  }
}

export async function deleteClock(id: number): Promise<void> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8080'
  const res = await fetch(`${baseUrl}/clocks/${id}`, {
    method: 'DELETE',
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to delete clock: ${error}`)
  }
}
