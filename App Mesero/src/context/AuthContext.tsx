import React, { createContext, useContext, useState, useEffect } from 'react';
import { ApiService } from '@/services/api';

interface AuthContextType {
  mesero: any;
  isAuthenticated: boolean;
  isLoading: boolean;
  horaIngreso: string | null;
  loginConCodigo: (email: string, codigo: string) => Promise<{ ok: boolean; error?: string; hora_ingreso?: string; mesero?: any }>;
  logout: () => Promise<void>;
  refreshAuth: () => void;
}

const AuthContext = createContext<AuthContextType>({
  mesero: null,
  isAuthenticated: false,
  isLoading: true,
  horaIngreso: null,
  loginConCodigo: async () => ({ ok: false }),
  logout: async () => {},
  refreshAuth: () => {},
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [mesero, setMesero] = useState<any>(ApiService.getCurrentMesero());
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(ApiService.hasActiveSession());
  const [horaIngreso, setHoraIngreso] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    const m = ApiService.getCurrentMesero();
    const active = ApiService.hasActiveSession();
    setMesero(m);
    setIsAuthenticated(active);
    setIsLoading(false);
  }, []);

  const loginConCodigo = async (email: string, codigo: string) => {
    const res = await ApiService.verificarCodigoGmail(email, codigo);
    if (res.ok) {
      const active = ApiService.getCurrentMesero();
      setMesero(active);
      setIsAuthenticated(true);
      const h = res.hora_ingreso || new Date().toLocaleTimeString().slice(0, 5);
      setHoraIngreso(h);
      return { ok: true, hora_ingreso: h, mesero: active };
    }
    return { ok: false, error: res.error };
  };

  const logout = async () => {
    await ApiService.cerrarTurno();
    setMesero(null);
    setIsAuthenticated(false);
    setHoraIngreso(null);
  };

  const refreshAuth = () => {
    setMesero(ApiService.getCurrentMesero());
    setIsAuthenticated(ApiService.hasActiveSession());
  };

  return (
    <AuthContext.Provider
      value={{
        mesero,
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
