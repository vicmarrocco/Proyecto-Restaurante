import React from 'react';
import { ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { useCart } from '@/context/CartContext';
import FoodImage from '@/components/FoodImage';
import { money } from '@/utils/format';

const ENVIO_GRATIS = 25000; // mismo umbral que la web

export default function CarritoScreen() {
  const router = useRouter();
  const { items, subtotal, setCantidad, remove, clear, totalItems } = useCart();
  const falta = Math.max(0, ENVIO_GRATIS - subtotal);

  return (
    <SafeAreaView style={s.safe} edges={['top']}>
      <View style={s.header}>
        <Text style={s.title}>Mi pedido</Text>
        {items.length > 0 && <TouchableOpacity onPress={clear}><Text style={s.clear}>Vaciar</Text></TouchableOpacity>}
      </View>

      {items.length === 0 ? (
        <View style={s.empty}>
          <Text style={{ fontSize: 64 }}>🛒</Text>
          <Text style={s.emptyTitle}>Tu carrito está vacío</Text>
          <Text style={s.emptySub}>Sumá tus favoritos del menú y armá tu pedido.</Text>
          <TouchableOpacity style={s.cta} onPress={() => router.push('/menu')}><Text style={s.ctaTxt}>Ver el menú</Text></TouchableOpacity>
        </View>
      ) : (
        <>
          <ScrollView style={{ backgroundColor: C.lightBg }} contentContainerStyle={{ padding: 14, paddingBottom: 130 }}>
            <View style={s.envio}>
              <Text style={s.envioTxt}>{falta > 0 ? `🛵 Sumá ${money(falta)} más y el envío es gratis` : '🎉 ¡Tenés envío gratis!'}</Text>
              <View style={s.bar}><View style={[s.barFill, { width: `${Math.min(100, (subtotal / ENVIO_GRATIS) * 100)}%` }]} /></View>
            </View>

            {items.map((it) => (
              <View key={it.key} style={s.item}>
                <FoodImage uri={it.imagen_url} style={s.img} />
                <View style={{ flex: 1, marginHorizontal: 12 }}>
                  <Text style={s.name} numberOfLines={2}>{it.nombre}</Text>
                  {!!it.tamano && <Text style={s.mod}>{it.tamano}</Text>}
                  {!!it.notas && <Text style={s.mod}>📝 {it.notas}</Text>}
                  <Text style={s.price}>{money(it.precio * it.cantidad)}</Text>
                </View>
                <View style={{ alignItems: 'flex-end', justifyContent: 'space-between' }}>
                  <TouchableOpacity onPress={() => remove(it.key)}><Text style={s.del}>🗑️</Text></TouchableOpacity>
                  <View style={s.qty}>
                    <TouchableOpacity style={s.qBtn} onPress={() => setCantidad(it.key, it.cantidad - 1)}><Text style={s.qTxt}>−</Text></TouchableOpacity>
                    <Text style={s.qNum}>{it.cantidad}</Text>
                    <TouchableOpacity style={s.qBtn} onPress={() => setCantidad(it.key, it.cantidad + 1)}><Text style={s.qTxt}>+</Text></TouchableOpacity>
                  </View>
                </View>
              </View>
            ))}
            <TouchableOpacity onPress={() => router.push('/menu')}><Text style={s.more}>+ Agregar más productos</Text></TouchableOpacity>
          </ScrollView>

          <View style={s.footer}>
            <View style={{ flex: 1 }}>
              <Text style={s.fLabel}>{totalItems} {totalItems === 1 ? 'producto' : 'productos'}</Text>
              <Text style={s.fTotal}>{money(subtotal)}</Text>
            </View>
            <TouchableOpacity style={s.pay} onPress={() => router.push('/checkout')} activeOpacity={0.9}>
              <Text style={s.payTxt}>Ir a pagar →</Text>
            </TouchableOpacity>
          </View>
        </>
      )}
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 14 },
  title: { color: C.white, fontSize: 22, fontWeight: '900' },
  clear: { color: C.amarillo, fontWeight: '800' },
  empty: { flex: 1, backgroundColor: C.lightBg, alignItems: 'center', justifyContent: 'center', padding: 30 },
  emptyTitle: { fontSize: 20, fontWeight: '900', color: C.textDark, marginTop: 12 },
  emptySub: { color: C.textMuted, marginTop: 6, textAlign: 'center' },
  cta: { backgroundColor: C.rojo, paddingHorizontal: 28, paddingVertical: 14, borderRadius: 14, marginTop: 20 },
  ctaTxt: { color: C.white, fontWeight: '900' },
  envio: { backgroundColor: C.amarilloClaro, padding: 12, borderRadius: 14, marginBottom: 12 },
  envioTxt: { fontSize: 13, fontWeight: '800', color: '#92400E' },
  bar: { height: 6, backgroundColor: '#FDE68A', borderRadius: 3, marginTop: 8, overflow: 'hidden' },
  barFill: { height: 6, backgroundColor: C.amarilloOscuro },
  item: { flexDirection: 'row', backgroundColor: C.white, padding: 12, borderRadius: 16, marginBottom: 10, borderWidth: 1, borderColor: C.border },
  img: { width: 70, height: 70, borderRadius: 12 },
  name: { fontSize: 14, fontWeight: '800', color: C.textDark },
  mod: { fontSize: 12, color: C.textMuted, marginTop: 2 },
  price: { fontSize: 14, fontWeight: '900', color: C.rojo, marginTop: 6 },
  del: { fontSize: 16 },
  qty: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: C.border, borderRadius: 10 },
  qBtn: { width: 32, height: 32, alignItems: 'center', justifyContent: 'center' },
  qTxt: { fontSize: 18, fontWeight: '900', color: C.rojo },
  qNum: { minWidth: 20, textAlign: 'center', fontWeight: '900', color: C.textDark },
  more: { color: C.rojo, fontWeight: '800', textAlign: 'center', marginTop: 8, paddingVertical: 10 },
  footer: { position: 'absolute', left: 0, right: 0, bottom: 0, flexDirection: 'row', alignItems: 'center', padding: 14, backgroundColor: C.white, borderTopWidth: 1, borderTopColor: C.border },
  fLabel: { fontSize: 12, color: C.textMuted, fontWeight: '700' },
  fTotal: { fontSize: 20, fontWeight: '900', color: C.textDark },
  pay: { backgroundColor: C.rojo, paddingHorizontal: 26, height: 50, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  payTxt: { color: C.white, fontWeight: '900', fontSize: 15 },
});
