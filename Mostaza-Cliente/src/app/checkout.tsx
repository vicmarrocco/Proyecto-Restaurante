import React, { useEffect, useMemo, useRef, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  AppState,
  Image,
  KeyboardAvoidingView,
  Linking,
  Modal,
  Platform,
  ScrollView,
  StyleSheet,
  Switch,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import { SafeAreaView, useSafeAreaInsets } from 'react-native-safe-area-context';
import { useRouter } from 'expo-router';
import { MostazaColors as C } from '@/constants/Colors';
import { ApiService } from '@/services/api';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { ConfigPago, Cupon, MetodoPago, Mesa, PreferenciaMP, Sucursal, TipoEntrega } from '@/types/cliente';
import LoginScreen from '@/components/LoginScreen';
import { money } from '@/utils/format';

const ENTREGAS: { id: TipoEntrega; label: string; emoji: string; sub: string }[] = [
  { id: 'delivery', label: 'Delivery', emoji: '🛵', sub: 'A domicilio' },
  { id: 'takeaway', label: 'Retiro', emoji: '🛍️', sub: 'En mostrador' },
  { id: 'salon', label: 'Salón', emoji: '🍽️', sub: 'En tu mesa' },
];

const PAGOS: { id: MetodoPago; label: string; sub: string; emoji: string }[] = [
  { id: 'efectivo', label: 'Efectivo', sub: 'Pagás al recibir el pedido o en caja', emoji: '💵' },
  { id: 'mercadopago', label: 'Mercado Pago', sub: 'Checkout Pro · QR · Dinero en cuenta', emoji: '💙' },
];

const PROPINAS = [0, 5, 10, 15];

export default function CheckoutScreen() {
  const router = useRouter();
  const { usuario, refresh, setUsuario } = useAuth();
  const { items, subtotal, clear } = useCart();
  const insets = useSafeAreaInsets();
  const bottomSafe = Platform.OS === 'web' ? 0 : Math.max(insets.bottom, Platform.OS === 'android' ? 44 : 16);

  const [showLoginModal, setShowLoginModal] = useState(false);
  const [nombreCliente, setNombreCliente] = useState('Cliente Mostaza');
  const [tipo, setTipo] = useState<TipoEntrega>('delivery');
  const [direccion, setDireccion] = useState('');
  const [telefono, setTelefono] = useState('');
  const [referencia, setReferencia] = useState('');
  const [sucursales, setSucursales] = useState<Sucursal[]>([]);
  const [sucursal, setSucursal] = useState('Mostaza Formosa Centro');
  const [horario, setHorario] = useState('');
  const [mesas, setMesas] = useState<Mesa[]>([]);
  const [mesa, setMesa] = useState<number | null>(null);

  // Medios de pago (Se paga primero -> luego pasa a cocina)
  const [pago, setPago] = useState<MetodoPago>('efectivo');
  const [pagoConfirmado, setPagoConfirmado] = useState(false);
  const [pagaCon, setPagaCon] = useState('');
  const [comprobante, setComprobante] = useState('');
  const [cfg, setCfg] = useState<ConfigPago | null>(null);

  // Estado Mercado Pago
  const [mpLoading, setMpLoading] = useState(false);
  const [mpData, setMpData] = useState<PreferenciaMP | null>(null);
  const [mpQrTab, setMpQrTab] = useState<'checkout' | 'emvco'>('checkout');
  const [mpPolling, setMpPolling] = useState(false);
  const [mpVerificando, setMpVerificando] = useState(false);
  const [mpOperacion, setMpOperacion] = useState('');
  const [mpError, setMpError] = useState<string | null>(null);
  const mpIntervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  // Cupón, puntos y propina
  const [cuponInput, setCuponInput] = useState('');
  const [cupon, setCupon] = useState<{ codigo: string; descuento: number } | null>(null);
  const [cuponesDisp, setCuponesDisp] = useState<Cupon[]>([]);
  const [cuponMsg, setCuponMsg] = useState<string | null>(null);
  const [usarPuntos, setUsarPuntos] = useState(false);
  const [propinaPct, setPropinaPct] = useState(0);
  const [notas, setNotas] = useState('');
  const [busy, setBusy] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    if (!usuario) return;
    setShowLoginModal(false);
    setNombreCliente(`${usuario.nombre || ''} ${usuario.apellido || ''}`.trim() || 'Cliente Mostaza');
    setDireccion((prev) => prev || usuario.direccion_favorita || usuario.direccion || '');
    setTelefono((prev) => prev || usuario.telefono_favorito || usuario.telefono || '');
  }, [usuario]);

  useEffect(() => {
    ApiService.sucursales().then((r) => {
      if (r.ok && r.sucursales) {
        setSucursales(r.sucursales);
        if (r.sucursales[0]) setSucursal(r.sucursales[0].nombre);
      }
    });
    ApiService.configPago().then((r) => {
      if (r.ok) setCfg(r as any);
    });
    ApiService.cupones().then((r) => {
      if (r.ok && r.cupones) setCuponesDisp(r.cupones);
    });
    return () => {
      if (mpIntervalRef.current) clearInterval(mpIntervalRef.current);
    };
  }, []);

  useEffect(() => {
    if (tipo === 'salon') {
      ApiService.mesas().then((r) => {
        if (r.ok && r.mesas) {
          setMesas(r.mesas);
          setMesa((prev) => {
            if (prev) return prev;
            const libre = r.mesas.find((m) => m.estado === 'disponible');
            return libre ? libre.numero_mesa : (r.mesas[0]?.numero_mesa ?? 1);
          });
        }
      });
    }
  }, [tipo]);

  // Revalidar cupón si cambia el subtotal
  useEffect(() => {
    if (!cupon) return;
    ApiService.validarCupon(cupon.codigo, subtotal).then((r) => {
      if (r.ok) setCupon({ codigo: r.codigo, descuento: r.descuento });
      else {
        setCupon(null);
        setCuponMsg(r.error || null);
      }
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [subtotal]);

  const aplicarCupon = async (codigo?: string) => {
    const cod = (codigo ?? cuponInput).trim().toUpperCase();
    if (!cod) return;
    const r = await ApiService.validarCupon(cod, subtotal);
    if (r.ok) {
      setCupon({ codigo: r.codigo, descuento: r.descuento });
      setCuponMsg(`✅ ${r.descripcion || 'Cupón aplicado'}`);
      setCuponInput('');
    } else {
      setCupon(null);
      setCuponMsg(`⚠️ ${r.error}`);
    }
  };

  const envioGratis = cfg?.envio_gratis_desde ?? 25000;
  const envioCosto = cfg?.envio_costo ?? 1200;
  const calc = useMemo(() => {
    const desc = cupon?.descuento || 0;
    const puntos = usarPuntos && usuario ? Math.min(usuario.puntos, Math.floor((subtotal - desc) * 0.5)) : 0;
    const envio = tipo === 'delivery' && subtotal > 0 && subtotal < envioGratis ? envioCosto : 0;
    const propina = (subtotal * propinaPct) / 100;
    const total = Math.max(0, subtotal - desc - puntos + envio + propina);
    return { desc, puntos, envio, propina, total };
  }, [cupon, usarPuntos, usuario, subtotal, tipo, propinaPct, envioGratis, envioCosto]);

  // Pre-cargar monto exacto cuando cambia el total
  useEffect(() => {
    if (calc.total > 0) {
      setPagaCon(String(Math.ceil(calc.total)));
    }
  }, [calc.total]);

  // Generar preferencia de Mercado Pago cuando se selecciona Mercado Pago
  const generarOrdenMp = async () => {
    if (calc.total <= 0) return;
    if (mpIntervalRef.current) clearInterval(mpIntervalRef.current);
    setMpLoading(true);
    setMpError(null);
    setMpData(null);
    const r = await ApiService.crearPreferenciaMP({
      items: items.map((i) => ({
        idproducto: i.idproducto,
        nombre: i.nombre,
        precio_unitario: i.precio,
        cantidad: i.cantidad,
      })),
      total: calc.total,
      tipo_entrega: tipo,
      numero_mesa: mesa,
    });
    setMpLoading(false);
    if (r.ok) {
      setMpData(r);
      if (r.modo_real && r.external_reference) {
        iniciarPollingMp(r.external_reference, calc.total);
      }
    } else {
      setMpError(r.error || 'No pudimos generar la orden de Mercado Pago. Probá de nuevo o elegí Efectivo.');
    }
  };

  useEffect(() => {
    setPagoConfirmado(false);
    setErrorMsg(null);
    if (pago === 'mercadopago') {
      generarOrdenMp();
    } else {
      if (mpIntervalRef.current) clearInterval(mpIntervalRef.current);
      setMpPolling(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pago, calc.total]);

  const iniciarPollingMp = (extRef: string, monto: number) => {
    if (mpIntervalRef.current) clearInterval(mpIntervalRef.current);
    setMpPolling(true);
    mpIntervalRef.current = setInterval(async () => {
      const v = await ApiService.verificarPagoMP({ external_reference: extRef, monto_esperado: monto });
      if (v.ok && v.pagado) {
        if (mpIntervalRef.current) clearInterval(mpIntervalRef.current);
        setMpPolling(false);
        const opId = String(v.payment_id || `MP-${Math.floor(100000000 + Math.random() * 900000000)}`);
        setComprobante(opId);
        setPagoConfirmado(true);
      }
    }, 4500);
  };

  const abrirCheckoutMp = async () => {
    const url = mpData?.init_point || cfg?.link_mp || 'https://link.mercadopago.com.ar/mostaza';
    try {
      if (Platform.OS === 'web' && typeof window !== 'undefined') {
        window.open(url, '_blank');
      } else {
        await Linking.openURL(url);
      }
    } catch {
      mostrarError('No se pudo abrir el navegador de Mercado Pago.');
    }
  };

  // Consulta a Mercado Pago si el pago ya está acreditado. Nunca da por pagado algo que MP no confirma.
  const verificarPagoMpAhora = async (manual: boolean) => {
    if (!mpData?.external_reference) {
      if (manual) mostrarError('Primero necesitamos generar la orden de Mercado Pago. Tocá "Generar QR de pago".');
      return false;
    }
    if (manual) setMpVerificando(true);
    const v = await ApiService.verificarPagoMP({
      external_reference: mpData.external_reference,
      monto_esperado: calc.total,
      payment_id: mpOperacion.trim() || undefined,
    });
    if (manual) setMpVerificando(false);
    if (v.ok && v.pagado) {
      if (mpIntervalRef.current) clearInterval(mpIntervalRef.current);
      setMpPolling(false);
      setComprobante(String(v.payment_id || mpOperacion.trim() || mpData.external_reference));
      setPagoConfirmado(true);
      setErrorMsg(null);
      return true;
    }
    if (manual) {
      mostrarError('Todavía no vemos el pago acreditado en Mercado Pago. Si ya pagaste, esperá unos segundos y probá de nuevo.');
    }
    return false;
  };

  // Solo cuando Mercado Pago está en modo demostración (sin credenciales reales), igual que la web.
  const confirmarPagoMpDemo = () => {
    const op = mpOperacion.trim() || `MP-${Math.floor(100000000 + Math.random() * 900000000)}`;
    setComprobante(op);
    setPagoConfirmado(true);
    setErrorMsg(null);
  };

  // Al volver de la app/navegador de Mercado Pago, revisar enseguida si el pago ya se acreditó.
  useEffect(() => {
    if (pago !== 'mercadopago' || !mpData?.modo_real || pagoConfirmado) return undefined;
    const sub = AppState.addEventListener('change', (st) => {
      if (st === 'active') verificarPagoMpAhora(false);
    });
    return () => sub.remove();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [pago, mpData, pagoConfirmado, calc.total, mpOperacion]);

  const validarEfectivo = (): boolean => {
    const monto = Number(pagaCon || calc.total);
    if (isNaN(monto) || monto < calc.total) {
      mostrarError(`El monto con el que abonás debe ser al menos ${money(calc.total)}.`);
      return false;
    }
    return true;
  };

  const mostrarError = (msg: string) => {
    setErrorMsg(msg);
    if (Platform.OS !== 'web') {
      Alert.alert('Atención', msg);
    }
  };

  if (items.length === 0) {
    return (
      <SafeAreaView style={s.safe} edges={['top']}>
        <View style={s.header}>
          <TouchableOpacity onPress={() => router.replace('/carrito')}>
            <Text style={s.back}>‹</Text>
          </TouchableOpacity>
          <Text style={s.title}>Pagar y Enviar a Cocina</Text>
        </View>
        <View style={s.emptyBox}>
          <Text style={{ fontSize: 50 }}>🛒</Text>
          <Text style={s.emptyTxt}>Tu carrito está vacío</Text>
          <TouchableOpacity style={s.backMenuBtn} onPress={() => router.replace('/menu')}>
            <Text style={{ color: C.white, fontWeight: '900' }}>Ir al menú</Text>
          </TouchableOpacity>
        </View>
      </SafeAreaView>
    );
  }

  const pagarYEnviarACocina = async () => {
    setErrorMsg(null);

    // 1. Validar datos de entrega
    if (tipo === 'delivery' && !direccion.trim()) {
      return mostrarError('Indicá la dirección de entrega (o elegí Retiro / Salón).');
    }
    if (tipo === 'delivery' && !telefono.trim()) {
      return mostrarError('Indicá un teléfono de contacto para el repartidor.');
    }
    if (tipo === 'salon' && !mesa) {
      return mostrarError('Seleccioná tu número de mesa en el salón.');
    }

    // 2. Medio de pago
    let comprobanteFinal = '';
    if (pago === 'efectivo') {
      // Efectivo: se cobra al entregar / en caja (igual que la web). No hay nada que acreditar ahora.
      if (!validarEfectivo()) return;
    } else {
      // Mercado Pago: el pedido solo se crea cuando el pago está acreditado.
      if (!pagoConfirmado) {
        if (mpLoading) return mostrarError('Estamos generando tu orden de Mercado Pago, un segundo…');
        if (!mpData) {
          await generarOrdenMp();
          return mostrarError('Generamos tu orden de Mercado Pago. Tocá de nuevo para abrirla y pagar.');
        }
        if (mpData.modo_real === false) {
          return mostrarError('Mercado Pago está en modo demostración. Tocá "Confirmar pago (demo)" o elegí Efectivo.');
        }
        if (await verificarPagoMpAhora(false)) return;
        await abrirCheckoutMp();
        return mostrarError('Completá el pago en Mercado Pago. Apenas se acredite, volvé acá y tocá "Finalizar pedido".');
      }
      comprobanteFinal = comprobante.trim();
    }

    // 3. Crear el pedido
    setBusy(true);
    const r = await ApiService.crearPedido({
      idusuario: usuario?.idusuario,
      nombre_cliente: usuario ? `${usuario.nombre} ${usuario.apellido}` : nombreCliente.trim() || 'Cliente Mostaza',
      email_cliente: usuario?.email || '',
      tipo_entrega: tipo,
      direccion_envio: direccion.trim(),
      telefono_contacto: telefono.trim() || usuario?.telefono || '3704000000',
      referencia_entrega: referencia.trim(),
      sucursal: tipo === 'delivery' ? '' : sucursal,
      horario_retiro: horario.trim(),
      numero_mesa: mesa,
      notas: notas.trim(),
      metodo_pago: pago,
      paga_con: pago === 'efectivo' ? Number(pagaCon || calc.total) : undefined,
      comprobante: comprobanteFinal,
      external_reference: pago === 'mercadopago' ? mpData?.external_reference : undefined,
      codigo_cupon: cupon?.codigo || '',
      usar_puntos: usarPuntos,
      propina_tipo: 'porcentaje',
      propina_valor: propinaPct,
      items: items.map((i) => ({
        idproducto: i.idproducto,
        cantidad: i.cantidad,
        tamano: i.tamano || '',
        notas: i.notas || '',
      })),
    });
    setBusy(false);

    if (!r.ok) {
      return mostrarError(r.error || 'No pudimos procesar tu pago y pedido. Intentá nuevamente.');
    }

    if (mpIntervalRef.current) clearInterval(mpIntervalRef.current);
    if (r.token) await ApiService.setToken(r.token);
    if (r.usuario) setUsuario(r.usuario);

    clear();
    refresh();
    router.replace({ pathname: '/pedido/[id]', params: { id: String(r.idpedido), nuevo: '1' } });
  };

  const vueltoEfectivo = Math.max(0, Number(pagaCon || calc.total) - calc.total);
  const qrUrlActual = mpQrTab === 'checkout' ? mpData?.qr_checkout_url : mpData?.qr_emvco_url;

  return (
    <SafeAreaView style={s.safe} edges={['top']}>
      <View style={s.header}>
        <TouchableOpacity onPress={() => router.back()} hitSlop={12}>
          <Text style={s.back}>‹</Text>
        </TouchableOpacity>
        <View style={{ flex: 1 }}>
          <Text style={s.title}>Pagar y Enviar a Cocina</Text>
          <Text style={s.headerSub}>Efectivo al recibir o Mercado Pago</Text>
        </View>
      </View>

      <Modal visible={showLoginModal} animationType="slide" onRequestClose={() => setShowLoginModal(false)}>
        <View style={{ flex: 1, backgroundColor: C.rojo }}>
          <View style={{ flexDirection: 'row', justifyContent: 'flex-end', paddingHorizontal: 16, paddingTop: 14 }}>
            <TouchableOpacity onPress={() => setShowLoginModal(false)} style={s.closeModalBtn}>
              <Text style={{ color: C.white, fontWeight: '900', fontSize: 13 }}>✕ Continuar sin iniciar sesión</Text>
            </TouchableOpacity>
          </View>
          <LoginScreen motivo="Iniciá sesión para sumar o canjear puntos Mostaza" />
        </View>
      </Modal>

      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <ScrollView
          style={{ backgroundColor: C.lightBg }}
          contentContainerStyle={{ padding: 14, paddingBottom: 155 + bottomSafe }}
          keyboardShouldPersistTaps="handled">
          {/* Banner de flujo Pago Previo -> Cocina */}
          <View style={s.flowBanner}>
            <View style={s.flowStep}>
              <View style={[s.flowDot, { backgroundColor: C.amarillo }]}>
                <Text style={s.flowDotTxt}>1</Text>
              </View>
              <Text style={s.flowTxt}>Elegís cómo pagar</Text>
            </View>
            <Text style={s.flowArrow}>➔</Text>
            <View style={s.flowStep}>
              <View style={[s.flowDot, { backgroundColor: '#10B981' }]}>
                <Text style={[s.flowDotTxt, { color: C.white }]}>2</Text>
              </View>
              <Text style={s.flowTxt}>Pasa a Cocina</Text>
            </View>
            <Text style={s.flowArrow}>➔</Text>
            <View style={s.flowStep}>
              <View style={[s.flowDot, { backgroundColor: C.white }]}>
                <Text style={s.flowDotTxt}>3</Text>
              </View>
              <Text style={s.flowTxt}>Seguimiento en vivo</Text>
            </View>
          </View>

          {/* Identificación del cliente */}
          {!usuario ? (
            <View style={[s.card, { marginBottom: 4, borderColor: '#FCD34D', backgroundColor: '#FFFBEB' }]}>
              <View style={{ flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 }}>
                <Text style={{ fontWeight: '900', color: '#92400E', fontSize: 13 }}>👤 Datos del comprador</Text>
                <TouchableOpacity onPress={() => setShowLoginModal(true)}>
                  <Text style={{ color: C.rojo, fontWeight: '900', fontSize: 12 }}>¿Tenés cuenta? Ingresar →</Text>
                </TouchableOpacity>
              </View>
              <TextInput
                style={[s.input, { marginBottom: 0, backgroundColor: C.white }]}
                placeholder="Tu nombre (ej: Juan Pérez)"
                value={nombreCliente}
                onChangeText={setNombreCliente}
                placeholderTextColor={C.textMuted}
              />
            </View>
          ) : (
            <View style={[s.card, { paddingVertical: 10, marginBottom: 4, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }]}>
              <Text style={{ fontSize: 13, fontWeight: '800', color: C.textDark }}>
                👤 {usuario.nombre} {usuario.apellido}
              </Text>
              <Text style={{ fontSize: 12, fontWeight: '800', color: '#047857' }}>⭐ {usuario.puntos} pts disponibles</Text>
            </View>
          )}

          {/* PASO 1:MODALIDAD DE ENTREGA */}
          <Text style={s.label}>1. ¿CÓMO QUERÉS RECIBIR TU PEDIDO?</Text>
          <View style={s.seg}>
            {ENTREGAS.map((e) => (
              <TouchableOpacity
                key={e.id}
                style={[s.segBtn, tipo === e.id && s.segOn]}
                onPress={() => {
                  setTipo(e.id);
                  setErrorMsg(null);
                }}>
                <Text style={{ fontSize: 22 }}>{e.emoji}</Text>
                <Text style={[s.segTxt, tipo === e.id && { color: C.white }]}>{e.label}</Text>
                <Text style={[s.segSub, tipo === e.id && { color: '#FEE2E2' }]}>{e.sub}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {tipo === 'delivery' && (
            <View style={[s.card, { marginTop: 10 }]}>
              <Text style={s.fieldLabel}>📍 Dirección de envío *</Text>
              <TextInput
                style={s.input}
                placeholder="Ej: Av. 25 de Mayo 612, Formosa"
                value={direccion}
                onChangeText={(v) => {
                  setDireccion(v);
                  setErrorMsg(null);
                }}
                placeholderTextColor={C.textMuted}
              />
              {!direccion.trim() && (
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 10 }}>
                  {['Av. 25 de Mayo 612, Formosa', 'Av. González Lelong 450, Formosa', 'Belgrano 820, Formosa'].map((dirSug) => (
                    <TouchableOpacity
                      key={dirSug}
                      style={s.quickChip}
                      onPress={() => {
                        setDireccion(dirSug);
                        if (!telefono.trim()) setTelefono('3704556677');
                        setErrorMsg(null);
                      }}>
                      <Text style={s.quickChipTxt}>📍 {dirSug}</Text>
                    </TouchableOpacity>
                  ))}
                </ScrollView>
              )}
              <Text style={s.fieldLabel}>📞 Teléfono de contacto *</Text>
              <TextInput
                style={s.input}
                placeholder="Ej: 3704556677"
                keyboardType="phone-pad"
                value={telefono}
                onChangeText={(v) => {
                  setTelefono(v);
                  setErrorMsg(null);
                }}
                placeholderTextColor={C.textMuted}
              />
              <TextInput
                style={[s.input, { marginBottom: 0 }]}
                placeholder="Referencia opcional (portón negro, piso 2…)"
                value={referencia}
                onChangeText={setReferencia}
                placeholderTextColor={C.textMuted}
              />
            </View>
          )}

          {tipo === 'takeaway' && (
            <View style={[s.card, { marginTop: 10 }]}>
              <Text style={[s.optSub, { marginBottom: 8 }]}>Retirá en mostrador con tu número de orden apenas esté listo:</Text>
              {sucursales.map((su) => (
                <TouchableOpacity key={su.idsucursal} style={[s.opt, sucursal === su.nombre && s.optOn]} onPress={() => setSucursal(su.nombre)}>
                  <View style={{ flex: 1 }}>
                    <Text style={s.optName}>{su.nombre}</Text>
                    <Text style={s.optSub}>
                      {su.direccion} · {su.horario_atencion}
                    </Text>
                  </View>
                  {sucursal === su.nombre && <Text style={{ color: C.rojo, fontWeight: '900', fontSize: 16 }}>✓</Text>}
                </TouchableOpacity>
              ))}
              <TextInput
                style={[s.input, { marginBottom: 0 }]}
                placeholder="Horario de retiro opcional (ej: Lo antes posible / 21:30)"
                value={horario}
                onChangeText={setHorario}
                placeholderTextColor={C.textMuted}
              />
            </View>
          )}

          {tipo === 'salon' && (
            <View style={[s.card, { marginTop: 10 }]}>
              <Text style={s.optSub}>Elegí tu mesa libre. Una vez abonado, cocina lo prepara y el mesero te lo lleva a la mesa.</Text>
              <View style={s.mesas}>
                {mesas.map((m) => {
                  const libre = m.estado === 'disponible';
                  const on = mesa === m.numero_mesa;
                  return (
                    <TouchableOpacity
                      key={m.numero_mesa}
                      disabled={!libre && !on}
                      style={[s.mesa, !libre && !on && s.mesaOff, on && s.mesaOn]}
                      onPress={() => {
                        setMesa(m.numero_mesa);
                        setErrorMsg(null);
                      }}>
                      <Text style={[s.mesaNum, on && { color: C.white }]}>#{m.numero_mesa}</Text>
                      <Text style={[s.mesaCap, on && { color: C.white }]}>👥 {m.capacidad}</Text>
                    </TouchableOpacity>
                  );
                })}
              </View>
              <TextInput
                style={[s.input, { marginBottom: 0 }]}
                placeholder="Nota para cocina o el mozo (opcional)"
                value={notas}
                onChangeText={setNotas}
                placeholderTextColor={C.textMuted}
              />
            </View>
          )}

          {/* PASO 2: PAGO PREVIO OBLIGATORIO ANTES DE COCINA */}
          <Text style={s.label}>2. MEDIO DE PAGO</Text>
          <View style={s.card}>
            {PAGOS.map((p) => {
              const active = pago === p.id;
              return (
                <TouchableOpacity
                  key={p.id}
                  style={[s.opt, active && s.optOn]}
                  onPress={() => {
                    setPago(p.id);
                    setErrorMsg(null);
                  }}>
                  <Text style={{ fontSize: 24, marginRight: 12 }}>{p.emoji}</Text>
                  <View style={{ flex: 1 }}>
                    <Text style={s.optName}>{p.label}</Text>
                    <Text style={s.optSub}>{p.sub}</Text>
                  </View>
                  <View style={[s.radio, active && s.radioOn]}>{active && <View style={s.radioInner} />}</View>
                </TouchableOpacity>
              );
            })}

            {/* DETALLE: MERCADO PAGO */}
            {pago === 'mercadopago' && (
              <View style={s.mpBox}>
                <View style={s.mpHeader}>
                  <View style={{ flex: 1, paddingRight: 8 }}>
                    <Text style={s.mpTitle}>💙 Pagá con Mercado Pago</Text>
                    <Text style={s.mpSub}>Escaneá el QR o abrí Mercado Pago. Cuando se acredite, finalizás el pedido.</Text>
                  </View>
                  <View style={s.mpBadge}>
                    <Text style={s.mpBadgeTxt}>{money(calc.total)}</Text>
                  </View>
                </View>

                <View style={s.mpTabs}>
                  <TouchableOpacity style={[s.mpTab, mpQrTab === 'checkout' && s.mpTabOn]} onPress={() => setMpQrTab('checkout')}>
                    <Text style={[s.mpTabTxt, mpQrTab === 'checkout' && { color: C.white }]}>QR Orden MP</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={[s.mpTab, mpQrTab === 'emvco' && s.mpTabOn]} onPress={() => setMpQrTab('emvco')}>
                    <Text style={[s.mpTabTxt, mpQrTab === 'emvco' && { color: C.white }]}>QR Transf. 3.0</Text>
                  </TouchableOpacity>
                </View>

                <View style={s.qrWrapper}>
                  {mpLoading ? (
                    <View style={s.qrPlaceholder}>
                      <ActivityIndicator size="large" color="#009EE3" />
                      <Text style={{ fontSize: 12, color: C.textMuted, marginTop: 8, fontWeight: '700' }}>Generando orden en Mercado Pago...</Text>
                    </View>
                  ) : qrUrlActual ? (
                    <Image source={{ uri: qrUrlActual }} style={s.qrImg} resizeMode="contain" />
                  ) : (
                    <TouchableOpacity style={s.qrPlaceholder} onPress={generarOrdenMp}>
                      <Text style={{ fontSize: 28 }}>🔄</Text>
                      <Text style={{ fontSize: 12, color: '#009EE3', fontWeight: '800', marginTop: 6 }}>Generar QR de pago</Text>
                    </TouchableOpacity>
                  )}
                </View>

                {!!mpError && <Text style={s.mpErrTxt}>⚠️ {mpError}</Text>}

                {mpPolling && !pagoConfirmado && (
                  <View style={s.pollingRow}>
                    <ActivityIndicator size="small" color="#009EE3" />
                    <Text style={s.pollingTxt}>Esperando confirmación automática de Mercado Pago...</Text>
                  </View>
                )}

                {!pagoConfirmado && (
                  <TouchableOpacity style={s.mpOpenBtn} onPress={abrirCheckoutMp} activeOpacity={0.85}>
                    <Text style={s.mpOpenBtnTxt}>🌐 Abrir Mercado Pago · Pagar {money(calc.total)}</Text>
                  </TouchableOpacity>
                )}

                {!pagoConfirmado ? (
                  <>
                    <TextInput
                      style={[s.input, { marginTop: 10, marginBottom: 0, backgroundColor: C.white }]}
                      placeholder="N° de operación de Mercado Pago (opcional)"
                      keyboardType="number-pad"
                      value={mpOperacion}
                      onChangeText={setMpOperacion}
                      placeholderTextColor={C.textMuted}
                    />
                    {mpData?.modo_real === false ? (
                      <TouchableOpacity style={s.acreditBtn} onPress={confirmarPagoMpDemo} activeOpacity={0.85}>
                        <Text style={s.acreditBtnTxt}>🧪 Confirmar pago (demo)</Text>
                      </TouchableOpacity>
                    ) : (
                      <TouchableOpacity style={[s.acreditBtn, mpVerificando && { opacity: 0.7 }]} disabled={mpVerificando} onPress={() => verificarPagoMpAhora(true)} activeOpacity={0.85}>
                        <Text style={s.acreditBtnTxt}>{mpVerificando ? 'Verificando pago…' : '✔️ Ya pagué · Verificar pago'}</Text>
                      </TouchableOpacity>
                    )}
                  </>
                ) : (
                  <View style={s.approvedBanner}>
                    <Text style={s.approvedTitle}>✅ ¡PAGO ACREDITADO EN MERCADO PAGO!</Text>
                    <Text style={s.approvedSub}>Operación #{comprobante} · Ya podés finalizar tu pedido</Text>
                  </View>
                )}
              </View>
            )}

            {/* DETALLE: EFECTIVO */}
            {pago === 'efectivo' && (
              <View style={s.cashBox}>
                <Text style={s.cardBoxTitle}>💵 Efectivo · {money(calc.total)}</Text>
                <Text style={[s.optSub, { marginBottom: 8 }]}>
                  {tipo === 'delivery' ? 'Abonás en efectivo al repartidor cuando llega tu pedido.' : tipo === 'salon' ? 'Abonás en efectivo en tu mesa o en caja.' : 'Abonás en efectivo en el mostrador al retirar.'}
                  {' '}Indicá con cuánto pagás para que lleven tu vuelto:
                </Text>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginBottom: 10 }}>
                  {[
                    { lbl: `Exacto (${money(calc.total)})`, val: Math.ceil(calc.total) },
                    { lbl: '+$1.000', val: Math.ceil(calc.total) + 1000 },
                    { lbl: '+$2.000', val: Math.ceil(calc.total) + 2000 },
                    { lbl: '+$5.000', val: Math.ceil(calc.total) + 5000 },
                    { lbl: '+$10.000', val: Math.ceil(calc.total) + 10000 },
                  ].map((b) => (
                    <TouchableOpacity
                      key={b.lbl}
                      style={[s.quickChip, Number(pagaCon) === b.val && s.quickChipActive]}
                      onPress={() => {
                        setPagaCon(String(b.val));
                        setErrorMsg(null);
                      }}>
                      <Text style={[s.quickChipTxt, Number(pagaCon) === b.val && { color: C.white }]}>{b.lbl}</Text>
                    </TouchableOpacity>
                  ))}
                </ScrollView>

                <TextInput
                  style={s.input}
                  placeholder={`¿Con cuánto abonás? (mínimo ${money(calc.total)})`}
                  keyboardType="number-pad"
                  value={pagaCon}
                  onChangeText={(v) => {
                    setPagaCon(v.replace(/\D/g, ''));
                    setErrorMsg(null);
                  }}
                  placeholderTextColor={C.textMuted}
                />

                <View style={s.vueltoBox}>
                  <Text style={s.vueltoLabel}>Vuelto a recibir:</Text>
                  <Text style={s.vueltoVal}>{money(vueltoEfectivo)}</Text>
                </View>
              </View>
            )}
          </View>

          {/* CUPÓN Y PUNTOS */}
          <Text style={s.label}>CUPÓN DE DESCUENTO Y PUNTOS</Text>
          <View style={s.card}>
            <View style={{ flexDirection: 'row', gap: 8 }}>
              <TextInput
                style={[s.input, { flex: 1, marginBottom: 0 }]}
                placeholder="Código de cupón"
                autoCapitalize="characters"
                value={cuponInput}
                onChangeText={setCuponInput}
                placeholderTextColor={C.textMuted}
              />
              <TouchableOpacity style={s.apply} onPress={() => aplicarCupon()}>
                <Text style={s.applyTxt}>Aplicar</Text>
              </TouchableOpacity>
            </View>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} style={{ marginTop: 10 }}>
              {cuponesDisp.map((c) => (
                <TouchableOpacity key={c.codigo} style={[s.pill, cupon?.codigo === c.codigo && s.pillOn]} onPress={() => aplicarCupon(c.codigo)}>
                  <Text style={[s.pillTxt, cupon?.codigo === c.codigo && { color: C.white }]}>{c.codigo}</Text>
                </TouchableOpacity>
              ))}
            </ScrollView>
            {!!cuponMsg && <Text style={s.cuponMsg}>{cuponMsg}</Text>}
            {usuario && usuario.puntos > 0 && (
              <View style={s.puntosRow}>
                <View style={{ flex: 1 }}>
                  <Text style={s.optName}>⭐ Usar mis puntos Mostaza</Text>
                  <Text style={s.optSub}>Tenés {usuario.puntos} puntos (1 punto = $1, hasta el 50%)</Text>
                </View>
                <Switch value={usarPuntos} onValueChange={setUsarPuntos} trackColor={{ true: C.amarillo }} thumbColor={C.white} />
              </View>
            )}
          </View>

          {/* PROPINA */}
          <Text style={s.label}>PROPINA OPCIONAL</Text>
          <View style={s.seg}>
            {PROPINAS.map((p) => (
              <TouchableOpacity
                key={p}
                style={[s.segBtn, { paddingVertical: 10 }, propinaPct === p && s.segOn]}
                onPress={() => setPropinaPct(p)}>
                <Text style={[s.segTxt, { marginTop: 0 }, propinaPct === p && { color: C.white }]}>{p === 0 ? 'Sin propina' : `${p}%`}</Text>
              </TouchableOpacity>
            ))}
          </View>

          {/* RESUMEN DE COMPRA */}
          <Text style={s.label}>RESUMEN DEL PEDIDO</Text>
          <View style={s.card}>
            {items.map((it) => (
              <View key={it.key} style={{ flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4 }}>
                <Text style={{ fontSize: 13, color: C.textMuted, flex: 1 }} numberOfLines={1}>
                  {it.cantidad}x {it.nombre} {it.tamano ? `(${it.tamano})` : ''}
                </Text>
                <Text style={{ fontSize: 13, fontWeight: '700', color: C.textDark }}>{money(it.precio * it.cantidad)}</Text>
              </View>
            ))}
            <View style={s.divider} />
            <Row l={`Subtotal (${items.reduce((a, b) => a + b.cantidad, 0)} prod.)`} v={money(subtotal)} />
            {calc.desc > 0 && <Row l={`Cupón ${cupon?.codigo}`} v={`− ${money(calc.desc)}`} green />}
            {calc.puntos > 0 && <Row l="Puntos Mostaza" v={`− ${money(calc.puntos)}`} green />}
            {tipo === 'delivery' && <Row l="Envío a domicilio" v={calc.envio > 0 ? money(calc.envio) : '¡Gratis!'} green={calc.envio === 0} />}
            {calc.propina > 0 && <Row l={`Propina (${propinaPct}%)`} v={money(calc.propina)} />}
            <View style={s.divider} />
            <Row l="Total a Pagar" v={money(calc.total)} big />
          </View>
        </ScrollView>
      </KeyboardAvoidingView>

      {/* FOOTER FIJO DE COBRO Y ENVÍO A COCINA */}
      <View style={[s.footer, { paddingBottom: 14 + bottomSafe }]}>
        {!!errorMsg && (
          <View style={s.errorBanner}>
            <Text style={s.errorBannerTxt}>⚠️ {errorMsg}</Text>
          </View>
        )}
        <TouchableOpacity
          style={[s.confirm, (pago === 'efectivo' || pagoConfirmado) && s.confirmReady, busy && { opacity: 0.7 }]}
          onPress={pagarYEnviarACocina}
          disabled={busy}
          activeOpacity={0.9}>
          {busy ? (
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
              <ActivityIndicator color={C.white} />
              <Text style={s.confirmTxt}>Enviando tu pedido...</Text>
            </View>
          ) : (
            <>
              <View style={{ flex: 1 }}>
                <Text style={s.confirmSub}>
                  {pago === 'efectivo' ? 'PAGÁS EN EFECTIVO AL RECIBIR' : pagoConfirmado ? '✅ PAGO ACREDITADO' : 'PAGO CON MERCADO PAGO'}
                </Text>
                <Text style={s.confirmTxt}>
                  {pago === 'efectivo' ? '✅ Confirmar pedido →' : pagoConfirmado ? '✅ Finalizar pedido →' : '💙 Pagar con Mercado Pago →'}
                </Text>
              </View>
              <View style={s.confirmPriceBox}>
                <Text style={s.confirmPrice}>{money(calc.total)}</Text>
              </View>
            </>
          )}
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const Row = ({ l, v, big, green }: { l: string; v: string; big?: boolean; green?: boolean }) => (
  <View style={{ flexDirection: 'row', justifyContent: 'space-between', marginVertical: 3 }}>
    <Text style={{ fontSize: big ? 17 : 14, fontWeight: big ? '900' : '600', color: C.textDark }}>{l}</Text>
    <Text style={{ fontSize: big ? 19 : 14, fontWeight: '900', color: green ? '#047857' : big ? C.rojo : C.textDark }}>{v}</Text>
  </View>
);

const s = StyleSheet.create({
  safe: { flex: 1, backgroundColor: C.rojo },
  header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: 14, paddingVertical: 10 },
  back: { color: C.white, fontSize: 34, fontWeight: '300', marginRight: 12, marginTop: -4 },
  title: { color: C.white, fontSize: 19, fontWeight: '900' },
  headerSub: { color: C.amarillo, fontSize: 11, fontWeight: '800', marginTop: 1 },
  closeModalBtn: { backgroundColor: 'rgba(0,0,0,0.25)', paddingHorizontal: 14, paddingVertical: 8, borderRadius: 20 },
  emptyBox: { flex: 1, backgroundColor: C.lightBg, alignItems: 'center', justifyContent: 'center', padding: 24 },
  emptyTxt: { fontWeight: '800', color: C.textDark, marginTop: 8, fontSize: 16 },
  backMenuBtn: { marginTop: 16, backgroundColor: C.rojo, paddingHorizontal: 22, paddingVertical: 12, borderRadius: 12 },
  flowBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    backgroundColor: C.dark,
    borderRadius: 14,
    paddingVertical: 10,
    paddingHorizontal: 12,
    marginBottom: 12,
  },
  flowStep: { flexDirection: 'row', alignItems: 'center', gap: 6 },
  flowDot: { width: 20, height: 20, borderRadius: 10, alignItems: 'center', justifyContent: 'center' },
  flowDotTxt: { fontSize: 11, fontWeight: '900', color: C.dark },
  flowTxt: { color: C.white, fontSize: 11, fontWeight: '800' },
  flowArrow: { color: C.amarillo, fontSize: 12, fontWeight: '900' },
  label: { fontSize: 11, fontWeight: '900', color: C.textMuted, letterSpacing: 0.8, marginTop: 14, marginBottom: 8 },
  fieldLabel: { fontSize: 12, fontWeight: '800', color: C.textDark, marginBottom: 4 },
  card: { backgroundColor: C.white, borderRadius: 16, padding: 14, borderWidth: 1, borderColor: C.border },
  seg: { flexDirection: 'row', gap: 8 },
  segBtn: { flex: 1, alignItems: 'center', paddingVertical: 10, borderRadius: 14, backgroundColor: C.white, borderWidth: 1.5, borderColor: C.border },
  segOn: { backgroundColor: C.rojo, borderColor: C.rojo },
  segTxt: { fontSize: 12, fontWeight: '900', color: C.textDark, marginTop: 2 },
  segSub: { fontSize: 10, color: C.textMuted, fontWeight: '600' },
  input: { borderWidth: 1, borderColor: C.border, borderRadius: 12, padding: 12, fontSize: 14, color: C.textDark, marginBottom: 10, backgroundColor: C.lightBg },
  quickChip: {
    backgroundColor: '#FFF6DD',
    borderWidth: 1,
    borderColor: '#FCD34D',
    paddingHorizontal: 12,
    paddingVertical: 7,
    borderRadius: 12,
    marginRight: 8,
  },
  quickChipActive: { backgroundColor: C.rojo, borderColor: C.rojo },
  quickChipTxt: { fontSize: 12, fontWeight: '800', color: '#92400E' },
  opt: { flexDirection: 'row', alignItems: 'center', padding: 12, borderRadius: 12, borderWidth: 1.5, borderColor: C.border, marginBottom: 8 },
  optOn: { borderColor: C.rojo, backgroundColor: C.rojoClaro },
  optName: { fontSize: 14, fontWeight: '800', color: C.textDark },
  optSub: { fontSize: 11, color: C.textMuted, marginTop: 2 },
  radio: { width: 20, height: 20, borderRadius: 10, borderWidth: 2, borderColor: C.border, alignItems: 'center', justifyContent: 'center' },
  radioOn: { borderColor: C.rojo },
  radioInner: { width: 10, height: 10, borderRadius: 5, backgroundColor: C.rojo },
  mesas: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginVertical: 12 },
  mesa: { width: 60, height: 58, borderRadius: 12, borderWidth: 1.5, borderColor: C.green, backgroundColor: C.greenBg, alignItems: 'center', justifyContent: 'center' },
  mesaOff: { borderColor: C.border, backgroundColor: C.borderLight, opacity: 0.45 },
  mesaOn: { backgroundColor: C.rojo, borderColor: C.rojo },
  mesaNum: { fontSize: 16, fontWeight: '900', color: C.textDark },
  mesaCap: { fontSize: 10, color: C.textMuted, fontWeight: '700' },

  // Mercado Pago Box
  mpBox: { backgroundColor: '#F0F9FF', borderRadius: 14, padding: 12, borderWidth: 1.5, borderColor: '#BAE6FD', marginTop: 4 },
  mpHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  mpTitle: { fontSize: 14, fontWeight: '900', color: '#0369A1' },
  mpSub: { fontSize: 11, color: '#0284C7', marginTop: 1 },
  mpBadge: { backgroundColor: '#0284C7', paddingHorizontal: 10, paddingVertical: 5, borderRadius: 10 },
  mpBadgeTxt: { color: C.white, fontWeight: '900', fontSize: 13 },
  mpTabs: { flexDirection: 'row', backgroundColor: '#E0F2FE', borderRadius: 10, padding: 3, marginBottom: 10 },
  mpTab: { flex: 1, paddingVertical: 6, alignItems: 'center', borderRadius: 8 },
  mpTabOn: { backgroundColor: '#0284C7' },
  mpTabTxt: { fontSize: 12, fontWeight: '800', color: '#0369A1' },
  qrWrapper: { alignItems: 'center', justifyContent: 'center', backgroundColor: C.white, borderRadius: 14, padding: 12, marginBottom: 10, borderWidth: 1, borderColor: '#E0F2FE' },
  qrImg: { width: 175, height: 175 },
  qrPlaceholder: { width: 175, height: 175, alignItems: 'center', justifyContent: 'center' },
  mpErrTxt: { color: '#B91C1C', fontWeight: '700', fontSize: 12, marginTop: 8, textAlign: 'center' },
  pollingRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: 8, marginBottom: 10 },
  pollingTxt: { fontSize: 11, color: '#0369A1', fontWeight: '700' },
  mpOpenBtn: { backgroundColor: '#009EE3', borderRadius: 12, paddingVertical: 12, alignItems: 'center', marginBottom: 8 },
  mpOpenBtnTxt: { color: C.white, fontWeight: '900', fontSize: 13 },
  acreditBtn: { backgroundColor: '#059669', borderRadius: 12, paddingVertical: 12, alignItems: 'center' },
  acreditBtnTxt: { color: C.white, fontWeight: '900', fontSize: 13 },
  approvedBanner: { backgroundColor: '#DCFCE7', borderWidth: 1.5, borderColor: '#10B981', borderRadius: 12, padding: 12, alignItems: 'center' },
  approvedTitle: { color: '#065F46', fontWeight: '900', fontSize: 13 },
  approvedSub: { color: '#047857', fontWeight: '700', fontSize: 11, marginTop: 2 },

  // Tarjeta Box
  cardBox: { backgroundColor: '#F9FAFB', borderRadius: 14, padding: 12, borderWidth: 1, borderColor: C.border, marginTop: 4 },
  cardBoxTitle: { fontSize: 14, fontWeight: '900', color: C.textDark },
  testCardBtn: { backgroundColor: '#EEF2FF', borderWidth: 1, borderColor: '#C7D2FE', paddingHorizontal: 8, paddingVertical: 5, borderRadius: 8 },
  testCardTxt: { fontSize: 11, fontWeight: '800', color: '#4338CA' },
  cuotaChip: { flex: 1, paddingVertical: 8, paddingHorizontal: 6, borderRadius: 10, borderWidth: 1, borderColor: C.border, backgroundColor: C.white, alignItems: 'center' },
  cuotaChipOn: { backgroundColor: C.dark, borderColor: C.dark },
  cuotaChipTxt: { fontSize: 11, fontWeight: '800', color: C.textDark, textAlign: 'center' },

  // Efectivo Box
  cashBox: { backgroundColor: '#FFFBEB', borderRadius: 14, padding: 12, borderWidth: 1, borderColor: '#FDE68A', marginTop: 4 },
  vueltoBox: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#DCFCE7', padding: 12, borderRadius: 12, borderWidth: 1, borderColor: '#86EFAC' },
  vueltoLabel: { fontSize: 13, fontWeight: '800', color: '#065F46' },
  vueltoVal: { fontSize: 17, fontWeight: '900', color: '#047857' },

  // Transferencia Box
  bank: { backgroundColor: C.blueBg, borderRadius: 12, padding: 12, marginTop: 4, borderWidth: 1, borderColor: '#BFDBFE' },
  bankTitle: { fontWeight: '900', color: C.blue, marginBottom: 6, fontSize: 13 },
  bankLine: { fontSize: 13, color: C.textMuted, marginVertical: 1 },
  bankVal: { fontWeight: '900', color: C.textDark },
  copyBtn: { marginTop: 8, backgroundColor: C.white, borderWidth: 1, borderColor: '#93C5FD', borderRadius: 10, paddingVertical: 8, alignItems: 'center' },
  copyBtnTxt: { fontSize: 12, fontWeight: '800', color: C.blue },

  apply: { backgroundColor: C.dark, borderRadius: 12, paddingHorizontal: 18, justifyContent: 'center' },
  applyTxt: { color: C.white, fontWeight: '900' },
  pill: { paddingHorizontal: 12, paddingVertical: 7, borderRadius: 16, borderWidth: 1.5, borderColor: C.amarillo, backgroundColor: C.amarilloClaro, marginRight: 8 },
  pillOn: { backgroundColor: C.rojo, borderColor: C.rojo },
  pillTxt: { fontSize: 12, fontWeight: '900', color: '#92400E' },
  cuponMsg: { marginTop: 10, fontSize: 13, fontWeight: '700', color: C.textDark },
  puntosRow: { flexDirection: 'row', alignItems: 'center', marginTop: 14, paddingTop: 12, borderTopWidth: 1, borderTopColor: C.border },
  divider: { height: 1, backgroundColor: C.border, marginVertical: 8 },
  footer: { position: 'absolute', left: 0, right: 0, bottom: 0, padding: 12, backgroundColor: C.white, borderTopWidth: 1, borderTopColor: C.border, elevation: 14 },
  errorBanner: { backgroundColor: '#FEE2E2', borderWidth: 1, borderColor: '#FCA5A5', borderRadius: 10, padding: 9, marginBottom: 8 },
  errorBannerTxt: { color: '#B91C1C', fontSize: 12, fontWeight: '800', textAlign: 'center' },
  confirm: {
    backgroundColor: C.rojo,
    borderRadius: 14,
    paddingHorizontal: 16,
    paddingVertical: 12,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  confirmReady: { backgroundColor: '#059669' },
  confirmSub: { color: C.amarillo, fontSize: 10, fontWeight: '900', letterSpacing: 0.6 },
  confirmTxt: { color: C.white, fontWeight: '900', fontSize: 16, marginTop: 1 },
  confirmPriceBox: { backgroundColor: 'rgba(0,0,0,0.2)', paddingHorizontal: 12, paddingVertical: 8, borderRadius: 10 },
  confirmPrice: { color: C.white, fontWeight: '900', fontSize: 16 },
});
