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

export default function HistorialMeseroScreen() {
  const [atenciones, setAtenciones] = useState<any[]>([]);
  const [resumen, setResumen] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const mesero = ApiService.getCurrentMesero();

  const cargarDatos = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const [resHist, resResumen] = await Promise.all([
      ApiService.getHistorialMesero(),
      ApiService.getResumenTurno(),
    ]);

    if (resHist.ok && Array.isArray(resHist.atenciones)) {
      setAtenciones(resHist.atenciones);
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

  const totalMesas = resumen?.rendimiento?.mesas_atendidas ?? atenciones.length;
  const totalFacturado = resumen?.rendimiento?.total_facturado ?? atenciones.reduce((acc, p) => acc + Number(p.total || 0), 0);
  const ticketPromedio = totalMesas > 0 ? Math.round(totalFacturado / totalMesas) : 0;

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>📊 MI HISTORIAL DE ATENCIONES</Text>
          <Text style={styles.headerSub}>
            🤵 {mesero?.nombre_completo || mesero?.nombre || 'Mesero'} · {mesero?.seccion_nombre || 'Salón Principal'}
          </Text>
        </View>
        <TouchableOpacity style={styles.btnSync} onPress={() => cargarDatos(true)}>
          <Text style={styles.btnSyncTxt}>🔄 Actualizar</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargarDatos(true)} colors={[MostazaColors.rojo]} />}>
        
        {/* Banner de Métricas del Mesero */}
        <View style={styles.metricsGrid}>
          <View style={styles.metricCard}>
            <Text style={styles.metricEmoji}>🍽️</Text>
            <Text style={styles.metricVal}>{totalMesas}</Text>
            <Text style={styles.metricLabel}>Mesas Atendidas</Text>
          </View>

          <View style={styles.metricCard}>
            <Text style={styles.metricEmoji}>💰</Text>
            <Text style={[styles.metricVal, { color: MostazaColors.rojo }]}>
              ${totalFacturado.toLocaleString('es-AR')}
            </Text>
            <Text style={styles.metricLabel}>Total Facturado</Text>
          </View>

          <View style={styles.metricCard}>
            <Text style={styles.metricEmoji}>📈</Text>
            <Text style={styles.metricVal}>
              ${ticketPromedio.toLocaleString('es-AR')}
            </Text>
            <Text style={styles.metricLabel}>Ticket Promedio</Text>
          </View>
        </View>

        <Text style={styles.sectionTitle}>Registro de Atenciones de la Jornada</Text>

        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.rojo} style={{ marginTop: 30 }} />
        ) : atenciones.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={{ fontSize: 36, marginBottom: 8 }}>🤵</Text>
            <Text style={styles.emptyTitle}>Sin atenciones registradas hoy</Text>
            <Text style={styles.emptySub}>
              Las comandas que cobres o sirvas en mesa quedarán asociadas a tu legajo y se listarán acá.
            </Text>
          </View>
        ) : (
          atenciones.map((item) => (
            <View key={item.idpedido} style={styles.card}>
              <View style={styles.cardHeader}>
                <View style={styles.badgeMesa}>
                  <Text style={styles.badgeMesaTxt}>MESA #{item.numero_mesa || 'S/M'}</Text>
                </View>
                <View style={styles.cardInfo}>
                  <Text style={styles.facturaTxt}>{item.numero_factura || `FAC-B-${item.idpedido}`}</Text>
                  <Text style={styles.horaTxt}>🕒 {item.hora || item.fecha}</Text>
                </View>
              </View>

              <View style={styles.cardBody}>
                <View style={styles.rowBetween}>
                  <Text style={styles.clienteTxt}>👤 {item.cliente || 'Consumidor Final'}</Text>
                  <Text style={styles.montoTxt}>${Number(item.total).toLocaleString('es-AR')}</Text>
                </View>

                <View style={styles.pagoRow}>
                  <Text style={styles.pagoTxt}>
                    💳 Pago: <Text style={{ fontWeight: '800' }}>{(item.metodo_pago || 'Efectivo').toUpperCase()}</Text>
                  </Text>
                  <Text style={styles.estadoTxt}>
                    {item.estado === 'Entregado' ? '✓ Servido en Mesa' : `● ${item.estado}`}
                  </Text>
                </View>

                {item.cocinero_nombre ? (
                  <Text style={styles.cocineroTxt}>
                    👨‍🍳 Cocina: {item.cocinero_nombre} {item.duracion_cocina_minutos > 0 ? `(${item.duracion_cocina_minutos} min)` : ''}
                  </Text>
                ) : null}

                {/* Items */}
                {item.items && item.items.length > 0 && (
                  <View style={styles.itemsBox}>
                    {item.items.map((it: any, idx: number) => (
                      <Text key={idx} style={styles.itemRowTxt} numberOfLines={1}>
                        • {it.cantidad}x {it.descripcion}
                      </Text>
                    ))}
                  </View>
                )}
              </View>
            </View>
          ))
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F8FAFC' },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 20,
    paddingVertical: 14,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2, fontWeight: '600' },
  btnSync: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  btnSyncTxt: { fontSize: 11, fontWeight: '800', color: '#475569' },
  scroll: { padding: 16, paddingBottom: 36 },

  metricsGrid: {
    flexDirection: 'row',
    gap: 8,
    marginBottom: 18,
  },
  metricCard: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    elevation: 1,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowOffset: { width: 0, height: 1 },
    shadowRadius: 2,
  },
  metricEmoji: { fontSize: 20, marginBottom: 4 },
  metricVal: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  metricLabel: { fontSize: 10, color: '#64748B', textAlign: 'center', marginTop: 2, fontWeight: '700' },

  sectionTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: '#0F172A',
    marginBottom: 12,
    letterSpacing: -0.2,
  },

  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    elevation: 1,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowOffset: { width: 0, height: 1 },
    shadowRadius: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
    paddingBottom: 8,
    marginBottom: 8,
  },
  badgeMesa: {
    backgroundColor: '#0F172A',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
  },
  badgeMesaTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 12 },
  cardInfo: { alignItems: 'flex-end' },
  facturaTxt: { fontSize: 11, fontWeight: '800', color: '#64748B' },
  horaTxt: { fontSize: 10, color: '#94A3B8', marginTop: 1 },

  cardBody: { gap: 6 },
  rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  clienteTxt: { fontSize: 13, fontWeight: '800', color: '#1E293B' },
  montoTxt: { fontSize: 15, fontWeight: '900', color: MostazaColors.rojo },

  pagoRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  pagoTxt: { fontSize: 11, color: '#475569' },
  estadoTxt: { fontSize: 11, fontWeight: '800', color: '#059669' },

  cocineroTxt: { fontSize: 11, color: '#2563EB', fontStyle: 'italic' },

  itemsBox: {
    backgroundColor: '#F8FAFC',
    borderRadius: 6,
    padding: 8,
    gap: 3,
    marginTop: 4,
  },
  itemRowTxt: { fontSize: 11, color: '#475569' },

  emptyCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 30,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  emptyTitle: { fontSize: 15, fontWeight: '900', color: '#0F172A' },
  emptySub: { fontSize: 12, color: '#64748B', textAlign: 'center', marginTop: 4, lineHeight: 18 },
});
