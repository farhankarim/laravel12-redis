import React, { useEffect, useState } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
  CButton,
  CCard,
  CCardBody,
  CCardHeader,
  CForm,
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

export default function CrudPage({ title, apiPath, fields, displayColumns }) {
  const [items, setItems] = useState([]);
  const [form, setForm] = useState({});
  const [editing, setEditing] = useState(null);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const fetchAll = () => {
    axios.get(apiPath)
      .then(r => setItems(r.data))
      .catch(() => setError('Failed to load data.'));
  };

  useEffect(() => { fetchAll(); }, [apiPath]);

  const handleSubmit = (e) => {
    e.preventDefault();
    setError(''); setSuccess('');
    const promise = editing
      ? axios.put(`${apiPath}/${editing}`, form)
      : axios.post(apiPath, form);
    promise
      .then(() => { setSuccess(editing ? 'Updated!' : 'Created!'); setForm({}); setEditing(null); fetchAll(); })
      .catch(err => setError(err.response?.data?.message || 'Error saving.'));
  };

  const handleEdit = (item) => { setEditing(item.id); setForm(item); setError(''); setSuccess(''); };
  const handleDelete = (id) => {
    Swal.fire({
      title: 'Delete this record?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it',
    }).then((result) => {
      if (!result.isConfirmed) return;
      axios.delete(`${apiPath}/${id}`)
        .then(() => { setSuccess('Deleted!'); fetchAll(); })
        .catch(() => setError('Delete failed.'));
    });
  };

  return (
    <>
      <h2 className="mb-4">{title}</h2>
      <CCard className="mb-4">
        <CCardHeader>{editing ? 'Edit Record' : 'Add New'}</CCardHeader>
        <CCardBody>
          {error && <CAlert color="danger">{error}</CAlert>}
          {success && <CAlert color="success">{success}</CAlert>}
          <CForm onSubmit={handleSubmit}>
            {fields.map(f => (
              <div key={f.key} className="mb-3">
                <CFormLabel>{f.label}</CFormLabel>
                <CFormInput
                  type={f.type || 'text'}
                  value={form[f.key] || ''}
                  onChange={e => setForm({ ...form, [f.key]: e.target.value })}
                  required={!editing}
                />
              </div>
            ))}
            <CButton type="submit" color="primary">{editing ? 'Update' : 'Create'}</CButton>
            {editing && <CButton color="secondary" className="ms-2" onClick={() => { setEditing(null); setForm({}); }}>Cancel</CButton>}
          </CForm>
        </CCardBody>
      </CCard>
      <CCard>
        <CCardHeader>Records</CCardHeader>
        <CCardBody>
          <CTable striped hover responsive>
            <CTableHead>
              <CTableRow>
                <CTableHeaderCell>ID</CTableHeaderCell>
                {displayColumns.map(c => <CTableHeaderCell key={c.key}>{c.label}</CTableHeaderCell>)}
                <CTableHeaderCell>Actions</CTableHeaderCell>
              </CTableRow>
            </CTableHead>
            <CTableBody>
              {items.map(item => (
                <CTableRow key={item.id}>
                  <CTableDataCell>{item.id}</CTableDataCell>
                  {displayColumns.map(c => <CTableDataCell key={c.key}>{String(item[c.key] ?? '')}</CTableDataCell>)}
                  <CTableDataCell>
                    <CButton size="sm" color="warning" className="me-2" onClick={() => handleEdit(item)}>Edit</CButton>
                    <CButton size="sm" color="danger" onClick={() => handleDelete(item.id)}>Delete</CButton>
                  </CTableDataCell>
                </CTableRow>
              ))}
            </CTableBody>
          </CTable>
        </CCardBody>
      </CCard>
    </>
  );
}
