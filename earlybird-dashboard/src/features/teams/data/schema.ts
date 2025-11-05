import { z } from 'zod'

export const teamSchema = z.object({
  id: z.number(),
  name: z.string(),
  description: z.string().nullable(),
  manager: z
    .object({
      id: z.number(),
      firstname: z.string(),
      lastname: z.string(),
      email: z.string(),
    })
    .nullable(),
  members: z.array(
    z.object({
      id: z.number(),
      firstname: z.string(),
      lastname: z.string(),
      email: z.string(),
      start_time: z.string().nullable(),
      end_time: z.string().nullable(),
    })
  ),
})

export type Team = z.infer<typeof teamSchema>
