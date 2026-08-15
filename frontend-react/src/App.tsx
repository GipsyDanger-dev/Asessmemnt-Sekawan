import { type FormEvent, useState } from 'react'
import { Navigate, Route, Routes, useNavigate } from 'react-router-dom'
import { useAuth } from './features/auth/AuthContext'
import './App.css'

function LoginPage() {
  const { user, login } = useAuth(); const navigate = useNavigate()
  const [email, setEmail] = useState('admin@vehicle.test'); const [password, setPassword] = useState('Password123!')
  const [error, setError] = useState(''); const [loading, setLoading] = useState(false)
  if (user) return <Navigate to="/dashboard" replace />
  async function submit(event: FormEvent) { event.preventDefault(); setLoading(true); setError(''); try { await login(email, password); navigate('/dashboard') } catch { setError('Email atau password tidak valid.') } finally { setLoading(false) } }
  return <main className="login-shell"><section className="login-card"><p className="eyebrow">VEHICLE OPERATIONS</p><h1>Booking kendaraan, tanpa bentrok jadwal.</h1><p className="muted">Masuk untuk mengelola kendaraan, driver, dan approval perjalanan operasional.</p><form onSubmit={submit}><label>Email<input value={email} onChange={(e) => setEmail(e.target.value)} type="email" required /></label><label>Password<input value={password} onChange={(e) => setPassword(e.target.value)} type="password" required /></label>{error && <p className="error">{error}</p>}<button disabled={loading}>{loading ? 'Memproses...' : 'Masuk ke aplikasi'}</button></form><p className="hint">Demo: admin@vehicle.test / Password123!</p></section></main>
}

function Dashboard() {
  const { user, logout } = useAuth(); const navigate = useNavigate(); if (!user) return <Navigate to="/login" replace />
  return <main className="app-shell"><aside><p className="eyebrow">VEHICLE OPS</p><h2>Control Center</h2><nav><a className="active">Dashboard</a><a>Booking</a><a>Kendaraan</a><a>Driver</a><a>Laporan</a></nav></aside><section className="content"><header><div><p className="eyebrow">OVERVIEW</p><h1>Selamat datang, {user.name}</h1><p className="muted">Pantau pemakaian armada dan approval perjalanan.</p></div><button className="secondary" onClick={() => { logout(); navigate('/login') }}>Keluar</button></header><div className="stats"><article><span>Booking hari ini</span><strong>—</strong><small>Menunggu data dashboard</small></article><article><span>Menunggu approval</span><strong>—</strong><small>Data akan tersedia berikutnya</small></article><article><span>Kendaraan tersedia</span><strong>2</strong><small>Seed data lokal</small></article></div><section className="empty"><h2>Fondasi dashboard siap</h2><p>Endpoint dashboard, daftar booking, dan grafik akan menjadi langkah implementasi selanjutnya.</p></section></section></main>
}

export default function App() { return <Routes><Route path="/login" element={<LoginPage />} /><Route path="/dashboard" element={<Dashboard />} /><Route path="*" element={<Navigate to="/dashboard" replace />} /></Routes> }
