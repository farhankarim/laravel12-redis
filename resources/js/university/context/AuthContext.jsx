import React, { createContext, useContext, useState } from 'react';
import axios from 'axios';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [token, setToken]   = useState(() => {
    const t = localStorage.getItem('auth_token');
    if (t) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${t}`;
    }
    return t;
  });
  const [user, setUser]     = useState(() => {
    try { return JSON.parse(localStorage.getItem('auth_user')); } catch { return null; }
  });

  const saveAuth = (newToken, newUser) => {
    localStorage.setItem('auth_token', newToken);
    localStorage.setItem('auth_user', JSON.stringify(newUser));
    axios.defaults.headers.common['Authorization'] = `Bearer ${newToken}`;
    setToken(newToken);
    setUser(newUser);
  };

  const clearAuth = () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('auth_user');
    delete axios.defaults.headers.common['Authorization'];
    setToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ token, user, saveAuth, clearAuth, isAuthenticated: Boolean(token) }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
