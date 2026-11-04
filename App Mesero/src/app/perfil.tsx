import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';

export default function PerfilScreen() {
  const { mesero, logout, horaIngreso } = useAuth();

  const handleCerrarTurno = () => {
    Alert.alert(
      'Cerrar Turno de Salón',
      `¿Confirmás el cierre de turno para ${mesero?.nombre_completo || mesero?.nombre || 'el mesero actual'}? Se registrará tu hora de salida.`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Cerrar Turno',
          style: 'destructive',
          onPress: async () => {
            await logout();
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>⚙️ AJUSTES Y TURNO MESERO</Text>
        <Text style={styles.headerSub}>Control de Asistencia y Terminal de Salón</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Tarjeta de Mesero Autenticado */}
        <View style={styles.card}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={styles.cardTitle}>👤 MESERO EN TURNO</Text>
            <View style={styles.badgeTurnoOn}>
              <Text style={styles.badgeTurnoTxt}>● SESIÓN ACTIVA</Text>
            </View>
          </View>

          <View style={styles.profileRow}>
            <Text style={styles.profileAvatar}>{mesero?.avatar || '🤵'}</Text>
            <View style={{ flex: 1 }}>
              <Text style={styles.profileName}>{mesero?.nombre_completo || mesero?.nombre || 'Mesero Mostaza'}</Text>
              <Text style={styles.profileEmail}>{mesero?.email || 'mesero@mostaza.com.ar'}</Text>
              <Text style={styles.profileRole}>
                Rol: {mesero?.rol_nombre || 'Mozo / Mesero'} · P.V. Salón Principal
              </Text>
              {horaIngreso ? (
                <Text style={styles.profileHora}>⏱️ Ingreso registrado: {horaIngreso} hs</Text>
              ) : null}
            </View>
          </View>

          <TouchableOpacity
            style={styles.btnTurnoSalir}
            onPress={handleCerrarTurno}
            activeOpacity={0.85}>
            <Text style={styles.btnTurnoTxt}>🚪 Cerrar Turno y Salir</Text>
          </TouchableOpacity>
        </View>

        {/* Protocolos de Atención */}
        <Text style={styles.secTitle}>📋 Normas de Servicio y Atención en Salón</Text>
        <View style={styles.card}>
          <Text style={styles.protocolText}>
            1. <Text style={styles.protocolBold}>Tiempo de Acercamiento:</Text> Contactar a los clientes en la mesa en menos de 2 minutos.{'\n'}
            2. <Text style={styles.protocolBold}>Toma de Comanda:</Text> Verificar alergias o pedidos especiales antes de marchar a cocina.{'\n'}
            3. <Text style={styles.protocolBold}>Salida de Platos:</Text> Retirar bandejas del pase de cocina de inmediato al recibir el aviso.{'\n'}
            4. <Text style={styles.protocolBold}>Auditoría de Pedidos:</Text> Todo pedido enviado queda auditado con tu usuario de mesero en el sistema central.
          </Text>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F8FAFC' },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    elevation: 2,
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2, fontWeight: '600' },
  scroll: { padding: 16, paddingBottom: 36 },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
    elevation: 1,
  },
  cardTitle: { fontSize: 11, fontWeight: '800', color: '#64748B', letterSpacing: 0.5 },
  badgeTurnoOn: { backgroundColor: '#DCFCE7', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 12 },
  badgeTurnoTxt: { color: '#166534', fontSize: 10, fontWeight: '900' },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 14,
    gap: 12,
  },
  profileAvatar: {
    fontSize: 40,
    backgroundColor: '#F1F5F9',
    padding: 10,
    borderRadius: 24,
    overflow: 'hidden',
    textAlign: 'center',
  },
  profileName: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  profileEmail: { fontSize: 12, color: '#64748B', marginTop: 1 },
  profileRole: {
    fontSize: 11,
    fontWeight: '700',
    color: '#059669',
    marginTop: 3,
  },
  profileHora: {
    fontSize: 11,
    color: '#3B82F6',
    fontWeight: '700',
    marginTop: 2,
  },
  btnTurnoSalir: {
    backgroundColor: '#DC2626',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 6,
  },
  btnTurnoTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 13 },
  secTitle: { fontSize: 13, fontWeight: '800', color: '#0F172A', marginBottom: 8 },
  protocolText: { fontSize: 12, color: '#334155', lineHeight: 20 },
  protocolBold: { fontWeight: '800', color: MostazaColors.rojo },
});
