import React, { useCallback, useEffect, useRef, useState } from 'react';
import { ActivityIndicator, Platform, RefreshControl, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { Config } from '@/constants/Config';
import { ApiService } from '@/services/api';
import { Pedido, Tracking } from '@/types/cliente';
import StatusStepper from '@/components/StatusStepper';
import { ENTREGA_LABEL, esActivo, estadoColor, estadoEmoji, estadoLabel, estadoPaso, formatNumPedido, horaCorta, money } from '@/utils/format';

export default function PedidoDetalle() {
  const router = useRouter();
  const { id, nuevo } = useLocalSearchParams<{ id: string; nuevo?: string }>();
  const insets = useSafeAreaInsets();
  const bottomSafe = Platform.OS === 'web' ? 0 : Math.max(insets.bottom, Platform.OS === 'android' ? 44 : 16);
  const [t, setT] = useState<Tracking | null>(null);
  const [pedido, setPedido] = useState<Pedido | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const timer = useRef<ReturnType<typeof setInterval> | null>(null);

  const cargar = useCallback(async () => {
    const [r, mp] = await Promise.all([
      ApiService.tracking(Number(id)),
      ApiService.misPedidos(),
    ]);
    if (r.ok) {
      setT(r as unknown as Tracking);
      setError(null);
    } else {
      setError(r.error || 'No pudimos cargar el pedido.');
    }
    if (mp.ok) {
      setPedido(mp.pedidos.find((p) => Number(p.idpedido) === Number(id)) || null);
    }
    setRefreshing(false);
  }, [id]);

  useEffect(() => {
    cargar();
  }, [cargar, id]);

  // Seguimiento en vivo (polling continuo igual que las apps de cocina/mesero)
  useEffect(() => {
    if (timer.current) clearInterval(timer.current);
    if (t && !esActivo(t.estado)) return undefined;
    timer.current = setInterval(cargar, Config.POLLING_MS);
    return () => { if (timer.current) clearInterval(timer.current); };
  }, [t?.estado, cargar]);

  const volver = () => router.replace('/pedidos');
  const estadoActual = t?.estado || pedido?.estado || '';
  const tipoActual = t?.tipo_entrega || pedido?.tipo_entrega || 'delivery';
  const col = estadoActual ? estadoColor(estadoActual) : null;
  const labelEstado = t?.estado_label || estadoLabel(estadoActual, tipoActual);
  const emojiEstado = estadoEmoji(estadoActual, tipoActual);
  const pasoActual = t?.paso !== undefined ? t.paso : estadoPaso(estadoActual, tipoActual);
  const numOrdenUsuario = t?.numero_pedido_usuario || pedido?.numero_pedido_usuario;

  return (
    <SafeAreaView style={s.safe} edges={['top']}>
      <View style={s.header}>
        <TouchableOpacity onPress={volver} hitSlop={12}><Text style={s.back}>‹</Text></TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={s.title}>Seguimiento de Pedido</Text>
          <Text style={s.headerSub}>Orden {formatNumPedido(id || 0)}</Text>
        </View>
        <TouchableOpacity style={s.refreshBtn} onPress={() => { setRefreshing(true); cargar(); }}>
          <Text style={s.refreshTxt}>↻ Actualizar</Text>
        </TouchableOpacity>
      </View>

      <ScrollView
        style={{ backgroundColor: C.lightBg }}
        contentContainerStyle={{ padding: 14, paddingBottom: 40 + bottomSafe }}
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); cargar(); }} tintColor={C.rojo} />}>
        {nuevo === '1' && (
          <View style={s.ok}>
            <Text style={s.okTxt}>{pedido?.metodo_pago === 'efectivo' || t?.metodo_pago === 'efectivo' ? '✅ ¡Pedido confirmado! Ya ingresó a cocina. Pagás en efectivo al recibirlo. Acá podés seguir tu número de orden y estado en vivo.' : '✅ ¡Pago aprobado! Tu pedido ya ingresó a cocina. Acá podés seguir tu número de orden y estado en vivo.'}</Text>
          </View>
        )}
        {!t && !error && <ActivityIndicator color={C.rojo} size="large" style={{ marginTop: 40 }} />}
        {!!error && !t && <View style={s.err}><Text style={{ color: '#B91C1C', fontWeight: '700' }}>{error}</Text></View>}

        {t && col && (
          <>
            {/* Tarjeta Principal: Número de Pedido + Estado Actual */}
            <View style={s.heroOrderCard}>
              <View style={s.heroTopRow}>
                <View style={s.orderNumberBox}>
                  <Text style={s.orderNumberLabel}>N° DE PEDIDO</Text>
                  <Text style={s.orderNumberValue}>{formatNumPedido(t.idpedido)}</Text>
                  {!!numOrdenUsuario && (
                    <Text style={s.orderUserSub}>Tu pedido #{numOrdenUsuario}</Text>
                  )}
                </View>

                <View style={{ flex: 1, marginLeft: 14, justifyContent: 'center' }}>
                  <Text style={s.statusSectionLabel}>ESTADO DEL PEDIDO</Text>
                  <View style={[s.statusBigBadge, { backgroundColor: col.bg, borderColor: col.border }]}>
                    <Text style={s.statusBigEmoji}>{emojiEstado}</Text>
                    <Text style={[s.statusBigTxt, { color: col.fg }]}>{labelEstado.toUpperCase()}</Text>
                  </View>
                  <Text style={s.rawStateHint}>
                    Modalidad: {ENTREGA_LABEL[t.tipo_entrega]}
                    {t.tipo_entrega === 'salon' && t.numero_mesa ? ` · Mesa #${t.numero_mesa}` : ''}
                  </Text>
                </View>
              </View>

              <View style={s.heroDivider} />

              <Text style={s.big}>{t.titulo}</Text>
              <Text style={s.sub}>{t.descripcion}</Text>

              {pasoActual > 0 && <StatusStepper paso={pasoActual} tipo={t.tipo_entrega} />}

              {/* Hitos de horario del pedido */}
              <View style={s.timeRow}>
                {!!horaCorta(t.hora_recibido || pedido?.fecha) && (
                  <View style={s.timePill}>
                    <Text style={s.timePillLbl}>⏳ En espera</Text>
                    <Text style={s.timePillVal}>{horaCorta(t.hora_recibido || pedido?.fecha)}</Text>
                  </View>
                )}
                {!!horaCorta(t.hora_cocina_inicio) && (
                  <View style={s.timePill}>
                    <Text style={s.timePillLbl}>👨‍🍳 En preparación</Text>
                    <Text style={s.timePillVal}>{horaCorta(t.hora_cocina_inicio)}</Text>
                  </View>
                )}
                {!!horaCorta(t.hora_cocina_listo) && (
                  <View style={s.timePill}>
                    <Text style={s.timePillLbl}>✅ Listo</Text>
                    <Text style={s.timePillVal}>{horaCorta(t.hora_cocina_listo)}</Text>
                  </View>
                )}
                {!!horaCorta(t.hora_entregado) && (
                  <View style={s.timePill}>
                    <Text style={s.timePillLbl}>🎉 Entregado</Text>
                    <Text style={s.timePillVal}>{horaCorta(t.hora_entregado)}</Text>
                  </View>
                )}
              </View>

              {esActivo(t.estado) && (
                <View style={s.liveBanner}>
                  <Text style={s.liveDot}>●</Text>
                  <Text style={s.live}>Actualizando estado en tiempo real</Text>
                </View>
              )}
            </View>

            <View style={s.card}>
              <Text style={s.cardTitle}>Detalles del seguimiento</Text>
              <Info l="Número de comanda" v={`Pedido ${formatNumPedido(t.idpedido)}`} />
              <Info l="Estado actual" v={`${emojiEstado} ${labelEstado} (${t.estado})`} />
              <Info l="Modalidad" v={ENTREGA_LABEL[t.tipo_entrega]} />
              {t.tipo_entrega === 'delivery' && !!t.direccion_envio && <Info l="Entrega en" v={t.direccion_envio} />}
              {t.tipo_entrega === 'salon' && !!t.numero_mesa && <Info l="Mesa" v={`#${t.numero_mesa}`} />}
              <Info l="Sucursal" v={t.sucursal} />
              {!!t.cocinero && <Info l="Preparado por" v={`👨‍🍳 ${t.cocinero}`} />}
              {!!t.mesero && <Info l="Mesero asignado" v={`🤵 ${t.mesero}`} />}
              {!!t.repartidor && <Info l="Repartidor" v={`🛵 ${t.repartidor}${t.vehiculo ? ` · ${t.vehiculo}` : ''}`} />}
              <Info l="Pago" v={`${t.metodo_pago.toUpperCase()} · ${t.estado_pago}`} />
            </View>

            {pedido && (
              <View style={s.card}>
                <Text style={s.cardTitle}>Productos de tu pedido {formatNumPedido(pedido.idpedido)}</Text>
                {pedido.items.map((it, i) => (
                  <View key={i} style={s.item}>
                    <Text style={s.itemQty}>{it.cantidad}×</Text>
                    <View style={{ flex: 1 }}>
                      <Text style={s.itemName}>{it.descripcion}</Text>
                      {!!it.modificadores && <Text style={s.itemMod}>{it.modificadores}</Text>}
                    </View>
                    <Text style={s.itemPrice}>{money(it.subtotal)}</Text>
                  </View>
                ))}
                {Number(pedido.descuento) > 0 && <Info l="Descuentos" v={`− ${money(pedido.descuento)}`} />}
                {Number(pedido.costo_envio) > 0 && <Info l="Envío" v={money(pedido.costo_envio)} />}
                {Number(pedido.propina) > 0 && <Info l="Propina" v={money(pedido.propina)} />}
                <View style={s.totalRow}><Text style={s.totalL}>Total</Text><Text style={s.totalV}>{money(pedido.total)}</Text></View>
              </View>
            )}
          </>
        )}
        <View style={{ flexDirection: 'row', gap: 10, marginTop: 4 }}>
          <TouchableOpacity style={[s.btn, { flex: 1, backgroundColor: C.white, borderWidth: 1.5, borderColor: C.rojo }]} onPress={volver}>
            <Text style={[s.btnTxt, { color: C.rojo }]}>Ver mis pedidos</Text>
          </TouchableOpacity>
          <TouchableOpacity style={[s.btn, { flex: 1 }]} onPress={() => router.replace('/menu')}>
            <Text style={s.btnTxt}>Seguir pidiendo</Text>
          </TouchableOpacity>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

const Info = ({ l, v }: { l: string; v: string }) => (
  <View style={{ flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 5 }}>
    <Text style={{ color: C.textMuted, fontSize: 13, fontWeight: '600' }}>{l}</Text>
    <Text style={{ color: C.textDark, fontSize: 13, fontWeight: '800', flex: 1, textAlign: 'right', marginLeft: 16 }}>{v}</Text>
  </View>
);

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 14, paddingVertical: 12 },
  back: { color: C.white, fontSize: 34, fontWeight: '300', marginRight: 12, marginTop: -6 },
  title: { color: C.white, fontSize: 19, fontWeight: '900' },
  headerSub: { color: C.amarillo, fontSize: 12, fontWeight: '800', marginTop: 1 },
  refreshBtn: { backgroundColor: 'rgba(255,255,255,0.18)', paddingHorizontal: 12, paddingVertical: 7, borderRadius: 10 },
  refreshTxt: { color: C.white, fontSize: 12, fontWeight: '800' },
  ok: { backgroundColor: C.greenBg, padding: 12, borderRadius: 14, marginBottom: 12, borderWidth: 1, borderColor: '#86EFAC' },
  okTxt: { color: '#047857', fontWeight: '800', textAlign: 'center', fontSize: 13 },
  err: { backgroundColor: '#FEE2E2', padding: 14, borderRadius: 14 },
  heroOrderCard: { backgroundColor: C.white, borderRadius: 20, padding: 16, marginBottom: 12, borderWidth: 1.5, borderColor: C.border, elevation: 3, shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 8, shadowOffset: { width: 0, height: 2 } },
  heroTopRow: { flexDirection: 'row', alignItems: 'center' },
  orderNumberBox: { backgroundColor: C.dark, borderRadius: 16, paddingVertical: 12, paddingHorizontal: 16, alignItems: 'center', justifyContent: 'center', minWidth: 118, borderWidth: 2, borderColor: C.amarillo },
  orderNumberLabel: { color: C.amarillo, fontSize: 10, fontWeight: '900', letterSpacing: 1 },
  orderNumberValue: { color: C.white, fontSize: 28, fontWeight: '900', marginTop: 2 },
  orderUserSub: { color: '#CBD5E1', fontSize: 11, fontWeight: '700', marginTop: 2 },
  statusSectionLabel: { color: C.textMuted, fontSize: 10, fontWeight: '900', letterSpacing: 0.8, marginBottom: 5 },
  statusBigBadge: { flexDirection: 'row', alignItems: 'center', alignSelf: 'flex-start', paddingHorizontal: 12, paddingVertical: 7, borderRadius: 12, borderWidth: 1.5 },
  statusBigEmoji: { fontSize: 16, marginRight: 6 },
  statusBigTxt: { fontSize: 13, fontWeight: '900', letterSpacing: 0.5 },
  rawStateHint: { fontSize: 12, color: C.textMuted, fontWeight: '700', marginTop: 6 },
  heroDivider: { height: 1, backgroundColor: C.borderLight, marginVertical: 14 },
  card: { backgroundColor: C.white, borderRadius: 18, padding: 16, marginBottom: 12, borderWidth: 1, borderColor: C.border },
  big: { fontSize: 19, fontWeight: '900', color: C.textDark },
  sub: { fontSize: 13.5, color: C.textMuted, marginTop: 4, lineHeight: 19, marginBottom: 8 },
  timeRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 8 },
  timePill: { backgroundColor: C.lightBg, borderRadius: 10, paddingHorizontal: 10, paddingVertical: 6, borderWidth: 1, borderColor: C.border },
  timePillLbl: { fontSize: 10.5, color: C.textMuted, fontWeight: '700' },
  timePillVal: { fontSize: 12, color: C.textDark, fontWeight: '900', marginTop: 1 },
  liveBanner: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', backgroundColor: '#F0FDF4', paddingVertical: 7, borderRadius: 10, marginTop: 12 },
  liveDot: { color: C.green, fontSize: 12, fontWeight: '900', marginRight: 6 },
  live: { color: '#047857', fontSize: 12, fontWeight: '800' },
  cardTitle: { fontSize: 15, fontWeight: '900', color: C.textDark, marginBottom: 8 },
  item: { flexDirection: 'row', alignItems: 'flex-start', paddingVertical: 6 },
  itemQty: { width: 34, fontWeight: '900', color: C.rojo },
  itemName: { fontWeight: '700', color: C.textDark, fontSize: 14 },
  itemMod: { fontSize: 12, color: C.textMuted, marginTop: 1 },
  itemPrice: { fontWeight: '800', color: C.textDark, fontSize: 14 },
  totalRow: { flexDirection: 'row', justifyContent: 'space-between', borderTopWidth: 1, borderTopColor: C.border, marginTop: 8, paddingTop: 10 },
  totalL: { fontWeight: '900', fontSize: 16, color: C.textDark },
  totalV: { fontWeight: '900', fontSize: 18, color: C.rojo },
  btn: { backgroundColor: C.amarillo, height: 50, borderRadius: 14, alignItems: 'center', justifyContent: 'center' },
  btnTxt: { fontWeight: '900', color: C.dark, fontSize: 15 },
});
