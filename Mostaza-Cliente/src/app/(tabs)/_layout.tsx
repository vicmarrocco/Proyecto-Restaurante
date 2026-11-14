import React, { useCallback, useEffect, useState } from 'react';
import { Platform, Text } from 'react-native';
import { Tabs } from 'expo-router';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { MostazaColors as C } from '@/constants/Colors';
import { Config } from '@/constants/Config';
import { useAuth } from '@/context/AuthContext';
import { useCart } from '@/context/CartContext';
import { ApiService } from '@/services/api';
import { esActivo } from '@/utils/format';

const icon = (e: string) => () => <Text style={{ fontSize: 19 }}>{e}</Text>;

export default function TabsLayout() {
  const { usuario } = useAuth();
  const { totalItems } = useCart();
  const [activosCount, setActivosCount] = useState(0);
  const insets = useSafeAreaInsets();
  const bottomSafe = Platform.OS === 'web' ? 0 : Math.min(insets.bottom, 8);

  const checkActivos = useCallback(async () => {
    if (!usuario || !ApiService.hasToken()) {
      setActivosCount(0);
      return;
    }
    const r = await ApiService.misPedidos();
    if (r.ok) {
      setActivosCount(r.pedidos.filter((p) => esActivo(p.estado)).length);
    }
  }, [usuario]);

  useEffect(() => {
    checkActivos();
    const t = setInterval(checkActivos, Config.POLLING_MS);
    return () => clearInterval(t);
  }, [checkActivos]);

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: C.rojo,
        tabBarInactiveTintColor: C.textMuted,
        tabBarStyle: {
          backgroundColor: C.white,
          borderTopColor: C.border,
          borderTopWidth: 1,
          height: (Platform.OS === 'web' ? 65 : 56) + bottomSafe,
          paddingBottom: 4 + bottomSafe,
          paddingTop: 4,
          elevation: 8,
        },
        tabBarLabelStyle: { fontSize: 10, fontWeight: '800' },
        tabBarBadgeStyle: { backgroundColor: C.rojo, color: C.white, fontWeight: '900' },
      }}>
      <Tabs.Screen name="index" options={{ title: 'Inicio', tabBarIcon: icon('🏠') }} />
      <Tabs.Screen name="menu" options={{ title: 'Menú', tabBarIcon: icon('🍔') }} />
      <Tabs.Screen name="carrito" options={{ title: 'Carrito', tabBarIcon: icon('🛒'), tabBarBadge: totalItems > 0 ? totalItems : undefined }} />
      <Tabs.Screen name="pedidos" options={{ title: 'Pedidos', tabBarIcon: icon('🧾'), tabBarBadge: activosCount > 0 ? activosCount : undefined }} />
      <Tabs.Screen name="perfil" options={{ title: 'Perfil', tabBarIcon: icon('👤') }} />
    </Tabs>
  );
}
