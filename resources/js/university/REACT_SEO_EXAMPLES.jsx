/**
 * Example Page with SEO Optimization
 * 
 * This file demonstrates how to add SEO tags to React pages 
 * using the useSEO hook in your SPA.
 * 
 * Copy this pattern to all your page components.
 */

import React, { useEffect, useState } from 'react';
import { useSEO } from '../hooks/useSEO.jsx';
import { CTable, CTableHead, CTableBody, CTableRow, CTableHeaderCell, CTableDataCell } from '@coreui/react';

/**
 * StudentsPage - Shows how to implement SEO in a list page
 * 
 * @example
 * useSEO({
 *   title: 'Students Management',
 *   description: 'Manage all students in the university system with filtering and search capabilities.',
 *   keywords: 'students, university, management, enrollment'
 * });
 */
function StudentsPageExample() {
  const [students, setStudents] = useState([]);

  // Set SEO tags for this page
  useSEO({
    title: 'Students',
    description: 'View and manage all students in the university system.',
    keywords: 'students, university, management, enrollment, student records',
  });

  useEffect(() => {
    const fetchStudents = async () => {
      try {
        const response = await fetch('/api/v1/students');
        const data = await response.json();
        setStudents(data.data);
      } catch (error) {
        console.error('Error fetching students:', error);
      }
    };

    fetchStudents();
  }, []);

  return (
    <div>
      <h1>Students Management</h1>
      <p>Total Students: {students.length}</p>
      {/* Page content */}
    </div>
  );
}

/**
 * CreateStudentPage - Shows SEO for create/edit pages
 * 
 * @example
 * useSEO({
 *   title: 'Create New Student',
 *   description: 'Create a new student record in the university management system.',
 *   keywords: 'create student, enroll, registration'
 * });
 */
function CreateStudentPageExample() {
  useSEO({
    title: 'Create New Student',
    description: 'Add a new student to the university system with enrollment details.',
    keywords: 'create student, enrollment, new student, registration',
  });

  return (
    <div>
      <h1>Create New Student</h1>
      <p>Fill in the form below to register a new student.</p>
      {/* Form content */}
    </div>
  );
}

/**
 * ReportPage - Shows SEO for complex/report pages
 * 
 * @example
 * useSEO({
 *   title: 'Master Report - Students & Enrollment Analysis',
 *   description: 'Comprehensive master report with enrollment statistics, student demographics, and course analytics.',
 *   keywords: 'reporting, analytics, enrollment, statistics, demographics, analysis'
 * });
 */
function ReportPageExample() {
  useSEO({
    title: 'Master Report',
    description: 'Comprehensive reporting dashboard with enrollment statistics, student analytics, and system insights.',
    keywords: 'reporting, analytics, enrollment, statistics, student data, university reports',
  });

  return (
    <div>
      <h1>Master Report</h1>
      <p>Comprehensive analytics and reporting dashboard.</p>
      {/* Report content */}
    </div>
  );
}

/**
 * LoginPage - Shows SEO for authentication pages
 * 
 * @example
 * useSEO({
 *   title: 'Login - University Management System',
 *   description: 'Sign in to the University Management System to access student, course, and enrollment data.',
 *   keywords: 'login, sign in, authentication, access, university system'
 * });
 */
function LoginPageExample() {
  useSEO({
    title: 'Login',
    description: 'Sign in to your University Management System account to access all features.',
    keywords: 'login, sign in, authentication, access account, university',
  });

  return (
    <div>
      <h1>Sign In</h1>
      <p>Enter your credentials to access the system.</p>
      {/* Login form */}
    </div>
  );
}

/**
 * CoursesPage - Shows SEO for courses listing
 * 
 * @example
 * useSEO({
 *   title: 'Courses Management',
 *   description: 'Browse and manage all university courses including schedules, instructors, and enrolled students.',
 *   keywords: 'courses, management, curriculum, scheduling, course catalog'
 * });
 */
function CoursesPageExample() {
  useSEO({
    title: 'Courses',
    description: 'View and manage all university courses, schedules, instructors, and student enrollments.',
    keywords: 'courses, university, management, curriculum, course catalog, scheduling',
  });

  return (
    <div>
      <h1>Courses Management</h1>
      <p>Manage all available courses in the system.</p>
      {/* Courses table/content */}
    </div>
  );
}

/**
 * InstructorsPage - Shows SEO for instructors listing
 * 
 * @example
 * useSEO({
 *   title: 'Instructors Management',
 *   description: 'Manage faculty members, their qualifications, assigned courses, and contact information.',
 *   keywords: 'instructors, faculty, management, teaching staff, professors'
 * });
 */
function InstructorsPageExample() {
  useSEO({
    title: 'Instructors',
    description: 'View and manage all faculty members, their qualifications, and course assignments.',
    keywords: 'instructors, faculty, management, professors, teaching staff, qualifications',
  });

  return (
    <div>
      <h1>Instructors Management</h1>
      <p>Manage all faculty members and their assignments.</p>
      {/* Instructors table/content */}
    </div>
  );
}

/**
 * ClassroomsPage - Shows SEO for classroom/facilities management
 * 
 * @example
 * useSEO({
 *   title: 'Classrooms & Facilities Management',
 *   description: 'Manage classroom locations, capacity, facilities, equipment, and availability for course scheduling.',
 *   keywords: 'classrooms, facilities, rooms, capacity, scheduling, resources'
 * });
 */
function ClassroomsPageExample() {
  useSEO({
    title: 'Classrooms',
    description: 'Manage classroom locations, capacity, facilities, and scheduling.',
    keywords: 'classrooms, facilities, rooms, capacity, scheduling, university resources',
  });

  return (
    <div>
      <h1>Classrooms Management</h1>
      <p>Manage all classroom facilities and resources.</p>
      {/* Classrooms table/content */}
    </div>
  );
}

/**
 * DepartmentsPage - Shows SEO for departments management
 * 
 * @example
 * useSEO({
 *   title: 'Departments Management',
 *   description: 'Organize and manage all academic departments, their courses, faculty members, and student enrollments.',
 *   keywords: 'departments, academic organization, faculties, divisions, academic structure'
 * });
 */
function DepartmentsPageExample() {
  useSEO({
    title: 'Departments',
    description: 'Manage all academic departments, their structure, courses, and faculty members.',
    keywords: 'departments, academic organization, faculties, divisions, academic structure',
  });

  return (
    <div>
      <h1>Departments Management</h1>
      <p>Manage all academic departments in the university.</p>
      {/* Departments table/content */}
    </div>
  );
}

/**
 * IMPLEMENTATION GUIDE FOR YOUR PAGES
 * 
 * ====================================
 * 
 * 1. Import the hook at the top of your page component:
 *    import { useSEO } from '../hooks/useSEO.jsx';
 * 
 * 2. Call the hook with appropriate SEO data:
 *    useSEO({
 *      title: 'Page Title',
 *      description: 'Meta description (50-160 chars)',
 *      keywords: 'keyword1, keyword2, keyword3'
 *    });
 * 
 * 3. Important Guidelines:
 *    - Title should be descriptive and include entity name
 *    - Description should be 50-160 characters and action-oriented
 *    - Keywords should be relevant (2-4 primary keywords)
 *    - Always include the entity type (Students, Courses, etc.)
 *    - Create pages get "Create New" in title
 *    - Edit pages get "Edit" in title
 *    - Search/List pages get just the entity name
 * 
 * 4. SEO will automatically:
 *    - Update page title
 *    - Create proper meta tags
 *    - Generate Open Graph tags for social sharing
 *    - Create Twitter Card tags
 *    - Add JSON-LD structured data
 *    - Create canonical URLs
 * 
 * 5. Full Meta Tags Generated:
 *    - <title>page-title</title>
 *    - <meta name="description" content="...">
 *    - <meta name="keywords" content="...">
 *    - <meta property="og:title" content="...">
 *    - <meta property="og:description" content="...">
 *    - <meta name="twitter:title" content="...">
 *    - <meta name="twitter:description" content="...">
 *    - <link rel="canonical" href="...">
 *    - <script type="application/ld+json">... (structured data)
 * 
 * ====================================
 */

export default StudentsPageExample;
