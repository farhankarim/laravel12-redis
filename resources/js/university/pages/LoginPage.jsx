import React, { useState } from 'react';
import axios from 'axios';
import { Link } from 'react-router-dom';
import {
  CCard,
  CCardBody,
  CCardHeader,
  CForm,
  CFormInput,
  CFormLabel,
  CButton,
  CAlert,
} from '@coreui/react';
import { useAuth } from '../context/AuthContext.jsx';

export default function LoginPage() {
  const { saveAuth } = useAuth();
  const [form, setForm]     = useState({ email: '', password: '' });
  const [error, setError]   = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await axios.post('/api/auth/login', form);
      saveAuth(res.data.token, res.data.user);
    } catch (err) {
      setError(err.response?.data?.message || 'Login failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
      <CCard style={{ width: '100%', maxWidth: 420 }}>
        <CCardHeader><strong>Sign In</strong></CCardHeader>
        <CCardBody>
          {error && <CAlert color="danger">{error}</CAlert>}
          <CForm onSubmit={handleSubmit}>
            <div className="mb-3">
              <CFormLabel>Email</CFormLabel>
              <CFormInput
                type="email"
                value={form.email}
                onChange={e => setForm({ ...form, email: e.target.value })}
                required
                autoFocus
              />
            </div>
            <div className="mb-3">
              <CFormLabel>Password</CFormLabel>
              <CFormInput
                type="password"
                value={form.password}
                onChange={e => setForm({ ...form, password: e.target.value })}
                required
              />
            </div>
            <CButton type="submit" color="primary" className="w-100" disabled={loading}>
              {loading ? 'Signing in…' : 'Sign In'}
            </CButton>
          </CForm>
          <div className="mt-3 text-center">
            Don&apos;t have an account?{' '}
            <Link to="/register">Register</Link>
          </div>
        </CCardBody>
      </CCard>
    </div>
  );
}
