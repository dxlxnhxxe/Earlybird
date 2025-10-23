import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { Loader2, X } from 'lucide-react'
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
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { SelectDropdown } from '@/components/select-dropdown'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { createTeam, updateTeam } from '../data/fetch-teams'
import { fetchUsers, type ApiUser } from '@/features/users/data/fetch-users'
import { type Team } from '../data/schema'
import { useTeams } from './teams-provider'

const teamFormSchema = z.object({
  name: z.string().min(1, 'Team name is required'),
  description: z.string().optional(),
  manager_id: z.number().min(1, 'Manager is required'),
  member_ids: z.array(z.number()).optional(),
})

type TeamFormData = z.infer<typeof teamFormSchema>

type TeamsActionDialogProps = {
  open: boolean
  onOpenChange: () => void
  currentRow?: Team
}

export function TeamsActionDialog({
  open,
  onOpenChange,
  currentRow,
}: TeamsActionDialogProps) {
  const isUpdate = !!currentRow
  const { refetch } = useTeams()
  const [isLoading, setIsLoading] = useState(false)
  const [users, setUsers] = useState<ApiUser[]>([])
  const [loadingUsers, setLoadingUsers] = useState(false)
  const [selectedMembers, setSelectedMembers] = useState<number[]>([])
  const [memberSelectKey, setMemberSelectKey] = useState(0)

  const form = useForm<TeamFormData>({
    resolver: zodResolver(teamFormSchema),
    defaultValues: {
      name: currentRow?.name || '',
      description: currentRow?.description || '',
      manager_id: currentRow?.manager?.id || undefined,
      member_ids: currentRow?.members.map((m) => m.id) || [],
    },
  })

  // Load users for manager and member selection
  useEffect(() => {
    if (open) {
      setLoadingUsers(true)
      fetchUsers()
        .then((data) => setUsers(data))
        .catch(() => toast.error('Failed to load users'))
        .finally(() => setLoadingUsers(false))
    }
  }, [open])

  // Reset form and selected members when dialog opens or currentRow changes
  useEffect(() => {
    if (open) {
      const memberIds = currentRow?.members.map((m) => m.id) || []
      setSelectedMembers(memberIds)
      form.reset({
        name: currentRow?.name || '',
        description: currentRow?.description || '',
        manager_id: currentRow?.manager?.id || undefined,
        member_ids: memberIds,
      })
    }
  }, [open, currentRow, form])

  const addMember = (userId: string) => {
    const id = parseInt(userId)
    if (id && id !== 0 && !selectedMembers.includes(id)) {
      const newMembers = [...selectedMembers, id]
      setSelectedMembers(newMembers)
      form.setValue('member_ids', newMembers)
      // Reset the select dropdown by changing its key
      setMemberSelectKey((prev) => prev + 1)
    }
  }

  const removeMember = (userId: number) => {
    const newMembers = selectedMembers.filter((id) => id !== userId)
    setSelectedMembers(newMembers)
    form.setValue('member_ids', newMembers)
  }

  const onSubmit = async (data: TeamFormData) => {
    setIsLoading(true)
    try {
      const payload = {
        name: data.name,
        description: data.description,
        manager_id: data.manager_id,
        members: (data.member_ids || []).map((user_id) => ({ user_id })),
      }

      if (isUpdate && currentRow) {
        await updateTeam(currentRow.id, payload)
        toast.success('Team updated successfully')
      } else {
        await createTeam(payload)
        toast.success('Team created successfully')
      }
      onOpenChange()
      refetch?.()
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : 'Failed to save team'
      )
    } finally {
      setIsLoading(false)
    }
  }

  const availableUsers = users.filter(
    (user) => !selectedMembers.includes(Number(user.id))
  )

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-2xl max-h-[90vh]'>
        <DialogHeader>
          <DialogTitle>{isUpdate ? 'Edit' : 'Create'} Team</DialogTitle>
          <DialogDescription>
            {isUpdate
              ? 'Update the team information and members.'
              : 'Add a new team to your organization.'}
          </DialogDescription>
        </DialogHeader>
        <ScrollArea className='max-h-[60vh] pr-4'>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className='space-y-4'>
              <FormField
                control={form.control}
                name='name'
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Team Name</FormLabel>
                    <FormControl>
                      <Input
                        placeholder='Engineering Team'
                        {...field}
                        disabled={isLoading}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='description'
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Description (Optional)</FormLabel>
                    <FormControl>
                      <Textarea
                        placeholder='Team description...'
                        {...field}
                        disabled={isLoading}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name='manager_id'
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Manager</FormLabel>
                    {loadingUsers ? (
                      <div className='flex items-center gap-2 text-sm text-muted-foreground'>
                        <Loader2 className='h-4 w-4 animate-spin' />
                      </div>
                    ) : (
                      <SelectDropdown
                        defaultValue={field.value?.toString() || '0'}
                        onValueChange={(v) =>
                          field.onChange(v === '0' ? 0 : parseInt(v))
                        }
                        placeholder='Select a manager'
                        disabled={isLoading}
                        items={[
                          { label: 'Select a manager', value: '0' },
                          ...users.map((user) => ({
                            label: `${user.firstname} ${user.lastname}`,
                            value: user.id.toString(),
                          })),
                        ]}
                      />
                    )}
                    <FormMessage />
                  </FormItem>
                )}
              />

              <div className='space-y-2'>
                <FormLabel>Team Members</FormLabel>
                {loadingUsers ? (
                  <div className='flex items-center gap-2 text-sm text-muted-foreground'>
                    <Loader2 className='h-4 w-4 animate-spin' />
                  </div>
                ) : (
                  <>
                    {selectedMembers.length > 0 && (
                      <div className='flex flex-wrap gap-2 rounded-md border p-3'>
                        {selectedMembers.map((memberId) => {
                          const user = users.find((u) => u.id === memberId)
                          if (!user) return null
                          return (
                            <Badge
                              key={memberId}
                              variant='secondary'
                              className='flex items-center gap-1'
                            >
                              {user.firstname} {user.lastname}
                              <button
                                type='button'
                                onClick={() => removeMember(memberId)}
                                disabled={isLoading}
                                className='ml-1 rounded-sm hover:bg-muted'
                              >
                                <X className='h-3 w-3' />
                              </button>
                            </Badge>
                          )
                        })}
                      </div>
                    )}
                    {availableUsers.length > 0 && (
                      <div className='flex items-center gap-2'>
                        <SelectDropdown
                          key={memberSelectKey}
                          defaultValue='0'
                          placeholder='Add a member'
                          disabled={isLoading}
                          onValueChange={(value) => {
                            if (value && value !== '0') {
                              addMember(value)
                            }
                          }}
                          items={[
                            { label: 'Select a member to add', value: '0' },
                            ...availableUsers.map((user) => ({
                              label: `${user.firstname} ${user.lastname}`,
                              value: user.id.toString(),
                            })),
                          ]}
                        />
                      </div>
                    )}
                    {selectedMembers.length === 0 && (
                      <p className='text-muted-foreground text-sm'>
                        No members added yet.
                      </p>
                    )}
                  </>
                )}
              </div>
            </form>
          </Form>
        </ScrollArea>
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
            type='submit'
            disabled={isLoading}
            onClick={form.handleSubmit(onSubmit)}
          >
            {isLoading ? (
              <>
                <Loader2 className='mr-2 h-4 w-4 animate-spin' />
                Saving...
              </>
            ) : (
              'Save'
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
