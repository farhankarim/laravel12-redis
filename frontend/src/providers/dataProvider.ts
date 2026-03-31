/* eslint-disable @typescript-eslint/no-explicit-any */
import type { DataProvider } from 'react-admin';

const API_URL = '';

function getToken(): string {
  return localStorage.getItem('token') || '';
}

function authHeaders(): HeadersInit {
  return {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${getToken()}`,
  };
}

/** Map _id → id so React Admin's DataGrid can use it */
function normalise(record: Record<string, unknown>): Record<string, unknown> {
  if (record._id && !record.id) {
    return { ...record, id: record._id };
  }
  return record;
}

async function handleResponse(res: Response) {
  if (res.status === 204) return null;
  const json = await res.json();
  if (!res.ok) throw new Error((json as any)?.message || res.statusText);
  return json;
}

export const dataProvider: DataProvider = {
  async getList(resource, params) {
    const page = params.pagination?.page ?? 1;
    const perPage = params.pagination?.perPage ?? 20;
    const field = params.sort?.field;
    const order = params.sort?.order;

    const query = new URLSearchParams({
      page: String(page),
      limit: String(perPage),
      ...(field ? { sortField: field, sortOrder: order ?? 'DESC' } : {}),
      ...Object.fromEntries(
        Object.entries(params.filter || {}).filter(([, v]) => v !== undefined && v !== ''),
      ),
    });

    const res = await fetch(`${API_URL}/${resource}?${query}`, {
      headers: authHeaders(),
    });
    const json = await handleResponse(res);

    const data = ((json as any).data ?? json).map(normalise);
    const total = (json as any).total ?? data.length;

    return { data, total } as any;
  },

  async getOne(resource, params) {
    const res = await fetch(`${API_URL}/${resource}/${params.id}`, {
      headers: authHeaders(),
    });
    const json = await handleResponse(res);
    return { data: normalise(json as Record<string, unknown>) } as any;
  },

  async create(resource, params) {
    const res = await fetch(`${API_URL}/${resource}`, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify(params.data),
    });
    const json = await handleResponse(res);
    return { data: normalise(json as Record<string, unknown>) } as any;
  },

  async update(resource, params) {
    const res = await fetch(`${API_URL}/${resource}/${params.id}`, {
      method: 'PUT',
      headers: authHeaders(),
      body: JSON.stringify(params.data),
    });
    const json = await handleResponse(res);
    return { data: normalise(json as Record<string, unknown>) } as any;
  },

  async delete(resource, params) {
    await fetch(`${API_URL}/${resource}/${params.id}`, {
      method: 'DELETE',
      headers: authHeaders(),
    });
    return { data: { id: params.id } } as any;
  },

  async deleteMany(resource, params) {
    await Promise.all(
      (params.ids as string[]).map((id) =>
        fetch(`${API_URL}/${resource}/${id}`, {
          method: 'DELETE',
          headers: authHeaders(),
        }),
      ),
    );
    return { data: params.ids } as any;
  },

  async updateMany(resource, params) {
    await Promise.all(
      (params.ids as string[]).map((id) =>
        fetch(`${API_URL}/${resource}/${id}`, {
          method: 'PUT',
          headers: authHeaders(),
          body: JSON.stringify(params.data),
        }),
      ),
    );
    return { data: params.ids } as any;
  },

  async getMany(resource, params) {
    const results = await Promise.all(
      (params.ids as string[]).map((id) =>
        fetch(`${API_URL}/${resource}/${id}`, { headers: authHeaders() })
          .then((r) => r.json())
          .then((r) => normalise(r as Record<string, unknown>)),
      ),
    );
    return { data: results } as any;
  },

  async getManyReference(resource, params) {
    const page = params.pagination?.page ?? 1;
    const perPage = params.pagination?.perPage ?? 20;
    const query = new URLSearchParams({
      page: String(page),
      limit: String(perPage),
      [params.target]: String(params.id),
    });
    const res = await fetch(`${API_URL}/${resource}?${query}`, {
      headers: authHeaders(),
    });
    const json = await handleResponse(res);
    const data = ((json as any).data ?? json).map(normalise);
    return { data, total: (json as any).total ?? data.length } as any;
  },
};
