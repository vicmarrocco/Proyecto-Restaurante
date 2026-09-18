export interface DetalleItemCocina {
  iddetalle: number;
  idproducto: number;
  descripcion: string;
  modificadores: string;
  cantidad: number;
}

export interface ComandaCocina {
  idpedido: number;
  tipo_entrega: string;
  numero_mesa?: number;
  direccion_envio?: string;
  fecha: string;
  hora: string;
  minutos_espera: number;
  estado: string;
  sucursal: string;
  items: DetalleItemCocina[];
}

export interface Receta {
  idreceta: number;
  idproducto?: number;
  nombre: string;
  categoria: string;
  tiempo: string;
  tiempo_min: number;
  dificultad: string;
  ingredientes: string;
  pasos: string;
  temperatura: string;
}

export interface StockInsumo {
  idinsumo: number;
  nombre: string;
  categoria: string;
  unidad_medida: string;
  stock_actual: number;
  stock_minimo: number;
  alerta: boolean;
  actual_texto?: string;
  minimo_texto?: string;
  costo_unitario?: number;
  proveedor?: string;
}
