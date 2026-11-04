// ============================================================
// components/DeliveryHeader.tsx
// Header corporativo superior para la flota oficial de Mostaza Delivery
// ============================================================
import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Switch } from 'react-native';
import { MostazaColors } from '@/constants/Colors';
import { Repartidor } from '@/types/delivery';

interface DeliveryHeaderProps {
  rider: Repartidor;
  online: boolean;
  onToggleOnline: (val: boolean) => void;
  onRefresh: () => void;
  onLogout?: () => void;
  onOpenSwitchModal?: () => void;
  pedidosEnCaminoCount?: number;
}

export const DeliveryHeader: React.FC<DeliveryHeaderProps> = ({
  rider,
  online,
  onToggleOnline,
  onRefresh,
  onLogout,
  onOpenSwitchModal,
  pedidosEnCaminoCount = 0,
}) => {
  return (
    <View style={styles.container}>
      {/* Barra superior de marca corporativa */}
      <View style={styles.topRow}>
        <View style={styles.brandRow}>
          <Text style={styles.logoText}>MOSTAZA</Text>
          <View style={styles.tag}>
            <Text style={styles.tagText}>FLOTA OFICIAL</Text>
          </View>
        </View>

        <View style={styles.topActions}>
          <TouchableOpacity style={styles.refreshBtn} onPress={onRefresh} activeOpacity={0.7}>
            <Text style={styles.refreshIcon}>🔄</Text>
          </TouchableOpacity>
          {onLogout && (
            <TouchableOpacity style={styles.logoutBtn} onPress={onLogout} activeOpacity={0.7}>
              <Text style={styles.logoutText}>🚪 Cerrar Turno</Text>
            </TouchableOpacity>
          )}
        </View>
      </View>

      {/* Tarjeta interactiva del Repartidor */}
      <TouchableOpacity
        style={styles.riderCard}
        onPress={onOpenSwitchModal}
        activeOpacity={0.8}
        disabled={!onOpenSwitchModal}>
        <View style={styles.avatarBox}>
          <Text style={styles.avatarText}>{rider.avatar || '🛵'}</Text>
          <View style={[styles.avatarStatusDot, { backgroundColor: online ? '#22C55E' : '#94A3B8' }]} />
        </View>

        <View style={styles.riderInfo}>
          <View style={styles.riderTitleRow}>
            <Text style={styles.riderName}>{rider.nombre}</Text>
            <View style={styles.ratingBadge}>
              <Text style={styles.ratingText}>⭐ {Number(rider.calificacion || 4.95).toFixed(2)}</Text>
            </View>
          </View>

          <Text style={styles.riderVehicle}>
            {rider.vehiculo} · <Text style={styles.riderPlate}>[{rider.patente}]</Text>
          </Text>

          <View style={styles.riderBaseRow}>
            <Text style={styles.riderBaseText}>
              📍 {rider.sucursal_base || 'Mostaza Formosa Centro'}
            </Text>
            {onOpenSwitchModal && (
              <View style={styles.btnSwitchRider}>
                <Text style={styles.btnSwitchText}>Cambiar ⇄</Text>
              </View>
            )}
          </View>
        </View>
      </TouchableOpacity>

      {/* Barra de Estado y Disponibilidad en Servicio */}
      <View style={styles.statusRow}>
        <View style={styles.statusIndicator}>
          <View style={[styles.statusDot, { backgroundColor: online ? '#16A34A' : '#DC2626' }]} />
          <Text style={[styles.statusText, { color: online ? '#15803D' : '#B91C1C' }]}>
            {online ? 'EN SERVICIO (RECIBIENDO PEDIDOS)' : 'EN PAUSA (DISPONIBILIDAD APAGADA)'}
          </Text>
        </View>

        <Switch
          value={online}
          onValueChange={onToggleOnline}
          trackColor={{ false: '#E2E8F0', true: '#BBF7D0' }}
          thumbColor={online ? '#16A34A' : '#94A3B8'}
        />
      </View>

      {/* Banner de Pedidos en Viaje */}
      {pedidosEnCaminoCount > 0 && (
        <View style={styles.activeBanner}>
          <Text style={styles.activeBannerIcon}>🛵</Text>
          <Text style={styles.activeBannerText}>
            Tenés {pedidosEnCaminoCount} entrega(s) en curso en este momento.
          </Text>
        </View>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#FFFFFF',
    paddingTop: 14,
    paddingBottom: 12,
    paddingHorizontal: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    elevation: 3,
    shadowColor: '#000',
    shadowOpacity: 0.06,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 6,
  },
  topRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  logoText: {
    fontSize: 19,
    fontWeight: '900',
    color: MostazaColors.rojo,
    letterSpacing: 1.2,
  },
  tag: {
    backgroundColor: MostazaColors.amarillo,
    paddingHorizontal: 8,
    paddingVertical: 3,
    borderRadius: 6,
  },
  tagText: {
    color: '#0F172A',
    fontSize: 9,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  topActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  refreshBtn: {
    backgroundColor: '#F1F5F9',
    padding: 8,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  refreshIcon: {
    fontSize: 14,
  },
  logoutBtn: {
    backgroundColor: '#FEF2F2',
    paddingVertical: 6,
    paddingHorizontal: 10,
    borderRadius: 14,
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  logoutText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#DC2626',
  },
  riderCard: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    padding: 10,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    gap: 12,
  },
  avatarBox: {
    width: 48,
    height: 48,
    borderRadius: 24,
    backgroundColor: '#FFFFFF',
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 1.5,
    borderColor: '#CBD5E1',
    position: 'relative',
  },
  avatarText: {
    fontSize: 24,
  },
  avatarStatusDot: {
    position: 'absolute',
    bottom: 0,
    right: 0,
    width: 12,
    height: 12,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: '#FFFFFF',
  },
  riderInfo: {
    flex: 1,
  },
  riderTitleRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  riderName: {
    color: '#0F172A',
    fontSize: 14,
    fontWeight: '800',
  },
  ratingBadge: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 6,
    paddingVertical: 1,
    borderRadius: 5,
  },
  ratingText: {
    fontSize: 10,
    fontWeight: '800',
    color: '#92400E',
  },
  riderVehicle: {
    color: '#475569',
    fontSize: 11,
    marginTop: 2,
    fontWeight: '600',
  },
  riderPlate: {
    color: '#0F172A',
    fontWeight: '800',
  },
  riderBaseRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 4,
  },
  riderBaseText: {
    fontSize: 10,
    color: '#64748B',
    fontWeight: '600',
  },
  btnSwitchRider: {
    backgroundColor: '#E2E8F0',
    paddingHorizontal: 8,
    paddingVertical: 2,
    borderRadius: 6,
  },
  btnSwitchText: {
    fontSize: 9,
    fontWeight: '800',
    color: '#334155',
  },
  statusRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 10,
    paddingTop: 8,
    borderTopWidth: 1,
    borderTopColor: '#F1F5F9',
  },
  statusIndicator: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  statusDot: {
    width: 8,
    height: 8,
    borderRadius: 4,
  },
  statusText: {
    fontSize: 10,
    fontWeight: '800',
    letterSpacing: 0.3,
  },
  activeBanner: {
    backgroundColor: '#FEF2F2',
    paddingVertical: 8,
    paddingHorizontal: 12,
    borderRadius: 10,
    marginTop: 8,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    borderWidth: 1,
    borderColor: '#FECACA',
  },
  activeBannerIcon: {
    fontSize: 14,
  },
  activeBannerText: {
    color: '#991B1B',
    fontSize: 11,
    fontWeight: '800',
    flex: 1,
  },
});
