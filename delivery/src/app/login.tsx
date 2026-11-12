// ============================================================
// app/login.tsx
// Pantalla de Inicio de Sesión Oficial para Repartidores de Mostaza
// ============================================================
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { Repartidor } from '@/types/delivery';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';

export default function LoginScreen() {
  const router = useRouter();
  const { loginConCodigo, isAuthenticated, isLoading } = useAuth();

  const [flota, setFlota] = useState<Repartidor[]>([]);
  const [loadingFlota, setLoadingFlota] = useState(true);
  const [selectedRider, setSelectedRider] = useState<Repartidor | null>(null);
  
  // Flujo OTP de 2 pasos por Gmail
  const [step, setStep] = useState<'email' | 'codigo'>('email');
  const [email, setEmail] = useState('');
  const [codigo, setCodigo] = useState('');
  const [debugCode, setDebugCode] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Redirección diferida y segura sin early return
  useEffect(() => {
    if (!isLoading && isAuthenticated) {
      router.replace('/');
    }
  }, [isLoading, isAuthenticated]);

  // Cargar flota oficial de empleados repartidores
  useEffect(() => {
    let isMounted = true;
    async function loadFleet() {
      setLoadingFlota(true);
      setErrorMsg(null);
      const riders = await ApiService.getRepartidores();
      if (isMounted) {
        setFlota(riders);
        if (riders.length > 0) {
          setSelectedRider(riders[0]);
          setEmail(riders[0].email || 'delivery1@mostaza.com.ar');
        }
        setLoadingFlota(false);
      }
    }
    loadFleet();
    return () => {
      isMounted = false;
    };
  }, []);

  const handleSelectRider = (rider: Repartidor) => {
    setSelectedRider(rider);
    setEmail(rider.email || 'delivery1@mostaza.com.ar');
    setErrorMsg(null);
  };

  // Paso 1: Solicitar código OTP por Gmail
  const handleSolicitarCodigo = async () => {
    if (!email.trim() || !email.includes('@')) {
      setErrorMsg('Ingresá un correo electrónico corporativo válido.');
      return;
    }

    setSubmitting(true);
    setErrorMsg(null);

    const res = await ApiService.solicitarCodigoGmail(email.trim());
    setSubmitting(false);

    if (res.ok) {
      setDebugCode(res.debug_code || null);
      setStep('codigo');
      setCodigo(''); // Input vacío: el repartidor debe digitarlo
    } else {
      setErrorMsg(res.error || 'No se pudo enviar el código de seguridad.');
    }
  };

  // Paso 2: Verificar código OTP e iniciar turno
  const handleVerificarCodigo = async () => {
    const cleanCode = codigo.replace(/\D/g, '').trim();
    if (!cleanCode || cleanCode.length < 4) {
      setErrorMsg('Ingresá el código de 6 dígitos que recibiste por correo.');
      return;
    }

    setSubmitting(true);
    setErrorMsg(null);

    const res = await loginConCodigo(email.trim(), cleanCode);
    setSubmitting(false);

    if (res.ok) {
      router.replace('/');
    } else {
      setErrorMsg(res.error || 'Código incorrecto o expirado.');
    }
  };

  // Si aún está verificando la sesión inicial
  if (isLoading) {
    return (
      <View style={[styles.safeArea, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color={MostazaColors.rojo} />
      </View>
    );
  }

  return (
    <SafeAreaView style={styles.safeArea}>
      <ScrollView contentContainerStyle={styles.scrollContent}>
        {/* Banner Superior Corporativo Mostaza */}
        <View style={styles.header}>
          <View style={styles.brandRow}>
            <Text style={styles.brandLogo}>MOSTAZA</Text>
            <View style={styles.brandTag}>
              <Text style={styles.brandTagText}>FLOTA OFICIAL</Text>
            </View>
          </View>
          <Text style={styles.headerTitle}>Portal de Acceso para Repartidores</Text>
          <Text style={styles.headerSubtitle}>
            Autenticación unificada por código de seguridad enviado a tu Gmail
          </Text>
        </View>

        {/* Tarjeta Principal de Login */}
        <View style={styles.cardContainer}>
          {step === 'email' ? (
            <>
              {/* Paso 1: Selección de Repartidor de la Nómina */}
              <View style={styles.sectionHeader}>
                <View style={styles.stepBadge}>
                  <Text style={styles.stepBadgeText}>1</Text>
                </View>
                <Text style={styles.sectionTitle}>SELECCIONÁ TU PERFIL O INGRESÁ TU GMAIL:</Text>
              </View>

              {loadingFlota ? (
                <View style={styles.loadingBox}>
                  <ActivityIndicator size="large" color={MostazaColors.rojo} />
                  <Text style={styles.loadingText}>Cargando nómina de repartidores...</Text>
                </View>
              ) : flota.length === 0 ? (
                <View style={styles.emptyBox}>
                  <Text style={styles.emptyEmoji}>⚠️</Text>
                  <Text style={styles.emptyText}>
                    No se encontró personal de delivery en la base de datos.
                  </Text>
                </View>
              ) : (
                <View style={styles.fleetList}>
                  {flota.map((r) => {
                    const isSelected = selectedRider?.id === r.id;
                    return (
                      <TouchableOpacity
                        key={r.id}
                        style={[styles.riderCard, isSelected && styles.riderCardSelected]}
                        onPress={() => handleSelectRider(r)}
                        activeOpacity={0.8}>
                        <View style={styles.avatarBox}>
                          <Text style={styles.avatarEmoji}>{r.avatar || '🛵'}</Text>
                          {isSelected && (
                            <View style={styles.checkBadge}>
                              <Text style={styles.checkBadgeText}>✓</Text>
                            </View>
                          )}
                        </View>

                        <View style={styles.riderInfo}>
                          <View style={styles.riderTitleRow}>
                            <Text style={[styles.riderName, isSelected && styles.riderNameSelected]}>
                              {r.nombre}
                            </Text>
                            <View style={styles.ratingBadge}>
                              <Text style={styles.ratingText}>⭐ {Number(r.calificacion || 4.95).toFixed(2)}</Text>
                            </View>
                          </View>

                          <Text style={styles.riderVehicle}>
                            {r.vehiculo} · <Text style={styles.plateText}>[{r.patente}]</Text>
                          </Text>

                          <View style={styles.riderBaseRow}>
                            <Text style={styles.riderBaseText}>
                              ✉️ {r.email || 'delivery@mostaza.com.ar'}
                            </Text>
                            <Text style={styles.tripsText}>{r.viajes_totales || 0} viajes</Text>
                          </View>
                        </View>
                      </TouchableOpacity>
                    );
                  })}
                </View>
              )}

              {/* Input de Correo Corporativo */}
              <View style={styles.pinSection}>
                <Text style={styles.inputLabel}>CORREO ELECTRÓNICO (GMAIL CORPORATIVO):</Text>
                <TextInput
                  style={styles.emailInput}
                  placeholder="ej: delivery1@mostaza.com.ar"
                  placeholderTextColor="#94A3B8"
                  value={email}
                  onChangeText={setEmail}
                  autoCapitalize="none"
                  keyboardType="email-address"
                />

                {errorMsg && (
                  <View style={styles.errorBox}>
                    <Text style={styles.errorIcon}>⚠️</Text>
                    <Text style={styles.errorText}>{errorMsg}</Text>
                  </View>
                )}

                <TouchableOpacity
                  style={[styles.btnLogin, submitting && styles.btnLoginDisabled]}
                  onPress={handleSolicitarCodigo}
                  disabled={submitting}
                  activeOpacity={0.85}>
                  {submitting ? (
                    <ActivityIndicator color="#FFFFFF" />
                  ) : (
                    <>
                      <Text style={styles.btnLoginText}>
                        ENVIAR CÓDIGO POR GMAIL ➔
                      </Text>
                      <Text style={styles.btnLoginSub}>
                        Recibirás un token de seguridad de 6 dígitos en tu bandeja
                      </Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>
            </>
          ) : (
            <>
              {/* Paso 2: Validación del Código de 6 Dígitos */}
              <View style={styles.sectionHeader}>
                <View style={[styles.stepBadge, { backgroundColor: '#10B981' }]}>
                  <Text style={styles.stepBadgeText}>2</Text>
                </View>
                <Text style={styles.sectionTitle}>INGRESÁ EL CÓDIGO DE SEGURIDAD:</Text>
              </View>

              <View style={styles.emailBadgeBox}>
                <Text style={styles.emailBadgeText}>✉️ Código enviado a:</Text>
                <Text style={styles.emailTargetText}>{email}</Text>
              </View>

              {debugCode ? (
                <View style={styles.demoBox}>
                  <Text style={styles.demoTag}>CÓDIGO DE SEGURIDAD (GMAIL / DEMO):</Text>
                  <Text style={styles.demoCode}>{debugCode}</Text>
                  <Text style={styles.demoSub}>
                    Ingresá manualmente este código de 6 dígitos en el casillero inferior
                  </Text>
                </View>
              ) : null}

              <Text style={{ textAlign: 'center', color: '#64748B', fontSize: 13, marginVertical: 8, lineHeight: 18 }}>
                Revisá tu bandeja de entrada en Gmail e ingresá los 6 dígitos de seguridad para comenzar tu jornada:
              </Text>

              <TextInput
                style={styles.pinInput}
                placeholder="000000"
                placeholderTextColor="#94A3B8"
                value={codigo}
                onChangeText={(val) => setCodigo(val.replace(/\D/g, ''))}
                keyboardType="numeric"
                maxLength={6}
                autoFocus
              />

              {errorMsg && (
                <View style={styles.errorBox}>
                  <Text style={styles.errorIcon}>⚠️</Text>
                  <Text style={styles.errorText}>{errorMsg}</Text>
                </View>
              )}

              <TouchableOpacity
                style={[styles.btnLogin, { backgroundColor: '#059669' }, submitting && styles.btnLoginDisabled]}
                onPress={handleVerificarCodigo}
                disabled={submitting}
                activeOpacity={0.85}>
                {submitting ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <>
                    <Text style={styles.btnLoginText}>
                      ✓ VALIDAR CÓDIGO E INICIAR TURNO
                    </Text>
                    <Text style={styles.btnLoginSub}>
                      Fichar entrada y acceder al panel de pedidos
                    </Text>
                  </>
                )}
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.btnBack}
                onPress={() => {
                  setStep('email');
                  setCodigo('');
                  setErrorMsg(null);
                }}>
                <Text style={styles.btnBackText}>← Volver y cambiar correo electrónico</Text>
              </TouchableOpacity>
            </>
          )}
        </View>

        {/* Footer Institucional */}
        <View style={styles.footer}>
          <Text style={styles.footerText}>
            🔒 Mostaza Argentina S.A. · EPET Nº 7 · Sistema Oficial de Auditoría y Flota
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F1F5F9',
  },
  scrollContent: {
    paddingBottom: 40,
  },
  header: {
    backgroundColor: MostazaColors.rojo,
    paddingTop: 30,
    paddingBottom: 24,
    paddingHorizontal: 20,
    alignItems: 'center',
    borderBottomLeftRadius: 24,
    borderBottomRightRadius: 24,
    elevation: 4,
    shadowColor: '#000',
    shadowOpacity: 0.15,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 8,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 8,
  },
  brandLogo: {
    color: '#FFFFFF',
    fontSize: 26,
    fontWeight: '900',
    letterSpacing: 2,
  },
  brandTag: {
    backgroundColor: MostazaColors.amarillo,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  brandTagText: {
    color: '#0F172A',
    fontSize: 10,
    fontWeight: '900',
    letterSpacing: 0.8,
  },
  headerTitle: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: '800',
    textAlign: 'center',
    marginTop: 4,
  },
  headerSubtitle: {
    color: '#FECACA',
    fontSize: 12,
    textAlign: 'center',
    marginTop: 4,
    maxWidth: 320,
  },
  cardContainer: {
    backgroundColor: '#FFFFFF',
    marginHorizontal: 16,
    marginTop: -10,
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    elevation: 4,
    shadowColor: '#000',
    shadowOpacity: 0.08,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 10,
  },
  sectionHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 12,
  },
  stepBadge: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: MostazaColors.rojo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepBadgeText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '900',
  },
  sectionTitle: {
    fontSize: 12,
    fontWeight: '900',
    color: '#0F172A',
    letterSpacing: 0.5,
  },
  loadingBox: {
    padding: 30,
    alignItems: 'center',
    gap: 10,
  },
  loadingText: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '600',
  },
  emptyBox: {
    padding: 24,
    alignItems: 'center',
  },
  emptyEmoji: {
    fontSize: 32,
    marginBottom: 6,
  },
  emptyText: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
  },
  fleetList: {
    gap: 10,
    marginBottom: 16,
  },
  riderCard: {
    flexDirection: 'row',
    alignItems: 'center',
    padding: 12,
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    gap: 12,
  },
  riderCardSelected: {
    backgroundColor: '#FEF2F2',
    borderColor: MostazaColors.rojo,
  },
  avatarBox: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
    position: 'relative',
  },
  avatarEmoji: {
    fontSize: 24,
  },
  checkBadge: {
    position: 'absolute',
    bottom: -2,
    right: -2,
    backgroundColor: MostazaColors.rojo,
    width: 18,
    height: 18,
    borderRadius: 9,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: '#FFFFFF',
  },
  checkBadgeText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: '900',
  },
  riderInfo: {
    flex: 1,
  },
  riderTitleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  riderName: {
    fontSize: 14,
    fontWeight: '800',
    color: '#1E293B',
  },
  riderNameSelected: {
    color: MostazaColors.rojo,
  },
  ratingBadge: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 6,
  },
  ratingText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#92400E',
  },
  riderVehicle: {
    fontSize: 11,
    color: '#475569',
    marginTop: 2,
    fontWeight: '500',
  },
  plateText: {
    fontWeight: '700',
    color: '#0F172A',
  },
  riderBaseRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 4,
  },
  riderBaseText: {
    fontSize: 10,
    color: '#64748B',
    fontWeight: '600',
  },
  tripsText: {
    fontSize: 10,
    color: '#94A3B8',
    fontWeight: '600',
  },
  pinSection: {
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    paddingTop: 16,
  },
  pinInput: {
    width: '100%',
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    borderWidth: 2,
    borderColor: '#CBD5E1',
    paddingVertical: 14,
    paddingHorizontal: 16,
    fontSize: 24,
    fontWeight: '900',
    letterSpacing: 6,
    color: '#0F172A',
    textAlign: 'center',
    marginBottom: 12,
  },
  demoBox: {
    backgroundColor: '#FEF3C7',
    borderWidth: 1.5,
    borderColor: '#F59E0B',
    borderRadius: 14,
    padding: 12,
    alignItems: 'center',
    marginVertical: 10,
  },
  demoTag: {
    fontSize: 10,
    fontWeight: '900',
    color: '#92400E',
    letterSpacing: 0.8,
    marginBottom: 4,
    textAlign: 'center',
  },
  demoCode: {
    fontSize: 26,
    fontWeight: '900',
    color: '#B45309',
    letterSpacing: 6,
    marginVertical: 2,
  },
  demoSub: {
    fontSize: 11,
    fontWeight: '600',
    color: '#78350F',
    textAlign: 'center',
    marginTop: 2,
  },
  errorBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#FEE2E2',
    padding: 10,
    borderRadius: 10,
    gap: 8,
    marginBottom: 10,
  },
  errorIcon: {
    fontSize: 14,
  },
  errorText: {
    color: '#991B1B',
    fontSize: 11,
    fontWeight: '700',
    flex: 1,
  },
  btnLogin: {
    backgroundColor: MostazaColors.rojo,
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderRadius: 14,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 3,
    shadowColor: MostazaColors.rojo,
    shadowOpacity: 0.3,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 8,
    marginTop: 4,
  },
  btnLoginDisabled: {
    opacity: 0.6,
  },
  btnLoginText: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '900',
    letterSpacing: 0.8,
  },
  btnLoginSub: {
    color: '#FECACA',
    fontSize: 10,
    fontWeight: '600',
    marginTop: 2,
  },
  inputLabel: {
    fontSize: 12,
    fontWeight: '800',
    color: '#334155',
    marginBottom: 6,
  },
  emailInput: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 12,
    fontSize: 14,
    color: '#1E293B',
    marginBottom: 12,
  },
  emailBadgeBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    backgroundColor: '#F1F5F9',
    padding: 10,
    borderRadius: 10,
    marginBottom: 14,
  },
  emailBadgeText: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '600',
  },
  emailTargetText: {
    fontSize: 12,
    color: '#0F172A',
    fontWeight: '800',
  },
  btnBack: {
    paddingVertical: 12,
    alignItems: 'center',
    marginTop: 10,
  },
  btnBackText: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '700',
  },
  footer: {
    marginTop: 20,
    paddingHorizontal: 20,
    alignItems: 'center',
  },
  footerText: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
    textAlign: 'center',
  },
});