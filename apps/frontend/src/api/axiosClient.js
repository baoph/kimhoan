import axios from 'axios';

const API_URL = import.meta.env.VITE_API_URL || import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1';

function getCookie(name) {
  if (typeof document === 'undefined') {
    return null;
  }

  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) {
    return parts.pop().split(';').shift();
  }

  return null;
}

function getRequestPath(url = '') {
  try {
    return new URL(url, window.location.origin).pathname;
  } catch {
    return (url || '').split('?')[0];
  }
}

function shouldSkipWarehouseHeader(url = '') {
  const requestPath = getRequestPath(url);

  return requestPath.startsWith('/auth/') || requestPath === '/auth' || requestPath === '/warehouses' || requestPath === '/warehouses/';
}

const axiosClient = axios.create({
  baseURL: API_URL,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
});

axiosClient.interceptors.request.use(
  (config) => {
    config.withCredentials = true;
    config.headers = config.headers || {};
    config.headers['X-Requested-With'] = 'XMLHttpRequest';

    const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    const xsrfToken = getCookie('XSRF-TOKEN');
    if (xsrfToken) {
      const decodedToken = decodeURIComponent(xsrfToken);
      config.headers['X-XSRF-TOKEN'] = decodedToken;
    }

    const warehouseId = localStorage.getItem('current_warehouse_id');
    if (warehouseId && !shouldSkipWarehouseHeader(config.url)) {
      config.headers['X-Warehouse-Id'] = warehouseId;
    } else {
      delete config.headers['X-Warehouse-Id'];
    }

    return config;
  },
  (error) => Promise.reject(error)
);

axiosClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('auth_token');
      localStorage.removeItem('token');
    }

    return Promise.reject(error);
  }
);

export default axiosClient;
