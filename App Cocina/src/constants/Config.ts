import { Platform } from 'react-native';
import Constants from 'expo-constants';

export const getDefaultApiHost = (): string => {
  const hostUri = Constants.expoConfig?.hostUri || (Constants as any).manifest?.debuggerHost || '';
  if (hostUri) {
    const ip = hostUri.split(':')[0];
    if (ip && ip !== 'localhost' && ip !== '127.0.0.1') {
      return `http://${ip}/PROYECTO_REST/mostaza_web/api`;
    }
  }

  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location) {
    const host = window.location.hostname;
    if (host && host !== 'localhost' && host !== '127.0.0.1') {
      return `http://${host}/PROYECTO_REST/mostaza_web/api`;
    }
  }

  return 'http://10.0.5.77/PROYECTO_REST/mostaza_web/api';
};

export const Config = {
  APP_NAME: 'Mostaza Cocina KDS',
  VERSION: '1.0.0',
  DEFAULT_API_URL: getDefaultApiHost(),
  POLLING_INTERVAL_MS: 3500,
};
