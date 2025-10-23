import { type ColumnDef } from '@tanstack/react-table'
import { DataTableColumnHeader } from '@/components/data-table'
import { type Team } from '../data/schema'
import { DataTableRowActions } from './data-table-row-actions'

export const teamsColumns: ColumnDef<Team>[] = [
  {
    accessorKey: 'id',
    header: ({ column }) => <DataTableColumnHeader column={column} title='ID' />,
    cell: ({ row }) => <div className='w-10'>{row.getValue('id')}</div>,
    enableSorting: true,
    enableHiding: false,
  },
  {
    accessorKey: 'name',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Team Name' />,
    cell: ({ row }) => {
      return (
        <div className='flex space-x-2'>
          <span className='max-w-[500px] truncate font-medium'>
            {row.getValue('name')}
          </span>
        </div>
      )
    },
  },
  {
    accessorKey: 'description',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Description' />
    ),
    cell: ({ row }) => {
      const description = row.getValue('description') as string | null
      return (
        <div className='max-w-[300px] truncate'>
          {description || <span className='text-muted-foreground'>—</span>}
        </div>
      )
    },
  },
  {
    accessorKey: 'manager',
    header: ({ column }) => <DataTableColumnHeader column={column} title='Manager' />,
    cell: ({ row }) => {
      const manager = row.original.manager
      return (
        <div>
          {manager ? (
            <div>
              <div className='font-medium'>
                {manager.firstname} {manager.lastname}
              </div>
              <div className='text-muted-foreground text-xs'>
                {manager.email}
              </div>
            </div>
          ) : (
            <span className='text-muted-foreground'>No manager</span>
          )}
        </div>
      )
    },
    filterFn: (row, _id, value) => {
      const manager = row.original.manager
      if (!manager) return value.includes('no-manager')
      const fullName = `${manager.firstname} ${manager.lastname}`.toLowerCase()
      return fullName.includes(value.toLowerCase())
    },
  },
  {
    accessorKey: 'members',
    header: ({ column }) => (
      <DataTableColumnHeader column={column} title='Members Count' />
    ),
    cell: ({ row }) => {
      const members = row.original.members
      return (
        <div className='flex items-center'>
          <span className='font-medium'>{members.length}</span>
          <span className='text-muted-foreground ml-1'>
            member{members.length !== 1 ? 's' : ''}
          </span>
        </div>
      )
    },
  },
  {
    id: 'actions',
    cell: ({ row }) => <DataTableRowActions row={row} />,
  },
]
