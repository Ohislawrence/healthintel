import axios from 'axios';

const TOKEN_KEY = 'labdoc_token';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
    withCredentials: true,
});

// Request interceptor: attach Bearer token if available
api.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// Response interceptor for consistent error handling
api.interceptors.response.use(
    (response) => response.data,
    (error) => {
        if (error.response?.status === 401) {
            const url = error.config?.url || '';
            // Don't trigger auth:unauthenticated for logout or me requests
            // to prevent infinite 401 loops
            if (!url.includes('/auth/logout') && !url.includes('/auth/me')) {
                window.dispatchEvent(new CustomEvent('auth:unauthenticated'));
            }
        }
        return Promise.reject(error.response?.data || error);
    },
);

export default api;
