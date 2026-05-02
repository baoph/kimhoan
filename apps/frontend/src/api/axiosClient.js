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

    // Get token from localStorage (ưu tiên key mới, fallback key hiện tại)
    const token = localStorage.getItem('token') || localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    // Get XSRF token from cookie and add to header
    const xsrfToken = getCookie('XSRF-TOKEN');
    if (xsrfToken) {
      const decodedToken = decodeURIComponent(xsrfToken);
      config.headers['X-XSRF-TOKEN'] = decodedToken;
      console.log('[axiosClient] X-XSRF-TOKEN header set:', decodedToken.slice(0, 12) + '...');
    } else {
      console.warn('[axiosClient] XSRF-TOKEN cookie not found before request:', config.url);
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
