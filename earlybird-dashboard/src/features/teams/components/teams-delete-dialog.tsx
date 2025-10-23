import { useState } from 'react'
import { Loader2 } from 'lucide-react'
import { toast } from 'sonner'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { deleteTeam } from '../data/fetch-teams'
import { type Team } from '../data/schema'
import { useTeams } from './teams-provider'

type TeamsDeleteDialogProps = {
  open: boolean
  onOpenChange: () => void
  currentRow?: Team
}

export function TeamsDeleteDialog({
  open,
  onOpenChange,
  currentRow,
}: TeamsDeleteDialogProps) {
  const { refetch } = useTeams()
  const [isLoading, setIsLoading] = useState(false)

  const handleDelete = async () => {
    if (!currentRow) return

    setIsLoading(true)
    try {
      await deleteTeam(currentRow.id)
      toast.success('Team deleted successfully')
      onOpenChange()
      refetch?.()
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : 'Failed to delete team'
      )
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-md'>
        <DialogHeader>
          <DialogTitle>Delete Team</DialogTitle>
          <DialogDescription>
            Are you sure you want to delete the team{' '}
            <strong>{currentRow?.name}</strong>?
            <br />
            This action cannot be undone.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <Button
            type='button'
            variant='outline'
            onClick={onOpenChange}
            disabled={isLoading}
          >
            Cancel
          </Button>
          <Button
            type='button'
            variant='destructive'
            onClick={handleDelete}
            disabled={isLoading}
          >
            {isLoading ? (
              <>
                <Loader2 className='mr-2 h-4 w-4 animate-spin' />
                Deleting...
              </>
            ) : (
              'Delete'
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
