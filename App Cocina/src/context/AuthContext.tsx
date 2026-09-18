import React, { createContext, useContext, useState, useEffect } from 'react';
import { ApiService } from '@/services/api';

interface AuthContextType {
  cocinero: any;
  isAuthenticated: boolean;
  isLoading: boolean;
  horaIngreso: string | null;
  loginConCodigo: (email: string, codigo: string) => Promise<{ ok: boolean; error?: string; hora_ingreso?: string; cocinero?: any }>;
  logout: () => Promise<void>;
  refreshAuth: () => void;
}

const AuthContext = createContext<AuthContextType>({
  cocinero: null,
  isAuthenticated: false,
  isLoading: true,
  horaIngreso: null,
  loginConCodigo: async () => ({ ok: false }),
  logout: async () => {},
  refreshAuth: () => {},
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [cocinero, setCocinero] = useState<any>(ApiService.getCurrentCocinero());
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(ApiService.hasActiveSession());
  const [horaIngreso, setHoraIngreso] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    const c = ApiService.getCurrentCocinero();
    const active = ApiService.hasActiveSession();
    setCocinero(c);
    setIsAuthenticated(active);
    setIsLoading(false);
  }, []);

  const loginConCodigo = async (email: string, codigo: string) => {
    const res = await ApiService.verificarCodigoGmail(email, codigo);
    if (res.ok) {
      const active = ApiService.getCurrentCocinero();
      setCocinero(active);
      setIsAuthenticated(true);
      const h = res.hora_ingreso || new Date().toLocaleTimeString().slice(0, 5);
      setHoraIngreso(h);
      return { ok: true, hora_ingreso: h, cocinero: active };
    }
    return { ok: false, error: res.error };
  };

  const logout = async () => {
    await ApiService.cerrarTurno();
    setCocinero(null);
    setIsAuthenticated(false);
    setHoraIngreso(null);
  };

  const refreshAuth = () => {
    setCocinero(ApiService.getCurrentCocinero());
    setIsAuthenticated(ApiService.hasActiveSession());
  };

  return (
    <AuthContext.Provider
      value={{
        cocinero,
        isAuthenticated,
        isLoading,
        horaIngreso,
        loginConCodigo,
        logout,
        refreshAuth,
      }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
