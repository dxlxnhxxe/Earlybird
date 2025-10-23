import { useEffect, useState, useCallback } from 'react'
import { fetchClocks, type ApiClock } from './fetch-clocks'

export function useClocks() {
  const [clocks, setClocks] = useState<ApiClock[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const refetch = useCallback(() => {
    setLoading(true)
    setError(null)
    fetchClocks()
      .then((apiClocks) => {
        setClocks(apiClocks)
      })
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    refetch()
  }, [refetch])

  return { clocks, loading, error, refetch }
}
