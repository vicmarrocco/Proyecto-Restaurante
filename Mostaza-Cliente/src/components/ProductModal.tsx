import React, { useEffect, useState } from 'react';
import { Modal, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { MostazaColors as C } from '@/constants/Colors';
import { Producto } from '@/types/cliente';
import { useCart } from '@/context/CartContext';
import { money } from '@/utils/format';
import FoodImage from './FoodImage';

interface Props { producto: Producto | null; onClose: () => void; }

export default function ProductModal({ producto, onClose }: Props) {
  const { add } = useCart();
  const insets = useSafeAreaInsets();
  const bottomSafe = Platform.OS === 'web' ? 0 : Math.max(insets.bottom, Platform.OS === 'android' ? 44 : 16);
  const [cantidad, setCantidad] = useState(1);
  const [tamano, setTamano] = useState<string | undefined>();
  const [notas, setNotas] = useState('');

  useEffect(() => {
    if (producto) { setCantidad(1); setNotas(''); setTamano(producto.tamanos[0]?.nombre); }
  }, [producto]);

  if (!producto) return null;
  const extra = producto.tamanos.find((t) => t.nombre === tamano)?.extra || 0;
  const total = (producto.precio + extra) * cantidad;
  const showSizes = producto.tamanos.length > 1 && ['megacombos', 'exclusivos', 'pollo', 'paravos'].includes(producto.categoria);

  const agregar = () => {
    add(producto, { cantidad, tamano: showSizes ? tamano : undefined, extra: showSizes ? extra : 0, notas: notas.trim() || undefined });
    onClose();
  };

  return (
    <Modal visible animationType="slide" transparent onRequestClose={onClose}>
      <View style={s.backdrop}>
        <TouchableOpacity style={{ flex: 1 }} activeOpacity={1} onPress={onClose} />
        <View style={s.sheet}>
          <ScrollView showsVerticalScrollIndicator={false}>
            <View>
              <FoodImage uri={producto.imagen_url} style={s.hero} />
              <TouchableOpacity style={s.close} onPress={onClose}><Text style={s.closeTxt}>✕</Text></TouchableOpacity>
            </View>
            <View style={{ padding: 18 }}>
              <Text style={s.title}>{producto.nombre}</Text>
              <Text style={s.price}>{money(producto.precio)}</Text>
              {!!producto.descripcion && <Text style={s.desc}>{producto.descripcion}</Text>}
              {!!producto.ingredientes && <><Text style={s.label}>INGREDIENTES</Text><Text style={s.info}>{producto.ingredientes}</Text></>}
              {!!producto.alergenos && <View style={s.alergia}><Text style={s.alergiaTxt}>⚠️ Alérgenos: {producto.alergenos}</Text></View>}

              {showSizes && (
                <>
                  <Text style={s.label}>ELEGÍ EL TAMAÑO</Text>
                  {producto.tamanos.map((t) => {
                    const on = t.nombre === tamano;
                    return (
                      <TouchableOpacity key={t.nombre} style={[s.opt, on && s.optOn]} onPress={() => setTamano(t.nombre)}>
                        <View style={[s.radio, on && s.radioOn]}>{on && <View style={s.radioDot} />}</View>
                        <Text style={s.optName}>{t.nombre}</Text>
                        <Text style={s.optExtra}>{t.extra > 0 ? `+ ${money(t.extra)}` : 'Incluido'}</Text>
                      </TouchableOpacity>
                    );
                  })}
                </>
              )}

              <Text style={s.label}>¿ALGUNA INDICACIÓN?</Text>
              <TextInput style={s.input} value={notas} onChangeText={setNotas} placeholder="Ej: sin cebolla, bien cocida…" placeholderTextColor={C.textMuted} multiline maxLength={120} />
            </View>
          </ScrollView>

          <View style={[s.footer, { paddingBottom: 14 + bottomSafe }]}>
            <View style={s.qty}>
              <TouchableOpacity style={s.qBtn} onPress={() => setCantidad(Math.max(1, cantidad - 1))}><Text style={s.qTxt}>−</Text></TouchableOpacity>
              <Text style={s.qNum}>{cantidad}</Text>
              <TouchableOpacity style={s.qBtn} onPress={() => setCantidad(Math.min(20, cantidad + 1))}><Text style={s.qTxt}>+</Text></TouchableOpacity>
            </View>
            <TouchableOpacity style={s.addBtn} onPress={agregar} activeOpacity={0.9}>
              <Text style={s.addTxt}>Agregar</Text>
              <Text style={s.addTxt}>{money(total)}</Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
}

const s = StyleSheet.create({
  backdrop: { flex: 1, backgroundColor: 'rgba(15,23,42,0.55)' },
  sheet: { maxHeight: '90%', backgroundColor: C.white, borderTopLeftRadius: 26, borderTopRightRadius: 26, overflow: 'hidden' },
  hero: { width: '100%', height: 210 },
  close: { position: 'absolute', top: 14, right: 14, width: 36, height: 36, borderRadius: 18, backgroundColor: C.white, alignItems: 'center', justifyContent: 'center', elevation: 3 },
  closeTxt: { fontSize: 16, fontWeight: '900', color: C.textDark },
  title: { fontSize: 21, fontWeight: '900', color: C.textDark },
  price: { fontSize: 19, fontWeight: '900', color: C.rojo, marginTop: 4 },
  desc: { fontSize: 14, color: C.textMuted, marginTop: 8, lineHeight: 20 },
  label: { fontSize: 11, fontWeight: '900', color: C.textMuted, letterSpacing: 1, marginTop: 18, marginBottom: 8 },
  info: { fontSize: 13, color: C.textDark, lineHeight: 19 },
  alergia: { backgroundColor: C.amarilloClaro, padding: 10, borderRadius: 10, marginTop: 12 },
  alergiaTxt: { fontSize: 12, color: '#92400E', fontWeight: '600' },
  opt: { flexDirection: 'row', alignItems: 'center', padding: 14, borderRadius: 14, borderWidth: 1.5, borderColor: C.border, marginBottom: 8 },
  optOn: { borderColor: C.rojo, backgroundColor: C.rojoClaro },
  radio: { width: 20, height: 20, borderRadius: 10, borderWidth: 2, borderColor: C.border, alignItems: 'center', justifyContent: 'center', marginRight: 12 },
  radioOn: { borderColor: C.rojo },
  radioDot: { width: 10, height: 10, borderRadius: 5, backgroundColor: C.rojo },
  optName: { flex: 1, fontSize: 14, fontWeight: '700', color: C.textDark },
  optExtra: { fontSize: 13, fontWeight: '800', color: C.textMuted },
  input: { borderWidth: 1, borderColor: C.border, borderRadius: 12, padding: 12, minHeight: 56, fontSize: 14, color: C.textDark, textAlignVertical: 'top' },
  footer: { flexDirection: 'row', padding: 14, gap: 12, borderTopWidth: 1, borderTopColor: C.border, backgroundColor: C.white },
  qty: { flexDirection: 'row', alignItems: 'center', borderWidth: 1, borderColor: C.border, borderRadius: 14 },
  qBtn: { width: 40, height: 50, alignItems: 'center', justifyContent: 'center' },
  qTxt: { fontSize: 22, fontWeight: '900', color: C.rojo },
  qNum: { minWidth: 24, textAlign: 'center', fontSize: 16, fontWeight: '900', color: C.textDark },
  addBtn: { flex: 1, height: 50, backgroundColor: C.rojo, borderRadius: 14, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 18 },
  addTxt: { color: C.white, fontSize: 15, fontWeight: '900' },
});
