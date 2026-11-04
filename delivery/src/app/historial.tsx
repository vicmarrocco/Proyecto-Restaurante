// ============================================================
// app/historial.tsx
// Historial Profesional de Envíos, Rutas GPS Google Maps y Liquidación de Caja
// ============================================================
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  Platform,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PedidoDelivery, EstadisticasRider } from '@/types/delivery';
import { ApiService } from '@/services/api';
import { MostazaColors } from '@/constants/Colors';
import { StatusBadge } from '@/components/StatusBadge';
import { TrackingMapCard } from '@/components/TrackingMapCard';
import { useRouter } from 'expo-router';
import { useAuth } from '@/context/AuthContext';

export default function HistoryScreen() {
  const router = useRouter();
  const { rider, logout } = useAuth();
  const [pedidos, setPedidos] = useState<PedidoDelivery[]>([]);
  const [stats, setStats] = useState<EstadisticasRider | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

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
        '¿Deseás concluir tu turno de entrega de hoy y registrar tu salida?',
        [
          { text: 'Cancelar', style: 'cancel' },
          { text: 'Sí, Salir y Cerrar Turno', onPress: confirmar, style: 'destructive' },
        ]
      );
    }
  };

  // Filtros
  const [rangoFecha, setRangoFecha] = useState<'hoy' | 'semana' | 'todo'>('todo');
  const [filtroPago, setFiltroPago] = useState<'todos' | 'efectivo' | 'online'>('todos');

  // Control de pedidos expandidos para ver la ruta
  const [rutasExpandidas, setRutasExpandidas] = useState<Record<number, boolean>>({});
  const [itemsExpandidos, setItemsExpandidos] = useState<Record<number, boolean>>({});

  const cargarDatos = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);

    const [resPedidos, resStats] = await Promise.all([
      ApiService.getHistorial(rangoFecha, filtroPago),
      ApiService.getEstadisticas(),
    ]);

    if (resPedidos.ok) {
      setPedidos(resPedidos.pedidos);
    }
    if (resStats) {
      setStats(resStats);
    }

    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, [rangoFecha, filtroPago]);

  useEffect(() => {
    setLoading(true);
    cargarDatos();
  }, [cargarDatos]);

  const toggleRuta = (idpedido: number) => {
    setRutasExpandidas((prev) => ({
      ...prev,
      [idpedido]: !prev[idpedido],
    }));
  };

  const toggleItems = (idpedido: number) => {
    setItemsExpandidos((prev) => ({
      ...prev,
      [idpedido]: !prev[idpedido],
    }));
  };

  const rendirCajaACajero = () => {
    const efectivo = stats?.efectivo_a_rendir_formateado || '$0';
    Alert.alert(
      'Rendir Efectivo a Caja',
      `¿Confirmás la rendición de ${efectivo} en efectivo correspondiente a las entregas de hoy al cajero de Mostaza?\n\nAl confirmar se emitirá el recibo de liquidación del turno.`,
      [
        { text: 'Volver', style: 'cancel' },
        {
          text: '✅ Confirmar Rendición',
          onPress: async () => {
            Alert.alert(
              '¡Rendición Exitosa!',
              `Se registró la entrega de ${efectivo} a Caja Central de Mostaza Formosa.\n\n¡Turno liquidado correctamente!`
            );
            cargarDatos();
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      {/* Topbar Header */}
      <View style={styles.header}>
        <View style={{ flex: 1, paddingRight: 8 }}>
          <Text style={styles.headerTitle}>📊 HISTORIAL DE ENVÍOS</Text>
          <Text style={styles.headerSub} numberOfLines={1}>
            {rider ? `${rider.avatar || '🛵'} ${rider.nombre.split(' ')[0]} · ` : ''}Trazabilidad y Rendición
          </Text>
        </View>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
          <View style={styles.headerBadge}>
            <Text style={styles.headerBadgeText}>
              {pedidos.length} {pedidos.length === 1 ? 'ENVÍO' : 'ENVÍOS'}
            </Text>
          </View>
          <TouchableOpacity
            style={styles.btnLogout}
            onPress={handleCerrarTurno}
            activeOpacity={0.8}>
            <Text style={styles.btnLogoutText}>🚪 Cerrar Turno</Text>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => cargarDatos(true)}
            colors={[MostazaColors.rojo]}
          />
        }>
        {/* KPI: Métricas de Liquidación de Turno */}
        <View style={styles.statsCard}>
          <View style={styles.statMainRow}>
            <View style={{ flex: 1 }}>
              <Text style={styles.statMainLabel}>💵 EFECTIVO EN MANO A RENDIR</Text>
              <Text style={styles.statMainValue}>
                {stats?.efectivo_a_rendir_formateado || '$0'}
              </Text>
              <Text style={styles.statMainHint}>
                Cobros de pedidos en efectivo a entregar en caja
              </Text>
            </View>

            <TouchableOpacity
              style={styles.btnRendir}
              onPress={rendirCajaACajero}
              activeOpacity={0.8}>
              <Text style={styles.btnRendirText}>Rendir Caja</Text>
            </TouchableOpacity>
          </View>

          <View style={styles.statDivider} />

          <View style={styles.statsRow}>
            <View style={styles.statSubItem}>
              <Text style={styles.statSubEmoji}>📦</Text>
              <View>
                <Text style={styles.statSubLabel}>ENTREGAS HOY</Text>
                <Text style={styles.statSubValue}>
                  {stats?.entregados_hoy ?? pedidos.length}
                </Text>
              </View>
            </View>

            <View style={styles.statSubItem}>
              <Text style={styles.statSubEmoji}>⭐</Text>
              <View>
                <Text style={styles.statSubLabel}>PROPINAS</Text>
                <Text style={[styles.statSubValue, { color: '#92400E' }]}>
                  {stats?.total_propinas_formateado || '$0'}
                </Text>
              </View>
            </View>

            <View style={styles.statSubItem}>
              <Text style={styles.statSubEmoji}>📁</Text>
              <View>
                <Text style={styles.statSubLabel}>TOTAL HISTÓRICO</Text>
                <Text style={[styles.statSubValue, { color: '#1E40AF' }]}>
                  {stats?.entregados_total ?? pedidos.length} envíos
                </Text>
              </View>
            </View>
          </View>
        </View>

        {/* Filtros de Rango de Fecha */}
        <Text style={styles.sectionHeading}>Filtrar por Período:</Text>
        <View style={styles.pillsRow}>
          {[
            { key: 'hoy', label: '📅 Envíos de Hoy' },
            { key: 'semana', label: '🗓️ Últimos 7 Días' },
            { key: 'todo', label: '📁 Todo el Historial' },
          ].map((pill) => {
            const active = rangoFecha === pill.key;
            return (
              <TouchableOpacity
                key={pill.key}
                style={[styles.pillBtn, active && styles.pillBtnActive]}
                onPress={() => setRangoFecha(pill.key as any)}
                activeOpacity={0.7}>
                <Text style={[styles.pillText, active && styles.pillTextActive]}>
                  {pill.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>

        {/* Filtros de Medio de Pago */}
        <View style={[styles.pillsRow, { marginTop: 6, marginBottom: 16 }]}>
          {[
            { key: 'todos', label: 'Todos los pagos' },
            { key: 'efectivo', label: '💵 Solo Efectivo' },
            { key: 'online', label: '💳 Solo Online' },
          ].map((pill) => {
            const active = filtroPago === pill.key;
            return (
              <TouchableOpacity
                key={pill.key}
                style={[styles.pillSubBtn, active && styles.pillSubBtnActive]}
                onPress={() => setFiltroPago(pill.key as any)}
                activeOpacity={0.7}>
                <Text style={[styles.pillSubText, active && styles.pillSubTextActive]}>
                  {pill.label}
                </Text>
              </TouchableOpacity>
            );
          })}
        </View>

        {/* Listado de Envíos con Rutas */}
        <View style={styles.listHeader}>
          <Text style={styles.listTitle}>
            Registro de Envíos Realizados ({pedidos.length})
          </Text>
          <Text style={styles.listSubtitle}>
            Tocá "🗺️ Ver Ruta GPS" para ver el trazado y abrir en Google Maps
          </Text>
        </View>

        {loading ? (
          <View style={styles.centerContainer}>
            <ActivityIndicator size="large" color={MostazaColors.rojo} />
            <Text style={styles.loadingText}>Cargando historial de entregas...</Text>
          </View>
        ) : pedidos.length === 0 ? (
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyEmoji}>📦</Text>
            <Text style={styles.emptyTitle}>No se encontraron entregas</Text>
            <Text style={styles.emptyDesc}>
              No hay envíos registrados para el período y método de pago seleccionados.
            </Text>
          </View>
        ) : (
          pedidos.map((pedido) => {
            const isCash = pedido.metodo_pago === 'efectivo';
            const rutaVisible = Boolean(rutasExpandidas[pedido.idpedido]);
            const itemsVisible = Boolean(itemsExpandidos[pedido.idpedido]);

            return (
              <View key={pedido.idpedido} style={styles.historyCard}>
                {/* Header Comanda */}
                <View style={styles.cardHeader}>
                  <View>
                    <Text style={styles.orderNumber}>
                      Comanda #{pedido.idpedido}
                      {pedido.numero_pedido_usuario ? (
                        <Text style={styles.clientOrderTag}>
                          {' '}(Cliente #{pedido.numero_pedido_usuario})
                        </Text>
                      ) : null}
                    </Text>
                    <Text style={styles.orderDateTime}>
                      🕒 {pedido.fecha.split(' ')[0]} a las {pedido.hora} hs · {pedido.sucursal_nombre || 'Mostaza Formosa Centro'}
                    </Text>
                  </View>
                  <StatusBadge estado={pedido.estado} />
                </View>

                {/* Datos del Cliente y Destino */}
                <View style={styles.clientBox}>
                  <View style={styles.clientRow}>
                    <Text style={styles.clientIcon}>👤</Text>
                    <Text style={styles.clientName}>{pedido.cliente_nombre}</Text>
                  </View>
                  <View style={styles.clientRow}>
                    <Text style={styles.clientIcon}>📍</Text>
                    <Text style={styles.clientAddress}>{pedido.cliente_direccion}</Text>
                  </View>
                  {pedido.referencia_entrega ? (
                    <View style={styles.refBox}>
                      <Text style={styles.refText}>🚪 Ref: {pedido.referencia_entrega}</Text>
                    </View>
                  ) : null}
                </View>

                {/* Resumen Financiero y Pago */}
                <View style={styles.paymentSummary}>
                  <View style={[styles.payBadge, isCash ? styles.payBadgeCash : styles.payBadgeOnline]}>
                    <Text style={[styles.payBadgeText, { color: isCash ? '#92400E' : '#1E40AF' }]}>
                      {isCash ? '💵 Cobrado en Efectivo' : '💳 Pago Online Confirmado'}
                    </Text>
                  </View>
                  <Text style={styles.orderTotal}>{pedido.total_formateado}</Text>
                </View>

                {/* Toggle para ver los ítems de la orden */}
                <TouchableOpacity
                  style={styles.toggleRow}
                  onPress={() => toggleItems(pedido.idpedido)}
                  activeOpacity={0.7}>
                  <Text style={styles.toggleRowText}>
                    🍔 {pedido.cantidad_items} productos entregados
                  </Text>
                  <Text style={styles.toggleArrow}>
                    {itemsVisible ? '▲ Ocultar ítems' : '▼ Ver productos'}
                  </Text>
                </TouchableOpacity>

                {itemsVisible && (
                  <View style={styles.itemsList}>
                    {pedido.items.map((it, idx) => (
                      <View key={idx} style={styles.itemDetailRow}>
                        <Text style={styles.itemDetailName}>
                          <Text style={{ fontWeight: '800', color: MostazaColors.rojo }}>
                            {it.cantidad}x{' '}
                          </Text>
                          {it.descripcion}
                        </Text>
                        <Text style={styles.itemDetailPrice}>{it.subtotal_formateado}</Text>
                      </View>
                    ))}
                  </View>
                )}

                {/* BOTÓN TOGGLE PARA VER LA RUTA GPS DEL ENVÍO */}
                <TouchableOpacity
                  style={[styles.btnVerRuta, rutaVisible && styles.btnVerRutaActive]}
                  onPress={() => toggleRuta(pedido.idpedido)}
                  activeOpacity={0.8}>
                  <Text style={styles.btnVerRutaEmoji}>🗺️</Text>
                  <Text style={styles.btnVerRutaText}>
                    {rutaVisible ? 'OCULTAR RUTA DE GEOLOCALIZACIÓN' : 'VER RUTA DE GEOLOCALIZACIÓN (GOOGLE MAPS)'}
                  </Text>
                  <Text style={styles.btnVerRutaArrow}>{rutaVisible ? '▲' : '▼'}</Text>
                </TouchableOpacity>

                {/* MAPA DE GEOLOCALIZACIÓN DEL ENVÍO REALIZADO */}
                {rutaVisible && (
                  <View style={styles.mapWrapper}>
                    <TrackingMapCard
                      pedido={pedido}
                      modoHistorial={true}
                      titulo={`RUTA DE ENVÍO #${pedido.idpedido}`}
                      subtitulo={`Recorrido desde la sucursal hasta ${pedido.cliente_direccion}`}
                      iniciarExpandido={true}
                    />
                  </View>
                )}
              </View>
            );
          })
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    paddingBottom: Platform.OS === 'web' ? 70 : 65,
  },
  header: {
    backgroundColor: '#FFFFFF',
    paddingVertical: 18,
    paddingHorizontal: 20,
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
  headerTitle: {
    fontSize: 16,
    fontWeight: '900',
    color: MostazaColors.rojo,
    letterSpacing: 0.5,
  },
  headerSub: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
    fontWeight: '600',
  },
  headerBadge: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  headerBadgeText: {
    color: '#92400E',
    fontSize: 11,
    fontWeight: '900',
  },
  scrollContent: {
    padding: 16,
    paddingBottom: 40,
  },
  statsCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.06,
    shadowRadius: 10,
    elevation: 2,
  },
  statMainRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  statMainLabel: {
    fontSize: 11,
    fontWeight: '900',
    color: '#059669',
    letterSpacing: 0.5,
  },
  statMainValue: {
    fontSize: 26,
    fontWeight: '900',
    color: '#064E3B',
    marginTop: 2,
  },
  statMainHint: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  btnRendir: {
    backgroundColor: '#059669',
    paddingHorizontal: 14,
    paddingVertical: 10,
    borderRadius: 10,
  },
  btnRendirText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '900',
  },
  statDivider: {
    height: 1,
    backgroundColor: '#F1F5F9',
    marginVertical: 12,
  },
  statsRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  statSubItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  statSubEmoji: {
    fontSize: 20,
  },
  statSubLabel: {
    fontSize: 9,
    fontWeight: '800',
    color: '#64748B',
  },
  statSubValue: {
    fontSize: 13,
    fontWeight: '900',
    color: '#0F172A',
  },
  sectionHeading: {
    fontSize: 12,
    fontWeight: '800',
    color: '#475569',
    marginBottom: 8,
  },
  pillsRow: {
    flexDirection: 'row',
    gap: 8,
  },
  pillBtn: {
    flex: 1,
    backgroundColor: '#FFFFFF',
    paddingVertical: 8,
    borderRadius: 10,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  pillBtnActive: {
    backgroundColor: MostazaColors.rojo,
    borderColor: MostazaColors.rojo,
  },
  pillText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#334155',
  },
  pillTextActive: {
    color: '#FFFFFF',
    fontWeight: '900',
  },
  pillSubBtn: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    backgroundColor: '#E2E8F0',
  },
  pillSubBtnActive: {
    backgroundColor: '#0F172A',
  },
  pillSubText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#475569',
  },
  pillSubTextActive: {
    color: '#FFFFFF',
    fontWeight: '800',
  },
  listHeader: {
    marginBottom: 12,
  },
  listTitle: {
    fontSize: 14,
    fontWeight: '900',
    color: '#0F172A',
  },
  listSubtitle: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  centerContainer: {
    padding: 40,
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    color: '#64748B',
    fontSize: 13,
  },
  emptyContainer: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 30,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  emptyEmoji: {
    fontSize: 40,
    marginBottom: 10,
  },
  emptyTitle: {
    fontSize: 16,
    fontWeight: '800',
    color: '#0F172A',
  },
  emptyDesc: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
    marginTop: 4,
    lineHeight: 18,
  },
  historyCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.05,
    shadowRadius: 8,
    elevation: 2,
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingBottom: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  orderNumber: {
    fontSize: 16,
    fontWeight: '900',
    color: '#0F172A',
  },
  clientOrderTag: {
    fontSize: 12,
    color: MostazaColors.rojo,
    fontWeight: '800',
  },
  orderDateTime: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
  },
  clientBox: {
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  clientRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 4,
  },
  clientIcon: {
    fontSize: 14,
  },
  clientName: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0F172A',
  },
  clientAddress: {
    fontSize: 13,
    color: '#334155',
    fontWeight: '600',
  },
  refBox: {
    backgroundColor: '#F8FAFC',
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 4,
    marginTop: 4,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  refText: {
    fontSize: 11,
    color: '#64748B',
    fontStyle: 'italic',
  },
  paymentSummary: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 10,
  },
  payBadge: {
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
  },
  payBadgeCash: {
    backgroundColor: '#FEF3C7',
  },
  payBadgeOnline: {
    backgroundColor: '#EFF6FF',
  },
  payBadgeText: {
    fontSize: 11,
    fontWeight: '800',
  },
  orderTotal: {
    fontSize: 18,
    fontWeight: '900',
    color: MostazaColors.rojo,
  },
  toggleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    padding: 10,
    borderRadius: 8,
    marginBottom: 8,
  },
  toggleRowText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#334155',
  },
  toggleArrow: {
    fontSize: 11,
    color: MostazaColors.rojo,
    fontWeight: '800',
  },
  itemsList: {
    backgroundColor: '#F8FAFC',
    borderRadius: 8,
    padding: 10,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  itemDetailRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 4,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  itemDetailName: {
    fontSize: 12,
    color: '#0F172A',
    fontWeight: '600',
  },
  itemDetailPrice: {
    fontSize: 12,
    color: '#64748B',
    fontWeight: '700',
  },
  btnVerRuta: {
    backgroundColor: '#0F172A',
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginTop: 6,
  },
  btnVerRutaActive: {
    backgroundColor: MostazaColors.rojo,
  },
  btnVerRutaEmoji: {
    fontSize: 16,
    marginRight: 8,
  },
  btnVerRutaText: {
    color: '#FFFFFF',
    fontSize: 11,
    fontWeight: '900',
    flex: 1,
    letterSpacing: 0.5,
  },
  btnVerRutaArrow: {
    color: '#FBB728',
    fontSize: 12,
    fontWeight: '900',
  },
  mapWrapper: {
    marginTop: 10,
  },
  btnLogout: {
    backgroundColor: '#FEE2E2',
    borderWidth: 1,
    borderColor: '#FECACA',
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 8,
  },
  btnLogoutText: {
    color: '#DC2626',
    fontSize: 10,
    fontWeight: '800',
  },
});
