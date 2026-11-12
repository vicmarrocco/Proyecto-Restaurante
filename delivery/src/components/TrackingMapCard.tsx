// ============================================================
// components/TrackingMapCard.tsx
// Tarjeta de Seguimiento de Dirección en Tiempo Real y Navegación GPS
// ============================================================
import React, { useState } from 'react';
import {
  View,
  Text,
  StyleSheet,
  TouchableOpacity,
  Linking,
  Platform,
  Alert,
} from 'react-native';
import { PedidoDelivery } from '@/types/delivery';
import { MostazaColors } from '@/constants/Colors';

interface TrackingMapCardProps {
  pedido: PedidoDelivery;
  modoHistorial?: boolean;
  titulo?: string;
  subtitulo?: string;
  iniciarExpandido?: boolean;
}

export const TrackingMapCard: React.FC<TrackingMapCardProps> = ({
  pedido,
  modoHistorial = false,
  titulo,
  subtitulo,
  iniciarExpandido = true,
}) => {
  const [mapaExpandido, setMapaExpandido] = useState(iniciarExpandido);

  const sucursalLat = pedido.sucursal_lat ?? -26.185200;
  const sucursalLng = pedido.sucursal_lng ?? -58.175600;
  const sucursalDir = pedido.sucursal_direccion ?? 'Mostaza Formosa Centro (Av. 25 de Mayo 150)';

  const clienteLat = pedido.latitud ?? -26.185000;
  const clienteLng = pedido.longitud ?? -58.176000;
  const tieneGpsExacto = Boolean(pedido.latitud && pedido.longitud);

  // Destino para Google Maps
  const destinoUrlParam = tieneGpsExacto
    ? `${pedido.latitud},${pedido.longitud}`
    : encodeURIComponent(`${pedido.cliente_direccion}, Formosa, Argentina`);

  const abrirGoogleMaps = () => {
    const origin = `${sucursalLat},${sucursalLng}`;
    const destination = tieneGpsExacto
      ? `${pedido.latitud},${pedido.longitud}`
      : encodeURIComponent(`${pedido.cliente_direccion}, Formosa, Argentina`);
    const url = `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${destination}&travelmode=driving`;
    Linking.openURL(url).catch(() => {
      Alert.alert('Error', 'No se pudo abrir Google Maps.');
    });
  };

  const llamarCliente = () => {
    const cleanPhone = (pedido.cliente_telefono || '').replace(/[^0-9]/g, '');
    if (!cleanPhone) {
      Alert.alert('Aviso', 'El cliente no especificó número de teléfono.');
      return;
    }
    Linking.openURL(`tel:${cleanPhone}`).catch(() => {
      Alert.alert('Error', 'No se pudo iniciar la llamada.');
    });
  };

  const enviarWhatsApp = () => {
    const cleanPhone = (pedido.cliente_telefono || '').replace(/[^0-9]/g, '');
    if (!cleanPhone) {
      Alert.alert('Aviso', 'El cliente no especificó número de teléfono.');
      return;
    }
    let numWsp = cleanPhone;
    if (!numWsp.startsWith('54') && numWsp.length >= 10) {
      numWsp = `549${numWsp}`;
    }
    const numPed = pedido.numero_pedido_usuario ? `#${pedido.numero_pedido_usuario}` : `#${pedido.idpedido}`;
    const mensaje = encodeURIComponent(
      `¡Hola ${pedido.cliente_nombre}! 🛵 Soy tu repartidor de Mostaza. Estoy en camino con tu pedido ${numPed} hacia ${pedido.cliente_direccion}. ¡Llego en aprox. ${pedido.tiempo_estimado_min || 15} minutos! 🍔🍟`
    );
    const url = `https://wa.me/${numWsp}?text=${mensaje}`;
    Linking.openURL(url).catch(() => {
      Alert.alert('Error', 'No se pudo abrir WhatsApp.');
    });
  };

  // URL para el mapa embebido interactivo de OpenStreetMap
  const bboxMinLat = Math.min(sucursalLat, clienteLat) - 0.005;
  const bboxMinLng = Math.min(sucursalLng, clienteLng) - 0.005;
  const bboxMaxLat = Math.max(sucursalLat, clienteLat) + 0.005;
  const bboxMaxLng = Math.max(sucursalLng, clienteLng) + 0.005;
  const mapEmbedUrl = `https://www.openstreetmap.org/export/embed.html?bbox=${bboxMinLng}%2C${bboxMinLat}%2C${bboxMaxLng}%2C${bboxMaxLat}&layer=mapnik&marker=${clienteLat}%2C${clienteLng}`;

  return (
    <View style={styles.container}>
      {/* Encabezado del Seguimiento */}
      <View style={styles.header}>
        <View style={styles.titleRow}>
          <Text style={styles.headerIcon}>{modoHistorial ? '🗺️' : '🧭'}</Text>
          <View>
            <Text style={styles.headerTitle}>
              {titulo || (modoHistorial ? 'RUTA DEL ENVÍO REALIZADO' : 'SEGUIMIENTO DE DIRECCIÓN')}
            </Text>
            <Text style={styles.headerSubtitle}>
              {subtitulo || (modoHistorial ? 'Ruta de geolocalización y mapa' : 'Ruta en tiempo real desde la sucursal')}
            </Text>
          </View>
        </View>

        <TouchableOpacity
          style={styles.btnToggleMap}
          onPress={() => setMapaExpandido(!mapaExpandido)}
          activeOpacity={0.7}>
          <Text style={styles.btnToggleText}>
            {mapaExpandido ? '▲ Ocultar Mapa' : '▼ Ver Mapa'}
          </Text>
        </TouchableOpacity>
      </View>

      {/* Tarjeta de Puntos de la Ruta */}
      <View style={styles.routeCard}>
        {/* Origen: Sucursal */}
        <View style={styles.pointRow}>
          <View style={styles.pointIndicator}>
            <View style={[styles.dot, styles.dotOrigin]} />
            <View style={styles.line} />
          </View>
          <View style={styles.pointDetails}>
            <Text style={styles.pointLabel}>DESPACHO (ORIGEN):</Text>
            <Text style={styles.pointText}>{sucursalDir}</Text>
          </View>
        </View>

        {/* Destino: Cliente */}
        <View style={styles.pointRow}>
          <View style={styles.pointIndicator}>
            <View style={[styles.dot, styles.dotDestination]} />
          </View>
          <View style={styles.pointDetails}>
            <Text style={styles.pointLabel}>ENTREGA (DESTINO):</Text>
            <Text style={styles.destinationText}>{pedido.cliente_direccion}</Text>
            {pedido.referencia_entrega ? (
              <View style={styles.refBox}>
                <Text style={styles.refText}>🚪 Ref: {pedido.referencia_entrega}</Text>
              </View>
            ) : null}
            {tieneGpsExacto ? (
              <Text style={styles.coordsText}>
                📍 Coordenadas fijadas: {Number(pedido.latitud || 0).toFixed(5)}, {Number(pedido.longitud || 0).toFixed(5)}
              </Text>
            ) : (
              <Text style={styles.coordsText}>📍 Dirección postal en Formosa Capital</Text>
            )}
          </View>
        </View>

        {/* Métricas de Distancia y Tiempo */}
        <View style={styles.metricsBar}>
          <View style={styles.metricItem}>
            <Text style={styles.metricEmoji}>📏</Text>
            <View>
              <Text style={styles.metricLabel}>DISTANCIA</Text>
              <Text style={styles.metricValue}>{pedido.distancia_formateada || '2.4 km'}</Text>
            </View>
          </View>

          <View style={styles.metricDivider} />

          <View style={styles.metricItem}>
            <Text style={styles.metricEmoji}>⏱️</Text>
            <View>
              <Text style={styles.metricLabel}>TIEMPO ESTIMADO</Text>
              <Text style={styles.metricValue}>~{pedido.tiempo_estimado_min || 15} min</Text>
            </View>
          </View>
        </View>
      </View>

      {/* Mapa Visual Embebido */}
      {mapaExpandido && (
        <View style={styles.mapFrameContainer}>
          {Platform.OS === 'web' ? (
            <iframe
              src={mapEmbedUrl}
              style={{
                width: '100%',
                height: 180,
                border: 'none',
                borderRadius: 12,
              }}
              title="Mapa de Entrega"
            />
          ) : (
            <View style={styles.mobileMapBox}>
              <Text style={styles.mobileMapIcon}>🗺️</Text>
              <Text style={styles.mobileMapTitle}>Mapa Satelital de Ruta</Text>
              <Text style={styles.mobileMapDesc}>
                Presioná "Google Maps GPS" para activar la guía por voz y navegación paso a paso.
              </Text>
            </View>
          )}
        </View>
      )}

      {/* Botón Principal Exclusivo de Navegación Google Maps GPS */}
      <TouchableOpacity
        style={styles.btnGoogleMapsFull}
        onPress={abrirGoogleMaps}
        activeOpacity={0.85}>
        <View style={styles.btnGpsLeft}>
          <View style={styles.btnGpsIconBox}>
            <Text style={styles.btnGpsEmoji}>🗺️</Text>
          </View>
          <View style={styles.btnGpsTextCol}>
            <Text style={styles.btnGpsTitle}>NAVEGAR CON GOOGLE MAPS GPS</Text>
            <Text style={styles.btnGpsSubtitle}>
              Ruta en vivo desde sucursal · {pedido.distancia_formateada || '2.4 km'} (~{pedido.tiempo_estimado_min || 15} min)
            </Text>
          </View>
        </View>
        <Text style={styles.btnGpsArrow}>➔</Text>
      </TouchableOpacity>

      {/* Botones de Comunicación con el Cliente */}
      <View style={styles.contactRow}>
        <TouchableOpacity
          style={[styles.btnContact, styles.btnCall]}
          onPress={llamarCliente}
          activeOpacity={0.8}>
          <Text style={styles.btnContactEmoji}>📞</Text>
          <Text style={styles.btnContactText}>Llamar al Cliente</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={[styles.btnContact, styles.btnWsp]}
          onPress={enviarWhatsApp}
          activeOpacity={0.8}>
          <Text style={styles.btnContactEmoji}>💬</Text>
          <Text style={styles.btnContactText}>WhatsApp</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    backgroundColor: '#F8FAFC',
    borderRadius: 14,
    padding: 12,
    marginTop: 8,
    marginBottom: 8,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 10,
  },
  titleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
  },
  headerIcon: {
    fontSize: 18,
  },
  headerTitle: {
    fontSize: 12,
    fontWeight: '900',
    color: '#0F172A',
    letterSpacing: 0.5,
  },
  headerSubtitle: {
    fontSize: 10,
    color: '#64748B',
    fontWeight: '600',
  },
  btnToggleMap: {
    backgroundColor: '#E2E8F0',
    paddingVertical: 4,
    paddingHorizontal: 8,
    borderRadius: 6,
  },
  btnToggleText: {
    fontSize: 10,
    fontWeight: '700',
    color: '#334155',
  },
  routeCard: {
    backgroundColor: '#FFFFFF',
    borderRadius: 10,
    padding: 12,
    borderWidth: 1,
    borderColor: '#E2E8F0',
  },
  pointRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    gap: 10,
  },
  pointIndicator: {
    alignItems: 'center',
    width: 14,
    paddingTop: 4,
  },
  dot: {
    width: 12,
    height: 12,
    borderRadius: 6,
  },
  dotOrigin: {
    backgroundColor: '#10B981',
    borderWidth: 2,
    borderColor: '#D1FAE5',
  },
  dotDestination: {
    backgroundColor: '#EF4444',
    borderWidth: 2,
    borderColor: '#FEE2E2',
  },
  line: {
    width: 2,
    height: 28,
    backgroundColor: '#CBD5E1',
    marginVertical: 2,
  },
  pointDetails: {
    flex: 1,
    paddingBottom: 8,
  },
  pointLabel: {
    fontSize: 9,
    fontWeight: '800',
    color: '#94A3B8',
    letterSpacing: 0.5,
  },
  pointText: {
    fontSize: 12,
    fontWeight: '700',
    color: '#334155',
    marginTop: 1,
  },
  destinationText: {
    fontSize: 13,
    fontWeight: '800',
    color: '#0F172A',
    marginTop: 1,
  },
  refBox: {
    backgroundColor: '#FEF3C7',
    paddingHorizontal: 6,
    paddingVertical: 3,
    borderRadius: 6,
    marginTop: 4,
    alignSelf: 'flex-start',
  },
  refText: {
    fontSize: 11,
    fontWeight: '700',
    color: '#92400E',
  },
  coordsText: {
    fontSize: 10,
    color: '#64748B',
    marginTop: 3,
  },
  metricsBar: {
    flexDirection: 'row',
    backgroundColor: '#F1F5F9',
    borderRadius: 8,
    paddingVertical: 8,
    paddingHorizontal: 12,
    marginTop: 8,
    alignItems: 'center',
  },
  metricItem: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6,
  },
  metricEmoji: {
    fontSize: 16,
  },
  metricLabel: {
    fontSize: 8,
    fontWeight: '800',
    color: '#64748B',
  },
  metricValue: {
    fontSize: 12,
    fontWeight: '900',
    color: '#0F172A',
  },
  metricDivider: {
    width: 1,
    height: 24,
    backgroundColor: '#CBD5E1',
    marginHorizontal: 10,
  },
  mapFrameContainer: {
    marginTop: 10,
    borderRadius: 12,
    overflow: 'hidden',
    borderWidth: 1,
    borderColor: '#CBD5E1',
  },
  mobileMapBox: {
    backgroundColor: '#EFF6FF',
    padding: 16,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: 12,
  },
  mobileMapIcon: {
    fontSize: 28,
    marginBottom: 4,
  },
  mobileMapTitle: {
    fontSize: 13,
    fontWeight: '800',
    color: '#1E40AF',
  },
  mobileMapDesc: {
    fontSize: 11,
    color: '#475569',
    textAlign: 'center',
    marginTop: 4,
  },
  btnGoogleMapsFull: {
    backgroundColor: '#1D4ED8',
    borderRadius: 12,
    paddingVertical: 12,
    paddingHorizontal: 14,
    marginTop: 10,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    elevation: 3,
    shadowColor: '#1D4ED8',
    shadowOpacity: 0.25,
    shadowOffset: { width: 0, height: 3 },
    shadowRadius: 6,
  },
  btnGpsLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    flex: 1,
  },
  btnGpsIconBox: {
    width: 38,
    height: 38,
    borderRadius: 10,
    backgroundColor: '#3B82F6',
    alignItems: 'center',
    justifyContent: 'center',
  },
  btnGpsEmoji: {
    fontSize: 20,
  },
  btnGpsTextCol: {
    flex: 1,
  },
  btnGpsTitle: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '900',
    letterSpacing: 0.5,
  },
  btnGpsSubtitle: {
    color: '#BFDBFE',
    fontSize: 10,
    fontWeight: '600',
    marginTop: 2,
  },
  btnGpsArrow: {
    color: '#FFFFFF',
    fontSize: 18,
    fontWeight: '900',
    marginLeft: 6,
  },
  contactRow: {
    flexDirection: 'row',
    gap: 8,
    marginTop: 8,
  },
  btnContact: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    paddingVertical: 9,
    paddingHorizontal: 10,
    borderRadius: 10,
    gap: 6,
  },
  btnCall: {
    backgroundColor: '#0F172A',
  },
  btnWsp: {
    backgroundColor: '#16A34A',
  },
  btnContactEmoji: {
    fontSize: 14,
  },
  btnContactText: {
    color: '#FFFFFF',
    fontSize: 12,
    fontWeight: '800',
  },
});
