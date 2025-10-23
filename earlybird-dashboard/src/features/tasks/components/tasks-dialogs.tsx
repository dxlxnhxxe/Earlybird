import { useState } from 'react'
import { ConfirmDialog } from '@/components/confirm-dialog'
import { TasksImportDialog } from './tasks-import-dialog'
import { TasksMutateDrawer } from './tasks-mutate-drawer'
import { useTasks } from './tasks-provider'
import { deleteClock } from '../data/fetch-clocks'
import { toast } from 'sonner'

export function TasksDialogs() {
  const { open, setOpen, currentRow, setCurrentRow, refetch } = useTasks()
  const [isDeleting, setIsDeleting] = useState(false)

  const handleDelete = async () => {
    if (!currentRow) return
    
    setIsDeleting(true)
    try {
      await deleteClock(currentRow.id)
      toast.success('Clock deleted successfully')
      setOpen(null)
      setTimeout(() => {
        setCurrentRow(null)
      }, 500)
      refetch?.()
    } catch (error) {
      toast.error(error instanceof Error ? error.message : 'Failed to delete clock')
    } finally {
      setIsDeleting(false)
    }
  }

  return (
    <>
      <TasksMutateDrawer
        key='clock-create'
        open={open === 'create'}
        onOpenChange={() => setOpen('create')}
      />

      <TasksImportDialog
        key='clocks-import'
        open={open === 'import'}
        onOpenChange={() => setOpen('import')}
      />

      {currentRow && (
        <>
          <TasksMutateDrawer
            key={`clock-update-${currentRow.id}`}
            open={open === 'update'}
            onOpenChange={() => {
              setOpen('update')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            currentRow={currentRow}
          />

          <ConfirmDialog
            key='clock-delete'
            destructive
            open={open === 'delete'}
            onOpenChange={() => {
              setOpen('delete')
              setTimeout(() => {
                setCurrentRow(null)
              }, 500)
            }}
            handleConfirm={handleDelete}
            className='max-w-md'
            title={`Delete this clock: ${currentRow.id} ?`}
            desc={
              <>
                You are about to delete clock ID <strong>{currentRow.id}</strong> for{' '}
                <strong>{currentRow.user.firstname} {currentRow.user.lastname}</strong>. <br />
                This action cannot be undone.
              </>
            }
            confirmText={isDeleting ? 'Deleting...' : 'Delete'}
          />
        </>
      )}
    </>
  )
}
