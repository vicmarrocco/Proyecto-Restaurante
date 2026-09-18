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
import { ApiService } from '@/services/api';
import { ComandaCocina } from '@/types/cocina';
import { MostazaColors } from '@/constants/Colors';

export default function KdsScreen() {
  const [comandas, setComandas] = useState<ComandaCocina[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const cargar = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getComandas();
    if (res.ok) {
      setComandas(res.comandas);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  useEffect(() => {
    cargar();
    const t = setInterval(() => cargar(), 4000);
    return () => clearInterval(t);
  }, [cargar]);

  const completar = async (idpedido: number) => {
    Alert.alert(
      'Completar Comanda',
      `¿Marcar comanda #${idpedido} como lista para despacho?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: '✅ ¡LISTO!',
          onPress: async () => {
            const res = await ApiService.marcarCompletado(idpedido);
            if (res.ok) cargar();
          },
        },
      ]
    );
  };

  const cancelar = (idpedido: number) => {
    Alert.alert(
      'Cancelar Comanda en Cocina',
      `¿Cancelar comanda #${idpedido}?
Seleccioná el motivo:`,
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

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>🍳 MOSTAZA COCINA KDS</Text>
          <Text style={styles.headerSub}>Comandas en Tiempo Real · Sucursal Formosa</Text>
        </View>
        <View style={styles.badgeOnline}>
          <Text style={styles.badgeOnlineText}>{comandas.length} EN COLA</Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargar(true)} />}>
        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.amarillo} style={{ marginTop: 40 }} />
        ) : comandas.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={{ fontSize: 40, marginBottom: 10 }}>🎉</Text>
            <Text style={styles.emptyTitle}>¡Cocina al Día!</Text>
            <Text style={styles.emptySub}>No hay comandas pendientes en preparación.</Text>
          </View>
        ) : (
          <View style={styles.grid}>
            {comandas.map((c) => (
              <View key={c.idpedido} style={styles.kdsCard}>
                <View style={styles.cardTop}>
                  <Text style={styles.comandaNum}>#{c.idpedido}</Text>
                  <Text style={styles.tipoBadge}>
                    {c.tipo_entrega === 'salon' ? `Mesa #${c.numero_mesa || '-'}` : '🛵 Delivery'}
                  </Text>
                </View>

                <Text style={styles.timer}>🕒 Espera: {c.minutos_espera} min</Text>

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

                <View style={styles.actions}>
                  <TouchableOpacity
                    style={[styles.btnAction, styles.btnReady]}
                    onPress={() => completar(c.idpedido)}>
                    <Text style={styles.btnReadyTxt}>✅ ¡LISTO!</Text>
                  </TouchableOpacity>

                  <TouchableOpacity
                    style={[styles.btnAction, styles.btnCancel]}
                    onPress={() => cancelar(c.idpedido)}>
                    <Text style={styles.btnCancelTxt}>✕</Text>
                  </TouchableOpacity>
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
    paddingVertical: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2 },
  badgeOnline: { backgroundColor: '#DCFCE7', paddingHorizontal: 12, paddingVertical: 4, borderRadius: 20, borderWidth: 1, borderColor: '#86EFAC' },
  badgeOnlineText: { color: '#166534', fontSize: 11, fontWeight: '900' },
  scroll: { padding: 16 },
  emptyCard: { backgroundColor: '#FFFFFF', borderRadius: 16, padding: 30, alignItems: 'center', marginTop: 30, borderWidth: 1, borderColor: '#E2E8F0' },
  emptyTitle: { fontSize: 18, fontWeight: '900', color: '#0F172A' },
  emptySub: { fontSize: 12, color: '#64748B', marginTop: 4 },
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
  timer: { fontSize: 10, color: '#DC2626', fontWeight: '800', marginTop: 4 },
  itemsBox: { backgroundColor: '#F8FAFC', borderRadius: 10, padding: 10, marginTop: 8, minHeight: 90, borderWidth: 1, borderColor: '#F1F5F9' },
  itemRow: { marginBottom: 6 },
  itemTxt: { color: '#0F172A', fontSize: 12, fontWeight: '700' },
  modTxt: { color: '#B45309', fontSize: 10, fontStyle: 'italic', marginTop: 1 },
  actions: { flexDirection: 'row', gap: 6, marginTop: 10 },
  btnAction: { paddingVertical: 10, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  btnReady: { flex: 1, backgroundColor: '#10B981' },
  btnReadyTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 12 },
  btnCancel: { width: 36, backgroundColor: '#FEE2E2', borderWidth: 1, borderColor: '#FECACA' },
  btnCancelTxt: { color: '#DC2626', fontWeight: '900', fontSize: 14 },
});
