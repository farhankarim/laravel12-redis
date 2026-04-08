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

export default function RegisterPage() {
  const { saveAuth } = useAuth();
  const [form, setForm]     = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const [error, setError]   = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const res = await axios.post('/api/auth/register', form);
      saveAuth(res.data.token, res.data.user);
    } catch (err) {
      const data = err.response?.data;
      const msg  = data?.errors
        ? Object.values(data.errors).flat().join(' ')
        : data?.message || 'Registration failed.';
      setError(msg);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
      <CCard style={{ width: '100%', maxWidth: 420 }}>
        <CCardHeader><strong>Create Account</strong></CCardHeader>
        <CCardBody>
          {error && <CAlert color="danger">{error}</CAlert>}
          <CForm onSubmit={handleSubmit}>
            <div className="mb-3">
              <CFormLabel>Name</CFormLabel>
              <CFormInput
                value={form.name}
                onChange={e => setForm({ ...form, name: e.target.value })}
                required
                autoFocus
              />
            </div>
            <div className="mb-3">
              <CFormLabel>Email</CFormLabel>
              <CFormInput
                type="email"
                value={form.email}
                onChange={e => setForm({ ...form, email: e.target.value })}
                required
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
            <div className="mb-3">
              <CFormLabel>Confirm Password</CFormLabel>
              <CFormInput
                type="password"
                value={form.password_confirmation}
                onChange={e => setForm({ ...form, password_confirmation: e.target.value })}
                required
              />
            </div>
            <CButton type="submit" color="primary" className="w-100" disabled={loading}>
              {loading ? 'Creating account…' : 'Register'}
            </CButton>
          </CForm>
          <div className="mt-3 text-center">
            Already have an account?{' '}
            <Link to="/login">Sign In</Link>
          </div>
        </CCardBody>
      </CCard>
    </div>
  );
}
