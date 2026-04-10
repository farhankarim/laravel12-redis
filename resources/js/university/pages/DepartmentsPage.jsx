import React from 'react';
import { useParams } from 'react-router-dom';
import CrudPage from '../components/CrudPage.jsx';
import { useSEO } from '../hooks/useSEO.jsx';

export default function DepartmentsPage() {
  useSEO({
    title: 'Departments',
    description: 'View and manage all university departments, their programs, faculty, and academic offerings.',
    keywords: 'departments, academic programs, university, management, faculty'
  });
  return (
    <CrudPage
      title="Departments"
      apiPath="/api/v1/departments"
      fields={[
        { key: 'dept_name', label: 'Department Name' },
      ]}
      displayColumns={[
        { key: 'dept_name', label: 'Name' },
      ]}
      mode="list"
      basePath="/departments"
    />
  );
}

export function DepartmentsCreatePage() {
  useSEO({
    title: 'Create New Department',
    description: 'Add a new academic department to the university with program information and faculty details.',
    keywords: 'create department, add department, new department, academic management'
  });

  return (
    <CrudPage
      title="Departments"
      apiPath="/api/v1/departments"
      fields={[
        { key: 'dept_name', label: 'Department Name' },
      ]}
      displayColumns={[
        { key: 'dept_name', label: 'Name' },
      ]}
      mode="create"
      basePath="/departments"
    />
  );
}

export function DepartmentsEditPage() {
  const { id } = useParams();

  useSEO({
    title: 'Edit Department',
    description: 'Update department details including name, code, programs, and faculty information.',
    keywords: 'edit department, update department, academic management'
  });

  return (
    <CrudPage
      title="Departments"
      apiPath="/api/v1/departments"
      fields={[
        { key: 'dept_name', label: 'Department Name' },
      ]}
      displayColumns={[
        { key: 'dept_name', label: 'Name' },
      ]}
      mode="edit"
      basePath="/departments"
      recordId={id}
    />
  );
}
