import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TextInput, TouchableOpacity, Alert } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';

export default function PerfilScreen() {
  const { cocinero, logout, horaIngreso } = useAuth();
  const [apiUrl, setApiUrl] = useState(ApiService.getBaseUrl());

  const handleCerrarTurno = () => {
    Alert.alert(
      'Cerrar Turno de Cocina',
      `¿Deseás finalizar la jornada y registrar la salida de ${cocinero?.nombre || 'Cocinero'}?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: 'Sí, Cerrar Turno',
          style: 'destructive',
          onPress: async () => {
            await logout();
            Alert.alert('Turno Finalizado', 'Se registró tu horario de salida correctamente en el sistema de asistencia.');
          },
        },
      ]
    );
  };

  const guardarApi = () => {
    ApiService.setBaseUrl(apiUrl);
    Alert.alert('Configuración Guardada', `URL de API actualizada a: ${apiUrl}`);
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>⚙️ AJUSTES Y TURNO DE COCINA</Text>
        <Text style={styles.headerSub}>Control de Asistencia y Trazabilidad de Personal (KDS)</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Tarjeta de Cocinero y Turno */}
        <View style={styles.card}>
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <Text style={styles.cardTitle}>👨‍🍳 Parrillero / Cocinero Autenticado</Text>
            <View style={styles.badgeTurnoOn}>
              <Text style={styles.badgeTurnoTxt}>● EN TURNO ACTIVO</Text>
            </View>
          </View>

          <View style={styles.profileRow}>
            <Text style={styles.avatarTxt}>{cocinero?.avatar || '👨‍🍳'}</Text>
            <View style={{ flex: 1, marginLeft: 12 }}>
              <Text style={styles.cookName}>{cocinero?.nombre_completo || cocinero?.nombre || 'Cocinero Mostaza'}</Text>
              <Text style={styles.cookEmail}>{cocinero?.email || 'cocina@mostaza.com.ar'}</Text>
              <Text style={styles.cookId}>Legajo Empleado #{cocinero?.idempleado || cocinero?.id || 15} · Rol: {cocinero?.rol || 'Cocinero'}</Text>
            </View>
          </View>

          <View style={styles.shiftInfoBox}>
            <Text style={styles.shiftInfoText}>
              ⏱️ Ingreso registrado a las {horaIngreso || cocinero?.hora_entrada || '08:00'} hs · Sucursal Formosa Centro
            </Text>
          </View>

          <TouchableOpacity
            style={styles.btnTurnoSalir}
            onPress={handleCerrarTurno}
            activeOpacity={0.85}>
            <Text style={styles.btnTurnoTxt}>🚪 Registrar Salida y Cerrar Turno</Text>
          </TouchableOpacity>
        </View>

        {/* Políticas BPM Mostaza */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>📋 Checklist de Buenas Prácticas (BPM)</Text>
          <Text style={styles.bpmItem}>✅ Lavado y sanitizado de manos cada 30 min.</Text>
          <Text style={styles.bpmItem}>✅ Temperatura de planchas por encima de 175°C.</Text>
          <Text style={styles.bpmItem}>✅ Desinfección de tablas de armado y pinzas.</Text>
          <Text style={styles.bpmItem}>✅ Trazabilidad estricta: cada comanda despachada queda firmada con tu usuario.</Text>
        </View>

        {/* Configuración de Servidor API */}
        <View style={styles.card}>
          <Text style={styles.cardTitle}>🌐 Conexión con Servidor Central</Text>
          <Text style={styles.label}>Endpoint REST API:</Text>
          <TextInput
            style={styles.input}
            value={apiUrl}
            onChangeText={setApiUrl}
            placeholder="http://10.0.5.77/PROYECTO_REST/mostaza_web/api"
            placeholderTextColor="#94A3B8"
            autoCapitalize="none"
          />
          <View style={{ flexDirection: 'row', gap: 8, marginTop: 10 }}>
            <TouchableOpacity
              style={{ backgroundColor: '#1E3A8A', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 6 }}
              onPress={() => setApiUrl('http://10.0.5.77/PROYECTO_REST/mostaza_web/api')}>
              <Text style={{ fontSize: 11, color: '#93C5FD', fontWeight: '800' }}>📱 WiFi (10.0.5.77)</Text>
            </TouchableOpacity>
            <TouchableOpacity
              style={{ backgroundColor: '#334155', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 6 }}
              onPress={() => setApiUrl('http://localhost/PROYECTO_REST/mostaza_web/api')}>
              <Text style={{ fontSize: 11, color: '#CBD5E1', fontWeight: '800' }}>💻 localhost</Text>
            </TouchableOpacity>
          </View>
          <TouchableOpacity style={styles.btnGuardar} onPress={guardarApi} activeOpacity={0.85}>
            <Text style={styles.btnGuardarTxt}>💾 Guardar URL de API</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  header: {
    backgroundColor: '#0F172A',
    paddingHorizontal: 20,
    paddingTop: 16,
    paddingBottom: 16,
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '900',
    color: '#FBB728',
    letterSpacing: 0.5,
  },
  headerSub: {
    fontSize: 11,
    color: '#94A3B8',
    marginTop: 2,
  },
  scroll: {
    padding: 16,
    gap: 16,
  },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 6,
    elevation: 2,
  },
  cardTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0F172A',
    marginBottom: 12,
  },
  profileRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    padding: 12,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 12,
  },
  avatarTxt: {
    fontSize: 36,
  },
  cookName: {
    fontSize: 15,
    fontWeight: '800',
    color: '#0F172A',
  },
  cookEmail: {
    fontSize: 12,
    color: '#64748B',
    marginTop: 1,
  },
  cookId: {
    fontSize: 11,
    fontWeight: '700',
    color: '#A30002',
    marginTop: 2,
  },
  badgeTurnoOn: {
    backgroundColor: '#DCFCE7',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 20,
  },
  badgeTurnoTxt: {
    fontSize: 10,
    fontWeight: '900',
    color: '#166534',
  },
  shiftInfoBox: {
    backgroundColor: '#EFF6FF',
    padding: 10,
    borderRadius: 8,
    marginBottom: 14,
  },
  shiftInfoText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#1E40AF',
  },
  btnTurnoSalir: {
    backgroundColor: '#EF4444',
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
  },
  btnTurnoTxt: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 13,
  },
  bpmItem: {
    fontSize: 12,
    color: '#334155',
    lineHeight: 22,
    fontWeight: '600',
  },
  label: {
    fontSize: 11,
    fontWeight: '700',
    color: '#64748B',
    marginBottom: 6,
  },
  input: {
    backgroundColor: '#F8FAFC',
    borderWidth: 1,
    borderColor: '#CBD5E1',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    fontSize: 13,
    color: '#0F172A',
    marginBottom: 12,
  },
  btnGuardar: {
    backgroundColor: '#0F172A',
    paddingVertical: 10,
    borderRadius: 8,
    alignItems: 'center',
  },
  btnGuardarTxt: {
    color: '#FFFFFF',
    fontWeight: '800',
    fontSize: 12,
  },
});
