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
  TextInput,
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

  // Inicializar en las secciones asignadas al mesero (soporte multi-sector)
  const miSeccionId = Number(mesero?.fk_id_seccion || mesero?.idseccion || 1);
  const misSeccionesIds: number[] =
    Array.isArray(mesero?.secciones_asignadas) && mesero.secciones_asignadas.length > 0
      ? mesero.secciones_asignadas.map((x: any) => Number(x))
      : [miSeccionId];
  const [filtroSeccion, setFiltroSeccion] = useState<'mis_sectores' | number | null>(
    misSeccionesIds.length > 1 ? 'mis_sectores' : miSeccionId
  );

  // Modal: Ubicar Cliente en Mesa y Tomar Pedido
  const [modalUbicarVisible, setModalUbicarVisible] = useState(false);
  const [mesaSeleccionadaUbicar, setMesaSeleccionadaUbicar] = useState<number>(0);
  const [clienteNombreInput, setClienteNombreInput] = useState('');
  const [comensalesInput, setComensalesInput] = useState<number>(2);
  const [procesandoUbicacion, setProcesandoUbicacion] = useState(false);

  // Modal: Gestión de Mesa Ocupada
  const [modalOcupadaVisible, setModalOcupadaVisible] = useState(false);
  const [mesaActivaModal, setMesaActivaModal] = useState<Mesa | null>(null);
  const [confirmandoDesocupar, setConfirmandoDesocupar] = useState(false);
  const [procesandoDesocupar, setProcesandoDesocupar] = useState(false);

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

  const mesasFiltradas =
    filtroSeccion === 'mis_sectores'
      ? safeMesas.filter((m) => misSeccionesIds.includes(Number(m.idseccion)))
      : typeof filtroSeccion === 'number'
      ? safeMesas.filter((m) => Number(m.idseccion) === filtroSeccion)
      : safeMesas;

  // Mesas de los sectores asignados al mesero
  const misMesas = safeMesas.filter((m) => misSeccionesIds.includes(Number(m.idseccion)));
  const misMesasLibres = misMesas.filter((m) => !m.ocupada);

  // Nombre de la(s) sección(es) asignada(s) al mesero
  const seccionAsignada = safeSecciones.find((s) => Number(s.idseccion) === miSeccionId);
  const nombresMisSecciones = safeSecciones
    .filter((s) => misSeccionesIds.includes(Number(s.idseccion)))
    .map((s) => s.nombre);
  const seccionAsignadaNombre =
    mesero?.seccion_nombre ||
    (nombresMisSecciones.length > 0 ? nombresMisSecciones.join(' + ') : seccionAsignada?.nombre || 'Salón Principal');
  const misMesasNums = misMesas.map((m) => m.numero);
  const rangoMesas =
    misMesasNums.length > 0
      ? `${Math.min(...misMesasNums)} a ${Math.max(...misMesasNums)} (${misMesasNums.length} mesas habilitadas)`
      : miSeccionId === 1
      ? '1 a 8'
      : miSeccionId === 2
      ? '9 a 14'
      : miSeccionId === 3
      ? '15 a 20'
      : '21 a 24';

  // Abrir modal para ubicar cliente (preseleccionando la mesa tocada o la primera libre del sector)
  const abrirModalUbicarCliente = (mesaPreseleccionada?: Mesa) => {
    const mesaInicial =
      mesaPreseleccionada ||
      misMesasLibres[0] ||
      misMesas[0] ||
      null;

    if (!mesaInicial) {
      Alert.alert('Sin mesas disponibles', 'No se encontraron mesas configuradas en tus sectores asignados.');
      return;
    }

    setMesaSeleccionadaUbicar(mesaInicial.numero);
    setComensalesInput(mesaInicial.capacidad || 2);
    setClienteNombreInput('');
    setModalUbicarVisible(true);
  };

  // Acción 1: Ubicar al cliente en la mesa seleccionada e ir directo a tomar el pedido
  const confirmarUbicarYTomarPedido = async () => {
    if (!mesaSeleccionadaUbicar) {
      Alert.alert('Seleccioná una mesa', 'Por favor elegí en qué mesa ubicar al cliente.');
      return;
    }
    setProcesandoUbicacion(true);
    const nombreFinal = clienteNombreInput.trim() || `Cliente Mesa #${mesaSeleccionadaUbicar}`;
    const res = await ApiService.ocuparMesa(mesaSeleccionadaUbicar, nombreFinal, comensalesInput);
    setProcesandoUbicacion(false);

    if (res.ok) {
      setModalUbicarVisible(false);
      await cargarMesas();
      router.push({
        pathname: '/comanda',
        params: {
          mesa: String(mesaSeleccionadaUbicar),
          cliente: nombreFinal,
          comensales: String(comensalesInput),
        },
      });
    } else {
      Alert.alert('Aviso de Sección', res.error || 'No se pudo ubicar al cliente en la mesa seleccionada.');
    }
  };

  // Acción 2: Solo ubicar al cliente en la mesa (marcar ocupada y tomar pedido más tarde)
  const confirmarSoloUbicarEnMesa = async () => {
    if (!mesaSeleccionadaUbicar) return;
    setProcesandoUbicacion(true);
    const nombreFinal = clienteNombreInput.trim() || `Cliente Mesa #${mesaSeleccionadaUbicar}`;
    const res = await ApiService.ocuparMesa(mesaSeleccionadaUbicar, nombreFinal, comensalesInput);
    setProcesandoUbicacion(false);

    if (res.ok) {
      setModalUbicarVisible(false);
      cargarMesas();
    } else {
      Alert.alert('Aviso de Sección', res.error || 'No se pudo ocupar la mesa.');
    }
  };

  // Acción en mesa ocupada: desocupar y liberar
  const ejecutarDesocuparMesa = async () => {
    if (!mesaActivaModal) return;
    setProcesandoDesocupar(true);
    const res = await ApiService.desocuparMesa(mesaActivaModal.numero);
    setProcesandoDesocupar(false);
    if (res.ok) {
      setConfirmandoDesocupar(false);
      setModalOcupadaVisible(false);
      setMesaActivaModal(null);
      cargarMesas();
    } else {
      Alert.alert('Error', res.error || 'No se pudo desocupar la mesa.');
    }
  };

  const handleMesaPress = (mesa: Mesa) => {
    const esMiSeccion = misSeccionesIds.includes(Number(mesa.idseccion));

    if (!esMiSeccion) {
      Alert.alert(
        '🔒 Mesa Fuera de tu Sección',
        `La Mesa #${mesa.numero} pertenece a "${mesa.seccion_nombre}".\n\nTus sectores asignados son: "${seccionAsignadaNombre}".\n\nPor protocolo anticolisión estricto, solo podés gestionar mesas de tus sectores asignados.`,
        [{ text: 'Entendido', style: 'default' }]
      );
      return;
    }

    if (mesa.ocupada) {
      setMesaActivaModal(mesa);
      setConfirmandoDesocupar(false);
      setModalOcupadaVisible(true);
    } else {
      abrirModalUbicarCliente(mesa);
    }
  };

  const totalLibres = safeMesas.filter((m) => !m.ocupada).length;
  const totalOcupadas = safeMesas.filter((m) => m.ocupada).length;
  const totalListas = safeMesas.filter(
    (m) => m.info?.estado === 'Listo' || m.info?.estado === 'Listo para Despacho'
  ).length;

  const mesaObjUbicar = safeMesas.find((m) => m.numero === mesaSeleccionadaUbicar);

  return (
    <SafeAreaView style={styles.safeArea} edges={['top', 'left', 'right']}>
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
          <View style={{ flex: 1, paddingRight: 8 }}>
            <Text style={styles.bannerAreaTitle}>
              📍 Mi Área Asignada: {seccionAsignadaNombre}
            </Text>
            <Text style={styles.bannerAreaSub}>
              Mesas habilitadas: {rangoMesas}
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
              onPress={() => setFiltroSeccion(misSeccionesIds.length > 1 ? 'mis_sectores' : miSeccionId)}>
              <Text style={[styles.btnVerTodasTxt, { color: '#0F172A' }]}>
                {misSeccionesIds.length > 1 ? 'Mis Sectores' : 'Mi Sector'}
              </Text>
            </TouchableOpacity>
          )}
        </View>

        {/* Botón Principal para Ubicar Cliente en Mesa y Tomar Pedido */}
        <TouchableOpacity
          style={styles.btnUbicarPrincipal}
          onPress={() => abrirModalUbicarCliente()}
          activeOpacity={0.85}>
          <View style={styles.btnUbicarIconBox}>
            <Text style={{ fontSize: 22 }}>🪑</Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.btnUbicarTitle}>UBICAR CLIENTE EN MESA Y TOMAR PEDIDO</Text>
            <Text style={styles.btnUbicarSub}>
              {misMesasLibres.length > 0
                ? `${misMesasLibres.length} mesas libres en tu sector · Tocá para elegir mesa`
                : 'Seleccionar mesa de tu sector para tomar comanda'}
            </Text>
          </View>
          <View style={styles.btnUbicarArrow}>
            <Text style={styles.btnUbicarArrowTxt}>➔</Text>
          </View>
        </TouchableOpacity>

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

            {misSeccionesIds.length > 1 && (
              <TouchableOpacity
                style={[styles.pill, filtroSeccion === 'mis_sectores' && styles.pillActive]}
                onPress={() => setFiltroSeccion('mis_sectores')}>
                <Text style={[styles.pillTxt, filtroSeccion === 'mis_sectores' && styles.pillTxtActive]}>
                  📍 Mis Sectores ({misMesasNums.length})
                </Text>
              </TouchableOpacity>
            )}

            {safeSecciones.map((sec) => {
              const count = safeMesas.filter((m) => m.idseccion === sec.idseccion).length;
              const esMia = misSeccionesIds.includes(Number(sec.idseccion));
              return (
                <TouchableOpacity
                  key={sec.idseccion}
                  style={[styles.pill, filtroSeccion === sec.idseccion && styles.pillActive]}
                  onPress={() => setFiltroSeccion(sec.idseccion)}>
                  <Text style={[styles.pillTxt, filtroSeccion === sec.idseccion && styles.pillTxtActive]}>
                    {esMia ? '✓ ' : ''}{sec.nombre} ({count})
                  </Text>
                </TouchableOpacity>
              );
            })}
          </ScrollView>
        </View>

        <Text style={styles.sectionTitle}>
          Mapa de Mesas {filtroSeccion === 'mis_sectores' ? `· ${seccionAsignadaNombre}` : filtroSeccion ? `· ${safeSecciones.find(s => s.idseccion === filtroSeccion)?.nombre || ''}` : '· Todas'}
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
              const esMiSeccion = misSeccionesIds.includes(Number(mesa.idseccion));
              const esLista = mesa.info?.estado === 'Listo' || mesa.info?.estado === 'Listo para Despacho';
              const meseroAtiende = mesa.mesero_nombre || mesa.info?.mesero_nombre;
              const clienteUbicado = mesa.cliente_nombre || mesa.info?.cliente;
              const tienePedidoReal = mesa.info && mesa.info.idpedido && mesa.info.idpedido !== '-';

              return (
                <TouchableOpacity
                  key={mesa.idmesa}
                  style={[
                    styles.cardMesa,
                    !esMiSeccion && styles.cardBloqueada,
                    esMiSeccion && (mesa.ocupada ? styles.cardOcupada : styles.cardLibre),
                    esMiSeccion && esLista && styles.cardLista,
                  ]}
                  onPress={() => handleMesaPress(mesa)}
                  activeOpacity={0.8}>
                  <View style={styles.cardHeader}>
                    <Text style={[styles.mesaNum, !esMiSeccion && { color: '#64748B' }]}>
                      Mesa {mesa.numero} {!esMiSeccion && '🔒'}
                    </Text>
                    <View
                      style={[
                        styles.tagEstado,
                        !esMiSeccion
                          ? styles.tagBloqueada
                          : {
                              backgroundColor: mesa.ocupada
                                ? esLista
                                  ? '#F59E0B'
                                  : tienePedidoReal
                                  ? MostazaColors.rojo
                                  : '#D97706'
                                : '#10B981',
                            },
                      ]}>
                      <Text
                        style={[
                          styles.tagText,
                          esMiSeccion && esLista && { color: '#0F172A', fontWeight: '900' },
                        ]}>
                        {!esMiSeccion
                          ? 'OTRO SECTOR'
                          : mesa.ocupada
                          ? esLista
                            ? '🔔 ¡LISTA!'
                            : tienePedidoReal
                            ? 'EN COCINA'
                            : 'UBICADA'
                          : 'LIBRE'}
                      </Text>
                    </View>
                  </View>

                  <Text style={styles.seccionTxt}>
                    👥 {mesa.comensales || mesa.capacidad}p · {mesa.seccion_nombre}
                  </Text>

                  {!esMiSeccion ? (
                    <View style={styles.bloqueadoBox}>
                      <Text style={styles.bloqueadoTxt}>🔒 Solo lectura</Text>
                    </View>
                  ) : mesa.ocupada && tienePedidoReal && mesa.info ? (
                    <View style={styles.infoBox}>
                      <Text style={styles.infoPed}>Comanda #{mesa.info.idpedido}</Text>
                      {clienteUbicado ? (
                        <Text style={styles.clienteAsignadoTxt} numberOfLines={1}>
                          👤 {clienteUbicado}
                        </Text>
                      ) : null}
                      {meseroAtiende ? (
                        <Text style={styles.meseroAsignadoTxt} numberOfLines={1}>
                          🤵 {meseroAtiende}
                        </Text>
                      ) : null}
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
                  ) : mesa.ocupada ? (
                    <View style={styles.infoBox}>
                      <Text style={[styles.infoPed, { color: '#D97706' }]}>
                        🪑 Cliente ubicado
                      </Text>
                      {clienteUbicado ? (
                        <Text style={styles.clienteAsignadoTxt} numberOfLines={1}>
                          👤 {clienteUbicado}
                        </Text>
                      ) : null}
                      {meseroAtiende ? (
                        <Text style={styles.meseroAsignadoTxt} numberOfLines={1}>
                          🤵 {meseroAtiende}
                        </Text>
                      ) : null}
                      <View style={styles.btnTomarPedidoMini}>
                        <Text style={styles.btnTomarPedidoMiniTxt}>📝 Tomar Pedido ➔</Text>
                      </View>
                    </View>
                  ) : (
                    <View style={styles.libreBox}>
                      <Text style={styles.libreTxt}>🪑 Ubicar Cliente y Pedir</Text>
                    </View>
                  )}
                </TouchableOpacity>
              );
            })}
          </View>
        )}
      </ScrollView>

      {/* ============================================================ */}
      {/* MODAL 1: UBICAR CLIENTE EN MESA Y TOMAR PEDIDO               */}
      {/* ============================================================ */}
      <Modal visible={modalUbicarVisible} transparent animationType="slide">
        <View style={styles.modalOverlay}>
          <View style={[styles.modalCard, { maxWidth: 480 }]}>
            <View style={styles.modalHeaderRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.modalHeaderTitle}>🪑 UBICAR CLIENTE EN MESA</Text>
                <Text style={styles.modalHeaderSub}>
                  Seleccioná la mesa donde se sienta el cliente para tomar su pedido
                </Text>
              </View>
              <TouchableOpacity
                style={styles.btnCloseX}
                onPress={() => setModalUbicarVisible(false)}>
                <Text style={styles.btnCloseXTxt}>✕</Text>
              </TouchableOpacity>
            </View>

            <ScrollView style={{ maxHeight: 440 }} showsVerticalScrollIndicator={false}>
              {/* 1. Selección de Mesa */}
              <Text style={styles.fieldLabel}>1. ¿En qué mesa de tu sector ubicás al cliente?</Text>
              <View style={styles.mesasSelectorGrid}>
                {misMesas.map((m) => {
                  const seleccionada = mesaSeleccionadaUbicar === m.numero;
                  return (
                    <TouchableOpacity
                      key={m.idmesa}
                      style={[
                        styles.mesaOptionChip,
                        m.ocupada && styles.mesaOptionOcupada,
                        seleccionada && styles.mesaOptionActiva,
                      ]}
                      onPress={() => {
                        setMesaSeleccionadaUbicar(m.numero);
                        setComensalesInput(m.comensales || m.capacidad || 2);
                        if (m.cliente_nombre) setClienteNombreInput(m.cliente_nombre);
                      }}>
                      <Text
                        style={[
                          styles.mesaOptionNum,
                          seleccionada && { color: '#FFFFFF' },
                        ]}>
                        Mesa #{m.numero}
                      </Text>
                      <Text
                        style={[
                          styles.mesaOptionCap,
                          seleccionada && { color: '#FEE2E2' },
                        ]}
                        numberOfLines={1}>
                        👥 {m.capacidad}p · {m.ocupada ? 'Ocupada' : 'Libre'}
                      </Text>
                    </TouchableOpacity>
                  );
                })}
              </View>

              {mesaObjUbicar && (
                <View style={styles.mesaSelectedBanner}>
                  <Text style={styles.mesaSelectedBannerTxt}>
                    📍 Seleccionada: <Text style={{ fontWeight: '900' }}>Mesa #{mesaObjUbicar.numero}</Text> ({mesaObjUbicar.seccion_nombre} · Capacidad {mesaObjUbicar.capacidad} personas)
                  </Text>
                </View>
              )}

              {/* 2. Cantidad de Comensales */}
              <Text style={[styles.fieldLabel, { marginTop: 14 }]}>
                2. Cantidad de personas (comensales):
              </Text>
              <View style={styles.comensalesRow}>
                {[1, 2, 3, 4, 6, 8].map((cant) => (
                  <TouchableOpacity
                    key={cant}
                    style={[
                      styles.chipComensal,
                      comensalesInput === cant && styles.chipComensalActivo,
                    ]}
                    onPress={() => setComensalesInput(cant)}>
                    <Text
                      style={[
                        styles.chipComensalTxt,
                        comensalesInput === cant && styles.chipComensalTxtActivo,
                      ]}>
                      👥 {cant}
                    </Text>
                  </TouchableOpacity>
                ))}
              </View>

              {/* 3. Nombre del Cliente / Referencia */}
              <Text style={[styles.fieldLabel, { marginTop: 14 }]}>
                3. Nombre del cliente o referencia (opcional):
              </Text>
              <TextInput
                style={styles.inputCliente}
                placeholder={`Ej: Familia Gómez / Juan (Por defecto: Cliente Mesa #${mesaSeleccionadaUbicar})`}
                placeholderTextColor="#94A3B8"
                value={clienteNombreInput}
                onChangeText={setClienteNombreInput}
              />
            </ScrollView>

            {/* Botones de Acción */}
            <View style={{ gap: 10, marginTop: 16 }}>
              <TouchableOpacity
                style={[styles.btnUbicarPrimario, procesandoUbicacion && { opacity: 0.6 }]}
                onPress={confirmarUbicarYTomarPedido}
                disabled={procesandoUbicacion}>
                <Text style={styles.btnUbicarPrimarioTxt}>
                  {procesandoUbicacion
                    ? 'Ubicando cliente...'
                    : `📝 UBICAR EN MESA #${mesaSeleccionadaUbicar} Y TOMAR PEDIDO`}
                </Text>
              </TouchableOpacity>

              <View style={{ flexDirection: 'row', gap: 10 }}>
                <TouchableOpacity
                  style={[styles.btnUbicarSecundario, { flex: 1.3 }]}
                  onPress={confirmarSoloUbicarEnMesa}
                  disabled={procesandoUbicacion}>
                  <Text style={styles.btnUbicarSecundarioTxt}>
                    🪑 Solo Ubicar (Pedir luego)
                  </Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.btnModalCancel, { flex: 0.8 }]}
                  onPress={() => setModalUbicarVisible(false)}
                  disabled={procesandoUbicacion}>
                  <Text style={styles.btnModalCancelTxt}>Cancelar</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </View>
      </Modal>

      {/* ============================================================ */}
      {/* MODAL 2: GESTIÓN DE MESA OCUPADA                             */}
      {/* ============================================================ */}
      <Modal visible={modalOcupadaVisible} transparent animationType="fade">
        <View style={styles.modalOverlay}>
          <View style={styles.modalCard}>
            {mesaActivaModal && (
              <>
                <View style={styles.modalHeaderRow}>
                  <View style={{ flex: 1 }}>
                    <Text style={styles.modalHeaderTitle}>
                      🍽️ MESA #{mesaActivaModal.numero} · {mesaActivaModal.seccion_nombre.toUpperCase()}
                    </Text>
                    <Text style={styles.modalHeaderSub}>
                      Gestión de mesa ocupada en tu sector
                    </Text>
                  </View>
                  <TouchableOpacity
                    style={styles.btnCloseX}
                    onPress={() => setModalOcupadaVisible(false)}>
                    <Text style={styles.btnCloseXTxt}>✕</Text>
                  </TouchableOpacity>
                </View>

                <View style={styles.detalleOcupadaBox}>
                  <View style={styles.ticketRow}>
                    <Text style={styles.ticketLbl}>👤 Cliente en mesa:</Text>
                    <Text style={styles.ticketVal}>
                      {mesaActivaModal.cliente_nombre || mesaActivaModal.info?.cliente || `Clientes Mesa #${mesaActivaModal.numero}`}
                    </Text>
                  </View>

                  <View style={styles.ticketRow}>
                    <Text style={styles.ticketLbl}>👥 Comensales:</Text>
                    <Text style={styles.ticketVal}>
                      {mesaActivaModal.comensales || mesaActivaModal.info?.comensales || mesaActivaModal.capacidad} personas
                    </Text>
                  </View>

                  {(mesaActivaModal.mesero_nombre || mesaActivaModal.info?.mesero_nombre) && (
                    <View style={styles.ticketRow}>
                      <Text style={styles.ticketLbl}>🤵 Mesero asignado:</Text>
                      <Text style={[styles.ticketVal, { color: '#0284C7' }]}>
                        {mesaActivaModal.mesero_nombre || mesaActivaModal.info?.mesero_nombre}
                      </Text>
                    </View>
                  )}

                  {mesaActivaModal.info && mesaActivaModal.info.idpedido !== '-' ? (
                    <>
                      <View style={styles.ticketDivider} />
                      <View style={styles.ticketRow}>
                        <Text style={styles.ticketLbl}>🧾 Comanda Activa:</Text>
                        <Text style={[styles.ticketVal, { color: MostazaColors.rojo }]}>
                          #{mesaActivaModal.info.idpedido}
                        </Text>
                      </View>
                      <View style={styles.ticketRow}>
                        <Text style={styles.ticketLbl}>💰 Total Acumulado:</Text>
                        <Text style={[styles.ticketVal, { color: '#16A34A', fontSize: 14 }]}>
                          {mesaActivaModal.info.total_formateado}
                        </Text>
                      </View>
                      <View style={styles.ticketRow}>
                        <Text style={styles.ticketLbl}>🍳 Estado Cocina:</Text>
                        <Text style={styles.ticketVal}>{mesaActivaModal.info.estado}</Text>
                      </View>
                      <View style={styles.ticketRow}>
                        <Text style={styles.ticketLbl}>🕒 Tiempo:</Text>
                        <Text style={styles.ticketVal}>{mesaActivaModal.info.tiempo_texto}</Text>
                      </View>
                    </>
                  ) : (
                    <View style={styles.sinComandaBanner}>
                      <Text style={styles.sinComandaBannerTxt}>
                        ⏳ El cliente ya está ubicado en la Mesa #{mesaActivaModal.numero}, pero todavía no se cargó su pedido.
                      </Text>
                    </View>
                  )}
                </View>

                {confirmandoDesocupar ? (
                  <View style={styles.confirmDesocuparBox}>
                    <Text style={styles.confirmDesocuparTitle}>
                      ¿Confirmás liberar la Mesa #{mesaActivaModal.numero}?
                    </Text>
                    <Text style={styles.confirmDesocuparSub}>
                      La mesa quedará libre y disponible para ubicar a nuevos clientes.
                    </Text>
                    <View style={{ flexDirection: 'row', gap: 10, marginTop: 10 }}>
                      <TouchableOpacity
                        style={[styles.btnModalCancel, { flex: 1 }]}
                        onPress={() => setConfirmandoDesocupar(false)}
                        disabled={procesandoDesocupar}>
                        <Text style={styles.btnModalCancelTxt}>No, volver</Text>
                      </TouchableOpacity>
                      <TouchableOpacity
                        style={[styles.btnModalConfirm, { flex: 1.2, backgroundColor: '#10B981' }]}
                        onPress={ejecutarDesocuparMesa}
                        disabled={procesandoDesocupar}>
                        <Text style={styles.btnModalConfirmTxt}>
                          {procesandoDesocupar ? 'Liberando...' : '🟢 Sí, Liberar Mesa'}
                        </Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                ) : (
                  <View style={{ gap: 10, marginTop: 6 }}>
                    <TouchableOpacity
                      style={styles.btnUbicarPrimario}
                      onPress={() => {
                        const num = mesaActivaModal.numero;
                        const cli =
                          mesaActivaModal.cliente_nombre ||
                          mesaActivaModal.info?.cliente ||
                          `Cliente Mesa #${num}`;
                        const com =
                          mesaActivaModal.comensales ||
                          mesaActivaModal.info?.comensales ||
                          mesaActivaModal.capacidad;
                        setModalOcupadaVisible(false);
                        router.push({
                          pathname: '/comanda',
                          params: {
                            mesa: String(num),
                            cliente: cli,
                            comensales: String(com),
                          },
                        });
                      }}>
                      <Text style={styles.btnUbicarPrimarioTxt}>
                        {mesaActivaModal.info && mesaActivaModal.info.idpedido !== '-'
                          ? `➕ AGREGAR PEDIDO A MESA #${mesaActivaModal.numero}`
                          : `📝 TOMAR PEDIDO DE MESA #${mesaActivaModal.numero}`}
                      </Text>
                    </TouchableOpacity>

                    <View style={{ flexDirection: 'row', gap: 10 }}>
                      <TouchableOpacity
                        style={[styles.btnDesocuparMesa, { flex: 1.2 }]}
                        onPress={() => setConfirmandoDesocupar(true)}>
                        <Text style={styles.btnDesocuparMesaTxt}>
                          🟢 Desocupar Mesa #{mesaActivaModal.numero}
                        </Text>
                      </TouchableOpacity>

                      <TouchableOpacity
                        style={[styles.btnModalCancel, { flex: 0.8 }]}
                        onPress={() => setModalOcupadaVisible(false)}>
                        <Text style={styles.btnModalCancelTxt}>Cerrar</Text>
                      </TouchableOpacity>
                    </View>
                  </View>
                )}
              </>
            )}
          </View>
        </View>
      </Modal>

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
    marginBottom: 10,
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

  btnUbicarPrincipal: {
    backgroundColor: MostazaColors.rojo,
    borderRadius: 14,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    marginBottom: 14,
    shadowColor: MostazaColors.rojo,
    shadowOpacity: 0.25,
    shadowRadius: 8,
    elevation: 4,
  },
  btnUbicarIconBox: {
    width: 44,
    height: 44,
    borderRadius: 12,
    backgroundColor: 'rgba(255,255,255,0.18)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnUbicarTitle: { color: '#FFFFFF', fontSize: 13, fontWeight: '900', letterSpacing: 0.2 },
  btnUbicarSub: { color: '#FEE2E2', fontSize: 11, fontWeight: '600', marginTop: 2 },
  btnUbicarArrow: {
    backgroundColor: '#FFFFFF',
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnUbicarArrowTxt: { color: MostazaColors.rojo, fontSize: 14, fontWeight: '900' },

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
  cardBloqueada: { borderColor: '#CBD5E1', backgroundColor: '#F8FAFC', opacity: 0.8 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  mesaNum: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  tagEstado: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  tagBloqueada: { backgroundColor: '#64748B' },
  tagText: { color: '#FFFFFF', fontSize: 9, fontWeight: '900' },
  seccionTxt: { fontSize: 10, color: '#64748B', marginTop: 4 },
  clienteAsignadoTxt: { fontSize: 10, color: '#0F172A', fontWeight: '800', marginTop: 2 },
  meseroAsignadoTxt: { fontSize: 9, color: '#0284C7', fontWeight: '800', marginTop: 2 },
  bloqueadoBox: { marginTop: 10, paddingVertical: 6, borderRadius: 6, backgroundColor: '#E2E8F0', alignItems: 'center' },
  bloqueadoTxt: { color: '#475569', fontSize: 10, fontWeight: '800' },
  infoBox: { backgroundColor: '#F8FAFC', padding: 8, borderRadius: 8, marginTop: 8, borderWidth: 1, borderColor: '#F1F5F9' },
  infoPed: { fontSize: 11, fontWeight: '800', color: MostazaColors.rojo },
  infoTot: { fontSize: 12, fontWeight: '900', color: '#0F172A', marginTop: 2 },
  infoTime: { fontSize: 9, color: '#64748B', marginTop: 2 },
  btnTomarPedidoMini: {
    marginTop: 6,
    backgroundColor: '#FEF3C7',
    borderRadius: 6,
    paddingVertical: 4,
    paddingHorizontal: 6,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  btnTomarPedidoMiniTxt: { fontSize: 10, fontWeight: '900', color: '#B45309' },
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
  libreBox: { marginTop: 10, paddingVertical: 6, alignItems: 'center', backgroundColor: '#ECFDF5', borderRadius: 8 },
  libreTxt: { color: '#059669', fontSize: 11, fontWeight: '900' },
  emptyBox: { alignItems: 'center', paddingVertical: 60 },
  emptyTxt: { color: '#64748B', fontSize: 13, fontWeight: '700' },

  // Modales Generales
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.65)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
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
  modalHeaderRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    paddingBottom: 12,
    marginBottom: 14,
  },
  btnCloseX: {
    width: 30,
    height: 30,
    borderRadius: 15,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
    marginLeft: 8,
  },
  btnCloseXTxt: { fontSize: 14, fontWeight: '900', color: '#475569' },
  modalHeaderTitle: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  modalHeaderSub: { fontSize: 11, color: '#64748B', marginTop: 2, fontWeight: '600' },

  // Modal Ubicar Cliente
  fieldLabel: { fontSize: 12, fontWeight: '800', color: '#0F172A', marginBottom: 8 },
  mesasSelectorGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  mesaOptionChip: {
    width: '31%',
    backgroundColor: '#F8FAFC',
    borderWidth: 1.5,
    borderColor: '#10B981',
    borderRadius: 10,
    paddingVertical: 8,
    paddingHorizontal: 8,
    alignItems: 'center',
  },
  mesaOptionOcupada: {
    borderColor: '#F59E0B',
    backgroundColor: '#FFFBEB',
  },
  mesaOptionActiva: {
    backgroundColor: MostazaColors.rojo,
    borderColor: MostazaColors.rojo,
  },
  mesaOptionNum: { fontSize: 13, fontWeight: '900', color: '#0F172A' },
  mesaOptionCap: { fontSize: 9, fontWeight: '700', color: '#64748B', marginTop: 2 },
  mesaSelectedBanner: {
    backgroundColor: '#EFF6FF',
    borderRadius: 8,
    padding: 8,
    marginTop: 10,
    borderWidth: 1,
    borderColor: '#BFDBFE',
  },
  mesaSelectedBannerTxt: { fontSize: 11, color: '#1E40AF', fontWeight: '600' },
  comensalesRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chipComensal: {
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 10,
    backgroundColor: '#F1F5F9',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
  },
  chipComensalActivo: {
    backgroundColor: '#0F172A',
    borderColor: '#0F172A',
  },
  chipComensalTxt: { fontSize: 12, fontWeight: '800', color: '#475569' },
  chipComensalTxtActivo: { color: '#FFFFFF' },
  inputCliente: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
    borderRadius: 10,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 13,
    color: '#0F172A',
    fontWeight: '600',
  },
  btnUbicarPrimario: {
    backgroundColor: '#16A34A',
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
    shadowColor: '#16A34A',
    shadowOpacity: 0.25,
    shadowRadius: 6,
    elevation: 3,
  },
  btnUbicarPrimarioTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 13 },
  btnUbicarSecundario: {
    backgroundColor: '#FEF3C7',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  btnUbicarSecundarioTxt: { color: '#B45309', fontWeight: '900', fontSize: 12 },

  // Modal Mesa Ocupada
  detalleOcupadaBox: {
    backgroundColor: '#F8FAFC',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 12,
  },
  sinComandaBanner: {
    backgroundColor: '#FFFBEB',
    borderRadius: 8,
    padding: 10,
    marginTop: 8,
    borderWidth: 1,
    borderColor: '#FDE68A',
  },
  sinComandaBannerTxt: { fontSize: 11, color: '#92400E', fontWeight: '700' },
  btnDesocuparMesa: {
    backgroundColor: '#ECFDF5',
    borderRadius: 10,
    paddingVertical: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  btnDesocuparMesaTxt: { color: '#047857', fontWeight: '900', fontSize: 12 },
  confirmDesocuparBox: {
    backgroundColor: '#FEF2F2',
    borderRadius: 12,
    padding: 14,
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  confirmDesocuparTitle: { fontSize: 13, fontWeight: '900', color: '#991B1B' },
  confirmDesocuparSub: { fontSize: 11, color: '#B91C1C', marginTop: 4 },

  ticketContent: { paddingHorizontal: 4 },
  ticketRow: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 8 },
  ticketLbl: { fontSize: 12, color: '#64748B', fontWeight: '700' },
  ticketVal: { fontSize: 12, color: '#0F172A', fontWeight: '900' },
  ticketDivider: { height: 1, backgroundColor: '#E2E8F0', marginVertical: 10 },
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
