
import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { ConfigDrawer } from '@/components/config-drawer'
import { Header } from '@/components/layout/header'
import { Main } from '@/components/layout/main'
import { TopNav } from '@/components/layout/top-nav'
import { ProfileDropdown } from '@/components/profile-dropdown'
import { Search } from '@/components/search'
import { ThemeSwitch } from '@/components/theme-switch'
import { Analytics } from './components/analytics'
import { Kpis } from './components/kpis'
import { TeamMembersKpis } from './components/team-members-kpis'
import { TeamSelector } from './components/team-selector'
import { useEffect as useEffectReact } from 'react'
import { useTeamStore } from '@/stores/team-store'
import { fetchTeamAverages, ApiTeam } from './api'
import { useAuthStore } from '@/stores/auth-store'

export function Dashboard() {
  const { fetchAndInitializeTeams } = useTeamStore()
  useEffectReact(() => { fetchAndInitializeTeams() }, [fetchAndInitializeTeams])
  const [teams, setTeams] = useState<ApiTeam[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const { auth } = useAuthStore()

  useEffect(() => {
    setLoading(true)
    fetchTeamAverages('day')
      .then((data) => setTeams(data.teams))
      .catch((e) => setError(e.message))
      .finally(() => setLoading(false))
  }, [])

  return (
    <>
      {/* ===== Top Heading ===== */}
      <Header>
        <TopNav links={topNav} />
        <div className='ms-auto flex items-center space-x-4'>
          <Search />
          <ThemeSwitch />
          <ConfigDrawer />
          <ProfileDropdown />
        </div>
      </Header>

      {/* ===== Main ===== */}
      <Main>
        <div className='mb-2 flex items-center justify-between space-y-2'>
          <div>
            <h1 className='text-2xl font-bold tracking-tight'>
              Bienvenue {auth.user?.firstname || 'Utilisateur'} !
            </h1>
            <p className='text-muted-foreground text-sm'>
              Voici un aperçu de vos statistiques
            </p>
          </div>
          <div className='flex items-center space-x-2'>
            <Button>Download</Button>
          </div>
        </div>
        <Tabs
          orientation='vertical'
          defaultValue='overview'
          className='space-y-4'
        >
          <div className='w-full overflow-x-auto pb-2'>
            <TabsList>
              <TabsTrigger value='overview'>Overview</TabsTrigger>
              <TabsTrigger value='analytics'>Analytics</TabsTrigger>
              <TabsTrigger value='reports' disabled>
                Reports
              </TabsTrigger>
              <TabsTrigger value='notifications' disabled>
                Notifications
              </TabsTrigger>
            </TabsList>
          </div>
          <TabsContent value='overview' className='space-y-4'>
            <TeamSelector />
            <Kpis />
            <TeamMembersKpis />
            <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-4'>
              {loading ? (
                <Card>
                  <CardContent>Loading…</CardContent>
                </Card>
              ) : error ? (
                <Card>
                  <CardContent className='text-red-500'>{error}</CardContent>
                </Card>
              ) : teams.length === 0 ? (
                <Card>
                  <CardContent>No team data.</CardContent>
                </Card>
              ) : (
                teams.map((team) => (
                  <Card key={team.id}>
                    <CardHeader className='flex flex-row items-center justify-between space-y-0 pb-2'>
                      <CardTitle className='text-sm font-medium'>
                        {team.name}
                      </CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className='text-2xl font-bold'>
                        {team.average_work_time_seconds / 3600} h avg/day
                      </div>
                      <p className='text-muted-foreground text-xs'>
                        {team.members_count} members
                      </p>
                      <p className='text-muted-foreground text-xs'>
                        {team.average_work_time} (hh:mm:ss)
                      </p>
                    </CardContent>
                  </Card>
                ))
              )}
            </div>
            {/* You can add more cards/sections here for other KPIs or charts */}
          </TabsContent>
          <TabsContent value='analytics' className='space-y-4'>
            <Analytics />
          </TabsContent>
        </Tabs>
      </Main>
    </>
  )
}

const topNav = [
  {
    title: 'Overview',
    href: 'dashboard/overview',
    isActive: true,
    disabled: false,
  },
  {
    title: 'Customers',
    href: 'dashboard/customers',
    isActive: false,
    disabled: true,
  },
  {
    title: 'Products',
    href: 'dashboard/products',
    isActive: false,
    disabled: true,
  },
  {
    title: 'Settings',
    href: 'dashboard/settings',
    isActive: false,
    disabled: true,
  },
]
