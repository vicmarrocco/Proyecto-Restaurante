import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  SafeAreaView,
  Alert,
  ActivityIndicator,
} from 'react-native';
import { useRouter } from 'expo-router';
import { ApiService } from '@/services/api';
import { Mesa, Seccion } from '@/types/mesero';
import { MostazaColors } from '@/constants/Colors';

export default function MesasScreen() {
  const router = useRouter();
  const [mesas, setMesas] = useState<Mesa[]>([]);
  const [secciones, setSecciones] = useState<Seccion[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filtroSeccion, setFiltroSeccion] = useState<number | null>(null);

  const cargarMesas = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getMesas();
    if (res.ok) {
      setMesas(res.mesas);
      setSecciones(res.secciones);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  useEffect(() => {
    cargarMesas();
    const timer = setInterval(() => cargarMesas(), 5000);
    return () => clearInterval(timer);
  }, [cargarMesas]);

  const mesasFiltradas = filtroSeccion
    ? mesas.filter((m) => m.idseccion === filtroSeccion)
    : mesas;

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

  const totalLibres = mesas.filter((m) => !m.ocupada).length;
  const totalOcupadas = mesas.filter((m) => m.ocupada).length;
  const totalListas = mesas.filter(
    (m) => m.info?.estado === 'Listo' || m.info?.estado === 'Listo para Despacho'
  ).length;

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>🍽️ MOSTAZA MESERO</Text>
          <Text style={styles.headerSub}>Salón en Tiempo Real · Sucursal Formosa Centro</Text>
        </View>
        <View style={styles.badgeOnline}>
          <Text style={styles.badgeOnlineText}>● EN VIVO</Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargarMesas(true)} />}>
        
        {/* Barra de Métricas */}
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
                Todas ({mesas.length})
              </Text>
            </TouchableOpacity>

            {secciones.map((sec) => {
              const count = mesas.filter((m) => m.idseccion === sec.idseccion).length;
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

        <Text style={styles.sectionTitle}>Mapa Táctil de Mesas</Text>

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
                              ? MostazaColors.amarillo
                              : MostazaColors.rojo
                            : '#10B981',
                        },
                      ]}>
                      <Text
                        style={[
                          styles.tagText,
                          esLista && { color: '#0F172A' },
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
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F8FAFC' },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 20,
    paddingVertical: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  headerTitle: { fontSize: 17, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2 },
  badgeOnline: { backgroundColor: '#DCFCE7', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20 },
  badgeOnlineText: { color: '#166534', fontSize: 10, fontWeight: '800' },
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
  cardLista: { borderColor: MostazaColors.amarillo, backgroundColor: '#FFFDF0' },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  mesaNum: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  tagEstado: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  tagText: { color: '#FFFFFF', fontSize: 9, fontWeight: '900' },
  seccionTxt: { fontSize: 10, color: '#64748B', marginTop: 4 },
  infoBox: { backgroundColor: '#F8FAFC', padding: 8, borderRadius: 8, marginTop: 8, borderWidth: 1, borderColor: '#F1F5F9' },
  infoPed: { fontSize: 11, fontWeight: '800', color: MostazaColors.rojo },
  infoTot: { fontSize: 12, fontWeight: '900', color: '#0F172A', marginTop: 2 },
  infoTime: { fontSize: 9, color: '#64748B', marginTop: 2 },
  libreBox: { marginTop: 10, paddingVertical: 6, alignItems: 'center' },
  libreTxt: { color: '#10B981', fontSize: 11, fontWeight: '800' },
  emptyBox: { alignItems: 'center', paddingVertical: 60 },
  emptyTxt: { color: '#64748B', fontSize: 13, fontWeight: '700' },
});
