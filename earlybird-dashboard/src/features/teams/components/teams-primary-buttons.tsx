import { Button } from '@/components/ui/button'
import { Plus } from 'lucide-react'
import { useTeams } from './teams-provider'

export function TeamsPrimaryButtons() {
  const { setOpen } = useTeams()

  return (
    <div className='flex items-center gap-2'>
      <Button
        variant='outline'
        size='sm'
        className='h-8'
        onClick={() => setOpen('create')}
      >
        <Plus className='mr-2 size-4' aria-hidden='true' />
        New Team
      </Button>
    </div>
  )
}
