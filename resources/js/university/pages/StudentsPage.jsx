import React from 'react';
import { useParams } from 'react-router-dom';
import CrudPage from '../components/CrudPage.jsx';
import { useSEO } from '../hooks/useSEO.jsx';

export default function StudentsPage() {
  useSEO({
    title: 'Students',
    description: 'View and manage all students in the university system with enrollment tracking and academic records.',
    keywords: 'students, university, management, enrollment, academic records'
  });
  return (
    <CrudPage
      title="Students"
      apiPath="/api/v1/students"
      fields={[
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email', type: 'email' },
      ]}
      displayColumns={[
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
      ]}
      mode="list"
      basePath="/students"
    />
  );
}

export function StudentsCreatePage() {
  useSEO({
    title: 'Create New Student',
    description: 'Register a new student in the university system with enrollment details and contact information.',
    keywords: 'create student, enrollment, new student, registration'
  });

  return (
    <CrudPage
      title="Students"
      apiPath="/api/v1/students"
      fields={[
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email', type: 'email' },
      ]}
      displayColumns={[
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
      ]}
      mode="create"
      basePath="/students"
    />
  );
}

export function StudentsEditPage() {
  const { id } = useParams();

  useSEO({
    title: 'Edit Student',
    description: 'Modify student information including contact details, enrollment status, and academic records.',
    keywords: 'edit student, update enrollment, student records'
  });

  return (
    <CrudPage
      title="Students"
      apiPath="/api/v1/students"
      fields={[
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email', type: 'email' },
      ]}
      displayColumns={[
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
      ]}
      mode="edit"
      basePath="/students"
      recordId={id}
    />
  );
}
