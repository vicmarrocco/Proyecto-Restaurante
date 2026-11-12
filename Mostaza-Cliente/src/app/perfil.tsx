import React, { useCallback, useState } from 'react';
import { ActivityIndicator, Alert, FlatList, RefreshControl, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect, useRouter } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { Config } from '@/constants/Config';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { Pedido } from '@/types/cliente';
import LoginScreen from '@/components/LoginScreen';
import StatusStepper from '@/components/StatusStepper';
import {
  ENTREGA_LABEL,
  esActivo,
  estadoColor,
  estadoEmoji,
  estadoLabel,
  estadoPaso,
  estadoSubtitulo,
  fechaCorta,
  formatNumPedido,
  money,
} from '@/utils/format';

export default function PedidosScreen() {
  const router = useRouter();
  const { usuario } = useAuth();
  const { add } = useCart();
  const [pedidos, setPedidos] = useState<Pedido[]>([]);
  const [tab, setTab] = useState<'activos' | 'historial'>('activos');
  const [filtroNum, setFiltroNum] = useState('');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const cargar = useCallback(async () => {
    if (!ApiService.hasToken()) { setLoading(false); return; }
    const r = await ApiService.misPedidos();
    if (r.ok) { setPedidos(r.pedidos); setError(null); } else setError(r.error || 'No pudimos cargar tus pedidos.');
    setLoading(false); setRefreshing(false);
  }, []);

  // Carga inicial al enfocar y polling en tiempo real cada 5s para ver cambios de estado en vivo
  useFocusEffect(
    useCallback(() => {
      cargar();
      const timer = setInterval(cargar, Config.POLLING_MS);
      return () => clearInterval(timer);
    }, [cargar, usuario])
  );

  const repetir = async (p: Pedido) => {
    const r = await ApiService.productos();
    if (!r.ok) return Alert.alert('Error', r.error || 'No pudimos cargar el menú.');
    let n = 0;
    p.items.forEach((it) => {
      const prod = r.productos.find((x) => x.idproducto === Number(it.idproducto));
      if (!prod) return;
      const t = prod.tamanos.find((tm) => it.modificadores?.includes(tm.nombre));
      add(prod, { cantidad: Number(it.cantidad), tamano: t?.nombre, extra: t?.extra || 0 });
      n++;
    });
    if (n === 0) return Alert.alert('No disponible', 'Los productos de este pedido ya no están en el menú.');
    router.push('/carrito');
  };

  if (!usuario) return <LoginScreen motivo="Ingresá para ver el número y estado de tus pedidos" />;

  const qNum = filtroNum.replace(/[^0-9]/g, '').trim();
  const lista = pedidos.filter((p) => {
    const coincideTab = tab === 'activos' ? esActivo(p.estado) : !esActivo(p.estado);
    if (!qNum) return coincideTab;
    return (
      String(p.idpedido).includes(qNum) ||
      (p.numero_pedido_usuario && String(p.numero_pedido_usuario).includes(qNum))
    );
  });
  const activos = pedidos.filter((p) => esActivo(p.estado)).length;

  return (
    <SafeAreaView style={s.safe} edges={['top']}>
      <View style={s.header}>
        <View style={{ flex: 1 }}>
          <Text style={s.title}>Mis pedidos</Text>
          <Text style={s.subtitle}>Seguí tu número de pedido y estado en tiempo real</Text>
        </View>
        {activos > 0 && (
          <View style={s.liveHeaderPill}>
            <Text style={s.liveHeaderDot}>●</Text>
            <Text style={s.liveHeaderTxt}>En vivo</Text>
          </View>
        )}
      </View>

      <View style={s.tabs}>
        {(['activos', 'historial'] as const).map((t) => (
          <TouchableOpacity key={t} style={[s.tab, tab === t && s.tabOn]} onPress={() => setTab(t)}>
            <Text style={[s.tabTxt, tab === t && { color: C.white }]}>
              {t === 'activos' ? `En curso${activos ? ` (${activos})` : ''}` : 'Historial'}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <View style={s.searchBarWrap}>
        <TextInput
          style={s.searchInput}
          placeholder="Buscar por número de pedido (ej: 15)…"
          placeholderTextColor={C.textMuted}
          keyboardType="number-pad"
          value={filtroNum}
          onChangeText={setFiltroNum}
        />
        {!!filtroNum && (
          <TouchableOpacity style={s.clearBtn} onPress={() => setFiltroNum('')}>
            <Text style={s.clearTxt}>✕</Text>
          </TouchableOpacity>
        )}
      </View>

      {loading ? <ActivityIndicator color={C.rojo} size="large" style={{ marginTop: 40 }} /> : (
        <FlatList
          style={{ backgroundColor: C.lightBg }}
          contentContainerStyle={{ padding: 14, paddingBottom: 30, flexGrow: 1 }}
          data={lista}
          keyExtractor={(p) => String(p.idpedido)}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); cargar(); }} tintColor={C.rojo} />}
          ListEmptyComponent={
            <View style={s.empty}>
              <Text style={{ fontSize: 56 }}>{error ? '⚠️' : tab === 'activos' ? '🛵' : '🧾'}</Text>
              <Text style={s.emptyTxt}>
                {error || (qNum ? `No encontramos el pedido #${qNum}` : tab === 'activos' ? 'No tenés pedidos en curso' : 'Todavía no hiciste pedidos')}
              </Text>
              <TouchableOpacity style={s.cta} onPress={() => router.push('/menu')}><Text style={s.ctaTxt}>Pedir ahora</Text></TouchableOpacity>
            </View>
          }
          renderItem={({ item: p }) => {
            const col = estadoColor(p.estado);
            const lbl = estadoLabel(p.estado, p.tipo_entrega);
            const emo = estadoEmoji(p.estado, p.tipo_entrega);
            const paso = estadoPaso(p.estado, p.tipo_entrega);
            const subEstado = estadoSubtitulo(p.estado, p.tipo_entrega, p.numero_mesa);
            const resumen = p.items.map((i) => `${i.cantidad}× ${i.descripcion}`).join(', ');
            const activo = esActivo(p.estado);

            return (
              <TouchableOpacity
                style={[s.card, activo && s.cardActive]}
                activeOpacity={0.9}
                onPress={() => router.push({ pathname: '/pedido/[id]', params: { id: String(p.idpedido) } })}>
                <View style={s.rowTop}>
                  <View style={s.orderBadgeWrap}>
                    <View style={s.numTicket}>
                      <Text style={s.numTicketLbl}>PEDIDO N°</Text>
                      <Text style={s.numTicketVal}>{formatNumPedido(p.idpedido)}</Text>
                    </View>
                    <View style={{ marginLeft: 10 }}>
                      <Text style={s.num}>Pedido {formatNumPedido(p.idpedido)}</Text>
                      {!!p.numero_pedido_usuario && (
                        <Text style={s.userOrderNum}>Tu orden #{p.numero_pedido_usuario}</Text>
                      )}
                      <Text style={s.meta}>
                        {fechaCorta(p.fecha)} · {ENTREGA_LABEL[p.tipo_entrega]}
                        {p.numero_mesa ? ` · Mesa ${p.numero_mesa}` : ''}
                      </Text>
                    </View>
                  </View>

                  <View style={[s.badge, { backgroundColor: col.bg, borderColor: col.border }]}>
                    <Text style={s.badgeEmoji}>{emo}</Text>
                    <Text style={[s.badgeTxt, { color: col.fg }]}>{lbl.toUpperCase()}</Text>
                  </View>
                </View>

                <View style={[s.statusBox, { backgroundColor: col.bg }]}>
                  <Text style={[s.statusBoxTitle, { color: col.fg }]}>
                    {emo} Estado: {lbl}
                  </Text>
                  <Text style={s.statusBoxSub}>{subEstado}</Text>
                  {!!p.cocinero_nombre && (
                    <Text style={s.staffInfo}>👨‍🍳 En cocina: {p.cocinero_nombre}</Text>
                  )}
                  {!!p.repartidor_asignado && (
                    <Text style={s.staffInfo}>🛵 Repartidor: {p.repartidor_asignado}</Text>
                  )}
                  {!!p.mesero_nombre && (
                    <Text style={s.staffInfo}>🤵 Mesero: {p.mesero_nombre}</Text>
                  )}
                </View>

                {activo && paso > 0 && (
                  <StatusStepper paso={paso} tipo={p.tipo_entrega} />
                )}

                <Text style={s.items} numberOfLines={2}>{resumen}</Text>

                <View style={s.rowBottom}>
                  <Text style={s.total}>{money(p.total)}</Text>
                  {activo ? (
                    <View style={s.trackBtn}>
                      <Text style={s.track}>Ver estado en vivo →</Text>
                    </View>
                  ) : (
                    (p.estado === 'Entregado' || p.estado === 'Retirado') && (
                      <TouchableOpacity style={s.repeat} onPress={() => repetir(p)}>
                        <Text style={s.repeatTxt}>↻ Pedir de nuevo</Text>
                      </TouchableOpacity>
                    )
                  )}
                </View>
              </TouchableOpacity>
            );
          }}
        />
      )}
    </SafeAreaView>
  );
}

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 16, paddingVertical: 12 },
  title: { color: C.white, fontSize: 22, fontWeight: '900' },
  subtitle: { color: '#FECACA', fontSize: 12, fontWeight: '600', marginTop: 2 },
  liveHeaderPill: { flexDirection: 'row', alignItems: 'center', backgroundColor: 'rgba(255,255,255,0.18)', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 12 },
  liveHeaderDot: { color: '#4ADE80', fontSize: 10, marginRight: 5 },
  liveHeaderTxt: { color: C.white, fontSize: 11, fontWeight: '800' },
  tabs: { flexDirection: 'row', backgroundColor: C.white, paddingHorizontal: 12, paddingTop: 10, paddingBottom: 8, gap: 8 },
  tab: { flex: 1, paddingVertical: 10, borderRadius: 12, alignItems: 'center', backgroundColor: C.borderLight },
  tabOn: { backgroundColor: C.rojo },
  tabTxt: { fontWeight: '800', color: C.textMuted, fontSize: 13 },
  searchBarWrap: { flexDirection: 'row', alignItems: 'center', backgroundColor: C.white, paddingHorizontal: 12, paddingBottom: 10, borderBottomWidth: 1, borderBottomColor: C.border },
  searchInput: { flex: 1, backgroundColor: C.lightBg, borderWidth: 1, borderColor: C.border, borderRadius: 12, paddingHorizontal: 14, height: 40, fontSize: 13, color: C.textDark },
  clearBtn: { marginLeft: 8, paddingHorizontal: 10, height: 40, justifyContent: 'center' },
  clearTxt: { color: C.textMuted, fontWeight: '900', fontSize: 15 },
  card: { backgroundColor: C.white, borderRadius: 18, padding: 14, marginBottom: 12, borderWidth: 1, borderColor: C.border },
  cardActive: { borderColor: C.amarillo, borderWidth: 1.5, elevation: 2, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 6, shadowOffset: { width: 0, height: 2 } },
  rowTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
  orderBadgeWrap: { flexDirection: 'row', alignItems: 'center', flex: 1, marginRight: 8 },
  numTicket: { backgroundColor: C.dark, borderRadius: 12, paddingVertical: 8, paddingHorizontal: 10, alignItems: 'center', justifyContent: 'center', minWidth: 72, borderWidth: 1.5, borderColor: C.amarillo },
  numTicketLbl: { color: C.amarillo, fontSize: 8.5, fontWeight: '900', letterSpacing: 0.8 },
  numTicketVal: { color: C.white, fontSize: 18, fontWeight: '900', marginTop: 1 },
  num: { fontSize: 15, fontWeight: '900', color: C.textDark },
  userOrderNum: { fontSize: 11.5, fontWeight: '800', color: C.rojo, marginTop: 1 },
  badge: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 10, borderWidth: 1 },
  badgeEmoji: { fontSize: 13, marginRight: 4 },
  badgeTxt: { fontSize: 11, fontWeight: '900', letterSpacing: 0.4 },
  meta: { fontSize: 11.5, color: C.textMuted, marginTop: 2, fontWeight: '600' },
  statusBox: { marginTop: 12, padding: 10, borderRadius: 12 },
  statusBoxTitle: { fontSize: 13, fontWeight: '900' },
  statusBoxSub: { fontSize: 12, color: C.textDark, marginTop: 2, fontWeight: '600' },
  staffInfo: { fontSize: 11.5, color: C.textMuted, marginTop: 4, fontWeight: '700' },
  items: { fontSize: 13, color: C.textDark, marginTop: 8, lineHeight: 18, fontWeight: '600' },
  rowBottom: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 10, paddingTop: 10, borderTopWidth: 1, borderTopColor: C.borderLight },
  total: { fontSize: 17, fontWeight: '900', color: C.rojo },
  trackBtn: { backgroundColor: C.rojoClaro, paddingHorizontal: 12, paddingVertical: 7, borderRadius: 10 },
  track: { color: C.rojo, fontWeight: '900', fontSize: 12.5 },
  repeat: { backgroundColor: C.amarilloClaro, paddingHorizontal: 12, paddingVertical: 7, borderRadius: 10 },
  repeatTxt: { color: '#92400E', fontWeight: '900', fontSize: 12 },
  empty: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: 30 },
  emptyTxt: { marginTop: 10, fontSize: 16, fontWeight: '800', color: C.textDark, textAlign: 'center' },
  cta: { backgroundColor: C.rojo, paddingHorizontal: 26, paddingVertical: 13, borderRadius: 14, marginTop: 18 },
  ctaTxt: { color: C.white, fontWeight: '900' },
});
