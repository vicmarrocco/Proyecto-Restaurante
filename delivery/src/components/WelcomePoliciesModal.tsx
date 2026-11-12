// ============================================================
// components/WelcomePoliciesModal.tsx
// Modal de Bienvenida, Políticas de Trabajo y Seguridad Vial (R38 & R39)
// ============================================================
import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  Modal,
  ScrollView,
  TouchableOpacity,
  Platform,
  Alert,
} from 'react-native';
import { MostazaColors } from '@/constants/Colors';
import { Repartidor } from '@/types/delivery';

interface WelcomePoliciesModalProps {
  visible: boolean;
  rider: Repartidor;
  onClose: () => void;
  onConfirmShiftStart: () => void;
}

export const WelcomePoliciesModal: React.FC<WelcomePoliciesModalProps> = ({
  visible,
  rider,
  onClose,
  onConfirmShiftStart,
}) => {
  const [chkCasco, setChkCasco] = useState(false);
  const [chkMochila, setChkMochila] = useState(false);
  const [chkCelular, setChkCelular] = useState(false);
  const [chkCambio, setChkCambio] = useState(false);

  const todoMarcado = chkCasco && chkMochila && chkCelular && chkCambio;

  const handleStart = () => {
    if (!todoMarcado) {
      Alert.alert(
        'Checklist Obligatorio',
        'Por favor, marcá todas las casillas de seguridad vial e higiene antes de iniciar el turno.'
      );
      return;
    }
    onConfirmShiftStart();
  };

  return (
    <Modal visible={visible} animationType="slide" transparent={true} onRequestClose={onClose}>
      <View style={styles.overlay}>
        <View style={styles.modalBox}>
          {/* Header */}
          <View style={styles.header}>
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
              <Text style={{ fontSize: 28 }}>🛵</Text>
              <View>
                <Text style={styles.headerTitle}>BIENVENIDO AL TURNO</Text>
                <Text style={styles.headerSub}>Mostaza Delivery · Sucursal Formosa</Text>
              </View>
            </View>
            <TouchableOpacity onPress={onClose} style={styles.btnClose}>
              <Text style={styles.btnCloseText}>✕</Text>
            </TouchableOpacity>
          </View>

          <ScrollView style={styles.body} contentContainerStyle={{ paddingBottom: 20 }}>
            {/* Tarjeta del Repartidor */}
            <View style={styles.riderBanner}>
              <Text style={styles.riderEmoji}>{rider.avatar}</Text>
              <View style={{ flex: 1 }}>
                <Text style={styles.riderName}>{rider.nombre}</Text>
                <Text style={styles.riderVehicle}>Vehículo: {rider.vehiculo}</Text>
                <Text style={styles.riderPlate}>Patente: {rider.patente || 'En regla'}</Text>
              </View>
              <View style={styles.statusBadge}>
                <Text style={styles.statusBadgeText}>● DISPONIBLE</Text>
              </View>
            </View>

            {/* R39: Políticas de Trabajo y Seguridad Vial */}
            <View style={styles.policyCard}>
              <Text style={styles.policyHeading}>
                🛡️ R39: POLÍTICAS DE TRABAJO Y SEGURIDAD VIAL
              </Text>
              <Text style={styles.policyItem}>
                <Text style={styles.bold}>1. Seguridad en Vía Pública:</Text> Uso estricto de casco reglamentario homologado y chaleco reflectivo en todo momento. Respetar velocidades máximas (40 km/h en avenidas urbanas).
              </Text>
              <Text style={styles.policyItem}>
                <Text style={styles.bold}>2. Higiene de Alimentos:</Text> La mochila térmica debe estar desinfectada y cerrada herméticamente. Las bebidas frías van separadas de las hamburguesas calientes.
              </Text>
              <Text style={styles.policyItem}>
                <Text style={styles.bold}>3. Cobro y Rendición de Efectivo:</Text> Verificar billetes antes de entregar la comanda. El efectivo recaudado debe rendirse completo a caja al finalizar el turno.
              </Text>
              <Text style={styles.policyItem}>
                <Text style={styles.bold}>4. Protocolo de Contingencia:</Text> Ante ausencia del cliente o accidente, reportar de inmediato usando el botón de reporte en la aplicación.
              </Text>
            </View>

            {/* Checklist Pre-Salida */}
            <Text style={styles.checklistTitle}>📋 Checklist de Seguridad Pre-Turno:</Text>
            <TouchableOpacity
              style={styles.checkRow}
              onPress={() => setChkCasco(!chkCasco)}
              activeOpacity={0.7}>
              <Text style={[styles.checkBox, chkCasco && styles.checkBoxActive]}>
                {chkCasco ? '✓' : ''}
              </Text>
              <Text style={styles.checkText}>Casco y chaleco reflectivo puestos</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.checkRow}
              onPress={() => setChkMochila(!chkMochila)}
              activeOpacity={0.7}>
              <Text style={[styles.checkBox, chkMochila && styles.checkBoxActive]}>
                {chkMochila ? '✓' : ''}
              </Text>
              <Text style={styles.checkText}>Mochila térmica limpia y sanitizada</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.checkRow}
              onPress={() => setChkCelular(!chkCelular)}
              activeOpacity={0.7}>
              <Text style={[styles.checkBox, chkCelular && styles.checkBoxActive]}>
                {chkCelular ? '✓' : ''}
              </Text>
              <Text style={styles.checkText}>Smartphone con GPS activo y batería cargada</Text>
            </TouchableOpacity>

            <TouchableOpacity
              style={styles.checkRow}
              onPress={() => setChkCambio(!chkCambio)}
              activeOpacity={0.7}>
              <Text style={[styles.checkBox, chkCambio && styles.checkBoxActive]}>
                {chkCambio ? '✓' : ''}
              </Text>
              <Text style={styles.checkText}>Cambio en efectivo para vuelto en mano</Text>
            </TouchableOpacity>
          </ScrollView>

          {/* Footer Botón */}
          <View style={styles.footer}>
            <TouchableOpacity
              style={[styles.btnStart, !todoMarcado && styles.btnStartDisabled]}
              onPress={handleStart}
              activeOpacity={0.85}>
              <Text style={styles.btnStartText}>
                🚀 ACEPTAR POLÍTICAS E INICIAR JORNADA (R42)
              </Text>
            </TouchableOpacity>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: {
    flex: 1,
    backgroundColor: 'rgba(15, 23, 42, 0.85)',
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  modalBox: {
    backgroundColor: '#FFFFFF',
    borderRadius: 20,
    width: '100%',
    maxWidth: 520,
    maxHeight: '90%',
    overflow: 'hidden',
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 10 },
    shadowOpacity: 0.25,
    shadowRadius: 20,
    elevation: 10,
  },
  header: {
    backgroundColor: '#0F172A',
    paddingHorizontal: 20,
    paddingVertical: 16,
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  headerTitle: {
    fontSize: 16,
    fontWeight: '900',
    color: '#FFFFFF',
    letterSpacing: 0.5,
  },
  headerSub: {
    fontSize: 11,
    color: MostazaColors.amarillo,
    fontWeight: '700',
    marginTop: 2,
  },
  btnClose: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: '#1E293B',
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnCloseText: {
    color: '#94A3B8',
    fontSize: 14,
    fontWeight: '900',
  },
  body: {
    padding: 18,
  },
  riderBanner: {
    backgroundColor: '#FEF2F2',
    borderRadius: 14,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#FCA5A5',
    marginBottom: 16,
  },
  riderEmoji: {
    fontSize: 32,
    marginRight: 12,
  },
  riderName: {
    fontSize: 16,
    fontWeight: '900',
    color: MostazaColors.rojo,
  },
  riderVehicle: {
    fontSize: 12,
    color: '#4B5563',
    fontWeight: '600',
  },
  riderPlate: {
    fontSize: 11,
    color: '#6B7280',
    marginTop: 2,
  },
  statusBadge: {
    backgroundColor: '#10B981',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusBadgeText: {
    color: '#FFFFFF',
    fontSize: 9,
    fontWeight: '900',
  },
  policyCard: {
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    padding: 14,
    borderWidth: 1,
    borderColor: '#E2E8F0',
    marginBottom: 16,
  },
  policyHeading: {
    fontSize: 12,
    fontWeight: '900',
    color: '#0F172A',
    marginBottom: 8,
    letterSpacing: 0.5,
  },
  policyItem: {
    fontSize: 11,
    color: '#475569',
    lineHeight: 18,
    marginBottom: 6,
  },
  bold: {
    fontWeight: '800',
    color: '#1E293B',
  },
  checklistTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#0F172A',
    marginBottom: 10,
  },
  checkRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#F1F5F9',
  },
  checkBox: {
    width: 22,
    height: 22,
    borderRadius: 6,
    borderWidth: 2,
    borderColor: '#CBD5E1',
    marginRight: 12,
    alignItems: 'center',
    justifyContent: 'center',
    textAlign: 'center',
    fontSize: 14,
    fontWeight: '900',
    color: '#FFFFFF',
    backgroundColor: '#FFFFFF',
    overflow: 'hidden',
  },
  checkBoxActive: {
    backgroundColor: MostazaColors.rojo,
    borderColor: MostazaColors.rojo,
  },
  checkText: {
    fontSize: 12,
    fontWeight: '600',
    color: '#334155',
    flex: 1,
  },
  footer: {
    padding: 16,
    backgroundColor: '#F8FAFC',
    borderTopWidth: 1,
    borderTopColor: '#E2E8F0',
  },
  btnStart: {
    backgroundColor: MostazaColors.rojo,
    paddingVertical: 14,
    borderRadius: 12,
    alignItems: 'center',
  },
  btnStartDisabled: {
    backgroundColor: '#94A3B8',
  },
  btnStartText: {
    color: '#FFFFFF',
    fontSize: 13,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
});
