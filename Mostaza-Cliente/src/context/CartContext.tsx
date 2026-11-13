import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { ItemCarrito, Producto } from '@/types/cliente';

const CART_KEY = 'mostaza_cliente_carrito';

interface CartCtx {
  items: ItemCarrito[]; totalItems: number; subtotal: number;
  add: (p: Producto, o: { cantidad: number; tamano?: string; extra?: number; notas?: string }) => void;
  setCantidad: (key: string, cantidad: number) => void;
  remove: (key: string) => void;
  clear: () => void;
}
const Ctx = createContext<CartCtx>({} as CartCtx);

export const CartProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const [items, setItems] = useState<ItemCarrito[]>([]);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    AsyncStorage.getItem(CART_KEY).then((raw) => { if (raw) { try { setItems(JSON.parse(raw)); } catch {} } setReady(true); });
  }, []);
  useEffect(() => { if (ready) AsyncStorage.setItem(CART_KEY, JSON.stringify(items)); }, [items, ready]);

  const add: CartCtx['add'] = (p, { cantidad, tamano, extra = 0, notas }) => {
    const key = `p${p.idproducto}|${tamano || ''}|${(notas || '').trim().toLowerCase()}`;
    setItems((prev) => {
      const i = prev.findIndex((x) => x.key === key);
      if (i >= 0) { const c = [...prev]; c[i] = { ...c[i], cantidad: c[i].cantidad + cantidad }; return c; }
      return [...prev, { key, idproducto: p.idproducto, nombre: p.nombre, imagen_url: p.imagen_url, precioBase: p.precio, precio: p.precio + extra, cantidad, tamano, notas }];
    });
  };
  const setCantidad = (key: string, cantidad: number) =>
    setItems((prev) => (cantidad <= 0 ? prev.filter((x) => x.key !== key) : prev.map((x) => (x.key === key ? { ...x, cantidad } : x))));
  const remove = (key: string) => setItems((prev) => prev.filter((x) => x.key !== key));
  const clear = () => setItems([]);

  const totalItems = useMemo(() => items.reduce((a, b) => a + b.cantidad, 0), [items]);
  const subtotal = useMemo(() => items.reduce((a, b) => a + b.precio * b.cantidad, 0), [items]);
  return <Ctx.Provider value={{ items, totalItems, subtotal, add, setCantidad, remove, clear }}>{children}</Ctx.Provider>;
};
export const useCart = () => useContext(Ctx);
