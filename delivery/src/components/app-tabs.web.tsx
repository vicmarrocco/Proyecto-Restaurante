// ============================================================
// components/app-tabs.web.tsx
// Barra de navegación para entorno Web y Desktop
// ============================================================
import React from 'react';
import { View, Text, TouchableOpacity, StyleSheet } from 'react-native';
import { useRouter, usePathname } from 'expo-router';
import { MostazaColors } from '@/constants/Colors';

export default function AppTabs() {
  const router = useRouter();
  const pathname = usePathname();

  const tabs = [
    { name: 'Pedidos Disponibles', path: '/', icon: '📦' },
    { name: 'Pedido en Curso', path: '/activo', icon: '🛵' },
    { name: 'Historial y Rendición', path: '/historial', icon: '📊' },
    { name: 'Mi Turno', path: '/perfil', icon: '👤' },
  ];

  if (pathname === '/login') {
    return null;
  }

  return (
    <View style={styles.webContainer}>
      <View style={styles.tabBar}>
        <View style={styles.brandBox}>
          <Text style={styles.brandText}>MOSTAZA</Text>
          <View style={styles.badge}>
            <Text style={styles.badgeText}>DELIVERY</Text>
          </View>
        </View>

        <View style={styles.navLinks}>
          {tabs.map((t) => {
            const isActive = pathname === t.path;
            return (
              <TouchableOpacity
                key={t.path}
                style={[styles.tabItem, isActive && styles.tabItemActive]}
                onPress={() => router.push(t.path as any)}
                activeOpacity={0.7}>
                <Text style={styles.tabIcon}>{t.icon}</Text>
                <Text style={[styles.tabLabel, isActive && styles.tabLabelActive]}>{t.name}</Text>
              </TouchableOpacity>
            );
          })}
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  webContainer: {
    position: 'fixed' as any,
    bottom: 0,
    left: 0,
    right: 0,
    backgroundColor: '#FFFFFF',
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
    zIndex: 9999,
    alignItems: 'center',
    shadowColor: '#000',
    shadowOpacity: 0.06,
    shadowOffset: { width: 0, height: -2 },
    shadowRadius: 6,
  },
  tabBar: {
    maxWidth: 900,
    width: '100%',
    height: 60,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    paddingHorizontal: 20,
  },
  brandBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  brandText: {
    color: MostazaColors.rojo,
    fontWeight: '900',
    fontSize: 16,
    letterSpacing: 1,
  },
  badge: {
    backgroundColor: MostazaColors.amarillo,
    paddingHorizontal: 6,
    paddingVertical: 2,
    borderRadius: 4,
  },
  badgeText: {
    color: '#0F172A',
    fontWeight: '900',
    fontSize: 9,
  },
  navLinks: {
    flexDirection: 'row',
    gap: 12,
  },
  tabItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
    paddingVertical: 6,
    paddingHorizontal: 12,
    borderRadius: 8,
  },
  tabItemActive: {
    backgroundColor: '#FEF2F2',
  },
  tabIcon: {
    fontSize: 16,
  },
  tabLabel: {
    fontSize: 13,
    fontWeight: '700',
    color: '#64748B',
  },
  tabLabelActive: {
    color: MostazaColors.rojo,
    fontWeight: '900',
  },
});
