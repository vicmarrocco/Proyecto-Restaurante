"""
App Cajero - Ventana de Historial de Facturas y Reimpresión Fiscal
Consulta de comprobantes y facturas fiscales emitidas con filtro por fecha y búsqueda en vivo.
"""
import tkinter as tk
from tkinter import ttk, messagebox
import threading
from config import *
from api_client import api

class TicketsWindow:
    def __init__(self, parent):
        self.parent = parent
        self.win = tk.Toplevel(parent)
        self.win.title("Historial de Facturas y Comprobantes Fiscales · Mostaza POS")
        self.win.geometry("920x620")
        self.win.minsize(800, 520)
        self.win.configure(bg=COLOR_LIGHT_BG)
        self.win.grab_set()

        self.filtro_fecha = "hoy" # "hoy" o "todas"
        self.pedidos_raw = []

        self._build_ui()
        self._load_tickets()

    def _build_ui(self):
        for w in self.win.winfo_children():
            w.destroy()

        # Header Superior
        h = tk.Frame(self.win, bg=COLOR_SIDEBAR, height=54, padx=16, pady=8)
        h.pack(fill="x")

        tk.Label(
            h, text="🧾 HISTORIAL DE FACTURAS Y TICKETS FISCALES",
            font=(FONT_FAMILY, 12, "bold"), fg=COLOR_WHITE, bg=COLOR_SIDEBAR
        ).pack(side="left")

        btn_ref = tk.Button(
            h, text="🔄 Actualizar", font=(FONT_FAMILY, 9, "bold"),
            bg="#334155", fg=COLOR_WHITE, activebackground="#475569",
            relief="flat", padx=12, pady=4, cursor="hand2", command=self._load_tickets
        )
        btn_ref.pack(side="right")

        # Barra de Filtros y Pestañas
        filter_bar = tk.Frame(self.win, bg="#F1F5F9", padx=16, pady=8)
        filter_bar.pack(fill="x")

        self.btn_filtro_hoy = tk.Button(
            filter_bar, text="📅 Facturas de Hoy", font=(FONT_FAMILY, 9, "bold"),
            bg=COLOR_DARK, fg=COLOR_WHITE, relief="flat", padx=12, pady=4, cursor="hand2",
            command=lambda: self._cambiar_filtro_fecha("hoy")
        )
        self.btn_filtro_hoy.pack(side="left", padx=(0, 6))

        self.btn_filtro_todas = tk.Button(
            filter_bar, text="📜 Historial Completo (Todas las Fechas)", font=(FONT_FAMILY, 9, "bold"),
            bg="#E2E8F0", fg=COLOR_TEXT, relief="flat", padx=12, pady=4, cursor="hand2",
            command=lambda: self._cambiar_filtro_fecha("todas")
        )
        self.btn_filtro_todas.pack(side="left", padx=6)

        # Buscador Rápido
        search_f = tk.Frame(filter_bar, bg="#F1F5F9")
        search_f.pack(side="right")

        tk.Label(search_f, text="🔍 Buscar:", font=(FONT_FAMILY, 9, "bold"), bg="#F1F5F9").pack(side="left", padx=(0, 6))
        self.entry_search = tk.Entry(search_f, font=(FONT_FAMILY, 9), bg=COLOR_WHITE, relief="solid", bd=1, width=24)
        self.entry_search.pack(side="left", ipady=3)
        self.entry_search.bind("<KeyRelease>", lambda e: self._filtrar_tabla_local())

        # Contenedor Tabla
        container = tk.Frame(self.win, bg=COLOR_WHITE, padx=14, pady=12, relief="solid", bd=1)
        container.pack(fill="both", expand=True, padx=16, pady=(10, 8))

        columns = ("id", "factura", "fecha", "hora", "tipo", "pago", "total", "cajero")
        self.tree = ttk.Treeview(container, columns=columns, show="headings", selectmode="browse")

        self.tree.heading("id", text="N.º Pedido")
        self.tree.heading("factura", text="Factura Fiscal")
        self.tree.heading("fecha", text="Fecha")
        self.tree.heading("hora", text="Hora")
        self.tree.heading("tipo", text="Modalidad")
        self.tree.heading("pago", text="Medio de Pago")
        self.tree.heading("total", text="Total")
        self.tree.heading("cajero", text="Cajero")

        self.tree.column("id", width=70, anchor="center")
        self.tree.column("factura", width=140, anchor="center")
        self.tree.column("fecha", width=85, anchor="center")
        self.tree.column("hora", width=65, anchor="center")
        self.tree.column("tipo", width=120, anchor="center")
        self.tree.column("pago", width=110, anchor="center")
        self.tree.column("total", width=95, anchor="e")
        self.tree.column("cajero", width=120, anchor="w")

        scroll = ttk.Scrollbar(container, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=scroll.set)

        self.tree.pack(side="left", fill="both", expand=True)
        scroll.pack(side="right", fill="y")

        self.tree.bind("<Double-1>", lambda e: self._reimprimir_ticket())

        # Barra Inferior de Totales y Acciones
        status_bar = tk.Frame(self.win, bg=COLOR_LIGHT_BG, padx=16, pady=10)
        status_bar.pack(fill="x")

        self.lbl_tot_hoy = tk.Label(
            status_bar, text="Cargando facturas...",
            font=(FONT_FAMILY, 10, "bold"), fg=COLOR_DARK, bg=COLOR_LIGHT_BG
        )
        self.lbl_tot_hoy.pack(side="left")

        btn_cancelar = tk.Button(
            status_bar,
            text="❌ Cancelar Pedido Activo",
            font=(FONT_FAMILY, 10, "bold"),
            bg="#DC2626",
            fg=COLOR_WHITE,
            activebackground="#991B1B",
            relief="flat",
            cursor="hand2",
            padx=12,
            pady=8,
            command=self._cancelar_pedido_seleccionado
        )
        btn_cancelar.pack(side="right", padx=(0, 10))

        btn_reimprimir = tk.Button(
            status_bar,
            text="🖨 Ver Factura Fiscal / Reimprimir",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            activebackground=COLOR_ROJO_OSCURO,
            relief="flat",
            cursor="hand2",
            padx=16,
            pady=8,
            command=self._reimprimir_ticket
        )
        btn_reimprimir.pack(side="right")

    def _cambiar_filtro_fecha(self, modo):
        self.filtro_fecha = modo
        if modo == "hoy":
            self.btn_filtro_hoy.config(bg=COLOR_DARK, fg=COLOR_WHITE)
            self.btn_filtro_todas.config(bg="#E2E8F0", fg=COLOR_TEXT)
        else:
            self.btn_filtro_hoy.config(bg="#E2E8F0", fg=COLOR_TEXT)
            self.btn_filtro_todas.config(bg=COLOR_DARK, fg=COLOR_WHITE)
        self._load_tickets()

    def _load_tickets(self):
        for item in self.tree.get_children():
            self.tree.delete(item)

        self.lbl_tot_hoy.config(text="Cargando comprobantes desde el servidor...")

        def run_fetch():
            res = api.obtener_pedidos_hoy(fecha=self.filtro_fecha)
            self.win.after(0, lambda: self._on_tickets_loaded(res))

        threading.Thread(target=run_fetch, daemon=True).start()

    def _on_tickets_loaded(self, res):
        if not res.get("ok"):
            self.lbl_tot_hoy.config(text=f"Error: {res.get('error', 'No se pudo conectar a la base de datos')}")
            return

        self.pedidos_raw = res.get("pedidos", [])
        self._filtrar_tabla_local()

    def _filtrar_tabla_local(self):
        for item in self.tree.get_children():
            self.tree.delete(item)

        query = self.entry_search.get().strip().lower() if hasattr(self, 'entry_search') else ""

        pedidos_filtrados = []
        tot_sum = 0.0

        for p in self.pedidos_raw:
            id_p = str(p.get("idpedido", ""))
            num_f = str(p.get("numero_factura", ""))
            caj = str(p.get("cajero", "")).lower()
            cli = str(p.get("cliente", "")).lower()
            mesa = f"mesa {p.get('numero_mesa')}" if p.get("numero_mesa") else ""

            if query:
                if (query not in id_p and query not in num_f.lower() and query not in caj and query not in cli and query not in mesa):
                    continue

            pedidos_filtrados.append(p)
            tot_sum += float(p.get("total", 0))

            tipo_txt = p.get("tipo_entrega", "").upper()
            if p.get("numero_mesa"):
                tipo_txt += f" (Mesa {p['numero_mesa']})"

            # Extraer fecha corta
            fecha_raw = p.get("fecha", "")
            fecha_corta = fecha_raw.split(" ")[0] if " " in fecha_raw else fecha_raw

            self.tree.insert("", "end", values=(
                f"#{p.get('idpedido')}",
                p.get("numero_factura", "--"),
                fecha_corta,
                p.get("hora", ""),
                tipo_txt,
                p.get("metodo_pago", "").upper(),
                p.get("total_formateado", "$0"),
                p.get("cajero", "Cajero")
            ))

        tot_fmt = f"${int(tot_sum):,}".replace(",", ".")
        filtro_txt = "Hoy" if self.filtro_fecha == "hoy" else "Histórico Total"
        self.lbl_tot_hoy.config(
            text=f"Total {filtro_txt}: {tot_fmt} ({len(pedidos_filtrados)} comprobantes encontrados)"
        )

        if not pedidos_filtrados and self.filtro_fecha == "hoy" and not query:
            self.lbl_tot_hoy.config(
                text="No hay tickets emitidos hoy. Hacé clic en 'Historial Completo' para ver días anteriores."
            )

    def _reimprimir_ticket(self):
        sel = self.tree.selection()
        if not sel:
            messagebox.showinfo("Selección", "Hacé clic en una factura de la lista para ver el comprobante fiscal.")
            return

        vals = self.tree.item(sel[0], "values")
        id_pedido = int(vals[0].replace("#", ""))

        def fetch_factura():
            res = api.obtener_factura(idpedido=id_pedido)
            self.win.after(0, lambda: self._mostrar_factura_modal(res))

        threading.Thread(target=fetch_factura, daemon=True).start()

    def _mostrar_factura_modal(self, res):
        if not res.get("ok") or not res.get("factura"):
            messagebox.showerror("Error", "No se pudieron obtener los datos fiscales de la factura seleccionada.")
            return

        f = res["factura"]

        t_win = tk.Toplevel(self.win)
        t_win.title(f"Factura Fiscal {f.get('numero_factura')} · Mostaza POS")
        t_win.geometry("420x600")
        t_win.configure(bg="#333333")

        paper = tk.Frame(t_win, bg="#FFFFFF", padx=15, pady=15)
        paper.pack(fill="both", expand=True, padx=12, pady=12)

        ticket_text = f"""
========================================
             MOSTAZA SA
  EPET Nº 7 · Sistema Fiscal POS
 CUIT: 30-71234567-9 · IVA Resp. Inscripto
========================================
FACTURA B: {f.get('numero_factura')}
Fecha: {f.get('fecha_emision')}
Cajero: {f.get('cajero')}
Cliente: {f.get('nombre_cliente')}
Modalidad: {f.get('tipo_entrega', '').upper()} {f"(Mesa {f.get('numero_mesa')})" if f.get('numero_mesa') else ''}
Pago: {f.get('metodo_pago', '').upper()}
----------------------------------------
"""
        for it in f.get("items", []):
            cant = it.get('cantidad', 1)
            desc = it.get('descripcion', '')[:18]
            subt = int(it.get('subtotal', 0))
            ticket_text += f"{cant}x {desc:<18} ${subt:>7}\n"
            if it.get("modificadores"):
                ticket_text += f"   ↳ {it['modificadores']}\n"

        ticket_text += f"""----------------------------------------
Subtotal Neto (21%):       {f.get('neto_gravado_formateado'):>11}
IVA Discriminado (21%):     {f.get('iva_21_formateado'):>11}
TOTAL FACTURA:             {f.get('total_formateado'):>11}
----------------------------------------
CAE Nº: {f.get('cae')}
Vto. CAE: {f.get('cae_vto')}
========================================
       ¡GRACIAS POR SU COMPRA!
========================================
"""
        txt = tk.Text(paper, font=("Courier New", 9), bg="#FFFFFF", relief="flat")
        txt.insert(tk.END, ticket_text)
        txt.config(state="disabled")
        txt.pack(fill="both", expand=True)

        btn_c = tk.Button(
            paper, text="✓ Cerrar Comprobante", font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO, fg=COLOR_WHITE, relief="flat", cursor="hand2", command=t_win.destroy
        )
        btn_c.pack(fill="x", pady=(10, 0))

    def _cancelar_pedido_seleccionado(self):
        """R21: Cancelar Pedido Activo en POS"""
        sel = self.tree.selection()
        if not sel:
            messagebox.showwarning("Atención", "Seleccioná un pedido de la lista para cancelar.")
            return

        item = self.tree.item(sel[0])
        val = item.get("values", [])
        id_str = str(val[0]).replace("#", "").strip()
        try:
            idpedido = int(id_str)
        except ValueError:
            messagebox.showerror("Error", "ID de pedido inválido.")
            return

        estado_actual = str(val[7]) if len(val) > 7 else ""
        if estado_actual == "Cancelado":
            messagebox.showinfo("Aviso", f"El pedido #{idpedido} ya está cancelado.")
            return

        # Diálogo de confirmación e ingreso de motivo
        top_canc = tk.Toplevel(self.win)
        top_canc.title("Cancelar Pedido Activo")
        top_canc.geometry("400x260")
        top_canc.resizable(False, False)
        top_canc.configure(bg=COLOR_WHITE)
        top_canc.transient(self.win)
        top_canc.grab_set()

        lbl_t = tk.Label(top_canc, text=f"❌ Cancelar Comanda #{idpedido}", font=(FONT_FAMILY, 12, "bold"), fg="#DC2626", bg=COLOR_WHITE)
        lbl_t.pack(pady=(16, 8))

        lbl_m = tk.Label(top_canc, text="Ingresá el motivo de la cancelación:", font=(FONT_FAMILY, 9), fg=COLOR_TEXT, bg=COLOR_WHITE)
        lbl_m.pack(pady=(0, 6))

        entry_motivo = tk.Entry(top_canc, font=(FONT_FAMILY, 10), bg=COLOR_LIGHT_BG, relief="solid", bd=1)
        entry_motivo.insert(0, "Cancelado por el cliente en caja")
        entry_motivo.pack(fill="x", padx=20, ipady=6, pady=(0, 16))
        entry_motivo.focus_set()

        def confirmar():
            motivo = entry_motivo.get().strip() or "Cancelado desde POS"
            top_canc.destroy()
            res = api.cancelar_pedido(idpedido, motivo, cancelado_por="Cajero")
            if res.get("ok"):
                messagebox.showinfo("Éxito", f"Comanda #{idpedido} cancelada correctamente.\nCocina fue notificada.")
                self._load_tickets()
            else:
                messagebox.showerror("Error", res.get("error", "No se pudo cancelar el pedido."))

        btn_conf = tk.Button(top_canc, text="Confirmar Cancelación", font=(FONT_FAMILY, 10, "bold"), bg="#DC2626", fg=COLOR_WHITE, relief="flat", cursor="hand2", command=confirmar)
        btn_conf.pack(fill="x", padx=20, ipady=6)

