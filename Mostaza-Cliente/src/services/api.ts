import AsyncStorage from '@react-native-async-storage/async-storage';
import { Config } from '@/constants/Config';
import { Categoria, ConfigPago, Cupon, Mesa, Pedido, PreferenciaMP, Producto, Sucursal, Tracking, Usuario, VerificacionMP } from '@/types/cliente';

const TOKEN_KEY = 'mostaza_cliente_token';
let authToken = '';

const MAPA_TILDES: Record<string, string> = {
  '├í': 'á', '├®': 'é', '├¡': 'í', '├│': 'ó', '├║': 'ú',
  '├▒': 'ñ', '├Ñ': 'Ñ', '├ü': 'Á', '├ë': 'É', '├ì': 'Í',
  '├ô': 'Ó', '├Ü': 'Ú', '├╝': 'ü', '├Ö': 'Ü',
  '┬░': '°', '┬¡': '¡', '┬¿': '¿', '┬║': 'º', '┬¬': 'ª',
  'Ã¡': 'á', 'Ã©': 'é', 'Ã­': 'í', 'Ã³': 'ó', 'Ãº': 'ú',
  'Ã±': 'ñ', 'Ã‘': 'Ñ', 'Ã“': 'Ó', 'Ã‰': 'É', 'Ãš': 'Ú',
  'Ã¼': 'ü', 'Â°': '°', 'Â¡': '¡', 'Â¿': '¿'
};

function limpiarTildes<T>(val: T): T {
  if (typeof val === 'string') {
    let s: string = val;
    for (const [bad, good] of Object.entries(MAPA_TILDES)) {
      if (s.includes(bad)) s = s.split(bad).join(good);
    }
    return s as unknown as T;
  }
  if (Array.isArray(val)) return val.map((item) => limpiarTildes(item)) as unknown as T;
  if (val && typeof val === 'object') {
    const out: Record<string, any> = {};
    for (const [k, v] of Object.entries(val)) out[k] = limpiarTildes(v);
    return out as T;
  }
  return val;
}

type Resp<T = {}> = { ok: boolean; error?: string } & T;

async function call<T = {}>(action: string, opts: { body?: any; query?: Record<string, string | number> } = {}): Promise<Resp<T>> {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 22000);
  try {
    if (!authToken) {
      authToken = (await AsyncStorage.getItem(TOKEN_KEY)) || '';
    }
    const q: Record<string, string> = { action };
    Object.entries(opts.query || {}).forEach(([k, v]) => { q[k] = String(v); });
    const headers: Record<string, string> = { Accept: 'application/json', 'Content-Type': 'application/json' };
    if (authToken) {
      headers.Authorization = `Bearer ${authToken}`;
      headers['X-Auth-Token'] = authToken;
      q.auth = authToken;
    }
    const payload = opts.body && authToken && typeof opts.body === 'object' ? { auth_token: authToken, ...opts.body } : opts.body;
    const res = await fetch(`${Config.API_URL}?${new URLSearchParams(q).toString()}`, {
      method: payload ? 'POST' : 'GET',
      headers,
      body: payload ? JSON.stringify(payload) : undefined,
      signal: controller.signal,
    });
    const data = limpiarTildes(await res.json());
    if (data && (data as any).token) {
      await ApiService.setToken((data as any).token);
    }
    if (res.status === 401 && action !== 'crear_pedido') await ApiService.clearToken();
    return data;
  } catch (e: any) {
    return { ok: false, error: e?.name === 'AbortError' ? 'El servidor tardó demasiado en responder.' : 'No pudimos conectar con el servidor. Revisá tu red y la IP en Config.ts.' } as Resp<T>;
  } finally {
    clearTimeout(timer);
  }
}

export const ApiService = {
  async loadToken() { authToken = (await AsyncStorage.getItem(TOKEN_KEY)) || ''; return authToken; },
  async setToken(t: string) { authToken = t; await AsyncStorage.setItem(TOKEN_KEY, t); },
  async clearToken() { authToken = ''; await AsyncStorage.removeItem(TOKEN_KEY); },
  hasToken: () => !!authToken,

  categorias: () => call<{ categorias: Categoria[] }>('categorias'),
  productos: (cat = '', q = '') => call<{ productos: Producto[] }>('productos', { query: { cat, q } }),
  sucursales: () => call<{ sucursales: Sucursal[] }>('sucursales'),
  mesas: () => call<{ mesas: Mesa[] }>('mesas'),
  cupones: () => call<{ cupones: Cupon[] }>('cupones'),
  configPago: () => call<ConfigPago>('config_pago'),

  registro: (d: { nombre: string; apellido: string; email: string; telefono: string; direccion: string }) => call('registro', { body: d }),
  solicitarCodigo: (email: string) => call<{ mensaje?: string; debug_code?: string }>('solicitar_codigo', { body: { email } }),
  verificarCodigo: (email: string, codigo: string) => call<{ token: string; usuario: Usuario }>('verificar_codigo', { body: { email, codigo } }),
  perfil: () => call<{ usuario: Usuario; total_pedidos: number; total_gastado: number }>('perfil'),
  actualizarPerfil: (d: Partial<Usuario>) => call<{ usuario: Usuario }>('actualizar_perfil', { body: d }),

  validarCupon: (codigo: string, subtotal: number) => call<{ codigo: string; descuento: number; descripcion: string }>('validar_cupon', { body: { codigo, subtotal } }),
  crearPreferenciaMP: (payload: { items: any[]; total: number; tipo_entrega?: string; numero_mesa?: number | null }) =>
    call<PreferenciaMP>('crear_preferencia_mp', { body: payload }),
  verificarPagoMP: (payload: { external_reference?: string; monto_esperado?: number; payment_id?: string }) =>
    call<VerificacionMP>('verificar_pago_mp', { body: payload }),
  crearPedido: (payload: any) =>
    call<{ idpedido: number; numero_pedido_usuario?: number; total: number; puntos_ganados: number; numero_factura?: string; estado_pago?: string; comprobante?: string; token?: string; usuario?: Usuario }>('crear_pedido', { body: payload }),
  misPedidos: () => call<{ pedidos: Pedido[] }>('mis_pedidos'),
  tracking: (id: number) => call<Tracking>('tracking', { query: { id } }),
};
