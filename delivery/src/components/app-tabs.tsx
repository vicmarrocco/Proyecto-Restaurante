// ============================================================
// components/app-tabs.tsx
// Barra de navegación inferior móvil para Expo
// ============================================================
import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { useRouter, usePathname } from 'expo-router';
import { MostazaColors } from '@/constants/Colors';

export default function AppTabs() {
  const router = useRouter();
  const pathname = usePathname();

  const tabs = [
    { name: 'Disponibles', path: '/', icon: '📦' },
    { name: 'En Curso', path: '/activo', icon: '🛵' },
    { name: 'Historial', path: '/historial', icon: '📊' },
    { name: 'Mi Turno', path: '/perfil', icon: '👤' },
  ];

  if (pathname === '/login') {
    return null;
  }

  return (
    <View style={styles.tabBar}>
      {tabs.map((t) => {
        const isActive = pathname === t.path;
        return (
          <TouchableOpacity
            key={t.path}
            style={[styles.tabItem, isActive && styles.tabItemActive]}
            onPress={() => router.push(t.path as any)}
            activeOpacity={0.7}>
            <Text style={[styles.tabIcon, isActive && styles.tabIconActive]}>{t.icon}</Text>
            <Text style={[styles.tabLabel, isActive && styles.tabLabelActive]}>{t.name}</Text>
          </TouchableOpacity>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  tabBar: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 65,
    backgroundColor: '#FFFFFF',
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-around',
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    paddingBottom: 6,
    zIndex: 999,
    elevation: 8,
    shadowColor: '#000',
    shadowOpacity: 0.06,
    shadowOffset: { width: 0, height: -2 },
    shadowRadius: 6,
  },
  tabItem: {
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 12,
  },
  tabItemActive: {
    backgroundColor: '#FEF2F2',
  },
  tabIcon: {
    fontSize: 20,
    opacity: 0.7,
  },
  tabIconActive: {
    opacity: 1,
    transform: [{ scale: 1.1 }],
  },
  tabLabel: {
    fontSize: 11,
    fontWeight: '700',
    color: '#64748B',
    marginTop: 2,
  },
  tabLabelActive: {
    color: MostazaColors.rojo,
    fontWeight: '900',
  },
});
