import { useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'

export function ProfilePage() {
  const { user, logout } = useAuth(); const navigate = useNavigate()
  if (!user) return null
  return <section className="booking-page"><header><div><p className="kicker">ACCOUNT</p><h1>Profil akun.</h1><p>Informasi sesi dan peran akses Anda.</p></div></header><section className="profile-panel"><div className="profile-summary"><div className="profile-avatar">{user.name.slice(0, 1)}</div><div><h2>{user.name}</h2><p>{user.role === 'admin' ? 'Admin Pool' : `Approver Level ${user.approval_level}`}</p></div></div><dl className="profile-details"><div><dt>Email</dt><dd>{user.email}</dd></div><div><dt>Peran akses</dt><dd>{user.role === 'admin' ? 'Admin Pool' : `Approver Level ${user.approval_level}`}</dd></div><div><dt>Status sesi</dt><dd><span className="session-status">Aktif</span></dd></div></dl><button className="logout-button" onClick={() => { logout(); navigate('/login') }}>Keluar dari sesi</button></section></section>
}
