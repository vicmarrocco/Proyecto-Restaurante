import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  TouchableOpacity,
  Alert,
  SafeAreaView,
} from 'react-native';
import { ApiService } from '@/services/api';
import { MostazaColors } from '@/constants/Colors';

export default function PerfilScreen() {
  const [apiUrl, setApiUrl] = useState(ApiService.getBaseUrl());

  const guardar = () => {
    ApiService.setBaseUrl(apiUrl);
    Alert.alert('Guardado', `URL actualizada a: ${apiUrl}`);
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>⚙️ AJUSTES Y PERFIL MESERO</Text>
        <Text style={styles.headerSub}>Configuración de Conexión de Salón</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <View style={styles.card}>
          <Text style={styles.cardTitle}>👤 Mesero Activo</Text>
          <Text style={styles.meseroNom}>Lucas Benítez</Text>
          <Text style={styles.meseroRol}>Turno Salón Principal · Mostaza Formosa</Text>
        </View>

        <Text style={styles.secTitle}>Conexión con la API</Text>
        <View style={styles.card}>
          <Text style={styles.lbl}>URL Base de la API:</Text>
          <TextInput style={styles.input} value={apiUrl} onChangeText={setApiUrl} />

          <View style={{ flexDirection: 'row', gap: 8, marginTop: 10 }}>
            <TouchableOpacity
              style={[styles.btnQuick, { backgroundColor: '#DBEAFE' }]}
              onPress={() => setApiUrl('http://10.0.2.145/PROYECTO_REST/mostaza_web/api')}>
              <Text style={{ fontSize: 10, color: '#1E40AF', fontWeight: '800' }}>📱 WiFi (10.0.2.145)</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={[styles.btnQuick, { backgroundColor: '#F1F5F9' }]}
              onPress={() => setApiUrl('http://localhost/PROYECTO_REST/mostaza_web/api')}>
              <Text style={{ fontSize: 10, color: '#334155', fontWeight: '800' }}>💻 localhost</Text>
            </TouchableOpacity>
          </View>

          <TouchableOpacity style={styles.btnSave} onPress={guardar}>
            <Text style={styles.btnSaveTxt}>Guardar Configuración</Text>
          </TouchableOpacity>
        </View>
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
    borderRadius: 14,
    padding: 16,
    borderWidth: 1,
    borderColor: '#334155',
    marginBottom: 16,
  },
  cardTitle: { fontSize: 12, fontWeight: '800', color: '#94A3B8', marginBottom: 6 },
  meseroNom: { fontSize: 18, fontWeight: '900', color: '#FFFFFF' },
  meseroRol: { fontSize: 12, color: '#10B981', fontWeight: '700', marginTop: 2 },
  secTitle: { fontSize: 13, fontWeight: '800', color: '#F8FAFC', marginBottom: 8 },
  lbl: { fontSize: 11, color: '#94A3B8', fontWeight: '700', marginBottom: 6 },
  input: {
    backgroundColor: '#0F172A',
    borderRadius: 8,
    padding: 10,
    color: '#FFF',
    fontSize: 12,
    borderWidth: 1,
    borderColor: '#334155',
  },
  btnQuick: { paddingHorizontal: 8, paddingVertical: 6, borderRadius: 6 },
  btnSave: {
    backgroundColor: MostazaColors.rojo,
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 14,
  },
  btnSaveTxt: { color: '#FFF', fontWeight: '900', fontSize: 13 },
});
