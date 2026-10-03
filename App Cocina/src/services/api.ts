import { getDefaultApiHost } from '@/constants/Config';
import { ComandaCocina, Receta, StockInsumo } from '@/types/cocina';

let currentApiUrl = getDefaultApiHost();
let currentCocinero: any = {
  id: 0,
  idempleado: 0,
  nombre: '',
  apellido: '',
  nombre_completo: '',
  email: '',
  rol: 'Cocinero',
  avatar: '👨‍🍳',
  idregistro_turno: 0,
  turno_activo: false
};

const SESSION_KEY = 'mostaza_cocina_session';

// Cargar sesión guardada si existe (Web o AsyncStorage)
try {
  if (typeof window !== 'undefined' && window.localStorage) {
    const raw = window.localStorage.getItem(SESSION_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed && parsed.idempleado) {
        currentCocinero = { ...parsed, turno_activo: true };
      }
    }
  }
} catch (e) {}

const fetchWithTimeout = async (url: string, options: RequestInit = {}, timeoutMs = 7000): Promise<Response> => {
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

  getCurrentCocinero() {
    return currentCocinero;
  },

  setCurrentCocinero(cocinero: any) {
    currentCocinero = { ...currentCocinero, ...cocinero };
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        window.localStorage.setItem(SESSION_KEY, JSON.stringify(currentCocinero));
      }
    } catch (e) {}
  },

  hasActiveSession(): boolean {
    return !!(currentCocinero && (currentCocinero.idempleado > 0 || currentCocinero.id > 0) && currentCocinero.turno_activo);
  },

  // ------------------------------------------------------------
  // AUTENTICACIÓN POR GMAIL OTP Y CONTROL DE TURNOS
  // ------------------------------------------------------------
  async solicitarCodigoGmail(email: string): Promise<{ ok: boolean; mensaje?: string; debug_code?: string; error?: string }> {
    try {
      const url = `${currentApiUrl}/auth/solicitar_codigo.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email: email.trim(), app: 'cocina' })
      });
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error de conexión con el servidor.' };
    }
  },

  async verificarCodigoGmail(email: string, codigo: string): Promise<{ ok: boolean; cocinero?: any; error?: string; hora_ingreso?: string }> {
    try {
      const url = `${currentApiUrl}/auth/verificar_codigo.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email: email.trim(), codigo: codigo.trim(), app: 'cocina' })
      });
      const data = await res.json();
      if (data.ok && data.cocinero) {
        currentCocinero = {
          ...data.cocinero,
          id: data.cocinero.idempleado || data.cocinero.id,
          idempleado: data.cocinero.idempleado || data.cocinero.id,
          nombre: data.cocinero.nombre_completo || data.cocinero.nombre,
          turno_activo: true
        };
        try {
          if (typeof window !== 'undefined' && window.localStorage) {
            window.localStorage.setItem(SESSION_KEY, JSON.stringify(currentCocinero));
          }
        } catch (e) {}
      }
      return data;
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al validar el código.' };
    }
  },

  async cerrarTurno(idregistro = 0): Promise<{ ok: boolean; mensaje?: string }> {
    try {
      const url = `${currentApiUrl}/auth/cerrar_turno.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          idempleado: currentCocinero.idempleado || currentCocinero.id,
          idregistro_turno: idregistro || currentCocinero.idregistro_turno,
          observaciones: `Cierre de turno desde Cocina KDS (${currentCocinero.nombre})`
        })
      });
      currentCocinero.turno_activo = false;
      try {
        if (typeof window !== 'undefined' && window.localStorage) {
          window.localStorage.removeItem(SESSION_KEY);
        }
      } catch (e) {}
      return await res.json();
    } catch (e: any) {
      currentCocinero.turno_activo = false;
      return { ok: false, mensaje: e.message };
    }
  },

  async getListaCocineros(): Promise<{ ok: boolean; empleados: any[]; error?: string }> {
    try {
      const url = `${currentApiUrl}/empleados/listar.php?app=cocina`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, empleados: [], error: e.message };
    }
  },

  // ------------------------------------------------------------
  // COMANDAS Y AUDITORÍA DE PREPARACIÓN
  // ------------------------------------------------------------
  async getComandas(): Promise<{ ok: boolean; comandas: ComandaCocina[]; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/pedidos.php?fecha=hoy`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();
      
      const peds = (data.pedidos || []).filter((p: any) => 
        p.estado === 'Recibido' || p.estado === 'En Cocina' || p.estado === 'En Preparación' || p.estado === 'En Preparacion'
      ).map((p: any) => {
        const tiempoMinutos = Math.max(0, Math.round((Date.now() - new Date(p.fecha).getTime()) / 60000)) || 0;
        return {
          idpedido: p.idpedido,
          tipo_entrega: p.tipo_entrega || 'salon',
          numero_mesa: p.numero_mesa,
          numero_caja: p.numero_caja || 1,
          cajero_nombre: p.cajero_nombre,
          idempleado_cocinero: p.idempleado_cocinero ? parseInt(p.idempleado_cocinero) : undefined,
          cocinero_nombre: p.cocinero_nombre || '',
          duracion_cocina_minutos: p.duracion_cocina_minutos || 0,
          direccion_envio: p.direccion_envio,
          fecha: p.fecha,
          hora: p.hora || 'Ahora',
          minutos_espera: tiempoMinutos,
          estado: p.estado,
          sucursal: p.sucursal || 'Mostaza Formosa Centro',
          items: (p.items || []).map((it: any) => ({
            iddetalle: it.iddetalle || 0,
            idproducto: it.idproducto || 0,
            descripcion: it.descripcion || it.nombre || 'Plato',
            modificadores: it.modificadores || '',
            cantidad: it.cantidad || 1
          }))
        };
      });

      return { ok: true, comandas: peds };
    } catch (e: any) {
      return { ok: false, comandas: [], error: e.message };
    }
  },

  async tomarComanda(idpedido: number): Promise<{ ok: boolean; error?: string; mensaje?: string }> {
    try {
      const url = `${currentApiUrl}/pos/pedidos.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          accion: 'cambiar_estado',
          idpedido,
          nuevo_estado: 'En Preparación',
          idempleado: currentCocinero.idempleado || currentCocinero.id,
          cocinero_nombre: currentCocinero.nombre_completo || currentCocinero.nombre || 'Cocinero KDS',
          rol: 'Cocinero',
          detalles: `Comanda tomada para elaboración por ${currentCocinero.nombre_completo || currentCocinero.nombre}`
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },

  async getHistorialCocinero(): Promise<{ ok: boolean; comandas: any[]; error?: string }> {
    try {
      const cocId = currentCocinero.idempleado || currentCocinero.id;
      const url = `${currentApiUrl}/pos/pedidos.php?fecha=hoy&idempleado_cocinero=${cocId}`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();
      return { ok: true, comandas: data.pedidos || [] };
    } catch (e: any) {
      return { ok: false, comandas: [], error: e.message };
    }
  },

  async getResumenTurno(): Promise<{ ok: boolean; resumen?: any; error?: string }> {
    try {
      const cocId = currentCocinero.idempleado || currentCocinero.id;
      const regId = currentCocinero.idregistro_turno || 0;
      const url = `${currentApiUrl}/empleados/resumen_turno.php?idempleado=${cocId}&idregistro_turno=${regId}`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();
      return { ok: true, resumen: data };
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },

  async marcarCompletado(idpedido: number): Promise<{ ok: boolean; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/pedidos.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          accion: 'cambiar_estado',
          idpedido,
          nuevo_estado: 'Listo para Despacho',
          idempleado: currentCocinero.idempleado || currentCocinero.id,
          cocinero_nombre: currentCocinero.nombre_completo || currentCocinero.nombre || 'Cocinero KDS',
          rol: 'Cocinero',
          detalles: `Comanda elaborada y empaquetada por ${currentCocinero.nombre_completo || currentCocinero.nombre}`
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },

  async cancelarComanda(idpedido: number, motivo: string): Promise<{ ok: boolean; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/pedidos.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          accion: 'cancelar_pedido',
          idpedido,
          motivo_cancelacion: motivo,
          idempleado: currentCocinero.idempleado || currentCocinero.id,
          cancelado_por: `Cocina (${currentCocinero.nombre_completo || currentCocinero.nombre})`,
          rol: 'Cocinero'
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },

  // ------------------------------------------------------------
  // INSUMOS Y RECETAS
  // ------------------------------------------------------------
  async getStockInsumos(): Promise<{ ok: boolean; insumos: StockInsumo[]; total?: number; alertas_criticas?: number; error?: string }> {
    try {
      const url = `${currentApiUrl}/cocina/insumos.php`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, insumos: [], error: e.message };
    }
  },

  async solicitarInsumo(params: {
    nombre_insumo: string;
    cantidad: number;
    unidad_medida: string;
    prioridad: string;
    motivo: string;
  }): Promise<{ ok: boolean; mensaje?: string; error?: string }> {
    try {
      const url = `${currentApiUrl}/cocina/insumos.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          ...params,
          solicitante: currentCocinero.nombre_completo || currentCocinero.nombre
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },

  async getRecetas(busqueda = ''): Promise<{ ok: boolean; recetas: Receta[]; error?: string }> {
    try {
      const q = busqueda ? `?q=${encodeURIComponent(busqueda)}` : '';
      const url = `${currentApiUrl}/cocina/recetas.php${q}`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, recetas: [], error: e.message };
    }
  }
};
