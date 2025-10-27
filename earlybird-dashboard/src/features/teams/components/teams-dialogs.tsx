import { TeamsActionDialog } from './teams-action-dialog'
import { TeamsDeleteDialog } from './teams-delete-dialog'
import { useTeams } from './teams-provider'

export function TeamsDialogs() {
  const { open, setOpen, currentRow, setCurrentRow } = useTeams()

  return (
    <>
      <TeamsActionDialog
        key='team-create'
        open={open === 'create'}
        onOpenChange={() => setOpen('create')}
      />

      {currentRow && (
        <>
          <TeamsActionDialog
            key={`team-update-${currentRow.id}`}
            open={open === 'update'}
            onOpenChange={() => {
              setOpen('update')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <TeamsDeleteDialog
            key={`team-delete-${currentRow.id}`}
            open={open === 'delete'}
            onOpenChange={() => {
              setOpen('delete')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />
        </>
      )}
    </>
  )
}
