import { useState } from 'react'
import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { useNavigate } from '@tanstack/react-router'
import { Loader2, UserPlus } from 'lucide-react'
import { toast } from 'sonner'
import axios from 'axios'
import { IconFacebook, IconGithub } from '@/assets/brand-icons'
import { useAuthStore } from '@/stores/auth-store'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Form,
  FormControl,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form'
import { Input } from '@/components/ui/input'
import { PasswordInput } from '@/components/password-input'

const formSchema = z
  .object({
    email: z.email({
      error: (iss) =>
        iss.input === '' ? 'Please enter your email' : undefined,
    }),
    password: z
      .string()
      .min(1, 'Please enter your password')
      .min(7, 'Password must be at least 7 characters long'),
    confirmPassword: z.string().min(1, 'Please confirm your password'),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: "Passwords don't match.",
    path: ['confirmPassword'],
  })

export function SignUpForm({
  className,
  ...props
}: React.HTMLAttributes<HTMLFormElement>) {
  const [isLoading, setIsLoading] = useState(false)
  const navigate = useNavigate()
  const { auth } = useAuthStore()

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      email: '',
      password: '',
      confirmPassword: '',
    },
  })

  async function onSubmit(data: z.infer<typeof formSchema>) {
    setIsLoading(true)

    try {
      const API_URL =
        import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'

      // Créer le compte
      await axios.post(`${API_URL}/users`, {
        email: data.email,
        password: data.password,
      })

      // Connecter automatiquement l'utilisateur nouvellement créé
      const loginResponse = await axios.post(`${API_URL}/login`, {
        email: data.email,
        password: data.password,
      })

      const { token, refresh_token, user } = loginResponse.data

      localStorage.setItem('refresh_token_local', refresh_token)
      document.cookie = `thisisjustarandomstring=${token}; path=/; max-age=${7 * 24 * 60 * 60}`

      auth.setUser({
        id: user.id,
        email: user.email,
        firstname: user.firstname,
        lastname: user.lastname,
        roles: user.roles,
      })
      auth.setAccessToken(token)

      toast.success('Compte créé avec succès !')
      navigate({ to: '/', replace: true })
    } catch (error) {
      console.error('Erreur lors de la création du compte:', error)

      if (axios.isAxiosError(error)) {
        if (error.response) {
          const status = error.response.status
          const errorData =
            error.response.data?.error ||
            'Erreur lors de la création du compte'

          switch (status) {
            case 400:
              toast.error(errorData)
              break
            case 409:
              toast.error('Un compte existe déjà avec cet email.')
              break
            case 500:
            case 502:
            case 503:
            case 504:
              toast.error(
                'Le serveur rencontre un problème. Veuillez réessayer plus tard.'
              )
              break
            default:
              toast.error(errorData)
          }
        } else if (error.request) {
          toast.error(
            'Impossible de contacter le serveur. Vérifiez votre connexion internet ou que le backend est démarré.'
          )
        } else {
          toast.error(
            'Une erreur est survenue lors de la configuration de la requête.'
          )
        }
      } else {
        toast.error('Une erreur inattendue est survenue.')
      }
    } finally {
      setIsLoading(false)
    }
  }

  return (
    <Form {...form}>
      <form
        onSubmit={form.handleSubmit(onSubmit)}
        className={cn('grid gap-3', className)}
        {...props}
      >
        <FormField
          control={form.control}
          name='email'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Email</FormLabel>
              <FormControl>
                <Input placeholder='name@example.com' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name='password'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <PasswordInput placeholder='********' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <FormField
          control={form.control}
          name='confirmPassword'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Confirm Password</FormLabel>
              <FormControl>
                <PasswordInput placeholder='********' {...field} />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <Button className='mt-2' disabled={isLoading}>
          {isLoading ? <Loader2 className='animate-spin' /> : <UserPlus />}
          Create Account
        </Button>

        <div className='relative my-2'>
          <div className='absolute inset-0 flex items-center'>
            <span className='w-full border-t' />
          </div>
          <div className='relative flex justify-center text-xs uppercase'>
            <span className='bg-background text-muted-foreground px-2'>
              Or continue with
            </span>
          </div>
        </div>

        <div className='grid grid-cols-2 gap-2'>
          <Button
            variant='outline'
            className='w-full'
            type='button'
            disabled={isLoading}
          >
            <IconGithub className='h-4 w-4' /> GitHub
          </Button>
          <Button
            variant='outline'
            className='w-full'
            type='button'
            disabled={isLoading}
          >
            <IconFacebook className='h-4 w-4' /> Facebook
          </Button>
        </div>
      </form>
    </Form>
  )
}
