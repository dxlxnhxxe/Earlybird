import React, { useState } from 'react'
import useDialogState from '@/hooks/use-dialog-state'
import { type Team } from '../data/schema'

type TeamsDialogType = 'create' | 'update' | 'delete'

type TeamsContextType = {
  open: TeamsDialogType | null
  setOpen: (str: TeamsDialogType | null) => void
  currentRow: Team | null
  setCurrentRow: React.Dispatch<React.SetStateAction<Team | null>>
  refetch?: () => void
}

const TeamsContext = React.createContext<TeamsContextType | null>(null)

export function TeamsProvider({
  children,
  refetch,
}: {
  children: React.ReactNode
  refetch?: () => void
}) {
  const [open, setOpen] = useDialogState<TeamsDialogType>(null)
  const [currentRow, setCurrentRow] = useState<Team | null>(null)

  return (
    <TeamsContext value={{ open, setOpen, currentRow, setCurrentRow, refetch }}>
      {children}
    </TeamsContext>
  )
}

// eslint-disable-next-line react-refresh/only-export-components
export const useTeams = () => {
  const teamsContext = React.useContext(TeamsContext)

  if (!teamsContext) {
    throw new Error('useTeams has to be used within <TeamsContext>')
  }

  return teamsContext
}
