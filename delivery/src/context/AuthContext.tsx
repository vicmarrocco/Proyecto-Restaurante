// ============================================================
// context/AuthContext.tsx
// Contexto de Autenticación Oficial para Repartidores Mostaza
// ============================================================
import React, { createContext, useContext, useState, useEffect } from 'react';
import { Repartidor } from '@/types/delivery';
import { ApiService } from '@/services/api';

interface AuthContextType {
  rider: Repartidor | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  horaIngreso: string | null;
  login: (riderId: number, pin: string) => Promise<{ ok: boolean; error?: string; hora_ingreso?: string }>;
  loginConCodigo: (email: string, codigo: string) => Promise<{ ok: boolean; error?: string; hora_ingreso?: string; repartidor?: Repartidor }>;
  logout: () => Promise<void>;
  refreshAuth: () => void;
}

const AuthContext = createContext<AuthContextType>({
  rider: null,
  isAuthenticated: false,
  isLoading: true,
  horaIngreso: null,
  login: async () => ({ ok: false }),
  loginConCodigo: async () => ({ ok: false }),
  logout: async () => {},
  refreshAuth: () => {},
});

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [rider, setRider] = useState<Repartidor | null>(ApiService.getCurrentRider());
  const [isAuthenticated, setIsAuthenticated] = useState<boolean>(ApiService.hasActiveSession());
  const [horaIngreso, setHoraIngreso] = useState<string | null>(ApiService.getSession().hora_ingreso);
  const [isLoading, setIsLoading] = useState<boolean>(true);

  useEffect(() => {
    const currentR = ApiService.getCurrentRider();
    const hasSession = ApiService.hasActiveSession();
    setRider(currentR);
    setIsAuthenticated(hasSession);
    setHoraIngreso(ApiService.getSession().hora_ingreso);
    setIsLoading(false);
  }, []);

  const login = async (riderId: number, pin: string) => {
    const res = await ApiService.loginRider(riderId, pin);
    if (res.ok) {
      const active = ApiService.getCurrentRider();
      setRider(active);
      setIsAuthenticated(true);
      setHoraIngreso(res.hora_ingreso || 'Ahora');
      return { ok: true, hora_ingreso: res.hora_ingreso };
    }
    return { ok: false, error: res.error };
  };

  const loginConCodigo = async (email: string, codigo: string) => {
    const res = await ApiService.verificarCodigoGmail(email, codigo);
    if (res.ok) {
      const active = ApiService.getCurrentRider();
      setRider(active);
      setIsAuthenticated(true);
      setHoraIngreso(res.hora_ingreso || 'Ahora');
      return { ok: true, hora_ingreso: res.hora_ingreso, repartidor: active || undefined };
    }
    return { ok: false, error: res.error };
  };

  const logout = async () => {
    await ApiService.cerrarTurno();
    setRider(null);
    setIsAuthenticated(false);
    setHoraIngreso(null);
  };

  const refreshAuth = () => {
    setRider(ApiService.getCurrentRider());
    setIsAuthenticated(ApiService.hasActiveSession());
    setHoraIngreso(ApiService.getSession().hora_ingreso);
  };

  return (
    <AuthContext.Provider
      value={{
        rider,
        isAuthenticated,
        isLoading,
        horaIngreso,
        login,
        loginConCodigo,
        logout,
        refreshAuth,
      }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
