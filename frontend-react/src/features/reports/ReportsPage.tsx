import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'

type ReportRow = { id: number; booking_number: string; requester_name: string; destination: string; license_plate: string; driver_name: string; start_at: string; status: string }
export function ReportsPage() {
  const report = useQuery({ queryKey: ['booking-report'], queryFn: async () => (await api.get<{ data: ReportRow[] }>('/reports/bookings')).data.data })
  async function download() { const response = await api.get('/reports/bookings/export', { responseType: 'blob' }); const url = URL.createObjectURL(response.data); const link = document.createElement('a'); link.href = url; link.download = 'vehicle-booking-report.xlsx'; link.click(); URL.revokeObjectURL(url) }
  return <section className="booking-page"><header><div><p className="kicker">REPORTING</p><h1>Laporan booking.</h1><p>Rekap perjalanan dan pemakaian armada.</p></div><button onClick={download}>Export Excel</button></header><section className="table-card"><table><thead><tr><th>Booking</th><th>Pemohon</th><th>Tujuan</th><th>Armada</th><th>Waktu</th><th>Status</th></tr></thead><tbody>{report.isLoading ? <tr><td colSpan={6}>Memuat laporan...</td></tr> : report.data?.map((row) => <tr key={row.id}><td>{row.booking_number}</td><td>{row.requester_name}</td><td>{row.destination}</td><td>{row.license_plate} · {row.driver_name}</td><td>{new Date(row.start_at).toLocaleString('id-ID')}</td><td><span className="badge">{row.status.replaceAll('_',' ')}</span></td></tr>)}</tbody></table></section></section>
}
