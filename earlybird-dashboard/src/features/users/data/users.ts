
import { useEffect, useState, useCallback } from 'react'
import { fetchUsers } from './fetch-users'
import type { User } from './schema'

export function useUsers() {
  const [users, setUsers] = useState<User[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refetch = useCallback(() => {
    setLoading(true)
    setError(null)
    fetchUsers()
      .then((apiUsers) => {
        const mapped = apiUsers.map((u) => ({
          id: String(u.id),
          firstName: u.firstname || '',
          lastName: u.lastname || '',
          email: u.email || '',
          phoneNumber: u.phone_number || '',
          codePin: u.code_pin || 0,
          status: (u.status as User['status']) || 'active',
          role: (u.role as User['role']) || 'user',
          createdAt: u.createdAt ? new Date(u.createdAt) : new Date(),
          updatedAt: u.updatedAt ? new Date(u.updatedAt) : new Date(),
        }))
        setUsers(mapped)
      })
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    refetch()
  }, [refetch])

  return { users, loading, error, refetch }
}
