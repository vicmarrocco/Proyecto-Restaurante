"""
App Cajero - Ventana de Historial de Tickets, Rendimiento y Reimpresión
Consulta de comprobantes del turno, filtro por cajero y ticket de rendimiento oficial.
"""
import tkinter as tk
from tkinter import ttk, messagebox
import threading
from config import *
from api_client import api

class TicketsWindow:
    def __init__(self, parent, cajero_info=None):
        self.parent = parent
        self.cajero_info = cajero_info or {}
        self.win = tk.Toplevel(parent)
        self.win.title("Historial de Comprobantes y Rendimiento del Turno")
        self.win.geometry("900x620")
        self.win.minsize(740, 500)
        self.win.configure(bg=COLOR_LIGHT_BG)
        self.win.grab_set()

        self.pedidos = []
        self.filtro_origen = "todos"  # "todos", "mis_ventas", "takeaway", "salon"

        self._build_ui()
        self._load_tickets()

    def _build_ui(self):
        # 1. Header
        h = tk.Frame(self.win, bg=COLOR_DARK, height=54, padx=15, pady=8)
        h.pack(fill="x")

        head_l = tk.Frame(h, bg=COLOR_DARK)
        head_l.pack(side="left", fill="y")

        tk.Label(
            head_l,
            text="🧾 FACTURAS, TICKETS Y RENDIMIENTO DE CAJA",
            font=(FONT_FAMILY, 12, "bold"),
            fg=COLOR_WHITE,
            bg=COLOR_DARK
        ).pack(anchor="w")

        cajero_nom = self.cajero_info.get("nombre_completo", "Cajero")
        tk.Label(
            head_l,
            text=f"👤 Operador: {cajero_nom} | Terminal: {TERMINAL_NOMBRE}",
            font=(FONT_FAMILY, 8),
            fg="#94A3B8",
            bg=COLOR_DARK
        ).pack(anchor="w")

        head_r = tk.Frame(h, bg=COLOR_DARK)
        head_r.pack(side="right", fill="y")

        btn_rend = tk.Button(
            head_r,
            text="📊 Ticket Rendimiento",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#059669",
            fg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=10,
            pady=4,
            command=self._ver_ticket_rendimiento
        )
        btn_rend.pack(side="left", padx=6)

        btn_ref = tk.Button(
            head_r,
            text="🔄 Actualizar",
            font=(FONT_FAMILY, 9),
            bg="#334155",
            fg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=10,
            pady=4,
            command=self._load_tickets
        )
        btn_ref.pack(side="left")

        # 2. Resumen KPI Strip
        self.kpi_frame = tk.Frame(self.win, bg=COLOR_WHITE, padx=15, pady=8, relief="solid", bd=1)
        self.kpi_frame.pack(fill="x", padx=14, pady=(10, 4))

        self.lbl_kpi_total_tickets = tk.Label(
            self.kpi_frame,
            text="Tickets Emitidos: 0",
            font=(FONT_FAMILY, 9, "bold"),
            fg=COLOR_TEXT,
            bg=COLOR_WHITE
        )
        self.lbl_kpi_total_tickets.pack(side="left", padx=10)

        self.lbl_kpi_recaudacion = tk.Label(
            self.kpi_frame,
            text="Total Facturado: $0",
            font=(FONT_FAMILY, 10, "bold"),
            fg=COLOR_ROJO,
            bg=COLOR_WHITE
        )
        self.lbl_kpi_recaudacion.pack(side="left", padx=15)

        self.lbl_kpi_ticket_promedio = tk.Label(
            self.kpi_frame,
            text="Ticket Promedio: $0",
            font=(FONT_FAMILY, 9),
            fg="#475569",
            bg=COLOR_WHITE
        )
        self.lbl_kpi_ticket_promedio.pack(side="left", padx=10)

        # Filtro de Origen
        self.filtro_var = tk.StringVar(value="todos")
        filtro_box = tk.Frame(self.kpi_frame, bg=COLOR_WHITE)
        filtro_box.pack(side="right")

        for key, lbl in [("todos", "Todos"), ("mis_ventas", "Mis Tickets"), ("takeaway", "Take Away"), ("salon", "Salón")]:
            rb = tk.Radiobutton(
                filtro_box,
                text=lbl,
                variable=self.filtro_var,
                value=key,
                bg=COLOR_WHITE,
                font=(FONT_FAMILY, 8, "bold"),
                fg=COLOR_TEXT,
                command=self._aplicar_filtro
            )
            rb.pack(side="left", padx=4)

        # 3. Contenedor Tabla
        container = tk.Frame(self.win, bg=COLOR_WHITE, padx=10, pady=10, relief="solid", bd=1)
        container.pack(fill="both", expand=True, padx=14, pady=6)

        columns = ("id", "factura", "hora", "cliente", "tipo", "pago", "total", "cajero", "estado")
        self.tree = ttk.Treeview(container, columns=columns, show="headings", selectmode="browse")

        self.tree.heading("id", text="Comanda #")
        self.tree.heading("factura", text="Factura B")
        self.tree.heading("hora", text="Hora")
        self.tree.heading("cliente", text="Cliente")
        self.tree.heading("tipo", text="Modalidad")
        self.tree.heading("pago", text="Medio Pago")
        self.tree.heading("total", text="Total")
        self.tree.heading("cajero", text="Cajero")
        self.tree.heading("estado", text="Estado")

        self.tree.column("id", width=75, anchor="center")
        self.tree.column("factura", width=125, anchor="center")
        self.tree.column("hora", width=70, anchor="center")
        self.tree.column("cliente", width=130, anchor="w")
        self.tree.column("tipo", width=100, anchor="center")
        self.tree.column("pago", width=90, anchor="center")
        self.tree.column("total", width=90, anchor="e")
        self.tree.column("cajero", width=110, anchor="w")
        self.tree.column("estado", width=95, anchor="center")

        scroll = ttk.Scrollbar(container, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=scroll.set)

        self.tree.pack(side="left", fill="both", expand=True)
        scroll.pack(side="right", fill="y")

        # 4. Botón de Reimpresión
        btn_f = tk.Frame(self.win, bg=COLOR_LIGHT_BG, padx=14, pady=10)
        btn_f.pack(fill="x")

        btn_reimprimir = tk.Button(
            btn_f,
            text="🖨 Ver / Reimprimir Ticket Seleccionado",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=16,
            pady=8,
            command=self._reimprimir_ticket
        )
        btn_reimprimir.pack(side="right")

    def _load_tickets(self):
        for item in self.tree.get_children():
            self.tree.delete(item)

        def run_fetch():
            res = api._get("/pos/pedidos.php", {"fecha": "hoy"})
            self.win.after(0, lambda: self._on_tickets_loaded(res))

        threading.Thread(target=run_fetch, daemon=True).start()

    def _on_tickets_loaded(self, res):
        if not res.get("ok"):
            return
        self.pedidos = res.get("pedidos", [])
        self._aplicar_filtro()

    def _aplicar_filtro(self):
        filtro = self.filtro_var.get()
        cajero_id = self.cajero_info.get("id")

        for item in self.tree.get_children():
            self.tree.delete(item)

        filtrados = []
        tot_recaudado = 0.0

        for p in self.pedidos:
            tipo = p.get("tipo_entrega", "")
            es_mi_cajero = (cajero_id and p.get("idempleado_cajero") == cajero_id)

            if filtro == "mis_ventas" and not es_mi_cajero:
                continue
            if filtro == "takeaway" and tipo != "takeaway":
                continue
            if filtro == "salon" and tipo != "salon":
                continue

            filtrados.append(p)
            tot_recaudado += float(p.get("total", 0))

        # Actualizar KPIs
        cant = len(filtrados)
        self.lbl_kpi_total_tickets.configure(text=f"Tickets ({filtro}): {cant}")
        self.lbl_kpi_recaudacion.configure(text=f"Total: ${tot_recaudado:,.0f}".replace(",", "."))
        prom = (tot_recaudado / cant) if cant > 0 else 0
        self.lbl_kpi_ticket_promedio.configure(text=f"Promedio: ${prom:,.0f}".replace(",", "."))

        for p in filtrados:
            tipo_txt = p.get("tipo_entrega", "").upper()
            if p.get("numero_mesa"):
                tipo_txt += f" (Mesa {p['numero_mesa']})"

            pid = p.get("idpedido")
            self.tree.insert(
                "",
                "end",
                iid=str(pid),
                values=(
                    f"#{pid}",
                    p.get("numero_factura", f"FAC-B-{pid}"),
                    p.get("hora", ""),
                    p.get("cliente", "Consumidor Final"),
                    tipo_txt,
                    p.get("metodo_pago", "").upper(),
                    f"${float(p.get('total', 0)):,.0f}".replace(",", "."),
                    p.get("cajero_nombre", "Cajero"),
                    p.get("estado", "Recibido")
                )
            )

    def _ver_ticket_rendimiento(self):
        cajero_id = self.cajero_info.get("id")
        reg_id = self.cajero_info.get("idregistro_turno")
        cajero_nom = self.cajero_info.get("nombre_completo", "Cajero")

        def fetch():
            res = api.obtener_resumen_turno(cajero_id, reg_id)
            self.win.after(0, lambda: self._mostrar_modal_rendimiento(res))

        threading.Thread(target=fetch, daemon=True).start()

    def _mostrar_modal_rendimiento(self, res):
        rend = res.get("rendimiento", {}) if res.get("ok") else {}
        turno = res.get("turno", {}) if res.get("ok") else {}

        t_win = tk.Toplevel(self.win)
        t_win.title("Ticket de Rendimiento de Cajero · Mostaza")
        t_win.geometry("420x540")
        t_win.configure(bg="#0F172A")
        t_win.grab_set()

        card = tk.Frame(t_win, bg="#FFFFFF", padx=20, pady=20)
        card.pack(fill="both", expand=True, padx=16, pady=16)

        tk.Label(card, text="🍔 MOSTAZA FAST FOOD", font=(FONT_FAMILY, 14, "bold"), fg=COLOR_ROJO, bg="#FFFFFF").pack()
        tk.Label(card, text="TICKET DE RENDIMIENTO OFICIAL", font=(FONT_FAMILY, 9, "bold"), fg="#64748B", bg="#FFFFFF").pack(pady=(2, 10))

        tk.Frame(card, height=1, bg="#CBD5E1").pack(fill="x", pady=6)

        cajero_nom = self.cajero_info.get("nombre_completo", "Cajero")
        datos = [
            ("Cajero:", cajero_nom),
            ("Terminal:", TERMINAL_NOMBRE),
            ("Turno:", turno.get("nombre_turno", self.cajero_info.get("turno", "Turno"))),
            ("Ingreso:", turno.get("hora_ingreso", self.cajero_info.get("hora_entrada", "--:--"))),
            ("Duración:", turno.get("duracion_horas_texto", "En curso")),
            ("Total Ventas Realizadas:", f"{rend.get('ventas_realizadas', 0)} tickets"),
            ("Total Recaudado:", f"${float(rend.get('total_recaudado', 0)):,.0f}".replace(",", ".")),
            ("Ticket Promedio:", f"${float(rend.get('ticket_promedio', 0)):,.0f}".replace(",", ".")),
        ]

        for k, v in datos:
            row = tk.Frame(card, bg="#FFFFFF")
            row.pack(fill="x", pady=3)
            tk.Label(row, text=k, font=(FONT_FAMILY, 9), fg="#64748B", bg="#FFFFFF").pack(side="left")
            tk.Label(row, text=v, font=(FONT_FAMILY, 9, "bold"), fg=COLOR_TEXT, bg="#FFFFFF").pack(side="right")

        tk.Frame(card, height=1, bg="#CBD5E1").pack(fill="x", pady=10)

        # Desglose medios de pago
        desglose = rend.get("desglose_pagos", {})
        if desglose:
            tk.Label(card, text="DESGLOSE POR MEDIO DE PAGO:", font=(FONT_FAMILY, 8, "bold"), fg="#334155", bg="#FFFFFF", anchor="w").pack(fill="x")
            for m, val in desglose.items():
                r_m = tk.Frame(card, bg="#FFFFFF")
                r_m.pack(fill="x", pady=1)
                tk.Label(r_m, text=f"• {m.capitalize()}:", font=(FONT_FAMILY, 8), fg="#64748B", bg="#FFFFFF").pack(side="left")
                tk.Label(r_m, text=f"${float(val):,.0f}".replace(",", "."), font=(FONT_FAMILY, 8, "bold"), fg="#0F172A", bg="#FFFFFF").pack(side="right")

        btn_c = tk.Button(
            card,
            text="✓ Cerrar Ticket",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            command=t_win.destroy
        )
        btn_c.pack(fill="x", pady=(16, 0))

    def _reimprimir_ticket(self):
        sel = self.tree.selection()
        if not sel:
            messagebox.showinfo("Selección", "Seleccioná un ticket de la lista para ver el comprobante.", parent=self.win)
            return

        pid = int(sel[0])
        p = next((x for x in self.pedidos if x.get("idpedido") == pid), None)
        if not p:
            return

        t_win = tk.Toplevel(self.win)
        t_win.title(f"Ticket #{pid} · Reimpresión Mostaza POS")
        t_win.geometry("380x540")
        t_win.configure(bg="#1E293B")
        t_win.grab_set()

        paper = tk.Frame(t_win, bg="#FFFFFF", padx=16, pady=16)
        paper.pack(fill="both", expand=True, padx=14, pady=14)

        items_txt = ""
        for it in p.get("items", []):
            items_txt += f"{it.get('cantidad', 1)}x {it.get('descripcion', '')[:20]:<20} ${it.get('subtotal', 0):>7,.0f}\n"

        ticket_text = f"""
================================
         MOSTAZA FAST FOOD
   Spider Web · Sistema POS
 CUIT: 30-71234567-9 · IVA Resp.
================================
Factura B: {p.get('numero_factura', f'FAC-B-{pid}')}
Fecha/Hora: {p.get('fecha', '')}
Modalidad: {p.get('tipo_entrega', '').upper()}
Cliente: {p.get('cliente', 'Consumidor Final')}
Cajero: {p.get('cajero_nombre', 'Cajero')}
================================
ITEMS:
{items_txt}--------------------------------
Subtotal:          ${float(p.get('subtotal', p.get('total', 0))):,.0f}
Descuento:         ${float(p.get('descuento', 0)):,.0f}
TOTAL COBRADO:     ${float(p.get('total', 0)):,.0f}
--------------------------------
Medio de Pago:     {p.get('metodo_pago', '').upper()}
Estado:            {p.get('estado', 'Recibido')}
================================
CAE: {p.get('cae', '74123456789012')}  Vto: {p.get('cae_vto', '10 días')}
================================
   ¡GRACIAS POR SU COMPRA!
================================
"""
        txt = tk.Text(paper, font=("Courier New", 8), bg="#FFFFFF", relief="flat")
        txt.insert(tk.END, ticket_text)
        txt.config(state="disabled")
        txt.pack(fill="both", expand=True)

        btn_c = tk.Button(
            paper,
            text="✓ Cerrar",
            font=(FONT_FAMILY, 9, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            relief="flat",
            command=t_win.destroy
        )
        btn_c.pack(fill="x", pady=(8, 0))
