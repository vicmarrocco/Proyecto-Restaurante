import os
import re
import socket
import sys

ROOT = r"c:\xampp\htdocs\PROYECTO_REST"

def obtener_ip_activa():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"

def actualizar_ips(nueva_ip=None):
    if not nueva_ip:
        nueva_ip = obtener_ip_activa()

    print(f"\n========================================================")
    print(f"  SINCRONIZADOR AUTOMATICO DE RED - MOSTAZA REST")
    print(f"  IP Activa Detectada en esta red: {nueva_ip}")
    print(f"========================================================\n")

    if nueva_ip.startswith("127.") or nueva_ip == "0.0.0.0":
        print("[AVISO] No se detecto conexion a red LAN activa. Usando localhost.")
        return nueva_ip

    archivos_clave = [
        os.path.join(ROOT, "App Mesero", "src", "constants", "Config.ts"),
        os.path.join(ROOT, "App Cocina", "src", "constants", "Config.ts"),
        os.path.join(ROOT, "delivery", "src", "constants", "Config.ts"),
        os.path.join(ROOT, "Mostaza-Cliente", "src", "constants", "Config.ts"),
        os.path.join(ROOT, "App Cajero", "config.py"),
        os.path.join(ROOT, "App Cajero 2", "config.py"),
        os.path.join(ROOT, "App Mesero", "ejecutar_mesero.bat"),
        os.path.join(ROOT, "App Cocina", "ejecutar_cocina.bat"),
        os.path.join(ROOT, "App Cocina", "iniciar_cocina_expo.bat"),
        os.path.join(ROOT, "delivery", "ejecutar_delivery.bat"),
        os.path.join(ROOT, "Mostaza-Cliente", "ejecutar_cliente.bat"),
        os.path.join(ROOT, "admin", "cocina.php"),
    ]

    patron_ip = re.compile(r"(?:192\.168\.\d{1,3}\.\d{1,3}|10\.\d{1,3}\.\d{1,3}\.\d{1,3}|172\.(?:1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3})")
    modificados = 0

    for path in archivos_clave:
        if not os.path.isfile(path):
            continue
        try:
            with open(path, "rb") as f:
                content = f.read().decode("utf-8")
        except Exception as e:
            print(f"[!] Error leyendo {path}: {e}")
            continue

        coincidencias = set(patron_ip.findall(content))
        # Excluir la IP del router 192.168.x.1 si no corresponde
        coincidencias_a_cambiar = [ip for ip in coincidencias if not ip.endswith(".1") and ip != nueva_ip]

        if coincidencias_a_cambiar:
            nuevo_contenido = content
            for ip_vieja in coincidencias_a_cambiar:
                nuevo_contenido = nuevo_contenido.replace(ip_vieja, nueva_ip)

            if nuevo_contenido != content:
                with open(path, "wb") as f:
                    f.write(nuevo_contenido.encode("utf-8"))
                rel = os.path.relpath(path, ROOT)
                print(f"[OK] Actualizado {rel} -> {nueva_ip}")
                modificados += 1
        else:
            rel = os.path.relpath(path, ROOT)
            print(f"[--] Ya al dia: {rel}")

    print(f"\n[FIN] {modificados} archivos actualizados con la IP {nueva_ip}.\n")
    return nueva_ip

if __name__ == "__main__":
    ip_param = sys.argv[1] if len(sys.argv) > 1 else None
    actualizar_ips(ip_param)
