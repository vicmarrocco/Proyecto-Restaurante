import React from 'react';
import { ActivityIndicator, Text, View } from 'react-native';
import { Stack } from 'expo-router';
import { StatusBar } from 'expo-status-bar';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { MostazaColors as C } from '@/constants/Colors';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import { CartProvider } from '@/context/CartContext';
import LoginScreen from '@/components/LoginScreen';

// Si algún componente falla al renderizar, se ve el error en pantalla en vez de quedar en blanco
export { ErrorBoundary } from 'expo-router';

// La app siempre arranca en las tabs (Inicio, Menú, Carrito, Pedidos, Perfil)
export const unstable_settings = { initialRouteName: '(tabs)' };

function RootStack() {
  const { isLoading, usuario } = useAuth();

  // Mientras se verifica la sesión guardada
  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: C.rojo }}>
        <Text style={{ fontSize: 54 }}>🍔</Text>
        <ActivityIndicator size="large" color={C.amarillo} style={{ marginTop: 16 }} />
      </View>
    );
  }

  // Sin usuario: solo login / registro, a pantalla completa, sin tabs ni acceso al carrito
  if (!usuario) {
    return (
      <>
        <StatusBar style="light" />
        <LoginScreen />
      </>
    );
  }

  // Con usuario: la app completa (tabs + checkout + detalle del pedido)
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
