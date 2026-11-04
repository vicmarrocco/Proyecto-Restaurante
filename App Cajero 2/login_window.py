"""
Mostaza POS - Ventana de Identificación
Autenticación de Cajeros y Empleados mediante PIN / Código de seguridad.
Cumple con Requerimientos R13, R14, R15, R16, R17, R18, R19.
"""
import tkinter as tk
from tkinter import ttk, messagebox
import threading
from config import *
from api_client import api

class LoginWindow:
    def __init__(self, root, on_login_success):
        self.root = root
        self.on_login_success = on_login_success

        # Asegurar estado de ventana normal para el diálogo de login
        try:
            self.root.attributes("-fullscreen", False)
        except Exception:
            pass
        try:
            self.root.state("normal")
        except Exception:
            pass

        self.root.title(f"Mostaza POS · Terminal de Cajero 2 ({TERMINAL_NOMBRE})")
        self.root.geometry("540x720")
        self.root.resizable(False, False)
        self.root.configure(bg=COLOR_LIGHT_BG)

        # Centrar ventana en pantalla
        self._center_window()

        self.email_actual = ""
        self.paso = "email" # "email" o "codigo"

        self._build_ui()

    def _center_window(self):
        self.root.update_idletasks()
        width = 540
        height = 720
        x = (self.root.winfo_screenwidth() // 2) - (width // 2)
        y = (self.root.winfo_screenheight() // 2) - (height // 2)
        self.root.geometry(f"{width}x{height}+{x}+{y}")

    def _build_ui(self):
        # Limpiar
        for w in self.root.winfo_children():
            w.destroy()

        # Contenedor Tarjeta Blanca Central
        self.card = tk.Frame(self.root, bg=COLOR_WHITE, padx=30, pady=24, relief="solid", bd=1, highlightbackground=COLOR_BORDER, highlightthickness=1)
        self.card.place(relx=0.5, rely=0.5, anchor="center", width=480, height=660)

        # Encabezado con Marca (R13: Ver Pantalla de Bienvenida)
        header_frame = tk.Frame(self.card, bg=COLOR_WHITE)
        header_frame.pack(fill="x", pady=(0, 10))

        lbl_logo_icon = tk.Label(
            header_frame,
            text="🍔",
            font=(FONT_FAMILY, 24),
            bg=COLOR_WHITE
        )
        lbl_logo_icon.pack()

        lbl_brand = tk.Label(
            header_frame,
            text="MOSTAZA POS",
            font=(FONT_FAMILY, 20, "bold"),
            fg=COLOR_ROJO,
            bg=COLOR_WHITE
        )
        lbl_brand.pack()

        lbl_sub = tk.Label(
            header_frame,
            text="Terminal de Cajero y Salón · Sucursal Formosa",
            font=(FONT_FAMILY, 9),
            fg=COLOR_TEXT_MUTED,
            bg=COLOR_WHITE
        )
        lbl_sub.pack(pady=(2, 0))

        # Separador visual
        sep = tk.Frame(self.card, height=2, bg=COLOR_AMARILLO)
        sep.pack(fill="x", pady=(8, 14))

        # Contenedor dinámico según el paso
        self.content_frame = tk.Frame(self.card, bg=COLOR_WHITE)
        self.content_frame.pack(fill="both", expand=True)

        if self.paso == "email":
            self._render_paso_email()
        else:
            self._render_paso_codigo()

        # Footer con Información de Soporte (R14) y Términos y Condiciones (R15)
        footer_links = tk.Frame(self.card, bg=COLOR_WHITE)
        footer_links.pack(fill="x", side="bottom", pady=(10, 0))

        btn_soporte = tk.Button(
            footer_links,
            text="ℹ️ Soporte Técnico",
            font=(FONT_FAMILY, 9, "bold"),
            fg=COLOR_BLUE,
            bg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            command=self._mostrar_soporte
        )
        btn_soporte.pack(side="left", padx=4)

        lbl_dot = tk.Label(footer_links, text="·", font=(FONT_FAMILY, 10), fg=COLOR_TEXT_MUTED, bg=COLOR_WHITE)
        lbl_dot.pack(side="left")

        btn_terminos = tk.Button(
            footer_links,
            text="📄 Términos y Condiciones",
            font=(FONT_FAMILY, 9),
            fg=COLOR_TEXT_MUTED,
            bg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            command=self._mostrar_terminos
        )
        btn_terminos.pack(side="left", padx=4)

    def _render_paso_email(self):
        lbl_inst = tk.Label(
            self.content_frame,
            text="Ingresá tu correo de Cajero / Empleado:",
            font=(FONT_FAMILY, 11, "bold"),
            fg=COLOR_TEXT,
            bg=COLOR_WHITE,
            anchor="w"
        )
        lbl_inst.pack(fill="x", pady=(0, 6))

        lbl_help = tk.Label(
            self.content_frame,
            text="Se enviará un código token de seguridad a tu casilla.",
            font=(FONT_FAMILY, 9),
            fg=COLOR_TEXT_MUTED,
            bg=COLOR_WHITE,
            anchor="w"
        )
        lbl_help.pack(fill="x", pady=(0, 10))

        self.entry_email = tk.Entry(
            self.content_frame,
            font=(FONT_FAMILY, 12),
            bg=COLOR_LIGHT_BG,
            fg=COLOR_TEXT,
            relief="solid",
            bd=1
        )
        self.entry_email.insert(0, self.email_actual if self.email_actual else "cajero1@mostaza.com.ar")
        self.entry_email.pack(fill="x", ipady=8, pady=(0, 16))
        self.entry_email.focus_set()
        self.entry_email.bind("<Return>", lambda e: self._solicitar_codigo())

        self.btn_solicitar = tk.Button(
            self.content_frame,
            text="✉ ENVIAR CÓDIGO DE ACCESO",
            font=(FONT_FAMILY, 11, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            activebackground=COLOR_ROJO_OSCURO,
            activeforeground=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            command=self._solicitar_codigo
        )
        self.btn_solicitar.pack(fill="x", ipady=12, pady=(0, 12))

    def _solicitar_codigo(self):
        email = self.entry_email.get().strip()
        if not email or "@" not in email:
            messagebox.showwarning("Atención", "Ingresá un correo electrónico válido.")
            return

        self.email_actual = email
        self.btn_solicitar.config(state="disabled", text="Enviando código...")

        def run_req():
            res = api.solicitar_codigo_gmail(email)
            self.root.after(0, lambda: self._on_solicitar_res(res))

        threading.Thread(target=run_req, daemon=True).start()

    def _on_solicitar_res(self, res):
        self.btn_solicitar.config(state="normal", text="✉ ENVIAR CÓDIGO DE ACCESO")
        if res.get("ok"):
            self.paso = "codigo"
            self._build_ui()
            if res.get("codigo_demo"):
                messagebox.showinfo("Código Generado", f"Código de acceso enviado a {self.email_actual}.\n\n(Demo/Local: {res['codigo_demo']})")
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo enviar el código."))

    def _render_paso_codigo(self):
        lbl_inst = tk.Label(
            self.content_frame,
            text=f"Código enviado a {self.email_actual}",
            font=(FONT_FAMILY, 10, "bold"),
            fg=COLOR_ROJO,
            bg=COLOR_WHITE,
            anchor="w"
        )
        lbl_inst.pack(fill="x", pady=(0, 4))

        lbl_help = tk.Label(
            self.content_frame,
            text="Ingresá los 6 dígitos recibidos:",
            font=(FONT_FAMILY, 9),
            fg=COLOR_TEXT_MUTED,
            bg=COLOR_WHITE,
            anchor="w"
        )
        lbl_help.pack(fill="x", pady=(0, 8))

        self.entry_pin = tk.Entry(
            self.content_frame,
            font=(FONT_FAMILY, 20, "bold"),
            justify="center",
            bg=COLOR_LIGHT_BG,
            fg=COLOR_TEXT,
            relief="solid",
            bd=2
        )
        self.entry_pin.pack(fill="x", ipady=6, pady=(0, 10))
        self.entry_pin.focus_set()
        self.entry_pin.bind("<KeyRelease>", self._on_pin_type)
        self.entry_pin.bind("<Return>", lambda e: self._verificar_codigo())

        # Teclado Numérico en Pantalla
        numpad_frame = tk.Frame(self.content_frame, bg=COLOR_WHITE)
        numpad_frame.pack(fill="x", pady=(0, 10))

        botones = [
            ('1', 0, 0), ('2', 0, 1), ('3', 0, 2),
            ('4', 1, 0), ('5', 1, 1), ('6', 1, 2),
            ('7', 2, 0), ('8', 2, 1), ('9', 2, 2),
            ('C', 3, 0), ('0', 3, 1), ('⌫', 3, 2),
        ]

        for texto, r, c in botones:
            bg_color = "#F1F5F9"
            fg_color = COLOR_TEXT
            if texto == 'C':
                bg_color = "#FEE2E2"
                fg_color = COLOR_ROJO
            elif texto == '⌫':
                bg_color = "#FEF3C7"
                fg_color = "#B45309"

            btn = tk.Button(
                numpad_frame,
                text=texto,
                font=(FONT_FAMILY, 12, "bold"),
                bg=bg_color,
                fg=fg_color,
                relief="flat",
                cursor="hand2",
                command=lambda val=texto: self._press_numpad(val)
            )
            btn.grid(row=r, column=c, padx=3, pady=3, sticky="nsew", ipadx=8, ipady=4)

        for col in range(3):
            numpad_frame.columnconfigure(col, weight=1)

        # Botón de Login (R18)
        self.btn_validar = tk.Button(
            self.content_frame,
            text="🔑 INICIAR TURNO POS (ENTER)",
            font=(FONT_FAMILY, 11, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            activebackground=COLOR_ROJO_OSCURO,
            activeforeground=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            command=self._verificar_codigo
        )
        self.btn_validar.pack(fill="x", ipady=8)

        btn_volver = tk.Button(
            self.content_frame,
            text="← Usar otro correo de cajero",
            font=(FONT_FAMILY, 9),
            bg=COLOR_WHITE,
            fg=COLOR_TEXT_MUTED,
            relief="flat",
            cursor="hand2",
            command=self._volver_a_email
        )
        btn_volver.pack(pady=(6, 0))

    def _press_numpad(self, valor):
        actual = self.entry_pin.get()
        if valor == "C":
            self.entry_pin.delete(0, tk.END)
        elif valor == "⌫":
            if actual:
                self.entry_pin.delete(len(actual) - 1, tk.END)
        else:
            if len(actual) < 6:
                self.entry_pin.insert(tk.END, valor)
                if len(self.entry_pin.get()) == 6:
                    self._verificar_codigo()

    def _on_pin_type(self, event):
        val = self.entry_pin.get()
        val_clean = ''.join(filter(str.isdigit, val))[:6]
        if val != val_clean:
            self.entry_pin.delete(0, tk.END)
            self.entry_pin.insert(0, val_clean)
        if len(val_clean) == 6:
            self._verificar_codigo()

    def _volver_a_email(self):
        self.paso = "email"
        self._build_ui()

    def _verificar_codigo(self):
        codigo = self.entry_pin.get().strip()
        if len(codigo) < 6:
            messagebox.showwarning("Atención", "Ingresá el código completo de 6 dígitos.")
            return

        self.btn_validar.config(state="disabled", text="Validando...")

        def run_verify():
            res = api.verificar_codigo_gmail(self.email_actual, codigo)
            self.root.after(0, lambda: self._on_verify_res(res))

        threading.Thread(target=run_verify, daemon=True).start()

    def _on_verify_res(self, res):
        self.btn_validar.config(state="normal", text="🔑 INICIAR TURNO POS (ENTER)")
        if res.get("ok"):
            cajero_info = res.get("cajero", {})
            self.on_login_success(cajero_info)
        else:
            messagebox.showerror("Error", res.get("error", "Código incorrecto."))
            self.entry_pin.delete(0, tk.END)
            self.entry_pin.focus_set()
    def _mostrar_soporte(self):
        """R14: Ver Información de Soporte Técnico"""
        top = tk.Toplevel(self.root)
        top.title("Mostaza · Soporte Técnico POS")
        top.geometry("450x380")
        top.resizable(False, False)
        top.configure(bg=COLOR_WHITE)

        # Centrar
        top.transient(self.root)
        top.grab_set()

        lbl_tit = tk.Label(top, text="ℹ️ Centro de Soporte Técnico", font=(FONT_FAMILY, 14, "bold"), fg=COLOR_ROJO, bg=COLOR_WHITE)
        lbl_tit.pack(pady=(20, 10))

        info_txt = (
            "Para asistencia con la terminal de punto de venta,\n"
            "impresoras térmicas fiscales o conexión de red:\n\n"
            "📞 Línea Directa Sistemas: 3704-432100 (Int. 104)\n"
            "🚨 Guardia Técnica 24hs: 3704-123456\n"
            "✉️ Correo: soporte.sistemas@mostaza.com.ar\n"
            "🏢 Sucursal: Mostaza Formosa Centro\n"
            "💻 Versión del Sistema: Mostaza POS v2.4 (Build 2026)"
        )
        lbl_cuerpo = tk.Label(top, text=info_txt, font=(FONT_FAMILY, 10), justify="left", bg=COLOR_WHITE, fg=COLOR_TEXT)
        lbl_cuerpo.pack(padx=24, pady=10, fill="both")

        btn_cerrar = tk.Button(top, text="Entendido / Cerrar", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_DARK, fg=COLOR_WHITE, relief="flat", cursor="hand2", command=top.destroy)
        btn_cerrar.pack(pady=(10, 20), ipadx=14, ipady=6)

    def _mostrar_terminos(self):
        """R15: Ver Términos y Condiciones de Uso"""
        top = tk.Toplevel(self.root)
        top.title("Mostaza · Términos y Condiciones")
        top.geometry("480x420")
        top.resizable(False, False)
        top.configure(bg=COLOR_WHITE)

        top.transient(self.root)
        top.grab_set()

        lbl_tit = tk.Label(top, text="📄 Términos y Condiciones del Cajero", font=(FONT_FAMILY, 13, "bold"), fg=COLOR_DARK, bg=COLOR_WHITE)
        lbl_tit.pack(pady=(16, 8))

        terminos = (
            "1. Responsabilidad de Caja:\n"
            "El cajero autenticado es el único responsable por la recaudación de efectivo, cobros electrónicos y el arqueo de cierre de su turno.\n\n"
            "2. Emisión Fiscal Obligatoria:\n"
            "Cada transacción debe respaldarse con el comprobante fiscal/ticket térmico entregado al cliente.\n\n"
            "3. Confidencialidad:\n"
            "Las credenciales y tokens de acceso son personales e intransferibles.\n\n"
            "4. Cancelación de Comandas:\n"
            "Las cancelaciones deben contar con justificación válida y quedar registradas en el sistema antes de que cocina complete la orden."
        )
        lbl_cuerpo = tk.Label(top, text=terminos, font=(FONT_FAMILY, 9), justify="left", bg=COLOR_WHITE, fg="#334155", wraplength=430)
        lbl_cuerpo.pack(padx=20, pady=10, fill="both")

        btn_cerrar = tk.Button(top, text="Aceptar y Continuar", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_ROJO, fg=COLOR_WHITE, relief="flat", cursor="hand2", command=top.destroy)
        btn_cerrar.pack(pady=(10, 16), ipadx=14, ipady=6)
