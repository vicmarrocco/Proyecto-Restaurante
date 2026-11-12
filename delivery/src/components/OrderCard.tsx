// ============================================================
// components/OrderCard.tsx
// Tarjeta completa corporativa de pedido con detalles, telemetría y flujo
// ============================================================
import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Alert, ActivityIndicator } from 'react-native';
import { PedidoDelivery } from '@/types/delivery';
import { StatusBadge, PaymentBadge } from './StatusBadge';
import { TrackingMapCard } from './TrackingMapCard';
import { MostazaColors } from '@/constants/Colors';
import { ApiService } from '@/services/api';

interface OrderCardProps {
  pedido: PedidoDelivery;
  onStatusUpdated: () => void;
  destacado?: boolean;
}

export const OrderCard: React.FC<OrderCardProps> = ({
  pedido,
  onStatusUpdated,
  destacado = false,
}) => {
  const [expandido, setExpandido] = useState(false);
  const [loading, setLoading] = useState(false);

  const tomarPedido = async () => {
    Alert.alert(
      'Iniciar Reparto Oficial',
      `¿Deseás tomar la comanda #${pedido.idpedido} y comenzar el viaje a ${pedido.cliente_direccion}?`,
      [
        { text: 'Cancelar', style: 'cancel' },
        {
          text: '🛵 Iniciar Viaje',
          onPress: async () => {
            setLoading(true);
            const res = await ApiService.actualizarEstado(pedido.idpedido, 'En Camino');
            setLoading(false);
            if (res.ok) {
              Alert.alert('¡En Camino!', `Tomaste la comanda #${pedido.idpedido}. Conducí con precaución.`);
              onStatusUpdated();
            } else {
              Alert.alert('⚠️ Comanda No Disponible', res.error || 'No se pudo tomar la comanda. Es posible que otro repartidor ya la haya tomado.');
              onStatusUpdated();
            }
          },
        },
      ]
    );
  };

  const confirmarEntrega = async () => {
    const isEfectivo = pedido.metodo_pago === 'efectivo';
    const mensajeCobro = isEfectivo
      ? `Asegurate de haber cobrado ${pedido.total_formateado} en efectivo antes de confirmar.`
      : 'El pedido fue abonado online previamente.';

    Alert.alert(
      'Confirmar Entrega de Comanda',
      `¿Confirmás que entregaste la comanda #${pedido.idpedido} a ${pedido.cliente_nombre}?\n\n${mensajeCobro}`,
      [
        { text: 'Volver', style: 'cancel' },
        {
          text: '✅ Confirmar Entrega',
          style: 'default',
          onPress: async () => {
            setLoading(true);
            const res = await ApiService.actualizarEstado(pedido.idpedido, 'Entregado');
            setLoading(false);
            if (res.ok) {
              Alert.alert('¡Excelente Trabajo!', `Comanda #${pedido.idpedido} marcada como entregada.`);
              onStatusUpdated();
            } else {
              Alert.alert('Error', res.error || 'No se pudo confirmar la entrega.');
            }
          },
        },
      ]
    );
  };

  const cancelarPedido = () => {
    Alert.alert(
      'Reportar Incidencia',
      `¿Reportar imposibilidad de entrega para la comanda #${pedido.idpedido}?\n\nSeleccioná el motivo:`,
      [
        { text: 'Volver', style: 'cancel' },
        {
          text: '📞 Cliente no atiende en domicilio',
          onPress: () => ejecutarCancelacion('Cliente no atiende en domicilio'),
        },
        {
          text: '📍 Dirección incorrecta / fuera de rango',
          onPress: () => ejecutarCancelacion('Dirección incorrecta o fuera de rango'),
        },
        {
          text: '🛵 Problema técnico / contingencia vial',
          onPress: () => ejecutarCancelacion('Contingencia en transporte del repartidor'),
        },
      ]
    );
  };

  const ejecutarCancelacion = async (motivo: string) => {
    setLoading(true);
    const res = await ApiService.actualizarEstado(pedido.idpedido, 'Cancelado', motivo);
    setLoading(false);
    if (res.ok) {
      Alert.alert('Reporte Registrado', `Comanda #${pedido.idpedido} cancelada. El restaurante fue notificado.`);
      onStatusUpdated();
    } else {
      Alert.alert('Error', res.error || 'No se pudo cancelar el pedido.');
    }
  };

  const esEnCamino = pedido.estado === 'En Camino';
  const esPendiente =
    pedido.estado === 'Listo para Despacho' ||
    (pedido.estado as string) === 'Listo' ||
    pedido.estado === 'En Preparación' ||
    (pedido.estado as string) === 'En Preparacion' ||
    (pedido.estado as string) === 'En Cocina' ||
    pedido.estado === 'Recibido';
  const esEntregado = pedido.estado === 'Entregado';
  const esCancelado = pedido.estado === 'Cancelado';

  return (
    <View style={[styles.card, destacado && styles.cardDestacado]}>
      {/* Header de la tarjeta con badges */}
      <View style={styles.header}>
        <View style={{ flex: 1 }}>
          <View style={styles.titleRow}>
            <Text style={styles.orderId}>Comanda #{pedido.idpedido}</Text>
            {pedido.numero_pedido_usuario ? (
              <View style={styles.userBadge}>
                <Text style={styles.userBadgeText}>Cliente #{pedido.numero_pedido_usuario}</Text>
              </View>
            ) : null}
          </View>
          <Text style={styles.orderTime}>
            🕒 {pedido.hora} hs · 📍 {pedido.sucursal || 'Mostaza Formosa Centro'}
          </Text>
          {pedido.repartidor_asignado ? (
            <Text style={styles.riderTag}>🛵 Rider: {pedido.repartidor_asignado}</Text>
          ) : null}
        </View>
        <StatusBadge estado={pedido.estado} />
      </View>

      {/* Datos del Cliente */}
      <View style={styles.clientSection}>
        <View style={styles.clientHeaderRow}>
          <View style={styles.clientAvatarMini}>
            <Text style={styles.clientAvatarText}>
              {(pedido.cliente_nombre || 'C').charAt(0).toUpperCase()}
            </Text>
          </View>
          <View style={{ flex: 1 }}>
            <Text style={styles.clientName}>{pedido.cliente_nombre}</Text>
            <Text style={styles.clientPhone}>{pedido.cliente_telefono || 'Sin teléfono'}</Text>
          </View>
        </View>

        {pedido.notas ? (
          <View style={styles.notesBox}>
            <Text style={styles.notesText}>💬 Nota: {pedido.notas}</Text>
          </View>
        ) : null}
      </View>

      {/* SEGUIMIENTO DE DIRECCIÓN EN TIEMPO REAL CON GOOGLE MAPS GPS */}
      <TrackingMapCard pedido={pedido} />

      {/* Desglose de ítems (Colapsable / Expandible) */}
      <TouchableOpacity
        style={styles.toggleItems}
        onPress={() => setExpandido(!expandido)}
        activeOpacity={0.7}>
        <Text style={styles.toggleText}>
          🍔 {pedido.cantidad_items || pedido.items.length} ítem(s) en la comanda
        </Text>
        <Text style={styles.toggleArrow}>{expandido ? '▲ Ocultar' : '▼ Ver detalle'}</Text>
      </TouchableOpacity>

      {expandido && (
        <View style={styles.itemsList}>
          {pedido.items.map((item, idx) => (
            <View key={idx} style={styles.itemRow}>
              <View style={{ flex: 1 }}>
                <Text style={styles.itemName}>
                  <Text style={{ fontWeight: '800', color: MostazaColors.rojo }}>
                    {item.cantidad}x{' '}
                  </Text>
                  {item.descripcion}
                </Text>
                {item.modificadores ? (
                  <Text style={styles.itemMod}>↳ {item.modificadores}</Text>
                ) : null}
              </View>
              <Text style={styles.itemPrice}>{item.subtotal_formateado}</Text>
            </View>
          ))}
        </View>
      )}

      {/* Badge de Pago y Cobranza */}
      <PaymentBadge
        metodo={pedido.metodo_pago}
        total={pedido.total_formateado}
        pagaCon={pedido.paga_con}
        vuelto={pedido.vuelto_formateado}
      />

      {/* Botones Principales de Flujo de Entrega */}
      {loading ? (
        <View style={styles.loadingBox}>
          <ActivityIndicator size="small" color={MostazaColors.rojo} />
          <Text style={styles.loadingBtnText}>Actualizando estado del pedido...</Text>
        </View>
      ) : (
        <>
          {esPendiente && (
            <TouchableOpacity
              style={[styles.actionBtn, styles.btnTomar]}
              onPress={tomarPedido}
              activeOpacity={0.85}>
              <Text style={styles.actionBtnText}>🛵 TOMAR PEDIDO E INICIAR VIAJE</Text>
            </TouchableOpacity>
          )}

          {esEnCamino && (
            <View style={{ gap: 8, marginTop: 10 }}>
              <TouchableOpacity
                style={[styles.actionBtn, styles.btnEntregar]}
                onPress={confirmarEntrega}
                activeOpacity={0.85}>
                <Text style={styles.actionBtnText}>✅ CONFIRMAR ENTREGA AL CLIENTE</Text>
              </TouchableOpacity>

              <TouchableOpacity
                style={[styles.actionBtn, styles.btnCancelar]}
                onPress={cancelarPedido}
                activeOpacity={0.85}>
                <Text style={[styles.actionBtnText, { color: '#DC2626' }]}>
                  ⚠️ Reportar Incidencia de Entrega
                </Text>
              </TouchableOpacity>
            </View>
          )}

          {esEntregado && (
            <View style={styles.deliveredBox}>
              <Text style={styles.deliveredText}>✓ Pedido Entregado con Éxito</Text>
            </View>
          )}

          {esCancelado && (
            <View style={[styles.deliveredBox, { backgroundColor: '#FEF2F2', borderColor: '#FECACA' }]}>
              <Text style={[styles.deliveredText, { color: '#DC2626' }]}>✕ Pedido Cancelado</Text>
            </View>
          )}
        </>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  card: {
    backgroundColor: '#FFFFFF',
    borderRadius: 16,
    padding: 16,
    marginHorizontal: 16,
    marginBottom: 16,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 3 },
    shadowOpacity: 0.08,
    shadowRadius: 10,
    elevation: 3,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  cardDestacado: {
    borderColor: MostazaColors.rojo,
    borderWidth: 2,
    backgroundColor: '#FFFDFD',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingBottom: 12,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    flexWrap: 'wrap',
  },
  orderId: {
    fontSize: 17,
    fontWeight: '900',
    color: '#0F172A',
  },
  userBadge: {
    backgroundColor: '#FEF2F2',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
    borderWidth: 1,
    borderColor: '#FEE2E2',
  },
  userBadgeText: {
    color: MostazaColors.rojo,
    fontSize: 11,
    fontWeight: '800',
  },
  orderTime: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 3,
    fontWeight: '600',
  },
  riderTag: {
    fontSize: 11,
    fontWeight: '800',
    color: '#0284C7',
    marginTop: 2,
  },
  clientSection: {
    paddingVertical: 10,
  },
  clientHeaderRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
  },
  clientAvatarMini: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: '#F1F5F9',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  clientAvatarText: {
    fontSize: 14,
    fontWeight: '900',
    color: MostazaColors.rojo,
  },
  clientName: {
    fontSize: 14,
    fontWeight: '800',
    color: '#0F172A',
  },
  clientPhone: {
    fontSize: 11,
    color: '#64748B',
    fontWeight: '600',
  },
  notesBox: {
    backgroundColor: '#FEF2F2',
    padding: 8,
    borderRadius: 8,
    marginTop: 8,
    borderLeftWidth: 3,
    borderLeftColor: MostazaColors.rojo,
  },
  notesText: {
    fontSize: 12,
    color: '#991B1B',
    fontWeight: '600',
  },
  toggleItems: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 8,
    paddingHorizontal: 10,
    backgroundColor: '#F8FAFC',
    borderRadius: 8,
    marginTop: 6,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  toggleText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#334155',
  },
  toggleArrow: {
    fontSize: 11,
    fontWeight: '800',
    color: MostazaColors.rojo,
  },
  itemsList: {
    paddingTop: 8,
    paddingHorizontal: 8,
    gap: 6,
    backgroundColor: '#FAFAFA',
    borderRadius: 8,
    marginTop: 4,
    paddingBottom: 8,
  },
  itemRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    paddingVertical: 3,
  },
  itemName: {
    fontSize: 12.5,
    color: '#1E293B',
    fontWeight: '600',
  },
  itemMod: {
    fontSize: 11,
    color: MostazaColors.rojo,
    fontWeight: '600',
    marginTop: 1,
  },
  itemPrice: {
    fontSize: 12,
    fontWeight: '700',
    color: '#64748B',
  },
  actionBtn: {
    paddingVertical: 13,
    borderRadius: 12,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 10,
    elevation: 2,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.1,
    shadowRadius: 4,
  },
  btnTomar: {
    backgroundColor: MostazaColors.rojo,
  },
  btnEntregar: {
    backgroundColor: '#16A34A',
  },
  btnCancelar: {
    backgroundColor: '#FEF2F2',
    borderWidth: 1,
    borderColor: '#FCA5A5',
    elevation: 0,
    shadowOpacity: 0,
  },
  actionBtnText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  loadingBox: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 12,
    gap: 8,
  },
  loadingBtnText: {
    fontSize: 12,
    color: MostazaColors.rojo,
    fontWeight: '700',
  },
  deliveredBox: {
    backgroundColor: '#DCFCE7',
    padding: 10,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 10,
    borderWidth: 1,
    borderColor: '#BBF7D0',
  },
  deliveredText: {
    color: '#166534',
    fontSize: 13,
    fontWeight: '800',
  },
});
