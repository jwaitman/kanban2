import { defineStore } from 'pinia';
import axios from 'axios';

// Define the API base URL from your config
const API_BASE_URL = 'http://localhost:8080/api/v1';

const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
    }
});

// Add a request interceptor to include the token in headers
api.interceptors.request.use(config => {
    const authStore = useAuthStore();
    const token = authStore.token;
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem('token') || null,
        user: JSON.parse(localStorage.getItem('user')) || null,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
        userRole: (state) => state.user?.role,
    },
    actions: {
        async login(credentials) {
            try {
                const response = await api.post('/auth/login', credentials);
                const { access_token } = response.data;
                this.token = access_token;

                // Decode user info from token (simple way, for non-sensitive data)
                const payload = JSON.parse(atob(access_token.split('.')[1]));
                this.user = payload.data;

                localStorage.setItem('token', access_token);
                localStorage.setItem('user', JSON.stringify(this.user));

                return true;
            } catch (error) {
                console.error('Login failed:', error);
                this.logout(); // Clear any partial state
                return false;
            }
        },
        logout() {
            this.token = null;
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        },
        // You can add a refreshToken action here later if needed
    },
});

// Export the api instance to be used in other parts of the app
export { api };
