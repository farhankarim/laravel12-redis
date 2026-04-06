import React from 'react';
import CrudPage from '../components/CrudPage.jsx';

export default function CoursesPage() {
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
    />
  );
}
