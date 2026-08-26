"""
Mostaza POS - Ventana de Identificación
Autenticación de Cajeros y Empleados mediante PIN de seguridad.
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
        self.root.title("Mostaza · Terminal de Cajero")
        self.root.geometry("520x680")
        self.root.resizable(False, False)
        self.root.configure(bg=COLOR_SIDEBAR)

        # Centrar ventana en pantalla
        self._center_window()

        self.email_actual = ""
        self.paso = "email" # "email" o "codigo"

        self._build_ui()

    def _center_window(self):
        self.root.update_idletasks()
        width = 520
        height = 680
        x = (self.root.winfo_screenwidth() // 2) - (width // 2)
        y = (self.root.winfo_screenheight() // 2) - (height // 2)
        self.root.geometry(f"{width}x{height}+{x}+{y}")

    def _build_ui(self):
        # Limpiar
        for w in self.root.winfo_children():
            w.destroy()

        # Contenedor Tarjeta Blanca Central
        self.card = tk.Frame(self.root, bg=COLOR_WHITE, padx=30, pady=30, relief="flat")
        self.card.place(relx=0.5, rely=0.5, anchor="center", width=460, height=620)

        # Encabezado con Marca
        header_frame = tk.Frame(self.card, bg=COLOR_WHITE)
        header_frame.pack(fill="x", pady=(0, 15))

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
            text="Terminal de Punto de Venta",
            font=(FONT_FAMILY, 10),
            fg=COLOR_TEXT_MUTED,
            bg=COLOR_WHITE
        )
        lbl_sub.pack(pady=(2, 0))

        # Separador visual
        sep = tk.Frame(self.card, height=2, bg=COLOR_AMARILLO)
        sep.pack(fill="x", pady=(0, 20))

        # Contenedor dinámico según el paso
        self.content_frame = tk.Frame(self.card, bg=COLOR_WHITE)
        self.content_frame.pack(fill="both", expand=True)

        if self.paso == "email":
            self._render_paso_email()
        else:
            self._render_paso_codigo()

    def _render_paso_email(self):
        lbl_inst = tk.Label(
            self.content_frame,
            text="Ingresá tu correo de Gmail para identificarte:",
            font=(FONT_FAMILY, 11, "bold"),
            fg=COLOR_TEXT,
            bg=COLOR_WHITE,
            anchor="w"
        )
        lbl_inst.pack(fill="x", pady=(0, 8))

        lbl_help = tk.Label(
            self.content_frame,
            text="Se enviará un código token de 6 dígitos a tu casilla.",
            font=(FONT_FAMILY, 9),
            fg=COLOR_TEXT_MUTED,
            bg=COLOR_WHITE,
            anchor="w"
        )
        lbl_help.pack(fill="x", pady=(0, 12))

        self.entry_email = tk.Entry(
            self.content_frame,
            font=(FONT_FAMILY, 12),
            bg=COLOR_LIGHT_BG,
            fg=COLOR_TEXT,
            relief="solid",
            bd=1
        )
        self.entry_email.insert(0, self.email_actual if self.email_actual else "applegomez64@gmail.com")
        self.entry_email.pack(fill="x", ipady=8, pady=(0, 20))
        self.entry_email.focus_set()
        self.entry_email.bind("<Return>", lambda e: self._solicitar_codigo())

        self.btn_solicitar = tk.Button(
            self.content_frame,
            text="✉ SOLICITAR CÓDIGO DE ACCESO",
            font=(FONT_FAMILY, 11, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            activebackground=COLOR_ROJO_OSCURO,
            activeforeground=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            command=self._solicitar_codigo
        )
        self.btn_solicitar.pack(fill="x", ipady=10, pady=(10, 0))

    def _solicitar_codigo(self):
        email = self.entry_email.get().strip()
        if not email or "@" not in email:
            messagebox.showwarning("Atención", "Por favor ingresá un correo de Gmail válido.")
            return

        self.btn_solicitar.config(state="disabled", text="Enviando código por Gmail...")

        def run_req():
            res = api.solicitar_codigo_gmail(email)
            self.root.after(0, lambda: self._on_solicitar_res(email, res))

        threading.Thread(target=run_req, daemon=True).start()

    def _on_solicitar_res(self, email, res):
        self.btn_solicitar.config(state="normal", text="✉ SOLICITAR CÓDIGO DE ACCESO")
        if res.get("ok"):
            self.email_actual = email
            self.paso = "codigo"
            self._build_ui()
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo enviar el código por correo."))

    def _render_paso_codigo(self):
        lbl_msg = tk.Label(
            self.content_frame,
            text=f"Código de seguridad enviado a:\n{self.email_actual}",
            font=(FONT_FAMILY, 10, "bold"),
            fg=COLOR_ROJO,
            bg=COLOR_WHITE,
            justify="center"
        )
        lbl_msg.pack(pady=(0, 10))

        # Campo de entrada de PIN
        self.entry_pin = tk.Entry(
            self.content_frame,
            font=("Courier New", 22, "bold"),
            bg=COLOR_LIGHT_BG,
            fg=COLOR_DARK,
            justify="center",
            relief="solid",
            bd=2
        )
        self.entry_pin.pack(fill="x", ipady=6, pady=(0, 15))
        self.entry_pin.focus_set()
        self.entry_pin.bind("<Return>", lambda e: self._verificar_codigo())
        self.entry_pin.bind("<KeyRelease>", self._on_pin_type)

        # Teclado Numpad Táctil en Pantalla
        numpad_frame = tk.Frame(self.content_frame, bg=COLOR_WHITE)
        numpad_frame.pack(fill="x", pady=(0, 15))

        teclas = [
            ("1", 0, 0), ("2", 0, 1), ("3", 0, 2),
            ("4", 1, 0), ("5", 1, 1), ("6", 1, 2),
            ("7", 2, 0), ("8", 2, 1), ("9", 2, 2),
            ("C", 3, 0), ("0", 3, 1), ("⌫", 3, 2),
        ]

        for texto, r, c in teclas:
            cmd = lambda t=texto: self._press_numpad(t)
            btn = tk.Button(
                numpad_frame,
                text=texto,
                font=(FONT_FAMILY, 13, "bold"),
                bg="#F1F5F9" if texto not in ["C", "⌫"] else "#E2E8F0",
                fg=COLOR_TEXT,
                activebackground=COLOR_AMARILLO,
                relief="flat",
                cursor="hand2",
                command=cmd
            )
            btn.grid(row=r, column=c, padx=3, pady=3, sticky="nsew", ipadx=10, ipady=6)

        for col in range(3):
            numpad_frame.columnconfigure(col, weight=1)

        # Botón de Login
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

        # Volver / Cambiar correo
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
        btn_volver.pack(pady=(10, 0))

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
        # Solo números y máximo 6 dígitos
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
