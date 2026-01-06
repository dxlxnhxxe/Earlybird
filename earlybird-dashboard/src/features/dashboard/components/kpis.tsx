import { useQuery } from '@tanstack/react-query'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { fetchKpis, DashboardKpis, fetchTeamKpis, TeamKpis } from '../api'
import { useTeamStore } from '@/stores/team-store'
import { cn } from '@/lib/utils'
import { Users, AlarmClock, BarChart3, Crown, Timer, Gauge, ActivitySquare, Clock, CalendarRange, LayoutGrid } from 'lucide-react'

function formatNumber(n: number) {
  return new Intl.NumberFormat().format(n)
}

function Stat({
  label,
  value,
  icon: Icon,
  className,
  subtext,
}: {
  label: string
  value: string | number
  icon?: (props: { className?: string }) => any
  className?: string
  subtext?: string
}) {
  return (
    <Card className={cn('shadow-sm', className)}>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-sm font-medium">{label}</CardTitle>
        {Icon ? <Icon className="text-muted-foreground h-4 w-4" /> : null}
      </CardHeader>
      <CardContent>
        <div className="text-2xl font-semibold tracking-tight">{value}</div>
        {subtext ? (
          <p className="text-muted-foreground text-xs mt-1">{subtext}</p>
        ) : null}
      </CardContent>
    </Card>
  )
}

function Section({ title, icon: Icon, children }: { title: string; icon?: (props: { className?: string }) => any; children: any }) {
  return (
    <div className="space-y-3">
      <div className="flex items-center gap-2">
        {Icon ? <Icon className="h-4 w-4 text-muted-foreground" /> : null}
        <h2 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">{title}</h2>
      </div>
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        {children}
      </div>
    </div>
  )
}

export function Kpis() {
  const { selectedTeam } = useTeamStore()
  const isTeamSelected = !!selectedTeam && selectedTeam.id > 0

  const global = useQuery<DashboardKpis>({
    queryKey: ['dashboard', 'kpis', 'global'],
    queryFn: fetchKpis,
    staleTime: 30_000,
    enabled: !isTeamSelected,
  })

  const team = useQuery<TeamKpis>({
    queryKey: ['dashboard', 'kpis', 'team', selectedTeam?.id ?? 0],
    queryFn: () => fetchTeamKpis(selectedTeam!.id, 'day'),
    staleTime: 30_000,
    enabled: isTeamSelected,
  })

  if ((!isTeamSelected && global.isLoading) || (isTeamSelected && team.isLoading)) {
    return (
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {Array.from({ length: 4 }).map((_, i) => (
          <Card key={i}>
            <CardContent className="h-20 animate-pulse" />
          </Card>
        ))}
      </div>
    )
  }

  if ((!isTeamSelected && global.error) || (isTeamSelected && team.error)) {
    return (
      <Card>
        <CardContent className="text-red-500 py-6">
          Failed to load KPIs
        </CardContent>
      </Card>
    )
  }

  if ((!isTeamSelected && !global.data) || (isTeamSelected && !team.data)) return null

  if (!isTeamSelected) {
    const data = global.data!
    const mostActive = data.mostActiveUser
    return (
      <Section title="Overview" icon={LayoutGrid}>
        <Stat label="Total Users" value={formatNumber(data.totalUsers)} icon={Users} />
        <Stat label="Total Clocks" value={formatNumber(data.totalClocks)} icon={AlarmClock} />
        <Stat label="Avg Clocks/User" value={data.averageClocksPerUser.toFixed(2)} icon={BarChart3} />
        <Stat
          label="Most Active User"
          value={mostActive ? `${mostActive.firstname} ${mostActive.lastname}` : '—'}
          icon={Crown}
          subtext={mostActive ? `${formatNumber(mostActive.total_clocks)} clocks` : undefined}
        />
        <Stat
          label="Last Activity"
          value={data.lastActivity ? new Date(data.lastActivity).toLocaleString() : '—'}
          icon={Timer}
        />
      </Section>
    )
  }

  const k = team.data!
  const byClocks = k.mostActiveMembers.byClocks
  const byWork = k.mostActiveMembers.byWorkTime
  const punctual = k.punctuality
  // Derived helpers
  const toHours = (sec: number) => (sec / 3600).toFixed(1) + ' h'
  return (
    <div className="space-y-8">
      <Section title="Team Summary" icon={Users}>
        <Stat label="Members" value={formatNumber(k.summary.membersCount)} icon={Users} />
        <Stat label="Active" value={formatNumber(k.summary.activeMembersCount)} />
        <Stat label="Inactive" value={formatNumber(k.summary.inactiveMembersCount)} />
        <Stat label="Attendance" value={`${(k.summary.attendanceRate*100).toFixed(1)}%`} icon={Gauge} />
        <Stat label="Total Clocks" value={formatNumber(k.summary.totalClocks)} icon={AlarmClock} />
        <Stat label="Avg Clocks/Member" value={k.summary.averageClocksPerMember.toFixed(2)} icon={BarChart3} />
        <Stat label="Last Activity" value={k.summary.lastActivity ? new Date(k.summary.lastActivity).toLocaleString() : '—'} icon={Timer} />
      </Section>

      <Section title="Work Time" icon={Clock}>
        <Stat label="Avg Work" value={k.summary.teamAverageWorkTime} />
        <Stat label="Avg (hrs)" value={toHours(k.summary.teamAverageWorkTimeSeconds)} />
        <Stat label="Total Work" value={k.summary.teamTotalWorkTime} />
        <Stat label="Total (hrs)" value={toHours(k.summary.teamTotalWorkTimeSeconds)} />
        <Stat label="Median" value={k.summary.teamMedianWorkTime} />
        <Stat label="Min" value={k.summary.teamMinWorkTime} />
        <Stat label="Max" value={k.summary.teamMaxWorkTime} />
      </Section>

      <Section title="Attendance & Punctuality" icon={ActivitySquare}>
        <Stat label="Avg Lateness" value={k.summary.averageLateness} />
        <Stat label="Punctuality" value={`${k.summary.punctualityPercent}%`} />
        <Stat label="Earliest Arrival" value={k.summary.earliestArrival ?? '—'} />
        <Stat label="Latest Departure" value={k.summary.latestDeparture ?? '—'} />
        <Stat label="Avg Arrival Δ" value={k.summary.averageArrivalDeviation} />
        <Stat label="Avg Departure Δ" value={k.summary.averageDepartureDeviation} />
      </Section>

      <Section title="Sessions & Breaks" icon={AlarmClock}>
        <Stat label="Sessions Total" value={formatNumber(k.summary.sessionsTotal)} />
        <Stat label="Avg Session" value={k.summary.averageSessionLength} />
        <Stat label="Total Breaks" value={formatNumber(k.summary.totalBreaks)} />
        <Stat label="Avg Breaks/Member" value={k.summary.averageBreaksPerMember.toFixed(2)} />
        <Stat label="Avg Break" value={k.summary.averageBreakDuration} />
      </Section>

      <Section title="Utilization & Distribution" icon={BarChart3}>
        <Stat label="Expected Total" value={k.summary.expectedTotalWorkTime} />
        <Stat label="Utilization" value={`${k.summary.utilizationPercent}%`} />
        <Stat label="Std Dev" value={k.summary.stddevWorkTime} />
        <Stat label="P90" value={k.summary.p90WorkTime} />
        <Stat label="Peak Hour" value={`${k.summary.peakActivityHour}:00`} />
      </Section>

      <Section title="Top Performers" icon={Crown}>
        <Stat label="By Clocks" value={byClocks?.user ? `${byClocks.user.firstname} ${byClocks.user.lastname}` : '—'} subtext={byClocks ? `${formatNumber(byClocks.total_clocks)} clocks` : undefined} icon={Crown} />
        <Stat label="By Work" value={byWork?.user ? `${byWork.user.firstname} ${byWork.user.lastname}` : '—'} subtext={byWork ? byWork.avg_work_time : undefined} icon={Crown} />
        <Stat label="Top Punctual" value={punctual?.top_punctual?.user ? `${punctual.top_punctual.user.firstname} ${punctual.top_punctual.user.lastname}` : '—'} subtext={punctual?.top_punctual ? punctual.top_punctual.avg_lateness : undefined} />
        <Stat label="Top Late" value={punctual?.top_late?.user ? `${punctual.top_late.user.firstname} ${punctual.top_late.user.lastname}` : '—'} subtext={punctual?.top_late ? punctual.top_late.avg_lateness : undefined} />
      </Section>

      <Section title="Period" icon={CalendarRange}>
        <Stat label="Period" value={k.period.toUpperCase()} />
        <Stat label="Since" value={k.since} />
        <Stat label="Until" value={k.until} />
      </Section>
    </div>
  )
}
