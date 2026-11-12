import { Platform } from 'react-native';
import Constants from 'expo-constants';

const PROJECT_FOLDER = 'Proyecto-Restaurante';
const FALLBACK_LAN_IP = '192.168.0.106';

export const getDefaultApiHost = (): string => {
  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.hostname) {
    const host = window.location.hostname;
    return `http://${host}/${PROJECT_FOLDER}/mostaza_web/api`;
  }

  const hostUri = Constants.expoConfig?.hostUri || (Constants as any).manifest?.debuggerHost || '';
  if (hostUri) {
    const ip = hostUri.split(':')[0];
    if (ip && ip !== 'localhost' && ip !== '127.0.0.1') {
      return `http://${ip}/${PROJECT_FOLDER}/mostaza_web/api`;
    }
  }

  return `http://${FALLBACK_LAN_IP}/${PROJECT_FOLDER}/mostaza_web/api`;
};

export const Config = {
  APP_NAME: 'Mostaza Mesero',
  VERSION: '1.0.0',
  DEFAULT_API_URL: getDefaultApiHost(),
  POLLING_INTERVAL_MS: 3500,
};
