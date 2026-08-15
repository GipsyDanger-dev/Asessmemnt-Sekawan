import { createContext, useContext, useMemo, useState, type ReactNode } from 'react'
import axios from 'axios'
import { api, type SessionUser } from '../../lib/api'

type AuthContextValue = {
  user: SessionUser | null
  login: (email: string, password: string) => Promise<void>
  logout: () => void
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)
const storageKey = 'vehicle_booking_user'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<SessionUser | null>(() => {
    const stored = localStorage.getItem(storageKey)
    return stored ? JSON.parse(stored) as SessionUser : null
  })

  const value = useMemo<AuthContextValue>(() => ({
    user,
    async login(email, password) {
      try {
        const { data } = await api.post<{ token: string; user: SessionUser }>('/auth/login', { email, password })
        localStorage.setItem('vehicle_booking_token', data.token)
        localStorage.setItem(storageKey, JSON.stringify(data.user))
        setUser(data.user)
      } catch (error) {
        if (axios.isAxiosError(error) && error.response?.status === 401) throw error
        throw new Error('Tidak dapat terhubung ke API. Pastikan backend lokal aktif.')
      }
    },
    logout() {
      localStorage.removeItem('vehicle_booking_token')
      localStorage.removeItem(storageKey)
      setUser(null)
    },
  }), [user])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth must be used within AuthProvider')
  return context
}
