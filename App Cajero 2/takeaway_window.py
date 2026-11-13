"""
App Cajero - Ventana de Gestión Take Away / Mostrador
Visualización y entrega en tiempo real de pedidos para llevar (Zero-Refresh).
Soporta administración de pedidos Online (Web) y Presenciales (hechos en Caja),
con entrega en mostrador y cancelación con devolución automática de dinero.
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
        self.win.geometry("1020x680")
        self.win.minsize(860, 540)
        self.win.configure(bg=COLOR_LIGHT_BG)
        self.win.protocol("WM_DELETE_WINDOW", self._on_close)
        
        self.filtro_tab = "todos"  # "todos", "listos", "online", "presencial", "cocina", "entregados", "cancelados"
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
            text=f"Terminal: {TERMINAL_NOMBRE} | Caja #{TERMINAL_NUMERO:02d} | Pedidos Online y Presenciales en Vivo",
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
        tabs_frame = tk.Frame(self.win, bg=COLOR_WHITE, padx=12, pady=8, relief="solid", bd=1)
        tabs_frame.pack(fill="x", padx=14, pady=(10, 6))

        self.tab_buttons = {}
        tabs = [
            ("todos", "📋 Todos"),
            ("listos", "🔔 ¡Listos!"),
            ("online", "🌐 Online Web"),
            ("presencial", "🏪 Presencial Caja"),
            ("cocina", "🍳 En Cocina"),
            ("entregados", "✓ Entregados"),
            ("cancelados", "❌ Cancelados")
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
                padx=10,
                pady=5,
                command=lambda k=key: self._set_filtro(k)
            )
            btn.pack(side="left", padx=3)
            self.tab_buttons[key] = btn

        # 3. Contenedor Principal: Tabla Arriba y Detalle Abajo
        main_box = tk.Frame(self.win, bg=COLOR_LIGHT_BG)
        main_box.pack(fill="both", expand=True, padx=14, pady=6)

        # 3.1 Tabla
        table_card = tk.Frame(main_box, bg=COLOR_WHITE, relief="solid", bd=1)
        table_card.pack(fill="both", expand=True, pady=(0, 8))

        columns = ("id", "hora", "origen", "cliente", "items", "pago", "total", "cocinero", "estado")
        self.tree = ttk.Treeview(table_card, columns=columns, show="headings", selectmode="browse", height=9)

        self.tree.heading("id", text="Comanda #")
        self.tree.heading("hora", text="Hora")
        self.tree.heading("origen", text="Origen")
        self.tree.heading("cliente", text="Cliente")
        self.tree.heading("items", text="Productos")
        self.tree.heading("pago", text="Pago")
        self.tree.heading("total", text="Total")
        self.tree.heading("cocinero", text="Cocinero")
        self.tree.heading("estado", text="Estado Actual")

        self.tree.column("id", width=80, anchor="center")
        self.tree.column("hora", width=65, anchor="center")
        self.tree.column("origen", width=110, anchor="center")
        self.tree.column("cliente", width=130, anchor="w")
        self.tree.column("items", width=220, anchor="w")
        self.tree.column("pago", width=95, anchor="center")
        self.tree.column("total", width=90, anchor="e")
        self.tree.column("cocinero", width=105, anchor="w")
        self.tree.column("estado", width=135, anchor="center")

        scroll = ttk.Scrollbar(table_card, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=scroll.set)

        self.tree.pack(side="left", fill="both", expand=True)
        scroll.pack(side="right", fill="y")
        self.tree.bind("<<TreeviewSelect>>", self._on_select_item)

        # Configurar tags de color para filas
        self.tree.tag_configure("listo", background="#DCFCE7", foreground="#166534")      # Verde
        self.tree.tag_configure("cocina", background="#FEF3C7", foreground="#92400E")     # Amarillo
        self.tree.tag_configure("entregado", background="#F8FAFC", foreground="#64748B")  # Gris
        self.tree.tag_configure("cancelado", background="#FEE2E2", foreground="#991B1B")  # Rojo

        # 3.2 Panel de Detalle y Acción
        detail_card = tk.Frame(main_box, bg=COLOR_WHITE, relief="solid", bd=1, padx=14, pady=12)
        detail_card.pack(fill="x", side="bottom")

        self.lbl_detail_title = tk.Label(
            detail_card,
            text="Seleccioná un pedido de la tabla superior para ver detalles y gestionar entrega o cancelación.",
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

        # Botón 1: Confirmar Entrega / Retirado
        self.btn_entregar = tk.Button(
            actions_bar,
            text="🛎️ CONFIRMAR ENTREGA (RETIRADO)",
            font=(FONT_FAMILY, 10, "bold"),
            bg="#10B981",
            fg=COLOR_WHITE,
            activebackground="#059669",
            activeforeground=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=14,
            pady=8,
            state="disabled",
            command=self._confirmar_entrega
        )
        self.btn_entregar.pack(side="left", padx=(0, 8))

        # Botón 2: Cancelar Pedido y Devolver Dinero
        self.btn_cancelar = tk.Button(
            actions_bar,
            text="❌ CANCELAR Y DEVOLVER DINERO",
            font=(FONT_FAMILY, 10, "bold"),
            bg="#EF4444",
            fg=COLOR_WHITE,
            activebackground="#DC2626",
            activeforeground=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=14,
            pady=8,
            state="disabled",
            command=self._cancelar_pedido
        )
        self.btn_cancelar.pack(side="left")

        # Botón 3: Ver Factura Fiscal
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

        # Contadores de filtros
        pedidos_mostrar = []
        cant_listos = 0
        cant_online = 0
        cant_presencial = 0
        cant_cocina = 0
        cant_entregados = 0
        cant_cancelados = 0

        for p in self.pedidos:
            est = p.get("estado", "")
            is_listo = est in ["Listo", "Listo para Despacho"]
            is_cocina = est in ["En Cocina", "En Preparación", "En Preparacion", "Recibido"]
            is_entregado = est == "Entregado"
            is_cancelado = est == "Cancelado"

            # Determinar si es online o presencial
            is_presencial = bool(p.get("idempleado_cajero"))
            is_online = not is_presencial

            if is_listo: cant_listos += 1
            if is_online: cant_online += 1
            if is_presencial: cant_presencial += 1
            if is_cocina: cant_cocina += 1
            if is_entregado: cant_entregados += 1
            if is_cancelado: cant_cancelados += 1

            if self.filtro_tab == "listos" and not is_listo:
                continue
            if self.filtro_tab == "online" and not is_online:
                continue
            if self.filtro_tab == "presencial" and not is_presencial:
                continue
            if self.filtro_tab == "cocina" and not is_cocina:
                continue
            if self.filtro_tab == "entregados" and not is_entregado:
                continue
            if self.filtro_tab == "cancelados" and not is_cancelado:
                continue

            pedidos_mostrar.append(p)

        # Actualizar textos de tabs
        self.tab_buttons["todos"].configure(text=f"📋 Todos ({len(self.pedidos)})")
        self.tab_buttons["listos"].configure(text=f"🔔 ¡Listos! ({cant_listos})")
        self.tab_buttons["online"].configure(text=f"🌐 Online ({cant_online})")
        self.tab_buttons["presencial"].configure(text=f"🏪 Presencial ({cant_presencial})")
        self.tab_buttons["cocina"].configure(text=f"🍳 Cocina ({cant_cocina})")
        self.tab_buttons["entregados"].configure(text=f"✓ Entregados ({cant_entregados})")
        self.tab_buttons["cancelados"].configure(text=f"❌ Cancelados ({cant_cancelados})")

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
                est_txt = "✓ Retirado"
            elif est == "Cancelado":
                tag = "cancelado"
                est_txt = "❌ Cancelado"
            else:
                est_txt = est

            # Origen (Online Web vs Presencial en Caja)
            is_presencial = bool(p.get("idempleado_cajero"))
            origen_txt = "🏪 Presencial" if is_presencial else "🌐 Online Web"

            items_list = p.get("items", [])
            items_str = ", ".join([f"{it.get('cantidad',1)}x {it.get('descripcion','')}" for it in items_list]) or "Productos varios"

            item_id = self.tree.insert(
                "",
                "end",
                iid=str(pid),
                values=(
                    f"#{pid}",
                    p.get("hora", ""),
                    origen_txt,
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
            self.btn_cancelar.configure(state="disabled")
            self.btn_ver_factura.configure(state="disabled")
            self.lbl_detail_title.configure(text="Seleccioná un pedido de la tabla superior para ver detalles y gestionar entrega o cancelación.")
            self.lbl_detail_items.configure(text="")
            return

        pid = int(sel[0])
        self.selected_pedido = next((p for p in self.pedidos if p.get("idpedido") == pid), None)
        if not self.selected_pedido:
            return

        p = self.selected_pedido
        est = p.get("estado", "")
        is_presencial = bool(p.get("idempleado_cajero"))
        origen_label = "🏪 Presencial (Caja)" if is_presencial else "🌐 Online (Sitio Web Mostaza)"

        self.lbl_detail_title.configure(
            text=f"Comanda #{p.get('idpedido')} · {origen_label} · Cliente: {p.get('cliente', 'Consumidor Final')} · Pago: {p.get('metodo_pago','').upper()} · Total: {p.get('total_formateado')}"
        )

        items_det = []
        for it in p.get("items", []):
            mod = f" ({it.get('modificadores')})" if it.get("modificadores") else ""
            items_det.append(f"  • {it.get('cantidad')}x {it.get('descripcion')}{mod} - ${it.get('subtotal', 0):,.0f}")
        self.lbl_detail_items.configure(text="\n".join(items_det) or "Sin detalle de ítems")

        self.btn_ver_factura.configure(state="normal")

        # Botón entregar y cancelar según estado
        if est in ["Listo", "Listo para Despacho"]:
            self.btn_entregar.configure(state="normal", text="🛎️ ENTREGAR AL CLIENTE (RETIRADO)", bg="#10B981")
            self.btn_cancelar.configure(state="normal", text="❌ CANCELAR Y DEVOLVER DINERO", bg="#EF4444")
        elif est in ["En Cocina", "En Preparación", "En Preparacion", "Recibido"]:
            self.btn_entregar.configure(state="normal", text="⚠️ Marcar como Retirado", bg="#D97706")
            self.btn_cancelar.configure(state="normal", text="❌ CANCELAR Y DEVOLVER DINERO", bg="#EF4444")
        elif est == "Entregado":
            self.btn_entregar.configure(state="disabled", text="✓ Pedido ya retirado", bg="#94A3B8")
            self.btn_cancelar.configure(state="normal", text="❌ Anular Entrega y Devolver Dinero", bg="#EF4444")
        elif est == "Cancelado":
            self.btn_entregar.configure(state="disabled", text="❌ Comanda Cancelada", bg="#94A3B8")
            self.btn_cancelar.configure(state="disabled", text="✓ Dinero ya reembolsado", bg="#94A3B8")
        else:
            self.btn_entregar.configure(state="disabled", text="✓ No modificable", bg="#94A3B8")
            self.btn_cancelar.configure(state="disabled")

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
            messagebox.showinfo("Entrega Confirmada", "¡Comanda marcada como entregada (retirada) con éxito!", parent=self.win)
            self._load_takeaway()
            if self.on_update:
                try:
                    self.on_update()
                except Exception:
                    pass
        else:
            messagebox.showerror("Error", res.get("error", "No se pudo actualizar el estado"), parent=self.win)

    def _cancelar_pedido(self):
        if not self.selected_pedido:
            return
        p = self.selected_pedido
        pid = p.get("idpedido")
        cli = p.get("cliente", "Consumidor Final")
        tot = p.get("total_formateado", f"${p.get('total', 0):,.0f}")
        met = p.get("metodo_pago", "").upper()
        est = p.get("estado", "")

        if est == "Cancelado":
            messagebox.showinfo("Pedido Cancelado", "Este pedido ya se encuentra cancelado.", parent=self.win)
            return

        # Modal personalizado de confirmación y devolución
        dialog = tk.Toplevel(self.win)
        dialog.title(f"Cancelar Comanda #{pid} y Devolver Dinero")
        dialog.geometry("500x340")
        dialog.resizable(False, False)
        dialog.configure(bg=COLOR_WHITE)
        dialog.grab_set()

        # Header dialog
        tk.Label(
            dialog,
            text="⚠️ CANCELACIÓN Y DEVOLUCIÓN DE DINERO",
            font=(FONT_FAMILY, 11, "bold"),
            fg=COLOR_ROJO,
            bg=COLOR_WHITE
        ).pack(pady=(15, 4))

        tk.Label(
            dialog,
            text=f"Comanda #{pid} · Cliente: {cli}\nTotal a reintegrar: {tot} ({met})",
            font=(FONT_FAMILY, 10, "bold"),
            fg=COLOR_TEXT,
            bg=COLOR_WHITE
        ).pack(pady=4)

        if "MERCADO" in met or "MP" in met:
            info_txt = "ℹ️ El dinero se reembolsará automáticamente a la cuenta de Mercado Pago del cliente vía API de Devoluciones."
            bg_info = "#EFF6FF"
            fg_info = "#1D4ED8"
        else:
            info_txt = "ℹ️ Se registrará el egreso contable en la caja y deberás entregar el dinero en efectivo al cliente en mano."
            bg_info = "#FEF3C7"
            fg_info = "#92400E"

        tk.Label(
            dialog,
            text=info_txt,
            font=(FONT_FAMILY, 9),
            fg=fg_info,
            bg=bg_info,
            padx=10,
            pady=8,
            wraplength=450
        ).pack(fill="x", padx=16, pady=6)

        tk.Label(
            dialog,
            text="Motivo de la cancelación:",
            font=(FONT_FAMILY, 9, "bold"),
            fg=COLOR_TEXT,
            bg=COLOR_WHITE
        ).pack(anchor="w", padx=18, pady=(8, 2))

        txt_motivo = tk.Entry(dialog, font=(FONT_FAMILY, 10), bg="#F8FAFC", relief="solid", bd=1)
        txt_motivo.insert(0, "Solicitado por cliente en mostrador Take Away")
        txt_motivo.pack(fill="x", padx=18, pady=4)
        txt_motivo.focus_set()

        # Botones dialog
        btn_box = tk.Frame(dialog, bg=COLOR_WHITE)
        btn_box.pack(fill="x", padx=18, pady=16)

        def ejecutar_cancelacion():
            motivo = txt_motivo.get().strip() or "Cancelado en mostrador"
            dialog.destroy()
            
            cajero_nom = self.cajero_info.get("nombre_completo", "Cajero")
            cajero_id = self.cajero_info.get("id")

            def run_cancel():
                res = api.cancelar_pedido_con_devolucion(
                    pid,
                    motivo=motivo,
                    devolver_dinero=True,
                    cajero_nombre=cajero_nom,
                    idempleado=cajero_id,
                    rol="Cajero"
                )
                self.win.after(0, lambda: self._on_cancel_finish(res))

            threading.Thread(target=run_cancel, daemon=True).start()

        tk.Button(
            btn_box,
            text="❌ Confirmar Cancelación y Devolver",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            activebackground="#B91C1C",
            activeforeground=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=14,
            pady=7,
            command=ejecutar_cancelacion
        ).pack(side="left")

        tk.Button(
            btn_box,
            text="Volver",
            font=(FONT_FAMILY, 10),
            bg="#E2E8F0",
            fg=COLOR_TEXT,
            relief="flat",
            cursor="hand2",
            padx=14,
            pady=7,
            command=dialog.destroy
        ).pack(side="right")

    def _on_cancel_finish(self, res):
        if res.get("ok"):
            msg = res.get("mensaje", "Comanda cancelada exitosamente.")
            messagebox.showinfo("Cancelación y Devolución Exitosa", msg, parent=self.win)
            self._load_takeaway()
            if self.on_update:
                try:
                    self.on_update()
                except Exception:
                    pass
        else:
            messagebox.showerror("Error al Cancelar", res.get("error", "No se pudo cancelar el pedido"), parent=self.win)

    def _ver_factura(self):
        if not self.selected_pedido:
            return
        from tickets_window import TicketsWindow
        t_win = TicketsWindow(self.win)
