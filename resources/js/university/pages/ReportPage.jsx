import React, { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { useSEO } from '../hooks/useSEO.jsx';
import {
  CButton,
  CCard,
  CCardBody,
  CCardHeader,
  CFormCheck,
  CFormLabel,
  CFormSelect,
  CRow,
  CCol,
  CBadge,
  CTable,
  CTableBody,
  CTableDataCell,
  CTableHead,
  CTableHeaderCell,
  CTableRow,
} from '@coreui/react';

const MASTER_COLUMNS = [
  { key: 'student_id', label: 'Student ID' },
  { key: 'student_name', label: 'Student Name' },
  { key: 'student_email', label: 'Student Email' },
  { key: 'course_code', label: 'Course Code' },
  { key: 'course_title', label: 'Course Title' },
  { key: 'semester', label: 'Semester' },
  { key: 'grade', label: 'Grade' },
  { key: 'instructor_name', label: 'Instructor Name' },
  { key: 'instructor_specialization', label: 'Instructor Specialization' },
  { key: 'classroom_room_number', label: 'Room Number' },
  { key: 'classroom_building', label: 'Building' },
  { key: 'schedule_day', label: 'Day of Week' },
  { key: 'schedule_start_time', label: 'Start Time' },
  { key: 'department_name', label: 'Department' },
];

const DEFAULT_SELECTED = ['student_name', 'course_title', 'instructor_name', 'department_name'];

export default function ReportPage() {
  const [scope, setScope] = useState('all');
  const [studentId, setStudentId] = useState('');
  const [students, setStudents] = useState([]);
  const [selectedColumns, setSelectedColumns] = useState(DEFAULT_SELECTED);
  const [rows, setRows] = useState([]);
  const [meta, setMeta] = useState(null);
  const [isLoading, setIsLoading] = useState(false);
  const [draggedColumn, setDraggedColumn] = useState(null);
  const [dragSource, setDragSource] = useState(null);
  const [errors, setErrors] = useState({});

  useSEO({
    title: 'Master Report',
    description: 'Comprehensive reporting dashboard for university data. View student enrollments, course assignments, and detailed analytics.',
    keywords: 'reporting, analytics, enrollment, statistics, university reports, data analysis'
  });

  const columnMap = useMemo(
    () => MASTER_COLUMNS.reduce((acc, col) => ({ ...acc, [col.key]: col }), {}),
    [],
  );

  const availableColumns = MASTER_COLUMNS.filter(col => !selectedColumns.includes(col.key));

  useEffect(() => {
    const loadStudents = async () => {
      try {
        const response = await axios.get('/api/v1/students');
        setStudents(Array.isArray(response.data) ? response.data : []);
      } catch {
        setStudents([]);
      }
    };

    loadStudents();
  }, []);

  const moveToSelected = (columnKey) => {
    setSelectedColumns(prev => (prev.includes(columnKey) ? prev : [...prev, columnKey]));
  };

  const moveToAvailable = (columnKey) => {
    setSelectedColumns(prev => prev.filter(key => key !== columnKey));
  };

  const reorderSelected = (dragKey, targetKey) => {
    if (!dragKey || dragKey === targetKey) {
      return;
    }

    setSelectedColumns(prev => {
      const fromIndex = prev.indexOf(dragKey);
      const toIndex = prev.indexOf(targetKey);

      if (fromIndex === -1 || toIndex === -1) {
        return prev;
      }

      const next = [...prev];
      const [moved] = next.splice(fromIndex, 1);
      next.splice(toIndex, 0, moved);
      return next;
    });
  };

  const onDragStartColumn = (key, source) => {
    setDraggedColumn(key);
    setDragSource(source);
  };

  const onDropToSelected = (targetKey = null) => {
    if (!draggedColumn) {
      return;
    }

    if (dragSource === 'available') {
      moveToSelected(draggedColumn);
    } else if (dragSource === 'selected' && targetKey) {
      reorderSelected(draggedColumn, targetKey);
    }

    setDraggedColumn(null);
    setDragSource(null);
  };

  const onDropToAvailable = () => {
    if (draggedColumn && dragSource === 'selected') {
      moveToAvailable(draggedColumn);
    }

    setDraggedColumn(null);
    setDragSource(null);
  };

  const fetchReport = async (e) => {
    e.preventDefault();
    setErrors({});
    setIsLoading(true);

    if (selectedColumns.length === 0) {
      setRows([]);
      setMeta(null);
      setErrors({ columns: 'Select at least one field before generating.' });
      setIsLoading(false);
      return;
    }

    if (scope === 'student' && !studentId) {
      setRows([]);
      setMeta(null);
      setErrors({ studentId: 'Please select a student for student scope.' });
      setIsLoading(false);
      return;
    }

    try {
      const payload = {
        scope,
        columns: selectedColumns,
      };

      if (scope === 'student') {
        payload.student_id = Number(studentId);
      }

      const response = await axios.post('/api/v1/reports/master', payload);
      setRows(response.data?.rows ?? []);
      setMeta(response.data?.meta ?? null);
    } catch {
      setRows([]);
      setMeta(null);
      setErrors({
        report: 'Failed to load report. Ensure your data relationships are populated.',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const renderColumnChip = (column, source) => (
    <div
      key={column.key}
      className="d-flex align-items-center justify-content-between border rounded p-2 mb-2 bg-white"
      draggable
      onDragStart={() => onDragStartColumn(column.key, source)}
      onDragOver={e => e.preventDefault()}
      onDrop={() => source === 'selected' ? onDropToSelected(column.key) : undefined}
      style={{ cursor: 'grab' }}
    >
      <span>{column.label}</span>
      {source === 'available' ? (
        <CButton color="primary" variant="ghost" size="sm" onClick={() => moveToSelected(column.key)}>
          Add
        </CButton>
      ) : (
        <CButton color="danger" variant="ghost" size="sm" onClick={() => moveToAvailable(column.key)}>
          Remove
        </CButton>
      )}
    </div>
  );

  return (
    <>
      <h2 className="mb-4">Master Report Builder</h2>
      <CCard className="mb-4">
        <CCardHeader>Design Report Dataset</CCardHeader>
        <CCardBody>
          <form noValidate onSubmit={fetchReport}>
            <div className="mb-3">
              <CFormLabel className="fw-semibold">Report Scope</CFormLabel>
              <div className="d-flex gap-4">
                <CFormCheck
                  type="radio"
                  name="scope"
                  id="scope-all"
                  label="All Students"
                  checked={scope === 'all'}
                  onChange={() => setScope('all')}
                />
                <CFormCheck
                  type="radio"
                  name="scope"
                  id="scope-student"
                  label="Single Student"
                  checked={scope === 'student'}
                  onChange={() => setScope('student')}
                />
              </div>
            </div>

            {scope === 'student' && (
              <div className="mb-4" style={{ maxWidth: 420 }}>
                <CFormLabel htmlFor="student-select" className="fw-semibold">Student</CFormLabel>
                <CFormSelect
                  id="student-select"
                  value={studentId}
                  onChange={e => setStudentId(e.target.value)}
                >
                  <option value="">Choose student</option>
                  {students.map(student => (
                    <option key={student.id} value={student.id}>
                      {student.name} ({student.email})
                    </option>
                  ))}
                </CFormSelect>
                {errors.studentId && <div className="text-danger fw-bold mt-1">{errors.studentId}</div>}
              </div>
            )}

            <div className="d-flex gap-2 flex-wrap mb-3">
              <CButton
                type="button"
                color="dark"
                variant="outline"
                size="sm"
                onClick={() => setSelectedColumns(MASTER_COLUMNS.map(c => c.key))}
              >
                Select All Fields
              </CButton>
              <CButton
                type="button"
                color="secondary"
                variant="outline"
                size="sm"
                onClick={() => setSelectedColumns([])}
              >
                Clear All
              </CButton>
              <CButton
                type="button"
                color="info"
                variant="outline"
                size="sm"
                onClick={() => setSelectedColumns(DEFAULT_SELECTED)}
              >
                Reset Defaults
              </CButton>
            </div>

            <CRow className="g-3 mb-2">
              <CCol md={6}>
                <div
                  className="border rounded p-3"
                  onDragOver={e => e.preventDefault()}
                  onDrop={onDropToAvailable}
                  style={{ minHeight: 320, backgroundColor: '#f8f9fa' }}
                >
                  <div className="d-flex align-items-center justify-content-between mb-2">
                    <strong>Available Fields</strong>
                    <CBadge color="secondary">{availableColumns.length}</CBadge>
                  </div>
                  {availableColumns.length === 0 && (
                    <div className="text-body-secondary">All fields are selected.</div>
                  )}
                  {availableColumns.map(column => renderColumnChip(column, 'available'))}
                </div>
              </CCol>

              <CCol md={6}>
                <div
                  className="border rounded p-3"
                  onDragOver={e => e.preventDefault()}
                  onDrop={() => onDropToSelected()}
                  style={{ minHeight: 320, background: 'linear-gradient(145deg, #f2fffb 0%, #e8f4ff 100%)' }}
                >
                  <div className="d-flex align-items-center justify-content-between mb-2">
                    <strong>Selected Fields (Report Order)</strong>
                    <CBadge color="primary">{selectedColumns.length}</CBadge>
                  </div>
                  {selectedColumns.length === 0 && (
                    <div className="text-body-secondary">Drop fields here to build your dataset.</div>
                  )}
                  {selectedColumns.map(columnKey => renderColumnChip(columnMap[columnKey], 'selected'))}
                </div>
              </CCol>
            </CRow>

            {errors.columns && <div className="text-danger fw-bold mt-1">{errors.columns}</div>}
            {errors.report && <div className="text-danger fw-bold mt-1">{errors.report}</div>}

            <div className="mt-4 d-flex align-items-center gap-3">
              <CButton type="submit" color="primary" disabled={isLoading}>
                {isLoading ? 'Generating...' : 'Generate Master Report'}
              </CButton>
              <small className="text-body-secondary">Tip: drag between boxes and drop over selected fields to reorder.</small>
            </div>
          </form>
        </CCardBody>
      </CCard>

      {meta && (
        <div className="mb-3 text-body-secondary">
          {meta.total_rows} row(s) generated with {meta.columns?.length ?? 0} field(s).
        </div>
      )}

      {rows.length > 0 && (
        <CCard>
          <CCardHeader>Results</CCardHeader>
          <CCardBody>
            <CTable striped hover responsive>
              <CTableHead>
                <CTableRow>
                  {selectedColumns.map(columnKey => (
                    <CTableHeaderCell key={columnKey}>{columnMap[columnKey]?.label ?? columnKey}</CTableHeaderCell>
                  ))}
                </CTableRow>
              </CTableHead>
              <CTableBody>
                {rows.map((r, i) => (
                  <CTableRow key={i}>
                    {selectedColumns.map(columnKey => (
                      <CTableDataCell key={`${i}-${columnKey}`}>{r[columnKey] ?? '-'}</CTableDataCell>
                    ))}
                  </CTableRow>
                ))}
              </CTableBody>
            </CTable>
          </CCardBody>
        </CCard>
      )}
    </>
  );
}
