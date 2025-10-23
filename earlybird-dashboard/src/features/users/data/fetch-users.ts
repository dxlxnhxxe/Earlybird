// Fetch real users from your backend API
export type ApiUser = {
  id: number | string
  firstname: string
  lastname: string
  email: string
  phone_number?: string
  code_pin?: number
  status?: string
  role?: string
  createdAt?: string
  updatedAt?: string
}

export async function fetchUsers(): Promise<ApiUser[]> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  const res = await fetch(`${baseUrl}/users`)
  if (!res.ok) throw new Error('Failed to fetch users')
  return res.json()
}

export type CreateUserPayload = {
  firstname: string
  lastname: string
  email: string
  phone_number: string
  password: string
  role: string
}

export async function createUser(payload: CreateUserPayload): Promise<ApiUser> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  const res = await fetch(`${baseUrl}/users`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to create user: ${error}`)
  }
  return res.json()
}

export type UpdateUserPayload = {
  firstname?: string
  lastname?: string
  email?: string
  phone_number?: string
  password?: string
  role?: string
}

export async function updateUser(
  id: string | number,
  payload: UpdateUserPayload
): Promise<ApiUser> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  const res = await fetch(`${baseUrl}/users/${id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to update user: ${error}`)
  }
  return res.json()
}

export async function deleteUser(id: string | number): Promise<void> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  const res = await fetch(`${baseUrl}/users/${id}`, {
    method: 'DELETE',
  })
  if (!res.ok) {
    const error = await res.text()
    throw new Error(`Failed to delete user: ${error}`)
  }
}
