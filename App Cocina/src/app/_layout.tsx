import React from 'react';
import { Tabs } from 'expo-router';
import { Text, Platform, View, ActivityIndicator } from 'react-native';
import { MostazaColors } from '@/constants/Colors';
import { AuthProvider, useAuth } from '@/context/AuthContext';
import LoginScreen from '@/components/LoginScreen';

function CocinaGate() {
  const { isAuthenticated, isLoading } = useAuth();

  if (isLoading) {
    return (
      <View style={{ flex: 1, justifyContent: 'center', alignItems: 'center', backgroundColor: '#0F172A' }}>
        <ActivityIndicator size="large" color="#FBB728" />
      </View>
    );
  }

  if (!isAuthenticated) {
    return <LoginScreen />;
  }

  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: MostazaColors.rojo,
        tabBarInactiveTintColor: '#64748B',
        tabBarStyle: {
          backgroundColor: '#FFFFFF',
          borderTopColor: '#E2E8F0',
          borderTopWidth: 1,
          height: Platform.OS === 'web' ? 65 : 60,
          paddingBottom: Platform.OS === 'web' ? 8 : 6,
          paddingTop: 6,
          elevation: 8,
          shadowColor: '#000',
          shadowOpacity: 0.05,
          shadowOffset: { width: 0, height: -2 },
          shadowRadius: 6,
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '800',
        },
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'KDS Comandas',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>🍳</Text>,
        }}
      />
      <Tabs.Screen
        name="recetas"
        options={{
          title: 'Recetas',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>📖</Text>,
        }}
      />
      <Tabs.Screen
        name="stock"
        options={{
          title: 'Stock Insumos',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>📦</Text>,
        }}
      />
      <Tabs.Screen
        name="historial"
        options={{
          title: 'Mi Historial',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>⏱️</Text>,
        }}
      />
      <Tabs.Screen
        name="perfil"
        options={{
          title: 'Mi Turno',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>👤</Text>,
        }}
      />
      <Tabs.Screen
        name="login"
        options={{
          href: null,
          tabBarStyle: { display: 'none' },
        }}
      />
    </Tabs>
  );
}

export default function AppLayout() {
  return (
    <AuthProvider>
      <CocinaGate />
    </AuthProvider>
  );
}
