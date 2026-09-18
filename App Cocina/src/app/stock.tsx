import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  SafeAreaView,
  TouchableOpacity,
  Alert,
  ActivityIndicator,
  RefreshControl,
  Modal,
  TextInput,
} from 'react-native';
import { ApiService } from '@/services/api';
import { StockInsumo } from '@/types/cocina';
import { MostazaColors } from '@/constants/Colors';

export default function StockScreen() {
  const [insumos, setInsumos] = useState<StockInsumo[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [alertasCount, setAlertasCount] = useState(0);

  // Modal Solicitud
  const [modalVisible, setModalVisible] = useState(false);
  const [insumoSeleccionado, setInsumoSeleccionado] = useState<StockInsumo | null>(null);
  const [cantidad, setCantidad] = useState('10');
  const [prioridad, setPrioridad] = useState<'Media' | 'Alta' | 'Urgente'>('Alta');
  const [motivo, setMotivo] = useState('Stock crítico en turno');
  const [enviando, setEnviando] = useState(false);

  const cargarStock = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getStockInsumos();
    if (res.ok) {
      setInsumos(res.insumos);
      setAlertasCount(res.alertas_criticas || 0);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  useEffect(() => {
    cargarStock();
    const timer = setInterval(() => cargarStock(), 10000);
    return () => clearInterval(timer);
  }, [cargarStock]);

  const abrirModalSolicitud = (insumo: StockInsumo) => {
    setInsumoSeleccionado(insumo);
    setCantidad(String(Math.max(10, Math.round(insumo.stock_minimo))));
    setPrioridad(insumo.alerta ? 'Urgente' : 'Alta');
    setMotivo(insumo.alerta ? 'Stock por debajo del mínimo reglamentario' : 'Reposición preventiva de turno');
    setModalVisible(true);
  };

  const enviarSolicitud = async () => {
    if (!insumoSeleccionado) return;
    const cantNum = parseFloat(cantidad);
    if (isNaN(cantNum) || cantNum <= 0) {
      Alert.alert('Cantidad Inválida', 'Por favor ingresá un valor numérico mayor a cero.');
      return;
    }

    setEnviando(true);
    const res = await ApiService.solicitarInsumo({
      nombre_insumo: insumoSeleccionado.nombre,
      cantidad: cantNum,
      unidad_medida: insumoSeleccionado.unidad_medida,
      prioridad,
      motivo,
    });
    setEnviando(false);

    if (res.ok) {
      setModalVisible(false);
      Alert.alert('¡Solicitud Enviada!', res.mensaje || 'Solicitud registrada correctamente en el panel administrativo.');
      cargarStock(true);
    } else {
      Alert.alert('Error', res.error || 'No se pudo enviar la solicitud.');
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>📦 STOCK DE INSUMOS EN COCINA</Text>
          <Text style={styles.headerSub}>Monitor de Existencias en Tiempo Real (R36 & R37)</Text>
        </View>
        <View style={[styles.badge, alertasCount > 0 ? styles.badgeCritico : styles.badgeOk]}>
          <Text style={styles.badgeTxt}>
            {alertasCount > 0 ? `⚠️ ${alertasCount} CRÍTICOS` : '🟢 STOCK NORMAL'}
          </Text>
        </View>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargarStock(true)} />}>
        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.amarillo} style={{ marginTop: 40 }} />
        ) : insumos.length === 0 ? (
          <View style={styles.emptyCard}>
            <Text style={{ fontSize: 32, marginBottom: 8 }}>📦</Text>
            <Text style={styles.emptyTitle}>No hay insumos registrados</Text>
          </View>
        ) : (
          insumos.map((item) => (
            <View key={item.idinsumo} style={[styles.card, item.alerta && styles.cardAlerta]}>
              <View style={{ flex: 1 }}>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
                  <Text style={styles.nom}>{item.nombre}</Text>
                  {item.alerta && (
                    <View style={styles.tagUrgente}>
                      <Text style={styles.tagUrgenteTxt}>BAJO MÍNIMO</Text>
                    </View>
                  )}
                </View>
                <Text style={styles.cat}>{item.categoria.toUpperCase()} · Proveedor: {item.proveedor || 'Oficial Mostaza'}</Text>
                <View style={styles.stockRow}>
                  <Text style={[styles.cantActual, item.alerta && { color: '#EF4444' }]}>
                    Stock: {item.actual_texto || `${item.stock_actual} ${item.unidad_medida}`}
                  </Text>
                  <Text style={styles.cantMin}>
                    (Mín: {item.minimo_texto || `${item.stock_minimo} ${item.unidad_medida}`})
                  </Text>
                </View>
              </View>

              <TouchableOpacity
                style={[styles.btnPedir, item.alerta && styles.btnPedirUrgente]}
                onPress={() => abrirModalSolicitud(item)}
                activeOpacity={0.8}>
                <Text style={styles.btnPedirTxt}>Pedir</Text>
              </TouchableOpacity>
            </View>
          ))
        )}
      </ScrollView>

      {/* Modal de Solicitud de Insumo */}
      <Modal visible={modalVisible} transparent animationType="fade">
        <View style={styles.modalOverlay}>
          <View style={styles.modalBox}>
            <Text style={styles.modalTitle}>🚀 Pedir Reposición al Admin</Text>
            <Text style={styles.modalSub}>
              Insumo: <Text style={{ color: MostazaColors.amarillo, fontWeight: '800' }}>{insumoSeleccionado?.nombre}</Text>
            </Text>

            <Text style={styles.modalFieldLbl}>Cantidad a Solicitar ({insumoSeleccionado?.unidad_medida}):</Text>
            <TextInput
              style={styles.modalInput}
              keyboardType="numeric"
              value={cantidad}
              onChangeText={setCantidad}
              placeholder="Ej: 20"
              placeholderTextColor="#64748B"
            />

            <Text style={styles.modalFieldLbl}>Prioridad de Reposición:</Text>
            <View style={styles.prioContainer}>
              {(['Media', 'Alta', 'Urgente'] as const).map((p) => (
                <TouchableOpacity
                  key={p}
                  style={[
                    styles.prioBtn,
                    prioridad === p && (p === 'Urgente' ? styles.prioBtnUrgente : styles.prioBtnActive),
                  ]}
                  onPress={() => setPrioridad(p)}>
                  <Text
                    style={[
                      styles.prioBtnTxt,
                      prioridad === p && { color: '#FFFFFF', fontWeight: '900' },
                    ]}>
                    {p === 'Urgente' ? '🔥 Urgente' : p}
                  </Text>
                </TouchableOpacity>
              ))}
            </View>

            <Text style={styles.modalFieldLbl}>Motivo de la Solicitud:</Text>
            <TextInput
              style={[styles.modalInput, { height: 60, textAlignVertical: 'top' }]}
              multiline
              value={motivo}
              onChangeText={setMotivo}
              placeholder="Aclaración adicional para el depósito..."
              placeholderTextColor="#64748B"
            />

            <View style={styles.modalActions}>
              <TouchableOpacity
                style={styles.modalBtnCancel}
                onPress={() => setModalVisible(false)}
                disabled={enviando}>
                <Text style={styles.modalBtnCancelTxt}>Cancelar</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.modalBtnSend, enviando && { opacity: 0.6 }]}
                onPress={enviarSolicitud}
                disabled={enviando}>
                <Text style={styles.modalBtnSendTxt}>
                  {enviando ? 'Enviando...' : 'Confirmar Pedido'}
                </Text>
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
    paddingVertical: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: MostazaColors.rojo },
  headerSub: { fontSize: 11, color: '#64748B', marginTop: 2 },
  badge: { paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20 },
  badgeOk: { backgroundColor: '#DCFCE7' },
  badgeCritico: { backgroundColor: '#FEE2E2' },
  badgeTxt: { fontSize: 10, fontWeight: '900', color: '#166534' },
  scroll: { padding: 16 },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 14,
    marginBottom: 10,
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000000',
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  cardAlerta: { borderColor: '#EF4444', backgroundColor: '#FFF5F5' },
  nom: { fontSize: 14, fontWeight: '800', color: '#0F172A' },
  tagUrgente: { backgroundColor: '#EF4444', paddingHorizontal: 6, paddingVertical: 2, borderRadius: 4 },
  tagUrgenteTxt: { color: '#FFFFFF', fontSize: 9, fontWeight: '900' },
  cat: { fontSize: 10, color: '#64748B', fontWeight: '700', marginTop: 2 },
  stockRow: { flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 4 },
  cantActual: { fontSize: 12, fontWeight: '800', color: '#2563EB' },
  cantMin: { fontSize: 11, color: '#64748B' },
  btnPedir: {
    backgroundColor: MostazaColors.rojo,
    paddingHorizontal: 14,
    paddingVertical: 8,
    borderRadius: 8,
  },
  btnPedirUrgente: {
    backgroundColor: '#DC2626',
    borderWidth: 1,
    borderColor: '#F87171',
  },
  btnPedirTxt: { color: '#FFF', fontSize: 12, fontWeight: '900' },
  emptyCard: { alignItems: 'center', paddingVertical: 60 },
  emptyTitle: { fontSize: 15, fontWeight: '700', color: '#64748B' },

  // Modal
  modalOverlay: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.6)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 20,
  },
  modalBox: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 22,
    width: '100%',
    maxWidth: 440,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000000',
    shadowOpacity: 0.1,
    shadowRadius: 16,
    elevation: 5,
  },
  modalTitle: { fontSize: 16, fontWeight: '900', color: '#0F172A' },
  modalSub: { fontSize: 12, color: '#64748B', marginTop: 4, marginBottom: 14 },
  modalFieldLbl: { fontSize: 11, fontWeight: '800', color: '#334155', marginBottom: 4, marginTop: 8 },
  modalInput: {
    backgroundColor: '#F8FAFC',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 8,
    color: '#0F172A',
    fontSize: 13,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  prioContainer: { flexDirection: 'row', gap: 8, marginVertical: 4 },
  prioBtn: {
    flex: 1,
    paddingVertical: 8,
    borderRadius: 8,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  prioBtnActive: { backgroundColor: '#2563EB', borderColor: '#3B82F6' },
  prioBtnUrgente: { backgroundColor: MostazaColors.rojo, borderColor: '#EF4444' },
  prioBtnTxt: { fontSize: 11, fontWeight: '700', color: '#64748B' },
  modalActions: { flexDirection: 'row', gap: 10, marginTop: 18 },
  modalBtnCancel: {
    flex: 1,
    paddingVertical: 10,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
  },
  modalBtnCancelTxt: { color: '#475569', fontSize: 12, fontWeight: '800' },
  modalBtnSend: {
    flex: 1.5,
    paddingVertical: 10,
    borderRadius: 8,
    backgroundColor: MostazaColors.rojo,
    alignItems: 'center',
  },
  modalBtnSendTxt: { color: '#FFF', fontSize: 12, fontWeight: '900' },
});
