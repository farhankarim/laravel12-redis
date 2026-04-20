import React, { useState } from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, NavLink, Navigate, useNavigate } from 'react-router-dom';
import {
  CContainer,
  CSidebar,
  CSidebarNav,
  CNavItem,
  CNavLink,
  CNavTitle,
  CButton,
} from '@coreui/react';
import '@coreui/coreui/dist/css/coreui.min.css';

import { AuthProvider, useAuth } from './context/AuthContext.jsx';
import StudentsPage, { StudentsCreatePage, StudentsEditPage } from './pages/StudentsPage.jsx';
import CoursesPage, { CoursesCreatePage, CoursesEditPage } from './pages/CoursesPage.jsx';
import InstructorsPage, { InstructorsCreatePage, InstructorsEditPage } from './pages/InstructorsPage.jsx';
import ClassroomsPage, { ClassroomsCreatePage, ClassroomsEditPage } from './pages/ClassroomsPage.jsx';
import DepartmentsPage, { DepartmentsCreatePage, DepartmentsEditPage } from './pages/DepartmentsPage.jsx';
import ReportPage from './pages/ReportPage.jsx';
import CourseAssignPage from './pages/CourseAssignPage.jsx';
import LoginPage from './pages/LoginPage.jsx';
import RegisterPage from './pages/RegisterPage.jsx';
import UsersSearchPage from './pages/UsersSearchPage.jsx';
import axios from 'axios';

function AppLayout() {
  const { isAuthenticated, clearAuth, user } = useAuth();
  const navigate = useNavigate();
  const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(false);

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
      {!isSidebarCollapsed && (
        <CSidebar colorScheme="dark" style={{ minHeight: '100vh' }}>
          <div className="px-3 py-3 border-bottom border-secondary">
            <CButton
              color="light"
              variant="ghost"
              className="w-100 d-flex align-items-center justify-content-between"
              onClick={() => setIsSidebarCollapsed(true)}
              aria-label="Collapse sidebar"
            >
              <img src="/favicon.ico" alt="App" width="18" height="18" />
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M10.5 3.5L6 8l4.5 4.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </CButton>
          </div>
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
            <CNavTitle>Operations</CNavTitle>
            <CNavItem>
              <CNavLink as={NavLink} to="/course-assign">Course Assignment</CNavLink>
            </CNavItem>
            <CNavItem>
              <CNavLink as={NavLink} to="/users/search">User Search</CNavLink>
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
      )}
      <CContainer className="p-4" fluid>
        {isSidebarCollapsed && (
          <div className="mb-3">
            <CButton
              color="dark"
              variant="outline"
              className="d-flex align-items-center justify-content-center"
              onClick={() => setIsSidebarCollapsed(false)}
              aria-label="Expand sidebar"
            >
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M5.5 3.5L10 8l-4.5 4.5" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" />
              </svg>
            </CButton>
          </div>
        )}
        <Routes>
          <Route path="/students"    element={<StudentsPage />} />
          <Route path="/students/new"    element={<StudentsCreatePage />} />
          <Route path="/students/:id/edit"    element={<StudentsEditPage />} />
          <Route path="/courses"     element={<CoursesPage />} />
          <Route path="/courses/new"     element={<CoursesCreatePage />} />
          <Route path="/courses/:id/edit"     element={<CoursesEditPage />} />
          <Route path="/instructors" element={<InstructorsPage />} />
          <Route path="/instructors/new" element={<InstructorsCreatePage />} />
          <Route path="/instructors/:id/edit" element={<InstructorsEditPage />} />
          <Route path="/classrooms"  element={<ClassroomsPage />} />
          <Route path="/classrooms/new"  element={<ClassroomsCreatePage />} />
          <Route path="/classrooms/:id/edit"  element={<ClassroomsEditPage />} />
          <Route path="/departments" element={<DepartmentsPage />} />
          <Route path="/departments/new" element={<DepartmentsCreatePage />} />
          <Route path="/departments/:id/edit" element={<DepartmentsEditPage />} />
          <Route path="/course-assign" element={<CourseAssignPage />} />
          <Route path="/users/search" element={<UsersSearchPage />} />
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
