// ============================================================
// app/_layout.tsx
// Root Layout para Mostaza Delivery App con Expo Router y Auth Gate
// ============================================================
import React, { useEffect } from 'react';
import { View, StyleSheet, ActivityIndicator, Platform } from 'react-native';
import { StatusBar } from 'expo-status-bar';
import { Slot } from 'expo-router';
import { SafeAreaProvider, useSafeAreaInsets } from 'react-native-safe-area-context';
import * as SplashScreen from 'expo-splash-screen';
import AppTabs from '@/components/app-tabs';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import { MostazaColors } from '@/constants/Colors';

import LoginScreen from '@/components/LoginScreen';

SplashScreen.preventAutoHideAsync().catch(() => {});

function DeliveryGate() {
  const { isAuthenticated, isLoading } = useAuth();
  const insets = useSafeAreaInsets();
  const bottomSafe = Platform.OS === 'web' ? 0 : Math.max(insets.bottom, Platform.OS === 'android' ? 44 : 16);

  useEffect(() => {
    if (!isLoading) {
      SplashScreen.hideAsync().catch(() => {});
    }
  }, [isLoading]);

  if (isLoading) {
    return (
      <View style={styles.centerBox}>
        <ActivityIndicator size="large" color={MostazaColors.rojo} />
      </View>
    );
  }

  if (!isAuthenticated) {
    return (
      <View style={{ flex: 1, paddingBottom: bottomSafe, backgroundColor: '#F1F5F9' }}>
        <LoginScreen />
      </View>
    );
  }

  return (
    <View style={styles.container}>
      <StatusBar style="dark" />
      <View style={[styles.content, { paddingBottom: (Platform.OS === 'web' ? 65 : 62) + bottomSafe }]}>
        <Slot />
      </View>
      <AppTabs />
    </View>
  );
}

export default function RootLayout() {
  return (
    <SafeAreaProvider>
      <AuthProvider>
        <DeliveryGate />
      </AuthProvider>
    </SafeAreaProvider>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#FFFFFF',
  },
  content: {
    flex: 1,
    backgroundColor: '#F8FAFC',
  },
  centerBox: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#FFFFFF',
  },
});
