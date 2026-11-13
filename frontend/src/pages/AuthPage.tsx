import { useState, type FormEvent } from 'react'
import { Link, Navigate, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/useAuth'
import { ApiError, apiRequest } from '../lib/api'

export function AuthPage({ mode }: { mode: 'login' | 'register' }) {
  const register = mode === 'register'
  const { user, login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [confirmation, setConfirmation] = useState('')
  const [pending, setPending] = useState(false)
  const [error, setError] = useState('')
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({})
  if (user) return <Navigate to="/mon-espace" replace />

  async function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (pending) return
    setError('')
    setFieldErrors({})
    if (register && password !== confirmation) {
      setFieldErrors({ confirmation: 'Les mots de passe doivent être identiques.' })
      return
    }
    setPending(true)
    try {
      if (register) {
        await apiRequest('/auth/register', { method: 'POST', body: JSON.stringify({ email: email.trim().toLowerCase(), password }) })
        setPassword('')
        setConfirmation('')
        navigate('/connexion', { replace: true, state: { registered: true } })
      } else {
        await login(email.trim().toLowerCase(), password)
        navigate('/mon-espace', { replace: true })
      }
    } catch (failure) {
      setError(failure instanceof Error ? failure.message : 'Une erreur est survenue.')
      if (failure instanceof ApiError) setFieldErrors(failure.errors)
    } finally {
      setPending(false)
    }
  }

  return (
    <div className="auth-layout">
      <section className="form-panel" aria-labelledby="form-title">
        <h1 id="form-title">{register ? 'Créer un compte' : 'Connexion'}</h1>
        {!register && location.state?.registered && <p className="notice success" role="status">Votre compte est créé. Vous pouvez vous connecter.</p>}
        {error && <p className="notice error" role="alert">{error}</p>}
        <form onSubmit={submit} aria-busy={pending}>
          <div className="field">
            <label htmlFor="email">Email</label>
            <input id="email" name="email" type="email" autoComplete="email" required maxLength={254} value={email} onChange={e => setEmail(e.target.value)} placeholder="Saisissez votre email..." aria-invalid={Boolean(fieldErrors.email)} aria-describedby={fieldErrors.email ? 'email-error' : undefined} />
            {fieldErrors.email && <span className="field-error" id="email-error">{fieldErrors.email}</span>}
          </div>
          <div className="field">
            <label htmlFor="password">Mot de passe</label>
            
              <input id="password" name="password" placeholder="Saisissez votre mot de passe..." type="password" autoComplete={register ? 'new-password' : 'current-password'} required minLength={register ? 8 : undefined} maxLength={4096} value={password} onChange={e => setPassword(e.target.value)} aria-invalid={Boolean(fieldErrors.password)} aria-describedby={fieldErrors.password ? 'password-error' : undefined} />


            {fieldErrors.password && <span className="field-error" id="password-error">{fieldErrors.password}</span>}
          </div>
          {register && <div className="field"><label htmlFor="confirmation">Verification du mot de passe</label><input id="confirmation" name="confirmation" placeholder="Saisissez le à nouveau" type="password" autoComplete="new-password" required value={confirmation} onChange={e => setConfirmation(e.target.value)} aria-invalid={Boolean(fieldErrors.confirmation)} aria-describedby={fieldErrors.confirmation ? 'confirmation-error' : undefined} />{fieldErrors.confirmation && <span className="field-error" id="confirmation-error" role="alert">{fieldErrors.confirmation}</span>}</div>}
          <p className="form-switch"><Link to={register ? '/connexion' : '/inscription'}>{register ? "J’ai déjà un compte" : 'Créer un compte'}</Link></p>
          <button className="button primary" type="submit" disabled={pending}>{pending ? 'Un instant…' : register ? 'Créer mon compte' : 'Connexion'}</button>
        </form>
      </section>
    </div>
  )
}
