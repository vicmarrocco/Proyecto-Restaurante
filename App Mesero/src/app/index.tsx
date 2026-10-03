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
import { useRouter } from 'expo-router';
import { ApiService } from '@/services/api';
import { Mesa, Seccion } from '@/types/mesero';
import { MostazaColors } from '@/constants/Colors';
import { useAuth } from '@/context/AuthContext';

export default function MesasScreen() {
  const router = useRouter();
  const { mesero, logout } = useAuth();
  const [mesas, setMesas] = useState<Mesa[]>([]);
  const [secciones, setSecciones] = useState<Seccion[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  // Inicializar en la sección asignada al mesero (1: Salón Principal, 2: Terraza Exterior, etc.)
  const miSeccionId = mesero?.fk_id_seccion || mesero?.idseccion || 1;
  const [filtroSeccion, setFiltroSeccion] = useState<number | null>(miSeccionId);

  // Modal Ticket Rendimiento al Cerrar Turno
  const [modalTurnoVisible, setModalTurnoVisible] = useState(false);
  const [cargandoResumen, setCargandoResumen] = useState(false);
  const [resumenTurno, setResumenTurno] = useState<any>(null);

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

  const cargarMesas = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getMesas();
    if (res.ok) {
      setMesas(Array.isArray(res.mesas) ? res.mesas : []);
      setSecciones(Array.isArray(res.secciones) ? res.secciones : []);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  // Polling reactivo cada 3.5 segundos en segundo plano
  useEffect(() => {
    cargarMesas();
    const timer = setInterval(() => cargarMesas(), 3500);
    return () => clearInterval(timer);
  }, [cargarMesas]);

  const safeMesas = Array.isArray(mesas) ? mesas : [];
  const safeSecciones = Array.isArray(secciones) ? secciones : [];

  const mesasFiltradas = filtroSeccion
    ? safeMesas.filter((m) => m.idseccion === filtroSeccion)
    : safeMesas;

  // Nombre de la sección asignada al mesero
  const seccionAsignada = safeSecciones.find((s) => s.idseccion === miSeccionId);
  const seccionAsignadaNombre = mesero?.seccion_nombre || seccionAsignada?.nombre || 'Salón Principal';
  const rangoMesas = miSeccionId === 1 ? '1 a 8' : miSeccionId === 2 ? '9 a 14' : miSeccionId === 3 ? '15 a 20' : '21 a 24';

  const handleMesaPress = (mesa: Mesa) => {
    if (mesa.ocupada) {
      const comandaTxt = mesa.info
        ? `Cliente: ${mesa.info.cliente}\nComanda: #${mesa.info.idpedido}\nTotal: ${mesa.info.total_formateado}\nEstado: ${mesa.info.estado}\nTiempo: ${mesa.info.tiempo_texto}`
        : 'Mesa ocupada por comensales (sin comanda ingresada aún)';

      Alert.alert(
        `Mesa #${mesa.numero} (${mesa.seccion_nombre}) · Ocupada`,
        comandaTxt,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: '🟢 Desocupar Mesa',
            style: 'destructive',
            onPress: () => {
              Alert.alert(
                '¿Desocupar Mesa?',
                `¿Confirmás liberar la Mesa #${mesa.numero}? Las comandas activas se darán por finalizadas.`,
                [
                  { text: 'No', style: 'cancel' },
                  {
                    text: 'Sí, Desocupar',
                    onPress: async () => {
                      const res = await ApiService.desocuparMesa(mesa.numero);
                      if (res.ok) {
                        cargarMesas();
                      } else {
                        Alert.alert('Error', res.error || 'No se pudo desocupar la mesa');
                      }
                    },
                  },
                ]
              );
            },
          },
          {
            text: '➕ Agregar Ítems',
            onPress: () => router.push({ pathname: '/comanda', params: { mesa: mesa.numero } }),
          },
        ]
      );
    } else {
      Alert.alert(
        `Mesa #${mesa.numero} (${mesa.seccion_nombre}) · Libre`,
        `Capacidad: ${mesa.capacidad} personas.\n¿Qué acción deseás realizar?`,
        [
          { text: 'Cancelar', style: 'cancel' },
          {
            text: '🔴 Ocupar Mesa',
            onPress: async () => {
              const res = await ApiService.ocuparMesa(mesa.numero);
              if (res.ok) {
                cargarMesas();
              } else {
                Alert.alert('Error', res.error || 'No se pudo marcar la mesa como ocupada');
              }
            },
          },
          {
            text: '📝 Tomar Comanda',
            onPress: () => router.push({ pathname: '/comanda', params: { mesa: mesa.numero } }),
          },
        ]
      );
    }
  };

  const totalLibres = safeMesas.filter((m) => !m.ocupada).length;
  const totalOcupadas = safeMesas.filter((m) => m.ocupada).length;
  const totalListas = safeMesas.filter(
    (m) => m.info?.estado === 'Listo' || m.info?.estado === 'Listo para Despacho'
  ).length;

  return (
    <SafeAreaView style={styles.safeArea}>
      {/* Header Corporativo */}
      <View style={styles.header}>
        <View style={{ flex: 1, paddingRight: 10 }}>
          <Text style={styles.headerTitle}>🍽️ MOSTAZA MESERO</Text>
          <Text style={styles.headerSub} numberOfLines={1}>
            🤵 {mesero?.nombre || 'Franco Morales'} · Salón Formosa
          </Text>
        </View>
        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8 }}>
          <View style={styles.badgeOnline}>
            <Text style={styles.badgeOnlineText}>● EN VIVO</Text>
          </View>
          <TouchableOpacity
            style={styles.btnLogout}
            onPress={abrirCierreTurno}
            activeOpacity={0.8}>
            <Text style={styles.btnLogoutText}>🚪 Cerrar Turno</Text>
          </TouchableOpacity>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargarMesas(true)} />}>
        
        {/* Banner de Área Asignada al Mesero */}
        <View style={styles.bannerArea}>
          <View style={{ flex: 1 }}>
            <Text style={styles.bannerAreaTitle}>
              📍 Mi Área Asignada: {seccionAsignadaNombre}
            </Text>
            <Text style={styles.bannerAreaSub}>
              Mesas del sector: {rangoMesas} · Prioridad de atención
            </Text>
          </View>
          {filtroSeccion !== null ? (
            <TouchableOpacity
              style={styles.btnVerTodas}
              onPress={() => setFiltroSeccion(null)}>
              <Text style={styles.btnVerTodasTxt}>Ver Todas</Text>
            </TouchableOpacity>
          ) : (
            <TouchableOpacity
              style={[styles.btnVerTodas, { backgroundColor: MostazaColors.amarillo }]}
              onPress={() => setFiltroSeccion(miSeccionId)}>
              <Text style={[styles.btnVerTodasTxt, { color: '#0F172A' }]}>Mi Sector</Text>
            </TouchableOpacity>
          )}
        </View>

        {/* Barra de Métricas en Vivo */}
        <View style={styles.metricsBar}>
          <View style={styles.metricItem}>
            <Text style={styles.metricVal}>{totalLibres}</Text>
            <Text style={styles.metricLbl}>🟢 LIBRES</Text>
          </View>
          <View style={styles.metricDiv} />
          <View style={styles.metricItem}>
            <Text style={[styles.metricVal, { color: MostazaColors.rojo }]}>
              {totalOcupadas}
            </Text>
            <Text style={styles.metricLbl}>🔴 OCUPADAS</Text>
          </View>
          <View style={styles.metricDiv} />
          <View style={styles.metricItem}>
            <Text style={[styles.metricVal, { color: MostazaColors.amarillo }]}>
              {totalListas}
            </Text>
            <Text style={styles.metricLbl}>🔔 LISTAS</Text>
          </View>
        </View>

        {/* Filtro por Secciones */}
        <View style={styles.seccionesNav}>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.pillsScroll}>
            <TouchableOpacity
              style={[styles.pill, filtroSeccion === null && styles.pillActive]}
              onPress={() => setFiltroSeccion(null)}>
              <Text style={[styles.pillTxt, filtroSeccion === null && styles.pillTxtActive]}>
                Todas ({safeMesas.length})
              </Text>
            </TouchableOpacity>

            {safeSecciones.map((sec) => {
              const count = safeMesas.filter((m) => m.idseccion === sec.idseccion).length;
              return (
                <TouchableOpacity
                  key={sec.idseccion}
                  style={[styles.pill, filtroSeccion === sec.idseccion && styles.pillActive]}
                  onPress={() => setFiltroSeccion(sec.idseccion)}>
                  <Text style={[styles.pillTxt, filtroSeccion === sec.idseccion && styles.pillTxtActive]}>
                    {sec.nombre} ({count})
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        </View>

        <Text style={styles.sectionTitle}>
          Mapa de Mesas {filtroSeccion ? `· ${safeSecciones.find(s => s.idseccion === filtroSeccion)?.nombre || ''}` : '· Todas'}
        </Text>

        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.amarillo} style={{ marginTop: 40 }} />
        ) : mesasFiltradas.length === 0 ? (
          <View style={styles.emptyBox}>
            <Text style={{ fontSize: 32, marginBottom: 8 }}>🪑</Text>
            <Text style={styles.emptyTxt}>No hay mesas configuradas en esta sección.</Text>
          </View>
        ) : (
          <View style={styles.grid}>
            {mesasFiltradas.map((mesa) => {
              const esLista = mesa.info?.estado === 'Listo' || mesa.info?.estado === 'Listo para Despacho';
              return (
                <TouchableOpacity
                  key={mesa.idmesa}
                  style={[
                    styles.cardMesa,
                    mesa.ocupada ? styles.cardOcupada : styles.cardLibre,
                    esLista && styles.cardLista,
                  ]}
                  onPress={() => handleMesaPress(mesa)}
                  activeOpacity={0.8}>
                  <View style={styles.cardHeader}>
                    <Text style={styles.mesaNum}>Mesa {mesa.numero}</Text>
                    <View
                      style={[
                        styles.tagEstado,
                        {
                          backgroundColor: mesa.ocupada
                            ? esLista
                              ? '#F59E0B'
                              : MostazaColors.rojo
                            : '#10B981',
                        },
                      ]}>
                      <Text
                        style={[
                          styles.tagText,
                          esLista && { color: '#0F172A', fontWeight: '900' },
                        ]}>
                        {mesa.ocupada ? (esLista ? '🔔 ¡LISTA!' : 'COCINA') : 'LIBRE'}
                      </Text>
                    </View>
                  </View>

                  <Text style={styles.seccionTxt}>👥 {mesa.capacidad}p · {mesa.seccion_nombre}</Text>

                  {mesa.ocupada && mesa.info ? (
                    <View style={styles.infoBox}>
                      <Text style={styles.infoPed}>Comanda #{mesa.info.idpedido}</Text>
                      <Text style={styles.infoTot}>{mesa.info.total_formateado}</Text>
                      <Text style={styles.infoTime}>🕒 {mesa.info.tiempo_texto}</Text>
                      {esLista && (
                        <View style={styles.badgeAlertaLista}>
                          <Text style={styles.badgeAlertaListaTxt}>
                            🛎️ ¡Llevar a mesa ahora!
                          </Text>
                        </View>
                      )}
                    </View>
                  ) : (
                    <View style={styles.libreBox}>
                      <Text style={styles.libreTxt}>+ Abrir Comanda</Text>
                    </View>
                  )}
                </TouchableOpacity>
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
              <Text style={styles.modalHeaderSub}>Cierre de Turno · Salón Mostaza</Text>
            </View>

            {cargandoResumen ? (
              <View style={{ padding: 30, alignItems: 'center' }}>
                <ActivityIndicator size="large" color={MostazaColors.amarillo} />
                <Text style={{ marginTop: 12, color: '#64748B', fontWeight: '700' }}>
                  Calculando rendimiento en salón...
                </Text>
              </View>
            ) : (
              <View style={styles.ticketContent}>
                <View style={styles.ticketRow}>
                  <Text style={styles.ticketLbl}>🤵 Mesero:</Text>
                  <Text style={styles.ticketVal}>{mesero?.nombre || 'Franco Morales'}</Text>
                </View>

                <View style={styles.ticketRow}>
                  <Text style={styles.ticketLbl}>📍 Área:</Text>
                  <Text style={styles.ticketVal}>{seccionAsignadaNombre}</Text>
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
                      {resumenTurno?.rendimiento?.mesas_atendidas ?? 0}
                    </Text>
                    <Text style={styles.metricLabel}>Mesas Atendidas</Text>
                  </View>

                  <View style={styles.metricDivV} />

                  <View style={styles.metricCol}>
                    <Text style={[styles.metricNum, { color: '#16A34A' }]}>
                      {resumenTurno?.rendimiento?.total_recaudado_formateado || '$0'}
                    </Text>
                    <Text style={styles.metricLabel}>Total Recaudado</Text>
                  </View>

                  <View style={styles.metricDivV} />

                  <View style={styles.metricCol}>
                    <Text style={styles.metricNum}>
                      {resumenTurno?.rendimiento?.ticket_promedio || '$0'}
                    </Text>
                    <Text style={styles.metricLabel}>Ticket Medio</Text>
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
                <Text style={styles.btnModalCancelTxt}>Volver al Salón</Text>
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
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2 },
  badgeOnline: { backgroundColor: '#DCFCE7', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20 },
  badgeOnlineText: { color: '#166534', fontSize: 10, fontWeight: '800' },
  btnLogout: {
    backgroundColor: '#FEE2E2',
    borderWidth: 1,
    borderColor: '#FECACA',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
  },
  btnLogoutText: { color: '#DC2626', fontSize: 11, fontWeight: '800' },

  bannerArea: {
    backgroundColor: '#EFF6FF',
    marginHorizontal: 16,
    marginTop: 10,
    marginBottom: 8,
    borderRadius: 12,
    paddingHorizontal: 14,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  bannerAreaTitle: { fontSize: 12, fontWeight: '900', color: '#1E40AF' },
  bannerAreaSub: { fontSize: 10, color: '#3B82F6', marginTop: 2, fontWeight: '600' },
  btnVerTodas: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#93C5FD',
  },
  btnVerTodasTxt: { fontSize: 11, fontWeight: '800', color: '#1D4ED8' },

  scroll: { padding: 16 },
  metricsBar: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 12,
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 14,
    shadowColor: '#000000',
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  metricItem: { alignItems: 'center' },
  metricVal: { fontSize: 20, fontWeight: '900', color: '#10B981' },
  metricLbl: { fontSize: 10, fontWeight: '800', color: '#64748B', marginTop: 2 },
  metricDiv: { width: 1, height: 28, backgroundColor: '#E2E8F0' },

  seccionesNav: { marginBottom: 14 },
  pillsScroll: { gap: 8 },
  pill: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  pillActive: { backgroundColor: MostazaColors.rojo, borderColor: MostazaColors.rojo },
  pillTxt: { color: '#64748B', fontSize: 11, fontWeight: '700' },
  pillTxtActive: { color: '#FFFFFF', fontWeight: '900' },

  sectionTitle: { fontSize: 13, fontWeight: '800', color: '#0F172A', marginBottom: 12 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  cardMesa: {
    width: '48%',
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    shadowColor: '#000000',
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  cardLibre: { borderColor: '#10B981' },
  cardOcupada: { borderColor: MostazaColors.rojo, backgroundColor: '#FFF5F5' },
  cardLista: { borderColor: '#F59E0B', backgroundColor: '#FFFBEB' },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  mesaNum: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  tagEstado: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  tagText: { color: '#FFFFFF', fontSize: 9, fontWeight: '900' },
  seccionTxt: { fontSize: 10, color: '#64748B', marginTop: 4 },
  infoBox: { backgroundColor: '#F8FAFC', padding: 8, borderRadius: 8, marginTop: 8, borderWidth: 1, borderColor: '#F1F5F9' },
  infoPed: { fontSize: 11, fontWeight: '800', color: MostazaColors.rojo },
  infoTot: { fontSize: 12, fontWeight: '900', color: '#0F172A', marginTop: 2 },
  infoTime: { fontSize: 9, color: '#64748B', marginTop: 2 },
  badgeAlertaLista: {
    backgroundColor: '#FEF3C7',
    paddingVertical: 3,
    paddingHorizontal: 6,
    borderRadius: 6,
    marginTop: 6,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  badgeAlertaListaTxt: { fontSize: 9, fontWeight: '900', color: '#B45309', textAlign: 'center' },
  libreBox: { marginTop: 10, paddingVertical: 6, alignItems: 'center' },
  libreTxt: { color: '#10B981', fontSize: 11, fontWeight: '800' },
  emptyBox: { alignItems: 'center', paddingVertical: 60 },
  emptyTxt: { color: '#64748B', fontSize: 13, fontWeight: '700' },

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
  metricNum: { fontSize: 17, fontWeight: '900', color: MostazaColors.rojo },
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
