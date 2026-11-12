import React from 'react';
import { StyleSheet, Text, TouchableOpacity } from 'react-native';
import { useRouter } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { useCart } from '@/context/CartContext';
import { money } from '@/utils/format';

// Barra flotante "Ver carrito" estilo apps de delivery
export default function CartBar() {
  const { totalItems, subtotal } = useCart();
  const router = useRouter();
  if (totalItems === 0) return null;
  return (
    <TouchableOpacity style={s.bar} activeOpacity={0.9} onPress={() => router.push('/carrito')}>
      <Text style={s.badge}>{totalItems}</Text>
      <Text style={s.label}>Ver mi pedido</Text>
      <Text style={s.total}>{money(subtotal)}</Text>
    </TouchableOpacity>
  );
}

const s = StyleSheet.create({
  bar: { position: 'absolute', left: 14, right: 14, bottom: 12, height: 54, borderRadius: 16, backgroundColor: C.rojo, flexDirection: 'row', alignItems: 'center', paddingHorizontal: 14, elevation: 8, shadowColor: '#000', shadowOpacity: 0.25, shadowRadius: 8, shadowOffset: { width: 0, height: 3 } },
  badge: { backgroundColor: C.amarillo, color: C.dark, fontWeight: '900', fontSize: 13, minWidth: 26, height: 26, lineHeight: 26, textAlign: 'center', borderRadius: 13, overflow: 'hidden' },
  label: { flex: 1, color: C.white, fontWeight: '800', fontSize: 15, marginLeft: 12 },
  total: { color: C.white, fontWeight: '900', fontSize: 15 },
});
