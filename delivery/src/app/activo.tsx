// ============================================================
// app/activo.tsx
// Pantalla HUD: Pedido en Curso / En Viaje Activo con Google Maps GPS
// ============================================================
import React, { useState, useEffect, useCallback } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  RefreshControl,
  TouchableOpacity,
  Platform,
  ActivityIndicator,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PedidoDelivery } from '@/types/delivery';
import { ApiService } from '@/services/api';
import { OrderCard } from '@/components/OrderCard';
import { MostazaColors } from '@/constants/Colors';
import { useRouter } from 'expo-router';
import { useAuth } from '@/context/AuthContext';


export default function ActiveOrderScreen() {
  const router = useRouter();
  const { rider, logout } = useAuth();
  const [pedidosActivos, setPedidosActivos] = useState<PedidoDelivery[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);

  const handleCerrarTurno = () => {
    const confirmar = async () => {
      await logout();
      router.replace('/login');
    };

    if (Platform.OS === 'web') {
      if (window.confirm('¿Deseás concluir tu turno de entrega de hoy y registrar tu salida?')) {
        confirmar();
      }
    } else {
      Alert.alert(
        'Cerrar Sesión y Turno Oficial',
        '¿Deseás concluir tu turno de entrega de hoy y registrar tu salida?',
        [
          { text: 'Cancelar', style: 'cancel' },
          { text: 'Sí, Salir y Cerrar Turno', onPress: confirmar, style: 'destructive' },
        ]
      );
    }
  };

  const cargarActivos = useCallback(async (isRefresh = false) => {
    if (isRefresh) setRefreshing(true);
    const res = await ApiService.getPedidos('en_camino');
    if (res.ok) {
      setPedidosActivos(res.pedidos);
    }
    setLoading(false);
    if (isRefresh) setRefreshing(false);
  }, []);

  useEffect(() => {
    cargarActivos();
    const interval = setInterval(cargarActivos, 4000);
    return () => clearInterval(interval);
  }, [cargarActivos]);

  return (
    <SafeAreaView style={styles.safeArea}>
      {/* Header Corporativo HUD */}
      <View style={styles.header}>
        <View style={styles.headerTopRow}>
          <View style={styles.brandRow}>
            <Text style={styles.brandText}>MOSTAZA</Text>
            <View style={styles.badgeHUD}>
              <Text style={styles.badgeHUDText}>HUD RIDER</Text>
            </View>
          </View>
          <View style={{ flexDirection: 'row', alignItems: 'center', gap: 6 }}>
            <View style={styles.riderChip}>
              <Text style={styles.riderChipText}>
                {rider ? `${rider.avatar || '🛵'} ${rider.nombre.split(' ')[0]}` : '🛵 Repartidor'}
              </Text>
            </View>
            <TouchableOpacity
              style={styles.btnLogout}
              onPress={handleCerrarTurno}
              activeOpacity={0.8}>
              <Text style={styles.btnLogoutText}>🚪 Cerrar Turno</Text>
            </TouchableOpacity>
          </View>
        </View>

        <Text style={styles.headerTitle}>ENTREGA EN CURSO</Text>
        <Text style={styles.headerSub}>
          Navegación asistida con Google Maps GPS en tiempo real
        </Text>
      </View>

      <ScrollView
        contentContainerStyle={styles.scrollContent}
        refreshControl={
          <RefreshControl
            refreshing={refreshing}
            onRefresh={() => cargarActivos(true)}
            colors={[MostazaColors.rojo]}
          />
        }>
        {loading ? (
          <View style={styles.centerContainer}>
            <ActivityIndicator size="large" color={MostazaColors.rojo} />
            <Text style={styles.loadingText}>Sincronizando telemetría del viaje...</Text>
          </View>
        ) : pedidosActivos.length === 0 ? (
          <View style={styles.emptyContainer}>
            <View style={styles.emptyIconBox}>
              <Text style={styles.emptyEmoji}>🛣️</Text>
            </View>
            <Text style={styles.emptyTitle}>No tenés entregas en curso</Text>
            <Text style={styles.emptyDesc}>
              Seleccioná un pedido disponible en la pestaña "Disponibles" y presioná "Tomar Pedido" para iniciar el reparto con Google Maps.
            </Text>
            <TouchableOpacity
              style={styles.btnIrDisponibles}
              onPress={() => router.push('/')}
              activeOpacity={0.85}>
              <Text style={styles.btnIrText}>Ver Pedidos Disponibles ➔</Text>
            </TouchableOpacity>
          </View>
        ) : (
          pedidosActivos.map((pedido) => (
            <View key={pedido.idpedido} style={styles.activeWrapper}>
              <View style={styles.alertBanner}>
                <View style={styles.alertIconBox}>
                  <Text style={styles.alertIcon}>🚨</Text>
                </View>
                <View style={{ flex: 1 }}>
                  <Text style={styles.alertTitle}>VIAJE ACTIVO HACIA EL DESTINO</Text>
                  <Text style={styles.alertText} numberOfLines={1}>
                    {pedido.cliente_direccion}
                  </Text>
                </View>
              </View>

              <OrderCard
                pedido={pedido}
                onStatusUpdated={cargarActivos}
                destacado={true}
              />
            </View>
          ))
        )}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: {
    flex: 1,
    backgroundColor: '#F8FAFC',
    paddingBottom: Platform.OS === 'web' ? 70 : 65,
  },
  header: {
    backgroundColor: '#FFFFFF',
    paddingTop: 16,
    paddingBottom: 14,
    paddingHorizontal: 18,
    borderBottomWidth: 1,
    borderBottomColor: '#E2E8F0',
    elevation: 3,
    shadowColor: '#000',
    shadowOpacity: 0.05,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 5,
  },
  headerTopRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 6,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  brandText: {
    fontSize: 16,
    fontWeight: '900',
    color: MostazaColors.rojo,
    letterSpacing: 1,
  },
  badgeHUD: {
    backgroundColor: MostazaColors.amarillo,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 5,
  },
  badgeHUDText: {
    fontSize: 9,
    fontWeight: '900',
    color: '#0F172A',
  },
  riderChip: {
    backgroundColor: '#F1F5F9',
    paddingHorizontal: 10,
    paddingVertical: 4,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  riderChipText: {
    fontSize: 11,
    fontWeight: '800',
    color: '#334155',
  },
  headerTitle: {
    fontSize: 18,
    fontWeight: '900',
    color: '#0F172A',
    letterSpacing: 0.5,
  },
  headerSub: {
    fontSize: 11,
    color: '#64748B',
    marginTop: 2,
    fontWeight: '600',
  },
  scrollContent: {
    paddingTop: 14,
    paddingBottom: 30,
  },
  centerContainer: {
    padding: 40,
    alignItems: 'center',
  },
  loadingText: {
    marginTop: 12,
    color: MostazaColors.textMuted,
    fontSize: 13,
    fontWeight: '600',
  },
  emptyContainer: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 60,
    paddingHorizontal: 24,
    backgroundColor: '#FFFFFF',
    marginHorizontal: 16,
    marginTop: 16,
    borderRadius: 18,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  emptyIconBox: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: '#F8FAFC',
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  emptyEmoji: {
    fontSize: 36,
  },
  emptyTitle: {
    fontSize: 17,
    fontWeight: '800',
    color: '#0F172A',
    textAlign: 'center',
  },
  emptyDesc: {
    fontSize: 13,
    color: '#64748B',
    textAlign: 'center',
    marginTop: 8,
    lineHeight: 18,
    marginBottom: 20,
    maxWidth: 320,
  },
  btnIrDisponibles: {
    backgroundColor: MostazaColors.rojo,
    paddingVertical: 12,
    paddingHorizontal: 22,
    borderRadius: 12,
    elevation: 3,
    shadowColor: MostazaColors.rojo,
    shadowOpacity: 0.3,
    shadowOffset: { width: 0, height: 3 },
    shadowRadius: 6,
  },
  btnIrText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  activeWrapper: {
    marginBottom: 12,
  },
  alertBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#991B1B',
    marginHorizontal: 16,
    marginBottom: 10,
    paddingVertical: 10,
    paddingHorizontal: 14,
    borderRadius: 12,
    gap: 10,
    elevation: 2,
    shadowColor: '#991B1B',
    shadowOpacity: 0.25,
    shadowOffset: { width: 0, height: 2 },
    shadowRadius: 4,
  },
  alertIconBox: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: 'rgba(255, 255, 255, 0.2)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  alertIcon: {
    fontSize: 16,
  },
  alertTitle: {
    color: '#FEE2E2',
    fontSize: 10,
    fontWeight: '900',
    letterSpacing: 0.6,
  },
  alertText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '800',
  },
  btnLogout: {
    backgroundColor: '#FEE2E2',
    borderWidth: 1,
    borderColor: '#FECACA',
    paddingHorizontal: 8,
    paddingVertical: 5,
    borderRadius: 8,
  },
  btnLogoutText: {
    color: '#DC2626',
    fontSize: 10,
    fontWeight: '800',
  },
});
