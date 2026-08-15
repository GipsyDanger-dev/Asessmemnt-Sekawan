import { useQuery } from '@tanstack/react-query'
import { Bar, BarChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { NavLink } from 'react-router-dom'
import { api } from '../../lib/api'

type Summary = { total: number; statuses: Record<string, number>; vehicles_available: number; vehicles_in_use: number }
export function DashboardPage() {
  const summary = useQuery({ queryKey: ['dashboard-summary'], queryFn: async () => (await api.get<{ data: Summary }>('/dashboard/summary')).data.data })
  const trend = useQuery({ queryKey: ['dashboard-trend'], queryFn: async () => (await api.get<{ data: { month: string; total: number }[] }>('/dashboard/booking-trend')).data.data })
  const data = summary.data
  return <><header className="page-header"><div><p className="kicker">OPERATIONS OVERVIEW</p><h1>Operational control.</h1><p>Ringkasan penggunaan armada dan keputusan booking terkini.</p></div><NavLink className="primary-link" to="/bookings">+ Booking baru</NavLink></header><section className="metric-grid"><article><span>Total booking</span><strong>{data?.total ?? '—'}</strong><small>Periode bulan berjalan</small></article><article><span>Menunggu approval</span><strong>{data ? data.statuses.PENDING_LEVEL_1 + data.statuses.PENDING_LEVEL_2 : '—'}</strong><small>Butuh tindakan approver</small></article><article><span>Armada tersedia</span><strong>{data?.vehicles_available ?? '—'}</strong><small>{data?.vehicles_in_use ?? 0} sedang digunakan</small></article></section><section className="overview-grid"><article className="activity-card"><div><p className="kicker">BOOKING VOLUME</p><h2>Trend booking 6 bulan</h2></div><div className="chart-wrap"><ResponsiveContainer width="100%" height={190}><BarChart data={trend.data ?? []}><XAxis dataKey="month" fontSize={10} /><YAxis fontSize={10} allowDecimals={false} /><Tooltip /><Bar dataKey="total" fill="#2563eb" radius={[3,3,0,0]} /></BarChart></ResponsiveContainer></div></article><article className="fleet-card"><p className="kicker">FLEET PULSE</p><h2>Approval status</h2><div className="fleet-number">{data?.statuses.APPROVED ?? 0} <span>approved</span></div><div className="progress"><i style={{ width: `${data?.total ? ((data.statuses.APPROVED / data.total) * 100) : 0}%` }} /></div><small>{data?.statuses.REJECTED ?? 0} rejected · {data?.statuses.COMPLETED ?? 0} completed</small></article></section></>
}
