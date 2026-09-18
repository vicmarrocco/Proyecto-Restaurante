import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  TextInput,
  Alert,
  ActivityIndicator,
  SafeAreaView,
} from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { ApiService } from '@/services/api';
import { ProductoCatalogo, ItemComanda } from '@/types/mesero';
import { MostazaColors } from '@/constants/Colors';

export default function ComandaScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const [mesaNum, setMesaNum] = useState<number>(Number(params.mesa || 1));
  const [productos, setProductos] = useState<ProductoCatalogo[]>([]);
  const [itemsComanda, setItemsComanda] = useState<ItemComanda[]>([]);
  const [busqueda, setBusqueda] = useState('');
  const [categoriaActiva, setCategoriaActiva] = useState('Todas');
  const [loading, setLoading] = useState(true);
  const [enviando, setEnviando] = useState(false);

  useEffect(() => {
    const cargar = async () => {
      const res = await ApiService.getCatalogo();
      if (res.ok) setProductos(res.productos);
      setLoading(false);
    };
    cargar();
  }, []);

  const categorias = ['Todas', ...Array.from(new Set(productos.map((p) => p.categoria)))];

  const agregarItem = (p: ProductoCatalogo) => {
    setItemsComanda((prev) => {
      const exist = prev.find((x) => x.idproducto === p.idproducto);
      if (exist) {
        return prev.map((x) =>
          x.idproducto === p.idproducto ? { ...x, cantidad: x.cantidad + 1 } : x
        );
      }
      return [...prev, { idproducto: p.idproducto, nombre: p.nombre, precio: p.precio, cantidad: 1, modificadores: '' }];
    });
  };

  const cambiarQty = (idproducto: number, delta: number) => {
    setItemsComanda((prev) =>
      prev
        .map((x) => (x.idproducto === idproducto ? { ...x, cantidad: x.cantidad + delta } : x))
        .filter((x) => x.cantidad > 0)
    );
  };

  const setMod = (idproducto: number, text: string) => {
    setItemsComanda((prev) =>
      prev.map((x) => (x.idproducto === idproducto ? { ...x, modificadores: text } : x))
    );
  };

  const totalComanda = itemsComanda.reduce((acc, it) => acc + it.precio * it.cantidad, 0);

  const enviarACocina = async () => {
    if (itemsComanda.length === 0) {
      Alert.alert('Atención', 'Seleccioná al menos un ítem para la comanda.');
      return;
    }

    setEnviando(true);
    const res = await ApiService.enviarComanda(mesaNum, itemsComanda);
    setEnviando(false);

    if (res.ok) {
      Alert.alert('¡Comanda Enviada!', `Comanda enviada exitosamente a la Cocina KDS para Mesa #${mesaNum}.`, [
        { text: 'OK', onPress: () => router.push('/') },
      ]);
      setItemsComanda([]);
    } else {
      Alert.alert('Error', res.error || 'No se pudo enviar la comanda.');
    }
  };

  const prodsFiltrados = productos.filter((p) => {
    const matchCat = categoriaActiva === 'Todas' || p.categoria === categoriaActiva;
    const matchTxt = !busqueda || p.nombre.toLowerCase().includes(busqueda.toLowerCase());
    return matchCat && matchTxt;
  });

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View>
          <Text style={styles.headerTitle}>📝 COMANDA DE SALÓN</Text>
          <Text style={styles.headerSub}>Toma de Pedido y Marcha a Cocina</Text>
        </View>

        <View style={styles.mesaSelector}>
          <Text style={styles.mesaLbl}>MESA N.º</Text>
          <TextInput
            style={styles.mesaInput}
            value={String(mesaNum)}
            keyboardType="numeric"
            onChangeText={(v) => setMesaNum(Number(v) || 1)}
          />
        </View>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        {/* Catálogo */}
        <Text style={styles.secTitle}>1. Catálogo de Productos</Text>

        <TextInput
          style={styles.search}
          placeholder="🔍 Buscar hamburguesa, papas, gaseosa..."
          placeholderTextColor="#94A3B8"
          value={busqueda}
          onChangeText={setBusqueda}
        />

        {/* Categorías Pills */}
        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.catScroll}>
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

        {loading ? (
          <ActivityIndicator color={MostazaColors.rojo} style={{ marginVertical: 20 }} />
        ) : (
          <View style={styles.catalogGrid}>
            {prodsFiltrados.slice(0, 15).map((p) => (
              <TouchableOpacity
                key={p.idproducto}
                style={styles.catItem}
                onPress={() => agregarItem(p)}
                activeOpacity={0.8}>
                <Text style={styles.catName} numberOfLines={2}>{p.nombre}</Text>
                <Text style={styles.catPrice}>${p.precio.toLocaleString('es-AR')}</Text>
              </TouchableOpacity>
            ))}
          </View>
        )}

        {/* Resumen Comanda */}
        <Text style={[styles.secTitle, { marginTop: 22 }]}>
          2. Resumen de Comanda ({itemsComanda.length} ítems)
        </Text>

        {itemsComanda.length === 0 ? (
          <View style={styles.emptyBox}>
            <Text style={styles.emptyTxt}>Tocá productos arriba para agregarlos a la mesa.</Text>
          </View>
        ) : (
          itemsComanda.map((it) => (
            <View key={it.idproducto} style={styles.itemRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.itemName}>{it.nombre}</Text>
                <TextInput
                  style={styles.modInput}
                  placeholder="Aclaración cocina (ej: sin cebolla, bien cocido)"
                  placeholderTextColor="#94A3B8"
                  value={it.modificadores}
                  onChangeText={(t) => setMod(it.idproducto, t)}
                />
              </View>

              <View style={styles.qtyBox}>
                <TouchableOpacity style={styles.btnQty} onPress={() => cambiarQty(it.idproducto, -1)}>
                  <Text style={styles.btnQtyTxt}>-</Text>
                </TouchableOpacity>
                <Text style={styles.qtyTxt}>{it.cantidad}</Text>
                <TouchableOpacity style={styles.btnQty} onPress={() => cambiarQty(it.idproducto, 1)}>
                  <Text style={styles.btnQtyTxt}>+</Text>
                </TouchableOpacity>
              </View>

              <Text style={styles.subTot}>
                ${(it.precio * it.cantidad).toLocaleString('es-AR')}
              </Text>
            </View>
          ))
        )}

        {/* Total y Botón */}
        <View style={styles.totalCard}>
          <Text style={styles.totalLbl}>TOTAL COMANDA MESA #{mesaNum}:</Text>
          <Text style={styles.totalVal}>${totalComanda.toLocaleString('es-AR')}</Text>
        </View>

        <TouchableOpacity
          style={[styles.btnSend, enviando && { opacity: 0.6 }]}
          onPress={enviarACocina}
          disabled={enviando}
          activeOpacity={0.8}>
          <Text style={styles.btnSendTxt}>
            {enviando ? 'Enviando comanda...' : '🚀 MARCHAR COMANDA A COCINA KDS'}
          </Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F8FAFC' },
  header: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 20,
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
  mesaSelector: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  mesaLbl: { fontSize: 11, fontWeight: '800', color: '#475569' },
  mesaInput: {
    backgroundColor: '#F1F5F9',
    color: '#0F172A',
    fontWeight: '900',
    fontSize: 15,
    paddingHorizontal: 12,
    paddingVertical: 5,
    borderRadius: 8,
    borderWidth: 1.5,
    borderColor: MostazaColors.rojo,
    width: 60,
    textAlign: 'center',
  },
  scroll: { padding: 16, paddingBottom: 36 },
  secTitle: { fontSize: 14, fontWeight: '900', color: '#0F172A', marginBottom: 10, letterSpacing: -0.2 },
  search: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    paddingHorizontal: 14,
    paddingVertical: 10,
    color: '#0F172A',
    fontSize: 13,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 10,
    elevation: 1,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowOffset: { width: 0, height: 1 },
    shadowRadius: 2,
  },
  catScroll: { gap: 8, marginBottom: 14 },
  pill: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 14,
    paddingVertical: 7,
    borderRadius: 20,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  pillActive: { backgroundColor: MostazaColors.rojo, borderColor: MostazaColors.rojo },
  pillTxt: { color: '#64748B', fontSize: 11, fontWeight: '700' },
  pillTxtActive: { color: '#FFFFFF', fontWeight: '900' },
  catalogGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  catItem: {
    width: '31.5%',
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    justifyContent: 'space-between',
    minHeight: 80,
    elevation: 1,
    shadowColor: '#000',
    shadowOpacity: 0.04,
    shadowOffset: { width: 0, height: 1 },
    shadowRadius: 2,
  },
  catName: { fontSize: 11, fontWeight: '800', color: '#0F172A', lineHeight: 14 },
  catPrice: { fontSize: 12, fontWeight: '900', color: MostazaColors.rojo, marginTop: 4 },
  emptyBox: {
    backgroundColor: '#FFFFFF',
    padding: 24,
    borderRadius: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  emptyTxt: { color: '#64748B', fontSize: 12, fontWeight: '600' },
  itemRow: {
    backgroundColor: '#FFFFFF',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    elevation: 1,
    shadowColor: '#000',
    shadowOpacity: 0.03,
    shadowOffset: { width: 0, height: 1 },
    shadowRadius: 2,
  },
  itemName: { fontSize: 13, fontWeight: '800', color: '#0F172A' },
  modInput: {
    backgroundColor: '#F8FAFC',
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 4,
    color: '#0F172A',
    fontSize: 11,
    marginTop: 4,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  qtyBox: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  btnQty: { backgroundColor: '#F1F5F9', width: 28, height: 28, borderRadius: 6, alignItems: 'center', justifyContent: 'center', borderWidth: 1, borderColor: '#E2E8F0' },
  btnQtyTxt: { color: '#0F172A', fontWeight: '900', fontSize: 15 },
  qtyTxt: { color: '#0F172A', fontWeight: '900', fontSize: 13, minWidth: 16, textAlign: 'center' },
  subTot: { color: MostazaColors.rojo, fontWeight: '900', fontSize: 13, minWidth: 65, textAlign: 'right' },
  totalCard: {
    backgroundColor: '#FFFFFF',
    padding: 16,
    borderRadius: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 14,
    borderWidth: 1.5,
    borderColor: '#E2E8F0',
    elevation: 2,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
  },
  totalLbl: { color: '#475569', fontWeight: '800', fontSize: 12 },
  totalVal: { color: MostazaColors.rojo, fontWeight: '900', fontSize: 20 },
  btnSend: {
    backgroundColor: MostazaColors.rojo,
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 14,
    elevation: 3,
    shadowColor: MostazaColors.rojo,
    shadowOpacity: 0.3,
    shadowOffset: { width: 0, height: 4 },
    shadowRadius: 6,
  },
  btnSendTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 14, letterSpacing: 0.5 },
});
