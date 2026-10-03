
import json
import urllib.request
import urllib.parse
from config import API_BASE_URLS, TERMINAL_NUMERO, TERMINAL_NOMBRE

class APIClient:
    def __init__(self):
        self.active_base_url = None
        self._detect_working_url()

    def _detect_working_url(self):
        """Prueba las URLs base disponibles y fija la primera que responda."""
        for url in API_BASE_URLS:
            try:
                test_url = f"{url}/pos/catalogo.php"
                req = urllib.request.Request(test_url, headers={'User-Agent': 'MostazaDesktopPOS/1.0'})
                with urllib.request.urlopen(req, timeout=2) as response:
                    if response.status == 200:
                        self.active_base_url = url
                        return
            except Exception:
                continue
        # Default si no se pudo conectar
        self.active_base_url = API_BASE_URLS[0]

    def _post(self, endpoint, data):
        """Realiza una petición POST enviando JSON."""
        url = f"{self.active_base_url}{endpoint}"
        payload = json.dumps(data).encode('utf-8')
        req = urllib.request.Request(
            url,
            data=payload,
            headers={
                'Content-Type': 'application/json',
                'User-Agent': 'MostazaDesktopPOS/1.0'
            },
            method='POST'
        )
        try:
            with urllib.request.urlopen(req, timeout=8) as response:
                body = response.read().decode('utf-8-sig')
                return json.loads(body)
        except Exception as e:
            return {'ok': False, 'error': f"Error de conexión con la API: {str(e)}"}

    def _get(self, endpoint, params=None):
        """Realiza una petición GET."""
        url = f"{self.active_base_url}{endpoint}"
        if params:
            query = urllib.parse.urlencode(params)
            url = f"{url}?{query}"
        req = urllib.request.Request(url, headers={'User-Agent': 'MostazaDesktopPOS/1.0'})
        try:
            with urllib.request.urlopen(req, timeout=8) as response:
                body = response.read().decode('utf-8-sig')
                return json.loads(body)
        except Exception as e:
            return {'ok': False, 'error': f"Error de conexión con la API: {str(e)}"}

    # ------------------------------------------------------------
    # MÉTODOS DE AUTENTICACIÓN Y CONTROL DE TURNOS
    # ------------------------------------------------------------
    def solicitar_codigo_gmail(self, email):
        """Envía el código de 6 dígitos por Gmail al empleado validando rol de Cajero."""
        return self._post("/auth/solicitar_codigo.php", {"email": email, "app": "cajero"})

    def verificar_codigo_gmail(self, email, codigo):
        """Valida el código de 6 dígitos, registra la entrada y devuelve datos del turno."""
        return self._post("/auth/verificar_codigo.php", {"email": email, "codigo": codigo, "app": "cajero"})

    def cerrar_turno(self, idempleado, idregistro_turno=None, observaciones=""):
        """Registra la hora de salida y finaliza el turno del empleado."""
        payload = {
            "idempleado": idempleado,
            "idregistro_turno": idregistro_turno,
            "observaciones": observaciones or f"Turno finalizado desde {TERMINAL_NOMBRE}"
        }
        return self._post("/auth/cerrar_turno.php", payload)

    def obtener_historial_turnos(self, idempleado=None):
        """Consulta el registro histórico de asistencia y turnos."""
        params = {"idempleado": idempleado} if idempleado else {}
        return self._get("/auth/historial_turnos.php", params)

    # ------------------------------------------------------------
    # MÉTODOS DEL POS Y FACTURACIÓN
    # ------------------------------------------------------------
    def obtener_catalogo(self):
        """Obtiene las categorías y productos activos."""
        return self._get("/pos/catalogo.php")

    def crear_venta(self, payload):
        """Registra y cobra una venta desde la terminal POS."""
        if "numero_caja" not in payload:
            payload["numero_caja"] = TERMINAL_NUMERO
        if "terminal_nombre" not in payload:
            payload["terminal_nombre"] = TERMINAL_NOMBRE
        return self._post("/pos/crear_venta.php", payload)

    def obtener_mesas(self):
        """Obtiene las secciones y estado en tiempo real de las mesas del salón."""
        return self._get("/pos/mesas.php")

    def obtener_pedidos_hoy(self, idempleado=None, fecha=None):
        """Obtiene el listado de pedidos y facturas de la jornada."""
        params = {}
        if idempleado: params["idempleado"] = idempleado
        if fecha: params["fecha"] = fecha
        return self._get("/pos/pedidos.php", params)

    def obtener_factura(self, idfactura=None, idpedido=None):
        """Obtiene el detalle fiscal completo de una factura."""
        params = {}
        if idfactura: params["idfactura"] = idfactura
        if idpedido: params["idpedido"] = idpedido
        return self._get("/pos/factura.php", params)

    def cancelar_pedido(self, idpedido, motivo, cancelado_por="Cajero"):
        """Cancela una orden activa registrando el motivo en el sistema (R21)."""
        payload = {
            "idpedido": idpedido,
            "motivo": motivo or f"Cancelado desde {TERMINAL_NOMBRE}",
            "cancelado_por": cancelado_por
        }
        return self._post("/pos/pedidos.php", payload)

    def obtener_pedidos_takeaway(self):
        """Obtiene las órdenes activas y completadas de Take Away / Mostrador."""
        return self._get("/pos/pedidos.php", {"fecha": "hoy", "tipo_entrega": "takeaway"})

    def cambiar_estado_pedido(self, idpedido, nuevo_estado, empleado_nombre="Cajero", idempleado=None, rol="Cajero", detalles=""):
        """Actualiza el estado de una orden (ej: Entregado en mostrador Take Away)."""
        payload = {
            "accion": "cambiar_estado",
            "idpedido": idpedido,
            "nuevo_estado": nuevo_estado,
            "empleado_nombre": empleado_nombre,
            "idempleado": idempleado,
            "rol": rol,
            "detalles": detalles or f"Comanda {nuevo_estado} en mostrador {TERMINAL_NOMBRE}"
        }
        return self._post("/pos/pedidos.php", payload)

    def obtener_resumen_turno(self, idempleado, idregistro_turno=None):
        """Obtiene las métricas de rendimiento y recaudación del cajero en su turno."""
        params = {"idempleado": idempleado}
        if idregistro_turno:
            params["idregistro_turno"] = idregistro_turno
        return self._get("/empleados/resumen_turno.php", params)

    # ------------------------------------------------------------
    # MÉTODOS DE CAJA Y ARQUEO MULTI-TERMINAL
    # ------------------------------------------------------------
    def obtener_estado_caja(self, numero_caja=None):
        """Obtiene el estado de la caja registradora activa de esta terminal."""
        caja_num = numero_caja if numero_caja is not None else TERMINAL_NUMERO
        return self._get("/caja/estado.php", {"numero_caja": caja_num})

    def movimiento_caja(self, accion, payload):
        """Abre o cierra turnos de caja, registra arqueo físico y sangrías."""
        data = {
            "accion": accion,
            "numero_caja": TERMINAL_NUMERO,
            "terminal_nombre": TERMINAL_NOMBRE,
            **payload
        }
        return self._post("/caja/movimiento.php", data)

    def registrar_retiro_seguridad(self, monto, motivo="", idarqueo=None, cajero="", idempleado=None):
        """Registra una sangría / retiro de seguridad de la gaveta hacia tesorería."""
        payload = {
            "monto": monto,
            "motivo": motivo or "Retiro de seguridad / Excedente a caja fuerte",
            "idarqueo": idarqueo,
            "cajero": cajero,
            "idempleado": idempleado
        }
        return self.movimiento_caja("retiro_efectivo", payload)

    def cierre_x(self, idarqueo=None):
        """Genera un Arqueo Parcial Ciego (Cierre X) sin bloquear la caja."""
        return self.movimiento_caja("cierre_x", {"idarqueo": idarqueo} if idarqueo else {})

    def obtener_informe_cierre(self, idarqueo):
        """Obtiene el informe oficial de cierre fiscal Z de un arqueo."""
        return self._get("/caja/movimiento.php", {"accion": "informe_cierre", "idarqueo": idarqueo})

    def obtener_historial_cajas(self, limite=30, numero_caja=None):
        """Obtiene el historial de turnos y arqueos de caja."""
        params = {"limite": limite}
        if numero_caja is not None:
            params["numero_caja"] = numero_caja
        return self._get("/caja/historial.php", params)

    # ------------------------------------------------------------
    # CLIENTES Y CLUB MOSTAZA
    # ------------------------------------------------------------
    def buscar_cliente(self, query):
        """Busca cliente por DNI o Email."""
        return self._get("/clientes/buscar.php", {"q": query})

# Instancia global del cliente API
api = APIClient()

