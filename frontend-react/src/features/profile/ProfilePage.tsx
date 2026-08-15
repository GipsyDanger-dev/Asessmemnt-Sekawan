import { useNavigate } from 'react-router-dom'
import { useAuth } from '../auth/AuthContext'

export function ProfilePage() {
  const { user, logout } = useAuth(); const navigate = useNavigate()
  if (!user) return null
  return <section className="booking-page"><header><div><p className="kicker">ACCOUNT</p><h1>Profil akun.</h1><p>Informasi sesi dan peran akses Anda.</p></div></header><section className="detail-card"><div className="profile-avatar">{user.name.slice(0, 1)}</div><div className="detail-grid"><p><strong>Nama</strong>{user.name}</p><p><strong>Email</strong>{user.email}</p><p><strong>Peran</strong>{user.role === 'admin' ? 'Admin Pool' : `Approver Level ${user.approval_level}`}</p><p><strong>Status sesi</strong><span className="badge">Aktif</span></p></div><button className="danger" onClick={() => { logout(); navigate('/login') }}>Keluar dari sesi</button></section></section>
}
