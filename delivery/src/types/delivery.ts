// ============================================================
// types/delivery.ts
// Definiciones TypeScript para la aplicación de Delivery Mostaza
// ============================================================

export type EstadoPedido = 
  | 'Recibido' 
  | 'En Preparación' 
  | 'Listo para Despacho' 
  | 'En Camino' 
  | 'Entregado' 
  | 'Cancelado';

export type MetodoPago = 'efectivo' | 'tarjeta' | 'mercadopago' | 'transferencia';

export interface ItemPedido {
  idproducto: number | null;
  descripcion: string;
  modificadores?: string | null;
  precio_unitario: number;
  cantidad: number;
  subtotal: number;
  subtotal_formateado: string;
}

export interface PedidoDelivery {
  idpedido: number;
  numero_pedido_usuario?: number;
  idusuario: number | null;
  cliente_nombre: string;
  cliente_telefono: string;
  cliente_direccion: string;
  referencia_entrega?: string;
  latitud?: number | null;
  longitud?: number | null;
  sucursal_nombre?: string;
  sucursal_direccion?: string;
  sucursal_lat?: number;
  sucursal_lng?: number;
  distancia_km?: number | null;
  distancia_formateada?: string;
  tiempo_estimado_min?: number;
  fecha: string;
  hora: string;
  estado: EstadoPedido;
  tipo_entrega: string;
  sucursal: string;
  metodo_pago: MetodoPago;
  estado_pago: string;
  subtotal: number;
  costo_envio: number;
  descuento: number;
  propina: number;
  total: number;
  total_formateado: string;
  paga_con: number;
  vuelto: number;
  vuelto_formateado: string;
  notas: string;
  repartidor_asignado: string;
  idempleado_repartidor?: number | null;
  items: ItemPedido[];
  cantidad_items: number;
}

export interface Repartidor {
  id: number;
  nombre: string;
  email?: string;
  dni?: string;
  telefono: string;
  vehiculo: string;
  patente: string;
  pin?: string;
  avatar: string;
  sucursal_base?: string;
  calificacion?: number;
  viajes_totales?: number;
  estado_servicio?: string;
  estado?: string;
}

export interface EstadisticasRider {
  ok: boolean;
  repartidor: string;
  entregados_hoy: number;
  entregados_total?: number;
  en_camino_actual: number;
  efectivo_a_rendir: number;
  efectivo_a_rendir_formateado: string;
  total_propinas: number;
  total_propinas_formateado: string;
  total_facturado: number;
  total_facturado_formateado: string;
  total_historico?: number;
  total_historico_formateado?: string;
}

export interface SesionTurno {
  token: string | null;
  idregistro: number | null;
  hora_ingreso: string | null;
  fecha: string | null;
  activo: boolean;
}
