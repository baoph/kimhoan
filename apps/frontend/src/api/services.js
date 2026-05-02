import axiosClient from './axiosClient';

const q = (params = {}) => ({ params });

export const authApi = {
  login: (payload) => axiosClient.post('/auth/login', payload),
  profile: () => axiosClient.get('/auth/profile'),
  logout: () => axiosClient.post('/auth/logout'),
};

export const dashboardApi = {
  todayStats: () => axiosClient.get('/dashboard/today-stats'),
  topSelling: () => axiosClient.get('/dashboard/top-selling-products'),
  topCustomers: () => axiosClient.get('/dashboard/top-customers'),
  revenueChart: (type = 'day') => axiosClient.get('/dashboard/revenue-chart', q({ type })),
};

export const productsApi = {
  list: (params) => axiosClient.get('/products', q(params)),
  create: (payload) => axiosClient.post('/products', payload),
  update: (id, payload) => axiosClient.put(`/products/${id}`, payload),
  remove: (id) => axiosClient.delete(`/products/${id}`),
  lowStock: (params) => axiosClient.get('/products/low-stock', q(params)),
};

export const customersApi = {
  list: (params) => axiosClient.get('/customers', q(params)),
  create: (payload) => axiosClient.post('/customers', payload),
  update: (id, payload) => axiosClient.put(`/customers/${id}`, payload),
  remove: (id) => axiosClient.delete(`/customers/${id}`),
};

export const ordersApi = {
  list: (params) => axiosClient.get('/orders', q(params)),
  create: (payload) => axiosClient.post('/orders', payload),
  show: (id) => axiosClient.get(`/orders/${id}`),
  updateStatus: (id, payload) => axiosClient.patch(`/orders/${id}/status`, payload),
  remove: (id) => axiosClient.delete(`/orders/${id}`),
};

export const categoriesApi = {
  list: (params) => axiosClient.get('/categories', q(params)),
};

export const brandsApi = {
  list: (params) => axiosClient.get('/brands', q(params)),
};

export const reportsApi = {
  sales: (params) => axiosClient.get('/reports/sales', q(params)),
  inventory: (params) => axiosClient.get('/reports/inventory', q(params)),
};
