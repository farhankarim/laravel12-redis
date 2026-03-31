import type { AuthProvider } from 'react-admin';

const API_URL = '';

export const authProvider: AuthProvider = {
  async login({ username, password }: { username: string; password: string }) {
    const res = await fetch(`${API_URL}/auth/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: username, password }),
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({}));
      throw new Error((err as { message?: string })?.message || 'Login failed');
    }
    const data = await res.json() as { access_token: string; user?: { _id?: string; name?: string; email?: string } };
    localStorage.setItem('token', data.access_token);
    localStorage.setItem('user', JSON.stringify(data.user ?? { email: username }));
  },

  async logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
  },

  async checkAuth() {
    const token = localStorage.getItem('token');
    if (!token) throw new Error('Not authenticated');
  },

  async checkError(error: { status?: number }) {
    if (error?.status === 401 || error?.status === 403) {
      localStorage.removeItem('token');
      throw new Error('Session expired');
    }
  },

  async getIdentity() {
    const stored = localStorage.getItem('user');
    if (!stored) return { id: 'unknown', fullName: 'User' };
    const user = JSON.parse(stored) as { _id?: string; name?: string; email?: string };
    return {
      id: user._id ?? 'unknown',
      fullName: user.name ?? user.email ?? 'User',
    };
  },

  async getPermissions() {
    return null;
  },
};
