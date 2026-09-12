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
  nombre_plato: string;
  categoria: string;
  tiempo_preparacion_min: number;
  dificultad: string;
  ingredientes_lista: string;
  pasos_preparacion: string;
  temperatura_coccion: string;
}

export interface StockInsumo {
  idinsumo: number;
  nombre: string;
  categoria: string;
  unidad_medida: string;
  stock_actual: number;
  stock_minimo: number;
}
