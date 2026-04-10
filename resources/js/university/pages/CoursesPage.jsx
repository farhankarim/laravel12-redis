import React from 'react';
import { useParams } from 'react-router-dom';
import CrudPage from '../components/CrudPage.jsx';
import { useSEO } from '../hooks/useSEO.jsx';

export default function CoursesPage() {
  useSEO({
    title: 'Courses',
    description: 'View and manage all university courses including schedules, instructors, and enrolled students.',
    keywords: 'courses, university, management, curriculum, course catalog, scheduling'
  });
  return (
    <CrudPage
      title="Courses"
      apiPath="/api/v1/courses"
      fields={[
        { key: 'course_code', label: 'Course Code' },
        { key: 'title', label: 'Title' },
      ]}
      displayColumns={[
        { key: 'course_code', label: 'Code' },
        { key: 'title', label: 'Title' },
      ]}
      mode="list"
      basePath="/courses"
    />
  );
}

export function CoursesCreatePage() {
  useSEO({
    title: 'Create New Course',
    description: 'Add a new course to the university curriculum with course code, title, and details.',
    keywords: 'create course, new course, course management, curriculum'
  });

  return (
    <CrudPage
      title="Courses"
      apiPath="/api/v1/courses"
      fields={[
        { key: 'course_code', label: 'Course Code' },
        { key: 'title', label: 'Title' },
      ]}
      displayColumns={[
        { key: 'course_code', label: 'Code' },
        { key: 'title', label: 'Title' },
      ]}
      mode="create"
      basePath="/courses"
    />
  );
}

export function CoursesEditPage() {
  const { id } = useParams();

  useSEO({
    title: 'Edit Course',
    description: 'Update course information including course code, title, and curriculum details.',
    keywords: 'edit course, update course, course management'
  });

  return (
    <CrudPage
      title="Courses"
      apiPath="/api/v1/courses"
      fields={[
        { key: 'course_code', label: 'Course Code' },
        { key: 'title', label: 'Title' },
      ]}
      displayColumns={[
        { key: 'course_code', label: 'Code' },
        { key: 'title', label: 'Title' },
      ]}
      mode="edit"
      basePath="/courses"
      recordId={id}
    />
  );
}
