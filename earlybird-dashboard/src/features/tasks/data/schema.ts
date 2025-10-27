import { z } from 'zod'

// Clock schema matching the API response
export const clockSchema = z.object({
  id: z.number(),
  timestamp: z.string(),
  type: z.enum(['arrival', 'departure']),
  user: z.object({
    id: z.number(),
    firstname: z.string(),
    lastname: z.string(),
    email: z.string(),
  }),
  team: z.object({
    id: z.number(),
    name: z.string(),
  }),
})

export type Clock = z.infer<typeof clockSchema>

// Keep Task type for backwards compatibility
export const taskSchema = clockSchema
export type Task = Clock
