"""
App Cajero - Ventana de Arqueo y Control de Caja Multi-Terminal
Gestión de Turnos, Conteo Físico de Billetes, Sangrías, Cierre X e Informe Fiscal Cierre Z.
"""
import tkinter as tk
from tkinter import ttk, messagebox, simpledialog
import threading
import datetime
from config import *
from api_client import api

class ArqueoWindow:
    def __init__(self, parent, cajero_info, on_update=None):
        self.parent = parent
        self.cajero_info = cajero_info
        self.on_update = on_update

        self.win = tk.Toplevel(parent)
        self.win.title(f"Arqueo y Control de Caja · {TERMINAL_NOMBRE} · Mostaza POS")
        self.win.geometry("820x720")
        self.win.minsize(740, 640)
        self.win.configure(bg=COLOR_LIGHT_BG)
        self.win.grab_set()

        self.caja_activa = None
        self.historial_turnos = []

        self._build_ui()
        self._load_caja()

    def _build_ui(self):
        for w in self.win.winfo_children():
            w.destroy()

        # Header
        h = tk.Frame(self.win, bg=COLOR_WHITE, height=54, padx=16, pady=8, relief="solid", bd=1)
        h.pack(fill="x")

        tk.Label(
            h, text=f"📊 CONTROL Y ARQUEO DE CAJA · {TERMINAL_NOMBRE.upper()}",
            font=(FONT_FAMILY, 11, "bold"), fg=COLOR_ROJO, bg=COLOR_WHITE
        ).pack(side="left")

        lbl_terminal_tag = tk.Label(
            h, text=f"P.V. {TERMINAL_PV} · CAJA {TERMINAL_NUMERO}",
            font=(FONT_FAMILY, 9, "bold"), fg=COLOR_WHITE, bg=COLOR_DARK,
            padx=8, pady=3
        )
        lbl_terminal_tag.pack(side="right")

        # Tabs de Navegación (Turno Activo / Historial)
        tabs_f = tk.Frame(self.win, bg="#F1F5F9", padx=16, pady=6)
        tabs_f.pack(fill="x")

        self.btn_tab_activo = tk.Button(
            tabs_f, text="⚡ Turno Actual (En Vivo)", font=(FONT_FAMILY, 9, "bold"),
            bg=COLOR_ROJO, fg=COLOR_WHITE, relief="flat", padx=12, pady=4, cursor="hand2",
            command=self._mostrar_tab_activo
        )
        self.btn_tab_activo.pack(side="left", padx=(0, 4))

        self.btn_tab_historial = tk.Button(
            tabs_f, text="📜 Historial de Turnos y Arqueos", font=(FONT_FAMILY, 9, "bold"),
            bg="#E2E8F0", fg=COLOR_TEXT, relief="flat", padx=12, pady=4, cursor="hand2",
            command=self._mostrar_tab_historial
        )
        self.btn_tab_historial.pack(side="left", padx=4)

        # Contenedor Principal con Scrollbar
        self.main_container = tk.Frame(self.win, bg=COLOR_LIGHT_BG, padx=16, pady=10)
        self.main_container.pack(fill="both", expand=True)

    def _mostrar_tab_activo(self):
        self.btn_tab_activo.config(bg=COLOR_ROJO, fg=COLOR_WHITE)
        self.btn_tab_historial.config(bg="#E2E8F0", fg=COLOR_TEXT)
        self._load_caja()

    def _mostrar_tab_historial(self):
        self.btn_tab_activo.config(bg="#E2E8F0", fg=COLOR_TEXT)
        self.btn_tab_historial.config(bg=COLOR_ROJO, fg=COLOR_WHITE)
        self._load_historial()

    def _load_caja(self):
        def run_fetch():
            res = api.obtener_estado_caja(TERMINAL_NUMERO)
            self.win.after(0, lambda: self._on_caja_loaded(res))

        threading.Thread(target=run_fetch, daemon=True).start()

    def _on_caja_loaded(self, res):
        for w in self.main_container.winfo_children():
            w.destroy()

        if res.get("ok") and res.get("abierta"):
            self.caja_activa = res.get("caja", {})
            self._render_caja_abierta()
        else:
            self._render_abrir_caja()

    def _render_caja_abierta(self):
        c = self.caja_activa
        card = tk.Frame(self.main_container, bg=COLOR_WHITE, padx=18, pady=12, relief="solid", bd=1)
        card.pack(fill="both", expand=True)

        top_f = tk.Frame(card, bg=COLOR_WHITE)
        top_f.pack(fill="x", pady=(0, 8))

        caj_nombre = c.get('cajero', 'Cajero')
        tk.Label(
            top_f, text=f"Turno: {c.get('turno', 'Turno')} · {caj_nombre} ({c.get('terminal_nombre', TERMINAL_NOMBRE)})",
            font=(FONT_FAMILY, 11, "bold"), fg=COLOR_DARK, bg=COLOR_WHITE
        ).pack(side="left")
        tk.Label(
            top_f, text="● CAJA ABIERTA", font=(FONT_FAMILY, 8, "bold"),
            fg=COLOR_GREEN, bg=COLOR_GREEN_BG, padx=8, pady=3
        ).pack(side="right")

        # Alerta de Sangría / Retiro de Seguridad
        if c.get("alerta_retiro_seguridad"):
            alerta_f = tk.Frame(card, bg="#FEF3C7", padx=12, pady=6, relief="solid", bd=1, highlightbackground="#F59E0B")
            alerta_f.pack(fill="x", pady=(0, 8))
            tk.Label(
                alerta_f, text=c.get("mensaje_alerta", "⚠️ Alerta de Seguridad: Excedente de efectivo."),
                font=(FONT_FAMILY, 8, "bold"), fg="#92400E", bg="#FEF3C7"
            ).pack(side="left")
            btn_sangria_quick = tk.Button(
                alerta_f, text="💸 Hacer Retiro Ahora", font=(FONT_FAMILY, 8, "bold"),
                bg="#F59E0B", fg=COLOR_WHITE, relief="flat", cursor="hand2",
                command=lambda: self._prompt_retiro(c.get("idarqueo"))
            )
            btn_sangria_quick.pack(side="right", padx=4)

        # Grid Simétrico de Métricas
        grid_m = tk.Frame(card, bg=COLOR_WHITE)
        grid_m.pack(fill="x", pady=(0, 8))

        monto_ini = float(c.get("monto_inicial", 0))
        ventas_ef = float(c.get("ventas_efectivo", 0))
        monto_retiros = float(c.get("monto_retiros", 0))
        total_esperado = max(0, monto_ini + ventas_ef - monto_retiros)

        metricas = [
            ("💵 Fondo Inicial", f"${int(monto_ini):,}"),
            ("💰 Ventas Efectivo", f"${int(ventas_ef):,}"),
            ("💳 Ventas Tarjetas POS", f"${int(c.get('ventas_tarjeta', 0)):,}"),
            ("📱 Ventas Mercado Pago", f"${int(c.get('ventas_mercadopago', 0)):,}"),
            ("🏦 Ventas Transferencia", f"${int(c.get('ventas_transferencia', 0)):,}"),
            ("💸 Retiros a Tesorería", f"${int(monto_retiros):,}"),
            ("⭐ Recaudación Total", f"${int(c.get('total_ventas', 0)):,}"),
            ("🧾 Total Transacciones", f"{c.get('total_transacciones', 0)} tickets")
        ]

        for idx, (lbl, val) in enumerate(metricas):
            r = idx // 4
            col = idx % 4
            box = tk.Frame(grid_m, bg="#F8FAFC", padx=8, pady=6, relief="solid", bd=1)
            box.grid(row=r, column=col, padx=3, pady=3, sticky="nsew")
            tk.Label(box, text=lbl, font=(FONT_FAMILY, 7, "bold"), fg=COLOR_TEXT_MUTED, bg="#F8FAFC").pack(anchor="w")
            col_txt = COLOR_DARK
            if "Total" in lbl: col_txt = COLOR_ROJO
            elif "Retiros" in lbl: col_txt = "#B45309"
            tk.Label(
                box, text=val.replace(",", "."), font=(FONT_FAMILY, 10, "bold"),
                fg=col_txt, bg="#F8FAFC"
            ).pack(anchor="w")

        for i in range(4): grid_m.columnconfigure(i, weight=1)

        # Botones de Acciones Operativas (Sangría & Cierre X)
        ops_frame = tk.Frame(card, bg=COLOR_WHITE)
        ops_frame.pack(fill="x", pady=(0, 8))

        btn_sangria = tk.Button(
            ops_frame, text="💸 Registrar Sangría / Retiro a Tesorería",
            font=(FONT_FAMILY, 8, "bold"), bg="#F1F5F9", fg="#92400E",
            relief="solid", bd=1, cursor="hand2", padx=8, pady=4,
            command=lambda: self._prompt_retiro(c.get("idarqueo"))
        )
        btn_sangria.pack(side="left", padx=(0, 6))

        btn_cierre_x = tk.Button(
            ops_frame, text="🔍 Cierre X (Arqueo Parcial sin Bloquear Caja)",
            font=(FONT_FAMILY, 8, "bold"), bg="#EFF6FF", fg=COLOR_BLUE,
            relief="solid", bd=1, cursor="hand2", padx=8, pady=4,
            command=lambda: self._ejecutar_cierre_x(c.get("idarqueo"))
        )
        btn_cierre_x.pack(side="left")

        # Sección Arqueo Físico
        f_arq = tk.Frame(card, bg="#FAF5EA", padx=14, pady=10, relief="solid", bd=1)
        f_arq.pack(fill="x", pady=(0, 10))

        info_arq_f = tk.Frame(f_arq, bg="#FAF5EA")
        info_arq_f.pack(fill="x", pady=(0, 4))

        tk.Label(
            info_arq_f, text="Efectivo Esperado en Gaveta (Fondo + Efectivo - Retiros):",
            font=(FONT_FAMILY, 9), fg="#92400E", bg="#FAF5EA"
        ).pack(side="left")
        tk.Label(
            info_arq_f, text=f"${int(total_esperado):,}".replace(",", "."),
            font=(FONT_FAMILY, 10, "bold"), fg="#92400E", bg="#FAF5EA"
        ).pack(side="right")

        tk.Label(
            f_arq, text="Conteo Físico de Billetes en Gaveta ($):",
            font=(FONT_FAMILY, 9, "bold"), fg=COLOR_DARK, bg="#FAF5EA"
        ).pack(anchor="w", pady=(2, 2))

        entry_fisico = tk.Entry(
            f_arq, font=("Courier New", 13, "bold"), bg=COLOR_WHITE,
            relief="solid", bd=1, justify="center"
        )
        entry_fisico.insert(0, str(int(total_esperado)))
        entry_fisico.pack(fill="x", ipady=4, pady=(0, 4))

        lbl_diff = tk.Label(
            f_arq, text="Diferencia: $0 (Cuadre Exacto)",
            font=(FONT_FAMILY, 9, "bold"), fg="#166534", bg="#DCFCE7", pady=3
        )
        lbl_diff.pack(fill="x")

        def calc_diff():
            try:
                fisico = float(entry_fisico.get().replace(".", "").replace(",", "."))
                diff = fisico - total_esperado
                if diff == 0:
                    lbl_diff.config(text="Diferencia: $0 (Cuadre Exacto)", fg="#166534", bg="#DCFCE7")
                elif diff > 0:
                    lbl_diff.config(text=f"Diferencia: +${int(diff):,} (Sobrante)".replace(",", "."), fg="#1E40AF", bg="#DBEAFE")
                else:
                    lbl_diff.config(text=f"Diferencia: -${int(abs(diff)):,} (Faltante)".replace(",", "."), fg="#991B1B", bg="#FEE2E2")
            except ValueError:
                lbl_diff.config(text="Diferencia: $0")

        entry_fisico.bind("<KeyRelease>", lambda e: calc_diff())

        # Botón Cerrar Turno (Cierre Z)
        btn_cerrar = tk.Button(
            card,
            text=f"🔒 CONFIRMAR ARQUEO Y EMITIR INFORME DE CIERRE Z ({TERMINAL_NOMBRE})",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_DARK,
            fg=COLOR_WHITE,
            activebackground="#1E293B",
            relief="flat",
            cursor="hand2",
            command=lambda: self._cerrar_turno(c.get("idarqueo"), entry_fisico.get(), total_esperado)
        )
        btn_cerrar.pack(fill="x", ipady=8)

    def _prompt_retiro(self, idarqueo):
        """Prompt para registrar sangría o retiro de seguridad."""
        win_ret = tk.Toplevel(self.win)
        win_ret.title("Registrar Retiro de Seguridad / Sangría")
        win_ret.geometry("400x260")
        win_ret.resizable(False, False)
        win_ret.configure(bg=COLOR_WHITE)
        win_ret.transient(self.win)
        win_ret.grab_set()

        tk.Label(win_ret, text="💸 Retiro de Efectivo a Tesorería", font=(FONT_FAMILY, 11, "bold"), fg=COLOR_ROJO, bg=COLOR_WHITE).pack(pady=(15, 6))
        tk.Label(win_ret, text=f"Terminal: {TERMINAL_NOMBRE}", font=(FONT_FAMILY, 8), fg=COLOR_TEXT_MUTED, bg=COLOR_WHITE).pack(pady=(0, 10))

        tk.Label(win_ret, text="Monto a Retirar ($):", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_WHITE).pack(anchor="w", padx=25)
        entry_monto = tk.Entry(win_ret, font=("Courier New", 12, "bold"), justify="center", relief="solid", bd=1)
        entry_monto.pack(fill="x", padx=25, ipady=4, pady=(2, 8))
        entry_monto.focus_set()

        tk.Label(win_ret, text="Motivo / Destino:", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_WHITE).pack(anchor="w", padx=25)
        entry_motivo = tk.Entry(win_ret, font=(FONT_FAMILY, 9), relief="solid", bd=1)
        entry_motivo.insert(0, "Excedente de gaveta a caja fuerte de tesorería")
        entry_motivo.pack(fill="x", padx=25, ipady=4, pady=(2, 14))

        def do_retiro():
            try:
                monto = float(entry_monto.get().replace(".", "").replace(",", "."))
                if monto <= 0: raise ValueError
            except ValueError:
                messagebox.showwarning("Atención", "Ingresá un monto válido mayor a $0.")
                return

            motivo = entry_motivo.get().strip() or "Retiro de seguridad a caja fuerte"
            res = api.registrar_retiro_seguridad(
                monto=monto,
                motivo=motivo,
                idarqueo=idarqueo,
                cajero=self.cajero_info.get("nombre_completo", "Cajero"),
                idempleado=self.cajero_info.get("id") or self.cajero_info.get("idempleado")
            )
            if res.get("ok"):
                messagebox.showinfo("Retiro Exitoso", f"Se registró el retiro de ${int(monto):,} correctamente.".replace(",", "."))
                win_ret.destroy()
                self._load_caja()
                if self.on_update: self.on_update()
            else:
                messagebox.showerror("Error", res.get("error", "No se pudo registrar el retiro."))

        tk.Button(
            win_ret, text="CONFIRMAR RETIRO", font=(FONT_FAMILY, 9, "bold"),
            bg="#B45309", fg=COLOR_WHITE, relief="flat", cursor="hand2",
            command=do_retiro
        ).pack(fill="x", padx=25, ipady=6)

    def _ejecutar_cierre_x(self, idarqueo):
        """Muestra arqueo ciego o parcial sin cerrar la caja."""
        res = api.cierre_x(idarqueo)
        if res.get("ok"):
            caja_x = res.get("caja", {})
            self._mostrar_ticket_cierre_z(caja_x, es_cierre_x=True)
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo generar el Cierre X."))

    def _render_abrir_caja(self):
        card = tk.Frame(self.main_container, bg=COLOR_WHITE, padx=25, pady=25, relief="solid", bd=1)
        card.pack(fill="both", expand=True)

        tk.Label(card, text=f"Apertura de Turno · {TERMINAL_NOMBRE}", font=(FONT_FAMILY, 13, "bold"), fg=COLOR_DARK, bg=COLOR_WHITE).pack(anchor="w", pady=(0, 10))

        tk.Label(card, text="Cajero Asignado:", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_WHITE).pack(anchor="w", pady=(0, 4))
        lbl_cajero_txt = tk.Label(
            card, text=f"👤 {self.cajero_info.get('nombre_completo', 'Operador')} (ID #{self.cajero_info.get('id', 1)})",
            font=(FONT_FAMILY, 9), fg=COLOR_TEXT_MUTED, bg="#F1F5F9",
            padx=10, pady=6, anchor="w", relief="solid", bd=1
        )
        lbl_cajero_txt.pack(fill="x", pady=(0, 12))

        tk.Label(card, text="Seleccionar Turno:", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_WHITE).pack(anchor="w", pady=(0, 4))
        combo_t = ttk.Combobox(card, values=["Turno Mañana (08:00 - 16:00)", "Turno Tarde/Noche (16:00 - 00:00)"], state="readonly")
        combo_t.current(0)
        combo_t.pack(fill="x", pady=(0, 12))

        tk.Label(card, text="Monto Inicial en Gaveta (Fondo para cambio $):", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_WHITE).pack(anchor="w", pady=(0, 4))
        entry_ini = tk.Entry(card, font=("Courier New", 13, "bold"), bg=COLOR_LIGHT_BG, relief="solid", bd=1, justify="center")
        entry_ini.insert(0, "15000")
        entry_ini.pack(fill="x", ipady=4, pady=(0, 16))

        btn_abrir = tk.Button(
            card,
            text=f"🔑 INICIAR TURNO EN {TERMINAL_NOMBRE.upper()}",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            command=lambda: self._abrir_turno(combo_t.get(), entry_ini.get())
        )
        btn_abrir.pack(fill="x", ipady=8)

    def _abrir_turno(self, turno, monto_ini_str):
        try:
            monto = float(monto_ini_str.replace(".", "").replace(",", "."))
        except ValueError:
            monto = 15000.0

        payload = {
            "numero_caja": TERMINAL_NUMERO,
            "terminal_nombre": TERMINAL_NOMBRE,
            "idempleado": self.cajero_info.get("id", 1),
            "cajero": self.cajero_info.get("nombre_completo", "Cajero"),
            "turno": turno,
            "monto_inicial": monto
        }

        res = api.movimiento_caja("abrir_caja", payload)
        if res.get("ok"):
            txt_m = f"${int(monto):,}".replace(",", ".")
            messagebox.showinfo("Éxito", f"¡Turno de {TERMINAL_NOMBRE} abierto correctamente!\nFondo Inicial: {txt_m}")
            if self.on_update: self.on_update()
            self._load_caja()
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo abrir la caja."))

    def _cerrar_turno(self, idarqueo, monto_fisico_str, total_esperado):
        try:
            monto_fisico = float(monto_fisico_str.replace(".", "").replace(",", "."))
        except ValueError:
            monto_fisico = total_esperado

        if not messagebox.askyesno("Confirmar Cierre Z", f"¿Estás seguro de cerrar el turno de {TERMINAL_NOMBRE} y emitir el Informe de Cierre Z?"):
            return

        payload = {
            "idarqueo": idarqueo,
            "numero_caja": TERMINAL_NUMERO,
            "monto_fisico": monto_fisico,
            "total_esperado": total_esperado
        }

        res = api.movimiento_caja("cerrar_caja", payload)
        if res.get("ok"):
            informe = res.get("informe_cierre", {})
            self._mostrar_ticket_cierre_z(informe)
            if self.on_update: self.on_update()
            self._load_caja()
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo cerrar la caja."))

    def _mostrar_ticket_cierre_z(self, r, es_cierre_x=False):
        tipo_rep = "REPORTE PARCIAL DE ARQUEO (CIERRE X)" if es_cierre_x else "INFORME FISCAL DE CIERRE DE CAJA (CIERRE Z)"
        z_win = tk.Toplevel(self.win)
        z_win.title(f"{tipo_rep} · {TERMINAL_NOMBRE}")
        z_win.geometry("450x640")
        z_win.configure(bg="#222222")

        paper = tk.Frame(z_win, bg="#FFFFFF", padx=15, pady=15)
        paper.pack(fill="both", expand=True, padx=12, pady=12)

        txt_z = f"""
==================================================
           MOSTAZA ARGENTINA S.A.
       SUCURSAL FORMOSA CENTRO · EPET Nº 7
        CUIT: 30-71234567-9 · IVA RESP. INSC.
==================================================
{tipo_rep:^50}
==================================================
PUNTO DE VENTA:        P.V. {r.get('pv_afip', TERMINAL_PV)} ({r.get('terminal_nombre', TERMINAL_NOMBRE)})
TURNO ARQUEO:          #{r.get('idarqueo', '0')}
CAJERO RESPONSABLE:    {r.get('cajero', 'Cajero')}
TURNO HORARIO:         {r.get('turno', 'General')}
FECHA APERTURA:        {r.get('fecha_apertura', '-')}
FECHA CIERRE:          {r.get('fecha_cierre', r.get('fecha_emision_x', '-'))}
TOTAL OPERACIONES:     {r.get('total_transacciones', 0)} comprobantes
--------------------------------------------------
DESGLOSE POR MEDIO DE PAGO:
(+) Fondo Inicial Apertura:     ${int(r.get('monto_inicial', 0)):>13,d}
(+) Ventas Efectivo:            ${int(r.get('ventas_efectivo', 0)):>13,d}
(+) Ventas Tarjetas Déb/Créd:   ${int(r.get('ventas_tarjeta', 0)):>13,d}
(+) Ventas Mercado Pago QR:     ${int(r.get('ventas_mercadopago', 0)):>13,d}
(+) Ventas Transferencia:       ${int(r.get('ventas_transferencia', 0)):>13,d}
--------------------------------------------------
TOTAL FACTURADO DEL TURNO:      ${int(r.get('total_ventas', 0)):>13,d}
--------------------------------------------------
MOVIMIENTOS DE TESORERÍA:
(-) Retiros / Sangrías:         ${int(r.get('monto_retiros', 0)):>13,d}
--------------------------------------------------
BALANCE Y CONCILIACIÓN DE GAVETA:
(=) Efectivo Esperado Sistema:  ${int(r.get('efectivo_esperado', r.get('efectivo_esperado_gaveta', 0))):>13,d}
(=) Conteo Físico Real:         ${int(r.get('conteo_fisico_declarado', r.get('efectivo_esperado_gaveta', 0))):>13,d}
DIFERENCIA DE CAJA:             {r.get('diferencia_texto', 'Cuadre Exacto ($0)'):>20}
==================================================
FIRMA FISCAL: {r.get('firma_digital', 'MOSTAZA-AFIP-OK')}
==================================================
"""
        txt_z = txt_z.replace(",", ".")
        t_box = tk.Text(paper, font=("Courier New", 8), bg="#FFFFFF", relief="flat")
        t_box.insert(tk.END, txt_z)
        t_box.config(state="disabled")
        t_box.pack(fill="both", expand=True)

        btn_c = tk.Button(
            paper, text="✓ Cerrar Vista de Informe", font=(FONT_FAMILY, 9, "bold"),
            bg=COLOR_ROJO, fg=COLOR_WHITE, relief="flat", cursor="hand2", command=z_win.destroy
        )
        btn_c.pack(fill="x", pady=(8, 0))

    # ------------------------------------------------------------
    # TAB HISTORIAL DE TURNOS
    # ------------------------------------------------------------
    def _load_historial(self):
        for w in self.main_container.winfo_children():
            w.destroy()

        tk.Label(self.main_container, text=f"📜 Historial de Turnos de {TERMINAL_NOMBRE}", font=(FONT_FAMILY, 11, "bold"), bg=COLOR_LIGHT_BG).pack(anchor="w", pady=(0, 8))

        tree_frame = tk.Frame(self.main_container, bg=COLOR_WHITE, relief="solid", bd=1)
        tree_frame.pack(fill="both", expand=True)

        cols = ("id", "caja", "cajero", "apertura", "cierre", "ventas", "esperado", "fisico", "diff", "estado")
        self.tree = ttk.Treeview(tree_frame, columns=cols, show="headings", height=12)

        self.tree.heading("id", text="N.º")
        self.tree.heading("caja", text="Caja")
        self.tree.heading("cajero", text="Cajero")
        self.tree.heading("apertura", text="Apertura")
        self.tree.heading("cierre", text="Cierre")
        self.tree.heading("ventas", text="Total Ventas")
        self.tree.heading("esperado", text="Ef. Esperado")
        self.tree.heading("fisico", text="Conteo")
        self.tree.heading("diff", text="Diferencia")
        self.tree.heading("estado", text="Estado")

        self.tree.column("id", width=45, anchor="center")
        self.tree.column("caja", width=60, anchor="center")
        self.tree.column("cajero", width=120)
        self.tree.column("apertura", width=110, anchor="center")
        self.tree.column("cierre", width=110, anchor="center")
        self.tree.column("ventas", width=90, anchor="e")
        self.tree.column("esperado", width=85, anchor="e")
        self.tree.column("fisico", width=85, anchor="e")
        self.tree.column("diff", width=95, anchor="center")
        self.tree.column("estado", width=70, anchor="center")

        self.tree.pack(fill="both", expand=True)

        btn_ver_inf = tk.Button(
            self.main_container, text="📄 Ver Informe Detallado Cierre Z del Turno Seleccionado",
            font=(FONT_FAMILY, 9, "bold"), bg=COLOR_DARK, fg=COLOR_WHITE,
            relief="flat", cursor="hand2", command=self._ver_informe_seleccionado
        )
        btn_ver_inf.pack(fill="x", pady=(8, 0), ipady=4)

        def fetch_hist():
            res = api.obtener_historial_cajas(30, numero_caja=TERMINAL_NUMERO)
            self.win.after(0, lambda: self._render_hist_rows(res))

        threading.Thread(target=fetch_hist, daemon=True).start()

    def _render_hist_rows(self, res):
        for item in self.tree.get_children():
            self.tree.delete(item)

        turnos = res.get("historial", []) if res.get("ok") else []
        for t in turnos:
            diff = float(t.get("diferencia") or 0)
            diff_txt = "$0"
            if diff > 0: diff_txt = f"+${int(diff):,}"
            elif diff < 0: diff_txt = f"-${int(abs(diff)):,}"

            cierre_txt = t.get("fecha_cierre") or "En curso"
            if cierre_txt != "En curso":
                try: cierre_txt = datetime.datetime.strptime(cierre_txt, "%Y-%m-%d %H:%M:%S").strftime("%d/%m %H:%M")
                except: pass

            apert_txt = t.get("fecha_apertura", "")
            try: apert_txt = datetime.datetime.strptime(apert_txt, "%Y-%m-%d %H:%M:%S").strftime("%d/%m %H:%M")
            except: pass

            self.tree.insert("", "end", values=(
                f"#{t.get('idarqueo')}",
                f"Caja {t.get('numero_caja', TERMINAL_NUMERO)}",
                t.get("cajero", "Cajero"),
                apert_txt,
                cierre_txt,
                f"${int(float(t.get('total_ventas', 0))):,}".replace(",", "."),
                f"${int(float(t.get('monto_final_sistema', 0))):,}".replace(",", "."),
                f"${int(float(t.get('monto_fisico_cierre', 0))):,}".replace(",", "."),
                diff_txt.replace(",", "."),
                t.get("estado", "Abierta")
            ))

    def _ver_informe_seleccionado(self):
        sel = self.tree.selection()
        if not sel:
            messagebox.showinfo("Información", "Seleccioná un turno del historial para ver su informe.")
            return
        vals = self.tree.item(sel[0])["values"]
        try:
            idarqueo = int(str(vals[0]).replace("#", ""))
            res = api.obtener_informe_cierre(idarqueo)
            if res.get("ok"):
                self._mostrar_ticket_cierre_z(res.get("informe_cierre", {}))
            else:
                messagebox.showerror("Error", res.get("error", "No se encontró el informe."))
        except Exception as e:
            messagebox.showerror("Error", f"Error al abrir informe: {e}")
