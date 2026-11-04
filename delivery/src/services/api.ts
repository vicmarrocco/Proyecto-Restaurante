// ============================================================
// services/api.ts
// Cliente de API REST para la aplicación corporativa de Delivery Mostaza
// ============================================================
import { Config, getDefaultApiHost } from '@/constants/Config';
import { PedidoDelivery, EstadisticasRider, Repartidor, EstadoPedido } from '@/types/delivery';

let currentApiUrl = getDefaultApiHost();

// Por defecto el repartidor es null hasta que inicie sesión
let currentRider: Repartidor | null = null;

let currentSession: {
  token: string | null;
  idregistro: number | null;
  hora_ingreso: string | null;
  activo: boolean;
} = {
  token: null,
  idregistro: null,
  hora_ingreso: null,
  activo: false,
};

// Restaurar sesión previa si está activa en entorno web
try {
  if (typeof window !== 'undefined' && window.localStorage) {
    const savedSession = window.localStorage.getItem('mostaza_session');
    const savedRider = window.localStorage.getItem('mostaza_rider');
    if (savedSession && savedRider) {
      const parsedSession = JSON.parse(savedSession);
      if (parsedSession && parsedSession.activo && parsedSession.token) {
        currentSession = parsedSession;
        currentRider = JSON.parse(savedRider);
      }
    }
  }
} catch {
  // Ignorar en entornos sin localStorage
}

const fetchWithTimeout = async (url: string, options: RequestInit = {}, timeoutMs = 6000): Promise<Response> => {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), timeoutMs);
  try {
    const res = await fetch(url, {
      ...options,
      signal: controller.signal,
    });
    return res;
  } finally {
    clearTimeout(timeout);
  }
};

export const ApiService = {
  getBaseUrl(): string {
    return currentApiUrl;
  },

  setBaseUrl(url: string) {
    currentApiUrl = url.trim().replace(/\/+$/, '');
  },

  getCurrentRider(): Repartidor | null {
    return currentRider;
  },

  setCurrentRider(rider: Repartidor | null) {
    currentRider = rider ? { ...(currentRider || {}), ...rider } as Repartidor : null;
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        if (currentRider) {
          window.localStorage.setItem('mostaza_rider', JSON.stringify(currentRider));
        } else {
          window.localStorage.removeItem('mostaza_rider');
        }
      }
    } catch {}
  },

  getSession() {
    return currentSession;
  },

  hasActiveSession(): boolean {
    return Boolean(currentSession.activo && currentSession.token && currentRider);
  },

  async solicitarCodigoGmail(email: string): Promise<{ ok: boolean; mensaje?: string; debug_code?: string; error?: string }> {
    try {
      const authBase = currentApiUrl.replace(/\/delivery\/?$/, '/auth');
      const url = `${authBase}/solicitar_codigo.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ email, app: 'delivery' }),
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al conectar con el servidor de Mostaza' };
    }
  },

  async verificarCodigoGmail(email: string, codigo: string): Promise<{
    ok: boolean;
    token?: string;
    idregistro?: number;
    hora_ingreso?: string;
    error?: string;
    repartidor?: Repartidor;
    empleado?: any;
  }> {
    try {
      const authBase = currentApiUrl.replace(/\/delivery\/?$/, '/auth');
      const url = `${authBase}/verificar_codigo.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ email, codigo, app: 'delivery' }),
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();

      if (data.ok && (data.repartidor || data.empleado)) {
        const rData = data.repartidor || data.empleado;
        currentRider = {
          id: rData.id || rData.idempleado,
          nombre: rData.nombre_completo || `${rData.nombre} ${rData.apellido}`,
          email: rData.email,
          telefono: rData.telefono || '3704-551122',
          vehiculo: rData.vehiculo || 'Moto de Reparto',
          patente: rData.patente || 'MOTO-01',
          avatar: rData.avatar || '🛵',
          sucursal_base: rData.sucursal_base || 'Mostaza Formosa Centro',
          calificacion: rData.calificacion || 4.95,
          viajes_totales: rData.viajes_totales || 0,
          estado_servicio: 'Disponible',
          estado: 'Disponible',
        };

        currentSession = {
          token: data.token || `tok_del_${currentRider.id}`,
          idregistro: rData.idregistro_turno || data.idregistro || 1,
          hora_ingreso: rData.hora_entrada || 'Ahora',
          activo: true,
        };

        try {
          if (typeof window !== 'undefined' && window.localStorage) {
            window.localStorage.setItem('mostaza_rider', JSON.stringify(currentRider));
            window.localStorage.setItem('mostaza_session', JSON.stringify(currentSession));
          }
        } catch {}
      }

      return data;
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al validar código con el servidor' };
    }
  },

  async loginRider(
    riderOrId: Repartidor | number,
    pin?: string
  ): Promise<{
    ok: boolean;
    token?: string;
    idregistro?: number;
    hora_ingreso?: string;
    error?: string;
    repartidor?: Repartidor;
  }> {
    try {
      const riderId = typeof riderOrId === 'number' ? riderOrId : riderOrId.id;
      const nombre = typeof riderOrId === 'object' ? riderOrId.nombre : '';

      const url = `${currentApiUrl}/auth.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          accion: 'login',
          repartidor_id: riderId,
          nombre,
          pin: pin || '',
        }),
      });

      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();

      if (data.ok) {
        if (data.repartidor) {
          currentRider = { ...data.repartidor, estado: 'Disponible', estado_servicio: 'Disponible' };
        } else if (typeof riderOrId === 'object') {
          currentRider = { ...riderOrId, estado: 'Disponible', estado_servicio: 'Disponible' };
        }

        currentSession = {
          token: data.token,
          idregistro: data.idregistro,
          hora_ingreso: data.hora_ingreso,
          activo: true,
        };

        try {
          if (typeof window !== 'undefined' && window.localStorage) {
            window.localStorage.setItem('mostaza_rider', JSON.stringify(currentRider));
            window.localStorage.setItem('mostaza_session', JSON.stringify(currentSession));
          }
        } catch {}
      }

      return data;
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error de autenticación con el servidor de Mostaza' };
    }
  },

  async cerrarTurno(): Promise<{ ok: boolean; mensaje?: string; error?: string }> {
    try {
      const riderId = currentRider ? currentRider.id : 0;
      const riderNombre = currentRider ? currentRider.nombre : '';

      const url = `${currentApiUrl}/auth.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          accion: 'cerrar_turno',
          idregistro: currentSession.idregistro,
          repartidor_id: riderId,
          nombre: riderNombre,
        }),
      });

      currentSession = {
        token: null,
        idregistro: null,
        hora_ingreso: null,
        activo: false,
      };
      currentRider = null;

      try {
        if (typeof window !== 'undefined' && window.localStorage) {
          window.localStorage.removeItem('mostaza_session');
          window.localStorage.removeItem('mostaza_rider');
        }
      } catch {}

      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch {
      currentSession = {
        token: null,
        idregistro: null,
        hora_ingreso: null,
        activo: false,
      };
      currentRider = null;
      try {
        if (typeof window !== 'undefined' && window.localStorage) {
          window.localStorage.removeItem('mostaza_session');
          window.localStorage.removeItem('mostaza_rider');
        }
      } catch {}
      return { ok: true, mensaje: 'Sesión cerrada exitosamente.' };
    }
  },

  async logout(): Promise<void> {
    await this.cerrarTurno();
  },

  async getPedidos(
    filtro: 'pendientes' | 'en_camino' | 'entregados' | 'todos' = 'todos'
  ): Promise<{ ok: boolean; pedidos: PedidoDelivery[]; error?: string }> {
    try {
      const riderNombre = currentRider ? currentRider.nombre : '';
      const url = `${currentApiUrl}/pedidos.php?filtro=${filtro}&repartidor=${encodeURIComponent(riderNombre)}`;
      const res = await fetchWithTimeout(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });
      if (!res.ok) {
        throw new Error(`HTTP Error ${res.status}`);
      }
      return await res.json();
    } catch {
      return {
        ok: false,
        pedidos: [],
        error: `No se pudo conectar con el servidor central de Mostaza (${currentApiUrl}). Verificá que el servidor esté activo.`,
      };
    }
  },

  async getHistorial(
    rango: 'hoy' | 'semana' | 'todo' = 'hoy',
    metodoPago?: 'todos' | 'efectivo' | 'online'
  ): Promise<{ ok: boolean; pedidos: PedidoDelivery[]; error?: string }> {
    try {
      const riderNombre = currentRider ? currentRider.nombre : '';
      let url = `${currentApiUrl}/pedidos.php?filtro=historial&rango=${rango}&repartidor=${encodeURIComponent(riderNombre)}`;
      if (metodoPago && metodoPago !== 'todos') {
        url += `&metodo_pago=${metodoPago}`;
      }
      const res = await fetchWithTimeout(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, pedidos: [], error: e.message || 'Error al cargar historial' };
    }
  },

  async actualizarEstado(
    idpedido: number,
    nuevoEstado: EstadoPedido | 'Cancelado',
    motivoCancelacion?: string
  ): Promise<{ ok: boolean; mensaje?: string; error?: string }> {
    try {
      const url = `${currentApiUrl}/actualizar_estado.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
        },
        body: JSON.stringify({
          idpedido,
          nuevo_estado: nuevoEstado,
          repartidor: currentRider ? currentRider.nombre : 'Repartidor Mostaza',
          idempleado: currentRider ? currentRider.id : null,
          motivo_cancelacion: motivoCancelacion || '',
        }),
      });
      if (!res.ok) {
        throw new Error(`HTTP Error ${res.status}`);
      }
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al actualizar estado del pedido' };
    }
  },

  async getEstadisticas(): Promise<EstadisticasRider | null> {
    try {
      const riderNombre = currentRider ? currentRider.nombre : '';
      const url = `${currentApiUrl}/estadisticas.php?repartidor=${encodeURIComponent(riderNombre)}`;
      const res = await fetchWithTimeout(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });
      if (!res.ok) return null;
      return await res.json();
    } catch {
      return null;
    }
  },

  async getRepartidores(): Promise<Repartidor[]> {
    try {
      const url = `${currentApiUrl}/repartidores.php`;
      const res = await fetchWithTimeout(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });
      if (!res.ok) throw new Error();
      const data = await res.json();
      if (Array.isArray(data.repartidores) && data.repartidores.length > 0) {
        return data.repartidores;
      }
      throw new Error();
    } catch {
      try {
        const urlAuth = `${currentApiUrl}/auth.php?accion=lista_repartidores`;
        const resAuth = await fetchWithTimeout(urlAuth, {
          headers: { Accept: 'application/json' },
          cache: 'no-store',
        });
        if (!resAuth.ok) return [];
        const dataAuth = await resAuth.json();
        return dataAuth.flota || [];
      } catch {
        return [];
      }
    }
  },

  async getResumenTurno(): Promise<{ ok: boolean; resumen?: any; error?: string }> {
    try {
      const riderId = currentRider?.id || 0;
      const regId = currentSession?.idregistro || 0;
      const empBase = currentApiUrl.replace(/\/delivery\/?$/, '/empleados');
      const url = `${empBase}/resumen_turno.php?idempleado=${riderId}&idregistro_turno=${regId}`;
      const res = await fetchWithTimeout(url, {
        headers: { Accept: 'application/json' },
        cache: 'no-store',
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();
      return { ok: true, resumen: data };
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },
};

