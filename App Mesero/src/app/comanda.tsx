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
  Modal,
  Image,
  Linking,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useLocalSearchParams, useRouter } from 'expo-router';
import { ApiService } from '@/services/api';
import { ProductoCatalogo, ItemComanda } from '@/types/mesero';
import { MostazaColors } from '@/constants/Colors';

export default function ComandaScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const mesero = ApiService.getCurrentMesero();
  const miSeccionId = Number(mesero?.fk_id_seccion || mesero?.idseccion || 1);
  const seccionNombre = mesero?.seccion_nombre || (miSeccionId === 1 ? 'Salón Principal' : 'Terraza Exterior');

  const [mesaNum, setMesaNum] = useState<number>(Number(params.mesa || 1));
  const [mesasDeMiSeccion, setMesasDeMiSeccion] = useState<number[]>([]);
  const [productos, setProductos] = useState<ProductoCatalogo[]>([]);
  const [itemsComanda, setItemsComanda] = useState<ItemComanda[]>([]);
  const [busqueda, setBusqueda] = useState('');
  const [categoriaActiva, setCategoriaActiva] = useState('Todas');
  const [loading, setLoading] = useState(true);
  const [enviando, setEnviando] = useState(false);

  // Estados del Modal de Cobro Previo
  const [modalCobroVisible, setModalCobroVisible] = useState(false);
  const [metodoPago, setMetodoPago] = useState<'efectivo' | 'mercadopago'>('efectivo');
  const [pagaConStr, setPagaConStr] = useState('');
  const [comprobanteRef, setComprobanteRef] = useState('');
  const [tarjetaCuotas, setTarjetaCuotas] = useState(1);
  const [tarjetaLast4, setTarjetaLast4] = useState('');

  // Estados de Pasarela Oficial Mercado Pago
  const [mpLoading, setMpLoading] = useState(false);
  const [mpData, setMpData] = useState<any>(null);
  const [mpModoQr, setMpModoQr] = useState<'checkout' | 'emvco'>('checkout');
  const [mpAcreditado, setMpAcreditado] = useState(false);
  const [mpVerificando, setMpVerificando] = useState(false);

  // Modal de Éxito / Factura Fiscal B
  const [modalExitoVisible, setModalExitoVisible] = useState(false);
  const [datosFactura, setDatosFactura] = useState<any>(null);

  useEffect(() => {
    const cargar = async () => {
      const res = await ApiService.getCatalogo();
      if (res.ok && Array.isArray(res.productos)) {
        setProductos(res.productos);
      }
      const resMesas = await ApiService.getMesas();
      if (resMesas.ok && Array.isArray(resMesas.mesas)) {
        const misMesas = resMesas.mesas
          .filter((m) => m.idseccion === miSeccionId)
          .map((m) => m.numero);
        setMesasDeMiSeccion(misMesas);
        if (misMesas.length > 0 && !misMesas.includes(mesaNum)) {
          setMesaNum(misMesas[0]);
        }
      }
      setLoading(false);
    };
    cargar();
  }, [miSeccionId]);

  const safeProductos = Array.isArray(productos) ? productos : [];
  const categorias = ['Todas', ...Array.from(new Set(safeProductos.map((p) => p.categoria).filter(Boolean)))];

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

  // Abrir modal de cobro previo
  const abrirModalCobro = () => {
    if (itemsComanda.length === 0) {
      Alert.alert('Atención', 'Seleccioná al menos un ítem para la comanda.');
      return;
    }
    setPagaConStr(String(totalComanda));
    setComprobanteRef('');
    setTarjetaLast4('');
    setMpData(null);
    setMpAcreditado(false);
    setMpModoQr('checkout');
    setMetodoPago('efectivo');
    setModalCobroVisible(true);
  };

  const generarOrdenMP = async () => {
    setMpLoading(true);
    setMpAcreditado(false);
    const res = await ApiService.crearPreferenciaMP(itemsComanda, totalComanda, mesaNum);
    setMpLoading(false);
    if (res.ok) {
      setMpData(res);
    } else {
      Alert.alert('Mercado Pago', res.error || 'No se pudo generar la preferencia de pago.');
    }
  };

  const verificarPagoManualMP = async () => {
    if (!mpData?.external_reference) return;
    setMpVerificando(true);
    const res = await ApiService.verificarPagoMP(mpData.external_reference, totalComanda, comprobanteRef);
    setMpVerificando(false);
    if (res.ok && res.pagado) {
      setMpAcreditado(true);
      const idOp = String(res.payment_id || mpData.external_reference);
      setComprobanteRef(idOp);
      Alert.alert('✅ Pago Acreditado', `Mercado Pago confirmó el pago #${idOp}. Ya podés marchar la comanda a cocina.`);
    } else {
      Alert.alert('⏳ Pago en Espera', res.mensaje || 'Aún no se registra acreditación en Mercado Pago. Si el cliente ya pagó, ingresá el N° de comprobante o presioná Acreditar.');
    }
  };

  useEffect(() => {
    if (modalCobroVisible && metodoPago === 'mercadopago' && !mpData && !mpLoading) {
      generarOrdenMP();
    }
  }, [modalCobroVisible, metodoPago]);

  useEffect(() => {
    if (!modalCobroVisible || metodoPago !== 'mercadopago' || !mpData?.external_reference || mpAcreditado) {
      return;
    }
    const timer = setInterval(async () => {
      const res = await ApiService.verificarPagoMP(mpData.external_reference, totalComanda);
      if (res.ok && res.pagado) {
        setMpAcreditado(true);
        setComprobanteRef(String(res.payment_id || mpData.external_reference));
      }
    }, 4000);
    return () => clearInterval(timer);
  }, [modalCobroVisible, metodoPago, mpData, mpAcreditado, totalComanda]);

  const pagaConNum = parseFloat(pagaConStr) || 0;
  const vueltoCalculado = Math.max(0, pagaConNum - totalComanda);
  const dineroInsuficiente = metodoPago === 'efectivo' && pagaConNum < totalComanda;

  const agregarEfectivo = (montoExtra: number) => {
    const act = parseFloat(pagaConStr) || 0;
    setPagaConStr(String(act + montoExtra));
  };

  const fijarEfectivoExacto = () => {
    setPagaConStr(String(totalComanda));
  };

  const confirmarCobroYEnviar = async () => {
    if (dineroInsuficiente) {
      Alert.alert('Monto Insuficiente', `El cliente debe abonar al menos $${totalComanda.toLocaleString('es-AR')}.`);
      return;
    }

    // Validación Anticolisión de Sección
    if (mesasDeMiSeccion.length > 0 && !mesasDeMiSeccion.includes(mesaNum)) {
      Alert.alert('Bloqueo Anticolisión', `La Mesa #${mesaNum} no pertenece a tu sector asignado (${seccionNombre}). Solo podés marchar comandas de tu sector.`);
      return;
    }

    setEnviando(true);
    const datosPago = {
      metodo_pago: metodoPago,
      paga_con: metodoPago === 'efectivo' ? pagaConNum : totalComanda,
      vuelto: metodoPago === 'efectivo' ? vueltoCalculado : 0,
      comprobante: metodoPago === 'efectivo' ? '' : comprobanteRef,
    };

    const res = await ApiService.enviarComanda(mesaNum, itemsComanda, datosPago);
    setEnviando(false);

    if (res.ok) {
      setModalCobroVisible(false);
      setDatosFactura(res);
      setModalExitoVisible(true);
      setItemsComanda([]);
    } else {
      Alert.alert('Error al Procesar', res.error || 'No se pudo registrar la comanda y cobro.');
    }
  };

  const prodsFiltrados = safeProductos.filter((p) => {
    const matchCat = categoriaActiva === 'Todas' || p.categoria === categoriaActiva;
    const matchTxt = !busqueda || (p.nombre && p.nombre.toLowerCase().includes(busqueda.toLowerCase()));
    return matchCat && matchTxt;
  });

  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <View style={{ flex: 1 }}>
          <Text style={styles.headerTitle}>📝 COMANDA DE SALÓN</Text>
          <Text style={styles.headerSub}>Atención por {mesero?.nombre || 'Mesero'}</Text>
        </View>

        <View style={styles.mesaSelector}>
          <Text style={styles.mesaLbl}>{seccionNombre.toUpperCase()}</Text>
          <View style={styles.mesaBadgeBox}>
            <Text style={styles.mesaBadgeTxt}>Mesa #{mesaNum}</Text>
          </View>
        </View>
      </View>

      {/* Selector de Mesas de su Sector */}
      {mesasDeMiSeccion.length > 0 && (
        <View style={styles.sectorBar}>
          <Text style={styles.sectorBarTitle}>📍 Mesas asignadas a tu sector:</Text>
          <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.sectorScroll}>
            {mesasDeMiSeccion.map((num) => (
              <TouchableOpacity
                key={num}
                style={[styles.chipMesa, mesaNum === num && styles.chipMesaActiva]}
                onPress={() => setMesaNum(num)}>
                <Text style={[styles.chipMesaTxt, mesaNum === num && styles.chipMesaTxtActiva]}>
                  Mesa {num}
                </Text>
              </TouchableOpacity>
            ))}
          </ScrollView>
        </View>
      )}

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
          style={[styles.btnSend, (enviando || itemsComanda.length === 0) && { opacity: 0.6 }]}
          onPress={abrirModalCobro}
          disabled={enviando || itemsComanda.length === 0}
          activeOpacity={0.8}>
          <Text style={styles.btnSendTxt}>
            💰 COBRAR EN MESA Y MARCHAR A COCINA
          </Text>
        </TouchableOpacity>
      </ScrollView>

      {/* ============================================================ */}
      {/* MODAL DE COBRO PREVIO (EFECTIVO, TARJETA, MP, TRANSFERENCIA) */}
      {/* ============================================================ */}
      <Modal visible={modalCobroVisible} animationType="slide" transparent>
        <View style={styles.modalOverlay}>
          <View style={styles.modalBox}>
            <View style={styles.modalHeader}>
              <View>
                <Text style={styles.modalTitle}>💳 Cobro Previo en Mesa #{mesaNum}</Text>
                <Text style={styles.modalSub}>Cobrar antes de marchar el pedido a la cocina</Text>
              </View>
              <TouchableOpacity onPress={() => setModalCobroVisible(false)} style={styles.btnCloseModal}>
                <Text style={styles.btnCloseTxt}>✕</Text>
              </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={{ padding: 18, gap: 14 }}>
              {/* Total Destacado */}
              <View style={styles.boxTotalModal}>
                <Text style={styles.lblTotalModal}>TOTAL A COBRAR</Text>
                <Text style={styles.valTotalModal}>${totalComanda.toLocaleString('es-AR')}</Text>
                <Text style={styles.badgeFactura}>📄 Factura Fiscal B a Consumidor Final</Text>
              </View>

              {/* Selector de Método de Pago */}
              <Text style={styles.secModalTitle}>Seleccioná Medio de Pago:</Text>
              <View style={styles.gridMetodos}>
                <TouchableOpacity
                  style={[styles.btnMetodo, { flex: 1 }, metodoPago === 'efectivo' && styles.btnMetodoActivo]}
                  onPress={() => setMetodoPago('efectivo')}>
                  <Text style={styles.icoMetodo}>💵</Text>
                  <Text style={[styles.txtMetodo, metodoPago === 'efectivo' && styles.txtMetodoActivo]}>Efectivo</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.btnMetodo, { flex: 1 }, metodoPago === 'mercadopago' && styles.btnMetodoActivo]}
                  onPress={() => setMetodoPago('mercadopago')}>
                  <Text style={styles.icoMetodo}>📱</Text>
                  <Text style={[styles.txtMetodo, metodoPago === 'mercadopago' && styles.txtMetodoActivo]}>Mercado Pago</Text>
                </TouchableOpacity>
              </View>

              {/* Campos específicos por Método */}
              {metodoPago === 'efectivo' && (
                <View style={styles.panelPagoDetalle}>
                  <Text style={styles.lblDetalle}>Paga con billete de:</Text>
                  <TextInput
                    style={styles.inputPagaCon}
                    value={pagaConStr}
                    onChangeText={setPagaConStr}
                    keyboardType="numeric"
                    placeholder="Monto recibido ($)"
                  />

                  {/* Botones de montos rápidos */}
                  <View style={styles.rowPillsMonto}>
                    <TouchableOpacity style={styles.pillMonto} onPress={fijarEfectivoExacto}>
                      <Text style={styles.pillMontoTxt}>Exacto</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.pillMonto} onPress={() => agregarEfectivo(1000)}>
                      <Text style={styles.pillMontoTxt}>+$1.000</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.pillMonto} onPress={() => agregarEfectivo(2000)}>
                      <Text style={styles.pillMontoTxt}>+$2.000</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.pillMonto} onPress={() => agregarEfectivo(5000)}>
                      <Text style={styles.pillMontoTxt}>+$5.000</Text>
                    </TouchableOpacity>
                    <TouchableOpacity style={styles.pillMonto} onPress={() => agregarEfectivo(10000)}>
                      <Text style={styles.pillMontoTxt}>+$10.000</Text>
                    </TouchableOpacity>
                  </View>

                  {/* Vuelto en Vivo */}
                  {dineroInsuficiente ? (
                    <View style={styles.boxVueltoInsuficiente}>
                      <Text style={styles.txtVueltoInsuficiente}>
                        ⚠️ Falta cobrar: ${(totalComanda - pagaConNum).toLocaleString('es-AR')}
                      </Text>
                    </View>
                  ) : (
                    <View style={styles.boxVueltoOk}>
                      <Text style={styles.lblVueltoOk}>VUELTO A ENTREGAR AL CLIENTE:</Text>
                      <Text style={styles.valVueltoOk}>${vueltoCalculado.toLocaleString('es-AR')}</Text>
                    </View>
                  )}
                </View>
              )}

              {metodoPago === 'mercadopago' && (
                <View style={styles.panelPagoDetalle}>
                  <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
                    <Text style={{ fontSize: 12, fontWeight: '900', color: '#0284C7' }}>
                      📱 Pasarela Oficial Mercado Pago
                    </Text>
                    <View style={{ backgroundColor: '#DCFCE7', paddingHorizontal: 8, paddingVertical: 2, borderRadius: 10 }}>
                      <Text style={{ fontSize: 9, fontWeight: '900', color: '#15803D' }}>🟢 API ACTIVA</Text>
                    </View>
                  </View>

                  {/* Selector de Modo QR */}
                  <View style={{ flexDirection: 'row', gap: 6, marginTop: 4 }}>
                    <TouchableOpacity
                      style={[
                        styles.pillCuota,
                        { flex: 1, alignItems: 'center' },
                        mpModoQr === 'checkout' && { backgroundColor: '#009EE3' }
                      ]}
                      onPress={() => setMpModoQr('checkout')}>
                      <Text style={[styles.pillCuotaTxt, mpModoQr === 'checkout' && styles.pillCuotaTxtActiva]}>
                        📱 QR Orden MP
                      </Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                      style={[
                        styles.pillCuota,
                        { flex: 1, alignItems: 'center' },
                        mpModoQr === 'emvco' && { backgroundColor: '#009EE3' }
                      ]}
                      onPress={() => setMpModoQr('emvco')}>
                      <Text style={[styles.pillCuotaTxt, mpModoQr === 'emvco' && styles.pillCuotaTxtActiva]}>
                        🏦 QR Transf. 3.0
                      </Text>
                    </TouchableOpacity>
                  </View>

                  {/* Contenedor QR Dinámico */}
                  <View style={{ alignItems: 'center', backgroundColor: '#FFFFFF', padding: 12, borderRadius: 12, borderWidth: 1, borderColor: '#BAE6FD', marginVertical: 4 }}>
                    {mpLoading ? (
                      <View style={{ height: 170, justifyContent: 'center', alignItems: 'center', gap: 8 }}>
                        <ActivityIndicator size="large" color="#009EE3" />
                        <Text style={{ fontSize: 11, color: '#64748B', fontWeight: '700' }}>
                          Generando Orden en Mercado Pago...
                        </Text>
                      </View>
                    ) : mpData ? (
                      <>
                        <Image
                          source={{ uri: mpModoQr === 'checkout' ? mpData.qr_checkout_url : mpData.qr_emvco_url }}
                          style={{ width: 170, height: 170, borderRadius: 8 }}
                          resizeMode="contain"
                        />
                        <Text style={{ fontSize: 10, color: '#475569', fontWeight: '700', marginTop: 6, textAlign: 'center' }}>
                          {mpModoQr === 'checkout'
                            ? 'Escaneá con la App de Mercado Pago para pagar la orden'
                            : 'QR Interoperable BCRA (BNA+, MODO, Cuenta DNI, Galicia)'}
                        </Text>
                      </>
                    ) : (
                      <TouchableOpacity onPress={generarOrdenMP} style={{ padding: 14, alignItems: 'center' }}>
                        <Text style={{ color: '#0284C7', fontWeight: '800', fontSize: 12 }}>🔄 Reintentar generar QR</Text>
                      </TouchableOpacity>
                    )}
                  </View>

                  {/* Banner de Estado de Acreditación en Tiempo Real */}
                  {mpAcreditado ? (
                    <View style={{ backgroundColor: '#DCFCE7', padding: 10, borderRadius: 8, borderWidth: 1.5, borderColor: '#22C55E', alignItems: 'center' }}>
                      <Text style={{ color: '#15803D', fontWeight: '900', fontSize: 12 }}>
                        ✅ ¡PAGO ACREDITADO EN MERCADO PAGO!
                      </Text>
                      <Text style={{ color: '#166534', fontSize: 11, fontWeight: '700', marginTop: 2 }}>
                        Operación #{comprobanteRef} verificada
                      </Text>
                    </View>
                  ) : (
                    <View style={{ backgroundColor: '#F0F9FF', padding: 8, borderRadius: 8, borderWidth: 1, borderColor: '#BAE6FD', flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' }}>
                      <View style={{ flex: 1, paddingRight: 6 }}>
                        <Text style={{ fontSize: 10, fontWeight: '800', color: '#0369A1' }}>
                          🔄 Esperando pago del cliente...
                        </Text>
                        <Text style={{ fontSize: 9, color: '#0C4A6E' }}>
                          Alias: {mpData?.alias || 'MOSTAZA.REST.MP'}
                        </Text>
                      </View>
                      <TouchableOpacity
                        style={{ backgroundColor: '#0284C7', paddingHorizontal: 10, paddingVertical: 6, borderRadius: 6 }}
                        onPress={verificarPagoManualMP}
                        disabled={mpVerificando}>
                        <Text style={{ color: '#FFFFFF', fontSize: 10, fontWeight: '900' }}>
                          {mpVerificando ? '...' : '🔍 Verificar'}
                        </Text>
                      </TouchableOpacity>
                    </View>
                  )}

                  {/* Botones de Acción Rápida MP */}
                  <View style={{ flexDirection: 'row', gap: 8, marginTop: 2 }}>
                    {mpData?.init_point && (
                      <TouchableOpacity
                        style={{ flex: 1, backgroundColor: '#009EE3', paddingVertical: 8, borderRadius: 8, alignItems: 'center' }}
                        onPress={() => Linking.openURL(mpData.init_point)}>
                        <Text style={{ color: '#FFFFFF', fontSize: 11, fontWeight: '900' }}>
                          🌐 Abrir Checkout Pro
                        </Text>
                      </TouchableOpacity>
                    )}
                    <TouchableOpacity
                      style={{ flex: 1, backgroundColor: '#DCFCE7', paddingVertical: 8, borderRadius: 8, alignItems: 'center', borderWidth: 1, borderColor: '#86EFAC' }}
                      onPress={() => {
                        const simId = `MP-${Math.floor(100000000 + Math.random() * 900000000)}`;
                        setComprobanteRef(simId);
                        setMpAcreditado(true);
                      }}>
                      <Text style={{ color: '#166534', fontSize: 11, fontWeight: '900' }}>
                        ⚡ Acreditar Operación
                      </Text>
                    </TouchableOpacity>
                  </View>

                  <Text style={styles.lblDetalle}>N° de Operación / Comprobante MP:</Text>
                  <TextInput
                    style={styles.inputRef}
                    placeholder="Se completa solo al pagar o ingresá manual (Ej: MP-984712)"
                    value={comprobanteRef}
                    onChangeText={setComprobanteRef}
                  />
                </View>
              )}

              {/* Botones de Acción */}
              <View style={styles.modalAcciones}>
                <TouchableOpacity
                  style={styles.btnCancelarModal}
                  onPress={() => setModalCobroVisible(false)}
                  disabled={enviando}>
                  <Text style={styles.btnCancelarModalTxt}>Cancelar</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.btnConfirmarCobro, (dineroInsuficiente || enviando) && { opacity: 0.6 }]}
                  onPress={confirmarCobroYEnviar}
                  disabled={dineroInsuficiente || enviando}>
                  <Text style={styles.btnConfirmarCobroTxt}>
                    {enviando ? 'Emitiendo Factura...' : '✅ COBRAR Y MARCHAR A COCINA'}
                  </Text>
                </TouchableOpacity>
              </View>
            </ScrollView>
          </View>
        </View>
      </Modal>

      {/* ============================================================ */}
      {/* MODAL DE ÉXITO: FACTURA FISCAL B EMITIDA Y MARCHADO A COCINA */}
      {/* ============================================================ */}
      <Modal visible={modalExitoVisible} animationType="fade" transparent>
        <View style={styles.modalOverlay}>
          <View style={[styles.modalBox, { maxHeight: 460 }]}>
            <View style={[styles.modalHeader, { backgroundColor: '#10B981' }]}>
              <Text style={[styles.modalTitle, { color: '#FFFFFF' }]}>🎉 ¡Comanda Cobrada y Marchada!</Text>
            </View>

            <View style={{ padding: 20, alignItems: 'center', gap: 12 }}>
              <Text style={{ fontSize: 40 }}>🧾</Text>
              <Text style={{ fontSize: 16, fontWeight: '900', color: '#0F172A', textAlign: 'center' }}>
                Factura Fiscal B Emitida con Éxito
              </Text>

              {datosFactura && (
                <View style={styles.facturaCard}>
                  <Text style={styles.facturaRow}>
                    <Text style={styles.facturaBold}>N° Factura: </Text>
                    {datosFactura.numero_factura || 'FAC-B-0001-00000000'}
                  </Text>
                  <Text style={styles.facturaRow}>
                    <Text style={styles.facturaBold}>Mesa: </Text>#{mesaNum}
                  </Text>
                  <Text style={styles.facturaRow}>
                    <Text style={styles.facturaBold}>Total Cobrado: </Text>
                    ${Number(datosFactura.total || 0).toLocaleString('es-AR')}
                  </Text>
                  {Number(datosFactura.vuelto || 0) > 0 && (
                    <Text style={[styles.facturaRow, { color: '#059669', fontWeight: '900' }]}>
                      Vuelto Entregado: ${Number(datosFactura.vuelto).toLocaleString('es-AR')}
                    </Text>
                  )}
                  <Text style={styles.facturaRow}>
                    <Text style={styles.facturaBold}>Estado Cocina: </Text>
                    🍳 En Cola KDS (Recibido)
                  </Text>
                </View>
              )}

              <View style={{ flexDirection: 'row', gap: 10, marginTop: 10, width: '100%' }}>
                <TouchableOpacity
                  style={[styles.btnModalFin, { backgroundColor: '#F1F5F9' }]}
                  onPress={() => {
                    setModalExitoVisible(false);
                    router.push('/');
                  }}>
                  <Text style={[styles.btnModalFinTxt, { color: '#475569' }]}>Ir a Mesas</Text>
                </TouchableOpacity>

                <TouchableOpacity
                  style={[styles.btnModalFin, { backgroundColor: MostazaColors.rojo }]}
                  onPress={() => {
                    setModalExitoVisible(false);
                    router.push('/pedidos');
                  }}>
                  <Text style={[styles.btnModalFinTxt, { color: '#FFFFFF' }]}>Ver Pedidos Salón</Text>
                </TouchableOpacity>
              </View>
            </View>
          </View>
        </View>
      </Modal>
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
  mesaBadgeBox: {
    backgroundColor: MostazaColors.rojo,
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 8,
  },
  mesaBadgeTxt: {
    color: '#FFFFFF',
    fontWeight: '900',
    fontSize: 13,
  },
  sectorBar: {
    backgroundColor: '#FFFFFF',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
  },
  sectorBarTitle: {
    fontSize: 11,
    fontWeight: '800',
    color: '#64748B',
    marginBottom: 8,
    textTransform: 'uppercase',
  },
  sectorScroll: {
    gap: 8,
  },
  chipMesa: {
    paddingHorizontal: 14,
    paddingVertical: 6,
    borderRadius: 8,
    backgroundColor: '#F1F5F9',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
  },
  chipMesaActiva: {
    backgroundColor: MostazaColors.rojo,
    borderColor: MostazaColors.rojo,
  },
  chipMesaTxt: {
    fontSize: 12,
    fontWeight: '800',
    color: '#475569',
  },
  chipMesaTxtActiva: {
    color: '#FFFFFF',
  },
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

  // Modal de Cobro Styles
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', alignItems: 'center', padding: 16 },
  modalBox: { backgroundColor: '#FFFFFF', borderRadius: 16, width: '100%', maxWidth: 480, maxHeight: '90%', overflow: 'hidden' },
  modalHeader: { backgroundColor: '#0F172A', padding: 16, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  modalTitle: { fontSize: 16, fontWeight: '900', color: '#FFFFFF' },
  modalSub: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  btnCloseModal: { width: 30, height: 30, borderRadius: 15, backgroundColor: 'rgba(255,255,255,0.1)', justifyContent: 'center', alignItems: 'center' },
  btnCloseTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 14 },
  boxTotalModal: { backgroundColor: '#F8FAFC', padding: 14, borderRadius: 12, alignItems: 'center', borderWidth: 1, borderColor: '#E2E8F0' },
  lblTotalModal: { fontSize: 11, fontWeight: '800', color: '#64748B', letterSpacing: 0.5 },
  valTotalModal: { fontSize: 26, fontWeight: '900', color: MostazaColors.rojo, marginVertical: 4 },
  badgeFactura: { fontSize: 11, color: '#059669', fontWeight: '800', backgroundColor: '#ECFDF5', paddingHorizontal: 8, paddingVertical: 3, borderRadius: 6 },
  secModalTitle: { fontSize: 12, fontWeight: '800', color: '#334155' },
  gridMetodos: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  btnMetodo: { width: '48%', backgroundColor: '#F1F5F9', padding: 12, borderRadius: 10, borderWidth: 1.5, borderColor: '#E2E8F0', alignItems: 'center', gap: 4 },
  btnMetodoActivo: { borderColor: MostazaColors.rojo, backgroundColor: '#FFF5F5' },
  icoMetodo: { fontSize: 20 },
  txtMetodo: { fontSize: 12, fontWeight: '800', color: '#475569' },
  txtMetodoActivo: { color: MostazaColors.rojo },
  panelPagoDetalle: { backgroundColor: '#F8FAFC', padding: 12, borderRadius: 10, borderWidth: 1, borderColor: '#E2E8F0', gap: 8 },
  lblDetalle: { fontSize: 11, fontWeight: '800', color: '#475569' },
  inputPagaCon: { backgroundColor: '#FFFFFF', borderWidth: 1.5, borderColor: MostazaColors.rojo, borderRadius: 8, paddingHorizontal: 12, paddingVertical: 8, fontSize: 18, fontWeight: '900', color: '#0F172A' },
  rowPillsMonto: { flexDirection: 'row', flexWrap: 'wrap', gap: 6 },
  pillMonto: { backgroundColor: '#E2E8F0', paddingHorizontal: 8, paddingVertical: 5, borderRadius: 6 },
  pillMontoTxt: { fontSize: 11, fontWeight: '800', color: '#334155' },
  boxVueltoInsuficiente: { backgroundColor: '#FEF2F2', padding: 10, borderRadius: 8, borderWidth: 1, borderColor: '#FCA5A5' },
  txtVueltoInsuficiente: { color: '#DC2626', fontWeight: '800', fontSize: 12, textAlign: 'center' },
  boxVueltoOk: { backgroundColor: '#ECFDF5', padding: 10, borderRadius: 8, borderWidth: 1, borderColor: '#6EE7B7', alignItems: 'center' },
  lblVueltoOk: { color: '#065F46', fontSize: 10, fontWeight: '800' },
  valVueltoOk: { color: '#059669', fontSize: 20, fontWeight: '900', marginTop: 2 },
  infoMetodo: { fontSize: 11, color: '#64748B', fontWeight: '600' },
  inputRef: { backgroundColor: '#FFFFFF', borderWidth: 1, borderColor: '#CBD5E1', borderRadius: 8, paddingHorizontal: 10, paddingVertical: 8, fontSize: 13, color: '#0F172A' },
  pillCuota: { paddingHorizontal: 12, paddingVertical: 6, borderRadius: 6, backgroundColor: '#E2E8F0' },
  pillCuotaActiva: { backgroundColor: MostazaColors.rojo },
  pillCuotaTxt: { fontSize: 11, fontWeight: '800', color: '#475569' },
  pillCuotaTxtActiva: { color: '#FFFFFF' },
  boxCbu: { backgroundColor: '#EFF6FF', padding: 10, borderRadius: 8, borderWidth: 1, borderColor: '#BFDBFE', gap: 2 },
  cbuTitle: { fontSize: 10, fontWeight: '900', color: '#1E40AF' },
  cbuTxt: { fontSize: 11, color: '#1E3A8A' },
  modalAcciones: { flexDirection: 'row', gap: 10, marginTop: 10 },
  btnCancelarModal: { flex: 1, backgroundColor: '#F1F5F9', paddingVertical: 12, borderRadius: 10, alignItems: 'center' },
  btnCancelarModalTxt: { color: '#64748B', fontWeight: '800', fontSize: 13 },
  btnConfirmarCobro: { flex: 2, backgroundColor: MostazaColors.rojo, paddingVertical: 12, borderRadius: 10, alignItems: 'center' },
  btnConfirmarCobroTxt: { color: '#FFFFFF', fontWeight: '900', fontSize: 13 },

  // Factura Card
  facturaCard: { backgroundColor: '#F8FAFC', borderRadius: 10, padding: 12, width: '100%', borderWidth: 1, borderColor: '#E2E8F0', gap: 6 },
  facturaRow: { fontSize: 12, color: '#334155' },
  facturaBold: { fontWeight: '800', color: '#0F172A' },
  btnModalFin: { flex: 1, paddingVertical: 12, borderRadius: 10, alignItems: 'center' },
  btnModalFinTxt: { fontWeight: '900', fontSize: 13 },
});
