import { lazy, Suspense, type FormEvent, type ReactNode, useState } from 'react'
import { NavLink, Navigate, Route, Routes, useNavigate } from 'react-router-dom'
import { useAuth } from './features/auth/AuthContext'
import { BookingPage } from './features/bookings/BookingPage'
import { ApprovalPage } from './features/approvals/ApprovalPage'
import { ProfilePage } from './features/profile/ProfilePage'
import { FleetMonitoringPage } from './features/fleet/FleetMonitoringPage'
import './App.css'

const DashboardPage = lazy(() => import('./features/dashboard/DashboardPage').then((module) => ({ default: module.DashboardPage })))
const ReportsPage = lazy(() => import('./features/reports/ReportsPage').then((module) => ({ default: module.ReportsPage })))
const ActivityLogPage = lazy(() => import('./features/logs/ActivityLogPage').then((module) => ({ default: module.ActivityLogPage })))
const MasterDataPage = lazy(() => import('./features/master/MasterDataPage').then((module) => ({ default: module.MasterDataPage })))

function LoginPage() {
  const { user, login } = useAuth(); const navigate = useNavigate()
  const [email, setEmail] = useState('admin@vehicle.test'); const [password, setPassword] = useState('Password123!'); const [error, setError] = useState(''); const [loading, setLoading] = useState(false)
  if (user) return <Navigate to="/dashboard" replace />
  async function submit(event: FormEvent) { event.preventDefault(); setLoading(true); setError(''); try { await login(email, password); navigate('/dashboard') } catch (error) { setError(error instanceof Error ? error.message : 'Email atau password tidak valid.') } finally { setLoading(false) } }
  return <main className="auth-page"><section className="auth-panel"><div className="brand"><span className="brand-mark">V</span><span>VEHICLE OPS</span></div><p className="kicker">OPERATIONS CONTROL</p><h1>Every trip,<br />under control.</h1><p>Kelola armada, jadwal, dan approval dengan satu alur operasional yang jelas.</p><div className="auth-proof"><span>01</span><span>02</span><span>03</span><b>Booking</b><b>Approval</b><b>Complete</b></div></section><section className="auth-form-wrap"><form className="auth-form" onSubmit={submit}><p className="kicker">SECURE ACCESS</p><h2>Masuk ke workspace</h2><p className="form-copy">Gunakan akun operasional untuk melanjutkan.</p><label>Email<input value={email} onChange={(e) => setEmail(e.target.value)} type="email" required /></label><label>Password<input value={password} onChange={(e) => setPassword(e.target.value)} type="password" required /></label>{error && <p className="error">{error}</p>}<button disabled={loading}>{loading ? 'Memverifikasi...' : 'Masuk'}</button><p className="demo-note">Demo account: admin@vehicle.test</p></form></section></main>
}

export function AppShell({ children }: { children: ReactNode }) {
  const { user, logout } = useAuth(); const navigate = useNavigate(); if (!user) return <Navigate to="/login" replace />
  return <main className="workspace"><aside className="sidebar"><div className="brand"><span className="brand-mark">V</span><span>VEHICLE OPS</span></div><div className="user-card"><span className="avatar">{user.name.slice(0, 1)}</span><div><b>{user.name}</b><small>{user.role === 'admin' ? 'Admin Pool' : 'Approver'}</small></div></div><NavLink className="create-trip" to="/bookings">Buat pemesanan</NavLink><nav><p>OPERATIONS</p><NavLink to="/dashboard">Overview</NavLink>{user.role === 'admin' ? <><NavLink to="/bookings">Pemesanan</NavLink><NavLink to="/vehicles">Kendaraan</NavLink><NavLink to="/fleet-monitoring">Fleet monitoring</NavLink><NavLink to="/drivers">Driver</NavLink><NavLink to="/regions">Region</NavLink><NavLink to="/users">User & Approver</NavLink></> : <NavLink to="/approvals">Approval inbox</NavLink>}<p className="nav-space">MANAGEMENT</p>{user.role === 'admin' && <><NavLink to="/reports">Laporan</NavLink><NavLink to="/activity-logs">Activity log</NavLink></>}<NavLink to="/profile">Profil akun</NavLink></nav><button className="signout" onClick={() => { logout(); navigate('/login') }}>Keluar workspace</button></aside><section className="main-stage"><header className="app-topbar"><div><span className="topbar-dot" /> Sistem armada tersinkron</div><div className="topbar-actions"><span>{user.role === 'admin' ? 'Admin pool' : 'Approver'}</span></div></header><section className="main-content">{children}</section></section></main>
}

function Dashboard() { return <AppShell><Suspense fallback={<p>Memuat dashboard...</p>}><DashboardPage /></Suspense></AppShell> }

 export default function App() { const lazyPage = (page: ReactNode) => <AppShell><Suspense fallback={<p>Memuat halaman...</p>}>{page}</Suspense></AppShell>; return <Routes><Route path="/login" element={<LoginPage />} /><Route path="/dashboard" element={<Dashboard />} /><Route path="/bookings" element={<AppShell><BookingPage /></AppShell>} /><Route path="/approvals" element={<AppShell><ApprovalPage /></AppShell>} /><Route path="/profile" element={<AppShell><ProfilePage /></AppShell>} /><Route path="/fleet-monitoring" element={<AppShell><FleetMonitoringPage /></AppShell>} /><Route path="/reports" element={lazyPage(<ReportsPage />)} /><Route path="/activity-logs" element={lazyPage(<ActivityLogPage />)} /><Route path="/vehicles" element={lazyPage(<MasterDataPage resource="vehicles" />)} /><Route path="/drivers" element={lazyPage(<MasterDataPage resource="drivers" />)} /><Route path="/regions" element={lazyPage(<MasterDataPage resource="regions" />)} /><Route path="/users" element={lazyPage(<MasterDataPage resource="users" />)} /><Route path="*" element={<Navigate to="/dashboard" replace />} /></Routes> }
