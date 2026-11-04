# 🍔 Mostaza POS - Terminal de Punto de Venta
**Sistema Oficial de Facturación y Comandas para Cajeros de Mostaza**

Esta es la aplicación de punto de venta para cajeros, desarrollada en **Python** y conectada en tiempo real a la **API REST Central** del restaurante.

---

## 🚀 Cómo Ejecutar la Aplicación

1. **Asegúrate de que Apache y MySQL estén iniciados en XAMPP**.
2. Haz doble clic en el archivo:
   👉 **`ejecutar_pos.bat`**  
   *(O abre una terminal en esta carpeta y ejecuta: `python main.py`)*

---

## 🔑 Identificación de Cajero

1. **Paso 1**: Ingresa tu correo registrado como cajero (ej: `applegomez64@gmail.com`).
2. **Paso 2**: Recibirás un código token de 6 dígitos en tu bandeja de entrada de Gmail (enviado vía SMTP).
3. **Paso 3**: Ingresa el PIN en el teclado numérico de la pantalla (o con el teclado físico) y presiona **Enter** para iniciar tu turno.

---

## ✨ Funcionalidades Principales

- **Catálogo Rápido**:
  - Filtro instantáneo por texto (`F2`).
  - Navegación por categorías: *Exclusivos, Mega Combos, Pollo, Ensaladas, Cajitas, Postres & Café*.
  - Selección de modificadores y notas para cocina (*"Sin cebolla", "Extra cheddar", etc.*).
- **Comanda en Vivo**:
  - 2 modalidades físicas: **Mostrador (Take Away)** y **Salón (Mesas 1 a 12)**.
  - Búsqueda de clientes por DNI/Email para acumular y consultar **Puntos del Club Mostaza**.
  - Modificación de cantidades (`+` / `-`) y eliminación de ítems.
- **Cobro Rápido Multicanal (F10 / Enter)**:
  - **Efectivo**: Calculadora con botones de billetes rápidos ($2.000, $5.000, $10.000, $20.000, $50.000 o Exacto) y **cálculo automático instantáneo del vuelto**.
  - **Tarjeta Débito/Crédito POS**.
  - **Mercado Pago QR Dinámico**.
  - **Transferencia Bancaria** (con CUIT y Alias).
- **Emisión de Comprobante / Ticket**:
  - Generación de comanda y tique fiscal con número de comanda, desglose y datos de la empresa.
- **Arqueo y Cierre de Caja**:
  - Conteo físico de gaveta vs total virtual del sistema, cálculo de diferencia (sobrante/faltante) y cierre de turno.
- **Historial de Tickets de Hoy**:
  - Lista de comprobantes emitidos en el día con reimpresión en 1 clic.

---

## 🛠️ Arquitectura Técnica

- **Lenguaje GUI**: Python 3 con interfaz gráfica Tkinter de alto rendimiento.
- **Comunicación**: HTTP JSON REST API (`/api/auth/`, `/api/pos/`, `/api/caja/`, `/api/clientes/`).
- **Base de Datos**: MySQL `rest`.
