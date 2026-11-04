import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TextInput,
  ActivityIndicator,
  RefreshControl,
  TouchableOpacity,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { ApiService } from '@/services/api';
import { Receta } from '@/types/cocina';
import { MostazaColors } from '@/constants/Colors';

export default function RecetasScreen() {
  const [recetas, setRecetas] = useState<Receta[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [busqueda, setBusqueda] = useState('');
  const [categoriaActiva, setCategoriaActiva] = useState<string>('Todas');

  const cargarRecetas = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getRecetas();
    if (res.ok && Array.isArray(res.recetas)) {
      setRecetas(res.recetas);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  useEffect(() => {
    cargarRecetas();
  }, [cargarRecetas]);

  const safeRecetas = Array.isArray(recetas) ? recetas : [];
  const categorias = ['Todas', ...Array.from(new Set(safeRecetas.map((r) => r.categoria).filter(Boolean)))];

  const recetasFiltradas = safeRecetas.filter((r) => {
    const matchCat = categoriaActiva === 'Todas' || r.categoria === categoriaActiva;
    const matchTxt =
      !busqueda ||
      (r.nombre && r.nombre.toLowerCase().includes(busqueda.toLowerCase())) ||
      (r.ingredientes && r.ingredientes.toLowerCase().includes(busqueda.toLowerCase()));
    return matchCat && matchTxt;
  });

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>📖 LIBRO DE RECETAS TÉCNICAS</Text>
          <Text style={styles.headerSub}>Fichas Técnicas Oficiales, Gramajes y Tiempos (R35)</Text>
        </View>
        <View style={styles.badgeCount}>
          <Text style={styles.badgeCountTxt}>{recetas.length} RECETAS</Text>
        </View>
      </View>

      {/* Buscador Rápido */}
      <View style={styles.searchBar}>
        <TextInput
          style={styles.searchInput}
          placeholder="🔍 Buscar receta por nombre o ingrediente..."
          placeholderTextColor="#64748B"
          value={busqueda}
          onChangeText={setBusqueda}
        />
      </View>

      {/* Categorías Pills */}
      <View style={styles.catPillsContainer}>
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.pillsScroll}>
          {categorias.map((cat) => (
            <TouchableOpacity
              key={cat}
              style={[styles.pill, categoriaActiva === cat && styles.pillActive]}
              onPress={() => setCategoriaActiva(cat)}>
              <Text style={[styles.pillTxt, categoriaActiva === cat && styles.pillTxtActive]}>
                {cat}
              </Text>
            </TouchableOpacity>
          ))}
        </ScrollView>
      </View>

      <ScrollView
        contentContainerStyle={styles.scroll}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => cargarRecetas(true)} />}>
        {loading ? (
          <ActivityIndicator size="large" color={MostazaColors.amarillo} style={{ marginTop: 40 }} />
        ) : recetasFiltradas.length === 0 ? (
          <View style={styles.emptyBox}>
            <Text style={{ fontSize: 32, marginBottom: 8 }}>🔍</Text>
            <Text style={styles.emptyTxt}>No se encontraron recetas con el filtro actual.</Text>
          </View>
        ) : (
          recetasFiltradas.map((r) => (
            <View key={r.idreceta} style={styles.card}>
              <View style={styles.cardTop}>
                <View style={{ flex: 1 }}>
                  <Text style={styles.recName}>{r.nombre}</Text>
                  <Text style={styles.recCat}>{r.categoria.toUpperCase()} · Dificultad: {r.dificultad}</Text>
                </View>
                <View style={styles.badgeTime}>
                  <Text style={styles.recTime}>⏱️ {r.tiempo}</Text>
                </View>
              </View>

              <View style={styles.tempBox}>
                <Text style={styles.tempTxt}>🔥 Temperatura / Equipos: <Text style={{ color: '#FBB728', fontWeight: '800' }}>{r.temperatura}</Text></Text>
              </View>

              <Text style={styles.secLbl}>Gramajes e Ingredientes:</Text>
              <Text style={styles.secTxt}>{r.ingredientes}</Text>

              <Text style={[styles.secLbl, { marginTop: 10 }]}>Pasos de Elaboración:</Text>
              <Text style={styles.secTxt}>{r.pasos}</Text>
            </View>
          ))
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
  badgeCount: { backgroundColor: '#FEF3C7', paddingHorizontal: 10, paddingVertical: 4, borderRadius: 20, borderWidth: 1, borderColor: '#FDE68A' },
  badgeCountTxt: { color: '#92400E', fontSize: 10, fontWeight: '800' },
  searchBar: { paddingHorizontal: 16, paddingTop: 12 },
  searchInput: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
    color: '#0F172A',
    fontSize: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  catPillsContainer: { paddingVertical: 8 },
  pillsScroll: { paddingHorizontal: 16, gap: 8 },
  pill: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  pillActive: { backgroundColor: MostazaColors.rojo, borderColor: MostazaColors.rojo },
  pillTxt: { color: '#64748B', fontSize: 11, fontWeight: '700' },
  pillTxtActive: { color: '#FFFFFF', fontWeight: '900' },
  scroll: { padding: 16 },
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 14,
    padding: 16,
    marginBottom: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    shadowColor: '#000000',
    shadowOpacity: 0.04,
    shadowRadius: 6,
    elevation: 2,
  },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 8 },
  recName: { fontSize: 15, fontWeight: '900', color: '#0F172A' },
  recCat: { fontSize: 10, color: '#64748B', fontWeight: '700', marginTop: 2 },
  badgeTime: { backgroundColor: '#FEF3C7', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8, borderWidth: 1, borderColor: '#FDE68A' },
  recTime: { fontSize: 11, color: '#B45309', fontWeight: '900' },
  tempBox: { backgroundColor: '#F8FAFC', padding: 10, borderRadius: 8, marginVertical: 8, borderWidth: 1, borderColor: '#F1F5F9' },
  tempTxt: { fontSize: 11, color: '#334155' },
  secLbl: { fontSize: 11, fontWeight: '800', color: MostazaColors.rojo, marginTop: 4 },
  secTxt: { fontSize: 11, color: '#334155', marginTop: 2, lineHeight: 17 },
  emptyBox: { alignItems: 'center', paddingVertical: 60 },
  emptyTxt: { color: '#64748B', fontSize: 13, fontWeight: '700' },
});
