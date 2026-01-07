import { useLayout } from '@/context/layout-provider'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from '@/components/ui/sidebar'
// import { AppTitle } from './app-title'
import { sidebarData } from './data/sidebar-data'
import { NavGroup } from './nav-group'
import { NavUser } from './nav-user'
import { TeamSwitcher } from './team-switcher'
import { useTeamStore } from '@/stores/team-store'
import { useAuthStore } from '@/stores/auth-store'

export function AppSidebar() {
  const { collapsible, variant } = useLayout()
  const { isAdminTeam } = useTeamStore()
  const { auth } = useAuthStore()

  // Check if user has admin role from auth store or team
  const isAdmin = auth.isAdmin() || isAdminTeam

  // Filter navigation groups to only show admin items for admin users
  const filteredNavGroups = sidebarData.navGroups.map(group => ({
    ...group,
    items: group.items.filter(item => {
      // Hide Users and Teams for non-admin users
      if ((item.title === 'Teams' || item.title === 'Users') && !isAdmin) {
        return false
      }
      return true
    })
  })).filter(group => group.items.length > 0) // Remove empty groups

  return (
    <Sidebar collapsible={collapsible} variant={variant}>
      <SidebarHeader>
        <TeamSwitcher />

        {/* Replace <TeamSwitch /> with the following <AppTitle />
         /* if you want to use the normal app title instead of TeamSwitch dropdown */}
        {/* <AppTitle /> */}
      </SidebarHeader>
      <SidebarContent>
        {filteredNavGroups.map((props) => (
          <NavGroup key={props.title} {...props} />
        ))}
      </SidebarContent>
      <SidebarFooter>
        <NavUser user={sidebarData.user} />
      </SidebarFooter>
      <SidebarRail />
    </Sidebar>
  )
}
