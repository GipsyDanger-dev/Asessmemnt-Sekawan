import axios from 'axios'

export type SessionUser = {
  id: number
  name: string
  email: string
  role: 'admin' | 'approver'
  approval_level: number | null
}

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://127.0.0.1:8080/api',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('vehicle_booking_token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})
