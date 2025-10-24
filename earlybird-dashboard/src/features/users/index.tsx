import { getRouteApi } from '@tanstack/react-router'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { UsersDialogs } from './components/users-dialogs'
import { UsersPrimaryButtons } from './components/users-primary-buttons'
import { UsersProvider } from './components/users-provider'
import { UsersTable } from './components/users-table'
import { useUsers } from './data/users'
import { useTeamStore } from '@/stores/team-store'
import { Loader2 } from 'lucide-react'

const route = getRouteApi('/_authenticated/users/')

export function Users() {
  const search = route.useSearch()
  const navigate = route.useNavigate()
  const { selectedTeam, isAdminTeam } = useTeamStore()
  const teamId = isAdminTeam ? undefined : selectedTeam?.id
  const { users, loading, error, refetch } = useUsers(teamId)

  return (
    <UsersProvider refetch={refetch}>
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
            <h2 className='text-2xl font-bold tracking-tight'>User List</h2>
            <p className='text-muted-foreground'>
              Manage your users and their roles here.
              {selectedTeam && !isAdminTeam && (
                <span className='block text-sm font-medium text-primary'>
                  Showing users from: {selectedTeam.name}
                </span>
              )}
              {isAdminTeam && (
                <span className='block text-sm font-medium text-primary'>
                  Admin view: Showing all users
                </span>
              )}
            </p>
          </div>
          <UsersPrimaryButtons />
        </div>
        {loading ? (
          <div className="flex justify-center items-center h-32"><Loader2 className='mr-2 h-4 w-4 animate-spin' />Loading users</div>
        ) : error ? (
          <div className="flex justify-center items-center h-32 text-red-500">{error}</div>
        ) : (
          <UsersTable data={users} search={search} navigate={navigate} />
        )}
      </Main>

      <UsersDialogs />
    </UsersProvider>
  )
}
