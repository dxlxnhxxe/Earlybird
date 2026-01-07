import { useState } from 'react'
import { z } from 'zod'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { Link, useNavigate } from '@tanstack/react-router'
import { Loader2, LogIn } from 'lucide-react'
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

const formSchema = z.object({
  email: z.email({
    error: (iss) => (iss.input === '' ? 'Please enter your email' : undefined),
  }),
  password: z
    .string()
    .min(1, 'Please enter your password')
    .min(7, 'Password must be at least 7 characters long'),
})

interface UserAuthFormProps extends React.HTMLAttributes<HTMLFormElement> {
  redirectTo?: string
}

export function UserAuthForm({
  className,
  redirectTo,
  ...props
}: UserAuthFormProps) {
  const [isLoading, setIsLoading] = useState(false)
  const navigate = useNavigate()
  const { auth } = useAuthStore()

  const form = useForm<z.infer<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      email: '',
      password: '',
    },
  })

  async function onSubmit(data: z.infer<typeof formSchema>) {
    setIsLoading(true)

    try {
      const API_URL = import.meta.env.VITE_API_BASE_URL || 'http://earlybird-api'

      const response = await axios.post(`${API_URL}/login`, {
        email: data.email,
        password: data.password,
      })

      const { token, refresh_token, user } = response.data

      // Stocker le refresh token dans localStorage
      localStorage.setItem('refresh_token_local', refresh_token)

      // Stocker le token d'accès dans un cookie
      document.cookie = `thisisjustarandomstring=${token}; path=/; max-age=${7 * 24 * 60 * 60}`

      // Mettre à jour le store avec les informations utilisateur
      auth.setUser({
        id: user.id,
        email: user.email,
        firstname: user.firstname,
        lastname: user.lastname,
        roles: user.roles,
      })
      auth.setAccessToken(token)

      // Redirection vers le dashboard
      const targetPath = redirectTo || '/'
      navigate({ to: targetPath, replace: true })

      toast.success(`Bienvenue ${user.firstname} ${user.lastname}!`)
    } catch (error) {
      // Capturer TOUTES les erreurs et les gérer ici
      // Ne jamais laisser l'erreur remonter au niveau global
      console.error('Erreur de connexion:', error)

      if (axios.isAxiosError(error)) {
        if (error.response) {
          // Erreur avec réponse du serveur
          const status = error.response.status
          const errorData = error.response.data?.error || 'Erreur lors de la connexion'

          switch (status) {
            case 400:
              // Email invalide ou champs manquants
              toast.error(errorData)
              break
            case 401:
              // Utilisateur introuvable ou mot de passe incorrect
              toast.error('Email ou mot de passe incorrect. Veuillez réessayer.')
              // Optionnel: vider le champ password
              form.setValue('password', '')
              break
            case 500:
            case 502:
            case 503:
            case 504:
              // Erreurs serveur
              toast.error('Le serveur rencontre un problème. Veuillez réessayer plus tard.')
              break
            default:
              toast.error(errorData)
          }
        } else if (error.request) {
          // Serveur inaccessible (pas de réponse reçue)
          toast.error('Impossible de contacter le serveur. Vérifiez votre connexion internet ou que le backend est démarré.')
        } else {
          // Erreur de configuration de la requête
          toast.error('Une erreur est survenue lors de la configuration de la requête.')
        }
      } else {
        // Erreur non liée à axios
        toast.error('Une erreur inattendue est survenue.')
      }

      // NE PAS relancer l'erreur - elle est complètement gérée ici
      // return false pour indiquer que le formulaire n'a pas été soumis avec succès
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
            <FormItem className='relative'>
              <FormLabel>Password</FormLabel>
              <FormControl>
                <PasswordInput placeholder='********' {...field} />
              </FormControl>
              <FormMessage />
              <Link
                to='/forgot-password'
                className='text-muted-foreground absolute end-0 -top-0.5 text-sm font-medium hover:opacity-75'
              >
                Forgot password?
              </Link>
            </FormItem>
          )}
        />
        <Button className='mt-2' disabled={isLoading}>
          {isLoading ? <Loader2 className='animate-spin' /> : <LogIn />}
          Sign in
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
          <Button variant='outline' type='button' disabled={isLoading}>
            <IconGithub className='h-4 w-4' /> GitHub
          </Button>
          <Button variant='outline' type='button' disabled={isLoading}>
            <IconFacebook className='h-4 w-4' /> Facebook
          </Button>
        </div>
      </form>
    </Form>
  )
}
