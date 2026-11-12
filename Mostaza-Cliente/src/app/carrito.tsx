import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useLocalSearchParams } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { ApiService } from '@/services/api';
import { Categoria, Producto } from '@/types/cliente';
import ProductCard from '@/components/ProductCard';
import ProductModal from '@/components/ProductModal';
import CartBar from '@/components/CartBar';

export default function MenuScreen() {
  const params = useLocalSearchParams<{ cat?: string; q?: string }>();
  const [cats, setCats] = useState<Categoria[]>([]);
  const [productos, setProductos] = useState<Producto[]>([]);
  const [cat, setCat] = useState<string>('');
  const [q, setQ] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [sel, setSel] = useState<Producto | null>(null);

  useEffect(() => { setCat(params.cat || ''); setQ(params.q || ''); }, [params.cat, params.q]);

  const cargar = useCallback(async () => {
    const [c, p] = await Promise.all([ApiService.categorias(), ApiService.productos()]);
    if (!c.ok || !p.ok) setError(c.error || p.error || 'No pudimos cargar el menú.');
    else { setError(null); setCats(c.categorias); setProductos(p.productos); }
    setLoading(false); setRefreshing(false);
  }, []);
  useEffect(() => { cargar(); }, [cargar]);

  // Lista plana con encabezados de sección (como las apps de delivery)
  const data = useMemo(() => {
    const term = q.trim().toLowerCase();
    const filtrados = productos.filter((p) =>
      (!cat || p.categoria === cat) && (!term || p.nombre.toLowerCase().includes(term) || (p.descripcion || '').toLowerCase().includes(term)));
    const rows: Array<{ type: 'h'; cat: Categoria } | { type: 'p'; p: Producto }> = [];
    cats.forEach((c) => {
      const delCat = filtrados.filter((p) => p.categoria === c.slug);
      if (delCat.length) { rows.push({ type: 'h', cat: c }); delCat.forEach((p) => rows.push({ type: 'p', p })); }
    });
    return rows;
  }, [productos, cats, cat, q]);

  return (
    <SafeAreaView style={s.safe} edges={['top']}>
      <View style={s.header}>
        <Text style={s.title}>Nuestro menú</Text>
        <TextInput style={s.search} placeholder="Buscar en el menú…" placeholderTextColor={C.textMuted} value={q} onChangeText={setQ} />
      </View>
      <View style={s.chipsWrap}>
        <FlatList horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 12 }}
          data={[{ slug: '', nombre: 'Todo', emoji: '🍽️' } as Categoria, ...cats]} keyExtractor={(c) => c.slug || 'all'}
          renderItem={({ item }) => {
            const on = cat === item.slug;
            return (
              <TouchableOpacity style={[s.chip, on && s.chipOn]} onPress={() => setCat(item.slug)}>
                <Text style={[s.chipTxt, on && { color: C.white }]}>{item.emoji} {item.nombre}</Text>
              </TouchableOpacity>
            );
          }} />
      </View>

      {loading ? <ActivityIndicator color={C.rojo} size="large" style={{ marginTop: 40 }} /> : error ? (
        <View style={s.errBox}><Text style={s.errTxt}>{error}</Text><TouchableOpacity onPress={() => { setLoading(true); cargar(); }}><Text style={s.retry}>Reintentar</Text></TouchableOpacity></View>
      ) : (
        <FlatList
          style={{ backgroundColor: C.lightBg }}
          contentContainerStyle={{ padding: 14, paddingBottom: 100 }}
          data={data}
          keyExtractor={(r) => (r.type === 'h' ? `h-${r.cat.slug}` : `p-${r.p.idproducto}`)}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); cargar(); }} tintColor={C.rojo} />}
          ListEmptyComponent={<Text style={s.empty}>No encontramos productos 😕{'\n'}Probá con otra búsqueda.</Text>}
          renderItem={({ item }) => item.type === 'h' ? (
            <View style={{ marginTop: 8, marginBottom: 10 }}>
              <Text style={s.sectionTitle}>{item.cat.emoji} {item.cat.nombre}</Text>
              <Text style={s.sectionSub}>{item.cat.sub}</Text>
            </View>
          ) : <ProductCard producto={item.p} onPress={setSel} />}
        />
      )}
      <CartBar />
      <ProductModal producto={sel} onClose={() => setSel(null)} />
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  header: { paddingHorizontal: 16, paddingTop: 8, paddingBottom: 14 },
  title: { color: C.white, fontSize: 22, fontWeight: '900', marginBottom: 10 },
  search: { backgroundColor: C.white, borderRadius: 14, paddingHorizontal: 16, height: 46, fontSize: 14, color: C.textDark },
  chipsWrap: { backgroundColor: C.white, paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: C.border },
  chip: { paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20, borderWidth: 1.5, borderColor: C.border, marginRight: 8, backgroundColor: C.white },
  chipOn: { backgroundColor: C.rojo, borderColor: C.rojo },
  chipTxt: { fontSize: 13, fontWeight: '800', color: C.textDark },
  sectionTitle: { fontSize: 18, fontWeight: '900', color: C.textDark },
  sectionSub: { fontSize: 12, color: C.textMuted, marginTop: 2 },
  empty: { textAlign: 'center', color: C.textMuted, marginTop: 50, fontSize: 15, lineHeight: 22 },
  errBox: { margin: 20, padding: 16, backgroundColor: '#FEE2E2', borderRadius: 14, alignItems: 'center' },
  errTxt: { color: '#B91C1C', fontWeight: '700', textAlign: 'center' },
  retry: { color: C.rojo, fontWeight: '900', marginTop: 10 },
});
