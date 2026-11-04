// ============================================================
// app/perfil.tsx
// Panel de Turno y Cierre de Sesión para Repartidores Oficiales Mostaza
// ============================================================
import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';

export default function ProfileScreen() {
  const router = useRouter();
  const { rider, logout, horaIngreso } = useAuth();

  const handleCerrarTurno = () => {
    const confirmar = async () => {
      await logout();
      router.replace('/login');
    };

    if (Platform.OS === 'web') {
      if (window.confirm('¿Deseás concluir tu turno de entrega de hoy y registrar tu salida?')) {
        confirmar();
      }
    } else {
      Alert.alert(
        'Cerrar Sesión y Turno Oficial',
        `¿Confirmás el cierre de turno para ${rider?.nombre || 'el repartidor actual'}?\n\nSe registrará tu hora de salida en la auditoría central y tu estado pasará a Desconectado.`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: 'Sí, Salir y Concluir Turno',
            style: 'destructive',
            onPress: confirmar,
          },
        ]
      );
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>👤 MI TURNO Y SALIDA</Text>
        <Text style={styles.headerSub}>Control de Jornada Laboral y Conexión de Terminal</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Tarjeta de Repartidor Autenticado */}
        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardTitle}>🛵 REPARTIDOR EN TURNO</Text>
            <View style={styles.badgeTurnoOn}>
              <Text style={styles.badgeTurnoTxt}>● TURNO ACTIVO</Text>
            </View>
          </View>

          <View style={styles.profileRow}>
            <View style={styles.avatarBox}>
              <Text style={styles.profileAvatar}>{rider?.avatar || '🛵'}</Text>
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={styles.profileName}>{rider?.nombre || 'Repartidor Mostaza'}</Text>
              <Text style={styles.profileDetail}>DNI: {rider?.dni || '-'} · Tel: {rider?.telefono || '-'}</Text>
              <Text style={styles.profileDetail}>
                Vehículo: {rider?.vehiculo || 'Moto'} ({rider?.patente || 'S/D'})
              </Text>
              <Text style={styles.profileDetail}>
                Base: {rider?.sucursal_base || 'Mostaza Formosa Centro'}
              </Text>
              {horaIngreso ? (
                <Text style={styles.profileHora}>⏱️ Ingreso registrado: {horaIngreso} hs</Text>
              ) : null}
            </View>
          </View>

          {/* Métricas de Rendimiento */}
          <View style={styles.statsRow}>
            <View style={styles.statBox}>
              <Text style={styles.statVal}>⭐ {rider?.calificacion ? Number(rider.calificacion).toFixed(1) : '5.0'}</Text>
              <Text style={styles.statLbl}>CALIFICACIÓN</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statBox}>
              <Text style={styles.statVal}>🛵 {rider?.viajes_totales || 0}</Text>
              <Text style={styles.statLbl}>VIAJES TOTALES</Text>
            </View>
          </View>

          {/* Botón Principal para Concluir Turno */}
          <TouchableOpacity
            style={styles.btnTurnoSalir}
            onPress={handleCerrarTurno}
            activeOpacity={0.85}>
            <Text style={styles.btnTurnoTxt}>🚪 Registrar Salida y Concluir Turno</Text>
          </TouchableOpacity>
        </View>

        {/* Políticas de Servicio Mostaza */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>📋 Normas de Seguridad en Reparto</Text>
          <Text style={styles.policyItem}>✅ Uso obligatorio de casco y chaleco reflectivo.</Text>
          <Text style={styles.policyItem}>✅ Caja térmica sanitizada y cerrada herméticamente.</Text>
          <Text style={styles.policyItem}>✅ Rendición de efectivo a Caja Central al finalizar la jornada.</Text>
          <Text style={styles.policyItem}>✅ Registro estricto de tiempos de despacho y entrega con GPS.</Text>
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
  cardHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  cardTitle: { fontSize: 11, fontWeight: '800', color: '#64748B', letterSpacing: 0.5 },
  badgeTurnoOn: { backgroundColor: '#DCFCE7', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 12 },
  badgeTurnoTxt: { color: '#166534', fontSize: 10, fontWeight: '900' },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 10,
  },
  avatarBox: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#FEF2F2',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: '#FECACA',
  },
  profileAvatar: {
    fontSize: 28,
  },
  profileName: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  profileDetail: { fontSize: 11, color: '#64748B', marginTop: 1, fontWeight: '500' },
  profileHora: { fontSize: 11, color: '#2563EB', fontWeight: '700', marginTop: 2 },
  statsRow: {
    flexDirection: 'row',
    backgroundColor: '#F8FAFC',
    borderRadius: 10,
    paddingVertical: 10,
    marginVertical: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  statBox: {
    flex: 1,
    alignItems: 'center',
  },
  statDivider: {
    width: 1,
    backgroundColor: '#E2E8F0',
  },
  statVal: {
    fontSize: 14,
    fontWeight: '900',
    color: '#0F172A',
  },
  statLbl: {
    fontSize: 9,
    fontWeight: '800',
    color: '#64748B',
    marginTop: 2,
    letterSpacing: 0.5,
  },
  btnTurnoSalir: {
    backgroundColor: '#DC2626',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 4,
  },
  btnTurnoTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 13 },
  policyItem: {
    fontSize: 12,
    color: '#334155',
    lineHeight: 22,
    fontWeight: '500',
  },
});
