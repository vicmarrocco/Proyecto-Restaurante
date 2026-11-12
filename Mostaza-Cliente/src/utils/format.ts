export const money = (n: number | string) => '$' + Math.round(Number(n) || 0).toLocaleString('es-AR');
export const formatNumPedido = (id: number | string) => `#${String(id).padStart(3, '0')}`;
export const esActivo = (estado: string) => !['Entregado', 'Retirado', 'Cancelado'].includes((estado || '').trim());

export const estadoLabel = (estado: string, tipoEntrega?: string): string => {
  const e = (estado || '').trim();
  if (e === 'Recibido' || e === 'Pendiente' || e === 'En Espera') return 'En espera';
  if (['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'].includes(e)) return 'En preparación';
  if (['Listo', 'Listo para Despacho'].includes(e)) {
    if (tipoEntrega === 'delivery') return 'Listo para despacho';
    if (tipoEntrega === 'salon') return 'Listo para servir';
    return 'Listo para retirar';
  }
  if (e === 'En Camino') return 'En camino';
  if (e === 'Retirado') return 'Retirado';
  if (e === 'Entregado') return 'Entregado';
  if (e === 'Cancelado') return 'Cancelado';
  return e || 'En espera';
};

export const estadoEmoji = (estado: string, tipoEntrega?: string): string => {
  const e = (estado || '').trim();
  if (e === 'Recibido' || e === 'Pendiente' || e === 'En Espera') return '⏳';
  if (['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'].includes(e)) return '👨‍🍳';
  if (['Listo', 'Listo para Despacho'].includes(e)) {
    if (tipoEntrega === 'delivery') return '📦';
    if (tipoEntrega === 'salon') return '🍽️';
    return '🛎️';
  }
  if (e === 'En Camino') return '🛵';
  if (e === 'Entregado' || e === 'Retirado') return '✅';
  if (e === 'Cancelado') return '❌';
  return '⏳';
};

export const estadoPaso = (estado: string, tipoEntrega?: string): number => {
  const e = (estado || '').trim();
  if (e === 'Cancelado') return 0;
  if (e === 'Entregado' || e === 'Retirado') return 4;
  if (e === 'En Camino') return 3;
  if (['Listo', 'Listo para Despacho'].includes(e)) return tipoEntrega === 'delivery' ? 2 : 3;
  if (['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'].includes(e)) return 2;
  return 1;
};

export const estadoSubtitulo = (estado: string, tipoEntrega?: string, numeroMesa?: number | null): string => {
  const e = (estado || '').trim();
  if (e === 'Recibido' || e === 'Pendiente' || e === 'En Espera') {
    return 'Tu pedido fue recibido y está en espera para entrar a cocina.';
  }
  if (['En Cocina', 'En Preparacion', 'En Preparación', 'Preparando'].includes(e)) {
    return 'Estamos preparando tu pedido en cocina en este momento.';
  }
  if (['Listo', 'Listo para Despacho'].includes(e)) {
    if (tipoEntrega === 'delivery') return 'Tu pedido ya está listo y empaquetado esperando al repartidor.';
    if (tipoEntrega === 'salon') return `¡Listo en cocina! Un mesero lo lleva a tu Mesa ${numeroMesa ? '#' + numeroMesa : ''}.`;
    return '¡Ya está listo! Acercate al mostrador con tu número de pedido para retirarlo.';
  }
  if (e === 'En Camino') return 'El repartidor salió del local y va en camino a tu domicilio.';
  if (e === 'Retirado') return 'Pedido retirado en mostrador. ¡Que lo disfrutes!';
  if (e === 'Entregado') return 'Pedido entregado. ¡Gracias por elegir Mostaza!';
  if (e === 'Cancelado') return 'Este pedido fue cancelado.';
  return 'Seguimiento de tu pedido en tiempo real.';
};

export const estadoColor = (estado: string) => {
  const e = (estado || '').trim();
  if (e === 'Entregado' || e === 'Retirado') return { fg: '#047857', bg: '#DCFCE7', border: '#86EFAC' };
  if (e === 'Cancelado') return { fg: '#B91C1C', bg: '#FEE2E2', border: '#FCA5A5' };
  if (e === 'En Camino') return { fg: '#1D4ED8', bg: '#EFF6FF', border: '#93C5FD' };
  if (e.startsWith('Listo')) return { fg: '#047857', bg: '#DCFCE7', border: '#86EFAC' };
  if (e === 'Recibido' || e === 'Pendiente' || e === 'En Espera') return { fg: '#92400E', bg: '#FFF6DD', border: '#FCD34D' };
  return { fg: '#C2410C', bg: '#FFEDD5', border: '#FDBA74' };
};

export const fechaCorta = (f: string) => {
  const d = new Date((f || '').replace(' ', 'T'));
  if (isNaN(d.getTime())) return f;
  return d.toLocaleDateString('es-AR', { day: '2-digit', month: 'short' }) + ' · ' + d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
};

export const horaCorta = (f?: string | null) => {
  if (!f) return null;
  const d = new Date(f.replace(' ', 'T'));
  if (isNaN(d.getTime())) return null;
  return d.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' });
};

export const ENTREGA_LABEL: Record<string, string> = { delivery: '🛵 Delivery', takeaway: '🛍️ Retiro en local', salon: '🍽️ Salón' };
