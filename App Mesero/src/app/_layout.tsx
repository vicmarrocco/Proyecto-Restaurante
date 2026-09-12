import React from 'react';
import { Tabs } from 'expo-router';
import { Text, Platform } from 'react-native';
import { MostazaColors } from '@/constants/Colors';

export default function AppLayout() {
  return (
    <Tabs
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: MostazaColors.rojo,
        tabBarInactiveTintColor: '#94A3B8',
        tabBarStyle: {
          backgroundColor: '#0F172A',
          borderTopColor: '#1E293B',
          borderTopWidth: 1,
          height: Platform.OS === 'web' ? 65 : 60,
          paddingBottom: Platform.OS === 'web' ? 8 : 6,
          paddingTop: 6,
        },
        tabBarLabelStyle: {
          fontSize: 11,
          fontWeight: '800',
        },
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'Mesas Salón',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>🍽️</Text>,
        }}
      />
      <Tabs.Screen
        name="comanda"
        options={{
          title: 'Tomar Comanda',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>📝</Text>,
        }}
      />
      <Tabs.Screen
        name="perfil"
        options={{
          title: 'Ajustes',
          tabBarIcon: () => <Text style={{ fontSize: 20 }}>⚙️</Text>,
        }}
      />
    </Tabs>
  );
}
