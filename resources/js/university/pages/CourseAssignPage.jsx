import React, { useEffect, useState } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';
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
  CSpinner,
} from '@coreui/react';

const ASSIGNMENT_TYPES = [
  { value: 'students', label: 'Assign Students to Course', icon: '👥' },
  { value: 'instructors', label: 'Assign Instructors to Course', icon: '👨‍🏫' },
];

export default function CourseAssignPage() {
  const [courses, setCourses] = useState([]);
  const [selectedCourse, setSelectedCourse] = useState('');
  const [assignmentType, setAssignmentType] = useState('students');
  const [available, setAvailable] = useState([]);
  const [assigned, setAssigned] = useState([]);
  const [selectedForAssign, setSelectedForAssign] = useState([]);
  const [selectedForRevoke, setSelectedForRevoke] = useState([]);
  const [semesterForStudents, setSemesterForStudents] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [draggedItem, setDraggedItem] = useState(null);
  const [dragSource, setDragSource] = useState(null);

  const courseLabel = assignmentType === 'students' ? 'Student' : 'Instructor';
  const courseFieldKey = assignmentType === 'students' ? 'student_ids' : 'instructor_ids';
  const isSingleInstructorMode = assignmentType === 'instructors';

  // Load courses on mount
  useEffect(() => {
    const loadCourses = async () => {
      try {
        const response = await axios.get('/api/v1/courses');
        setCourses(Array.isArray(response.data) ? response.data : []);
      } catch {
        setCourses([]);
      }
    };
    loadCourses();
  }, []);

  // Load available and assigned entities when course/type changes
  useEffect(() => {
    if (!selectedCourse) {
      setAvailable([]);
      setAssigned([]);
      setSelectedForAssign([]);
      setSelectedForRevoke([]);
      return;
    }

    const loadEntities = async () => {
      setIsLoading(true);
      try {
        const availableKey = assignmentType === 'students' ? 'available-students' : 'available-instructors';
        const assignedKey = assignmentType === 'students' ? 'assigned-students' : 'assigned-instructors';

        const [availRes, assignRes] = await Promise.all([
          axios.get(`/api/v1/courses/${selectedCourse}/${availableKey}`),
          axios.get(`/api/v1/courses/${selectedCourse}/${assignedKey}`),
        ]);

        setAvailable(Array.isArray(availRes.data) ? availRes.data : []);
        setAssigned(Array.isArray(assignRes.data) ? assignRes.data : []);
        setSelectedForAssign([]);
        setSelectedForRevoke([]);
      } catch {
        setAvailable([]);
        setAssigned([]);
      } finally {
        setIsLoading(false);
      }
    };

    loadEntities();
  }, [selectedCourse, assignmentType]);

  const toggleSelectForAssign = (id) => {
    setSelectedForAssign(prev => {
      if (isSingleInstructorMode) {
        return prev.includes(id) ? [] : [id];
      }

      return prev.includes(id)
        ? prev.filter(x => x !== id)
        : [...prev, id];
    });
  };

  const toggleSelectForRevoke = (id) => {
    setSelectedForRevoke(prev =>
      prev.includes(id)
        ? prev.filter(x => x !== id)
        : [...prev, id]
    );
  };

  const selectAllAvailable = () => {
    if (isSingleInstructorMode) {
      setSelectedForAssign(available[0] ? [available[0].id] : []);
      return;
    }

    setSelectedForAssign(available.map(a => a.id));
  };

  const clearAllAvailable = () => {
    setSelectedForAssign([]);
  };

  const selectAllAssigned = () => {
    setSelectedForRevoke(assigned.map(a => a.id));
  };

  const clearAllAssigned = () => {
    setSelectedForRevoke([]);
  };

  const handleDragStart = (id, source) => {
    setDraggedItem(id);
    setDragSource(source);
  };

  const handleDragOver = (e) => {
    e.preventDefault();
  };

  const handleDropToAssign = () => {
    if (draggedItem && dragSource === 'available') {
      toggleSelectForAssign(draggedItem);
      setDraggedItem(null);
      setDragSource(null);
    }
  };

  const handleDropToRevoke = () => {
    if (draggedItem && dragSource === 'assigned') {
      toggleSelectForRevoke(draggedItem);
      setDraggedItem(null);
      setDragSource(null);
    }
  };

  const submitAssignments = async () => {
    if (selectedForAssign.length === 0) {
      Swal.fire({
        icon: 'info',
        title: 'Nothing to assign',
        text: `Select at least one ${courseLabel.toLowerCase()} to assign.`,
      });
      return;
    }

    if (assignmentType === 'students' && !semesterForStudents) {
      Swal.fire({
        icon: 'warning',
        title: 'Semester required',
        text: 'Please select a semester for student assignments.',
      });
      return;
    }

    setIsSubmitting(true);
    try {
      const payload = {
        [courseFieldKey]: selectedForAssign,
      };

      if (assignmentType === 'students') {
        payload.semester = semesterForStudents;

        const validationResponse = await axios.post(
          `/api/v1/courses/${selectedCourse}/validate-students-assignment`,
          payload
        );

        const preConflictCount = validationResponse.data?.conflict_count ?? 0;
        const preConflictStudents = validationResponse.data?.conflict_students ?? [];

        if (preConflictCount > 0) {
          const names = preConflictStudents.map(student => student.name).join(', ');

          const proceed = await Swal.fire({
            icon: 'warning',
            title: 'Schedule conflicts detected',
            text: `${preConflictCount} selected student(s) already have another course at the same time in ${semesterForStudents}. Conflicting students will be skipped. Continue?${names ? ` Conflicts: ${names}` : ''}`,
            showCancelButton: true,
            confirmButtonText: 'Continue',
            cancelButtonText: 'Cancel',
          });

          if (!proceed.isConfirmed) {
            setIsSubmitting(false);
            return;
          }
        }
      }

      const response = await axios.post(
        `/api/v1/courses/${selectedCourse}/assign-${assignmentType}`,
        payload
      );

      const assignedCount = response.data.assigned_count ?? response.data.count ?? 0;
      const conflictCount = response.data.conflict_count ?? 0;
      const conflictStudents = response.data.conflict_students ?? [];

      let successText = response.data.message;

      if (assignmentType === 'students' && conflictCount > 0) {
        const names = conflictStudents.map(student => student.name).join(', ');
        successText = `${assignedCount} assigned, ${conflictCount} skipped due to schedule overlap in ${semesterForStudents}.`;

        if (names) {
          successText += ` Conflicts: ${names}`;
        }
      }

      Swal.fire({
        icon: conflictCount > 0 ? 'warning' : 'success',
        title: conflictCount > 0 ? 'Partial assignment' : 'Success',
        text: successText,
      });

      // Refresh the lists
      const availableKey = assignmentType === 'students' ? 'available-students' : 'available-instructors';
      const assignedKey = assignmentType === 'students' ? 'assigned-students' : 'assigned-instructors';

      const [availRes, assignRes] = await Promise.all([
        axios.get(`/api/v1/courses/${selectedCourse}/${availableKey}`),
        axios.get(`/api/v1/courses/${selectedCourse}/${assignedKey}`),
      ]);

      setAvailable(Array.isArray(availRes.data) ? availRes.data : []);
      setAssigned(Array.isArray(assignRes.data) ? assignRes.data : []);
      setSelectedForAssign([]);
      setSelectedForRevoke([]);
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Assignment failed',
        text: err.response?.data?.message || 'Unable to complete assignment.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const submitRevocations = async () => {
    if (selectedForRevoke.length === 0) {
      Swal.fire({
        icon: 'info',
        title: 'Nothing to revoke',
        text: `Select at least one ${courseLabel.toLowerCase()} to revoke.`,
      });
      return;
    }

    const result = await Swal.fire({
      icon: 'warning',
      title: 'Confirm revocation',
      text: `Remove ${selectedForRevoke.length} ${courseLabel.toLowerCase()}(s) from course?`,
      showCancelButton: true,
      confirmButtonText: 'Remove',
      cancelButtonText: 'Cancel',
    });

    if (!result.isConfirmed) return;

    setIsSubmitting(true);
    try {
      const payload = {
        [courseFieldKey]: selectedForRevoke,
      };

      const response = await axios.post(
        `/api/v1/courses/${selectedCourse}/revoke-${assignmentType}`,
        payload
      );

      Swal.fire({
        icon: 'success',
        title: 'Success',
        text: response.data.message,
      });

      // Refresh the lists
      const availableKey = assignmentType === 'students' ? 'available-students' : 'available-instructors';
      const assignedKey = assignmentType === 'students' ? 'assigned-students' : 'assigned-instructors';

      const [availRes, assignRes] = await Promise.all([
        axios.get(`/api/v1/courses/${selectedCourse}/${availableKey}`),
        axios.get(`/api/v1/courses/${selectedCourse}/${assignedKey}`),
      ]);

      setAvailable(Array.isArray(availRes.data) ? availRes.data : []);
      setAssigned(Array.isArray(assignRes.data) ? assignRes.data : []);
      setSelectedForAssign([]);
      setSelectedForRevoke([]);
    } catch (err) {
      Swal.fire({
        icon: 'error',
        title: 'Revocation failed',
        text: err.response?.data?.message || 'Unable to complete revocation.',
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  const renderEntityItem = (item, isSelected, onToggle, draggable = false, source = null) => {
    const label = item.name;
    const sublabel = item.email || item.specialization || '';

    return (
      <div
        key={item.id}
        className="d-flex align-items-center gap-2 border rounded p-2 mb-2 bg-white"
        draggable={draggable}
        onDragStart={() => handleDragStart(item.id, source)}
        style={{ cursor: draggable ? 'grab' : 'pointer' }}
        onClick={() => onToggle(item.id)}
      >
        <CFormCheck
          type="checkbox"
          checked={isSelected}
          onChange={() => onToggle(item.id)}
          style={{ margin: 0, cursor: 'pointer' }}
        />
        <div className="flex-grow-1">
          <div className="fw-medium">{label}</div>
          {sublabel && <div className="text-body-secondary small">{sublabel}</div>}
        </div>
      </div>
    );
  };

  return (
    <>
      <div className="mb-4">
        <h2 className="mb-1">Course Assignment Manager</h2>
        <div className="text-body-secondary">Assign students and instructors to courses</div>
      </div>

      <CCard className="mb-4">
        <CCardHeader>Setup & Configuration</CCardHeader>
        <CCardBody>
          <CRow className="g-3">
            <CCol md={6}>
              <CFormLabel htmlFor="course-select" className="fw-semibold">
                Select Course
              </CFormLabel>
              <CFormSelect
                id="course-select"
                value={selectedCourse}
                onChange={e => setSelectedCourse(e.target.value)}
              >
                <option value="">Choose a course...</option>
                {courses.map(course => (
                  <option key={course.id} value={course.id}>
                    {course.course_code} - {course.title} ({course.credit_hours ?? 0} CH)
                  </option>
                ))}
              </CFormSelect>
            </CCol>

            <CCol md={6}>
              <CFormLabel htmlFor="type-select" className="fw-semibold">
                Assignment Type
              </CFormLabel>
              <CFormSelect
                id="type-select"
                value={assignmentType}
                onChange={e => setAssignmentType(e.target.value)}
                disabled={!selectedCourse}
              >
                {ASSIGNMENT_TYPES.map(type => (
                  <option key={type.value} value={type.value}>
                    {type.icon} {type.label}
                  </option>
                ))}
              </CFormSelect>
            </CCol>
          </CRow>

          {assignmentType === 'students' && selectedCourse && (
            <div className="mt-3">
              <CFormLabel htmlFor="semester-select" className="fw-semibold">
                Semester (for new enrollments)
              </CFormLabel>
              <CFormSelect
                id="semester-select"
                value={semesterForStudents}
                onChange={e => setSemesterForStudents(e.target.value)}
              >
                <option value="">Select semester...</option>
                <option value="Spring 2024">Spring 2024</option>
                <option value="Summer 2024">Summer 2024</option>
                <option value="Fall 2024">Fall 2024</option>
                <option value="Winter 2024">Winter 2024</option>
                <option value="Spring 2025">Spring 2025</option>
                <option value="Summer 2025">Summer 2025</option>
                <option value="Fall 2025">Fall 2025</option>
                <option value="Winter 2025">Winter 2025</option>
              </CFormSelect>
            </div>
          )}
        </CCardBody>
      </CCard>

      {!selectedCourse ? (
        <CCard>
          <CCardBody className="text-center text-body-secondary py-5">
            <p>Select a course to begin assigning {courseLabel.toLowerCase()}s.</p>
          </CCardBody>
        </CCard>
      ) : isLoading ? (
        <CCard>
          <CCardBody className="text-center py-5">
            <CSpinner color="primary" />
            <p className="mt-2">Loading available {courseLabel.toLowerCase()}s...</p>
          </CCardBody>
        </CCard>
      ) : (
        <CRow className="g-3">
          <CCol lg={6}>
            <CCard>
              <CCardHeader className="d-flex justify-content-between align-items-center">
                <span>Available {courseLabel}s ({available.length})</span>
                <div className="d-flex gap-2">
                  <CButton
                    size="sm"
                    color="primary"
                    variant="outline"
                    onClick={selectAllAvailable}
                  >
                    All
                  </CButton>
                  <CButton
                    size="sm"
                    color="secondary"
                    variant="outline"
                    onClick={clearAllAvailable}
                  >
                    Clear
                  </CButton>
                </div>
              </CCardHeader>
              <CCardBody
                onDragOver={handleDragOver}
                onDrop={handleDropToAssign}
                style={{ minHeight: 400, backgroundColor: '#f8f9fa' }}
              >
                {available.length === 0 ? (
                  <div className="text-center text-body-secondary py-4">
                    All {courseLabel.toLowerCase()}s are already assigned.
                  </div>
                ) : (
                  available.map(item =>
                    renderEntityItem(
                      item,
                      selectedForAssign.includes(item.id),
                      toggleSelectForAssign,
                      true,
                      'available'
                    )
                  )
                )}
              </CCardBody>
            </CCard>
          </CCol>

          <CCol lg={6}>
            <CCard>
              <CCardHeader className="d-flex justify-content-between align-items-center">
                <span>Assigned {courseLabel}s ({assigned.length})</span>
                <div className="d-flex gap-2">
                  <CButton
                    size="sm"
                    color="primary"
                    variant="outline"
                    onClick={selectAllAssigned}
                  >
                    All
                  </CButton>
                  <CButton
                    size="sm"
                    color="secondary"
                    variant="outline"
                    onClick={clearAllAssigned}
                  >
                    Clear
                  </CButton>
                </div>
              </CCardHeader>
              <CCardBody
                onDragOver={handleDragOver}
                onDrop={handleDropToRevoke}
                style={{ minHeight: 400, backgroundColor: '#fff8f0' }}
              >
                {assigned.length === 0 ? (
                  <div className="text-center text-body-secondary py-4">
                    No {courseLabel.toLowerCase()}s assigned yet.
                  </div>
                ) : (
                  assigned.map(item =>
                    renderEntityItem(
                      item,
                      selectedForRevoke.includes(item.id),
                      toggleSelectForRevoke,
                      true,
                      'assigned'
                    )
                  )
                )}
              </CCardBody>
            </CCard>
          </CCol>
        </CRow>
      )}

      {selectedCourse && !isLoading && (
        <CRow className="g-3 mt-3">
          <CCol md={6}>
            <CCard>
              <CCardHeader>Assign Selected</CCardHeader>
              <CCardBody>
                {selectedForAssign.length === 0 ? (
                  <p className="text-body-secondary mb-0">
                    Select {courseLabel.toLowerCase()}s from the available list.
                  </p>
                ) : (
                  <>
                    <CBadge color="primary" className="mb-3">
                      {selectedForAssign.length} {courseLabel.toLowerCase()}(s) ready to assign
                    </CBadge>
                    <CButton
                      color="success"
                      width="100%"
                      onClick={submitAssignments}
                      disabled={isSubmitting}
                      className="w-100"
                    >
                      {isSubmitting ? 'Assigning...' : `Assign ${selectedForAssign.length} ${courseLabel.toLowerCase()}(s)`}
                    </CButton>
                  </>
                )}
              </CCardBody>
            </CCard>
          </CCol>

          <CCol md={6}>
            <CCard>
              <CCardHeader>Revoke Selected</CCardHeader>
              <CCardBody>
                {selectedForRevoke.length === 0 ? (
                  <p className="text-body-secondary mb-0">
                    Select {courseLabel.toLowerCase()}s from the assigned list.
                  </p>
                ) : (
                  <>
                    <CBadge color="danger" className="mb-3">
                      {selectedForRevoke.length} {courseLabel.toLowerCase()}(s) to remove
                    </CBadge>
                    <CButton
                      color="danger"
                      onClick={submitRevocations}
                      disabled={isSubmitting}
                      className="w-100"
                    >
                      {isSubmitting ? 'Revoking...' : `Revoke ${selectedForRevoke.length} ${courseLabel.toLowerCase()}(s)`}
                    </CButton>
                  </>
                )}
              </CCardBody>
            </CCard>
          </CCol>
        </CRow>
      )}

      <div className="mt-4 text-body-secondary small">
        <p>💡 <strong>Tip:</strong> You can drag items between lists or click to select. Use the "All" and "Clear" buttons for bulk selection.</p>
      </div>
    </>
  );
}
