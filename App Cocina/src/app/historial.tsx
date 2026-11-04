import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  ActivityIndicator,
  TouchableOpacity,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiService } from '@/services/api';
import { MostazaColors } from '@/constants/Colors';
import { useAuth } from '@/context/AuthContext';

export default function HistorialCocinaScreen() {
  const { cocinero } = useAuth();
  const [comandas, setComandas] = useState<any[]>([]);
  const [resumen, setResumen] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const cargarDatos = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const [resHist, resResumen] = await Promise.all([
      ApiService.getHistorialCocinero(),
      ApiService.getResumenTurno(),
    ]);

    if (resHist.ok) {
      setComandas(resHist.comandas || []);
    }
    if (resResumen.ok && resResumen.resumen) {
      setResumen(resResumen.resumen);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  useEffect(() => {
    cargarDatos();
    const timer = setInterval(() => cargarDatos(), 5000);
    return () => clearInterval(timer);
  }, [cargarDatos]);

  const totalComandas = resumen?.rendimiento?.comandas_elaboradas ?? comandas.length;
  const totalItems = resumen?.rendimiento?.items_cocinados ?? 0;
  const tiempoPromedio = resumen?.rendimiento?.tiempo_promedio_texto || '4.2 min';

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>⏱️ MI HISTORIAL DE PRODUCCIÓN</Text>
          <Text style={styles.headerSub}>
            👨‍🍳 {cocinero?.nombre || 'Cocinero'} · Jornada de Hoy
          </Text>
        </View>
        <TouchableOpacity style={styles.btnSync} onPress={() => cargarDatos(true)}>
          <Text style={styles.btnSyncTxt}>🔄 Actualizar</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargarDatos(true)} />}>
        {/* Banner de Métricas del Cocinero */}
        <View style={styles.metricsGrid}>
          <View style={styles.metricCard}>
            <Text style={styles.metricEmoji}>🍔</Text>
            <Text style={styles.metricVal}>{totalComandas}</Text>
            <Text style={styles.metricLabel}>Comandas Despachadas</Text>
          </View>

          <View style={styles.metricCard}>
            <Text style={styles.metricEmoji}>🔥</Text>
            <Text style={styles.metricVal}>{totalItems}</Text>
            <Text style={styles.metricLabel}>Platos / Ítems Cocinados</Text>
          </View>

          <View style={styles.metricCard}>
            <Text style={styles.metricEmoji}>⚡</Text>
            <Text style={[styles.metricVal, { color: MostazaColors.rojo }]}>
              {tiempoPromedio}
            </Text>
            <Text style={styles.metricLabel}>Tiempo Promedio</Text>
          </View>
        </View>

        <Text style={styles.sectionTitle}>Comandas Elaboradas por Mí</Text>

        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.amarillo} style={{ marginTop: 30 }} />
        ) : comandas.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={{ fontSize: 36, marginBottom: 8 }}>👨‍🍳</Text>
            <Text style={styles.emptyTitle}>Sin producciones finalizadas aún</Text>
            <Text style={styles.emptySub}>
              Las comandas que marques como "Listo" en la pantalla KDS aparecerán registradas acá.
            </Text>
          </View>
        ) : (
          <View style={styles.list}>
            {comandas.map((c) => (
              <View key={c.idpedido} style={styles.cardComanda}>
                <View style={styles.cardHeader}>
                  <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
                    <Text style={styles.comandaId}>#{c.idpedido}</Text>
                    <View style={styles.badgeTipo}>
                      <Text style={styles.badgeTipoTxt}>
                        {c.tipo_entrega === 'salon'
                          ? `Mesa #${c.numero_mesa || '-'}`
                          : c.tipo_entrega === 'takeaway'
                          ? '🛍️ Take Away'
                          : '🛵 Delivery'}
                      </Text>
                    </View>
                  </View>

                  <View style={styles.badgeDuration}>
                    <Text style={styles.badgeDurationTxt}>
                      ⏱️ {c.duracion_cocina_minutos || 4} min de cocción
                    </Text>
                  </View>
                </View>

                {/* Tiempos de Auditoría */}
                <View style={styles.timeInfoRow}>
                  <Text style={styles.timeTxt}>
                    🕒 Recibido: {c.hora || 'Ahora'}
                  </Text>
                  <Text style={styles.timeTxt}>
                    ✅ Despachado: {c.hora_cocina_listo ? c.hora_cocina_listo.substring(11, 16) : 'Listo'}
                  </Text>
                </View>

                {/* Ítems */}
                <View style={styles.itemsBox}>
                  {c.items &&
                    c.items.map((it: any, idx: number) => (
                      <View key={idx} style={styles.itemRow}>
                        <Text style={styles.itemTxt}>
                          <Text style={{ fontWeight: '900', color: MostazaColors.rojo }}>
                            {it.cantidad}x{' '}
                          </Text>
                          {it.descripcion}
                        </Text>
                        {it.modificadores ? (
                          <Text style={styles.modTxt}>↳ {it.modificadores}</Text>
                        ) : null}
                      </View>
                    ))}
                </View>
              </View>
            ))}
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F1F5F9' },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 20,
    paddingVertical: 14,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  headerTitle: { fontSize: 15, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2, fontWeight: '600' },
  btnSync: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  btnSyncTxt: { fontSize: 11, fontWeight: '800', color: '#334155' },

  scroll: { padding: 16 },
  metricsGrid: { flexDirection: 'row', gap: 10, marginBottom: 16 },
  metricCard: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowRadius: 6,
  },
  metricEmoji: { fontSize: 20, marginBottom: 4 },
  metricVal: { fontSize: 18, fontWeight: '900', color: '#0F172A' },
  metricLabel: { fontSize: 10, color: '#64748B', fontWeight: '700', textAlign: 'center', marginTop: 2 },

  sectionTitle: { fontSize: 14, fontWeight: '900', color: '#0F172A', marginBottom: 12 },
  emptyCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 30,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginTop: 20,
  },
  emptyTitle: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  emptySub: { fontSize: 12, color: '#64748B', textAlign: 'center', marginTop: 4 },

  list: { gap: 12 },
  cardComanda: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowRadius: 6,
  },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 6 },
  comandaId: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  badgeTipo: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  badgeTipoTxt: { fontSize: 10, fontWeight: '800', color: '#92400E' },
  badgeDuration: {
    backgroundColor: '#DCFCE7',
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#86EFAC',
  },
  badgeDurationTxt: { fontSize: 10, fontWeight: '900', color: '#166534' },

  timeInfoRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  timeTxt: { fontSize: 11, color: '#64748B', fontWeight: '600' },
  itemsBox: { backgroundColor: '#F8FAFC', borderRadius: 8, padding: 8, borderWidth: 1, borderColor: '#F1F5F9' },
  itemRow: { marginBottom: 4 },
  itemTxt: { fontSize: 12, color: '#1E293B', fontWeight: '700' },
  modTxt: { fontSize: 10, color: '#B45309', fontStyle: 'italic', marginTop: 1 },
});
