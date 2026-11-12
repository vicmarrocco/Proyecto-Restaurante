import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { MostazaColors as C } from '@/constants/Colors';
import { TipoEntrega } from '@/types/cliente';

const PASOS: Record<TipoEntrega, string[]> = {
  delivery: ['En espera', 'En preparación', 'En camino', 'Entregado'],
  takeaway: ['En espera', 'En preparación', 'Listo', 'Retirado'],
  salon: ['En espera', 'En preparación', 'Para servir', 'En tu mesa'],
};
const EMOJI_POR_TIPO: Record<TipoEntrega, string[]> = {
  delivery: ['⏳', '👨‍🍳', '🛵', '✅'],
  takeaway: ['⏳', '👨‍🍳', '🛎️', '✅'],
  salon: ['⏳', '👨‍🍳', '🍽️', '✅'],
};

export default function StatusStepper({ paso, tipo }: { paso: number; tipo: TipoEntrega }) {
  const labels = PASOS[tipo] || PASOS.delivery;
  const emojis = EMOJI_POR_TIPO[tipo] || EMOJI_POR_TIPO.delivery;
  return (
    <View style={s.wrap}>
      {labels.map((l, i) => {
        const n = i + 1;
        const done = paso >= n;
        const current = paso === n && paso < 4;
        return (
          <View key={l} style={s.step}>
            <View style={s.lineRow}>
              <View style={[s.line, i === 0 && { opacity: 0 }, paso >= n && { backgroundColor: C.green }]} />
              <View style={[s.dot, done && s.dotDone, current && s.dotCurrent]}>
                <Text style={{ fontSize: 15 }}>{paso > n || paso === 4 ? '✓' : emojis[i]}</Text>
              </View>
              <View style={[s.line, i === labels.length - 1 && { opacity: 0 }, paso > n && { backgroundColor: C.green }]} />
            </View>
            <Text style={[s.label, done && { color: C.textDark, fontWeight: '800' }, current && { color: C.rojo, fontWeight: '900' }]}>
              {l}
            </Text>
          </View>
        );
      })}
    </View>
  );
}

const s = StyleSheet.create({
  wrap: { flexDirection: 'row', marginVertical: 10 },
  step: { flex: 1, alignItems: 'center' },
  lineRow: { flexDirection: 'row', alignItems: 'center', width: '100%' },
  line: { flex: 1, height: 3, backgroundColor: C.border },
  dot: { width: 38, height: 38, borderRadius: 19, backgroundColor: C.borderLight, alignItems: 'center', justifyContent: 'center', opacity: 0.6 },
  dotDone: { backgroundColor: C.greenBg, opacity: 1, borderWidth: 1.5, borderColor: C.green },
  dotCurrent: { backgroundColor: C.amarilloClaro, borderWidth: 2.5, borderColor: C.rojo, opacity: 1 },
  label: { fontSize: 10.5, color: C.textMuted, marginTop: 6, fontWeight: '600', textAlign: 'center' },
});
