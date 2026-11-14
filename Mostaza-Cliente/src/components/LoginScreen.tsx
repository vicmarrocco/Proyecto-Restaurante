import React, { useState } from 'react';
import { ActivityIndicator, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { MostazaColors as C } from '@/constants/Colors';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';

type Modo = 'login' | 'registro';

// Login sin contraseña: email + código de 6 dígitos enviado por correo (igual que la web)
export default function LoginScreen({ motivo }: { motivo?: string }) {
  const { login } = useAuth();
  const insets = useSafeAreaInsets();
  const bottomSafe = Platform.OS === 'web' ? 0 : Math.min(insets.bottom, 8);
  const [modo, setModo] = useState<Modo>('login');
  const [paso, setPaso] = useState<'email' | 'codigo'>('email');
  const [f, setF] = useState({ nombre: '', apellido: '', email: '', telefono: '', direccion: '' });
  const [codigo, setCodigo] = useState('');
  const [debug, setDebug] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);
  const [msg, setMsg] = useState<{ t: 'err' | 'ok'; m: string } | null>(null);
  const set = (k: keyof typeof f) => (v: string) => setF((p) => ({ ...p, [k]: v }));

  const pedirCodigo = async () => {
    if (!f.email.includes('@')) return setMsg({ t: 'err', m: 'Ingresá un correo válido.' });
    setBusy(true); setMsg(null);
    if (modo === 'registro') {
      if (!f.nombre.trim() || !f.apellido.trim()) { setBusy(false); return setMsg({ t: 'err', m: 'Nombre y apellido son obligatorios.' }); }
      const r = await ApiService.registro({ ...f, email: f.email.trim() });
      if (!r.ok) { setBusy(false); return setMsg({ t: 'err', m: r.error || 'No pudimos crear tu cuenta.' }); }
    }
    const r = await ApiService.solicitarCodigo(f.email.trim());
    setBusy(false);
    if (!r.ok) return setMsg({ t: 'err', m: r.error || 'No pudimos enviar el código.' });
    setDebug(r.debug_code || null);
    if (r.debug_code) setCodigo(String(r.debug_code));
    setMsg({ t: 'ok', m: r.mensaje || 'Te enviamos un código a tu correo.' });
    setPaso('codigo');
  };

  const verificar = async () => {
    if (codigo.trim().length < 6) return setMsg({ t: 'err', m: 'El código tiene 6 dígitos.' });
    setBusy(true); setMsg(null);
    const r = await login(f.email.trim(), codigo.trim());
    setBusy(false);
    if (!r.ok) setMsg({ t: 'err', m: r.error || 'Código incorrecto.' });
  };

  return (
    <SafeAreaView style={s.safe}>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView contentContainerStyle={[s.scroll, { paddingBottom: 20 + bottomSafe }]} keyboardShouldPersistTaps="handled">
          <View style={s.hero}>
            <Text style={s.logo}>🍔</Text>
            <Text style={s.brand}>MOSTAZA</Text>
            <Text style={s.tag}>{motivo || 'Pedí tus favoritos, donde estés'}</Text>
          </View>

          <View style={s.card}>
            <View style={s.tabs}>
              {(['login', 'registro'] as Modo[]).map((m) => (
                <TouchableOpacity key={m} style={[s.tab, modo === m && s.tabOn]} onPress={() => { setModo(m); setPaso('email'); setMsg(null); }}>
                  <Text style={[s.tabTxt, modo === m && { color: C.white }]}>{m === 'login' ? 'Ingresar' : 'Crear cuenta'}</Text>
                </TouchableOpacity>
              ))}
            </View>

            {paso === 'email' ? (
              <>
                {modo === 'registro' && (
                  <>
                    <View style={{ flexDirection: 'row', gap: 10 }}>
                      <TextInput style={[s.input, { flex: 1 }]} placeholder="Nombre" value={f.nombre} onChangeText={set('nombre')} placeholderTextColor={C.textMuted} />
                      <TextInput style={[s.input, { flex: 1 }]} placeholder="Apellido" value={f.apellido} onChangeText={set('apellido')} placeholderTextColor={C.textMuted} />
                    </View>
                    <TextInput style={s.input} placeholder="Teléfono" keyboardType="phone-pad" value={f.telefono} onChangeText={set('telefono')} placeholderTextColor={C.textMuted} />
                    <TextInput style={s.input} placeholder="Dirección de entrega" value={f.direccion} onChangeText={set('direccion')} placeholderTextColor={C.textMuted} />
                  </>
                )}
                <TextInput style={s.input} placeholder="Tu correo electrónico" autoCapitalize="none" keyboardType="email-address" value={f.email} onChangeText={set('email')} placeholderTextColor={C.textMuted} />
                <Text style={s.hint}>Sin contraseña: te enviamos un código de 6 dígitos a tu correo.</Text>
                <TouchableOpacity style={s.btn} onPress={pedirCodigo} disabled={busy}>
                  {busy ? <ActivityIndicator color={C.dark} /> : <Text style={s.btnTxt}>{modo === 'login' ? 'Enviarme el código' : 'Crear cuenta y recibir código'}</Text>}
                </TouchableOpacity>
              </>
            ) : (
              <>
                <Text style={s.codeTitle}>Revisá {f.email}</Text>
                <TextInput style={[s.input, s.code]} placeholder="······" keyboardType="number-pad" maxLength={6} value={codigo} onChangeText={setCodigo} placeholderTextColor={C.border} />
                {!!debug && <Text style={s.debug}>Modo desarrollo · código: {debug}</Text>}
                <TouchableOpacity style={s.btn} onPress={verificar} disabled={busy}>
                  {busy ? <ActivityIndicator color={C.dark} /> : <Text style={s.btnTxt}>Entrar</Text>}
                </TouchableOpacity>
                <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginTop: 14 }}>
                  <TouchableOpacity onPress={() => { setPaso('email'); setCodigo(''); setMsg(null); }}><Text style={s.link}>← Cambiar correo</Text></TouchableOpacity>
                  <TouchableOpacity onPress={pedirCodigo}><Text style={s.link}>Reenviar código</Text></TouchableOpacity>
                </View>
              </>
            )}
            {!!msg && <Text style={[s.msg, msg.t === 'err' ? { color: '#B91C1C', backgroundColor: '#FEE2E2' } : { color: '#047857', backgroundColor: C.greenBg }]}>{msg.m}</Text>}
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  scroll: { flexGrow: 1, justifyContent: 'center', padding: 20 },
  hero: { alignItems: 'center', marginBottom: 22 },
  logo: { fontSize: 54 },
  brand: { fontSize: 32, fontWeight: '900', color: C.amarillo, letterSpacing: 4, marginTop: 4 },
  tag: { color: C.white, opacity: 0.9, marginTop: 6, fontSize: 14, fontWeight: '600', textAlign: 'center' },
  card: { backgroundColor: C.white, borderRadius: 22, padding: 18 },
  tabs: { flexDirection: 'row', backgroundColor: C.borderLight, borderRadius: 12, padding: 4, marginBottom: 14 },
  tab: { flex: 1, paddingVertical: 10, alignItems: 'center', borderRadius: 9 },
  tabOn: { backgroundColor: C.rojo },
  tabTxt: { fontWeight: '800', color: C.textMuted, fontSize: 13 },
  input: { borderWidth: 1, borderColor: C.border, borderRadius: 12, padding: 13, fontSize: 15, color: C.textDark, marginBottom: 10, backgroundColor: C.lightBg },
  hint: { fontSize: 12, color: C.textMuted, marginBottom: 12 },
  btn: { backgroundColor: C.amarillo, borderRadius: 14, height: 50, alignItems: 'center', justifyContent: 'center' },
  btnTxt: { fontWeight: '900', fontSize: 15, color: C.dark },
  codeTitle: { fontWeight: '800', color: C.textDark, marginBottom: 12, textAlign: 'center' },
  code: { textAlign: 'center', fontSize: 28, letterSpacing: 10, fontWeight: '900' },
  debug: { fontSize: 12, color: '#92400E', backgroundColor: C.amarilloClaro, padding: 8, borderRadius: 8, marginBottom: 10, textAlign: 'center' },
  link: { color: C.rojo, fontWeight: '800', fontSize: 13 },
  msg: { marginTop: 14, padding: 10, borderRadius: 10, fontSize: 13, fontWeight: '600', overflow: 'hidden' },
});
