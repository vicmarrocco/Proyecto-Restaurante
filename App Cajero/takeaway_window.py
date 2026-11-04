"""
App Cajero - Ventana de Gestión Take Away / Mostrador
Visualización y entrega en tiempo real de pedidos para llevar (Zero-Refresh).
"""
import tkinter as tk
from tkinter import ttk, messagebox
import threading
from datetime import datetime
from config import *
from api_client import api

class TakeAwayWindow:
    def __init__(self, parent, cajero_info=None, on_update=None):
        self.parent = parent
        self.cajero_info = cajero_info or {}
        self.on_update = on_update
        self.is_active = True
        
        self.win = tk.Toplevel(parent)
        self.win.title(f"🛍️ Gestión Take Away · {TERMINAL_NOMBRE}")
        self.win.geometry("960x650")
        self.win.minsize(800, 520)
        self.win.configure(bg=COLOR_LIGHT_BG)
        self.win.protocol("WM_DELETE_WINDOW", self._on_close)
        
        self.filtro_tab = "todos"  # "todos", "listos", "cocina", "entregados"
        self.pedidos = []
        self.selected_pedido = None
        
        self._build_ui()
        self._load_takeaway()
        self._start_auto_refresh()

    def _on_close(self):
        self.is_active = False
        if self.on_update:
            try:
                self.on_update()
            except Exception:
                pass
        self.win.destroy()

    def _build_ui(self):
        # 1. Header
        header = tk.Frame(self.win, bg=COLOR_DARK, height=60, padx=18, pady=10)
        header.pack(fill="x")

        head_left = tk.Frame(header, bg=COLOR_DARK)
        head_left.pack(side="left", fill="y")

        lbl_tit = tk.Label(
            head_left,
            text="🛍️ MOSTAZA TAKE AWAY · RETIRO EN MOSTRADOR",
            font=(FONT_FAMILY, 13, "bold"),
            fg=COLOR_WHITE,
            bg=COLOR_DARK
        )
        lbl_tit.pack(anchor="w")

        lbl_sub = tk.Label(
            head_left,
            text=f"Terminal: {TERMINAL_NOMBRE} | Caja #{TERMINAL_NUMERO:02d} | Zero-Refresh Activo",
            font=(FONT_FAMILY, 9),
            fg="#94A3B8",
            bg=COLOR_DARK
        )
        lbl_sub.pack(anchor="w")

        head_right = tk.Frame(header, bg=COLOR_DARK)
        head_right.pack(side="right", fill="y")

        self.lbl_live = tk.Label(
            head_right,
            text="● EN VIVO",
            font=(FONT_FAMILY, 9, "bold"),
            fg="#10B981",
            bg=COLOR_DARK
        )
        self.lbl_live.pack(side="left", padx=10)

        btn_ref = tk.Button(
            head_right,
            text="🔄 Actualizar",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#334155",
            fg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=10,
            pady=4,
            command=self._load_takeaway
        )
        btn_ref.pack(side="left")

        # 2. Barra de Filtros / Tabs
        tabs_frame = tk.Frame(self.win, bg=COLOR_WHITE, padx=15, pady=8, relief="solid", bd=1)
        tabs_frame.pack(fill="x", padx=14, pady=(10, 6))

        self.tab_buttons = {}
        tabs = [
            ("todos", "📋 Todos"),
            ("listos", "🔔 ¡Listos en Mostrador!"),
            ("cocina", "🍳 En Cocina"),
            ("entregados", "✓ Entregados Hoy")
        ]

        for key, label in tabs:
            btn = tk.Button(
                tabs_frame,
                text=label,
                font=(FONT_FAMILY, 9, "bold"),
                bg=COLOR_ROJO if key == self.filtro_tab else "#F1F5F9",
                fg=COLOR_WHITE if key == self.filtro_tab else COLOR_TEXT,
                activebackground=COLOR_ROJO,
                activeforeground=COLOR_WHITE,
                relief="flat",
                cursor="hand2",
                padx=12,
                pady=5,
                command=lambda k=key: self._set_filtro(k)
            )
            btn.pack(side="left", padx=4)
            self.tab_buttons[key] = btn

        # 3. Contenedor Principal: Tabla Arriba y Detalle Abajo
        main_box = tk.Frame(self.win, bg=COLOR_LIGHT_BG)
        main_box.pack(fill="both", expand=True, padx=14, pady=6)

        # 3.1 Tabla
        table_card = tk.Frame(main_box, bg=COLOR_WHITE, relief="solid", bd=1)
        table_card.pack(fill="both", expand=True, pady=(0, 8))

        columns = ("id", "hora", "cliente", "items", "pago", "total", "cocinero", "estado")
        self.tree = ttk.Treeview(table_card, columns=columns, show="headings", selectmode="browse", height=9)

        self.tree.heading("id", text="Comanda #")
        self.tree.heading("hora", text="Hora")
        self.tree.heading("cliente", text="Cliente")
        self.tree.heading("items", text="Productos")
        self.tree.heading("pago", text="Pago")
        self.tree.heading("total", text="Total")
        self.tree.heading("cocinero", text="Cocinero")
        self.tree.heading("estado", text="Estado Actual")

        self.tree.column("id", width=85, anchor="center")
        self.tree.column("hora", width=70, anchor="center")
        self.tree.column("cliente", width=140, anchor="w")
        self.tree.column("items", width=220, anchor="w")
        self.tree.column("pago", width=90, anchor="center")
        self.tree.column("total", width=95, anchor="e")
        self.tree.column("cocinero", width=110, anchor="w")
        self.tree.column("estado", width=125, anchor="center")

        scroll = ttk.Scrollbar(table_card, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=scroll.set)

        self.tree.pack(side="left", fill="both", expand=True)
        scroll.pack(side="right", fill="y")
        self.tree.bind("<<TreeviewSelect>>", self._on_select_item)

        # Configurar tags de color para filas
        self.tree.tag_configure("listo", background="#DCFCE7", foreground="#166534")  # Verde
        self.tree.tag_configure("cocina", background="#FEF3C7", foreground="#92400E") # Amarillo
        self.tree.tag_configure("entregado", background="#F8FAFC", foreground="#64748B") # Gris

        # 3.2 Panel de Detalle y Acción
        detail_card = tk.Frame(main_box, bg=COLOR_WHITE, relief="solid", bd=1, padx=14, pady=12)
        detail_card.pack(fill="x", side="bottom")

        self.lbl_detail_title = tk.Label(
            detail_card,
            text="Seleccioná un pedido de la tabla superior para ver detalles y gestionar entrega.",
            font=(FONT_FAMILY, 10, "bold"),
            fg=COLOR_TEXT,
            bg=COLOR_WHITE,
            anchor="w"
        )
        self.lbl_detail_title.pack(fill="x")

        self.lbl_detail_items = tk.Label(
            detail_card,
            text="",
            font=(FONT_FAMILY, 9),
            fg="#475569",
            bg=COLOR_WHITE,
            justify="left",
            anchor="w"
        )
        self.lbl_detail_items.pack(fill="x", pady=4)

        # Botones de Acción Inferiores
        actions_bar = tk.Frame(detail_card, bg=COLOR_WHITE)
        actions_bar.pack(fill="x", pady=(6, 0))

        self.btn_entregar = tk.Button(
            actions_bar,
            text="🛎️ CONFIRMAR ENTREGA EN MOSTRADOR (RETIRADO)",
            font=(FONT_FAMILY, 11, "bold"),
            bg="#10B981",
            fg=COLOR_WHITE,
            activebackground="#059669",
            activeforeground=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=16,
            pady=8,
            state="disabled",
            command=self._confirmar_entrega
        )
        self.btn_entregar.pack(side="left")

        self.btn_ver_factura = tk.Button(
            actions_bar,
            text="🖨 Ver Factura B",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#F1F5F9",
            fg=COLOR_TEXT,
            relief="solid",
            bd=1,
            cursor="hand2",
            padx=12,
            pady=8,
            state="disabled",
            command=self._ver_factura
        )
        self.btn_ver_factura.pack(side="right")

    def _start_auto_refresh(self):
        """Auto-polling cada 3 segundos."""
        if not self.is_active:
            return
        self._load_takeaway(silent=True)
        self.win.after(3000, self._start_auto_refresh)

    def _set_filtro(self, key):
        self.filtro_tab = key
        for k, btn in self.tab_buttons.items():
            if k == key:
                btn.configure(bg=COLOR_ROJO, fg=COLOR_WHITE)
            else:
                btn.configure(bg="#F1F5F9", fg=COLOR_TEXT)
        self._render_pedidos()

    def _load_takeaway(self, silent=False):
        def fetch():
            res = api.obtener_pedidos_takeaway()
            if self.is_active:
                self.win.after(0, lambda: self._on_data_loaded(res, silent))
        threading.Thread(target=fetch, daemon=True).start()

    def _on_data_loaded(self, res, silent=False):
        if not self.is_active:
            return
        if res.get("ok"):
            self.pedidos = res.get("pedidos", [])
            self._render_pedidos()
        elif not silent:
            messagebox.showerror("Error", res.get("error", "No se pudieron obtener pedidos Take Away"), parent=self.win)

    def _render_pedidos(self):
        selected_id = self.selected_pedido.get("idpedido") if self.selected_pedido else None

        # Limpiar
        for item in self.tree.get_children():
            self.tree.delete(item)

        # Filtrar
        pedidos_mostrar = []
        cant_listos = 0
        cant_cocina = 0
        cant_entregados = 0

        for p in self.pedidos:
            est = p.get("estado", "")
            is_listo = est in ["Listo", "Listo para Despacho"]
            is_cocina = est in ["En Cocina", "En Preparación", "En Preparacion", "Recibido"]
            is_entregado = est == "Entregado"

            if is_listo: cant_listos += 1
            if is_cocina: cant_cocina += 1
            if is_entregado: cant_entregados += 1

            if self.filtro_tab == "listos" and not is_listo:
                continue
            if self.filtro_tab == "cocina" and not is_cocina:
                continue
            if self.filtro_tab == "entregados" and not is_entregado:
                continue

            pedidos_mostrar.append(p)

        # Actualizar textos de tabs
        self.tab_buttons["todos"].configure(text=f"📋 Todos ({len(self.pedidos)})")
        self.tab_buttons["listos"].configure(text=f"🔔 ¡Listos! ({cant_listos})")
        self.tab_buttons["cocina"].configure(text=f"🍳 En Cocina ({cant_cocina})")
        self.tab_buttons["entregados"].configure(text=f"✓ Entregados ({cant_entregados})")

        # Destacar tab de listos si hay alguno esperando
        if cant_listos > 0 and self.filtro_tab != "listos":
            self.tab_buttons["listos"].configure(bg="#DCFCE7", fg="#166534")

        # Insertar filas
        for p in pedidos_mostrar:
            pid = p.get("idpedido")
            est = p.get("estado", "")
            tag = ""
            if est in ["Listo", "Listo para Despacho"]:
                tag = "listo"
                est_txt = "🔔 ¡LISTO EN MOSTRADOR!"
            elif est in ["En Cocina", "En Preparación", "En Preparacion"]:
                tag = "cocina"
                est_txt = "🍳 En Cocina KDS"
            elif est == "Recibido":
                tag = "cocina"
                est_txt = "⏳ En Espera"
            elif est == "Entregado":
                tag = "entregado"
                est_txt = "✓ Entregado"
            else:
                est_txt = est

            items_list = p.get("items", [])
            items_str = ", ".join([f"{it.get('cantidad',1)}x {it.get('descripcion','')}" for it in items_list]) or "Productos varios"

            item_id = self.tree.insert(
                "",
                "end",
                iid=str(pid),
                values=(
                    f"#{pid}",
                    p.get("hora", ""),
                    p.get("cliente", "Consumidor Final"),
                    items_str,
                    p.get("metodo_pago", "").upper(),
                    p.get("total_formateado", f"${p.get('total', 0):,.0f}"),
                    p.get("cocinero_nombre") or "--",
                    est_txt
                ),
                tags=(tag,)
            )

            if selected_id and str(pid) == str(selected_id):
                self.tree.selection_set(item_id)

    def _on_select_item(self, event):
        sel = self.tree.selection()
        if not sel:
            self.selected_pedido = None
            self.btn_entregar.configure(state="disabled")
            self.btn_ver_factura.configure(state="disabled")
            self.lbl_detail_title.configure(text="Seleccioná un pedido de la tabla superior para ver detalles y gestionar entrega.")
            self.lbl_detail_items.configure(text="")
            return

        pid = int(sel[0])
        self.selected_pedido = next((p for p in self.pedidos if p.get("idpedido") == pid), None)
        if not self.selected_pedido:
            return

        p = self.selected_pedido
        est = p.get("estado", "")
        self.lbl_detail_title.configure(
            text=f"Comanda #{p.get('idpedido')} · Factura: {p.get('numero_factura', 'FAC-B')} · Cliente: {p.get('cliente', 'Consumidor Final')} · Total: {p.get('total_formateado')}"
        )

        items_det = []
        for it in p.get("items", []):
            mod = f" ({it.get('modificadores')})" if it.get("modificadores") else ""
            items_det.append(f"  • {it.get('cantidad')}x {it.get('descripcion')}{mod} - ${it.get('subtotal', 0):,.0f}")
        self.lbl_detail_items.configure(text="\n".join(items_det) or "Sin detalle de ítems")

        self.btn_ver_factura.configure(state="normal")

        # Botón entregar solo si está Listo o en Cocina
        if est in ["Listo", "Listo para Despacho"]:
            self.btn_entregar.configure(state="normal", text="🛎️ ENTREGAR AL CLIENTE (PEDIDO RETIRADO)", bg="#10B981")
        elif est in ["En Cocina", "En Preparación", "En Preparacion", "Recibido"]:
            self.btn_entregar.configure(state="normal", text="⚠️ Marcar como Retirado de Mostrador", bg="#D97706")
        else:
            self.btn_entregar.configure(state="disabled", text="✓ Pedido ya entregado", bg="#94A3B8")

    def _confirmar_entrega(self):
        if not self.selected_pedido:
            return
        p = self.selected_pedido
        pid = p.get("idpedido")
        cli = p.get("cliente", "Consumidor Final")

        if not messagebox.askyesno("Confirmar Entrega Take Away", f"¿Confirmás que el cliente '{cli}' retiró la comanda #{pid} por mostrador?", parent=self.win):
            return

        cajero_nom = self.cajero_info.get("nombre_completo", "Cajero")
        cajero_id = self.cajero_info.get("id")

        def run_entrega():
            res = api.cambiar_estado_pedido(
                pid,
                "Entregado",
                empleado_nombre=cajero_nom,
                idempleado=cajero_id,
                rol="Cajero",
                detalles=f"Pedido retirado en mostrador Take Away de {TERMINAL_NOMBRE}"
            )
            self.win.after(0, lambda: self._on_entrega_finish(res))

        threading.Thread(target=run_entrega, daemon=True).start()

    def _on_entrega_finish(self, res):
        if res.get("ok"):
            messagebox.showinfo("Entrega Confirmada", "¡Comanda marcada como entregada con éxito!", parent=self.win)
            self._load_takeaway()
            if self.on_update:
                try:
                    self.on_update()
                except Exception:
                    pass
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo actualizar el estado"), parent=self.win)

    def _ver_factura(self):
        if not self.selected_pedido:
            return
        from tickets_window import TicketsWindow
        # Abre la ventana de tickets con el detalle
        t_win = TicketsWindow(self.win)
