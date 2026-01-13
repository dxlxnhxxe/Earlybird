// API fetcher for team-averages
export type ApiTeam = {
  id: number
  name: string
  average_work_time_seconds: number
  average_work_time: string
  members_count: number
}

export type ApiOverview = {
  teams: ApiTeam[]
  since?: string
  until?: string
  period?: string
}

function parseDurationToSeconds(text: string | undefined | null): number {
  if (!text) return 0
  // Expected format like "08h 30m 00s"
  const match = /(?:(\d+)h)?\s*(?:(\d+)m)?\s*(?:(\d+)s)?/i.exec(text)
  if (!match) return 0
  const h = Number(match[1] || 0)
  const m = Number(match[2] || 0)
  const s = Number(match[3] || 0)
  return h * 3600 + m * 60 + s
}

export async function fetchTeamAverages(period: string = 'day'): Promise<ApiOverview> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  // Try backend aggregator route first
  try {
    const res = await fetch(`${baseUrl}/team-averages?period=${encodeURIComponent(period)}`)
    if (res.ok) {
      const json = await res.json()
      const teams = (json?.teams ?? []) as ApiTeam[]
      // Enrich by converting any formatted duration to seconds if missing
      const enriched = teams.map((t) => ({
        ...t,
        average_work_time_seconds:
          typeof t.average_work_time_seconds === 'number'
            ? t.average_work_time_seconds
            : parseDurationToSeconds((t as any).average_work_time),
      }))
      return { teams: enriched, since: json?.since, until: json?.until, period: json?.period ?? period }
    }
  } catch {}

  // Fallback: build overview client-side via existing endpoints
  const teamsRes = await fetch(`${baseUrl}/teams`)
  if (!teamsRes.ok) throw new Error('Failed to fetch teams')
  const teamsData: Array<{ id: number; name: string; members?: any[] }> = await teamsRes.json()
  const enriched = await Promise.all(
    teamsData.map(async (t) => {
      try {
        const avgRes = await fetch(
          `${baseUrl}/reports/average-work-time?type=team&team_id=${encodeURIComponent(String(t.id))}&period=${encodeURIComponent(period)}`
        )
        if (avgRes.ok) {
          const avgJson = await avgRes.json()
          const teamSummary = avgJson?.team
          const avgText = teamSummary?.team_average_work_time as string | undefined
          const avgSeconds = parseDurationToSeconds(avgText)
          const membersCount = Number(teamSummary?.members_count ?? t.members?.length ?? 0)
          return {
            id: t.id,
            name: t.name,
            average_work_time_seconds: avgSeconds,
            average_work_time: avgText ?? '00h 00m 00s',
            members_count: membersCount,
          } as ApiTeam
        }
      } catch {}
      return {
        id: t.id,
        name: t.name,
        average_work_time_seconds: 0,
        average_work_time: '00h 00m 00s',
        members_count: Array.isArray(t.members) ? t.members.length : 0,
      } as ApiTeam
    })
  )

  return { teams: enriched, period }
}

// KPI types and fetchers
export type DashboardKpis = {
  totalUsers: number
  totalClocks: number
  averageClocksPerUser: number
  mostActiveUser?: {
    id: number
    firstname: string
    lastname: string
    email: string
    total_clocks: number
  } | null
  lastActivity: string | null
}

export type TeamKpis = {
  teamId: number
  period: string
  since: string
  until: string
  summary: {
    membersCount: number
    activeMembersCount: number
    inactiveMembersCount: number
    attendanceRate: number
    totalClocks: number
    averageClocksPerMember: number
    lastActivity: string | null
    teamAverageWorkTimeSeconds: number
    teamAverageWorkTime: string
    teamTotalWorkTimeSeconds: number
    teamTotalWorkTime: string
    teamMedianWorkTimeSeconds: number
    teamMedianWorkTime: string
    teamMinWorkTimeSeconds: number
    teamMinWorkTime: string
    teamMaxWorkTimeSeconds: number
    teamMaxWorkTime: string
    averageLatenessSeconds: number
    averageLateness: string
    punctualityRate: number
    punctualityPercent: number
    sessionsTotal: number
    averageSessionLengthSeconds: number
    averageSessionLength: string
    totalBreaks: number
    averageBreaksPerMember: number
    averageBreakDurationSeconds: number
    averageBreakDuration: string
    earliestArrival: string | null
    latestDeparture: string | null
    averageArrivalDeviationSeconds: number
    averageArrivalDeviation: string
    averageDepartureDeviationSeconds: number
    averageDepartureDeviation: string
    expectedTotalWorkTimeSeconds: number
    expectedTotalWorkTime: string
    utilizationRate: number
    utilizationPercent: number
    stddevWorkTimeSeconds: number
    stddevWorkTime: string
    p90WorkTimeSeconds: number
    p90WorkTime: string
    peakActivityHour: number
  }
  mostActiveMembers: {
    byClocks: {
      user: { id: number; firstname: string; lastname: string; email: string } | null
      total_clocks: number
    } | null
    byWorkTime: {
      user: { id: number; firstname: string; lastname: string; email: string } | null
      avg_work_seconds: number
      avg_work_time: string
    } | null
  }
  punctuality: {
    top_punctual: {
      user: { id: number; firstname: string; lastname: string; email: string } | null
      avg_lateness_seconds: number
      avg_lateness: string
    } | null
    top_late: {
      user: { id: number; firstname: string; lastname: string; email: string } | null
      avg_lateness_seconds: number
      avg_lateness: string
    } | null
  }
}

export async function fetchKpis(): Promise<DashboardKpis> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  // Preferred endpoint from Symfony backend: /reports (global)
  try {
    const res = await fetch(`${baseUrl}/reports`)
    if (res.ok) {
      const json = await res.json()
      const k = json.kpis ?? json
      if (k) {
        return {
          totalUsers: Number(k.total_users ?? 0),
          totalClocks: Number(k.total_clocks ?? 0),
          averageClocksPerUser: Number(k.average_clocks_per_user ?? 0),
          mostActiveUser: k.most_active_user ?? null,
          lastActivity: k.last_activity ?? null,
        }
      }
    }
  } catch (e) {
    // fall through to alternate endpoints
  }

  // Alternate endpoints for flexibility
  const endpoints = ['/kpis', '/metrics/kpis']
  for (const ep of endpoints) {
    try {
      const res = await fetch(`${baseUrl}${ep}`)
      if (res.ok) {
        const k = await res.json()
        if ('total_users' in k || 'totalClocks' in k) {
          return {
            totalUsers: Number(k.total_users ?? k.totalUsers ?? 0),
            totalClocks: Number(k.total_clocks ?? k.totalClocks ?? 0),
            averageClocksPerUser: Number(k.average_clocks_per_user ?? k.averageClocksPerUser ?? 0),
            mostActiveUser: k.most_active_user ?? k.mostActiveUser ?? null,
            lastActivity: k.last_activity ?? k.lastActivity ?? null,
          }
        }
      }
    } catch {}
  }

  // Fallback mock when API not available (e.g., local dev)
  const base = 50
  const totalUsers = 32 + Math.floor(Math.random() * base)
  const totalClocks = Math.floor(totalUsers * (2 + Math.random() * 4))
  return {
    totalUsers,
    totalClocks,
    averageClocksPerUser: Number((totalClocks / Math.max(totalUsers, 1)).toFixed(2)),
    mostActiveUser: null,
    lastActivity: null,
  }
}

export async function fetchTeamKpis(teamId: number, period: string = 'day'): Promise<TeamKpis> {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  const res = await fetch(`${baseUrl}/reports/team-kpis?team_id=${encodeURIComponent(String(teamId))}&period=${encodeURIComponent(period)}`)
  if (!res.ok) {
    throw new Error('Failed to fetch team KPIs')
  }
  const json = await res.json()
  // Normalize snake_case to camelCase
  return {
    teamId: json.team_id,
    period: json.period,
    since: json.since,
    until: json.until,
    summary: {
      membersCount: json.summary.members_count,
      activeMembersCount: json.summary.active_members_count,
      inactiveMembersCount: json.summary.inactive_members_count,
      attendanceRate: json.summary.attendance_rate,
      totalClocks: json.summary.total_clocks,
      averageClocksPerMember: json.summary.average_clocks_per_member,
      lastActivity: json.summary.last_activity,
      teamAverageWorkTimeSeconds: json.summary.team_average_work_time_seconds,
      teamAverageWorkTime: json.summary.team_average_work_time,
      teamTotalWorkTimeSeconds: json.summary.team_total_work_time_seconds,
      teamTotalWorkTime: json.summary.team_total_work_time,
      teamMedianWorkTimeSeconds: json.summary.team_median_work_time_seconds,
      teamMedianWorkTime: json.summary.team_median_work_time,
      teamMinWorkTimeSeconds: json.summary.team_min_work_time_seconds,
      teamMinWorkTime: json.summary.team_min_work_time,
      teamMaxWorkTimeSeconds: json.summary.team_max_work_time_seconds,
      teamMaxWorkTime: json.summary.team_max_work_time,
      averageLatenessSeconds: json.summary.average_lateness_seconds,
      averageLateness: json.summary.average_lateness,
      punctualityRate: json.summary.punctuality_rate,
      punctualityPercent: json.summary.punctuality_percent,
      sessionsTotal: json.summary.sessions_total,
      averageSessionLengthSeconds: json.summary.average_session_length_seconds,
      averageSessionLength: json.summary.average_session_length,
      totalBreaks: json.summary.total_breaks,
      averageBreaksPerMember: json.summary.average_breaks_per_member,
      averageBreakDurationSeconds: json.summary.average_break_duration_seconds,
      averageBreakDuration: json.summary.average_break_duration,
      earliestArrival: json.summary.earliest_arrival,
      latestDeparture: json.summary.latest_departure,
      averageArrivalDeviationSeconds: json.summary.average_arrival_deviation_seconds,
      averageArrivalDeviation: json.summary.average_arrival_deviation,
      averageDepartureDeviationSeconds: json.summary.average_departure_deviation_seconds,
      averageDepartureDeviation: json.summary.average_departure_deviation,
      expectedTotalWorkTimeSeconds: json.summary.expected_total_work_time_seconds,
      expectedTotalWorkTime: json.summary.expected_total_work_time,
      utilizationRate: json.summary.utilization_rate,
      utilizationPercent: json.summary.utilization_percent,
      stddevWorkTimeSeconds: json.summary.stddev_work_time_seconds,
      stddevWorkTime: json.summary.stddev_work_time,
      p90WorkTimeSeconds: json.summary.p90_work_time_seconds,
      p90WorkTime: json.summary.p90_work_time,
      peakActivityHour: json.summary.peak_activity_hour,
    },
    mostActiveMembers: {
      byClocks: json.most_active_members?.by_clocks ?? null,
      byWorkTime: json.most_active_members?.by_work_time ?? null,
    },
    punctuality: {
      top_punctual: json.punctuality?.top_punctual ?? null,
      top_late: json.punctuality?.top_late ?? null,
    },
  }
}

// Per-employee KPIs for a team
export type TeamMemberKpi = {
  user: { id: number; firstname: string; lastname: string; email: string } | null
  total_clocks: number
  sessions_total: number
  total_work_seconds: number
  total_work_time: string
  avg_work_seconds_per_day?: number
  avg_work_time_per_day?: string
  avg_work_seconds_per_week?: number
  avg_work_time_per_week?: string
  avg_work_seconds_per_month?: number
  avg_work_time_per_month?: string
  avg_work_seconds_per_year?: number
  avg_work_time_per_year?: string
  total_breaks: number
  total_break_seconds: number
  total_break_time: string
  avg_lateness_seconds: number
  avg_lateness: string
  punctuality_rate: number
  first_arrival: string | null
  last_departure: string | null
}

export async function fetchTeamMembersKpis(teamId: number, period: string = 'day') {
  const baseUrl = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'
  const res = await fetch(`${baseUrl}/reports/team-members-kpis?team_id=${encodeURIComponent(String(teamId))}&period=${encodeURIComponent(period)}`)
  if (!res.ok) throw new Error('Failed to fetch team members KPIs')
  const json = await res.json()
  return {
    teamId: json.team_id as number,
    period: json.period as string,
    since: json.since as string,
    until: json.until as string,
    members: (json.members as any[]).map((m) => m as TeamMemberKpi),
  }
}
