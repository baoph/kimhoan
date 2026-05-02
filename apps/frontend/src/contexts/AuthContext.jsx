import { createContext, useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'react-toastify';
import axiosClient from '../api/axiosClient';
import { authApi } from '../api/services';

export const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const navigate = useNavigate();

  useEffect(() => {
    const init = async () => {
      const token = localStorage.getItem('auth_token');
      if (!token) {
        setLoading(false);
        return;
      }
      try {
        const res = await authApi.profile();
        setUser(res.data.data);
      } catch {
        localStorage.removeItem('auth_token');
        setUser(null);
      } finally {
        setLoading(false);
      }
    };

    const interceptor = axiosClient.interceptors.response.use(
      (response) => response,
      (error) => {
        if (error.response?.status === 401) {
          localStorage.removeItem('auth_token');
          setUser(null);
          navigate('/login');
        }
        return Promise.reject(error);
      }
    );

    init();
    return () => axiosClient.interceptors.response.eject(interceptor);
  }, [navigate]);

  const login = useCallback(async (payload) => {
    const res = await authApi.login(payload);
    const { token, user: loggedUser } = res.data.data;
    localStorage.setItem('auth_token', token);
    setUser(loggedUser);
    toast.success('Đăng nhập thành công');
    return res;
  }, []);

  const logout = useCallback(async () => {
    try {
      await authApi.logout();
    } catch {
      // ignore
    }
    localStorage.removeItem('auth_token');
    setUser(null);
    toast.info('Đã đăng xuất');
    navigate('/login');
  }, [navigate]);

  const value = useMemo(() => ({ user, loading, login, logout }), [user, loading, login, logout]);

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}
