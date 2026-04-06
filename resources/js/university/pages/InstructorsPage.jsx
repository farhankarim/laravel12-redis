import React from 'react';
import CrudPage from '../components/CrudPage.jsx';

export default function InstructorsPage() {
  return (
    <CrudPage
      title="Instructors"
      apiPath="/api/v1/instructors"
      fields={[
        { key: 'name', label: 'Name' },
        { key: 'specialization', label: 'Specialization' },
      ]}
      displayColumns={[
        { key: 'name', label: 'Name' },
        { key: 'specialization', label: 'Specialization' },
      ]}
    />
  );
}
