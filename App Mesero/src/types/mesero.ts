export interface MesaInfo {
  idpedido: number;
  numero_mesa: number;
  cliente: string;
  total: number;
  total_formateado: string;
  hora: string;
  minutos_transcurridos: number;
  tiempo_texto: string;
  estado: string;
  metodo_pago: string;
}

export interface Mesa {
  idmesa: number;
  numero: number;
  numero_mesa: number;
  idseccion: number;
  seccion_nombre: string;
  seccion_icono: string;
  capacidad: number;
  ocupada: boolean;
  estado_texto: string;
  info: MesaInfo | null;
}

export interface Seccion {
  idseccion: number;
  nombre: string;
  descripcion: string;
  icono: string;
}

export interface ProductoCatalogo {
  idproducto: number;
  nombre: string;
  categoria: string;
  precio: number;
  imagen?: string;
  descripcion?: string;
}

export interface ItemComanda {
  idproducto: number;
  nombre: string;
  precio: number;
  cantidad: number;
  modificadores?: string;
}

export interface PedidoSalon {
  idpedido: number;
  tipo_entrega: string;
  numero_mesa?: number;
  cliente: string;
  total: number;
  total_formateado: string;
  metodo_pago: string;
  estado_pago?: string;
  estado: string;
  origen: 'online' | 'presencial';
  es_online: boolean;
  hora: string;
  fecha: string;
  idfactura?: number;
  numero_factura?: string;
  idempleado_mesero?: number;
  mesero_nombre?: string;
  cocinero_nombre?: string;
  items: Array<{
    idproducto: number;
    descripcion: string;
    cantidad: number;
    precio_unitario: number;
    subtotal: number;
    modificadores?: string;
  }>;
}

export interface ResumenTurnoMesero {
  horas_trabajadas: number;
  mesas_atendidas: number;
  total_recaudado: number;
  total_recaudado_formateado: string;
  ticket_promedio: string;
  tiempo_promedio_servicio: string;
  hora_entrada: string;
}
