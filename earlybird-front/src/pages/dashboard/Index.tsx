import React, { useEffect, useMemo, useState } from 'react'
import { Link } from 'react-router-dom'
import '../../styles/main.sass'

type TeamKPI = {
  teamId: number
  teamName: string
  totalMembers: number
  avgDailyHours: number
  lateArrivalsThisMonth: number
}

type MemberKPI = {
  userId: number
  name: string
  avgDailyHours: number
  lastActivity: string
  lateArrivalsThisMonth: number
}

type ApiTeam = {
  id: number
  name: string
  average_work_time_seconds: number
  average_work_time: string
  members_count: number
}

type ApiOverview = {
  teams?: ApiTeam[]
}

// Future: define ApiMember when backend provides a team members KPIs endpoint

type ApiDailyWorkTime = {
  date?: string
  work_time_seconds?: number
  daily_work_time_seconds?: number
  average_work_time_seconds?: number
  work_time?: string
  average_work_time?: string
}

const apiBase = 'http://earlybird-api'

const DashboardIndex: React.FC = () => {
  const [teamKpis, setTeamKpis] = useState<TeamKPI[]>([])
  const [selectedTeamId, setSelectedTeamId] = useState<number | null>(null)
  const [members, setMembers] = useState<MemberKPI[]>([])
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  // Daily work-time query states
  const [dailyEmployeeId, setDailyEmployeeId] = useState<string>('')
  const [dailyDate, setDailyDate] = useState<string>('')
  const [dailyLoading, setDailyLoading] = useState<boolean>(false)
  const [dailyError, setDailyError] = useState<string | null>(null)
  const [dailyResult, setDailyResult] = useState<ApiDailyWorkTime | null>(null)

  const selectedTeam = useMemo(() => teamKpis.find(t => t.teamId === selectedTeamId) || null, [teamKpis, selectedTeamId])

  useEffect(() => {
    const fetchOverview = async () => {
      try {
        setLoading(true)
        setError(null)
        // Backend endpoint: /team-averages?period=day|week|month
        const res = await fetch(`${apiBase}/team-averages?period=day`)
        if (!res.ok) throw new Error('Failed to load KPIs')
        const data: ApiOverview = await res.json()
        // Map backend structure to TeamKPI[]
        const mapped: TeamKPI[] = (data.teams || []).map((t: ApiTeam) => ({
          teamId: t.id,
          teamName: t.name,
          totalMembers: t.members_count,
          // Convert average_work_time_seconds to hours (two decimals)
          avgDailyHours: t.average_work_time_seconds / 3600,
          lateArrivalsThisMonth: 0
        }))
        setTeamKpis(mapped)
        if (mapped.length) setSelectedTeamId(mapped[0].teamId)
      } catch (e) {
        setError(e instanceof Error ? e.message : 'Error')
      } finally {
        setLoading(false)
      }
    }
    fetchOverview()
  }, [])

  // Initialize the daily work-time date to today (YYYY-MM-DD)
  useEffect(() => {
    const today = new Date()
    const yyyy = today.getFullYear()
    const mm = String(today.getMonth() + 1).padStart(2, '0')
    const dd = String(today.getDate()).padStart(2, '0')
    setDailyDate(`${yyyy}-${mm}-${dd}`)
  }, [])

  useEffect(() => {
    // TODO: Backend endpoint needed to list team members and/or their KPIs for a team.
    // Suggestion: GET /teams/{id}/members or /reports/team/{id}/members-averages
    setMembers([])
  }, [selectedTeamId])

  return (
    <div style={{ padding: 24 }}>
      <h1 style={{ marginBottom: 16 }}>Manager Dashboard</h1>
      {error && <div style={{ color: 'red', marginBottom: 12 }}>{error}</div>}

      <section style={{ marginBottom: 24 }}>
        <h2 style={{ marginBottom: 8 }}>Team Overview</h2>
        {loading && !teamKpis.length ? (
          <div>Loading...</div>
        ) : (
          <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(260px, 1fr))', gap: 12 }}>
            {teamKpis.map(team => (
              <div key={team.teamId} style={{ border: '1px solid #eee', borderRadius: 8, padding: 16, background: '#fff' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                  <strong>{team.teamName}</strong>
                  <button onClick={() => setSelectedTeamId(team.teamId)} disabled={selectedTeamId === team.teamId}>
                    View
                  </button>
                </div>
                <div style={{ fontSize: 13, color: '#555', marginTop: 8 }}>Members: {team.totalMembers}</div>
                <div style={{ fontSize: 13, color: '#555' }}>Avg daily hours: {team.avgDailyHours.toFixed(2)}</div>
                <div style={{ fontSize: 13, color: '#555' }}>Late arrivals (month): {team.lateArrivalsThisMonth}</div>
              </div>
            ))}
          </div>
        )}
      </section>

      <section style={{ marginBottom: 24 }}>
        <h2 style={{ marginBottom: 8 }}>Employee Daily Work Time</h2>
        <div style={{ display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap', marginBottom: 12 }}>
          <label>
            Employee ID
            <input
              type="number"
              value={dailyEmployeeId}
              onChange={e => setDailyEmployeeId(e.target.value)}
              placeholder="e.g. 1"
              style={{ marginLeft: 8 }}
            />
          </label>
          <label>
            Date
            <input
              type="date"
              value={dailyDate}
              onChange={e => setDailyDate(e.target.value)}
              style={{ marginLeft: 8 }}
            />
          </label>
          <button
            onClick={async () => {
              setDailyError(null)
              setDailyResult(null)
              if (!dailyEmployeeId || !dailyDate) {
                setDailyError('Please provide both employee ID and date.')
                return
              }
              try {
                setDailyLoading(true)
                const res = await fetch(`${apiBase}/reports/employee/${encodeURIComponent(dailyEmployeeId)}/daily-work-time?date=${encodeURIComponent(dailyDate)}`)
                if (!res.ok) throw new Error(`Request failed (${res.status})`)
                const json = (await res.json()) as ApiDailyWorkTime
                setDailyResult(json)
              } catch (err) {
                setDailyError(err instanceof Error ? err.message : 'Unknown error')
              } finally {
                setDailyLoading(false)
              }
            }}
            disabled={dailyLoading}
          >
            {dailyLoading ? 'Loading…' : 'Fetch'}
          </button>
        </div>
        {dailyError && <div style={{ color: 'red', marginBottom: 8 }}>{dailyError}</div>}
        {dailyResult && (
          <div style={{ border: '1px solid #eee', borderRadius: 8, padding: 12, background: '#fafafa' }}>
            <div style={{ marginBottom: 4 }}>Date: {dailyResult.date || dailyDate}</div>
            <div style={{ marginBottom: 4 }}>
              Worked hours: {
                (() => {
                  const seconds = dailyResult.work_time_seconds ?? dailyResult.daily_work_time_seconds ?? dailyResult.average_work_time_seconds
                  return typeof seconds === 'number' ? (seconds / 3600).toFixed(2) : '—'
                })()
              }
            </div>
            <div style={{ color: '#666', fontSize: 13 }}>
              Raw time: {dailyResult.work_time || dailyResult.average_work_time || '—'}
            </div>
          </div>
        )}
      </section>

      <section>
        <h2 style={{ marginBottom: 8 }}>Team Members {selectedTeam ? `– ${selectedTeam.teamName}` : ''}</h2>
        {selectedTeamId && members.length === 0 ? (
          <div style={{ color: '#666' }}>Members KPIs not available yet.</div>
        ) : (
          <div style={{ overflowX: 'auto' }}>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <thead>
                <tr>
                  <th style={{ textAlign: 'left', padding: 8, borderBottom: '1px solid #eee' }}>Member</th>
                  <th style={{ textAlign: 'left', padding: 8, borderBottom: '1px solid #eee' }}>Avg daily hours</th>
                  <th style={{ textAlign: 'left', padding: 8, borderBottom: '1px solid #eee' }}>Last activity</th>
                  <th style={{ textAlign: 'left', padding: 8, borderBottom: '1px solid #eee' }}>Late arrivals (month)</th>
                  <th style={{ textAlign: 'left', padding: 8, borderBottom: '1px solid #eee' }}>Actions</th>
                </tr>
              </thead>
              <tbody>
                {members.map(m => (
                  <tr key={m.userId}>
                    <td style={{ padding: 8, borderBottom: '1px solid #f3f3f3' }}>{m.name || `User #${m.userId}`}</td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f3f3f3' }}>{m.avgDailyHours.toFixed(2)}</td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f3f3f3' }}>{m.lastActivity}</td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f3f3f3' }}>{m.lateArrivalsThisMonth}</td>
                    <td style={{ padding: 8, borderBottom: '1px solid #f3f3f3' }}>
                      <Link to={`#user/${m.userId}`}>View details</Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>
    </div>
  )
}

export default DashboardIndex
