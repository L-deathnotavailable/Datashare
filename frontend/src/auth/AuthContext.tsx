import { useEffect, useState, type ReactNode } from 'react'
import { apiRequest, type LoginResponse, type User } from '../lib/api'
import { AuthContext } from './useAuth'

type Session = { user: User; accessToken: string; expiresAt: number }

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<Session | null>(null)
  useEffect(() => {
    if (!session) return
    const timer = window.setTimeout(() => setSession(null), Math.max(0, session.expiresAt - Date.now()))
    return () => window.clearTimeout(timer)
  }, [session])

  async function login(email: string, password: string) {
    const result = await apiRequest<LoginResponse>('/auth/login', { method: 'POST', body: JSON.stringify({ email, password }) })
    const user = await apiRequest<User>('/me', {}, result.accessToken)
    setSession({ user, accessToken: result.accessToken, expiresAt: Date.now() + result.expiresIn * 1000 })
  }
  return <AuthContext.Provider value={{ user: session?.user ?? null, accessToken: session?.accessToken ?? null, login, logout: () => setSession(null) }}>{children}</AuthContext.Provider>
}

