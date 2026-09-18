"""
Mostaza POS - Configuración General
Terminal de Punto de Venta para Cajeros
"""

# URLs de la API REST central (detecta automáticamente el host local)
API_BASE_URLS = [
    "http://192.168.1.237/PROYECTO_REST/mostaza_web/api",
    "http://localhost/PROYECTO_REST/mostaza_web/api",
    "http://127.0.0.1/PROYECTO_REST/mostaza_web/api",
    "http://localhost/osi/mostaza_web/api",
    "http://127.0.0.1/osi/mostaza_web/api"
]

# Paleta de Colores Mostaza (Énfasis en Blanco Corporativo y Moderno)
COLOR_ROJO = "#A30002"
COLOR_ROJO_OSCURO = "#7A0002"
COLOR_AMARILLO = "#FBB728"
COLOR_AMARILLO_OSCURO = "#D4930A"
COLOR_DARK = "#0F172A"
COLOR_SIDEBAR = "#FFFFFF"
COLOR_CARD = "#FFFFFF"
COLOR_LIGHT_BG = "#F8FAFC"
COLOR_WHITE = "#FFFFFF"
COLOR_BORDER = "#E2E8F0"
COLOR_BORDER_LIGHT = "#F1F5F9"
COLOR_TEXT = "#0F172A"
COLOR_TEXT_MUTED = "#64748B"
COLOR_GREEN = "#10B981"
COLOR_GREEN_BG = "#DCFCE7"
COLOR_BLUE = "#2563EB"
COLOR_BLUE_BG = "#EFF6FF"

# Fuentes
FONT_FAMILY = "Segoe UI"

# ============================================================
# IDENTIFICACIÓN DE TERMINAL POS (CAJA 2)
# ============================================================
TERMINAL_NUMERO = 2
TERMINAL_NOMBRE = "Caja 2 - Express / Salón"
TERMINAL_PV = "0002"
TERMINAL_BADGE_COLOR = "#D4930A"

# ============================================================
# CONFIGURACIÓN DE PANTALLA Y MODO KIOSCO (FULLSCREEN POS)
# ============================================================
POS_INICIAR_MAXIMIZADO = True          # Iniciar maximizado ocupando toda la pantalla (con barra de tareas)
POS_INICIAR_PANTALLA_COMPLETA = False  # Modo Kiosco 100% pantalla completa (oculta barra de tareas y bordes)


# ============================================================
# DATOS BANCARIOS OFICIALES PARA PAGOS Y TRANSFERENCIAS
# ============================================================
BANCO_NOMBRE = "Mercado Pago"
BANCO_TITULAR = "Alberto Emanuel Gomez"
BANCO_CUIT = "20-48001876-2"
BANCO_ALIAS = "gomez16.mp"
BANCO_CVU = "0000003100054955214368"
MP_LINK_PAGO = "https://link.mercadopago.com.ar/gomez16mp"

# ============================================================
# GENERADOR OFICIAL EMVCO / TRANSFERENCIAS 3.0 BCRA
# ============================================================
def emv_tag(tag, val):
    return f"{tag}{len(val):02d}{val}"

def crc16_ccitt(data):
    crc = 0xFFFF
    for ch in data:
        crc ^= (ord(ch) << 8)
        for _ in range(8):
            if crc & 0x8000:
                crc = ((crc << 1) ^ 0x1021) & 0xFFFF
            else:
                crc = (crc << 1) & 0xFFFF
    return f"{crc:04X}"

def generar_qr_emvco(monto=0.0, alias=BANCO_ALIAS, cvu=BANCO_CVU, titular=BANCO_TITULAR, ciudad="FORMOSA"):
    payload = emv_tag("00", "01")
    payload += emv_tag("01", "12" if monto > 0 else "11")
    
    sub00 = emv_tag("00", "COM.MERCADOPAGO")
    cuenta = cvu if cvu else alias.lower().strip()
    sub01 = emv_tag("01", cuenta)
    payload += emv_tag("43", sub00 + sub01)
    
    payload += emv_tag("52", "5814")
    payload += emv_tag("53", "032")
    if monto > 0:
        payload += emv_tag("54", f"{monto:.2f}")
    payload += emv_tag("58", "AR")
    
    nom = "".join(c for c in titular.upper() if c.isalnum() or c == " ")[:25] or "MOSTAZA OFICIAL"
    payload += emv_tag("59", nom)
    
    ciu = "".join(c for c in ciudad.upper() if c.isalnum() or c == " ")[:15] or "FORMOSA"
    payload += emv_tag("60", ciu)
    
    payload += emv_tag("62", emv_tag("01", "MOSTAZA"))
    
    with_tag63 = payload + "6304"
    crc = crc16_ccitt(with_tag63)
    return with_tag63 + crc

import urllib.request
import urllib.parse

def obtener_qr_image_bytes(monto=0.0):
    """
    Genera el payload EMVCo Transferencias 3.0 para Mercado Pago
    y descarga la imagen PNG para visualización directa en Tkinter PhotoImage.
    """
    try:
        payload = generar_qr_emvco(monto=monto)
        encoded = urllib.parse.quote(payload)
        url = f"https://api.qrserver.com/v1/create-qr-code/?size=160x160&margin=6&data={encoded}"
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"})
        with urllib.request.urlopen(req, timeout=5) as resp:
            return resp.read()
    except Exception as e:
        print(f"Error al descargar imagen QR: {e}")
        return None

