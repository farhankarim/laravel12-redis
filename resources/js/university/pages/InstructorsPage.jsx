import React from 'react';
import { useParams } from 'react-router-dom';
import CrudPage from '../components/CrudPage.jsx';
import { useSEO } from '../hooks/useSEO.jsx';

export default function InstructorsPage() {
  useSEO({
    title: 'Instructors',
    description: 'View and manage all university instructors, their courses, qualifications, and assignments.',
    keywords: 'instructors, faculty, teachers, university, management, course assignments'
  });
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
      mode="list"
      basePath="/instructors"
    />
  );
}

export function InstructorsCreatePage() {
  useSEO({
    title: 'Create New Instructor',
    description: 'Add a new instructor to the university with qualifications, contact information, and course assignments.',
    keywords: 'create instructor, add faculty, new instructor, faculty management'
  });

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
      mode="create"
      basePath="/instructors"
    />
  );
}

export function InstructorsEditPage() {
  const { id } = useParams();

  useSEO({
    title: 'Edit Instructor',
    description: 'Update instructor profile, qualifications, contact information, and course assignments.',
    keywords: 'edit instructor, update faculty, instructor profile'
  });

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
      mode="edit"
      basePath="/instructors"
      recordId={id}
    />
  );
}
