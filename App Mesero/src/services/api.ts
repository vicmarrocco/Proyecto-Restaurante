import { getDefaultApiHost } from '@/constants/Config';
import { Mesa, Seccion, ProductoCatalogo, ItemComanda } from '@/types/mesero';

let currentApiUrl = getDefaultApiHost();
let currentMesero: any = {
  id: 0,
  idempleado: 0,
  nombre: '',
  apellido: '',
  nombre_completo: '',
  email: '',
  rol: 'Mesero',
  avatar: '🤵',
  idregistro_turno: 0,
  turno_activo: false
};

const SESSION_KEY = 'mostaza_mesero_session';

try {
  if (typeof window !== 'undefined' && window.localStorage) {
    const raw = window.localStorage.getItem(SESSION_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed && parsed.idempleado) {
        currentMesero = { ...parsed, turno_activo: true };
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

  getCurrentMesero() {
    return currentMesero;
  },

  setCurrentMesero(mesero: any) {
    currentMesero = { ...currentMesero, ...mesero };
    try {
      if (typeof window !== 'undefined' && window.localStorage) {
        window.localStorage.setItem(SESSION_KEY, JSON.stringify(currentMesero));
      }
    } catch (e) {}
  },

  hasActiveSession(): boolean {
    return !!(currentMesero && (currentMesero.idempleado > 0 || currentMesero.id > 0) && currentMesero.turno_activo);
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
        body: JSON.stringify({ email: email.trim(), app: 'mesero' })
      });
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error de conexión con el servidor.' };
    }
  },

  async verificarCodigoGmail(email: string, codigo: string): Promise<{ ok: boolean; mesero?: any; error?: string; hora_ingreso?: string }> {
    try {
      const url = `${currentApiUrl}/auth/verificar_codigo.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ email: email.trim(), codigo: codigo.trim(), app: 'mesero' })
      });
      const data = await res.json();
      if (data.ok && data.mesero) {
        currentMesero = {
          ...data.mesero,
          id: data.mesero.idempleado || data.mesero.id,
          idempleado: data.mesero.idempleado || data.mesero.id,
          nombre: data.mesero.nombre_completo || data.mesero.nombre,
          turno_activo: true
        };
        try {
          if (typeof window !== 'undefined' && window.localStorage) {
            window.localStorage.setItem(SESSION_KEY, JSON.stringify(currentMesero));
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
          idempleado: currentMesero.idempleado || currentMesero.id,
          idregistro_turno: idregistro || currentMesero.idregistro_turno,
          observaciones: `Cierre de turno desde App Mesero (${currentMesero.nombre})`
        })
      });
      currentMesero.turno_activo = false;
      try {
        if (typeof window !== 'undefined' && window.localStorage) {
          window.localStorage.removeItem(SESSION_KEY);
        }
      } catch (e) {}
      return await res.json();
    } catch (e: any) {
      currentMesero.turno_activo = false;
      return { ok: false, mensaje: e.message };
    }
  },

  async getListaMeseros(): Promise<{ ok: boolean; empleados: any[]; error?: string }> {
    try {
      const url = `${currentApiUrl}/empleados/listar.php?app=mesero`;
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
  // MESAS Y COMANDAS
  // ------------------------------------------------------------
  async getMesas(): Promise<{ ok: boolean; mesas: Mesa[]; secciones: Seccion[]; total_libres: number; total_ocupadas: number; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/mesas.php`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, mesas: [], secciones: [], total_libres: 0, total_ocupadas: 0, error: e.message };
    }
  },

  async ocuparMesa(numeroMesa: number): Promise<{ ok: boolean; mensaje?: string; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/mesas.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ accion: 'ocupar', numero_mesa: numeroMesa })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al ocupar mesa' };
    }
  },

  async desocuparMesa(numeroMesa: number): Promise<{ ok: boolean; mensaje?: string; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/mesas.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ accion: 'desocupar', numero_mesa: numeroMesa })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al desocupar mesa' };
    }
  },

  async getCatalogo(): Promise<{ ok: boolean; productos: ProductoCatalogo[]; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/catalogo.php`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();
      let productos: ProductoCatalogo[] = [];
      if (Array.isArray(data.productos)) {
        productos = data.productos;
      } else if (data.productos_por_categoria && typeof data.productos_por_categoria === 'object') {
        Object.values(data.productos_por_categoria).forEach((list: any) => {
          if (Array.isArray(list)) {
            productos.push(...list);
          }
        });
      }
      return { ok: true, productos };
    } catch (e: any) {
      return { ok: false, productos: [], error: e.message };
    }
  },

  async enviarComanda(
    numeroMesa: number,
    items: ItemComanda[],
    datosPago: {
      metodo_pago: string;
      paga_con?: number;
      vuelto?: number;
      comprobante?: string;
    } = { metodo_pago: 'efectivo' }
  ): Promise<{ ok: boolean; idpedido?: number; factura?: any; error?: string }> {
    try {
      const subtotal = items.reduce((acc, it) => acc + it.precio * it.cantidad, 0);
      const url = `${currentApiUrl}/pos/crear_venta.php`;
      const payload = {
        tipo_entrega: 'salon',
        numero_mesa: numeroMesa,
        metodo_pago: datosPago.metodo_pago || 'efectivo',
        paga_con: datosPago.paga_con || subtotal,
        vuelto: datosPago.vuelto || 0,
        comprobante: datosPago.comprobante || '',
        subtotal: subtotal,
        total: subtotal,
        idempleado_mesero: currentMesero.idempleado || currentMesero.id || 17,
        idempleado: currentMesero.idempleado || currentMesero.id || 17,
        mesero_nombre: currentMesero.nombre_completo || currentMesero.nombre || 'Mesero Salón',
        items: items.map(it => ({
          idproducto: it.idproducto,
          descripcion: it.nombre,
          modificadores: it.modificadores || '',
          precio: it.precio,
          cantidad: it.cantidad,
          subtotal: it.precio * it.cantidad
        })),
        observaciones: `Comanda y cobro en Mesa #${numeroMesa} por ${currentMesero.nombre_completo || currentMesero.nombre}`
      };

      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al enviar comanda' };
    }
  },

  async getPedidosSalon(tipoFiltro: 'todos' | 'online' | 'presencial' = 'todos'): Promise<{ ok: boolean; pedidos: any[]; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/pedidos.php?fecha=hoy&tipo_entrega=salon`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();
      let peds: any[] = data.pedidos || [];
      if (tipoFiltro === 'online') {
        peds = peds.filter(p => p.es_online);
      } else if (tipoFiltro === 'presencial') {
        peds = peds.filter(p => !p.es_online);
      }
      return { ok: true, pedidos: peds };
    } catch (e: any) {
      return { ok: false, pedidos: [], error: e.message };
    }
  },

  async confirmarEntregaEnMesa(idpedido: number): Promise<{ ok: boolean; error?: string; mensaje?: string }> {
    try {
      const url = `${currentApiUrl}/pos/pedidos.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          accion: 'cambiar_estado',
          idpedido,
          nuevo_estado: 'Entregado',
          idempleado: currentMesero.idempleado || currentMesero.id || 17,
          mesero_nombre: currentMesero.nombre_completo || currentMesero.nombre || 'Mesero Salón',
          rol: 'Mesero',
          detalles: `Comanda servida en mesa por ${currentMesero.nombre_completo || currentMesero.nombre}`
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },

  async getHistorialMesero(): Promise<{ ok: boolean; atenciones: any[]; error?: string }> {
    try {
      const mesId = currentMesero.idempleado || currentMesero.id || 17;
      const url = `${currentApiUrl}/pos/pedidos.php?fecha=hoy&idempleado_mesero=${mesId}&tipo_entrega=salon`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = await res.json();
      return { ok: true, atenciones: data.pedidos || [] };
    } catch (e: any) {
      return { ok: false, atenciones: [], error: e.message };
    }
  },

  async getResumenTurno(): Promise<{ ok: boolean; resumen?: any; error?: string }> {
    try {
      const mesId = currentMesero.idempleado || currentMesero.id || 17;
      const regId = currentMesero.idregistro_turno || 0;
      const url = `${currentApiUrl}/empleados/resumen_turno.php?idempleado=${mesId}&idregistro_turno=${regId}`;
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
  }
};
