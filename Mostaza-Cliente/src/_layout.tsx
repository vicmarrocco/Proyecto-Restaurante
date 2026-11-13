import React, { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, Platform, Text, View } from 'react-native';
import { Tabs } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider, useSafeAreaInsets } from 'react-native-safe-area-context';
import { MostazaColors as C } from '@/constants/Colors';
import { Config } from '@/constants/Config';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import { CartProvider, useCart } from '@/context/CartContext';
import { ApiService } from '@/services/api';
import { esActivo } from '@/utils/format';

// Si algún componente falla al renderizar, se ve el error en pantalla en vez de quedar en blanco
export { ErrorBoundary } from 'expo-router';

const icon = (e: string) => () => <Text style={{ fontSize: 19 }}>{e}</Text>;

function AppTabs() {
  const { isLoading, usuario } = useAuth();
  const { totalItems } = useCart();
  const [activosCount, setActivosCount] = useState(0);
  const [splashListo, setSplashListo] = useState(false);
  const insets = useSafeAreaInsets();
  const bottomSafe = Platform.OS === 'web' ? 0 : Math.max(insets.bottom, Platform.OS === 'android' ? 44 : 16);

  useEffect(() => {
    const t = setTimeout(() => setSplashListo(true), 2500);
    return () => clearTimeout(t);
  }, []);

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

  if (isLoading && !splashListo) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: C.rojo }}>
        <Text style={{ fontSize: 54 }}>🍔</Text>
        <ActivityIndicator size="large" color={C.amarillo} style={{ marginTop: 16 }} />
      </View>
    );
  }

  return (
    <>
      <StatusBar style="light" />
      <Tabs
        screenOptions={{
          headerShown: false,
          tabBarActiveTintColor: C.rojo,
          tabBarInactiveTintColor: C.textMuted,
          tabBarStyle: {
            backgroundColor: C.white,
            borderTopColor: C.border,
            borderTopWidth: 1,
            height: (Platform.OS === 'web' ? 65 : 60) + bottomSafe,
            paddingBottom: (Platform.OS === 'web' ? 8 : 8) + bottomSafe,
            paddingTop: 6,
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
        <Tabs.Screen name="checkout" options={{ href: null, tabBarStyle: { display: 'none' } }} />
        <Tabs.Screen name="pedido/[id]" options={{ href: null, tabBarStyle: { display: 'none' } }} />
      </Tabs>
    </>
  );
}

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <CartProvider>
          <AppTabs />
        </CartProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
