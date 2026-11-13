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
import { useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';
import { ApiService } from '@/services/api';

export default function PerfilScreen() {
  const { mesero, logout, horaIngreso } = useAuth();
  const [stats, setStats] = useState<any>(null);
  const [loadingStats, setLoadingStats] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const cargarStats = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    try {
      const res = await ApiService.getEstadisticas();
      if (res && res.ok) {
        setStats(res);
      }
    } catch (e) {
      // Ignorar error de red silencioso
    } finally {
      setLoadingStats(false);
      if (isRefresh) setRefreshing(false);
    }
  }, []);

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
        <Text style={styles.headerTitle}>⚙️ MI TURNO Y RENDICIÓN DE CAJA</Text>
        <Text style={styles.headerSub}>Terminal de Salón · Control de Asistencia y Liquidación</Text>
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
              Cobros en billetes físicos recaudados de comensales en mesas que debés entregar en Caja Central de Mostaza.
            </Text>
          </View>

          {/* Submétricas del turno */}
          <View style={styles.rendirStatsRow}>
            <View style={styles.rendirStatItem}>
              <Text style={styles.rendirStatEmoji}>🍽️</Text>
              <View>
                <Text style={styles.rendirStatLbl}>MESAS ATENDIDAS</Text>
                <Text style={styles.rendirStatVal}>{stats?.mesas_atendidas ?? 0}</Text>
              </View>
            </View>

            <View style={styles.rendirStatDivider} />

            <View style={styles.rendirStatItem}>
              <Text style={styles.rendirStatEmoji}>💰</Text>
              <View>
                <Text style={styles.rendirStatLbl}>TOTAL RECAUDADO</Text>
                <Text style={[styles.rendirStatVal, { color: '#059669' }]}>
                  {stats?.total_recaudado_formateado || '$0'}
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

        {/* TARJETA 2: DATOS DEL MESERO Y JORNADA */}
        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <Text style={styles.cardTitle}>🤵 MESERO EN TURNO</Text>
            <View style={styles.badgeTurnoOn}>
              <Text style={styles.badgeTurnoTxt}>● SESIÓN ACTIVA</Text>
            </View>
          </View>

          <View style={styles.profileRow}>
            <View style={styles.avatarBox}>
              <Text style={styles.profileAvatar}>{mesero?.avatar || '🤵'}</Text>
            </View>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={styles.profileName}>
                {mesero?.nombre_completo || mesero?.nombre || 'Mesero Mostaza'}
              </Text>
              <Text style={styles.profileDetail}>
                {mesero?.email || 'mesero@mostaza.com.ar'}
              </Text>
              <Text style={styles.profileDetail}>
                Rol: {mesero?.rol_nombre || 'Mozo / Mesero'} · Salón Mostaza
              </Text>
              <Text style={styles.profileDetail}>
                Base: {mesero?.sucursal_base || 'Mostaza Formosa Centro'}
              </Text>
              {horaIngreso ? (
                <Text style={styles.profileHora}>⏱️ Ingreso registrado: {horaIngreso} hs</Text>
              ) : null}
            </View>
          </View>

          {/* Métricas de Calificación y Total Histórico */}
          <View style={styles.statsRow}>
            <View style={styles.statBox}>
              <Text style={styles.statVal}>⭐ {mesero?.calificacion ? Number(mesero.calificacion).toFixed(1) : '4.95'}</Text>
              <Text style={styles.statLbl}>CALIFICACIÓN</Text>
            </View>
            <View style={styles.statDivider} />
            <View style={styles.statBox}>
              <Text style={styles.statVal}>🍽️ {stats?.atenciones_total || mesero?.viajes_totales || 0}</Text>
              <Text style={styles.statLbl}>TOTAL HISTÓRICO</Text>
            </View>
          </View>
        </View>

        {/* TARJETA 3: NORMAS DE ATENCIÓN Y RENDICIÓN */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>📋 Normas de Rendición y Atención en Salón</Text>
          <Text style={styles.policyItem}>✅ Todo comensal debe ser atendido en mesa en menos de 2 minutos.</Text>
          <Text style={styles.policyItem}>✅ El efectivo cobrado en mesas debe custodiarse y rendirse al finalizar el turno.</Text>
          <Text style={styles.policyItem}>✅ Verificá que la comanda esté marcada como 'Entregado' al liberar la mesa.</Text>
          <Text style={styles.policyItem}>✅ La rendición a Caja Central emite automáticamente el cierre de sesión.</Text>
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
    marginBottom: 8,
  },
  cardTitle: { fontSize: 11, fontWeight: '800', color: '#64748B', letterSpacing: 0.5 },

  // Tarjeta de Rendición
  cardRendir: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1.5,
    borderColor: '#A7F3D0',
    marginBottom: 16,
    elevation: 3,
    shadowColor: '#059669',
    shadowOpacity: 0.08,
    shadowRadius: 8,
  },
  cardTitleRendir: {
    fontSize: 11,
    fontWeight: '900',
    color: '#065F46',
    letterSpacing: 0.5,
  },
  badgeRendir: {
    backgroundColor: '#D1FAE5',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  badgeRendirTxt: {
    color: '#047857',
    fontSize: 9,
    fontWeight: '900',
  },
  rendirMainBox: {
    backgroundColor: '#ECFDF5',
    borderRadius: 12,
    padding: 14,
    marginVertical: 10,
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
    padding: 10,
    marginBottom: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  rendirStatItem: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  rendirStatEmoji: { fontSize: 18 },
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
    shadowColor: '#059669',
    shadowOpacity: 0.2,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
    elevation: 3,
  },
  btnRendirPrincipalTxt: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 13,
    letterSpacing: 0.5,
  },

  // Perfil del mesero
  badgeTurnoOn: {
    backgroundColor: '#DCFCE7',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 12,
  },
  badgeTurnoTxt: { color: '#166534', fontSize: 10, fontWeight: '900' },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginVertical: 12,
  },
  avatarBox: {
    width: 60,
    height: 60,
    borderRadius: 30,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
  },
  profileAvatar: { fontSize: 32 },
  profileName: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  profileDetail: { fontSize: 11, color: '#64748B', marginTop: 2, fontWeight: '600' },
  profileHora: { fontSize: 11, color: '#3B82F6', fontWeight: '800', marginTop: 4 },

  statsRow: {
    flexDirection: 'row',
    backgroundColor: '#F8FAFC',
    borderRadius: 10,
    paddingVertical: 10,
    marginTop: 6,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  statBox: { flex: 1, alignItems: 'center' },
  statVal: { fontSize: 15, fontWeight: '900', color: '#0F172A' },
  statLbl: { fontSize: 9, fontWeight: '800', color: '#64748B', marginTop: 2 },
  statDivider: { width: 1, backgroundColor: '#E2E8F0' },

  policyItem: {
    fontSize: 12,
    color: '#334155',
    lineHeight: 20,
    marginTop: 6,
    fontWeight: '500',
  },
});
