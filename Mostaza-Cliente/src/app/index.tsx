import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Alert, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { Sucursal } from '@/types/cliente';
import LoginScreen from '@/components/LoginScreen';
import { money } from '@/utils/format';

const PUNTOS_META = 1000;

export default function PerfilScreen() {
  const { usuario, setUsuario, logout } = useAuth();
  const [form, setForm] = useState({ nombre: '', apellido: '', telefono: '', direccion: '' });
  const [stats, setStats] = useState({ pedidos: 0, gastado: 0 });
  const [sucursales, setSucursales] = useState<Sucursal[]>([]);
  const [saving, setSaving] = useState(false);
  const [ok, setOk] = useState(false);

  useEffect(() => {
    if (!usuario) return;
    setForm({ nombre: usuario.nombre, apellido: usuario.apellido, telefono: usuario.telefono || '', direccion: usuario.direccion_favorita || usuario.direccion || '' });
  }, [usuario]);

  useFocusEffect(useCallback(() => {
    if (!ApiService.hasToken()) return;
    ApiService.perfil().then((r) => { if (r.ok) { setStats({ pedidos: r.total_pedidos, gastado: r.total_gastado }); setUsuario(r.usuario); } });
    ApiService.sucursales().then((r) => { if (r.ok) setSucursales(r.sucursales); });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []));

  if (!usuario) return <LoginScreen motivo="Ingresá para ver tu perfil y tus puntos" />;

  const guardar = async () => {
    if (!form.nombre.trim() || !form.apellido.trim()) return Alert.alert('Datos incompletos', 'Nombre y apellido son obligatorios.');
    setSaving(true);
    const r = await ApiService.actualizarPerfil(form);
    setSaving(false);
    if (!r.ok || !r.usuario) return Alert.alert('Error', r.error || 'No pudimos guardar los cambios.');
    setUsuario(r.usuario); setOk(true); setTimeout(() => setOk(false), 2500);
  };

  const salir = () => {
    if (Platform.OS === 'web') { logout(); return; }
    Alert.alert('Cerrar sesión', '¿Querés salir de tu cuenta?', [{ text: 'Cancelar', style: 'cancel' }, { text: 'Salir', style: 'destructive', onPress: logout }]);
  };

  const iniciales = `${usuario.nombre[0] || ''}${usuario.apellido[0] || ''}`.toUpperCase();
  const pct = Math.min(100, (usuario.puntos / PUNTOS_META) * 100);

  return (
    <SafeAreaView style={s.safe} edges={['top']}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView style={{ backgroundColor: C.lightBg }} contentContainerStyle={{ paddingBottom: 40 }} keyboardShouldPersistTaps="handled">
          <View style={s.hero}>
            <View style={s.avatar}><Text style={s.avatarTxt}>{iniciales}</Text></View>
            <Text style={s.name}>{usuario.nombre} {usuario.apellido}</Text>
            <Text style={s.email}>{usuario.email}</Text>
          </View>

          <View style={s.body}>
            <View style={s.points}>
              <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                <View><Text style={s.pLabel}>PUNTOS MOSTAZA</Text><Text style={s.pNum}>⭐ {usuario.puntos}</Text></View>
                <Text style={s.pEq}>= {money(usuario.puntos)} de descuento</Text>
              </View>
              <View style={s.bar}><View style={[s.barFill, { width: `${pct}%` }]} /></View>
              <Text style={s.pHint}>Ganás 1 punto cada $100 que gastás. Usalos al pagar (hasta el 50% del pedido).</Text>
            </View>

            <View style={s.stats}>
              <View style={s.stat}><Text style={s.statNum}>{stats.pedidos}</Text><Text style={s.statLbl}>Pedidos</Text></View>
              <View style={s.stat}><Text style={s.statNum}>{money(stats.gastado)}</Text><Text style={s.statLbl}>Gastado</Text></View>
            </View>

            <Text style={s.label}>MIS DATOS</Text>
            <View style={s.card}>
              <View style={{ flexDirection: 'row', gap: 10 }}>
                <TextInput style={[s.input, { flex: 1 }]} placeholder="Nombre" value={form.nombre} onChangeText={(v) => setForm({ ...form, nombre: v })} placeholderTextColor={C.textMuted} />
                <TextInput style={[s.input, { flex: 1 }]} placeholder="Apellido" value={form.apellido} onChangeText={(v) => setForm({ ...form, apellido: v })} placeholderTextColor={C.textMuted} />
              </View>
              <TextInput style={s.input} placeholder="Teléfono" keyboardType="phone-pad" value={form.telefono} onChangeText={(v) => setForm({ ...form, telefono: v })} placeholderTextColor={C.textMuted} />
              <TextInput style={s.input} placeholder="Dirección favorita de entrega" value={form.direccion} onChangeText={(v) => setForm({ ...form, direccion: v })} placeholderTextColor={C.textMuted} />
              <TouchableOpacity style={s.save} onPress={guardar} disabled={saving}>
                {saving ? <ActivityIndicator color={C.dark} /> : <Text style={s.saveTxt}>{ok ? '✓ Guardado' : 'Guardar cambios'}</Text>}
              </TouchableOpacity>
            </View>

            {sucursales.length > 0 && (
              <>
                <Text style={s.label}>NUESTROS LOCALES</Text>
                <View style={s.card}>
                  {sucursales.map((su, i) => (
                    <View key={su.idsucursal} style={[s.local, i > 0 && { borderTopWidth: 1, borderTopColor: C.borderLight }]}>
                      <Text style={s.localName}>📍 {su.nombre}</Text>
                      <Text style={s.localInfo}>{su.direccion}</Text>
                      <Text style={s.localInfo}>🕒 {su.horario_atencion} · 📞 {su.telefono}</Text>
                    </View>
                  ))}
                </View>
              </>
            )}

            <TouchableOpacity style={s.logout} onPress={salir}><Text style={s.logoutTxt}>Cerrar sesión</Text></TouchableOpacity>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  hero: { alignItems: 'center', paddingTop: 14, paddingBottom: 54 },
  avatar: { width: 78, height: 78, borderRadius: 39, backgroundColor: C.amarillo, alignItems: 'center', justifyContent: 'center', borderWidth: 3, borderColor: C.white },
  avatarTxt: { fontSize: 28, fontWeight: '900', color: C.dark },
  name: { color: C.white, fontSize: 20, fontWeight: '900', marginTop: 10 },
  email: { color: '#FECACA', fontSize: 13, marginTop: 2 },
  body: { padding: 14, marginTop: -40 },
  points: { backgroundColor: C.dark, borderRadius: 20, padding: 16 },
  pLabel: { color: C.amarillo, fontSize: 11, fontWeight: '900', letterSpacing: 1.5 },
  pNum: { color: C.white, fontSize: 30, fontWeight: '900', marginTop: 2 },
  pEq: { color: '#CBD5E1', fontSize: 12, fontWeight: '700', maxWidth: 130, textAlign: 'right' },
  bar: { height: 6, backgroundColor: '#334155', borderRadius: 3, marginTop: 12, overflow: 'hidden' },
  barFill: { height: 6, backgroundColor: C.amarillo },
  pHint: { color: '#94A3B8', fontSize: 11.5, marginTop: 10, lineHeight: 16 },
  stats: { flexDirection: 'row', gap: 10, marginTop: 12 },
  stat: { flex: 1, backgroundColor: C.white, borderRadius: 16, padding: 14, alignItems: 'center', borderWidth: 1, borderColor: C.border },
  statNum: { fontSize: 20, fontWeight: '900', color: C.rojo },
  statLbl: { fontSize: 12, color: C.textMuted, fontWeight: '700', marginTop: 2 },
  label: { fontSize: 11, fontWeight: '900', color: C.textMuted, letterSpacing: 1, marginTop: 20, marginBottom: 8 },
  card: { backgroundColor: C.white, borderRadius: 16, padding: 14, borderWidth: 1, borderColor: C.border },
  input: { borderWidth: 1, borderColor: C.border, borderRadius: 12, padding: 12, fontSize: 14, color: C.textDark, marginBottom: 10, backgroundColor: C.lightBg },
  save: { backgroundColor: C.amarillo, height: 48, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  saveTxt: { fontWeight: '900', color: C.dark },
  local: { paddingVertical: 10 },
  localName: { fontWeight: '900', color: C.textDark, fontSize: 14 },
  localInfo: { color: C.textMuted, fontSize: 12.5, marginTop: 2 },
  logout: { marginTop: 22, height: 48, borderRadius: 14, borderWidth: 1.5, borderColor: C.rojo, alignItems: 'center', justifyContent: 'center' },
  logoutTxt: { color: C.rojo, fontWeight: '900' },
});
