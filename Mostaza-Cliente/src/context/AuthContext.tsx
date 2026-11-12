import React, { createContext, useCallback, useContext, useEffect, useState } from 'react';
import { ApiService } from '@/services/api';
import { Usuario } from '@/types/cliente';

interface AuthCtx {
  usuario: Usuario | null; isAuthenticated: boolean; isLoading: boolean;
  login: (email: string, codigo: string) => Promise<{ ok: boolean; error?: string }>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
  setUsuario: (u: Usuario) => void;
}
const Ctx = createContext<AuthCtx>({} as AuthCtx);

export const AuthProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [usuario, setUsuario] = useState<Usuario | null>(null);
  const [isLoading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    if (!ApiService.hasToken()) { setUsuario(null); return; }
    const r = await ApiService.perfil();
    setUsuario(r.ok && r.usuario ? r.usuario : null);
  }, []);

  useEffect(() => {
    (async () => { await ApiService.loadToken(); await refresh(); setLoading(false); })();
  }, [refresh]);

  const login = async (email: string, codigo: string) => {
    const r = await ApiService.verificarCodigo(email, codigo);
    if (r.ok && r.token && r.usuario) { await ApiService.setToken(r.token); setUsuario(r.usuario); return { ok: true }; }
    return { ok: false, error: r.error };
  };
  const logout = async () => { await ApiService.clearToken(); setUsuario(null); };

  return <Ctx.Provider value={{ usuario, isAuthenticated: !!usuario, isLoading, login, logout, refresh, setUsuario }}>{children}</Ctx.Provider>;
};
export const useAuth = () => useContext(Ctx);
