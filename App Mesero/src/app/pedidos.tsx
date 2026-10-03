import React, { useState, useEffect, useCallback, useMemo } from 'react';
import {
  View,
  Text,
  StyleSheet,
  FlatList,
  TouchableOpacity,
  RefreshControl,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { ApiService } from '@/services/api';
import { MostazaColors } from '@/constants/Colors';

export default function PedidosSalonScreen() {
  const router = useRouter();
  const [pedidos, setPedidos] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [filtroTipo, setFiltroTipo] = useState<'todos' | 'presencial' | 'online'>('todos');
  const [filtroSeccion, setFiltroSeccion] = useState<boolean>(true); // Por defecto filtrar su sección
  const [entregandoId, setEntregandoId] = useState<number | null>(null);

  const mesero = ApiService.getCurrentMesero();
  const miSeccionId = Number(mesero?.fk_id_seccion || 1);
  const seccionNombre = mesero?.seccion_nombre || (miSeccionId === 1 ? 'Salón Principal' : 'Terraza Exterior');

  const cargarPedidos = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getPedidosSalon('todos');
    if (res.ok && Array.isArray(res.pedidos)) {
      setPedidos(res.pedidos);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  // Polling continuo en tiempo real cada 3.5 segundos
  useEffect(() => {
    cargarPedidos();
    const interval = setInterval(() => {
      cargarPedidos();
    }, 3500);
    return () => clearInterval(interval);
  }, [cargarPedidos]);

  // Confirmar entrega en mesa
  const handleConfirmarEntrega = async (idpedido: number, numeroMesa: number) => {
    Alert.alert(
      '🛎️ Confirmar Entrega en Mesa',
      `¿Confirmás que los platos de la Comanda #${idpedido} ya fueron servidos en la Mesa #${numeroMesa}?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Sí, Servido en Mesa',
          onPress: async () => {
            setEntregandoId(idpedido);
            const res = await ApiService.confirmarEntregaEnMesa(idpedido);
            setEntregandoId(null);
            if (res.ok) {
              Alert.alert('¡Entrega Confirmada!', `La Mesa #${numeroMesa} ya tiene su pedido servido. El cliente y el sistema fueron actualizados en tiempo real.`);
              cargarPedidos();
            } else {
              Alert.alert('Error', res.error || 'No se pudo confirmar la entrega');
            }
          },
        },
      ]
    );
  };

  // Filtrado reactivo de pedidos
  const pedidosFiltrados = useMemo(() => {
    return pedidos.filter((p) => {
      // Filtro tipo (online vs presencial)
      if (filtroTipo === 'online' && !p.es_online) return false;
      if (filtroTipo === 'presencial' && p.es_online) return false;

      // Filtro sección del mesero
      if (filtroSeccion && p.numero_mesa) {
        const num = Number(p.numero_mesa);
        if (miSeccionId === 1 && (num < 1 || num > 8)) return false;
        if (miSeccionId === 2 && (num < 9 || num > 14)) return false;
        if (miSeccionId === 3 && (num < 15 || num > 20)) return false;
        if (miSeccionId === 4 && (num < 21 || num > 24)) return false;
      }

      return true;
    });
  }, [pedidos, filtroTipo, filtroSeccion, miSeccionId]);

  // Contadores vivos
  const cantOnline = pedidos.filter((p) => p.es_online && p.estado !== 'Entregado').length;
  const cantPresencial = pedidos.filter((p) => !p.es_online && p.estado !== 'Entregado').length;
  const cantListosServir = pedidos.filter((p) => ['Listo', 'Listo para Despacho'].includes(p.estado)).length;

  const renderItem = ({ item }: { item: any }) => {
    const isListo = ['Listo', 'Listo para Despacho'].includes(item.estado);
    const isEnCocina = ['En Cocina', 'En Preparacion', 'En Preparación'].includes(item.estado);
    const isEntregado = item.estado === 'Entregado';
    const isRecibido = item.estado === 'Recibido';

    return (
      <View style={[styles.card, isListo && styles.cardListo]}>
        {/* Encabezado Tarjeta */}
        <View style={styles.cardHeader}>
          <View style={styles.headerLeft}>
            <View style={[styles.badgeMesa, isListo && styles.badgeMesaListo]}>
              <Text style={[styles.badgeMesaTxt, isListo && styles.badgeMesaTxtListo]}>
                MESA #{item.numero_mesa || 'S/M'}
              </Text>
            </View>
            <View style={[styles.badgeOrigen, item.es_online ? styles.badgeOnline : styles.badgePresencial]}>
              <Text style={styles.badgeOrigenTxt}>
                {item.es_online ? '🌐 ONLINE SALÓN' : '🍽️ PRESENCIAL'}
              </Text>
            </View>
          </View>

          <View style={styles.headerRight}>
            <Text style={styles.comandaNum}>#{item.idpedido}</Text>
            <Text style={styles.horaTxt}>🕒 {item.hora || 'Hoy'}</Text>
          </View>
        </View>

        {/* Cliente y Total */}
        <View style={styles.metaRow}>
          <Text style={styles.clienteTxt}>👤 {item.cliente || 'Cliente Salón'}</Text>
          <Text style={styles.totalTxt}>${Number(item.total).toLocaleString('es-AR')}</Text>
        </View>

        {/* Pipeline de Seguimiento Visual */}
        <View style={styles.pipeline}>
          <View style={[styles.pipeStep, styles.pipeStepActive]}>
            <Text style={styles.pipeIco}>💳</Text>
            <Text style={styles.pipeTxt}>Cobrado</Text>
          </View>
          <View style={styles.pipeLine} />

          <View style={[styles.pipeStep, (isEnCocina || isListo || isEntregado) && styles.pipeStepActive]}>
            <Text style={styles.pipeIco}>🍳</Text>
            <Text style={styles.pipeTxt}>En Cocina</Text>
          </View>
          <View style={styles.pipeLine} />

          <View style={[styles.pipeStep, (isListo || isEntregado) && styles.pipeStepActive]}>
            <Text style={styles.pipeIco}>🛎️</Text>
            <Text style={styles.pipeTxt}>Listo</Text>
          </View>
          <View style={styles.pipeLine} />

          <View style={[styles.pipeStep, isEntregado && styles.pipeStepActive]}>
            <Text style={styles.pipeIco}>✅</Text>
            <Text style={styles.pipeTxt}>Servido</Text>
          </View>
        </View>

        {/* Indicadores de Cocinero / Auditoría */}
        {item.cocinero_nombre ? (
          <View style={styles.cocineroBox}>
            <Text style={styles.cocineroTxt}>
              👨‍🍳 Preparado por: <Text style={{ fontWeight: '900' }}>{item.cocinero_nombre}</Text>
            </Text>
          </View>
        ) : null}

        {/* Items del Pedido */}
        <View style={styles.itemsList}>
          {item.items && item.items.map((it: any, idx: number) => (
            <View key={idx} style={styles.itemRow}>
              <Text style={styles.itemCant}>{it.cantidad}x</Text>
              <View style={{ flex: 1 }}>
                <Text style={styles.itemDesc}>{it.descripcion}</Text>
                {it.modificadores ? (
                  <Text style={styles.itemMod}>⚠️ {it.modificadores}</Text>
                ) : null}
              </View>
            </View>
          ))}
        </View>

        {/* Botón de Acción Principal */}
        {isListo && (
          <TouchableOpacity
            style={[styles.btnServir, entregandoId === item.idpedido && { opacity: 0.6 }]}
            onPress={() => handleConfirmarEntrega(item.idpedido, item.numero_mesa)}
            disabled={entregandoId === item.idpedido}
            activeOpacity={0.8}>
            <Text style={styles.btnServirTxt}>
              {entregandoId === item.idpedido ? 'Confirmando...' : '🛎️ CONFIRMAR ENTREGA EN MESA'}
            </Text>
          </TouchableOpacity>
        )}

        {isEnCocina && (
          <View style={styles.statusBannerCocina}>
            <ActivityIndicator size="small" color="#D97706" style={{ marginRight: 6 }} />
            <Text style={styles.statusBannerCocinaTxt}>
              Cocinando en KDS · Aguardando campana de listo
            </Text>
          </View>
        )}

        {isRecibido && (
          <View style={styles.statusBannerRecibido}>
            <Text style={styles.statusBannerRecibidoTxt}>
              ⏳ En cola de espera de Cocina KDS
            </Text>
          </View>
        )}

        {isEntregado && (
          <View style={styles.statusBannerEntregado}>
            <Text style={styles.statusBannerEntregadoTxt}>
              ✓ Servido en Mesa por {item.mesero_nombre || 'Mesero'}
            </Text>
          </View>
        )}
      </View>
    );
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      {/* Header Superior */}
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>🍽️ PEDIDOS EN SALÓN</Text>
          <Text style={styles.headerSub}>Seguimiento y Entrega en Mesa en Tiempo Real</Text>
        </View>
        <TouchableOpacity
          style={[styles.badgeSeccionFiltro, filtroSeccion && styles.badgeSeccionFiltroActivo]}
          onPress={() => setFiltroSeccion(!filtroSeccion)}>
          <Text style={[styles.badgeSeccionFiltroTxt, filtroSeccion && styles.badgeSeccionFiltroTxtActivo]}>
            {filtroSeccion ? `📍 Mi Área (${seccionNombre})` : '🌐 Todas las Áreas'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* Alerta si hay pedidos listos para servir */}
      {cantListosServir > 0 && (
        <View style={styles.alertListosBanner}>
          <Text style={styles.alertListosIco}>🛎️</Text>
          <Text style={styles.alertListosTxt}>
            <Text style={{ fontWeight: '900' }}>¡{cantListosServir} pedido(s) listos en Cocina!</Text> Acercate al mostrador de despacho para servirlos en mesa.
          </Text>
        </View>
      )}

      {/* Segmented Control de Filtro (Presencial vs Online) */}
      <View style={styles.segmentContainer}>
        <TouchableOpacity
          style={[styles.segmentBtn, filtroTipo === 'todos' && styles.segmentBtnActive]}
          onPress={() => setFiltroTipo('todos')}>
          <Text style={[styles.segmentTxt, filtroTipo === 'todos' && styles.segmentTxtActive]}>
            📋 Todos ({pedidos.length})
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.segmentBtn, filtroTipo === 'presencial' && styles.segmentBtnActive]}
          onPress={() => setFiltroTipo('presencial')}>
          <Text style={[styles.segmentTxt, filtroTipo === 'presencial' && styles.segmentTxtActive]}>
            🍽️ Presenciales ({cantPresencial})
          </Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.segmentBtn, filtroTipo === 'online' && styles.segmentBtnActive]}
          onPress={() => setFiltroTipo('online')}>
          <Text style={[styles.segmentTxt, filtroTipo === 'online' && styles.segmentTxtActive]}>
            🌐 Online Salón ({cantOnline})
          </Text>
        </TouchableOpacity>
      </View>

      {/* Lista de Pedidos */}
      {loading ? (
        <View style={styles.centerLoading}>
          <ActivityIndicator size="large" color={MostazaColors.rojo} />
          <Text style={styles.loadingTxt}>Sincronizando comandas de salón...</Text>
        </View>
      ) : (
        <FlatList
          data={pedidosFiltrados}
          keyExtractor={(item) => String(item.idpedido)}
          renderItem={renderItem}
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
              <Text style={styles.emptyIco}>✨</Text>
              <Text style={styles.emptyTitle}>No hay comandas activas</Text>
              <Text style={styles.emptySub}>
                {filtroTipo === 'online'
                  ? 'No hay pedidos online para servir en salón en este momento.'
                  : filtroTipo === 'presencial'
                  ? 'No hay pedidos presenciales activos en tu sección.'
                  : 'Todas las mesas están servidas o sin comandas en proceso.'}
              </Text>
              <TouchableOpacity
                style={styles.btnNuevaComanda}
                onPress={() => router.push('/comanda')}>
                <Text style={styles.btnNuevaComandaTxt}>➕ Tomar Nueva Comanda</Text>
              </TouchableOpacity>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F8FAFC' },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 16,
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
  badgeSeccionFiltro: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 10,
    paddingVertical: 6,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  badgeSeccionFiltroActivo: {
    backgroundColor: '#EFF6FF',
    borderColor: '#3B82F6',
  },
  badgeSeccionFiltroTxt: { fontSize: 11, fontWeight: '700', color: '#475569' },
  badgeSeccionFiltroTxtActivo: { color: '#1D4ED8', fontWeight: '900' },

  alertListosBanner: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 16,
    paddingVertical: 10,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#FDE68A',
  },
  alertListosIco: { fontSize: 20 },
  alertListosTxt: { fontSize: 12, color: '#92400E', flex: 1 },

  segmentContainer: {
    flexDirection: 'row',
    padding: 12,
    backgroundColor: '#FFFFFF',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    gap: 8,
  },
  segmentBtn: {
    flex: 1,
    paddingVertical: 8,
    paddingHorizontal: 6,
    borderRadius: 8,
    alignItems: 'center',
    backgroundColor: '#F1F5F9',
  },
  segmentBtnActive: {
    backgroundColor: MostazaColors.rojo,
  },
  segmentTxt: { fontSize: 11, fontWeight: '800', color: '#64748B' },
  segmentTxtActive: { color: '#FFFFFF', fontWeight: '900' },

  listContent: { padding: 14, gap: 14, paddingBottom: 36 },

  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
  },
  cardListo: {
    borderColor: '#10B981',
    borderWidth: 2,
    backgroundColor: '#F0FDF4',
  },
  cardHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  headerLeft: { flexDirection: 'row', gap: 6, alignItems: 'center' },
  badgeMesa: {
    backgroundColor: '#0F172A',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 6,
  },
  badgeMesaListo: { backgroundColor: '#10B981' },
  badgeMesaTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 12 },
  badgeMesaTxtListo: { color: '#FFFFFF' },
  badgeOrigen: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6 },
  badgeOnline: { backgroundColor: '#EFF6FF', borderWidth: 1, borderColor: '#93C5FD' },
  badgePresencial: { backgroundColor: '#FEF3C7', borderWidth: 1, borderColor: '#FCD34D' },
  badgeOrigenTxt: { fontSize: 10, fontWeight: '900', color: '#1E293B' },

  headerRight: { alignItems: 'flex-end' },
  comandaNum: { fontSize: 12, fontWeight: '900', color: '#64748B' },
  horaTxt: { fontSize: 10, color: '#94A3B8', marginTop: 2 },

  metaRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginVertical: 6,
  },
  clienteTxt: { fontSize: 13, fontWeight: '800', color: '#1E293B' },
  totalTxt: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },

  pipeline: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: '#F8FAFC',
    padding: 10,
    borderRadius: 10,
    marginVertical: 8,
  },
  pipeStep: { alignItems: 'center', opacity: 0.35, minWidth: 46 },
  pipeStepActive: { opacity: 1 },
  pipeIco: { fontSize: 16 },
  pipeTxt: { fontSize: 9, fontWeight: '800', color: '#334155', marginTop: 2 },
  pipeLine: { flex: 1, height: 2, backgroundColor: '#CBD5E1', marginHorizontal: 2 },

  cocineroBox: {
    backgroundColor: '#EFF6FF',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 6,
    marginBottom: 8,
  },
  cocineroTxt: { fontSize: 11, color: '#1D4ED8' },

  itemsList: {
    backgroundColor: '#F8FAFC',
    borderRadius: 8,
    padding: 10,
    gap: 6,
    marginVertical: 6,
  },
  itemRow: { flexDirection: 'row', gap: 8 },
  itemCant: { fontSize: 12, fontWeight: '900', color: MostazaColors.rojo },
  itemDesc: { fontSize: 12, fontWeight: '700', color: '#1E293B' },
  itemMod: { fontSize: 10, color: '#DC2626', fontWeight: '800', marginTop: 1 },

  btnServir: {
    backgroundColor: '#10B981',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 8,
    elevation: 3,
    shadowColor: '#10B981',
    shadowOpacity: 0.3,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 6,
  },
  btnServirTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 13, letterSpacing: 0.5 },

  statusBannerCocina: {
    backgroundColor: '#FFFBEB',
    padding: 10,
    borderRadius: 8,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 6,
  },
  statusBannerCocinaTxt: { color: '#B45309', fontSize: 11, fontWeight: '800' },

  statusBannerRecibido: {
    backgroundColor: '#F1F5F9',
    padding: 10,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 6,
  },
  statusBannerRecibidoTxt: { color: '#64748B', fontSize: 11, fontWeight: '800' },

  statusBannerEntregado: {
    backgroundColor: '#ECFDF5',
    padding: 10,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 6,
  },
  statusBannerEntregadoTxt: { color: '#059669', fontSize: 11, fontWeight: '900' },

  centerLoading: { flex: 1, justifyContent: 'center', alignItems: 'center', gap: 10 },
  loadingTxt: { color: '#64748B', fontSize: 12, fontWeight: '700' },

  emptyContainer: { alignItems: 'center', padding: 36, gap: 10 },
  emptyIco: { fontSize: 44 },
  emptyTitle: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  emptySub: { fontSize: 12, color: '#64748B', textAlign: 'center', lineHeight: 18 },
  btnNuevaComanda: {
    backgroundColor: MostazaColors.rojo,
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 8,
    marginTop: 10,
  },
  btnNuevaComandaTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 12 },
});
