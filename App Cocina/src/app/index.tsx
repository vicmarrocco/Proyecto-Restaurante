import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  Modal,
  Platform,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiService } from '@/services/api';
import { ComandaCocina } from '@/types/cocina';
import { MostazaColors } from '@/constants/Colors';
import { useAuth } from '@/context/AuthContext';

export default function KdsScreen() {
  const { cocinero, logout } = useAuth();
  const [comandas, setComandas] = useState<ComandaCocina[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filtroTab, setFiltroTab] = useState<'pendientes' | 'preparacion'>('pendientes');
  const [segundosTick, setSegundosTick] = useState(0);

  // Modal de Ticket de Rendimiento al Cerrar Turno
  const [modalTurnoVisible, setModalTurnoVisible] = useState(false);
  const [cargandoResumen, setCargandoResumen] = useState(false);
  const [resumenTurno, setResumenTurno] = useState<any>(null);

  // Cronómetro en vivo: tick cada segundo
  useEffect(() => {
    const timer = setInterval(() => {
      setSegundosTick((prev) => prev + 1);
    }, 1000);
    return () => clearInterval(timer);
  }, []);

  const cargar = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getComandas();
    if (res.ok) {
      setComandas(res.comandas);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  // Polling automático continuo cada 3.5 segundos
  useEffect(() => {
    cargar();
    const t = setInterval(() => cargar(), 3500);
    return () => clearInterval(t);
  }, [cargar]);

  const cocId = cocinero?.idempleado || cocinero?.id || 0;

  // Filtrar comandas según la pestaña activa
  // Pendientes: Comandas con 'Recibido' o no asignadas a ningún cocinero
  const comandasPendientes = comandas.filter(
    (c) => c.estado === 'Recibido' || (!c.idempleado_cocinero && c.estado !== 'Listo' && c.estado !== 'Listo para Despacho')
  );

  // En Preparación: Solo las que tomó este cocinero en su sesión
  const comandasEnPrep = comandas.filter((c) => {
    const esPrep = c.estado === 'En Preparación' || c.estado === 'En Preparacion' || c.estado === 'En Cocina';
    const esMio = c.idempleado_cocinero === cocId || !c.idempleado_cocinero;
    return esPrep && esMio;
  });

  const comandasMostradas = filtroTab === 'pendientes' ? comandasPendientes : comandasEnPrep;

  // Acción: Tomar Comanda con Bloqueo Anti-Colisión
  const handleTomarComanda = async (comanda: ComandaCocina) => {
    // Optimistic UI local: mover temporalmente a preparación
    setComandas((prev) =>
      prev.map((c) =>
        c.idpedido === comanda.idpedido
          ? { ...c, estado: 'En Preparación', idempleado_cocinero: cocId, cocinero_nombre: cocinero?.nombre }
          : c
      )
    );
    setFiltroTab('preparacion');

    const res = await ApiService.tomarComanda(comanda.idpedido);
    if (!res.ok) {
      Alert.alert('Comanda No Disponible', res.error || 'Esta comanda ya fue tomada por otro cocinero.');
      cargar();
    } else {
      cargar();
    }
  };

  // Acción: Marcar Comanda Lista
  const handleMarcarLista = async (idpedido: number) => {
    // Optimistic UI: retirar de la lista activa
    setComandas((prev) => prev.filter((c) => c.idpedido !== idpedido));

    const res = await ApiService.marcarCompletado(idpedido);
    if (!res.ok) {
      Alert.alert('Error', res.error || 'No se pudo marcar la comanda como lista.');
      cargar();
    } else {
      cargar();
    }
  };

  const handleCancelar = (idpedido: number) => {
    Alert.alert(
      'Cancelar Comanda en Cocina',
      `¿Cancelar comanda #${idpedido}?\nSeleccioná el motivo:`,
      [
        { text: 'Volver', style: 'cancel' },
        {
          text: 'Falta de insumo crítico',
          onPress: async () => {
            await ApiService.cancelarComanda(idpedido, 'Falta de insumo en cocina');
            cargar();
          },
        },
        {
          text: 'Error en pedido / duplicado',
          onPress: async () => {
            await ApiService.cancelarComanda(idpedido, 'Comanda duplicada o cancelada por salón');
            cargar();
          },
        },
      ]
    );
  };

  // Abrir Modal de Ticket de Rendimiento al presionar Cerrar Turno
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
  };

  // Formateador de tiempo transcurrido en vivo (MM:SS)
  const formatearTiempoTranscurrido = (fechaStr: string) => {
    const diffMs = Math.max(0, Date.now() - new Date(fechaStr).getTime());
    const totalSegs = Math.floor(diffMs / 1000);
    const mins = Math.floor(totalSegs / 60);
    const segs = totalSegs % 60;
    return `${mins.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
  };

  const getSlaColor = (fechaStr: string) => {
    const mins = Math.floor((Date.now() - new Date(fechaStr).getTime()) / 60000);
    if (mins < 5) return '#10B981'; // Verde
    if (mins < 10) return '#F59E0B'; // Amarillo
    return '#DC2626'; // Rojo crítico (>10 min)
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      {/* Header Corporativo */}
      <View style={styles.header}>
        <View style={{ flex: 1, paddingRight: 10 }}>
          <Text style={styles.headerTitle}>🍳 MOSTAZA COCINA KDS</Text>
          <Text style={styles.headerSub} numberOfLines={1}>
            👨‍🍳 {cocinero?.nombre || 'Cocinero'} · Sucursal Formosa
          </Text>
        </View>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
          <View style={styles.badgeOnline}>
            <Text style={styles.badgeOnlineText}>
              ● {comandasPendientes.length} EN COLA
            </Text>
          </View>
          <TouchableOpacity
            style={styles.btnLogout}
            onPress={abrirCierreTurno}
            activeOpacity={0.8}>
            <Text style={styles.btnLogoutText}>🚪 Cerrar Turno</Text>
          </TouchableOpacity>
        </View>
      </View>

      {/* Pestañas de Dinamismo en Cocina */}
      <View style={styles.tabsRow}>
        <TouchableOpacity
          style={[styles.tabBtn, filtroTab === 'pendientes' && styles.tabBtnActive]}
          onPress={() => setFiltroTab('pendientes')}
          activeOpacity={0.7}>
          <Text
            style={[
              styles.tabBtnTxt,
              filtroTab === 'pendientes' && styles.tabBtnTxtActive,
            ]}>
            📋 Pendientes / Entrantes ({comandasPendientes.length})
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.tabBtn, filtroTab === 'preparacion' && styles.tabBtnActive]}
          onPress={() => setFiltroTab('preparacion')}
          activeOpacity={0.7}>
          <Text
            style={[
              styles.tabBtnTxt,
              filtroTab === 'preparacion' && styles.tabBtnTxtActive,
            ]}>
            🍳 En Preparación ({comandasEnPrep.length})
          </Text>
        </TouchableOpacity>
      </View>

      {/* Grid de Comandas */}
      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargar(true)} />}>
        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.amarillo} style={{ marginTop: 40 }} />
        ) : comandasMostradas.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={{ fontSize: 40, marginBottom: 10 }}>
              {filtroTab === 'pendientes' ? '🎉' : '👨‍🍳'}
            </Text>
            <Text style={styles.emptyTitle}>
              {filtroTab === 'pendientes'
                ? '¡Cola de Cocina al Día!'
                : 'No tenés comandas en preparación'}
            </Text>
            <Text style={styles.emptySub}>
              {filtroTab === 'pendientes'
                ? 'No hay comandas entrantes pendientes de toma.'
                : 'Seleccioná una comanda en la pestaña "Pendientes" para comenzar.'}
            </Text>
          </View>
        ) : (
          <View style={styles.grid}>
            {comandasMostradas.map((c) => {
              const tiempoVivo = formatearTiempoTranscurrido(c.fecha);
              const colorSla = getSlaColor(c.fecha);
              const esMiComanda = filtroTab === 'preparacion';

              return (
                <View
                  key={c.idpedido}
                  style={[
                    styles.kdsCard,
                    esMiComanda && { borderColor: '#F59E0B', borderWidth: 2 },
                  ]}>
                  <View style={styles.cardTop}>
                    <Text style={styles.comandaNum}>#{c.idpedido}</Text>
                    <Text style={styles.tipoBadge}>
                      {c.tipo_entrega === 'salon'
                        ? `Mesa #${c.numero_mesa || '-'}`
                        : c.tipo_entrega === 'takeaway'
                        ? '🛍️ Take Away'
                        : '🛵 Delivery'}
                    </Text>
                  </View>

                  {/* Temporizador SLA en vivo */}
                  <View style={styles.timerRow}>
                    <Text style={[styles.timerTxt, { color: colorSla }]}>
                      ⏱️ Tiempo: {tiempoVivo} min
                    </Text>
                    {esMiComanda && (
                      <Text style={styles.badgePrep}>EN PLANCHA</Text>
                    )}
                  </View>

                  {/* Desglose de Productos */}
                  <View style={styles.itemsBox}>
                    {c.items.map((it, idx) => (
                      <View key={idx} style={styles.itemRow}>
                        <Text style={styles.itemTxt}>
                          <Text style={{ color: MostazaColors.amarillo, fontWeight: '900' }}>
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

                  {/* Acciones de la Tarjeta */}
                  <View style={styles.actions}>
                    {filtroTab === 'pendientes' ? (
                      <TouchableOpacity
                        style={[styles.btnAction, styles.btnTomar]}
                        onPress={() => handleTomarComanda(c)}>
                        <Text style={styles.btnTomarTxt}>👨‍🍳 Tomar y Preparar</Text>
                      </TouchableOpacity>
                    ) : (
                      <TouchableOpacity
                        style={[styles.btnAction, styles.btnReady]}
                        onPress={() => handleMarcarLista(c.idpedido)}>
                        <Text style={styles.btnReadyTxt}>✅ ¡MARCAR LISTO!</Text>
                      </TouchableOpacity>
                    )}

                    <TouchableOpacity
                      style={[styles.btnAction, styles.btnCancel]}
                      onPress={() => handleCancelar(c.idpedido)}>
                      <Text style={styles.btnCancelTxt}>✕</Text>
                    </TouchableOpacity>
                  </View>
                </View>
              );
            })}
          </View>
        )}
      </ScrollView>

      {/* Modal: Ticket de Rendimiento al Cerrar Turno */}
      <Modal visible={modalTurnoVisible} transparent animationType="fade">
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            <View style={styles.modalHeader}>
              <Text style={styles.modalHeaderTitle}>📋 TICKET DE RENDIMIENTO</Text>
              <Text style={styles.modalHeaderSub}>Cierre de Turno · Cocina Mostaza</Text>
            </View>

            {cargandoResumen ? (
              <View style={{ padding: 30, alignItems: 'center' }}>
                <ActivityIndicator size="large" color={MostazaColors.amarillo} />
                <Text style={{ marginTop: 12, color: '#64748B', fontWeight: '700' }}>
                  Calculando estadísticas de la jornada...
                </Text>
              </View>
            ) : (
              <View style={styles.ticketContent}>
                <View style={styles.ticketRow}>
                  <Text style={styles.ticketLbl}>👨‍🍳 Cocinero:</Text>
                  <Text style={styles.ticketVal}>{cocinero?.nombre || 'Martín Paredes'}</Text>
                </View>

                <View style={styles.ticketRow}>
                  <Text style={styles.ticketLbl}>🕒 Ingreso Turno:</Text>
                  <Text style={styles.ticketVal}>
                    {resumenTurno?.turno?.hora_entrada || 'Registrado'} hs
                  </Text>
                </View>

                <View style={styles.ticketRow}>
                  <Text style={styles.ticketLbl}>⏱️ Horas Trabajadas:</Text>
                  <Text style={[styles.ticketVal, { color: MostazaColors.rojo }]}>
                    {resumenTurno?.turno?.horas_trabajadas_texto || 'En curso'}
                  </Text>
                </View>

                <View style={styles.ticketDivider} />

                {/* Métricas clave */}
                <View style={styles.metricsBox}>
                  <View style={styles.metricCol}>
                    <Text style={styles.metricNum}>
                      {resumenTurno?.rendimiento?.comandas_elaboradas ?? 0}
                    </Text>
                    <Text style={styles.metricLabel}>Comandas</Text>
                  </View>

                  <View style={styles.metricDivV} />

                  <View style={styles.metricCol}>
                    <Text style={styles.metricNum}>
                      {resumenTurno?.rendimiento?.items_cocinados ?? 0}
                    </Text>
                    <Text style={styles.metricLabel}>Platos / Ítems</Text>
                  </View>

                  <View style={styles.metricDivV} />

                  <View style={styles.metricCol}>
                    <Text style={styles.metricNum}>
                      {resumenTurno?.rendimiento?.tiempo_promedio_texto || '3.5 min'}
                    </Text>
                    <Text style={styles.metricLabel}>Tiempo Medio</Text>
                  </View>
                </View>

                <Text style={styles.ticketNotice}>
                  Al confirmar, se registrará tu hora de salida en el reloj central y se cerrará tu sesión.
                </Text>
              </View>
            )}

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.btnModalCancel}
                onPress={() => setModalTurnoVisible(false)}>
                <Text style={styles.btnModalCancelTxt}>Volver a Cocina</Text>
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
  headerTitle: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2 },
  badgeOnline: { backgroundColor: '#DCFCE7', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20, borderWidth: 1, borderColor: '#86EFAC' },
  badgeOnlineText: { color: '#166534', fontSize: 11, fontWeight: '900' },
  btnLogout: {
    backgroundColor: '#FEE2E2',
    borderWidth: 1,
    borderColor: '#FECACA',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  btnLogoutText: { color: '#DC2626', fontSize: 11, fontWeight: '800' },

  tabsRow: {
    flexDirection: 'row',
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    gap: 8,
  },
  tabBtn: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 10,
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  tabBtnActive: {
    backgroundColor: MostazaColors.rojo,
    borderColor: MostazaColors.rojo,
  },
  tabBtnTxt: { fontSize: 12, fontWeight: '800', color: '#475569' },
  tabBtnTxtActive: { color: '#FFFFFF' },

  scroll: { padding: 16 },
  emptyCard: { backgroundColor: '#FFFFFF', borderRadius: 16, padding: 30, alignItems: 'center', marginTop: 30, borderWidth: 1, borderColor: '#E2E8F0' },
  emptyTitle: { fontSize: 18, fontWeight: '900', color: '#0F172A' },
  emptySub: { fontSize: 12, color: '#64748B', marginTop: 4, textAlign: 'center' },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  kdsCard: {
    width: '48%',
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    shadowColor: '#000000',
    shadowOpacity: 0.04,
    shadowRadius: 8,
    elevation: 2,
  },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  comandaNum: { fontSize: 18, fontWeight: '900', color: MostazaColors.rojo },
  tipoBadge: { backgroundColor: '#FEF3C7', color: '#92400E', fontSize: 10, fontWeight: '800', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6 },
  timerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 4 },
  timerTxt: { fontSize: 11, fontWeight: '900' },
  badgePrep: { fontSize: 9, fontWeight: '900', backgroundColor: '#FEF3C7', color: '#B45309', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4 },
  itemsBox: { backgroundColor: '#F8FAFC', borderRadius: 10, padding: 10, marginTop: 8, minHeight: 90, borderWidth: 1, borderColor: '#F1F5F9' },
  itemRow: { marginBottom: 6 },
  itemTxt: { color: '#0F172A', fontSize: 12, fontWeight: '700' },
  modTxt: { color: '#B45309', fontSize: 10, fontStyle: 'italic', marginTop: 1 },
  actions: { flexDirection: 'row', gap: 6, marginTop: 10 },
  btnAction: { paddingVertical: 10, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  btnTomar: { flex: 1, backgroundColor: MostazaColors.amarillo },
  btnTomarTxt: { color: '#0F172A', fontWeight: '900', fontSize: 12 },
  btnReady: { flex: 1, backgroundColor: '#10B981' },
  btnReadyTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 12 },
  btnCancel: { width: 36, backgroundColor: '#FEE2E2', borderWidth: 1, borderColor: '#FECACA' },
  btnCancelTxt: { color: '#DC2626', fontWeight: '900', fontSize: 14 },

  // Modal Ticket Rendimiento
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 18,
    width: '100%',
    maxWidth: 440,
    padding: 20,
    shadowColor: '#000',
    shadowOpacity: 0.15,
    shadowRadius: 15,
    elevation: 8,
  },
  modalHeader: {
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    paddingBottom: 12,
    marginBottom: 14,
  },
  modalHeaderTitle: { fontSize: 17, fontWeight: '900', color: MostazaColors.rojo },
  modalHeaderSub: { fontSize: 12, color: '#64748B', marginTop: 2, fontWeight: '600' },
  ticketContent: { paddingHorizontal: 4 },
  ticketRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  ticketLbl: { fontSize: 12, color: '#64748B', fontWeight: '700' },
  ticketVal: { fontSize: 12, color: '#0F172A', fontWeight: '900' },
  ticketDivider: { height: 1, backgroundColor: '#E2E8F0', marginVertical: 12 },
  metricsBox: {
    flexDirection: 'row',
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    paddingVertical: 12,
    marginBottom: 14,
  },
  metricCol: { flex: 1, alignItems: 'center' },
  metricNum: { fontSize: 18, fontWeight: '900', color: MostazaColors.rojo },
  metricLabel: { fontSize: 10, color: '#64748B', fontWeight: '700', marginTop: 2 },
  metricDivV: { width: 1, backgroundColor: '#CBD5E1' },
  ticketNotice: { fontSize: 11, color: '#94A3B8', textAlign: 'center', fontStyle: 'italic', marginBottom: 14 },
  modalActions: { flexDirection: 'row', gap: 10 },
  btnModalCancel: {
    flex: 1,
    backgroundColor: '#F1F5F9',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  btnModalCancelTxt: { color: '#475569', fontWeight: '800', fontSize: 12 },
  btnModalConfirm: {
    flex: 1.2,
    backgroundColor: '#DC2626',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
  },
  btnModalConfirmTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 12 },
});
