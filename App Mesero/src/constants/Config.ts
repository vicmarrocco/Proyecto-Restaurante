import { Platform } from 'react-native';
import Constants from 'expo-constants';

export const getDefaultApiHost = (): string => {
  if (Platform.OS === 'web') {
    return 'http://localhost/PROYECTO_REST/mostaza_web/api';
  }

  const hostUri = Constants.expoConfig?.hostUri || (Constants as any).manifest?.debuggerHost || '';
  if (hostUri) {
    const ip = hostUri.split(':')[0];
    if (ip && ip !== 'localhost' && ip !== '127.0.0.1') {
      return `http://${ip}/PROYECTO_REST/mostaza_web/api`;
    }
  }

  return 'http://10.0.2.145/PROYECTO_REST/mostaza_web/api';
};

export const Config = {
  APP_NAME: 'Mostaza Mesero',
  VERSION: '1.0.0',
  DEFAULT_API_URL: getDefaultApiHost(),
  POLLING_INTERVAL_MS: 5000,
};
