import { getDefaultApiHost } from '@/constants/Config';
import { Mesa, Seccion, ProductoCatalogo, ItemComanda } from '@/types/mesero';

const MAPA_TILDES: Record<string, string> = {
  '├í': 'á', '├®': 'é', '├¡': 'í', '├│': 'ó', '├║': 'ú',
  '├▒': 'ñ', '├Ñ': 'Ñ', '├ü': 'Á', '├ë': 'É', '├ì': 'Í',
  '├ô': 'Ó', '├Ü': 'Ú', '├╝': 'ü', '├Ö': 'Ü',
  '┬░': '°', '┬¡': '¡', '┬¿': '¿', '┬║': 'º', '┬¬': 'ª',
  'Ã¡': 'á', 'Ã©': 'é', 'Ã­': 'í', 'Ã³': 'ó', 'Ãº': 'ú',
  'Ã±': 'ñ', 'Ã‘': 'Ñ', 'Ã“': 'Ó', 'Ã‰': 'É', 'Ãš': 'Ú',
  'Ã¼': 'ü', 'Â°': '°', 'Â¡': '¡', 'Â¿': '¿'
};

export function limpiarTildes<T>(val: T): T {
  if (typeof val === 'string') {
    let s = val;
    for (const [bad, good] of Object.entries(MAPA_TILDES)) {
      if (s.includes(bad)) {
        s = s.split(bad).join(good);
      }
    }
    return s as unknown as T;
  }
  if (Array.isArray(val)) {
    return val.map((item) => limpiarTildes(item)) as unknown as T;
  }
  if (val && typeof val === 'object') {
    const out: Record<string, any> = {};
    for (const [k, v] of Object.entries(val)) {
      out[k] = limpiarTildes(v);
    }
    return out as T;
  }
  return val;
}

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
      const parsed = limpiarTildes(JSON.parse(raw));
      if (parsed && parsed.idempleado) {
        currentMesero = { ...parsed, turno_activo: true };
      }
    }
  }
} catch (e) {}

const fetchWithTimeout = async (url: string, options: RequestInit = {}, timeoutMs = 15000): Promise<Response> => {
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
    return limpiarTildes(currentMesero);
  },

  setCurrentMesero(mesero: any) {
    currentMesero = limpiarTildes({ ...currentMesero, ...mesero });
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
      }, 22000);
      const data = limpiarTildes(await res.json());
      return data;
    } catch (e: any) {
      const msg = e?.name === 'AbortError' ? 'El servidor tardó demasiado en responder. Verificá que Apache y MySQL estén encendidos en XAMPP.' : (e.message || 'Error de conexión con el servidor.');
      return { ok: false, error: msg };
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
      const data = limpiarTildes(await res.json());
      if (data.ok && data.mesero) {
        currentMesero = limpiarTildes({
          ...data.mesero,
          id: data.mesero.idempleado || data.mesero.id,
          idempleado: data.mesero.idempleado || data.mesero.id,
          nombre: data.mesero.nombre_completo || data.mesero.nombre,
          turno_activo: true
        });
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
      return limpiarTildes(await res.json());
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
      return limpiarTildes(await res.json());
    } catch (e: any) {
      return { ok: false, empleados: [], error: e.message };
    }
  },

  // ------------------------------------------------------------
  // MESAS, UBICACIÓN DE CLIENTES Y COMANDAS
  // ------------------------------------------------------------
  async getMesas(): Promise<{ ok: boolean; mesas: Mesa[]; secciones: Seccion[]; total_libres: number; total_ocupadas: number; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/mesas.php`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return limpiarTildes(await res.json());
    } catch (e: any) {
      return { ok: false, mesas: [], secciones: [], total_libres: 0, total_ocupadas: 0, error: e.message };
    }
  },

  async ocuparMesa(
    numeroMesa: number,
    clienteNombre?: string,
    comensales?: number
  ): Promise<{ ok: boolean; mensaje?: string; error?: string; cliente_nombre?: string; comensales?: number }> {
    try {
      const url = `${currentApiUrl}/pos/mesas.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          accion: 'ocupar',
          numero_mesa: numeroMesa,
          idempleado_mesero: currentMesero.idempleado || currentMesero.id || 17,
          mesero_nombre: currentMesero.nombre_completo || currentMesero.nombre || 'Mesero Salón',
          fk_id_seccion: currentMesero.fk_id_seccion || currentMesero.idseccion || 1,
          secciones_asignadas: currentMesero.secciones_asignadas || [],
          cliente_nombre: clienteNombre ? clienteNombre.trim() : '',
          comensales: comensales || 0
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return limpiarTildes(await res.json());
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
        body: JSON.stringify({
          accion: 'desocupar',
          numero_mesa: numeroMesa,
          idempleado_mesero: currentMesero.idempleado || currentMesero.id || 17,
          mesero_nombre: currentMesero.nombre_completo || currentMesero.nombre || 'Mesero Salón',
          fk_id_seccion: currentMesero.fk_id_seccion || currentMesero.idseccion || 1,
          secciones_asignadas: currentMesero.secciones_asignadas || []
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return limpiarTildes(await res.json());
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
      const data = limpiarTildes(await res.json());
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
    } = { metodo_pago: 'efectivo' },
    clienteNombre?: string
  ): Promise<{ ok: boolean; idpedido?: number; factura?: any; error?: string }> {
    try {
      const subtotal = items.reduce((acc, it) => acc + it.precio * it.cantidad, 0);
      const url = `${currentApiUrl}/pos/crear_venta.php`;
      const nombreCli = clienteNombre && clienteNombre.trim() ? clienteNombre.trim() : `Mesa #${numeroMesa} (Salón)`;
      const payload = {
        tipo_entrega: 'salon',
        numero_mesa: numeroMesa,
        nombre_cliente: nombreCli,
        metodo_pago: datosPago.metodo_pago || 'efectivo',
        paga_con: datosPago.paga_con || subtotal,
        vuelto: datosPago.vuelto || 0,
        comprobante: datosPago.comprobante || '',
        subtotal: subtotal,
        total: subtotal,
        idempleado_mesero: currentMesero.idempleado || currentMesero.id || 17,
        idempleado: currentMesero.idempleado || currentMesero.id || 17,
        mesero_nombre: currentMesero.nombre_completo || currentMesero.nombre || 'Mesero Salón',
        fk_id_seccion: currentMesero.fk_id_seccion || currentMesero.idseccion || 1,
        secciones_asignadas: currentMesero.secciones_asignadas || [],
        items: items.map(it => ({
          idproducto: it.idproducto,
          descripcion: it.nombre,
          modificadores: it.modificadores || '',
          precio: it.precio,
          cantidad: it.cantidad,
          subtotal: it.precio * it.cantidad
        })),
        observaciones: `Comanda en Mesa #${numeroMesa} (${nombreCli}) por ${currentMesero.nombre_completo || currentMesero.nombre}`
      };

      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return limpiarTildes(await res.json());
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al enviar comanda' };
    }
  },

  async getPedidosSalon(tipoFiltro: 'todos' | 'online' | 'presencial' = 'todos', idseccion?: number | string | number[]): Promise<{ ok: boolean; pedidos: any[]; error?: string }> {
    try {
      let url = `${currentApiUrl}/pos/pedidos.php?fecha=hoy&tipo_entrega=salon`;
      if (idseccion) {
        const secParam = Array.isArray(idseccion) ? idseccion.join(',') : String(idseccion);
        url += `&idseccion=${secParam}`;
      }
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      const data = limpiarTildes(await res.json());
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
          fk_id_seccion: currentMesero.fk_id_seccion || currentMesero.idseccion || 1,
          detalles: `Comanda servida en mesa por ${currentMesero.nombre_completo || currentMesero.nombre}`
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return limpiarTildes(await res.json());
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
      const data = limpiarTildes(await res.json());
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
      const data = limpiarTildes(await res.json());
      return { ok: true, resumen: data };
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  },

  async crearPreferenciaMP(
    items: ItemComanda[],
    total: number,
    numeroMesa: number
  ): Promise<{
    ok: boolean;
    modo_real?: boolean;
    preference_id?: string;
    init_point?: string;
    external_reference?: string;
    qr_checkout_url?: string;
    qr_emvco_url?: string;
    alias?: string;
    cvu?: string;
    titular?: string;
    error?: string;
  }> {
    try {
      const url = `${currentApiUrl}/pagos/crear_preferencia_pos.php`;
      const res = await fetchWithTimeout(
        url,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({
            origen: 'MESERO',
            numero_mesa: numeroMesa,
            total,
            items: items.map((it) => ({
              idproducto: it.idproducto,
              nombre: it.nombre,
              precio: it.precio,
              cantidad: it.cantidad
            }))
          })
        },
        12000
      );
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return limpiarTildes(await res.json());
    } catch (e: any) {
      return { ok: false, error: e.message || 'Error al generar orden Mercado Pago' };
    }
  },

  async verificarPagoMP(
    externalRef: string,
    montoEsperado: number,
    paymentId: string = ''
  ): Promise<{
    ok: boolean;
    pagado?: boolean;
    payment_id?: string;
    estado?: string;
    mensaje?: string;
    error?: string;
  }> {
    try {
      const url = `${currentApiUrl}/pagos/verificar_pago_mp.php`;
      const res = await fetchWithTimeout(
        url,
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({
            external_reference: externalRef,
            monto_esperado: montoEsperado,
            payment_id: paymentId
          })
        },
        8000
      );
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return limpiarTildes(await res.json());
    } catch (e: any) {
      return { ok: false, pagado: false, error: e.message };
    }
  }
};
