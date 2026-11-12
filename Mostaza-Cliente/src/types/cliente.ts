export interface Usuario {
  idusuario: number; nombre: string; apellido: string; email: string;
  telefono: string | null; direccion: string | null;
  direccion_favorita: string | null; telefono_favorito: string | null;
  puntos: number; fecha_registro: string;
}
export interface Categoria { slug: string; nombre: string; sub: string; emoji: string; cantidad: number; }
export interface Tamano { nombre: string; extra: number; }
export interface Producto {
  idproducto: number; nombre: string; descripcion: string | null; precio: number; categoria: string;
  imagen: string; imagen_url: string; ingredientes: string | null; alergenos: string | null;
  calorias: number; tamanos: Tamano[];
}
export interface ItemCarrito {
  key: string; idproducto: number; nombre: string; imagen_url: string;
  precioBase: number; precio: number; cantidad: number; tamano?: string; notas?: string;
}
export type TipoEntrega = 'delivery' | 'takeaway' | 'salon';
export type MetodoPago = 'efectivo' | 'mercadopago';
export interface PreferenciaMP {
  modo_real: boolean;
  public_key?: string;
  preference_id?: string;
  init_point: string;
  external_reference: string;
  total: number;
  total_formateado: string;
  qr_checkout_url: string;
  qr_emvco_url: string;
  alias: string;
  cvu: string;
  titular: string;
}
export interface VerificacionMP {
  modo_real: boolean;
  pagado: boolean;
  status: string;
  payment_id?: string;
  monto?: number;
  metodo_detalle?: string;
}
export interface Sucursal { idsucursal: number; nombre: string; direccion: string; telefono: string; horario_atencion: string; }
export interface Mesa { numero_mesa: number; capacidad: number; estado: string; seccion: string | null; }
export interface Cupon { codigo: string; tipo: 'porcentaje' | 'fijo'; valor: string; minimo_compra: string; tope_descuento: string | null; descripcion: string | null; }
export interface ConfigPago { alias: string; cvu: string; titular: string; banco: string; link_mp: string; envio_costo: number; envio_gratis_desde: number; }
export interface PedidoItem { idproducto: number; descripcion: string; modificadores: string | null; precio_unitario: string; cantidad: string; subtotal: string; }
export interface Pedido {
  idpedido: number; numero_pedido_usuario: number | null; fecha: string; tipo_entrega: TipoEntrega; numero_mesa: number | null;
  sucursal: string; metodo_pago: string; estado_pago: string; subtotal: string; costo_envio: string; descuento: string;
  propina: string; total: string; estado: string; items: PedidoItem[];
  cocinero_nombre?: string | null; repartidor_asignado?: string | null; mesero_nombre?: string | null;
  motivo_cancelacion?: string | null; hora_recibido?: string | null; hora_cocina_inicio?: string | null;
  hora_cocina_listo?: string | null; hora_entregado?: string | null;
}
export interface Tracking {
  idpedido: number; numero_pedido_usuario?: number | null; estado: string; estado_label?: string;
  tipo_entrega: TipoEntrega; paso: number; titulo: string; descripcion: string;
  fecha?: string; repartidor: string | null; vehiculo: string | null; cocinero: string | null; mesero: string | null;
  numero_mesa: number | null; sucursal: string; direccion_envio: string | null; total: number; metodo_pago: string; estado_pago: string;
  hora_recibido?: string | null; hora_cocina_inicio?: string | null; hora_cocina_listo?: string | null; hora_entregado?: string | null;
}
