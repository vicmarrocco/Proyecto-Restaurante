import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  SafeAreaView,
  Alert,
  Platform,
} from 'react-native';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';

export default function LoginScreen() {
  const { loginConCodigo } = useAuth();
  const [cocineros, setCocineros] = useState<any[]>([]);
  const [loadingStaff, setLoadingStaff] = useState(true);
  const [selectedCocinero, setSelectedCocinero] = useState<any>(null);

  const [step, setStep] = useState<'email' | 'codigo'>('email');
  const [email, setEmail] = useState('cocina1@mostaza.com.ar');
  const [codigo, setCodigo] = useState('');
  const [debugCode, setDebugCode] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    let isMounted = true;
    async function loadCooks() {
      setLoadingStaff(true);
      const res = await ApiService.getListaCocineros();
      if (isMounted) {
        if (res.ok && res.empleados.length > 0) {
          setCocineros(res.empleados);
          setSelectedCocinero(res.empleados[0]);
          setEmail(res.empleados[0].email || 'cocina1@mostaza.com.ar');
        }
        setLoadingStaff(false);
      }
    }
    loadCooks();
    return () => { isMounted = false; };
  }, []);

  const handleSelectCook = (c: any) => {
    setSelectedCocinero(c);
    setEmail(c.email);
    setErrorMsg(null);
  };

  const handleSolicitarCodigo = async () => {
    if (!email.trim() || !email.includes('@')) {
      setErrorMsg('Ingresá un correo corporativo válido.');
      return;
    }
    setSubmitting(true);
    setErrorMsg(null);
    const res = await ApiService.solicitarCodigoGmail(email.trim());
    setSubmitting(false);

    if (res.ok) {
      setDebugCode(res.debug_code || null);
      setStep('codigo');
      if (res.debug_code) {
        setCodigo(res.debug_code);
      }
    } else {
      setErrorMsg(res.error || 'No se pudo enviar el código.');
    }
  };

  const handleVerificarCodigo = async () => {
    if (!codigo.trim() || codigo.trim().length < 4) {
      setErrorMsg('Ingresá los 6 dígitos del código.');
      return;
    }
    setSubmitting(true);
    setErrorMsg(null);
    const res = await loginConCodigo(email.trim(), codigo.trim());
    setSubmitting(false);

    if (!res.ok) {
      setErrorMsg(res.error || 'Código incorrecto o vencido.');
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.card}>
          {/* Header Marca */}
          <View style={styles.headerBox}>
            <Text style={styles.logoIcon}>🍔</Text>
            <Text style={styles.brandTitle}>MOSTAZA COCINA · KDS</Text>
            <Text style={styles.brandSub}>Identificación de Parrilleros · Sucursal Formosa</Text>
            <View style={styles.accentBar} />
          </View>

          {/* Mensaje de Error */}
          {errorMsg ? (
            <View style={styles.errorBox}>
              <Text style={styles.errorText}>⚠️ {errorMsg}</Text>
            </View>
          ) : null}

          {step === 'email' ? (
            <View>
              {/* Selector Rápido de Cocineros */}
              <Text style={styles.sectionTitle}>👨‍🍳 Cocineros de Turno:</Text>
              {loadingStaff ? (
                <ActivityIndicator size="small" color={MostazaColors.rojo} style={{ marginVertical: 10 }} />
              ) : (
                <View style={styles.cooksGrid}>
                  {cocineros.map((c) => {
                    const isSelected = selectedCocinero?.id === c.id || email === c.email;
                    return (
                      <TouchableOpacity
                        key={c.id}
                        style={[styles.cookBtn, isSelected && styles.cookBtnActive]}
                        onPress={() => handleSelectCook(c)}
                        activeOpacity={0.8}>
                        <Text style={styles.cookAvatar}>{c.avatar || '👨‍🍳'}</Text>
                        <View style={{ flex: 1 }}>
                          <Text style={[styles.cookName, isSelected && styles.cookNameActive]}>
                            {c.nombre_completo || c.nombre}
                          </Text>
                          <Text style={styles.cookEmail}>{c.email}</Text>
                        </View>
                        {isSelected && <Text style={styles.checkIcon}>✓</Text>}
                      </TouchableOpacity>
                    );
                  })}
                </View>
              )}

              <Text style={styles.inputLabel}>O ingresá tu correo de Cocinero:</Text>
              <TextInput
                style={styles.input}
                value={email}
                onChangeText={setEmail}
                placeholder="ej: cocina1@mostaza.com.ar"
                placeholderTextColor="#94A3B8"
                keyboardType="email-address"
                autoCapitalize="none"
              />

              <TouchableOpacity
                style={[styles.mainBtn, submitting && styles.btnDisabled]}
                onPress={handleSolicitarCodigo}
                disabled={submitting}
                activeOpacity={0.85}>
                {submitting ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <Text style={styles.mainBtnText}>✉ ENVIAR CÓDIGO DE ACCESO (GMAIL)</Text>
                )}
              </TouchableOpacity>
            </View>
          ) : (
            <View>
              <View style={styles.stepHeader}>
                <Text style={styles.stepTitle}>Ingresá el código de 6 dígitos</Text>
                <Text style={styles.stepSub}>Enviado a {email}</Text>
              </View>

              {debugCode ? (
                <View style={styles.demoBox}>
                  <Text style={styles.demoTag}>MODO LOCAL / DEMO</Text>
                  <Text style={styles.demoCode}>{debugCode}</Text>
                </View>
              ) : null}

              <TextInput
                style={styles.pinInput}
                value={codigo}
                onChangeText={setCodigo}
                placeholder="000000"
                placeholderTextColor="#CBD5E1"
                keyboardType="number-pad"
                maxLength={6}
                autoFocus
              />

              <TouchableOpacity
                style={[styles.mainBtn, submitting && styles.btnDisabled]}
                onPress={handleVerificarCodigo}
                disabled={submitting}
                activeOpacity={0.85}>
                {submitting ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <Text style={styles.mainBtnText}>🔑 INICIAR TURNO DE COCINA (KDS)</Text>
                )}
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.backBtn}
                onPress={() => { setStep('email'); setCodigo(''); setErrorMsg(null); }}>
                <Text style={styles.backBtnText}>← Usar otro correo de cocinero</Text>
              </TouchableOpacity>
            </View>
          )}

          {/* Footer Informativo */}
          <View style={styles.footer}>
            <Text style={styles.footerText}>Sistema de Seguridad Mostaza · EPET Nº 7</Text>
            <Text style={styles.footerSub}>Acceso exclusivo para Personal Autorizado de Cocina</Text>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#0F172A',
  },
  scroll: {
    flexGrow: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  card: {
    width: '100%',
    maxWidth: 480,
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 24,
    shadowColor: '#000',
    shadowOpacity: 0.25,
    shadowRadius: 16,
    elevation: 8,
  },
  headerBox: {
    alignItems: 'center',
    marginBottom: 16,
  },
  logoIcon: {
    fontSize: 36,
    marginBottom: 4,
  },
  brandTitle: {
    fontSize: 20,
    fontWeight: '900',
    color: '#A30002',
    letterSpacing: 0.5,
  },
  brandSub: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 2,
  },
  accentBar: {
    width: 60,
    height: 4,
    backgroundColor: '#FBB728',
    borderRadius: 2,
    marginTop: 8,
  },
  errorBox: {
    backgroundColor: '#FEE2E2',
    borderColor: '#EF4444',
    borderWidth: 1,
    borderRadius: 8,
    padding: 10,
    marginBottom: 14,
  },
  errorText: {
    color: '#991B1B',
    fontSize: 12,
    fontWeight: '700',
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#1E293B',
    marginBottom: 8,
  },
  cooksGrid: {
    gap: 8,
    marginBottom: 14,
  },
  cookBtn: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 10,
    backgroundColor: '#F8FAFC',
    borderRadius: 10,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
  },
  cookBtnActive: {
    backgroundColor: '#FEF2F2',
    borderColor: '#A30002',
  },
  cookAvatar: {
    fontSize: 22,
    marginRight: 10,
  },
  cookName: {
    fontSize: 13,
    fontWeight: '800',
    color: '#0F172A',
  },
  cookNameActive: {
    color: '#A30002',
  },
  cookEmail: {
    fontSize: 11,
    color: '#64748B',
  },
  checkIcon: {
    fontSize: 16,
    fontWeight: '900',
    color: '#A30002',
    marginLeft: 8,
  },
  inputLabel: {
    fontSize: 12,
    fontWeight: '700',
    color: '#334155',
    marginBottom: 6,
  },
  input: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: Platform.OS === 'ios' ? 12 : 10,
    fontSize: 14,
    color: '#0F172A',
    marginBottom: 16,
  },
  mainBtn: {
    backgroundColor: '#A30002',
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnDisabled: {
    opacity: 0.6,
  },
  mainBtnText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  stepHeader: {
    alignItems: 'center',
    marginBottom: 16,
  },
  stepTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0F172A',
  },
  stepSub: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 4,
  },
  demoBox: {
    backgroundColor: '#FEF3C7',
    borderColor: '#F59E0B',
    borderWidth: 1,
    borderRadius: 8,
    padding: 8,
    alignItems: 'center',
    marginBottom: 14,
  },
  demoTag: {
    fontSize: 10,
    fontWeight: '900',
    color: '#92400E',
  },
  demoCode: {
    fontSize: 20,
    fontWeight: '900',
    color: '#B45309',
    letterSpacing: 4,
    marginTop: 2,
  },
  pinInput: {
    backgroundColor: '#F8FAFC',
    borderWidth: 2,
    borderColor: '#A30002',
    borderRadius: 12,
    paddingVertical: 12,
    fontSize: 28,
    fontWeight: '900',
    color: '#0F172A',
    textAlign: 'center',
    letterSpacing: 8,
    marginBottom: 16,
  },
  backBtn: {
    alignItems: 'center',
    paddingVertical: 12,
    marginTop: 6,
  },
  backBtnText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#64748B',
  },
  footer: {
    marginTop: 20,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
    paddingTop: 12,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#64748B',
  },
  footerSub: {
    fontSize: 10,
    color: '#94A3B8',
    marginTop: 2,
  },
});
