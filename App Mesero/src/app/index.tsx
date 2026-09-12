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
import { Mesa } from '@/types/mesero';
import { MostazaColors } from '@/constants/Colors';

export default function MesasScreen() {
  const router = useRouter();
  const [mesas, setMesas] = useState<Mesa[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filtroSeccion, setFiltroSeccion] = useState<number | null>(null);

  const cargarMesas = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getMesas();
    if (res.ok) {
      setMesas(res.mesas);
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
    if (mesa.ocupada && mesa.info) {
      Alert.alert(
        `Mesa #${mesa.numero} (Ocupada)`,
        `Cliente: ${mesa.info.cliente}\nComanda: #${mesa.info.idpedido}\nTotal: ${mesa.info.total_formateado}\nEstado: ${mesa.info.estado}\nTiempo: ${mesa.info.tiempo_texto}`,
        [
          { text: 'Cerrar', style: 'cancel' },
          {
            text: '➕ Agregar Ítems a la Mesa',
            onPress: () => router.push({ pathname: '/comanda', params: { mesa: mesa.numero } }),
          },
        ]
      );
    } else {
      router.push({ pathname: '/comanda', params: { mesa: mesa.numero } });
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>🍽️ MOSTAZA MESERO</Text>
          <Text style={styles.headerSub}>Salón en Tiempo Real · Sucursal Formosa</Text>
        </View>
        <View style={styles.badgeOnline}>
          <Text style={styles.badgeOnlineText}>● EN VIVO</Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargarMesas(true)} />}>
        <View style={styles.metricsBar}>
          <View style={styles.metricItem}>
            <Text style={styles.metricVal}>{mesas.filter((m) => !m.ocupada).length}</Text>
            <Text style={styles.metricLbl}>🟢 LIBRES</Text>
          </View>
          <View style={styles.metricDiv} />
          <View style={styles.metricItem}>
            <Text style={[styles.metricVal, { color: '#EF4444' }]}>
              {mesas.filter((m) => m.ocupada).length}
            </Text>
            <Text style={styles.metricLbl}>🔴 OCUPADAS</Text>
          </View>
          <View style={styles.metricDiv} />
          <View style={styles.metricItem}>
            <Text style={[styles.metricVal, { color: '#F59E0B' }]}>
              {mesas.filter((m) => m.info?.estado === 'Listo' || m.info?.estado === 'Listo para Despacho').length}
            </Text>
            <Text style={styles.metricLbl}>🔔 LISTAS</Text>
          </View>
        </View>

        <Text style={styles.sectionTitle}>Mapa de Mesas por Sectores</Text>

        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.rojo} style={{ marginTop: 40 }} />
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
                        { backgroundColor: mesa.ocupada ? (esLista ? '#F59E0B' : '#EF4444') : '#10B981' },
                      ]}>
                      <Text style={styles.tagText}>
                        {mesa.ocupada ? (esLista ? '🔔 ¡LISTA!' : 'EN COCINA') : 'LIBRE'}
                      </Text>
                    </View>
                  </View>

                  <Text style={styles.seccionTxt}>👥 {mesa.capacidad} pers. · {mesa.seccion_nombre}</Text>

                  {mesa.ocupada && mesa.info ? (
                    <View style={styles.infoBox}>
                      <Text style={styles.infoPed}>Comanda #{mesa.info.idpedido}</Text>
                      <Text style={styles.infoTot}>{mesa.info.total_formateado}</Text>
                      <Text style={styles.infoTime}>🕒 {mesa.info.tiempo_texto}</Text>
                    </View>
                  ) : (
                    <View style={styles.libreBox}>
                      <Text style={styles.libreTxt}>+ Tocar para abrir comanda</Text>
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
  safeArea: { flex: 1, backgroundColor: '#0B0F19' },
  header: {
    backgroundColor: '#0F172A',
    paddingHorizontal: 20,
    paddingVertical: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  headerTitle: { fontSize: 17, fontWeight: '900', color: '#FBB728' },
  headerSub: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  badgeOnline: { backgroundColor: '#064E3B', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20 },
  badgeOnlineText: { color: '#A7F3D0', fontSize: 10, fontWeight: '800' },
  scroll: { padding: 16 },
  metricsBar: {
    backgroundColor: '#1E293B',
    borderRadius: 14,
    padding: 12,
    flexDirection: 'row',
    justifyContent: 'space-around',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#334155',
    marginBottom: 16,
  },
  metricItem: { alignItems: 'center' },
  metricVal: { fontSize: 20, fontWeight: '900', color: '#10B981' },
  metricLbl: { fontSize: 10, fontWeight: '800', color: '#94A3B8', marginTop: 2 },
  metricDiv: { width: 1, height: 28, backgroundColor: '#334155' },
  sectionTitle: { fontSize: 14, fontWeight: '800', color: '#F8FAFC', marginBottom: 12 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  cardMesa: {
    width: '48%',
    backgroundColor: '#1E293B',
    borderRadius: 14,
    padding: 14,
    borderWidth: 2,
    borderColor: '#334155',
  },
  cardLibre: { borderColor: '#059669' },
  cardOcupada: { borderColor: '#DC2626' },
  cardLista: { borderColor: '#F59E0B' },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  mesaNum: { fontSize: 16, fontWeight: '900', color: '#FFFFFF' },
  tagEstado: { paddingHorizontal: 6, paddingVertical: 2, borderRadius: 6 },
  tagText: { color: '#FFFFFF', fontSize: 9, fontWeight: '800' },
  seccionTxt: { fontSize: 10, color: '#94A3B8', marginTop: 4 },
  infoBox: { backgroundColor: '#0F172A', padding: 8, borderRadius: 8, marginTop: 8 },
  infoPed: { fontSize: 11, fontWeight: '800', color: '#FBB728' },
  infoTot: { fontSize: 12, fontWeight: '900', color: '#FFFFFF', marginTop: 2 },
  infoTime: { fontSize: 9, color: '#94A3B8', marginTop: 2 },
  libreBox: { marginTop: 10, paddingVertical: 6, alignItems: 'center' },
  libreTxt: { color: '#10B981', fontSize: 10, fontWeight: '700' },
});
