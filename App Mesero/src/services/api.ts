import { getDefaultApiHost } from '@/constants/Config';
import { Mesa, Seccion, ProductoCatalogo, ItemComanda } from '@/types/mesero';

let currentApiUrl = getDefaultApiHost();
let currentMesero = {
  id: 1,
  nombre: 'Lucas Benítez',
  rol: 'Mesero Salón',
  avatar: '🍽️'
};

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

  getCurrentMesero() {
    return currentMesero;
  },

  setCurrentMesero(mesero: any) {
    currentMesero = mesero;
  },

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

  async getCatalogo(): Promise<{ ok: boolean; productos: ProductoCatalogo[]; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/catalogo.php`;
      const res = await fetchWithTimeout(url, {
        headers: { 'Accept': 'application/json' },
        cache: 'no-store'
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, productos: [], error: e.message };
    }
  },

  async enviarComanda(numeroMesa: number, items: ItemComanda[]): Promise<{ ok: boolean; idpedido?: number; error?: string }> {
    try {
      const subtotal = items.reduce((acc, it) => acc + it.precio * it.cantidad, 0);
      const url = `${currentApiUrl}/pos/crear_venta.php`;
      const payload = {
        tipo_entrega: 'salon',
        numero_mesa: numeroMesa,
        metodo_pago: 'efectivo',
        subtotal: subtotal,
        total: subtotal,
        items: items.map(it => ({
          idproducto: it.idproducto,
          descripcion: it.nombre,
          modificadores: it.modificadores || '',
          precio_unitario: it.precio,
          cantidad: it.cantidad,
          subtotal: it.precio * it.cantidad
        })),
        observaciones: `Comanda tomada por ${currentMesero.nombre}`
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
  }
};
