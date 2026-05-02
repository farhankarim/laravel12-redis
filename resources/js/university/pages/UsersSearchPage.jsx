import React, { useState } from 'react';
import axios from 'axios';
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
import { useSEO } from '../hooks/useSEO.jsx';

export default function UsersSearchPage() {
  useSEO({
    title: 'User Search',
    description: 'Search users by name using Elasticsearch-backed API results.',
    keywords: 'users, search, elasticsearch, name search',
  });

  const [name, setName] = useState('');
  const [results, setResults] = useState([]);
  const [loading, setLoading] = useState(false);
  const [searched, setSearched] = useState(false);
  const [error, setError] = useState('');

  const handleSearch = async (event) => {
    event.preventDefault();

    const query = name.trim();
    if (!query) {
      setResults([]);
      setSearched(false);
      setError('');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await axios.get('/api/v1/users/search', {
        params: { name: query },
      });
      setResults(response.data?.data ?? []);
      setSearched(true);
    } catch (requestError) {
      setError(requestError.response?.data?.message || 'Failed to search users.');
      setResults([]);
      setSearched(true);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <div className="mb-4">
        <h2 className="mb-1">Users</h2>
        <div className="text-body-secondary">Users / Search</div>
      </div>

      <CCard className="mb-4">
        <CCardHeader>Search Users by Name</CCardHeader>
        <CCardBody>
          <CForm className="row g-3" onSubmit={handleSearch}>
            <div className="col-12 col-md-8">
              <CFormLabel htmlFor="search-name">Name</CFormLabel>
              <CFormInput
                id="search-name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder="Type a user name..."
                required
              />
            </div>
            <div className="col-12 col-md-4 d-flex align-items-end">
              <CButton type="submit" color="primary" disabled={loading} className="w-100">
                {loading ? 'Searching...' : 'Search'}
              </CButton>
            </div>
          </CForm>
          {error && <div className="text-danger fw-bold mt-3">{error}</div>}
        </CCardBody>
      </CCard>

      <CCard>
        <CCardHeader>
          Results {searched ? `(${results.length})` : ''}
        </CCardHeader>
        <CCardBody>
          {searched && results.length === 0 ? (
            <div className="text-body-secondary">No users found for this query.</div>
          ) : (
            <CTable striped hover responsive>
              <CTableHead>
                <CTableRow>
                  <CTableHeaderCell>ID</CTableHeaderCell>
                  <CTableHeaderCell>Name</CTableHeaderCell>
                  <CTableHeaderCell>Email</CTableHeaderCell>
                </CTableRow>
              </CTableHead>
              <CTableBody>
                {results.map((user) => (
                  <CTableRow key={user.id}>
                    <CTableDataCell>{user.id}</CTableDataCell>
                    <CTableDataCell>{user.name}</CTableDataCell>
                    <CTableDataCell>{user.email}</CTableDataCell>
                  </CTableRow>
                ))}
              </CTableBody>
            </CTable>
          )}
        </CCardBody>
      </CCard>
    </>
  );
}
