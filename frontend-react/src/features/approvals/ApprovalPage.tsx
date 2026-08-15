import { useState } from 'react'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Navigate } from 'react-router-dom'
import { api } from '../../lib/api'
import { useAuth } from '../auth/AuthContext'
import './ApprovalPage.css'

type Approval = { approval_id: number; approval_level: number; booking_id: number; booking_number: string; requester_name: string; department: string; destination: string; start_at: string; end_at: string; passenger_count: number; license_plate: string; vehicle_type: string; driver_name: string }

export function ApprovalPage() {
  const { user } = useAuth(); const client = useQueryClient(); const [rejecting, setRejecting] = useState<number | null>(null); const [remarks, setRemarks] = useState(''); const [error, setError] = useState('')
  const inbox = useQuery({ queryKey: ['approval-inbox'], queryFn: async () => (await api.get<{ data: Approval[] }>('/approvals/inbox')).data.data })
  if (!user) return <Navigate to="/login" replace />
  if (user.role !== 'approver') return <Navigate to="/dashboard" replace />
  async function decide(item: Approval, decision: 'approve' | 'reject') { setError(''); try { await api.post(`/bookings/${item.booking_id}/${decision}`, decision === 'reject' ? { remarks } : {}); setRejecting(null); setRemarks(''); client.invalidateQueries({ queryKey: ['approval-inbox'] }) } catch (e: any) { setError(e.response?.data?.message ?? 'Aksi tidak dapat diproses.') } }
  return <section className="approval-page"><header><div><p className="kicker">APPROVAL INBOX</p><h1>Menunggu keputusanmu.</h1><p>Review detail perjalanan sebelum melanjutkan ke tahap berikutnya.</p></div><span className="inbox-count">{inbox.data?.length ?? 0} pending</span></header>{error && <p className="error">{error}</p>}<div className="approval-grid">{inbox.isLoading ? <p>Memuat inbox...</p> : inbox.data?.length ? inbox.data.map((item) => <article className="approval-card" key={item.approval_id}><div className="approval-top"><span>LEVEL {item.approval_level}</span><b>{item.booking_number}</b></div><h2>{item.destination}</h2><p className="requester">{item.requester_name} · {item.department}</p><dl><div><dt>Armada</dt><dd>{item.license_plate} · {item.vehicle_type}</dd></div><div><dt>Driver</dt><dd>{item.driver_name}</dd></div><div><dt>Jadwal</dt><dd>{new Date(item.start_at).toLocaleString('id-ID')}</dd></div></dl>{rejecting === item.approval_id ? <div className="reject-box"><textarea value={remarks} onChange={(e) => setRemarks(e.target.value)} placeholder="Alasan penolakan (wajib)" /><div><button className="ghost" onClick={() => setRejecting(null)}>Batal</button><button className="danger" onClick={() => decide(item, 'reject')}>Konfirmasi reject</button></div></div> : <div className="approval-actions"><button className="ghost danger-text" onClick={() => setRejecting(item.approval_id)}>Reject</button><button onClick={() => decide(item, 'approve')}>Approve</button></div>}</article>) : <section className="inbox-empty"><span>✓</span><h2>Inbox bersih</h2><p>Tidak ada approval yang membutuhkan tindakan saat ini.</p></section>}</div></section>
}
