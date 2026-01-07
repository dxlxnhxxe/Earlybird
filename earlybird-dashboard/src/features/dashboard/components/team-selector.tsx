import { useEffect } from 'react'
import { useTeamStore } from '@/stores/team-store'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

export function TeamSelector() {
  const { availableTeams, selectedTeam, setSelectedTeam, fetchAndInitializeTeams, isLoading } = useTeamStore()

  useEffect(() => { fetchAndInitializeTeams() }, [fetchAndInitializeTeams])

  return (
    <div className="flex items-center gap-3">
      <span className="text-sm text-muted-foreground">Team</span>
      <Select
        value={selectedTeam ? String(selectedTeam.id) : undefined}
        onValueChange={(val) => {
          const team = availableTeams.find((t) => String(t.id) === val) || null
          setSelectedTeam(team)
        }}
        disabled={isLoading}
      >
        <SelectTrigger className="w-[240px]">
          <SelectValue placeholder={isLoading ? 'Loading teams…' : 'Select team'} />
        </SelectTrigger>
        <SelectContent>
          {availableTeams.map((t) => (
            <SelectItem key={t.id} value={String(t.id)}>
              {t.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  )
}
