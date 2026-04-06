import React from 'react';
import CrudPage from '../components/CrudPage.jsx';

export default function ClassroomsPage() {
  return (
    <CrudPage
      title="Classrooms"
      apiPath="/api/v1/classrooms"
      fields={[
        { key: 'room_number', label: 'Room Number' },
        { key: 'building', label: 'Building' },
      ]}
      displayColumns={[
        { key: 'room_number', label: 'Room' },
        { key: 'building', label: 'Building' },
      ]}
    />
  );
}
