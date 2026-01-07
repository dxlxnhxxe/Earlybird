import { useQuery } from '@tanstack/react-query'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { fetchTeamMembersKpis, TeamMemberKpi } from '../api'
import { useTeamStore } from '@/stores/team-store'

export function TeamMembersKpis() {
  const { selectedTeam } = useTeamStore()
  const enabled = !!selectedTeam && selectedTeam.id > 0
  const { data, isLoading, error } = useQuery<{ teamId: number; period: string; since: string; until: string; members: TeamMemberKpi[] }>({
    queryKey: ['dashboard', 'team-members-kpis', selectedTeam?.id ?? 0],
    queryFn: () => fetchTeamMembersKpis(selectedTeam!.id, 'day'),
    enabled,
    staleTime: 30_000,
  })

  if (!enabled) return null

  if (isLoading) {
    return (
      <Card>
        <CardContent className="py-6">Loading team member KPIs…</CardContent>
      </Card>
    )
  }
  if (error || !data) {
    return (
      <Card>
        <CardContent className="py-6 text-red-500">Failed to load team member KPIs</CardContent>
      </Card>
    )
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0">
        <CardTitle className="text-sm font-semibold">Team Members</CardTitle>
        <div className="text-xs text-muted-foreground">
          <span className="me-2">Period: {data.period.toUpperCase()}</span>
          <span className="me-2">Since: {data.since}</span>
          <span>Until: {data.until}</span>
        </div>
      </CardHeader>
      <CardContent>
        <div className="w-full overflow-x-auto">
          <table className="w-full text-left text-sm">
            <thead className="text-muted-foreground sticky top-0 bg-background">
              <tr>
                <th className="py-2 pe-4 font-medium">Member</th>
                <th className="py-2 pe-4 font-medium">Sessions</th>
                <th className="py-2 pe-4 font-medium">Clocks</th>
                <th className="py-2 pe-4 font-medium">Work</th>
                <th className="py-2 pe-4 font-medium">Avg/Day</th>
                <th className="py-2 pe-4 font-medium">Breaks</th>
                <th className="py-2 pe-4 font-medium">Lateness</th>
                <th className="py-2 pe-4 font-medium">Punctuality</th>
                <th className="py-2 pe-4 font-medium">First In</th>
                <th className="py-2 pe-4 font-medium">Last Out</th>
              </tr>
            </thead>
            <tbody>
              {data.members.map((m: TeamMemberKpi, idx: number) => (
                <tr key={idx} className="border-t border-border/50 odd:bg-muted/20">
                  <td className="py-2 pe-4 whitespace-nowrap">
                    {m.user ? `${m.user.firstname} ${m.user.lastname}` : '—'}
                  </td>
                  <td className="py-2 pe-4 tabular-nums">{m.sessions_total}</td>
                  <td className="py-2 pe-4 tabular-nums">{m.total_clocks}</td>
                  <td className="py-2 pe-4 tabular-nums">{m.total_work_time}</td>
                  <td className="py-2 pe-4 tabular-nums">{m.avg_work_time_per_day ?? m.avg_work_time_per_week ?? m.avg_work_time_per_month ?? m.avg_work_time_per_year ?? '—'}</td>
                  <td className="py-2 pe-4 tabular-nums">{m.total_breaks} ({m.total_break_time})</td>
                  <td className="py-2 pe-4 tabular-nums">{m.avg_lateness}</td>
                  <td className="py-2 pe-4 tabular-nums">{(m.punctuality_rate*100).toFixed(0)}%</td>
                  <td className="py-2 pe-4 tabular-nums">{m.first_arrival ?? '—'}</td>
                  <td className="py-2 pe-4 tabular-nums">{m.last_departure ?? '—'}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  )
}
