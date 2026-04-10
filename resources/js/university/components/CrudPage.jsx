import React, { useEffect, useState } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useNavigate } from 'react-router-dom';
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
} from '@coreui/react';

export default function CrudPage({
  title,
  apiPath,
  fields,
  displayColumns,
  mode = 'list',
  basePath,
  recordId = null,
}) {
  const navigate = useNavigate();
  const isListMode = mode === 'list';
  const isEditMode = mode === 'edit';
  const screenLabel = isListMode
    ? 'List'
    : (isEditMode ? `Edit #${recordId ?? ''}` : 'New');
  const breadcrumbText = `${title} / ${screenLabel}`;

  const [items, setItems] = useState([]);
  const [form, setForm] = useState({});
  const [errors, setErrors] = useState({});
  const [loadingForm, setLoadingForm] = useState(false);

  const fetchAll = () => {
    axios.get(apiPath)
      .then(r => setItems(r.data))
      .catch(() => {
        Swal.fire({
          icon: 'error',
          title: 'Load failed',
          text: 'Failed to load data.',
        });
      });
  };

  const fetchOne = () => {
    if (!recordId) {
      return;
    }

    setLoadingForm(true);
    axios.get(`${apiPath}/${recordId}`)
      .then((r) => {
        setForm(r.data || {});
      })
      .catch(() => {
        Swal.fire({
          icon: 'error',
          title: 'Load failed',
          text: 'Failed to load record.',
        });
      })
      .finally(() => {
        setLoadingForm(false);
      });
  };

  useEffect(() => {
    setErrors({});

    if (isListMode) {
      fetchAll();
      return;
    }

    if (isEditMode) {
      fetchOne();
    } else {
      setForm({});
    }
  }, [apiPath, isEditMode, isListMode, recordId]);

  const handleSubmit = (e) => {
    e.preventDefault();
    setErrors({});
    const promise = isEditMode
      ? axios.put(`${apiPath}/${recordId}`, form)
      : axios.post(apiPath, form);

    promise
      .then(() => {
        Swal.fire({
          icon: 'success',
          title: isEditMode ? 'Updated!' : 'Created!',
          timer: 1600,
          showConfirmButton: false,
        });
        navigate(basePath);
      })
      .catch(err => {
        const responseErrors = err.response?.data?.errors;
        if (responseErrors && typeof responseErrors === 'object') {
          const nextErrors = {};
          Object.entries(responseErrors).forEach(([key, value]) => {
            nextErrors[key] = Array.isArray(value) ? value[0] : String(value);
          });
          setErrors(nextErrors);
          return;
        }

        Swal.fire({
          icon: 'error',
          title: 'Save failed',
          text: err.response?.data?.message || 'Error saving.',
        });
      });
  };

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
        .then(() => {
          Swal.fire({
            icon: 'success',
            title: 'Deleted!',
            timer: 1600,
            showConfirmButton: false,
          });
          fetchAll();
        })
        .catch(() => {
          Swal.fire({
            icon: 'error',
            title: 'Delete failed',
            text: 'Unable to delete this record.',
          });
        });
    });
  };

  return (
    <>
      <div className="mb-4">
        <h2 className="mb-1">{title}</h2>
        <div className="text-body-secondary">{breadcrumbText}</div>
      </div>

      {!isListMode && (
        <CCard className="mb-4">
          <CCardHeader>{isEditMode ? 'Edit Record' : 'Add New'}</CCardHeader>
          <CCardBody>
            {loadingForm ? (
              <div>Loading record...</div>
            ) : (
              <CForm noValidate onSubmit={handleSubmit}>
                {fields.map(f => (
                  <div key={f.key} className="mb-3">
                    <CFormLabel>{f.label}</CFormLabel>
                    <CFormInput
                      type={f.type || 'text'}
                      value={form[f.key] || ''}
                      onChange={e => setForm({ ...form, [f.key]: e.target.value })}
                      required
                    />
                    {errors[f.key] && (
                      <div className="text-danger fw-bold mt-1">{errors[f.key]}</div>
                    )}
                  </div>
                ))}
                <CButton type="submit" color="primary">{isEditMode ? 'Update' : 'Create'}</CButton>
                <CButton
                  color="secondary"
                  className="ms-2"
                  onClick={() => navigate(basePath)}
                >
                  Cancel
                </CButton>
              </CForm>
            )}
          </CCardBody>
        </CCard>
      )}

      {isListMode && (
        <CCard>
          <CCardHeader className="d-flex justify-content-between align-items-center">
            <span>Records</span>
            <CButton size="sm" color="primary" onClick={() => navigate(`${basePath}/new`)}>
              Add New
            </CButton>
          </CCardHeader>
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
                      <CButton
                        size="sm"
                        color="warning"
                        className="me-2"
                        onClick={() => navigate(`${basePath}/${item.id}/edit`)}
                      >
                        Edit
                      </CButton>
                      <CButton size="sm" color="danger" onClick={() => handleDelete(item.id)}>Delete</CButton>
                    </CTableDataCell>
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
