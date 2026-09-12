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
      Alert.alert('Atención', 'Seleccioná al menos un ítem para la mesa.');
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

  const prodsFiltrados = busqueda
    ? productos.filter((p) => p.nombre.toLowerCase().includes(busqueda.toLowerCase()))
    : productos;

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>📝 COMANDA MESA #{mesaNum}</Text>
        <TextInput
          style={styles.mesaInput}
          value={String(mesaNum)}
          keyboardType="numeric"
          onChangeText={(v) => setMesaNum(Number(v) || 1)}
        />
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        <Text style={styles.secTitle}>1. Catálogo de Productos</Text>
        <TextInput
          style={styles.search}
          placeholder="Buscar hamburguesa, papas, bebida..."
          placeholderTextColor="#64748B"
          value={busqueda}
          onChangeText={setBusqueda}
        />

        {loading ? (
          <ActivityIndicator color={MostazaColors.rojo} />
        ) : (
          <View style={styles.catalogGrid}>
            {prodsFiltrados.slice(0, 12).map((p) => (
              <TouchableOpacity key={p.idproducto} style={styles.catItem} onPress={() => agregarItem(p)}>
                <Text style={styles.catName} numberOfLines={1}>{p.nombre}</Text>
                <Text style={styles.catPrice}>${p.precio.toLocaleString('es-AR')}</Text>
              </TouchableOpacity>
            ))}
          </View>
        )}

        <Text style={[styles.secTitle, { marginTop: 20 }]}>2. Resumen Comanda ({itemsComanda.length} ítems)</Text>

        {itemsComanda.length === 0 ? (
          <View style={styles.emptyBox}>
            <Text style={styles.emptyTxt}>Tocá productos arriba para agregarlos a la mesa.</Text>
          </View>
        ) : (
          itemsComanda.map((it) => (
            <View key={it.idproducto} style={styles.itemRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.itemName}>{it.cantidad}x {it.nombre}</Text>
                <TextInput
                  style={styles.modInput}
                  placeholder="Aclaración cocina (ej: sin sal, sin aderezo)"
                  placeholderTextColor="#64748B"
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
              <Text style={styles.subTot}>${(it.precio * it.cantidad).toLocaleString('es-AR')}</Text>
            </View>
          ))
        )}

        <View style={styles.totalCard}>
          <Text style={styles.totalLbl}>TOTAL COMANDA:</Text>
          <Text style={styles.totalVal}>${totalComanda.toLocaleString('es-AR')}</Text>
        </View>

        <TouchableOpacity
          style={[styles.btnSend, enviando && { opacity: 0.6 }]}
          onPress={enviarACocina}
          disabled={enviando}>
          <Text style={styles.btnSendTxt}>
            {enviando ? 'Enviando...' : '🚀 ENVIAR COMANDA A COCINA'}
          </Text>
        </TouchableOpacity>
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#0B0F19' },
  header: {
    backgroundColor: '#0F172A',
    paddingHorizontal: 20,
    paddingVertical: 14,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: '#FBB728' },
  mesaInput: {
    backgroundColor: '#1E293B',
    color: '#FFF',
    fontWeight: '900',
    fontSize: 14,
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: '#334155',
    width: 60,
    textAlign: 'center',
  },
  scroll: { padding: 16 },
  secTitle: { fontSize: 13, fontWeight: '800', color: '#F8FAFC', marginBottom: 8 },
  search: {
    backgroundColor: '#1E293B',
    borderRadius: 10,
    padding: 10,
    color: '#FFF',
    fontSize: 13,
    marginBottom: 10,
    borderWidth: 1,
    borderColor: '#334155',
  },
  catalogGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  catItem: {
    width: '48%',
    backgroundColor: '#1E293B',
    padding: 10,
    borderRadius: 10,
    borderWidth: 1,
    borderColor: '#334155',
  },
  catName: { color: '#FBB728', fontSize: 12, fontWeight: '800' },
  catPrice: { color: '#94A3B8', fontSize: 11, fontWeight: '700', marginTop: 2 },
  emptyBox: { backgroundColor: '#1E293B', padding: 20, borderRadius: 12, alignItems: 'center' },
  emptyTxt: { color: '#64748B', fontSize: 12 },
  itemRow: {
    backgroundColor: '#1E293B',
    borderRadius: 10,
    padding: 10,
    marginBottom: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    borderWidth: 1,
    borderColor: '#334155',
  },
  itemName: { color: '#FFF', fontSize: 12, fontWeight: '800' },
  modInput: {
    backgroundColor: '#0F172A',
    borderRadius: 6,
    paddingHorizontal: 8,
    paddingVertical: 2,
    color: '#FFF',
    fontSize: 10,
    marginTop: 4,
    borderWidth: 1,
    borderColor: '#334155',
  },
  qtyBox: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  btnQty: { backgroundColor: '#334155', width: 24, height: 24, borderRadius: 4, alignItems: 'center', justifyContent: 'center' },
  btnQtyTxt: { color: '#FFF', fontWeight: '900', fontSize: 14 },
  qtyTxt: { color: '#FFF', fontWeight: '800', fontSize: 13 },
  subTot: { color: '#FBB728', fontWeight: '900', fontSize: 12 },
  totalCard: {
    backgroundColor: '#1E293B',
    padding: 14,
    borderRadius: 12,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 14,
    borderWidth: 1,
    borderColor: '#334155',
  },
  totalLbl: { color: '#FFF', fontWeight: '800', fontSize: 13 },
  totalVal: { color: MostazaColors.rojo, fontWeight: '900', fontSize: 18 },
  btnSend: {
    backgroundColor: MostazaColors.rojo,
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 14,
  },
  btnSendTxt: { color: '#FFF', fontWeight: '900', fontSize: 14 },
});
