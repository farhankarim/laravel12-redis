import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter, Routes, Route, NavLink } from 'react-router-dom';
import {
  CContainer,
  CSidebar,
  CSidebarBrand,
  CSidebarNav,
  CNavItem,
  CNavLink,
  CNavTitle,
} from '@coreui/react';
import '@coreui/coreui/dist/css/coreui.min.css';

import StudentsPage from './pages/StudentsPage.jsx';
import CoursesPage from './pages/CoursesPage.jsx';
import InstructorsPage from './pages/InstructorsPage.jsx';
import ClassroomsPage from './pages/ClassroomsPage.jsx';
import DepartmentsPage from './pages/DepartmentsPage.jsx';
import ReportPage from './pages/ReportPage.jsx';

function App() {
  return (
    <BrowserRouter basename="/university">
      <div className="d-flex" style={{ minHeight: '100vh' }}>
        <CSidebar colorScheme="dark" style={{ minHeight: '100vh' }}>
          <CSidebarBrand>University</CSidebarBrand>
          <CSidebarNav>
            <CNavTitle>Entities</CNavTitle>
            <CNavItem>
              <CNavLink component={NavLink} to="/students">Students</CNavLink>
            </CNavItem>
            <CNavItem>
              <CNavLink component={NavLink} to="/courses">Courses</CNavLink>
            </CNavItem>
            <CNavItem>
              <CNavLink component={NavLink} to="/instructors">Instructors</CNavLink>
            </CNavItem>
            <CNavItem>
              <CNavLink component={NavLink} to="/classrooms">Classrooms</CNavLink>
            </CNavItem>
            <CNavItem>
              <CNavLink component={NavLink} to="/departments">Departments</CNavLink>
            </CNavItem>
            <CNavTitle>Reports</CNavTitle>
            <CNavItem>
              <CNavLink component={NavLink} to="/report">Master Report</CNavLink>
            </CNavItem>
          </CSidebarNav>
        </CSidebar>
        <CContainer className="p-4" fluid>
          <Routes>
            <Route path="/students" element={<StudentsPage />} />
            <Route path="/courses" element={<CoursesPage />} />
            <Route path="/instructors" element={<InstructorsPage />} />
            <Route path="/classrooms" element={<ClassroomsPage />} />
            <Route path="/departments" element={<DepartmentsPage />} />
            <Route path="/report" element={<ReportPage />} />
            <Route path="/" element={<StudentsPage />} />
          </Routes>
        </CContainer>
      </div>
    </BrowserRouter>
  );
}

const el = document.getElementById('university-app');
if (el) {
  createRoot(el).render(<App />);
}
