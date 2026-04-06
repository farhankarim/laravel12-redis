import React, { useState } from 'react';
import axios from 'axios';
import {
  CButton,
  CCard,
  CCardBody,
  CCardHeader,
  CFormInput,
  CFormLabel,
  CTable,
  CTableBody,
  CTableDataCell,
  CTableHead,
  CTableHeaderCell,
  CTableRow,
  CAlert,
} from '@coreui/react';

export default function ReportPage() {
  const [studentId, setStudentId] = useState('');
  const [rows, setRows] = useState([]);
  const [error, setError] = useState('');

  const fetchReport = (e) => {
    e.preventDefault();
    setError('');
    axios.get(`/api/v1/students/${studentId}/report`)
      .then(r => setRows(r.data))
      .catch(() => setError('Failed to load report. Ensure student ID exists and all relations are set up.'));
  };

  return (
    <>
      <h2 className="mb-4">5-Entity Master Report</h2>
      <CCard className="mb-4">
        <CCardHeader>Run Report</CCardHeader>
        <CCardBody>
          {error && <CAlert color="danger">{error}</CAlert>}
          <form onSubmit={fetchReport} className="d-flex gap-3 align-items-end">
            <div>
              <CFormLabel>Student ID</CFormLabel>
              <CFormInput type="number" value={studentId} onChange={e => setStudentId(e.target.value)} required />
            </div>
            <CButton type="submit" color="primary">Generate</CButton>
          </form>
        </CCardBody>
      </CCard>
      {rows.length > 0 && (
        <CCard>
          <CCardHeader>Results</CCardHeader>
          <CCardBody>
            <CTable striped hover responsive>
              <CTableHead>
                <CTableRow>
                  <CTableHeaderCell>Student</CTableHeaderCell>
                  <CTableHeaderCell>Course</CTableHeaderCell>
                  <CTableHeaderCell>Instructor</CTableHeaderCell>
                  <CTableHeaderCell>Room</CTableHeaderCell>
                  <CTableHeaderCell>Department</CTableHeaderCell>
                </CTableRow>
              </CTableHead>
              <CTableBody>
                {rows.map((r, i) => (
                  <CTableRow key={i}>
                    <CTableDataCell>{r.student}</CTableDataCell>
                    <CTableDataCell>{r.course}</CTableDataCell>
                    <CTableDataCell>{r.instructor}</CTableDataCell>
                    <CTableDataCell>{r.room}</CTableDataCell>
                    <CTableDataCell>{r.department}</CTableDataCell>
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
