// ============================================================
// app/index.tsx
// Pantalla Principal: Pedidos de Delivery y Control de Turno
// Con Bloqueo Anti-Colisión y Ticket de Rendimiento
// ============================================================
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  RefreshControl,
  TouchableOpacity,
  ActivityIndicator,
  Platform,
  Alert,
  Modal,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { PedidoDelivery } from '@/types/delivery';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { OrderCard } from '@/components/OrderCard';
import { DeliveryHeader } from '@/components/DeliveryHeader';
import { MostazaColors } from '@/constants/Colors';
import { Config } from '@/constants/Config';

export default function HomeScreen() {
  const router = useRouter();
  const { rider, isAuthenticated, isLoading, horaIngreso, logout } = useAuth();

  const [pedidos, setPedidos] = useState<PedidoDelivery[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [online, setOnline] = useState(true);
  const [filtroTab, setFiltroTab] = useState<'todos' | 'listos' | 'en_camino' | 'en_preparacion'>('todos');
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Estados del Ticket de Rendimiento en Cierre de Turno
  const [modalTurnoVisible, setModalTurnoVisible] = useState(false);
  const [cargandoResumen, setCargandoResumen] = useState(false);
  const [resumenTurno, setResumenTurno] = useState<any>(null);

  const cargarPedidos = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    setErrorMsg(null);

    const res = await ApiService.getPedidos('todos');
    if (res.ok) {
      setPedidos(res.pedidos);
    } else {
      setErrorMsg(res.error || 'Error al conectar con el servidor central de Mostaza.');
    }

    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      cargarPedidos();

      // Auto-polling continuo en segundo plano cada 3.5 segundos
      const timer = setInterval(() => {
        if (online) {
          cargarPedidos();
        }
      }, Config.POLLING_INTERVAL_MS);

      return () => clearInterval(timer);
    }
  }, [cargarPedidos, online, isAuthenticated]);

  const abrirCierreTurno = async () => {
    setModalTurnoVisible(true);
    setCargandoResumen(true);
    const res = await ApiService.getResumenTurno();
    if (res.ok && res.resumen) {
      setResumenTurno(res.resumen);
    }
    setCargandoResumen(false);
  };

  const confirmarCierreDefinitivo = async () => {
    setModalTurnoVisible(false);
    await logout();
    router.replace('/login');
  };

  if (isLoading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color={MostazaColors.rojo} />
      </View>
    );
  }

  if (!isAuthenticated || !rider) {
    return null;
  }

  const riderId = rider.id || 0;

  // Filtrado reactivo con BLOQUEO ANTI-COLISIÓN
  const pedidosFiltrados = pedidos.filter((p) => {
    const isEnCamino = p.estado === 'En Camino';
    const isListo = p.estado === 'Listo para Despacho' || (p.estado as string) === 'Listo';

    // Anti-colisión: si la orden ya está en viaje con OTRO repartidor, no mostrarla a este rider
    if (isEnCamino && p.idempleado_repartidor && p.idempleado_repartidor !== riderId) {
      return false;
    }

    if (filtroTab === 'listos') {
      // Solo órdenes listas disponibles que no pertenezcan a otro rider
      const libreOAsignadoAMi = !p.idempleado_repartidor || p.idempleado_repartidor === riderId;
      return isListo && libreOAsignadoAMi;
    }

    if (filtroTab === 'en_camino') {
      // Solo órdenes que este repartidor tiene asignadas en viaje
      const asignadoAMi = !p.idempleado_repartidor || p.idempleado_repartidor === riderId;
      return isEnCamino && asignadoAMi;
    }

    if (filtroTab === 'en_preparacion') {
      return (
        p.estado === 'En Preparación' ||
        (p.estado as string) === 'En Preparacion' ||
        (p.estado as string) === 'En Cocina' ||
        p.estado === 'Recibido'
      );
    }

    // Pestaña "Todos": no mostrar órdenes que otro rider tiene en viaje
    return true;
  });

  const pedidosEnCamino = pedidos.filter(
    (p) => p.estado === 'En Camino' && (!p.idempleado_repartidor || p.idempleado_repartidor === riderId)
  );

  const pedidosListos = pedidos.filter(
    (p) =>
      (p.estado === 'Listo para Despacho' || (p.estado as string) === 'Listo') &&
      (!p.idempleado_repartidor || p.idempleado_repartidor === riderId)
  );

  const pedidosCocina = pedidos.filter(
    (p) =>
      p.estado === 'En Preparación' ||
      (p.estado as string) === 'En Preparacion' ||
      (p.estado as string) === 'En Cocina' ||
      p.estado === 'Recibido'
  );

  return (
    <SafeAreaView style={styles.safeArea}>
      {/* Header Corporativo Superior con Salir/Cerrar Sesión */}
      <DeliveryHeader
        rider={rider}
        online={online}
        onToggleOnline={setOnline}
        onRefresh={() => cargarPedidos(true)}
        onLogout={abrirCierreTurno}
        pedidosEnCaminoCount={pedidosEnCamino.length}
      />

      {/* Barra de Control de Turno Corporativo */}
      <View style={styles.shiftBar}>
        <View style={{ flex: 1 }}>
          <View style={styles.shiftStatusRow}>
            <View style={[styles.shiftDot, { backgroundColor: '#16A34A' }]} />
            <Text style={styles.shiftTitle}>
              TURNO EN CURSO ({horaIngreso || 'Fichado'} hs)
            </Text>
          </View>
          <Text style={styles.shiftSub}>
            Base: {rider.sucursal_base || 'Mostaza Formosa Centro'} · Anti-Colisión Activo
          </Text>
        </View>

        <TouchableOpacity
          style={styles.btnShiftActionEnd}
          onPress={abrirCierreTurno}
          activeOpacity={0.8}>
          <Text style={styles.btnShiftText}>🚪 Cerrar Turno</Text>
        </TouchableOpacity>
      </View>

      {/* Pestañas de Filtro Interactivas con Badges en Vivo */}
      <View style={styles.tabsContainer}>
        {[
          { key: 'todos', label: 'Todos', count: pedidosFiltrados.length },
          { key: 'listos', label: '🚀 Listos', count: pedidosListos.length },
          { key: 'en_camino', label: '🛵 En Viaje', count: pedidosEnCamino.length },
          { key: 'en_preparacion', label: '👨‍🍳 Cocina', count: pedidosCocina.length },
        ].map((tab) => {
          const isActive = filtroTab === tab.key;
          return (
            <TouchableOpacity
              key={tab.key}
              style={[styles.filterTab, isActive && styles.filterTabActive]}
              onPress={() => setFiltroTab(tab.key as any)}
              activeOpacity={0.7}>
              <Text style={[styles.filterTabText, isActive && styles.filterTabTextActive]}>
                {tab.label}
              </Text>
              <View style={[styles.tabBadge, isActive && styles.tabBadgeActive]}>
                <Text style={[styles.tabBadgeText, isActive && styles.tabBadgeTextActive]}>
                  {tab.count}
                </Text>
              </View>
            </TouchableOpacity>
          );
        })}
      </View>

      {/* Lista de Pedidos */}
      {loading ? (
        <View style={styles.centerContainer}>
          <ActivityIndicator size="large" color={MostazaColors.rojo} />
          <Text style={styles.loadingText}>Conectando con despacho de Mostaza...</Text>
        </View>
      ) : errorMsg ? (
        <View style={styles.centerContainer}>
          <Text style={styles.errorEmoji}>⚠️</Text>
          <Text style={styles.errorTitle}>Error de Conexión</Text>
          <Text style={styles.errorDesc}>{errorMsg}</Text>
          <TouchableOpacity style={styles.retryBtn} onPress={() => cargarPedidos(true)}>
            <Text style={styles.retryText}>Reintentar Conexión</Text>
          </TouchableOpacity>
        </View>
      ) : (
        <FlatList
          data={pedidosFiltrados}
          keyExtractor={(item) => item.idpedido.toString()}
          renderItem={({ item }) => (
            <OrderCard
              pedido={item}
              onStatusUpdated={() => cargarPedidos()}
              destacado={item.estado === 'En Camino' || item.estado === 'Listo para Despacho'}
            />
          )}
          contentContainerStyle={styles.listContent}
          refreshControl={
            <RefreshControl
              refreshing={refreshing}
              onRefresh={() => cargarPedidos(true)}
              colors={[MostazaColors.rojo]}
            />
          }
          ListEmptyComponent={
            <View style={styles.emptyContainer}>
              <Text style={styles.emptyEmoji}>🍔</Text>
              <Text style={styles.emptyTitle}>Sin pedidos en esta sección</Text>
              <Text style={styles.emptyDesc}>
                {filtroTab === 'listos'
                  ? 'No hay pedidos empaquetados listos para tomar en este momento.'
                  : filtroTab === 'en_camino'
                  ? 'No tenés ningún pedido en viaje. Podés tomar uno de la pestaña "Listos".'
                  : filtroTab === 'en_preparacion'
                  ? 'No hay pedidos en la línea de cocina de Mostaza.'
                  : 'No hay pedidos activos pendientes de entrega.'}
              </Text>
            </View>
          }
        />
      )}

      {/* ============================================================ */}
      {/* MODAL: TICKET DE RENDIMIENTO AL CERRAR TURNO                 */}
      {/* ============================================================ */}
      <Modal visible={modalTurnoVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalBox}>
            <View style={styles.modalHeader}>
              <View>
                <Text style={styles.modalHeaderTitle}>🏁 CIERRE DE JORNADA LABORAL</Text>
                <Text style={styles.modalHeaderSub}>Ticket de Rendimiento Oficial Mostaza</Text>
              </View>
              <TouchableOpacity
                onPress={() => setModalTurnoVisible(false)}
                style={styles.modalCloseBtn}>
                <Text style={styles.modalCloseTxt}>✕</Text>
              </TouchableOpacity>
            </View>

            {cargandoResumen ? (
              <View style={{ padding: 40, alignItems: 'center' }}>
                <ActivityIndicator size="large" color={MostazaColors.rojo} />
                <Text style={{ marginTop: 12, color: '#64748B', fontWeight: '700', fontSize: 13 }}>
                  Generando ticket de rendimiento...
                </Text>
              </View>
            ) : (
              <View style={styles.ticketContent}>
                {/* Cabecera del Ticket */}
                <View style={styles.ticketCard}>
                  <Text style={styles.ticketLogo}>🍔 MOSTAZA FAST FOOD</Text>
                  <Text style={styles.ticketSubHeader}>COMPROBANTE DE TURNO - REPARTIDOR</Text>
                  <View style={styles.ticketDivider} />

                  <View style={styles.ticketRow}>
                    <Text style={styles.ticketLbl}>Repartidor:</Text>
                    <Text style={styles.ticketVal}>{rider.nombre}</Text>
                  </View>
                  <View style={styles.ticketRow}>
                    <Text style={styles.ticketLbl}>Vehículo / Base:</Text>
                    <Text style={styles.ticketVal}>{rider.sucursal_base || 'Mostaza Centro'}</Text>
                  </View>
                  <View style={styles.ticketRow}>
                    <Text style={styles.ticketLbl}>Ingreso:</Text>
                    <Text style={styles.ticketVal}>
                      {resumenTurno?.turno?.hora_ingreso || horaIngreso || 'Hoy'}
                    </Text>
                  </View>
                  <View style={styles.ticketRow}>
                    <Text style={styles.ticketLbl}>Duración del Turno:</Text>
                    <Text style={styles.ticketVal}>
                      {resumenTurno?.turno?.duracion_horas_texto || 'En curso'}
                    </Text>
                  </View>

                  <View style={styles.ticketDivider} />

                  {/* Métricas clave */}
                  <View style={styles.metricsBox}>
                    <View style={styles.metricCol}>
                      <Text style={styles.metricNum}>
                        {resumenTurno?.rendimiento?.viajes_realizados ?? rider.viajes_totales ?? 0}
                      </Text>
                      <Text style={styles.metricLabel}>Viajes Realizados</Text>
                    </View>

                    <View style={styles.metricDivV} />

                    <View style={styles.metricCol}>
                      <Text style={[styles.metricNum, { color: '#059669' }]}>
                        ${Number(resumenTurno?.rendimiento?.efectivo_a_rendir ?? 0).toLocaleString('es-AR')}
                      </Text>
                      <Text style={styles.metricLabel}>Efectivo en Mano</Text>
                    </View>

                    <View style={styles.metricDivV} />

                    <View style={styles.metricCol}>
                      <Text style={[styles.metricNum, { color: MostazaColors.rojo }]}>
                        {resumenTurno?.rendimiento?.tiempo_promedio_entrega || '18 min'}
                      </Text>
                      <Text style={styles.metricLabel}>Tiempo Medio</Text>
                    </View>
                  </View>

                  <Text style={styles.ticketNotice}>
                    Recordá rendir en caja el efectivo cobrado en mano antes de retirarte de la sucursal.
                  </Text>
                </View>
              </View>
            )}

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.btnModalCancel}
                onPress={() => setModalTurnoVisible(false)}>
                <Text style={styles.btnModalCancelTxt}>Continuar en Turno</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={styles.btnModalConfirm}
                onPress={confirmarCierreDefinitivo}>
                <Text style={styles.btnModalConfirmTxt}>🚪 Salir y Cerrar Turno</Text>
              </TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    paddingBottom: Platform.OS === 'web' ? 70 : 65,
  },
  shiftBar: {
    backgroundColor: '#FFFFFF',
    marginHorizontal: 16,
    marginTop: 10,
    marginBottom: 6,
    padding: 12,
    borderRadius: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  shiftStatusRow: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  shiftDot: { width: 8, height: 8, borderRadius: 4 },
  shiftTitle: { fontSize: 11, fontWeight: '900', color: '#0F172A' },
  shiftSub: { fontSize: 10, color: '#64748B', marginTop: 2 },
  btnShiftActionEnd: {
    backgroundColor: '#FEE2E2',
    borderWidth: 1,
    borderColor: '#FECACA',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 8,
  },
  btnShiftText: { color: '#DC2626', fontSize: 11, fontWeight: '800' },

  tabsContainer: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    paddingVertical: 10,
    gap: 6,
  },
  filterTab: {
    flex: 1,
    paddingVertical: 8,
    paddingHorizontal: 6,
    borderRadius: 10,
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  filterTabActive: {
    backgroundColor: MostazaColors.rojo,
    borderColor: MostazaColors.rojo,
    elevation: 2,
    shadowColor: MostazaColors.rojo,
    shadowOpacity: 0.25,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
  },
  filterTabText: {
    fontSize: 10.5,
    fontWeight: '800',
    color: '#334155',
  },
  filterTabTextActive: {
    color: '#FFFFFF',
    fontWeight: '900',
  },
  tabBadge: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 5,
    paddingVertical: 1,
    borderRadius: 8,
  },
  tabBadgeActive: {
    backgroundColor: 'rgba(255, 255, 255, 0.25)',
  },
  tabBadgeText: {
    fontSize: 9,
    fontWeight: '900',
    color: '#475569',
  },
  tabBadgeTextActive: {
    color: '#FFFFFF',
  },
  listContent: {
    paddingTop: 6,
    paddingBottom: 24,
  },
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 32,
  },
  loadingText: {
    marginTop: 12,
    fontSize: 13,
    color: '#64748B',
    fontWeight: '600',
  },
  errorEmoji: {
    fontSize: 48,
    marginBottom: 8,
  },
  errorTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: MostazaColors.rojo,
    marginBottom: 4,
  },
  errorDesc: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 18,
    marginBottom: 16,
  },
  retryBtn: {
    backgroundColor: MostazaColors.rojo,
    paddingHorizontal: 20,
    paddingVertical: 10,
    borderRadius: 10,
  },
  retryText: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 12,
  },
  emptyContainer: {
    padding: 40,
    alignItems: 'center',
  },
  emptyEmoji: {
    fontSize: 44,
    marginBottom: 10,
  },
  emptyTitle: {
    fontSize: 15,
    fontWeight: '800',
    color: '#0F172A',
    marginBottom: 4,
  },
  emptyDesc: {
    fontSize: 12,
    color: '#64748B',
    textAlign: 'center',
    lineHeight: 18,
  },

  // Modal Styles
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  modalBox: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    width: '100%',
    maxWidth: 440,
    overflow: 'hidden',
  },
  modalHeader: {
    backgroundColor: '#0F172A',
    padding: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  modalHeaderTitle: { color: '#FFFFFF', fontSize: 14, fontWeight: '900' },
  modalHeaderSub: { color: '#94A3B8', fontSize: 11, marginTop: 2 },
  modalCloseBtn: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: 'rgba(255,255,255,0.1)',
    justifyContent: 'center',
    alignItems: 'center',
  },
  modalCloseTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 13 },

  ticketContent: { padding: 16 },
  ticketCard: {
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  ticketLogo: { fontSize: 14, fontWeight: '900', color: MostazaColors.rojo, textAlign: 'center' },
  ticketSubHeader: { fontSize: 10, color: '#64748B', textAlign: 'center', marginTop: 2, fontWeight: '700' },
  ticketDivider: { height: 1, backgroundColor: '#CBD5E1', marginVertical: 10 },
  ticketRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4 },
  ticketLbl: { fontSize: 11, color: '#64748B' },
  ticketVal: { fontSize: 11, fontWeight: '800', color: '#0F172A' },

  metricsBox: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 10,
    marginVertical: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  metricCol: { flex: 1, alignItems: 'center' },
  metricNum: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  metricLabel: { fontSize: 9, color: '#64748B', marginTop: 2, textAlign: 'center' },
  metricDivV: { width: 1, backgroundColor: '#E2E8F0', marginVertical: 4 },

  ticketNotice: {
    fontSize: 10,
    color: '#B45309',
    backgroundColor: '#FEF3C7',
    padding: 8,
    borderRadius: 6,
    textAlign: 'center',
    marginTop: 4,
    fontWeight: '700',
  },

  modalActions: {
    flexDirection: 'row',
    padding: 14,
    backgroundColor: '#F8FAFC',
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    gap: 10,
  },
  btnModalCancel: {
    flex: 1,
    paddingVertical: 12,
    backgroundColor: '#FFFFFF',
    borderRadius: 8,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  btnModalCancelTxt: { color: '#475569', fontWeight: '800', fontSize: 12 },
  btnModalConfirm: {
    flex: 1,
    paddingVertical: 12,
    backgroundColor: MostazaColors.rojo,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnModalConfirmTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 12 },
});
