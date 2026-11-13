import { createContext, useContext } from 'react'
import type { User } from '../lib/api'

type AuthValue = { user: User | null; accessToken: string | null; login: (email: string, password: string) => Promise<void>; logout: () => void }
export const AuthContext = createContext<AuthValue | null>(null)

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('AuthProvider manquant.')
  return context
}
