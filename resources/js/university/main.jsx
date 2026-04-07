import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, NavLink, Navigate, useNavigate } from 'react-router-dom';
import {
  CContainer,
  CSidebar,
  CSidebarBrand,
  CSidebarNav,
  CNavItem,
  CNavLink,
  CNavTitle,
  CButton,
} from '@coreui/react';
import '@coreui/coreui/dist/css/coreui.min.css';

import { AuthProvider, useAuth } from './context/AuthContext.jsx';
import StudentsPage from './pages/StudentsPage.jsx';
import CoursesPage from './pages/CoursesPage.jsx';
import InstructorsPage from './pages/InstructorsPage.jsx';
import ClassroomsPage from './pages/ClassroomsPage.jsx';
import DepartmentsPage from './pages/DepartmentsPage.jsx';
import ReportPage from './pages/ReportPage.jsx';
import LoginPage from './pages/LoginPage.jsx';
import RegisterPage from './pages/RegisterPage.jsx';
import axios from 'axios';

function AppLayout() {
  const { isAuthenticated, clearAuth, user } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    try {
      await axios.post('/api/auth/logout');
    } catch {
      // token may already be invalid
    }
    clearAuth();
    navigate('/login', { replace: true });
  };

  if (!isAuthenticated) {
    return (
      <Routes>
        <Route path="/login"    element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
        <Route path="*"         element={<Navigate to="/login" replace />} />
      </Routes>
    );
  }

  return (
    <div className="d-flex" style={{ minHeight: '100vh' }}>
      <CSidebar colorScheme="dark" style={{ minHeight: '100vh' }}>
        <CSidebarBrand>University</CSidebarBrand>
        <CSidebarNav>
          <CNavTitle>Entities</CNavTitle>
          <CNavItem>
            <CNavLink as={NavLink} to="/students">Students</CNavLink>
          </CNavItem>
          <CNavItem>
            <CNavLink as={NavLink} to="/courses">Courses</CNavLink>
          </CNavItem>
          <CNavItem>
            <CNavLink as={NavLink} to="/instructors">Instructors</CNavLink>
          </CNavItem>
          <CNavItem>
            <CNavLink as={NavLink} to="/classrooms">Classrooms</CNavLink>
          </CNavItem>
          <CNavItem>
            <CNavLink as={NavLink} to="/departments">Departments</CNavLink>
          </CNavItem>
          <CNavTitle>Reports</CNavTitle>
          <CNavItem>
            <CNavLink as={NavLink} to="/report">Master Report</CNavLink>
          </CNavItem>
          <CNavTitle>Account</CNavTitle>
          <CNavItem className="px-3 py-2">
            {user?.name && <div className="text-white-50 small mb-2">{user.name}</div>}
            <CButton color="secondary" size="sm" onClick={handleLogout}>Logout</CButton>
          </CNavItem>
        </CSidebarNav>
      </CSidebar>
      <CContainer className="p-4" fluid>
        <Routes>
          <Route path="/students"    element={<StudentsPage />} />
          <Route path="/courses"     element={<CoursesPage />} />
          <Route path="/instructors" element={<InstructorsPage />} />
          <Route path="/classrooms"  element={<ClassroomsPage />} />
          <Route path="/departments" element={<DepartmentsPage />} />
          <Route path="/report"      element={<ReportPage />} />
          <Route path="/"            element={<StudentsPage />} />
          <Route path="/login"       element={<Navigate to="/students" replace />} />
          <Route path="/register"    element={<Navigate to="/students" replace />} />
          <Route path="*"            element={<Navigate to="/" replace />} />
        </Routes>
      </CContainer>
    </div>
  );
}

function App() {
  return (
    <BrowserRouter basename="/university">
      <AuthProvider>
        <AppLayout />
      </AuthProvider>
    </BrowserRouter>
  );
}

const el = document.getElementById('university-app');
if (el) {
  createRoot(el).render(<App />);
}
