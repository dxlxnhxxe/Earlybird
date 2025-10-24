import { type QueryClient } from '@tanstack/react-query'
import { createRootRouteWithContext, Outlet } from '@tanstack/react-router'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import { TanStackRouterDevtools } from '@tanstack/react-router-devtools'
import { Toaster } from '@/components/ui/sonner'
import { NavigationProgress } from '@/components/navigation-progress'
import { GeneralError } from '@/features/errors/general-error'
import { NotFoundError } from '@/features/errors/not-found-error'
import { useTeamStore } from '@/stores/team-store'
import { useEffect } from 'react'

function App() {
  const { fetchAndInitializeTeams } = useTeamStore()

  useEffect(() => {
    // Initialize teams when the app starts
    fetchAndInitializeTeams()
  }, [fetchAndInitializeTeams])

  return (
    <>
      <NavigationProgress />
      <Outlet />
      <Toaster duration={5000} />
      {import.meta.env.MODE === 'development' && (
        <>
          <ReactQueryDevtools buttonPosition='bottom-left' />
          <TanStackRouterDevtools position='bottom-right' />
        </>
      )}
    </>
  )
}

export const Route = createRootRouteWithContext<{
  queryClient: QueryClient
}>()({
  component: App,
  notFoundComponent: NotFoundError,
  errorComponent: GeneralError,
})
