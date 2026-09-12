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
