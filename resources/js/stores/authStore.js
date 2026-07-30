import { create } from 'zustand';
import axios from 'axios';
import api from '../lib/api';

const TOKEN_KEY = 'labdoc_token';

const useAuthStore = create((set, get) => ({
    user: null,
    token: localStorage.getItem(TOKEN_KEY) || null,
    loading: true,

    setUser: (user) => set({ user, loading: false }),

    fetchUser: async () => {
        // Skip API call if no token — prevents 401 noise for guest visitors
        const token = localStorage.getItem(TOKEN_KEY);
        if (!token) {
            set({ user: null, loading: false });
            return;
        }
        try {
            const res = await api.get('/auth/me');
            set({ user: res.data.user, loading: false });
        } catch {
            // Clear stale token if /me fails
            localStorage.removeItem(TOKEN_KEY);
            set({ user: null, token: null, loading: false });
        }
    },

    login: async (credentials) => {
        await axios.get('/sanctum/csrf-cookie');
        const res = await api.post('/auth/login', credentials);
        const token = res.data.token;
        if (token) {
            localStorage.setItem(TOKEN_KEY, token);
            set({ token });
        }
        await get().fetchUser();
    },

    register: async (data) => {
        await axios.get('/sanctum/csrf-cookie');
        const res = await api.post('/auth/register', data);
        return res?.data || {};
    },

    verifyEmail: async (userId, code) => {
        const res = await api.post('/auth/verify-email', { user_id: userId, code });
        const token = res.data.token;
        if (token) {
            localStorage.setItem(TOKEN_KEY, token);
            set({ token });
        }
        await get().fetchUser();
    },

    logout: async () => {
        try {
            await api.post('/auth/logout');
        } finally {
            localStorage.removeItem(TOKEN_KEY);
            set({ user: null, token: null });
        }
    },

    isAuthenticated: () => !!get().user,

    hasHealthProfile: () => !!get().user?.health_profile?.profile_completed,
}));

export default useAuthStore;
