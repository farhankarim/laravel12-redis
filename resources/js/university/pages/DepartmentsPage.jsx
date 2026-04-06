import React from 'react';
import CrudPage from '../components/CrudPage.jsx';

export default function DepartmentsPage() {
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
    />
  );
}
