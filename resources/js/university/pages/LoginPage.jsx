import React, { useState } from 'react';
import axios from 'axios';
import { Link } from 'react-router-dom';
import { useSEO } from '../hooks/useSEO.jsx';
import {
  CCard,
  CCardBody,
  CCardHeader,
  CForm,
  CFormInput,
  CFormLabel,
  CButton,
} from '@coreui/react';
import { useAuth } from '../context/AuthContext.jsx';

export default function LoginPage() {
  const { saveAuth } = useAuth();
  const [form, setForm]     = useState({ email: '', password: '' });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState('');

  useSEO({
    title: 'Login',
    description: 'Sign in to your University Management System account. Access student records, courses, and administrative tools.',
    keywords: 'login, sign in, authentication, university system, access'
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setFormError('');
    setLoading(true);
    try {
      const res = await axios.post('/api/auth/login', form);
      saveAuth(res.data.token, res.data.user);
    } catch (err) {
      const responseErrors = err.response?.data?.errors;
      if (responseErrors && typeof responseErrors === 'object') {
        const nextErrors = {};
        Object.entries(responseErrors).forEach(([key, value]) => {
          nextErrors[key] = Array.isArray(value) ? value[0] : String(value);
        });
        setErrors(nextErrors);
      }
      setFormError(err.response?.data?.message || 'Login failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
      <CCard style={{ width: '100%', maxWidth: 420 }}>
        <CCardHeader><strong>Sign In</strong></CCardHeader>
        <CCardBody>
          <CForm noValidate onSubmit={handleSubmit}>
            <div className="mb-3">
              <CFormLabel>Email</CFormLabel>
              <CFormInput
                type="email"
                value={form.email}
                onChange={e => setForm({ ...form, email: e.target.value })}
                required
                autoFocus
              />
              {errors.email && <div className="text-danger fw-bold mt-1">{errors.email}</div>}
            </div>
            <div className="mb-3">
              <CFormLabel>Password</CFormLabel>
              <CFormInput
                type="password"
                value={form.password}
                onChange={e => setForm({ ...form, password: e.target.value })}
                required
              />
              {errors.password && <div className="text-danger fw-bold mt-1">{errors.password}</div>}
            </div>
            {formError && <div className="text-danger fw-bold mb-3">{formError}</div>}
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
