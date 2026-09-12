import React from 'react';
import { View, Text, StyleSheet, ScrollView, SafeAreaView } from 'react-native';

const RECETAS_STATIC = [
  {
    nombre: 'Mega Cuarto Doble',
    categoria: 'Hamburguesas',
    tiempo: '4 min',
    ingredientes: '2 Medallones 120g, 2 Fetas Cheddar, Mostaza especial, Ketchup, Cebolla picada, Pepinillos, Pan Brioche con sésamo.',
    pasos: '1. Tostar pan brioche a 210°C por 20 seg.\n2. Planchar carnes a 177°C por 110 seg.\n3. Montar queso cheddar sobre carne caliente.\n4. Aderezar tapa con mostaza, kétchup, cebolla y pepinillo.',
  },
  {
    nombre: 'Mega Pollo Crispy Deluxe',
    categoria: 'Pollo',
    tiempo: '5 min',
    ingredientes: 'Filet de pollo empanado crispy 140g, Mayonesa dulce, Lechuga repollada, Tomate redondo, Pan de Papa.',
    pasos: '1. Freír filet de pollo en freidora a 180°C por 4 min 30 seg.\n2. Tostar pan de papa.\n3. Aplicar 15g de mayonesa en ambas caras.\n4. Colocar tomate, lechuga y pollo caliente.',
  },
  {
    nombre: 'Papas Cheddar & Bacon Mega',
    categoria: 'Acompañamientos',
    tiempo: '3 min',
    ingredientes: 'Papas bastón premium 250g, Salsa cheddar tibia 60g, Panceta crocante picada 20g.',
    pasos: '1. Freír papas a 175°C por 3 min.\n2. Escurrir y salar inmediatamente.\n3. Servir en canasta y bañar con cheddar tibio.\n4. Espolvorear panceta crocante.',
  },
];

export default function RecetasScreen() {
  return (
    <SafeAreaView style={styles.safeArea}>
      <View style={styles.header}>
        <Text style={styles.headerTitle}>📖 LIBRO DE RECETAS TÉCNICAS</Text>
        <Text style={styles.headerSub}>Gramajes, Tiempos y Procedimientos de Cocina (R35)</Text>
      </View>

      <ScrollView contentContainerStyle={styles.scroll}>
        {RECETAS_STATIC.map((r, idx) => (
          <View key={idx} style={styles.card}>
            <View style={styles.cardTop}>
              <Text style={styles.recName}>{r.nombre}</Text>
              <Text style={styles.recTime}>⏱️ {r.tiempo}</Text>
            </View>
            <Text style={styles.secLbl}>Ingredientes y Gramajes:</Text>
            <Text style={styles.secTxt}>{r.ingredientes}</Text>

            <Text style={[styles.secLbl, { marginTop: 8 }]}>Pasos de Elaboración:</Text>
            <Text style={styles.secTxt}>{r.pasos}</Text>
          </View>
        ))}
      </ScrollView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#0B0F19' },
  header: {
    backgroundColor: '#0F172A',
    paddingHorizontal: 20,
    paddingVertical: 16,
    borderBottomWidth: 1,
    borderBottomColor: '#1E293B',
  },
  headerTitle: { fontSize: 16, fontWeight: '900', color: '#FBB728' },
  headerSub: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  scroll: { padding: 16 },
  card: { backgroundColor: '#1E293B', borderRadius: 14, padding: 16, marginBottom: 14, borderWidth: 1, borderColor: '#334155' },
  cardTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  recName: { fontSize: 15, fontWeight: '900', color: '#FFFFFF' },
  recTime: { fontSize: 11, color: '#FBB728', fontWeight: '800' },
  secLbl: { fontSize: 11, fontWeight: '800', color: '#FBB728' },
  secTxt: { fontSize: 11, color: '#CBD5E1', marginTop: 2, lineHeight: 16 },
});
