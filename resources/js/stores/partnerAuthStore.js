import { create } from 'zustand';
import { persist } from 'zustand/middleware';

const API_BASE = '/api/partner';

const usePartnerAuthStore = create(
  persist(
    (set, get) => ({
      provider: null,
      token: null,
      loading: false,
      error: null,

      login: async (accessCode) => {
        set({ loading: true, error: null });
        try {
          const res = await fetch(`${API_BASE}/login`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ access_code: accessCode }),
          });
          const data = await res.json();
          if (!res.ok) {
            throw new Error(data.message || 'Invalid access code');
          }
          set({
            provider: data.data?.provider || data.provider,
            token: data.data?.token || data.token,
            loading: false,
            error: null,
          });
          return true;
        } catch (err) {
          set({ loading: false, error: err.message });
          return false;
        }
      },

      logout: () => {
        set({ provider: null, token: null, error: null });
      },

      fetchProvider: async () => {
        const { token } = get();
        if (!token) return;
        try {
          const res = await fetch(`${API_BASE}/dashboard`, {
            headers: {
              Authorization: `Bearer ${token}`,
              Accept: 'application/json',
            },
          });
          if (!res.ok) {
            if (res.status === 401 || res.status === 403) {
              set({ provider: null, token: null });
            }
            return;
          }
          // Provider info is on the token itself; we already have it from login
        } catch {
          // offline or network error — keep existing state
        }
      },

      apiGet: async (url, params = {}) => {
        const { token } = get();
        if (!token) throw new Error('Not authenticated');
        const query = new URLSearchParams(params).toString();
        const fullUrl = `${API_BASE}${url}${query ? '?' + query : ''}`;
        const res = await fetch(fullUrl, {
          headers: {
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
        });
        if (!res.ok) {
          if (res.status === 401 || res.status === 403) {
            set({ provider: null, token: null });
            throw new Error('Session expired. Please log in again.');
          }
          const err = await res.json().catch(() => ({}));
          throw new Error(err.message || `Request failed (${res.status})`);
        }
        return res.json();
      },

      apiPost: async (url, body = {}) => {
        const { token } = get();
        if (!token) throw new Error('Not authenticated');
        const res = await fetch(`${API_BASE}${url}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
          body: JSON.stringify(body),
        });
        if (!res.ok) {
          if (res.status === 401 || res.status === 403) {
            set({ provider: null, token: null });
            throw new Error('Session expired. Please log in again.');
          }
          const err = await res.json().catch(() => ({}));
          throw new Error(err.message || `Request failed (${res.status})`);
        }
        return res.json();
      },

      apiPut: async (url, body = {}) => {
        const { token } = get();
        if (!token) throw new Error('Not authenticated');
        const res = await fetch(`${API_BASE}${url}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
            Accept: 'application/json',
          },
          body: JSON.stringify(body),
        });
        if (!res.ok) {
          if (res.status === 401 || res.status === 403) {
            set({ provider: null, token: null });
            throw new Error('Session expired. Please log in again.');
          }
          const err = await res.json().catch(() => ({}));
          throw new Error(err.message || `Request failed (${res.status})`);
        }
        return res.json();
      },

      clearError: () => set({ error: null }),
    }),
    {
      name: 'healthintel-partner-auth',
      partialize: (state) => ({ provider: state.provider, token: state.token }),
    }
  )
);

export default usePartnerAuthStore;