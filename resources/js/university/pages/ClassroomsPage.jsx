import React from 'react';
import { useParams } from 'react-router-dom';
import CrudPage from '../components/CrudPage.jsx';
import { useSEO } from '../hooks/useSEO.jsx';

export default function ClassroomsPage() {
  useSEO({
    title: 'Classrooms',
    description: 'Manage university classrooms, buildings, capacity, and course scheduling.',
    keywords: 'classrooms, buildings, university, management, scheduling, facilities'
  });
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
      mode="list"
      basePath="/classrooms"
    />
  );
}

export function ClassroomsCreatePage() {
  useSEO({
    title: 'Create New Classroom',
    description: 'Add a new classroom to the university with building information and capacity details.',
    keywords: 'create classroom, add classroom, new classroom, facility management'
  });

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
      mode="create"
      basePath="/classrooms"
    />
  );
}

export function ClassroomsEditPage() {
  const { id } = useParams();

  useSEO({
    title: 'Edit Classroom',
    description: 'Update classroom information including building assignment and capacity management.',
    keywords: 'edit classroom, update classroom, facility management'
  });

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
      mode="edit"
      basePath="/classrooms"
      recordId={id}
    />
  );
}
