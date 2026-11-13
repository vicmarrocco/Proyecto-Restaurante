import Constants from 'expo-constants';
import { Platform } from 'react-native';

// Carpeta del proyecto dentro de htdocs (XAMPP). Cambiala si tu carpeta se llama distinto.
const PROJECT_FOLDER = 'PROYECTO_REST';
const FALLBACK_HOST = '192.168.1.237'; // IP de tu PC en la red local

export const getWebRoot = (): string => {
  if (Platform.OS === 'web' && typeof window !== 'undefined' && window.location?.hostname) {
    return `http://${window.location.hostname}/${PROJECT_FOLDER}/mostaza_web`;
  }
  const hostUri = Constants.expoConfig?.hostUri || (Constants as any).manifest?.debuggerHost || '';
  const ip = hostUri ? hostUri.split(':')[0] : '';
  const host = ip && ip !== 'localhost' && ip !== '127.0.0.1' ? ip : FALLBACK_HOST;
  return `http://${host}/${PROJECT_FOLDER}/mostaza_web`;
};

export const Config = {
  APP_NAME: 'Mostaza',
  API_URL: `${getWebRoot()}/api/cliente/index.php`,
  POLLING_MS: 5000,
};
