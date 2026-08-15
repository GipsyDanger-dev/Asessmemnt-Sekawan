import { useQuery } from '@tanstack/react-query'
import { useMemo, useState } from 'react'
import { Bar, BarChart, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { NavLink } from 'react-router-dom'
import { api } from '../../lib/api'

type Attention = { id: number; booking_number: string; destination: string; start_at: string; status: string; license_plate: string }
type Summary = { total: number; statuses: Record<string, number>; vehicles_available: number; vehicles_in_use: number; fleet: { fuel_cost: number; services_due: number }; attention: Attention[] }
type Usage = { by_category: { category: string; total: number }[]; top_vehicles: { license_plate: string; vehicle_type: string; total: number }[] }
type Lookup = { id: number; name: string; vehicle_type?: string }

export function DashboardPage() {
  const [filters, setFilters] = useState({ from: '', to: '', region_id: '', vehicle_type: '' })
  const params = useMemo(() => Object.fromEntries(Object.entries(filters).filter(([, value]) => value)), [filters])
  const summary = useQuery({ queryKey: ['dashboard-summary', params], queryFn: async () => (await api.get<{ data: Summary }>('/dashboard/summary', { params })).data.data })
  const trend = useQuery({ queryKey: ['dashboard-trend', params], queryFn: async () => (await api.get<{ data: { month: string; total: number }[] }>('/dashboard/booking-trend', { params })).data.data })
  const usage = useQuery({ queryKey: ['dashboard-usage', params], queryFn: async () => (await api.get<{ data: Usage }>('/dashboard/vehicle-usage', { params })).data.data })
  const lookups = useQuery({ queryKey: ['dashboard-lookups'], queryFn: async () => {
    const [regions, vehicles] = await Promise.all([api.get<{ data: Lookup[] }>('/regions'), api.get<{ data: Lookup[] }>('/vehicles')])
    return { regions: regions.data.data, types: [...new Set(vehicles.data.data.map((item) => item.vehicle_type).filter(Boolean))] }
  } })
  const change = (key: keyof typeof filters, value: string) => setFilters((current) => ({ ...current, [key]: value }))
  const data = summary.data
  const status = data?.statuses ?? {}
  const pending = (status.PENDING_LEVEL_1 ?? 0) + (status.PENDING_LEVEL_2 ?? 0)

  return <section className="dashboard-page">
    <header className="dashboard-hero">
      <div><p className="kicker">OPERATIONS OVERVIEW</p><h1>Ringkasan operasional</h1><p>Prioritas booking, ketersediaan armada, dan kebutuhan service dalam satu tampilan.</p></div>
      <NavLink className="primary-link" to="/bookings" state={{ openForm: true }}>Buat pemesanan</NavLink>
    </header>
    <details className="dashboard-filters"><summary>Filter periode dan armada</summary><div className="filter-strip">
      <label>Periode mulai<input type="date" value={filters.from} onChange={(event) => change('from', event.target.value)} /></label>
      <label>Periode selesai<input type="date" value={filters.to} onChange={(event) => change('to', event.target.value)} /></label>
      <label>Region<select value={filters.region_id} onChange={(event) => change('region_id', event.target.value)}><option value="">Semua region</option>{lookups.data?.regions.map((item) => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
      <label>Jenis unit<select value={filters.vehicle_type} onChange={(event) => change('vehicle_type', event.target.value)}><option value="">Semua jenis</option>{lookups.data?.types.map((item) => <option key={item} value={item}>{item}</option>)}</select></label>
    </div></details>
    <section className="key-metrics" aria-label="Ringkasan utama"><Metric label="Booking tercatat" value={data?.total ?? '—'} /><Metric label="Menunggu approval" value={pending} emphasis={pending > 0} /><Metric label="Unit tersedia" value={data?.vehicles_available ?? '—'} /><Metric label="Service mendekat" value={data?.fleet.services_due ?? '—'} emphasis={(data?.fleet.services_due ?? 0) > 0} /></section>
    <section className="operations-layout">
      <article className="priority-panel"><header className="section-heading"><div><p className="kicker">ACTION QUEUE</p><h2>Booking yang perlu diperhatikan</h2></div><NavLink to="/bookings">Semua pemesanan</NavLink></header><div className="booking-queue">{data?.attention.length ? data.attention.slice(0, 4).map((booking) => <NavLink className="queue-row" to="/bookings" key={booking.id}><span className="queue-unit">{booking.license_plate}</span><span className="queue-destination"><b>{booking.destination}</b><small>{booking.booking_number} · {new Date(booking.start_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })}</small></span><span className={`status-tag status-${booking.status.toLowerCase()}`}>{booking.status.replaceAll('_', ' ')}</span></NavLink>) : <p className="empty-copy">Tidak ada booking yang memerlukan tindakan saat ini.</p>}</div><section className="trend-section"><div className="section-heading compact"><div><h3>Tren pemesanan</h3><p>Jumlah booking per bulan</p></div></div><ResponsiveContainer width="100%" height={210}><BarChart data={trend.data ?? []} margin={{ top: 8, right: 8, left: -22, bottom: 0 }}><XAxis dataKey="month" fontSize={12} axisLine={false} tickLine={false} /><YAxis fontSize={12} allowDecimals={false} axisLine={false} tickLine={false} /><Tooltip /><Bar dataKey="total" fill="#2563eb" radius={[4, 4, 0, 0]} /></BarChart></ResponsiveContainer></section></article>
      <aside className="operations-rail"><section className="fleet-state"><header className="section-heading"><div><p className="kicker">FLEET STATUS</p><h2>Kondisi armada</h2></div><NavLink to="/fleet-monitoring">Monitoring</NavLink></header><dl><StateRow label="Unit tersedia" value={data?.vehicles_available ?? '—'} tone="available" /><StateRow label="Sedang digunakan" value={data?.vehicles_in_use ?? '—'} tone="in-use" /><StateRow label="Perlu dijadwalkan service" value={data?.fleet.services_due ?? '—'} tone="service" /></dl><div className="fuel-summary"><span>Biaya BBM pada periode ini</span><strong>{data ? `Rp ${data.fleet.fuel_cost.toLocaleString('id-ID')}` : '—'}</strong></div></section><section className="usage-panel"><header className="section-heading"><div><p className="kicker">UTILISATION</p><h2>Pemakaian kendaraan</h2></div></header><div className="usage-layout"><ResponsiveContainer width="42%" height={154}><PieChart><Pie data={usage.data?.by_category ?? []} dataKey="total" nameKey="category" innerRadius={38} outerRadius={61}>{(usage.data?.by_category ?? []).map((item, index) => <Cell key={item.category} fill={index === 0 ? '#2563eb' : '#94a3b8'} />)}</Pie><Tooltip /></PieChart></ResponsiveContainer><ol className="top-vehicles">{usage.data?.top_vehicles.slice(0, 3).map((vehicle, index) => <li key={vehicle.license_plate}><span>{String(index + 1).padStart(2, '0')}</span><div><b>{vehicle.license_plate}</b><small>{vehicle.vehicle_type}</small></div><strong>{vehicle.total}x</strong></li>) || <li className="empty-copy">Belum ada data pemakaian.</li>}</ol></div></section></aside>
    </section>
  </section>
}

function Metric({ label, value, emphasis = false }: { label: string; value: string | number; emphasis?: boolean }) { return <div className={emphasis ? 'metric metric-alert' : 'metric'}><span>{label}</span><strong>{value}</strong></div> }
function StateRow({ label, value, tone }: { label: string; value: string | number; tone: string }) { return <div className="state-row"><dt><i className={tone} />{label}</dt><dd>{value}</dd></div> }
