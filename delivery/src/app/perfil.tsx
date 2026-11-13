// ============================================================
// app/perfil.tsx
// Panel de Turno, Rendición de Caja y Cierre de Sesión para Repartidores Oficiales Mostaza
// ============================================================
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
  Platform,
  RefreshControl,
  ActivityIndicator,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';
import { ApiService } from '@/services/api';
import { EstadisticasRider } from '@/types/delivery';

export default function ProfileScreen() {
  const router = useRouter();
  const { rider, logout, horaIngreso } = useAuth();
  const [stats, setStats] = useState<EstadisticasRider | null>(null);
  const [loadingStats, setLoadingStats] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const cargarStats = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    try {
      if (rider?.nombre) {
        const res = await ApiService.getEstadisticas();
        if (res) {
          setStats(res);
        }
      }
    } catch (e) {
      // Ignorar error de red silencioso
    } finally {
      setLoadingStats(false);
      if (isRefresh) setRefreshing(false);
    }
  }, [rider?.nombre]);

  useEffect(() => {
    cargarStats();
  }, [cargarStats]);

  const handleRendirYCerrarTurno = () => {
    const efectivo = stats?.efectivo_a_rendir_formateado || '$0';
    const montoNum = stats?.efectivo_a_rendir || 0;

    const mensaje =
      montoNum > 0
        ? `¿Confirmás la entrega de ${efectivo} en efectivo a Caja Central de Mostaza y el cierre de tu turno?\n\nAl confirmar se registrará tu rendición y se cerrará tu sesión.`
        : `¿Confirmás el cierre de turno y registro de salida?\n\n(No tenés efectivo pendiente a rendir). Al confirmar se cerrará tu sesión.`;

    const ejecutarSalida = async () => {
      if (Platform.OS !== 'web') {
        Alert.alert(
          '✅ Turno Finalizado',
          montoNum > 0
            ? `Se registró la rendición de ${efectivo} a Caja Central.\n\n¡Cierre de sesión completado! Buen descanso.`
            : `¡Turno concluido con éxito! Buen descanso.`
        );
      }
      await logout();
      router.replace('/login');
    };

    if (Platform.OS === 'web') {
      if (window.confirm(mensaje)) {
        ejecutarSalida();
      }
    } else {
      Alert.alert(
        'Rendir Caja y Cerrar Sesión',
        mensaje,
        [
          { text: 'Volver', style: 'cancel' },
          {
            text: '✓ Confirmar y Cerrar Sesión',
            style: 'destructive',
            onPress: ejecutarSalida,
          },
        ]
      );
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>👤 MI TURNO Y RENDICIÓN</Text>
        <Text style={styles.headerSub}>Control de Jornada Laboral, Liquidación de Caja y Salida</Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => cargarStats(true)}
            colors={[MostazaColors.rojo]}
          />
        }>
        {/* TARJETA 1: RENDICIÓN DE EFECTIVO Y CIERRE DE TURNO */}
        <View style={styles.cardRendir}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardTitleRendir}>💵 LIQUIDACIÓN Y RENDICIÓN DE CAJA</Text>
            <View style={styles.badgeRendir}>
              <Text style={styles.badgeRendirTxt}>CIERRE DE TURNO</Text>
            </View>
          </View>

          <View style={styles.rendirMainBox}>
            <Text style={styles.rendirLabel}>EFECTIVO EN MANO A ENTREGAR EN CAJA</Text>
            {loadingStats ? (
              <ActivityIndicator size="small" color="#059669" style={{ marginVertical: 6 }} />
            ) : (
              <Text style={styles.rendirMonto}>
                {stats?.efectivo_a_rendir_formateado || '$0'}
              </Text>
            )}
            <Text style={styles.rendirDesc}>
              Cobros en billetes físicos correspondientes a las entregas de hoy que debés entregar en Caja Central.
            </Text>
          </View>

          {/* Submétricas del turno */}
          <View style={styles.rendirStatsRow}>
            <View style={styles.rendirStatItem}>
              <Text style={styles.rendirStatEmoji}>📦</Text>
              <View>
                <Text style={styles.rendirStatLbl}>ENTREGAS HOY</Text>
                <Text style={styles.rendirStatVal}>{stats?.entregados_hoy ?? 0}</Text>
              </View>
            </View>

            <View style={styles.rendirStatDivider} />

            <View style={styles.rendirStatItem}>
              <Text style={styles.rendirStatEmoji}>⭐</Text>
              <View>
                <Text style={styles.rendirStatLbl}>PROPINAS HOY</Text>
                <Text style={[styles.rendirStatVal, { color: '#B45309' }]}>
                  {stats?.total_propinas_formateado || '$0'}
                </Text>
              </View>
            </View>
          </View>

          {/* Botón Principal: Rendir Caja y Cerrar Sesión */}
          <TouchableOpacity
            style={styles.btnRendirPrincipal}
            onPress={handleRendirYCerrarTurno}
            activeOpacity={0.85}>
            <Text style={styles.btnRendirPrincipalTxt}>
              💰 RENDIR CAJA Y CERRAR SESIÓN
            </Text>
          </TouchableOpacity>
        </View>

        {/* TARJETA 2: DATOS DEL REPARTIDOR Y JORNADA */}
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

          {/* Métricas de Calificación y Total Histórico */}
          <View style={styles.statsRow}>
            <View style={styles.statBox}>
              <Text style={styles.statVal}>⭐ {rider?.calificacion ? Number(rider.calificacion).toFixed(1) : '5.0'}</Text>
              <Text style={styles.statLbl}>CALIFICACIÓN</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statBox}>
              <Text style={styles.statVal}>🛵 {rider?.viajes_totales || stats?.entregados_total || 0}</Text>
              <Text style={styles.statLbl}>TOTAL HISTÓRICO</Text>
            </View>
          </View>
        </View>

        {/* TARJETA 3: NORMAS DE SEGURIDAD */}
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

  // Tarjeta Rendición
  cardRendir: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    borderWidth: 2,
    borderColor: '#A7F3D0',
    marginBottom: 16,
    elevation: 3,
    shadowColor: '#059669',
    shadowOpacity: 0.1,
    shadowOffset: { width: 0, height: 3 },
    shadowRadius: 8,
  },
  cardTitleRendir: {
    fontSize: 11,
    fontWeight: '900',
    color: '#065F46',
    letterSpacing: 0.5,
  },
  badgeRendir: {
    backgroundColor: '#DCFCE7',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 8,
  },
  badgeRendirTxt: {
    color: '#166534',
    fontSize: 9,
    fontWeight: '900',
  },
  rendirMainBox: {
    backgroundColor: '#ECFDF5',
    borderRadius: 12,
    padding: 14,
    marginVertical: 12,
    borderWidth: 1,
    borderColor: '#D1FAE5',
    alignItems: 'center',
  },
  rendirLabel: {
    fontSize: 10,
    fontWeight: '800',
    color: '#065F46',
    letterSpacing: 0.5,
  },
  rendirMonto: {
    fontSize: 32,
    fontWeight: '900',
    color: '#047857',
    marginVertical: 4,
  },
  rendirDesc: {
    fontSize: 11,
    color: '#065F46',
    textAlign: 'center',
    lineHeight: 16,
    fontWeight: '500',
  },
  rendirStatsRow: {
    flexDirection: 'row',
    backgroundColor: '#F8FAFC',
    borderRadius: 10,
    paddingVertical: 10,
    paddingHorizontal: 12,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  rendirStatItem: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  rendirStatEmoji: {
    fontSize: 18,
  },
  rendirStatLbl: {
    fontSize: 9,
    fontWeight: '800',
    color: '#64748B',
    letterSpacing: 0.5,
  },
  rendirStatVal: {
    fontSize: 13,
    fontWeight: '900',
    color: '#0F172A',
    marginTop: 1,
  },
  rendirStatDivider: {
    width: 1,
    backgroundColor: '#E2E8F0',
    marginHorizontal: 8,
  },
  btnRendirPrincipal: {
    backgroundColor: '#059669',
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 2,
    shadowColor: '#059669',
    shadowOpacity: 0.25,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
  },
  btnRendirPrincipalTxt: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 13,
    letterSpacing: 0.5,
  },

  // Tarjetas generales
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
  policyItem: {
    fontSize: 12,
    color: '#334155',
    lineHeight: 22,
    fontWeight: '500',
  },
});
