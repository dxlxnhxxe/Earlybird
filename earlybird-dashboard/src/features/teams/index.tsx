import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { TeamsDialogs } from './components/teams-dialogs'
import { TeamsPrimaryButtons } from './components/teams-primary-buttons'
import { TeamsProvider } from './components/teams-provider'
import { TeamsTable } from './components/teams-table'
import { useTeams } from './data/teams'
import { Loader2 } from 'lucide-react'

export function Teams() {
  const { teams, loading, error, refetch } = useTeams()

  return (
    <TeamsProvider refetch={refetch}>
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
            <h2 className='text-2xl font-bold tracking-tight'>Teams</h2>
            <p className='text-muted-foreground'>
              Manage your teams and their members here.
            </p>
          </div>
          <TeamsPrimaryButtons />
        </div>
        {loading ? (
          <div className='flex h-32 items-center justify-center'>
            <Loader2 className='mr-2 h-4 w-4 animate-spin' />Loading teams
          </div>
        ) : error ? (
          <div className='flex h-32 items-center justify-center text-red-500'>
            {error}
          </div>
        ) : (
          <TeamsTable data={teams} />
        )}
      </Main>

      <TeamsDialogs />
    </TeamsProvider>
  )
}
