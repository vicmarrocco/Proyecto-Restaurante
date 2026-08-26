"""
App Cajero - Ventana de Arqueo y Control de Caja
Gestión de Turnos, Conteo Físico de Billetes, Historial y Cierre Z.
"""
import tkinter as tk
from tkinter import ttk, messagebox
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
        self.win.title("Arqueo y Control de Turno de Caja · Mostaza POS")
        self.win.geometry("780x680")
        self.win.minsize(720, 620)
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
        h = tk.Frame(self.win, bg=COLOR_SIDEBAR, height=52, padx=16, pady=8)
        h.pack(fill="x")

        tk.Label(
            h, text="📊 CONTROL Y ARQUEO DE CAJA DE TURNO",
            font=(FONT_FAMILY, 12, "bold"), fg=COLOR_WHITE, bg=COLOR_SIDEBAR
        ).pack(side="left")

        # Tabs de Navegación (Turno Activo / Historial)
        tabs_f = tk.Frame(self.win, bg="#F1F5F9", padx=16, pady=6)
        tabs_f.pack(fill="x")

        self.btn_tab_activo = tk.Button(
            tabs_f, text="⚡ Turno Actual (En Vivo)", font=(FONT_FAMILY, 9, "bold"),
            bg=COLOR_DARK, fg=COLOR_WHITE, relief="flat", padx=12, pady=4, cursor="hand2",
            command=self._mostrar_tab_activo
        )
        self.btn_tab_activo.pack(side="left", padx=(0, 4))

        self.btn_tab_historial = tk.Button(
            tabs_f, text="📜 Historial de Turnos y Arqueos", font=(FONT_FAMILY, 9, "bold"),
            bg="#E2E8F0", fg=COLOR_TEXT, relief="flat", padx=12, pady=4, cursor="hand2",
            command=self._mostrar_tab_historial
        )
        self.btn_tab_historial.pack(side="left", padx=4)

        # Contenedor Principal
        self.main_container = tk.Frame(self.win, bg=COLOR_LIGHT_BG, padx=16, pady=12)
        self.main_container.pack(fill="both", expand=True)

    def _mostrar_tab_activo(self):
        self.btn_tab_activo.config(bg=COLOR_DARK, fg=COLOR_WHITE)
        self.btn_tab_historial.config(bg="#E2E8F0", fg=COLOR_TEXT)
        self._load_caja()

    def _mostrar_tab_historial(self):
        self.btn_tab_activo.config(bg="#E2E8F0", fg=COLOR_TEXT)
        self.btn_tab_historial.config(bg=COLOR_DARK, fg=COLOR_WHITE)
        self._load_historial()

    def _load_caja(self):
        def run_fetch():
            res = api.obtener_estado_caja()
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
        card = tk.Frame(self.main_container, bg=COLOR_WHITE, padx=18, pady=15, relief="solid", bd=1)
        card.pack(fill="both", expand=True)

        top_f = tk.Frame(card, bg=COLOR_WHITE)
        top_f.pack(fill="x", pady=(0, 10))

        tk.Label(
            top_f, text=f"Turno: {c.get('turno', 'Turno')} · {c.get('cajero', 'Cajero')}",
            font=(FONT_FAMILY, 12, "bold"), fg=COLOR_DARK, bg=COLOR_WHITE
        ).pack(side="left")
        tk.Label(
            top_f, text="● CAJA ABIERTA", font=(FONT_FAMILY, 8, "bold"),
            fg=COLOR_GREEN, bg=COLOR_GREEN_BG, padx=8, pady=3
        ).pack(side="right")

        # Grid Simétrico de 6 Métricas (2x3)
        grid_m = tk.Frame(card, bg=COLOR_WHITE)
        grid_m.pack(fill="x", pady=(0, 12))

        metricas = [
            ("💵 Fondo Inicial (Apertura)", f"${int(c.get('monto_inicial', 0)):,}"),
            ("💰 Ventas Efectivo", f"${int(c.get('ventas_efectivo', 0)):,}"),
            ("💳 Ventas Tarjetas POS", f"${int(c.get('ventas_tarjeta', 0)):,}"),
            ("📱 Ventas Mercado Pago", f"${int(c.get('ventas_mercadopago', 0)):,}"),
            ("🏦 Ventas Transferencia", f"${int(c.get('ventas_transferencia', 0)):,}"),
            ("⭐ Recaudación Total", f"${int(c.get('total_ventas', 0)):,}")
        ]

        for idx, (lbl, val) in enumerate(metricas):
            r = idx // 3
            col = idx % 3
            box = tk.Frame(grid_m, bg="#F8FAFC", padx=10, pady=8, relief="solid", bd=1)
            box.grid(row=r, column=col, padx=4, pady=4, sticky="nsew")
            tk.Label(box, text=lbl, font=(FONT_FAMILY, 8, "bold"), fg=COLOR_TEXT_MUTED, bg="#F8FAFC").pack(anchor="w")
            tk.Label(
                box, text=val.replace(",", "."), font=(FONT_FAMILY, 11, "bold"),
                fg=COLOR_DARK if "Total" not in lbl else COLOR_ROJO, bg="#F8FAFC"
            ).pack(anchor="w")

        for i in range(3): grid_m.columnconfigure(i, weight=1)

        # Sección Arqueo Físico
        f_arq = tk.Frame(card, bg="#FAF5EA", padx=14, pady=12, relief="solid", bd=1)
        f_arq.pack(fill="x", pady=(0, 12))

        monto_ini = float(c.get("monto_inicial", 0))
        ventas_ef = float(c.get("ventas_efectivo", 0))
        total_esperado = monto_ini + ventas_ef

        info_arq_f = tk.Frame(f_arq, bg="#FAF5EA")
        info_arq_f.pack(fill="x", pady=(0, 6))

        tk.Label(
            info_arq_f, text="Dinero Esperado por Sistema (Fondo + Efectivo):",
            font=(FONT_FAMILY, 9), fg="#92400E", bg="#FAF5EA"
        ).pack(side="left")
        tk.Label(
            info_arq_f, text=f"${int(total_esperado):,}".replace(",", "."),
            font=(FONT_FAMILY, 10, "bold"), fg="#92400E", bg="#FAF5EA"
        ).pack(side="right")

        tk.Label(
            f_arq, text="Conteo Físico de Billetes en Gaveta ($):",
            font=(FONT_FAMILY, 10, "bold"), fg=COLOR_DARK, bg="#FAF5EA"
        ).pack(anchor="w", pady=(4, 2))

        entry_fisico = tk.Entry(
            f_arq, font=("Courier New", 14, "bold"), bg=COLOR_WHITE,
            relief="solid", bd=1, justify="center"
        )
        entry_fisico.insert(0, str(int(total_esperado)))
        entry_fisico.pack(fill="x", ipady=4, pady=(0, 6))

        lbl_diff = tk.Label(
            f_arq, text="Diferencia: $0 (Cuadre Exacto)",
            font=(FONT_FAMILY, 10, "bold"), fg="#166534", bg="#DCFCE7", pady=4
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

        # Botón Cerrar Turno
        btn_cerrar = tk.Button(
            card,
            text="🔒 CONFIRMAR ARQUEO Y EMITIR CIERRE Z",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_DARK,
            fg=COLOR_WHITE,
            activebackground="#1E293B",
            relief="flat",
            cursor="hand2",
            command=lambda: self._cerrar_turno(c.get("idarqueo"), entry_fisico.get(), total_esperado)
        )
        btn_cerrar.pack(fill="x", ipady=8)

    def _render_abrir_caja(self):
        card = tk.Frame(self.main_container, bg=COLOR_WHITE, padx=25, pady=25, relief="solid", bd=1)
        card.pack(fill="both", expand=True)

        tk.Label(card, text="Apertura de Turno de Caja", font=(FONT_FAMILY, 14, "bold"), fg=COLOR_DARK, bg=COLOR_WHITE).pack(anchor="w", pady=(0, 15))

        tk.Label(card, text="Cajero Asignado:", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_WHITE).pack(anchor="w", pady=(0, 4))
        lbl_cajero_txt = tk.Label(
            card, text=f"👤 {self.cajero_info.get('nombre_completo', 'Operador')} (ID #{self.cajero_info.get('id', 1)})",
            font=(FONT_FAMILY, 10), fg=COLOR_TEXT_MUTED, bg="#F1F5F9",
            padx=10, pady=6, anchor="w", relief="solid", bd=1
        )
        lbl_cajero_txt.pack(fill="x", pady=(0, 15))

        tk.Label(card, text="Seleccionar Turno:", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_WHITE).pack(anchor="w", pady=(0, 4))
        combo_t = ttk.Combobox(card, values=["Turno Mañana (08:00 - 16:00)", "Turno Tarde/Noche (16:00 - 00:00)"], state="readonly")
        combo_t.current(0)
        combo_t.pack(fill="x", pady=(0, 15))

        tk.Label(card, text="Monto Inicial en Gaveta (Fondo para dar cambio $):", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_WHITE).pack(anchor="w", pady=(0, 4))
        entry_ini = tk.Entry(card, font=("Courier New", 14, "bold"), bg=COLOR_LIGHT_BG, relief="solid", bd=1, justify="center")
        entry_ini.insert(0, "15000")
        entry_ini.pack(fill="x", ipady=4, pady=(0, 20))

        btn_abrir = tk.Button(
            card,
            text="🔑 INICIAR TURNO Y REGISTRAR FONDO INICIAL",
            font=(FONT_FAMILY, 11, "bold"),
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
            "idempleado": self.cajero_info.get("id", 1),
            "cajero": self.cajero_info.get("nombre_completo", "Cajero"),
            "turno": turno,
            "monto_inicial": monto
        }

        res = api.movimiento_caja("abrir_caja", payload)
        if res.get("ok"):
            txt_m = f"${int(monto):,}".replace(",", ".")
            messagebox.showinfo("Éxito", f"¡Turno de caja abierto correctamente!\nFondo Inicial Registrado: {txt_m}")
            if self.on_update: self.on_update()
            self._load_caja()
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo abrir la caja."))

    def _cerrar_turno(self, idarqueo, monto_fisico_str, total_esperado):
        try:
            monto_fisico = float(monto_fisico_str.replace(".", "").replace(",", "."))
        except ValueError:
            monto_fisico = total_esperado

        if not messagebox.askyesno("Confirmar Cierre Z", "¿Estás seguro de cerrar el turno de caja y generar el Cierre Z fiscal?"):
            return

        payload = {
            "idarqueo": idarqueo,
            "monto_fisico": monto_fisico,
            "total_esperado": total_esperado
        }

        res = api.movimiento_caja("cerrar_caja", payload)
        if res.get("ok"):
            resumen = res.get("resumen", {})
            self._mostrar_ticket_cierre_z(resumen)
            if self.on_update: self.on_update()
            self._load_caja()
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo cerrar la caja."))

    def _mostrar_ticket_cierre_z(self, r):
        z_win = tk.Toplevel(self.win)
        z_win.title(f"Cierre Z · Turno #{r.get('idarqueo', '')}")
        z_win.geometry("420x580")
        z_win.configure(bg="#333333")

        paper = tk.Frame(z_win, bg="#FFFFFF", padx=15, pady=15)
        paper.pack(fill="both", expand=True, padx=12, pady=12)

        txt_z = f"""
========================================
             MOSTAZA SA
     REPORTE FISCAL DE CIERRE Z
 CUIT: 30-71234567-9 · IVA Resp. Inscripto
========================================
TURNO N.º: #{r.get('idarqueo')}
Cajero: {r.get('cajero')}
Turno: {r.get('turno')}
Apertura: {r.get('fecha_apertura')}
Cierre: {r.get('fecha_cierre')}
Transacciones: {r.get('total_transacciones', 0)}
----------------------------------------
DESGLOSE DE MEDIOS DE PAGO:
Fondo Inicial Apertura:   {r.get('monto_inicial_formateado', '$0'):>12}
Ventas Efectivo:          {r.get('ventas_efectivo_formateado', '$0'):>12}
Ventas Tarjetas POS:      {r.get('ventas_tarjeta_formateado', '$0'):>12}
Ventas Mercado Pago:      {r.get('ventas_mercadopago_formateado', '$0'):>12}
Ventas Transferencia:     {r.get('ventas_transferencia_formateado', '$0'):>12}
----------------------------------------
TOTAL VENTAS FACTURADAS:  {r.get('total_ventas_formateado', '$0'):>12}
----------------------------------------
CONTROL FÍSICO DE GAVETA:
Total Efectivo Sistema:   {r.get('monto_final_sistema_formateado', '$0'):>12}
Dinero Físico Contado:    {r.get('monto_fisico_formateado', '$0'):>12}
Diferencia de Arqueo:     {r.get('diferencia_texto', '$0'):>12}
========================================
    CIERRE DE TURNO CONSOLIDADO EN BD
========================================
"""
        t_box = tk.Text(paper, font=("Courier New", 9), bg="#FFFFFF", relief="flat")
        t_box.insert(tk.END, txt_z)
        t_box.config(state="disabled")
        t_box.pack(fill="both", expand=True)

        btn_c = tk.Button(
            paper, text="✓ Finalizar y Cerrar", font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO, fg=COLOR_WHITE, relief="flat", cursor="hand2", command=z_win.destroy
        )
        btn_c.pack(fill="x", pady=(8, 0))

    # ------------------------------------------------------------
    # TAB HISTORIAL DE TURNOS
    # ------------------------------------------------------------
    def _load_historial(self):
        for w in self.main_container.winfo_children():
            w.destroy()

        card = tk.Frame(self.main_container, bg=COLOR_WHITE, padx=15, pady=15, relief="solid", bd=1)
        card.pack(fill="both", expand=True)

        top_h = tk.Frame(card, bg=COLOR_WHITE)
        top_h.pack(fill="x", pady=(0, 10))

        tk.Label(
            top_h, text="Historial de Turnos y Arqueos Consolidados",
            font=(FONT_FAMILY, 11, "bold"), fg=COLOR_DARK, bg=COLOR_WHITE
        ).pack(side="left")

        btn_ref_h = tk.Button(
            top_h, text="🔄 Actualizar", font=(FONT_FAMILY, 8, "bold"),
            bg="#334155", fg=COLOR_WHITE, relief="flat", padx=10, cursor="hand2", command=self._load_historial
        )
        btn_ref_h.pack(side="right")

        cols = ("id", "cajero", "apertura", "cierre", "inicial", "ventas", "fisico", "diff", "estado")
        tree = ttk.Treeview(card, columns=cols, show="headings", selectmode="browse")

        tree.heading("id", text="N.º")
        tree.heading("cajero", text="Cajero / Turno")
        tree.heading("apertura", text="Apertura")
        tree.heading("cierre", text="Cierre")
        tree.heading("inicial", text="Fondo Ini")
        tree.heading("ventas", text="Ventas")
        tree.heading("fisico", text="Físico")
        tree.heading("diff", text="Diferencia")
        tree.heading("estado", text="Estado")

        tree.column("id", width=45, anchor="center")
        tree.column("cajero", width=120, anchor="w")
        tree.column("apertura", width=95, anchor="center")
        tree.column("cierre", width=95, anchor="center")
        tree.column("inicial", width=75, anchor="e")
        tree.column("ventas", width=80, anchor="e")
        tree.column("fisico", width=75, anchor="e")
        tree.column("diff", width=80, anchor="center")
        tree.column("estado", width=65, anchor="center")

        scroll = ttk.Scrollbar(card, orient="vertical", command=tree.yview)
        tree.configure(yscrollcommand=scroll.set)

        tree.pack(side="left", fill="both", expand=True)
        scroll.pack(side="right", fill="y")

        lbl_status = tk.Label(card, text="Cargando historial de turnos...", font=(FONT_FAMILY, 9), bg=COLOR_WHITE, fg=COLOR_TEXT_MUTED)
        lbl_status.pack(side="bottom", anchor="w", pady=(8, 0))

        def run_hist():
            res = api.obtener_historial_cajas(50)
            self.win.after(0, lambda: fill_tree(res))

        def fill_tree(res):
            for item in tree.get_children():
                tree.delete(item)

            if not res.get("ok"):
                lbl_status.config(text=f"Error: {res.get('error', 'No se pudo cargar el historial.')}")
                return

            items = res.get("historial", [])
            lbl_status.config(text=f"Se encontraron {len(items)} turnos de caja registrados.")

            for h in items:
                tree.insert("", "end", values=(
                    f"#{h.get('idarqueo')}",
                    f"{h.get('cajero')}",
                    h.get('fecha_apertura', ''),
                    h.get('fecha_cierre', 'En curso'),
                    h.get('monto_inicial_formateado', '$0'),
                    h.get('total_ventas_formateado', '$0'),
                    h.get('monto_fisico_cierre_formateado', '--'),
                    h.get('diferencia_texto', '--'),
                    h.get('estado', 'Cerrada')
                ))

        threading.Thread(target=run_hist, daemon=True).start()
