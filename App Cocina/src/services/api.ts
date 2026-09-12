import { getDefaultApiHost } from '@/constants/Config';
import { ComandaCocina, Receta, StockInsumo } from '@/types/cocina';

let currentApiUrl = getDefaultApiHost();
let currentCocinero = {
  id: 1,
  nombre: 'Martín Paredes',
  rol: 'Jefe de Cocina'
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

  getCurrentCocinero() {
    return currentCocinero;
  },

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
        p.estado === 'Recibido' || p.estado === 'En Cocina' || p.estado === 'En Preparación'
      ).map((p: any) => {
        const tiempoMinutos = Math.max(0, Math.round((Date.now() - new Date(p.fecha).getTime()) / 60000)) || 0;
        return {
          idpedido: p.idpedido,
          tipo_entrega: p.tipo_entrega || 'salon',
          numero_mesa: p.numero_mesa,
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

  async marcarCompletado(idpedido: number): Promise<{ ok: boolean; error?: string }> {
    try {
      const url = `${currentApiUrl}/pos/pedidos.php`;
      const res = await fetchWithTimeout(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ accion: 'cambiar_estado', idpedido, nuevo_estado: 'Listo para Despacho' })
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
          cancelado_por: `Cocina (${currentCocinero.nombre})`
        })
      });
      if (!res.ok) throw new Error(`HTTP Error ${res.status}`);
      return await res.json();
    } catch (e: any) {
      return { ok: false, error: e.message };
    }
  }
};
