import React from 'react';
import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { MostazaColors as C } from '@/constants/Colors';
import { Producto } from '@/types/cliente';
import { money } from '@/utils/format';
import FoodImage from './FoodImage';

interface Props { producto: Producto; onPress: (p: Producto) => void; variant?: 'row' | 'tile'; }

export default function ProductCard({ producto, onPress, variant = 'row' }: Props) {
  if (variant === 'tile') {
    return (
      <TouchableOpacity activeOpacity={0.85} style={s.tile} onPress={() => onPress(producto)}>
        <FoodImage uri={producto.imagen_url} style={s.tileImg} />
        <View style={{ padding: 10 }}>
          <Text numberOfLines={2} style={s.tileName}>{producto.nombre}</Text>
          <Text style={s.price}>{money(producto.precio)}</Text>
        </View>
        <View style={s.plusFloat}><Text style={s.plusTxt}>+</Text></View>
      </TouchableOpacity>
    );
  }
  return (
    <TouchableOpacity activeOpacity={0.85} style={s.row} onPress={() => onPress(producto)}>
      <View style={{ flex: 1, paddingRight: 12 }}>
        <Text numberOfLines={2} style={s.name}>{producto.nombre}</Text>
        {!!producto.descripcion && <Text numberOfLines={2} style={s.desc}>{producto.descripcion}</Text>}
        <View style={s.metaRow}>
          <Text style={s.price}>{money(producto.precio)}</Text>
          {producto.calorias > 0 && <Text style={s.kcal}>🔥 {producto.calorias} kcal</Text>}
        </View>
      </View>
      <View>
        <FoodImage uri={producto.imagen_url} style={s.img} />
        <View style={s.plus}><Text style={s.plusTxt}>+</Text></View>
      </View>
    </TouchableOpacity>
  );
}

const s = StyleSheet.create({
  row: { flexDirection: 'row', backgroundColor: C.white, padding: 14, borderRadius: 16, marginBottom: 10, borderWidth: 1, borderColor: C.border },
  name: { fontSize: 15, fontWeight: '800', color: C.textDark },
  desc: { fontSize: 12, color: C.textMuted, marginTop: 4, lineHeight: 17 },
  metaRow: { flexDirection: 'row', alignItems: 'center', marginTop: 8, gap: 10 },
  price: { fontSize: 15, fontWeight: '900', color: C.rojo, marginTop: 2 },
  kcal: { fontSize: 11, color: C.textMuted, fontWeight: '600' },
  img: { width: 96, height: 96, borderRadius: 14 },
  plus: { position: 'absolute', right: -6, bottom: -6, width: 32, height: 32, borderRadius: 16, backgroundColor: C.white, alignItems: 'center', justifyContent: 'center', borderWidth: 1, borderColor: C.border, elevation: 2 },
  plusTxt: { fontSize: 20, fontWeight: '900', color: C.rojo, marginTop: -2 },
  tile: { width: 160, backgroundColor: C.white, borderRadius: 16, marginRight: 12, borderWidth: 1, borderColor: C.border, overflow: 'hidden' },
  tileImg: { width: '100%', height: 110 },
  tileName: { fontSize: 13, fontWeight: '800', color: C.textDark, minHeight: 34 },
  plusFloat: { position: 'absolute', right: 8, top: 78, width: 32, height: 32, borderRadius: 16, backgroundColor: C.white, alignItems: 'center', justifyContent: 'center', elevation: 3 },
});
