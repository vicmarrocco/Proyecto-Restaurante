import React, { useCallback, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect, useRouter } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { Categoria, Cupon, Producto } from '@/types/cliente';
import ProductCard from '@/components/ProductCard';
import ProductModal from '@/components/ProductModal';
import CartBar from '@/components/CartBar';
import { money } from '@/utils/format';

export default function InicioScreen() {
  const router = useRouter();
  const { usuario } = useAuth();
  const [cats, setCats] = useState<Categoria[]>([]);
  const [destacados, setDestacados] = useState<Producto[]>([]);
  const [cupones, setCupones] = useState<Cupon[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [busqueda, setBusqueda] = useState('');
  const [sel, setSel] = useState<Producto | null>(null);

  const cargar = useCallback(async () => {
    const [c, p, cu] = await Promise.all([ApiService.categorias(), ApiService.productos(), ApiService.cupones()]);
    if (!c.ok || !p.ok) setError(c.error || p.error || 'No pudimos cargar el menú.');
    else {
      setError(null);
      setCats(c.categorias);
      const top = p.productos.filter((x) => ['exclusivos', 'megacombos', 'pollo'].includes(x.categoria));
      setDestacados(top.slice(0, 10));
    }
    if (cu.ok) setCupones(cu.cupones);
    setLoading(false); setRefreshing(false);
  }, []);

  useFocusEffect(
    useCallback(() => {
      cargar();
    }, [cargar, usuario])
  );

  const buscar = () => router.push({ pathname: '/menu', params: { q: busqueda.trim() } });

  return (
    <SafeAreaView style={s.safe} edges={['top']}>
      <View style={s.header}>
        <View style={{ flex: 1 }}>
          <Text style={s.hello}>{usuario ? `¡Hola, ${usuario.nombre}!` : '¡Hola! ¿Qué se te antoja?'}</Text>
          <TouchableOpacity onPress={() => router.push('/perfil')}>
            <Text style={s.addr} numberOfLines={1}>📍 {usuario?.direccion_favorita || usuario?.direccion || 'Agregá tu dirección de entrega'}  ▾</Text>
          </TouchableOpacity>
        </View>
        {usuario && <View style={s.pts}><Text style={s.ptsTxt}>⭐ {usuario.puntos}</Text></View>}
      </View>

      <ScrollView
        style={{ flex: 1, backgroundColor: C.lightBg }}
        contentContainerStyle={{ paddingBottom: 90 }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); cargar(); }} tintColor={C.rojo} />}>
        <View style={s.searchWrap}>
          <TextInput style={s.search} placeholder="Buscar hamburguesas, combos, postres…" placeholderTextColor={C.textMuted} value={busqueda} onChangeText={setBusqueda} onSubmitEditing={buscar} returnKeyType="search" />
        </View>

        {loading ? <ActivityIndicator color={C.rojo} style={{ marginTop: 40 }} size="large" /> : error ? (
          <View style={s.errBox}><Text style={s.errTxt}>{error}</Text><TouchableOpacity onPress={() => { setLoading(true); cargar(); }}><Text style={s.retry}>Reintentar</Text></TouchableOpacity></View>
        ) : (
          <>
            {cupones.length > 0 && (
              <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 16, paddingTop: 6 }}>
                {cupones.map((c) => (
                  <View key={c.codigo} style={s.promo}>
                    <Text style={s.promoTag}>CUPÓN</Text>
                    <Text style={s.promoCode}>{c.codigo}</Text>
                    <Text style={s.promoDesc} numberOfLines={2}>{c.descripcion || (c.tipo === 'porcentaje' ? `${Number(c.valor)}% OFF` : `${money(c.valor)} OFF`)}</Text>
                  </View>
                ))}
              </ScrollView>
            )}

            <Text style={s.section}>Categorías</Text>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 16 }}>
              {cats.map((c) => (
                <TouchableOpacity key={c.slug} style={s.cat} onPress={() => router.push({ pathname: '/menu', params: { cat: c.slug } })}>
                  <View style={s.catCircle}><Text style={{ fontSize: 30 }}>{c.emoji}</Text></View>
                  <Text style={s.catName} numberOfLines={2}>{c.nombre}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>

            <View style={s.sectionRow}>
              <Text style={s.section}>Los más pedidos</Text>
              <TouchableOpacity onPress={() => router.push('/menu')}><Text style={s.all}>Ver todo</Text></TouchableOpacity>
            </View>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{ paddingHorizontal: 16, paddingBottom: 6 }}>
              {destacados.map((p) => <ProductCard key={p.idproducto} producto={p} variant="tile" onPress={setSel} />)}
            </ScrollView>
          </>
        )}
      </ScrollView>
      <CartBar />
      <ProductModal producto={sel} onClose={() => setSel(null)} />
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingTop: 8, paddingBottom: 14 },
  hello: { color: C.white, fontSize: 19, fontWeight: '900' },
  addr: { color: C.amarillo, fontSize: 13, fontWeight: '700', marginTop: 3 },
  pts: { backgroundColor: C.amarillo, paddingHorizontal: 12, paddingVertical: 7, borderRadius: 20 },
  ptsTxt: { fontWeight: '900', color: C.dark, fontSize: 13 },
  searchWrap: { backgroundColor: C.rojo, paddingHorizontal: 16, paddingBottom: 18, borderBottomLeftRadius: 22, borderBottomRightRadius: 22 },
  search: { backgroundColor: C.white, borderRadius: 14, paddingHorizontal: 16, height: 48, fontSize: 14, color: C.textDark },
  section: { fontSize: 18, fontWeight: '900', color: C.textDark, paddingHorizontal: 16, marginTop: 20, marginBottom: 12 },
  sectionRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingRight: 16 },
  all: { color: C.rojo, fontWeight: '800', fontSize: 13, marginTop: 8 },
  promo: { width: 190, backgroundColor: C.dark, borderRadius: 16, padding: 14, marginRight: 12, marginTop: 14 },
  promoTag: { color: C.amarillo, fontSize: 10, fontWeight: '900', letterSpacing: 1.5 },
  promoCode: { color: C.white, fontSize: 22, fontWeight: '900', marginTop: 2 },
  promoDesc: { color: '#CBD5E1', fontSize: 12, marginTop: 4 },
  cat: { width: 78, alignItems: 'center', marginRight: 10 },
  catCircle: { width: 64, height: 64, borderRadius: 32, backgroundColor: C.amarilloClaro, alignItems: 'center', justifyContent: 'center', borderWidth: 2, borderColor: C.amarillo },
  catName: { fontSize: 11.5, fontWeight: '800', color: C.textDark, textAlign: 'center', marginTop: 6 },
  errBox: { margin: 20, padding: 16, backgroundColor: '#FEE2E2', borderRadius: 14, alignItems: 'center' },
  errTxt: { color: '#B91C1C', fontWeight: '700', textAlign: 'center' },
  retry: { color: C.rojo, fontWeight: '900', marginTop: 10 },
});
