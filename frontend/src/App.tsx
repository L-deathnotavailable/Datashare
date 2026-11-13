import { BrowserRouter, Link, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './auth/AuthContext'
import { useAuth } from './auth/useAuth'
import { AuthPage } from './pages/AuthPage'
import './App.css'

function AccountPage() {
  const { user, logout } = useAuth()
  if (!user) return <Navigate to="/connexion" replace />
  return (
    <section className="account-panel" aria-labelledby="account-title">
      <p className="eyebrow">MON ESPACE</p>
      <h1 id="account-title">Bienvenue chez vous.</h1>
      <p className="account-email">{user.email}</p>
      <div className="empty-state">
        <span className="file-symbol" aria-hidden="true">↗</span>
        <h2>Une place pour vos prochains partages</h2>
        <p>Votre compte est prêt. Le partage de fichiers sera disponible prochainement.</p>
      </div>
      <button className="button secondary" onClick={logout}>Se déconnecter</button>
    </section>
  )
}

function Layout() {
  const { user } = useAuth()
  return (
    <div className="app-shell">
      <header className="site-header">
        <Link className="brand" to={user ? '/mon-espace' : '/connexion'} aria-label="DataShare, accueil">
          DataShare
        </Link>
        <nav aria-label="Navigation principale">
          {user ? <Link to="/mon-espace">Mon espace</Link> : <Link to="/connexion">Se connecter</Link>}
        </nav>
      </header>
      <main id="main-content">
        <Routes>
          <Route path="/connexion" element={<AuthPage key="login" mode="login" />} />
          <Route path="/inscription" element={<AuthPage key="register" mode="register" />} />
          <Route path="/mon-espace" element={<AccountPage />} />
          <Route path="*" element={<Navigate to={user ? '/mon-espace' : '/connexion'} replace />} />
        </Routes>
      </main>
      
    </div>
  )
}

export default function App() {
  return <BrowserRouter><AuthProvider><Layout /></AuthProvider></BrowserRouter>
}
