import React from 'react';
import CrudPage from '../components/CrudPage.jsx';

export default function StudentsPage() {
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
    />
  );
}
