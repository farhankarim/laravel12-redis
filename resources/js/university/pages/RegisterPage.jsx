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

export default function RegisterPage() {
  const { saveAuth } = useAuth();
  const [form, setForm]     = useState({ name: '', email: '', password: '', password_confirmation: '' });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});
  const [formError, setFormError] = useState('');

  useSEO({
    title: 'Register',
    description: 'Create a new account in the University Management System. Join as a student, instructor, or administrator.',
    keywords: 'register, create account, sign up, new user, university system'
  });

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setFormError('');
    setLoading(true);
    try {
      const res = await axios.post('/api/auth/register', form);
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

      setFormError(err.response?.data?.message || 'Registration failed.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="d-flex justify-content-center align-items-center" style={{ minHeight: '100vh' }}>
      <CCard style={{ width: '100%', maxWidth: 420 }}>
        <CCardHeader><strong>Create Account</strong></CCardHeader>
        <CCardBody>
          <CForm noValidate onSubmit={handleSubmit}>
            <div className="mb-3">
              <CFormLabel>Name</CFormLabel>
              <CFormInput
                value={form.name}
                onChange={e => setForm({ ...form, name: e.target.value })}
                required
                autoFocus
              />
              {errors.name && <div className="text-danger fw-bold mt-1">{errors.name}</div>}
            </div>
            <div className="mb-3">
              <CFormLabel>Email</CFormLabel>
              <CFormInput
                type="email"
                value={form.email}
                onChange={e => setForm({ ...form, email: e.target.value })}
                required
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
            <div className="mb-3">
              <CFormLabel>Confirm Password</CFormLabel>
              <CFormInput
                type="password"
                value={form.password_confirmation}
                onChange={e => setForm({ ...form, password_confirmation: e.target.value })}
                required
              />
              {errors.password_confirmation && <div className="text-danger fw-bold mt-1">{errors.password_confirmation}</div>}
            </div>
            {formError && <div className="text-danger fw-bold mb-3">{formError}</div>}
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
