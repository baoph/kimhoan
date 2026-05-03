import { format } from 'date-fns';
import { vi } from 'date-fns/locale';

export const formatCurrency = (value = 0) =>
  Number(value || 0).toLocaleString('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  });

export const formatNumber = (value = 0) => Number(value || 0).toLocaleString('vi-VN');

export const formatDate = (value) => {
  if (!value) return '--';
  try {
    return format(new Date(value), 'dd/MM/yyyy HH:mm', { locale: vi });
  } catch {
    return '--';
  }
};

export const formatDateOnly = (value) => {
  if (!value) return '--';
  try {
    return format(new Date(value), 'dd/MM/yyyy', { locale: vi });
  } catch {
    return '--';
  }
};

export const toInputDateTime = (value) => {
  if (!value) return '';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '';
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};
