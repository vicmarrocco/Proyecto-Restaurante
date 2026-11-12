import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { MostazaColors as C } from '@/constants/Colors';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import { CartProvider } from '@/context/CartContext';

// Si algún componente falla al renderizar, se ve el error en pantalla en vez de quedar en blanco
export { ErrorBoundary } from 'expo-router';

// La app siempre arranca en las tabs (Inicio, Menú, Carrito, Pedidos, Perfil)
export const unstable_settings = { initialRouteName: '(tabs)' };

function RootStack() {
  const { isLoading } = useAuth();
  const [splashListo, setSplashListo] = useState(false);

  // Tope a la pantalla de carga: si el servidor tarda, el layout igual se muestra
  useEffect(() => {
    const t = setTimeout(() => setSplashListo(true), 2500);
    return () => clearTimeout(t);
  }, []);

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
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="(tabs)" />
        <Stack.Screen name="checkout" />
        <Stack.Screen name="pedido/[id]" />
      </Stack>
    </>
  );
}

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <CartProvider>
          <RootStack />
        </CartProvider>
      </AuthProvider>
    </SafeAreaProvider>
  );
}
