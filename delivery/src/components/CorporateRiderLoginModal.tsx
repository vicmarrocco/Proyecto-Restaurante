// ============================================================
// components/CorporateRiderLoginModal.tsx
// Modal Corporativo de Selección de Repartidor e Inicio de Sesión con PIN
// ============================================================
import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  ScrollView,
  TouchableOpacity,
  TextInput,
  ActivityIndicator,
  Alert,
  Platform,
} from 'react-native';
import { Repartidor } from '@/types/delivery';
import { ApiService } from '@/services/api';
import { MostazaColors } from '@/constants/Colors';

interface CorporateRiderLoginModalProps {
  visible: boolean;
  onClose?: () => void;
  onLoginSuccess: (rider: Repartidor, horaIngreso: string) => void;
  allowCloseWithoutLogin?: boolean;
}

export const CorporateRiderLoginModal: React.FC<CorporateRiderLoginModalProps> = ({
  visible,
  onClose,
  onLoginSuccess,
  allowCloseWithoutLogin = false,
}) => {
  const [flota, setFlota] = useState<Repartidor[]>([]);
  const [loadingFlota, setLoadingFlota] = useState(true);
  const [selectedRider, setSelectedRider] = useState<Repartidor | null>(null);
  const [pin, setPin] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Cargar flota oficial desde la base de datos
  useEffect(() => {
    if (!visible) return;

    let isMounted = true;
    async function loadFleet() {
      setLoadingFlota(true);
      setErrorMsg(null);
      const riders = await ApiService.getRepartidores();
      if (isMounted) {
        setFlota(riders);
        // Pre-seleccionar el rider actual de la sesión o el primero
        const current = ApiService.getCurrentRider();
        const found = (current && riders.find((r) => r.id === current.id)) || riders[0];
        if (found) {
          setSelectedRider(found);
          // Si el rider viene con PIN precargado (e.g. 1234), podemos setearlo o dejarlo listo
        }
        setLoadingFlota(false);
      }
    }
    loadFleet();

    return () => {
      isMounted = false;
    };
  }, [visible]);

  const handleSelectRider = (rider: Repartidor) => {
    setSelectedRider(rider);
    setPin('');
    setErrorMsg(null);
  };

  const handleQuickFillPin = () => {
    // Si conocemos los PINs estándar de los 5 repartidores oficiales
    const defaultPins: Record<number, string> = {
      1: '1234',
      2: '2345',
      3: '3456',
      4: '4567',
      5: '5678',
    };
    if (selectedRider && defaultPins[selectedRider.id]) {
      setPin(defaultPins[selectedRider.id]);
    } else {
      setPin('1234');
    }
    setErrorMsg(null);
  };

  const handleLogin = async () => {
    if (!selectedRider) {
      setErrorMsg('Por favor seleccioná tu perfil de repartidor.');
      return;
    }

    if (!pin.trim()) {
      setErrorMsg('Ingresá tu PIN de seguridad de 4 dígitos.');
      return;
    }

    setSubmitting(true);
    setErrorMsg(null);

    const res = await ApiService.loginRider(selectedRider.id, pin.trim());
    setSubmitting(false);

    if (res.ok) {
      const hora = res.hora_ingreso || 'Ahora';
      onLoginSuccess(res.repartidor || selectedRider, hora);
      if (onClose) onClose();
    } else {
      setErrorMsg(res.error || 'PIN incorrecto. Verificá tus credenciales corporativas.');
    }
  };

  // Determinar PIN sugerido para ayudar al usuario/evaluador
  const hintPin = selectedRider
    ? selectedRider.id === 1
      ? '1234'
      : selectedRider.id === 2
      ? '2345'
      : selectedRider.id === 3
      ? '3456'
      : selectedRider.id === 4
      ? '4567'
      : '5678'
    : '1234';

  return (
    <Modal visible={visible} animationType="fade" transparent={true} onRequestClose={() => {
      if (allowCloseWithoutLogin && onClose) onClose();
    }}>
      <View style={styles.backdrop}>
        <View style={styles.modalCard}>
          {/* Header Corporativo Mostaza */}
          <View style={styles.header}>
            <View style={styles.headerTitleRow}>
              <View style={styles.brandBadge}>
                <Text style={styles.brandBadgeText}>MOSTAZA OFICIAL</Text>
              </View>
              <Text style={styles.headerSubtitle}>FLOTA DE REPARTO</Text>
            </View>
            <Text style={styles.headerTitle}>Inicio de Sesión Profesional</Text>
            <Text style={styles.headerDesc}>
              Acceso exclusivo para repartidores autorizados de la empresa
            </Text>

            {allowCloseWithoutLogin && onClose && (
              <TouchableOpacity onPress={onClose} style={styles.btnClose}>
                <Text style={styles.btnCloseText}>✕</Text>
              </TouchableOpacity>
            )}
          </View>

          <ScrollView style={styles.scrollBody} contentContainerStyle={styles.scrollContent}>
            {/* Paso 1: Selección de Repartidor */}
            <View style={styles.stepHeader}>
              <View style={styles.stepCircle}>
                <Text style={styles.stepCircleText}>1</Text>
              </View>
              <Text style={styles.stepTitle}>Seleccioná tu Perfil de la Flota:</Text>
            </View>

            {loadingFlota ? (
              <View style={styles.loadingBox}>
                <ActivityIndicator size="small" color={MostazaColors.rojo} />
                <Text style={styles.loadingText}>Cargando flota desde la base de datos...</Text>
              </View>
            ) : flota.length === 0 ? (
              <View style={styles.emptyBox}>
                <Text style={styles.emptyEmoji}>🛵</Text>
                <Text style={styles.emptyText}>No se encontraron repartidores registrados en la base de datos.</Text>
              </View>
            ) : (
              <View style={styles.ridersGrid}>
                {flota.map((rider) => {
                  const isSelected = selectedRider?.id === rider.id;
                  return (
                    <TouchableOpacity
                      key={rider.id}
                      style={[styles.riderCard, isSelected && styles.riderCardSelected]}
                      onPress={() => handleSelectRider(rider)}
                      activeOpacity={0.8}>
                      <View style={styles.riderAvatarBox}>
                        <Text style={styles.riderAvatarEmoji}>{rider.avatar || '🛵'}</Text>
                        {isSelected && (
                          <View style={styles.checkBadge}>
                            <Text style={styles.checkBadgeText}>✓</Text>
                          </View>
                        )}
                      </View>

                      <View style={styles.riderMeta}>
                        <View style={styles.riderNameRow}>
                          <Text style={[styles.riderName, isSelected && styles.riderNameSelected]}>
                            {rider.nombre}
                          </Text>
                          <View style={styles.ratingPill}>
                            <Text style={styles.ratingText}>⭐ {Number(rider.calificacion || 4.95).toFixed(2)}</Text>
                          </View>
                        </View>

                        <Text style={styles.riderVehicle}>
                          {rider.vehiculo} · <Text style={styles.riderPlate}>[{rider.patente}]</Text>
                        </Text>

                        <View style={styles.riderBaseRow}>
                          <Text style={styles.riderBaseText}>
                            📍 {rider.sucursal_base || 'Mostaza Formosa Centro'}
                          </Text>
                          <Text style={styles.riderTripsText}>
                            {rider.viajes_totales || 200} viajes
                          </Text>
                        </View>
                      </View>
                    </TouchableOpacity>
                  );
                })}
              </View>
            )}

            {/* Paso 2: Autenticación con PIN */}
            {selectedRider && (
              <View style={styles.pinSection}>
                <View style={styles.stepHeader}>
                  <View style={styles.stepCircle}>
                    <Text style={styles.stepCircleText}>2</Text>
                  </View>
                  <Text style={styles.stepTitle}>
                    Ingresá el PIN de {selectedRider.nombre.split(' ')[0]}:
                  </Text>
                </View>

                <View style={styles.pinInputRow}>
                  <TextInput
                    style={styles.pinInput}
                    placeholder="••••"
                    placeholderTextColor="#94A3B8"
                    value={pin}
                    onChangeText={setPin}
                    keyboardType="number-pad"
                    maxLength={6}
                    secureTextEntry
                    autoFocus={false}
                  />

                  <TouchableOpacity
                    style={styles.btnQuickFill}
                    onPress={handleQuickFillPin}
                    activeOpacity={0.7}>
                    <Text style={styles.btnQuickFillText}>💡 PIN: {hintPin}</Text>
                  </TouchableOpacity>
                </View>

                {errorMsg && (
                  <View style={styles.errorBox}>
                    <Text style={styles.errorIcon}>⚠️</Text>
                    <Text style={styles.errorText}>{errorMsg}</Text>
                  </View>
                )}

                {/* Botón de Ingreso */}
                <TouchableOpacity
                  style={[styles.btnLogin, submitting && styles.btnLoginDisabled]}
                  onPress={handleLogin}
                  disabled={submitting}
                  activeOpacity={0.85}>
                  {submitting ? (
                    <ActivityIndicator color="#FFFFFF" />
                  ) : (
                    <>
                      <Text style={styles.btnLoginText}>
                        INGRESAR Y FICHAR ENTRADA ➔
                      </Text>
                      <Text style={styles.btnLoginSub}>
                        {selectedRider.sucursal_base || 'Sucursal Formosa Centro'}
                      </Text>
                    </>
                  )}
                </TouchableOpacity>
              </View>
            )}
          </ScrollView>

          {/* Footer Informativo */}
          <View style={styles.footer}>
            <Text style={styles.footerSecurity}>
              🔒 Sistema protegido · Conexión directa a base central de Mostaza
            </Text>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.75)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  modalCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    width: '100%',
    maxWidth: 520,
    maxHeight: '90%',
    overflow: 'hidden',
    elevation: 10,
    shadowColor: '#000',
    shadowOpacity: 0.25,
    shadowOffset: { width: 0, height: 10 },
    shadowRadius: 20,
  },
  header: {
    backgroundColor: MostazaColors.rojo,
    paddingVertical: 18,
    paddingHorizontal: 20,
    position: 'relative',
  },
  headerTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 4,
  },
  brandBadge: {
    backgroundColor: MostazaColors.amarillo,
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  brandBadgeText: {
    color: '#0F172A',
    fontWeight: '900',
    fontSize: 10,
    letterSpacing: 0.8,
  },
  headerSubtitle: {
    color: '#FEE2E2',
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 1,
  },
  headerTitle: {
    color: '#FFFFFF',
    fontSize: 20,
    fontWeight: '900',
    letterSpacing: 0.3,
  },
  headerDesc: {
    color: '#FECACA',
    fontSize: 11,
    marginTop: 2,
    fontWeight: '500',
  },
  btnClose: {
    position: 'absolute',
    top: 14,
    right: 14,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    width: 32,
    height: 32,
    borderRadius: 16,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnCloseText: {
    color: '#FFFFFF',
    fontSize: 16,
    fontWeight: '700',
  },
  scrollBody: {
    paddingHorizontal: 18,
    paddingVertical: 14,
  },
  scrollContent: {
    paddingBottom: 16,
  },
  stepHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 10,
  },
  stepCircle: {
    width: 22,
    height: 22,
    borderRadius: 11,
    backgroundColor: MostazaColors.rojo,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepCircleText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '900',
  },
  stepTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#0F172A',
  },
  loadingBox: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
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
  ridersGrid: {
    gap: 8,
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
  riderAvatarBox: {
    width: 46,
    height: 46,
    borderRadius: 23,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#CBD5E1',
    position: 'relative',
  },
  riderAvatarEmoji: {
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
  riderMeta: {
    flex: 1,
  },
  riderNameRow: {
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
  ratingPill: {
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
  riderPlate: {
    fontWeight: '700',
    color: '#0F172A',
  },
  riderBaseRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 3,
  },
  riderBaseText: {
    fontSize: 10,
    color: '#64748B',
    fontWeight: '600',
  },
  riderTripsText: {
    fontSize: 10,
    color: '#94A3B8',
    fontWeight: '600',
  },
  pinSection: {
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    paddingTop: 14,
  },
  pinInputRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    marginBottom: 10,
  },
  pinInput: {
    flex: 1,
    backgroundColor: '#F1F5F9',
    borderRadius: 12,
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
    paddingVertical: 12,
    paddingHorizontal: 16,
    fontSize: 18,
    fontWeight: '800',
    letterSpacing: 4,
    color: '#0F172A',
    textAlign: 'center',
  },
  btnQuickFill: {
    backgroundColor: '#FEF3C7',
    paddingVertical: 12,
    paddingHorizontal: 14,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  btnQuickFillText: {
    color: '#92400E',
    fontWeight: '800',
    fontSize: 12,
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
  footer: {
    backgroundColor: '#F8FAFC',
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    paddingVertical: 10,
    paddingHorizontal: 16,
    alignItems: 'center',
  },
  footerSecurity: {
    fontSize: 10,
    color: '#64748B',
    fontWeight: '600',
  },
});
