import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { TasksDialogs } from './components/tasks-dialogs'
import { TasksPrimaryButtons } from './components/tasks-primary-buttons'
import { TasksProvider } from './components/tasks-provider'
import { TasksTable } from './components/tasks-table'
import { useClocks } from './data/clocks'
import { useTeamStore } from '@/stores/team-store'
import { Loader2 } from 'lucide-react'

export function Tasks() {
  const { selectedTeam, isAdminTeam } = useTeamStore()
  const teamId = isAdminTeam ? undefined : selectedTeam?.id
  const { clocks, loading, error, refetch } = useClocks(teamId)

  return (
    <TasksProvider refetch={refetch}>
      <Header fixed>
        <Search />
        <div className='ms-auto flex items-center space-x-4'>
          <ThemeSwitch />
          <ConfigDrawer />
          <ProfileDropdown />
        </div>
      </Header>

      <Main className='flex flex-1 flex-col gap-4 sm:gap-6'>
        <div className='flex flex-wrap items-end justify-between gap-2'>
          <div>
            <h2 className='text-2xl font-bold tracking-tight'>Clocks</h2>
            <p className='text-muted-foreground'>
              Here&apos;s a list of all clock entries (arrivals and departures).
              {selectedTeam && !isAdminTeam && (
                <span className='block text-sm font-medium text-primary'>
                  Showing clocks from: {selectedTeam.name}
                </span>
              )}
              {isAdminTeam && (
                <span className='block text-sm font-medium text-primary'>
                  Admin view: Showing all clocks
                </span>
              )}
            </p>
          </div>
          <TasksPrimaryButtons />
        </div>
        {loading ? (
          <div className="flex justify-center items-center h-32"><Loader2 className='mr-2 h-4 w-4 animate-spin' />Loading clocks</div>
        ) : error ? (
          <div className="flex justify-center items-center h-32 text-red-500">{error}</div>
        ) : (
          <TasksTable data={clocks} />
        )}
      </Main>

      <TasksDialogs />
    </TasksProvider>
  )
}
