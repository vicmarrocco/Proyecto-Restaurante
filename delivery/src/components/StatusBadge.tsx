// ============================================================
// components/StatusBadge.tsx
// Badges estilizados para estados de pedidos y métodos de pago
// ============================================================
import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { EstadoPedido, MetodoPago } from '@/types/delivery';
import { MostazaColors } from '@/constants/Colors';

interface StatusBadgeProps {
  estado: EstadoPedido;
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ estado }) => {
  let bg = MostazaColors.border;
  let text = MostazaColors.textDark;
  let icon = '📦';

  switch (estado) {
    case 'Listo para Despacho':
      bg = MostazaColors.greenBg;
      text = MostazaColors.greenText;
      icon = '🚀';
      break;
    case 'En Preparación':
      bg = MostazaColors.orangeBg;
      text = MostazaColors.orangeText;
      icon = '👨‍🍳';
      break;
    case 'En Camino':
      bg = MostazaColors.blueBg;
      text = MostazaColors.blueText;
      icon = '🛵';
      break;
    case 'Entregado':
      bg = MostazaColors.greenBg;
      text = MostazaColors.greenText;
      icon = '✅';
      break;
    case 'Recibido':
      bg = MostazaColors.purpleBg;
      text = MostazaColors.purpleText;
      icon = '📥';
      break;
    default:
      bg = '#F1F5F9';
      text = '#475569';
  }

  return (
    <View style={[styles.badge, { backgroundColor: bg }]}>
      <Text style={styles.icon}>{icon}</Text>
      <Text style={[styles.text, { color: text }]}>{estado}</Text>
    </View>
  );
};

interface PaymentBadgeProps {
  metodo: MetodoPago;
  total: string;
  pagaCon?: number;
  vuelto?: string;
}

export const PaymentBadge: React.FC<PaymentBadgeProps> = ({ metodo, total, pagaCon, vuelto }) => {
  const isEfectivo = metodo === 'efectivo';

  return (
    <View style={[styles.payContainer, isEfectivo ? styles.payCash : styles.payOnline]}>
      <View style={styles.payHeader}>
        <Text style={styles.payIcon}>{isEfectivo ? '💵' : '💳'}</Text>
        <Text style={[styles.payTitle, { color: isEfectivo ? '#92400E' : '#1E40AF' }]}>
          {isEfectivo ? 'COBRAR EN EFECTIVO' : 'PAGADO ONLINE'}
        </Text>
      </View>
      <Text style={[styles.payAmount, { color: isEfectivo ? '#B45309' : '#1D4ED8' }]}>
        {total}
      </Text>
      {isEfectivo && vuelto && vuelto !== '$0' && (
        <Text style={styles.payChange}>
          Entregar vuelto: <Text style={{ fontWeight: 'bold' }}>{vuelto}</Text> (Paga con ${pagaCon})
        </Text>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  badge: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingHorizontal: 10,
    paddingVertical: 5,
    borderRadius: 20,
    alignSelf: 'flex-start',
  },
  icon: {
    fontSize: 12,
    marginRight: 5,
  },
  text: {
    fontSize: 12,
    fontWeight: '700',
  },
  payContainer: {
    padding: 10,
    borderRadius: 10,
    marginTop: 8,
    borderWidth: 1,
  },
  payCash: {
    backgroundColor: '#FEF3C7',
    borderColor: '#FDE68A',
  },
  payOnline: {
    backgroundColor: '#EFF6FF',
    borderColor: '#BFDBFE',
  },
  payHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  payIcon: {
    fontSize: 14,
  },
  payTitle: {
    fontSize: 11,
    fontWeight: '800',
    letterSpacing: 0.5,
  },
  payAmount: {
    fontSize: 18,
    fontWeight: '900',
    marginTop: 2,
  },
  payChange: {
    fontSize: 12,
    color: '#78350F',
    marginTop: 2,
  },
});
