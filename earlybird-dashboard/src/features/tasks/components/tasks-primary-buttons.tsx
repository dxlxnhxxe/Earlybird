import { Download, Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { useTasks } from './tasks-provider'
import { exportToCSV } from '@/lib/utils'
import type { ApiClock } from '../data/fetch-clocks'

interface TasksPrimaryButtonsProps {
  clocks: ApiClock[]
}

export function TasksPrimaryButtons({ clocks }: TasksPrimaryButtonsProps) {
  const { setOpen } = useTasks()

  const handleExport = () => {
    exportToCSV(clocks, 'clocks')
  }

  return (
    <div className='flex gap-2'>
      <Button
        variant='outline'
        className='space-x-1'
        onClick={handleExport}
      >
        <span>Export</span> <Download size={18} />
      </Button>
      <Button className='space-x-1' onClick={() => setOpen('create')}>
        <span>Create</span> <Plus size={18} />
      </Button>
    </div>
  )
}
