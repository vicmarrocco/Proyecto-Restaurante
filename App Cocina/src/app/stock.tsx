import React from 'react';
import { View, Text, StyleSheet, ScrollView, SafeAreaView, TouchableOpacity, Alert } from 'react-native';
import { MostazaColors } from '@/constants/Colors';

const STOCK_DATA = [
  { nombre: 'Medallón de Carne 120g', actual: '450 un.', min: '100 un.', alerta: false },
  { nombre: 'Pan Brioche con Sésamo', actual: '80 un.', min: '120 un.', alerta: true },
  { nombre: 'Queso Cheddar en Fetas', actual: '350 fetas', min: '100 fetas', alerta: false },
  { nombre: 'Papas Bastón Congeladas', actual: '15 kg', min: '20 kg', alerta: true },
  { nombre: 'Panceta Ahumada', actual: '25 kg', min: '10 kg', alerta: false },
  { nombre: 'Salsa Mostaza Especial', actual: '18 botellas', min: '5 botellas', alerta: false },
];

export default function StockScreen() {
  const pedirAdmin = (nombre: string) => {
    Alert.alert(
      'Solicitar Insumo al Admin (R37)',
      `¿Enviar solicitud de reposición urgente para "${nombre}" al administrador?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: '🚀 Enviar Pedido',
          onPress: () => Alert.alert('Enviado', 'Solicitud registrada con éxito en el sistema central.'),
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>📦 STOCK DE INSUMOS EN COCINA</Text>
        <Text style={styles.headerSub}>Monitor de Existencias y Reposición (R36 & R37)</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        {STOCK_DATA.map((item, idx) => (
          <View key={idx} style={[styles.card, item.alerta && styles.cardAlerta]}>
            <View style={{ flex: 1 }}>
              <Text style={styles.nom}>{item.nombre}</Text>
              <Text style={styles.cant}>Stock Actual: {item.actual} · Mínimo: {item.min}</Text>
            </View>

            <TouchableOpacity style={styles.btnPedir} onPress={() => pedirAdmin(item.nombre)}>
              <Text style={styles.btnPedirTxt}>Pedir Insumo</Text>
            </TouchableOpacity>
          </View>
        ))}
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
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: '#FBB728' },
  headerSub: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  scroll: { padding: 16 },
  card: {
    backgroundColor: '#1E293B',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#334155',
  },
  cardAlerta: { borderColor: '#EF4444', backgroundColor: '#3B1219' },
  nom: { fontSize: 13, fontWeight: '800', color: '#FFFFFF' },
  cant: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  btnPedir: { backgroundColor: MostazaColors.rojo, paddingHorizontal: 12, paddingVertical: 8, borderRadius: 8 },
  btnPedirTxt: { color: '#FFF', fontSize: 11, fontWeight: '800' },
});
