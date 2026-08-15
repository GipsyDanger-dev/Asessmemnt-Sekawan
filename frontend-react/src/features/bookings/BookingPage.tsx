import { type FormEvent, useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Navigate } from 'react-router-dom'
import { api } from '../../lib/api'
import { useAuth } from '../auth/AuthContext'

type Lookup = { id: number; name?: string; license_plate?: string; vehicle_type?: string; approval_level?: number }
type Booking = { id: number; booking_number: string; requester_name: string; destination: string; start_at: string; end_at: string; status: string; license_plate: string; driver_name: string }
const initial = { requester_name: '', requester_nik: '', department: '', region_id: '', vehicle_id: '', driver_id: '', purpose: '', destination: '', start_at: '', end_at: '', passenger_count: '1', requested_vehicle_category: 'PASSENGER', approver_level_1_id: '', approver_level_2_id: '', notes: '' }

export function BookingPage() {
  const { user } = useAuth()
  const client = useQueryClient(); const [showForm, setShowForm] = useState(false); const [form, setForm] = useState(initial); const [error, setError] = useState('')
  if (!user) return <Navigate to="/login" replace />
  const bookings = useQuery({ queryKey: ['bookings'], queryFn: async () => (await api.get<{ data: Booking[] }>('/bookings')).data.data })
  const lookups = useQuery({ queryKey: ['booking-lookups'], queryFn: async () => {
    const [regions, vehicles, drivers, approvers] = await Promise.all(['regions','vehicles','drivers','approvers'].map((path) => api.get<{ data: Lookup[] }>(`/${path}`)))
    return { regions: regions.data.data, vehicles: vehicles.data.data, drivers: drivers.data.data, approvers: approvers.data.data }
  } })
  const set = (key: keyof typeof initial, value: string) => setForm((current) => ({ ...current, [key]: value }))
  async function submit(event: FormEvent) { event.preventDefault(); setError(''); try { await api.post('/bookings', { ...form, region_id: +form.region_id, vehicle_id: +form.vehicle_id, driver_id: +form.driver_id, passenger_count: +form.passenger_count, approver_level_1_id: +form.approver_level_1_id, approver_level_2_id: +form.approver_level_2_id }); setForm(initial); setShowForm(false); client.invalidateQueries({ queryKey: ['bookings'] }) } catch (e: any) { setError(e.response?.data?.message ?? 'Booking gagal dibuat.') } }
  const lookup = lookups.data
  return <section className="booking-page"><header><div><p className="eyebrow">BOOKINGS</p><h1>Pemesanan kendaraan</h1><p className="muted">Buat dan pantau perjalanan operasional.</p></div><button onClick={() => setShowForm(!showForm)}>{showForm ? 'Tutup form' : '+ Booking baru'}</button></header>
    {showForm && <form className="booking-form" onSubmit={submit}><h2>Informasi pemohon</h2><div className="form-grid"><Field label="Nama pemohon" value={form.requester_name} onChange={(v) => set('requester_name',v)} /><Field label="NIK" value={form.requester_nik} onChange={(v) => set('requester_nik',v)} /><Field label="Departemen" value={form.department} onChange={(v) => set('department',v)} /><Select label="Region" value={form.region_id} onChange={(v) => set('region_id',v)} options={lookup?.regions ?? []} /></div><h2>Jadwal dan armada</h2><div className="form-grid"><Field label="Tujuan" value={form.destination} onChange={(v) => set('destination',v)} /><Field label="Keperluan" value={form.purpose} onChange={(v) => set('purpose',v)} /><Field label="Mulai" type="datetime-local" value={form.start_at} onChange={(v) => set('start_at',v)} /><Field label="Selesai" type="datetime-local" value={form.end_at} onChange={(v) => set('end_at',v)} /><Select label="Kendaraan" value={form.vehicle_id} onChange={(v) => set('vehicle_id',v)} options={lookup?.vehicles ?? []} vehicle /><Select label="Driver" value={form.driver_id} onChange={(v) => set('driver_id',v)} options={lookup?.drivers ?? []} /><Select label="Approver L1" value={form.approver_level_1_id} onChange={(v) => set('approver_level_1_id',v)} options={(lookup?.approvers ?? []).filter((x) => x.approval_level === 1)} /><Select label="Approver L2" value={form.approver_level_2_id} onChange={(v) => set('approver_level_2_id',v)} options={(lookup?.approvers ?? []).filter((x) => x.approval_level === 2)} /></div>{error && <p className="error">{error}</p>}<button type="submit">Kirim untuk approval</button></form>}
    <section className="table-card"><table><thead><tr><th>No. booking</th><th>Pemohon</th><th>Armada</th><th>Tujuan</th><th>Status</th></tr></thead><tbody>{bookings.isLoading ? <tr><td colSpan={5}>Memuat booking...</td></tr> : bookings.data?.length ? bookings.data.map((b) => <tr key={b.id}><td>{b.booking_number}</td><td>{b.requester_name}</td><td>{b.license_plate} · {b.driver_name}</td><td>{b.destination}</td><td><span className="badge">{b.status.replaceAll('_',' ')}</span></td></tr>) : <tr><td colSpan={5}>Belum ada booking.</td></tr>}</tbody></table></section>
  </section>
}
function Field({ label, value, onChange, type = 'text' }: { label: string; value: string; onChange: (value: string) => void; type?: string }) { return <label>{label}<input required type={type} value={value} onChange={(e) => onChange(e.target.value)} /></label> }
function Select({ label, value, onChange, options, vehicle }: { label: string; value: string; onChange: (value: string) => void; options: Lookup[]; vehicle?: boolean }) { return <label>{label}<select required value={value} onChange={(e) => onChange(e.target.value)}><option value="">Pilih {label}</option>{options.map((option) => <option value={option.id} key={option.id}>{vehicle ? `${option.license_plate} · ${option.vehicle_type}` : option.name}</option>)}</select></label> }
