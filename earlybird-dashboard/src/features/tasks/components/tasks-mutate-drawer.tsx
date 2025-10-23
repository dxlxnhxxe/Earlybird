import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useEffect, useState } from 'react'
import { Button } from '@/components/ui/button'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group'
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet'
import { SelectDropdown } from '@/components/select-dropdown'
import { type Clock } from '../data/schema'
import { createClock, updateClock } from '../data/fetch-clocks'
import { fetchUsers, type ApiUser } from '@/features/users/data/fetch-users'
import { fetchTeams, type ApiTeam } from '../data/fetch-teams'
import { useTasks } from './tasks-provider'
import { toast } from 'sonner'
import { Loader2 } from 'lucide-react'

type TaskMutateDrawerProps = {
  open: boolean
  onOpenChange: (open: boolean) => void
  currentRow?: Clock
}

const formSchema = z.object({
  user_id: z.number().min(1, 'Please select a user.'),
  team_id: z.number().min(1, 'Please select a team.'),
  type: z.enum(['arrival', 'departure']),
})
type ClockForm = z.infer<typeof formSchema>

export function TasksMutateDrawer({
  open,
  onOpenChange,
  currentRow,
}: TaskMutateDrawerProps) {
  const isUpdate = !!currentRow
  const { refetch } = useTasks()
  const [isLoading, setIsLoading] = useState(false)
  const [users, setUsers] = useState<ApiUser[]>([])
  const [teams, setTeams] = useState<ApiTeam[]>([])
  const [loadingData, setLoadingData] = useState(true)

  const form = useForm<ClockForm>({
    resolver: zodResolver(formSchema),
    defaultValues: currentRow
      ? {
          user_id: currentRow.user.id,
          team_id: currentRow.team.id,
          type: currentRow.type,
        }
      : undefined,
  })

  // Load users and teams
  useEffect(() => {
    if (open) {
      setLoadingData(true)
      Promise.all([fetchUsers(), fetchTeams()])
        .then(([usersData, teamsData]) => {
          setUsers(usersData)
          setTeams(teamsData)
        })
        .catch(() => {
          toast.error('Failed to load users and teams')
        })
        .finally(() => {
          setLoadingData(false)
        })
    }
  }, [open])

  const onSubmit = async (data: ClockForm) => {
    setIsLoading(true)
    try {
      if (isUpdate) {
        await updateClock(currentRow.id, {
          type: data.type,
        })
        toast.success('Clock updated successfully')
      } else {
        await createClock({
          user_id: data.user_id,
          team_id: data.team_id,
          type: data.type,
        })
        toast.success('Clock created successfully')
      }
      onOpenChange(false)
      form.reset()
      refetch?.()
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : 'Failed to save clock'
      )
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Sheet
      open={open}
      onOpenChange={(v) => {
        onOpenChange(v)
        form.reset()
      }}
    >
      <SheetContent className='flex flex-col'>
        <SheetHeader className='text-start'>
          <SheetTitle>{isUpdate ? 'Update' : 'Create'} Clock</SheetTitle>
          <SheetDescription>
            {isUpdate
              ? 'Update the clock entry by changing the type.'
              : 'Create a new clock entry by selecting user, team, and type.'}
          </SheetDescription>
        </SheetHeader>
        <Form {...form}>
          <form
            id='clocks-form'
            onSubmit={form.handleSubmit(onSubmit)}
            className='flex-1 space-y-6 overflow-y-auto px-4'
          >
            {loadingData ? (
              <div className='flex items-center justify-center py-8'>
                <Loader2 className='h-6 w-6 animate-spin' />
              </div>
            ) : (
              <>
                <FormField
                  control={form.control}
                  name='user_id'
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>User</FormLabel>
                      <SelectDropdown
                        defaultValue={field.value > 0 ? field.value.toString() : undefined}
                        onValueChange={(v) => field.onChange(parseInt(v))}
                        placeholder='Select a user'
                        disabled={isUpdate}
                        items={users.map((user) => ({
                          label: `${user.firstname} ${user.lastname}`,
                          value: user.id.toString(),
                        }))}
                      />
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name='team_id'
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Team</FormLabel>
                      <SelectDropdown
                        defaultValue={field.value > 0 ? field.value.toString() : undefined}
                        onValueChange={(v) => field.onChange(parseInt(v))}
                        placeholder='Select a team'
                        disabled={isUpdate}
                        items={teams.map((team) => ({
                          label: team.name,
                          value: team.id.toString(),
                        }))}
                      />
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name='type'
                  render={({ field }) => (
                    <FormItem className='relative'>
                      <FormLabel>Type</FormLabel>
                      <FormControl>
                        <RadioGroup
                          onValueChange={field.onChange}
                          defaultValue={field.value}
                          className='flex flex-col space-y-1'
                        >
                          <FormItem className='flex items-center'>
                            <FormControl>
                              <RadioGroupItem value='arrival' />
                            </FormControl>
                            <FormLabel className='font-normal'>
                              Arrival
                            </FormLabel>
                          </FormItem>
                          <FormItem className='flex items-center'>
                            <FormControl>
                              <RadioGroupItem value='departure' />
                            </FormControl>
                            <FormLabel className='font-normal'>
                              Departure
                            </FormLabel>
                          </FormItem>
                        </RadioGroup>
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </>
            )}
          </form>
        </Form>
        <SheetFooter className='gap-2'>
          <SheetClose asChild>
            <Button variant='outline' disabled={isLoading}>
              Close
            </Button>
          </SheetClose>
          <Button
            form='clocks-form'
            type='submit'
            disabled={isLoading || loadingData}
          >
            {isLoading ? (
              <>
                <Loader2 className='mr-2 h-4 w-4 animate-spin' />
                Saving...
              </>
            ) : (
              'Save changes'
            )}
          </Button>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  )
}
