"""
Mostaza POS - Ventana Principal de Terminal
Punto de Venta Profesional para Mostaza con Facturación Fiscal y Mesas Interactivas.
"""
import tkinter as tk
from tkinter import ttk, messagebox, simpledialog
import time
import datetime
import threading
import random
import webbrowser
from config import *
from api_client import api

class POSWindow:
    def __init__(self, root, cajero_info, on_logout):
        self.root = root
        self.cajero_info = cajero_info
        self.on_logout = on_logout

        self.root.title(f"Mostaza POS · Terminal de Cajero ({self.cajero_info.get('nombre_completo', 'Cajero')})")
        self.root.geometry("1240x780")
        self.root.minsize(1080, 700)
        self.root.configure(bg=COLOR_LIGHT_BG)
        self.root.resizable(True, True)

        # Control de Pantalla Completa y Responsividad
        self.is_fullscreen = False
        self._current_cols = 3
        if globals().get("POS_INICIAR_PANTALLA_COMPLETA", False):
            try:
                self.root.attributes("-fullscreen", True)
                self.is_fullscreen = True
            except Exception:
                pass
        elif globals().get("POS_INICIAR_MAXIMIZADO", True):
            try:
                self.root.state("zoomed")
            except Exception:
                pass

        # Estado del POS
        self.catalogo = {}
        self.categorias = {}
        self.categoria_activa = "exclusivos"
        self.filtro_texto = ""
        self.comanda = []
        self.tipo_entrega = "takeaway"
        self.numero_mesa = 1
        self.mesa_info = {"numero": 1, "seccion": "Salón Principal", "capacidad": 4}
        self.cliente_club = None
        self.descuento = 0.0
        self.caja_info = {}
        self.mesas_datos = []
        self.secciones_datos = []

        self._build_layout()
        self._start_clock()
        self._load_data()
        self._setup_shortcuts()
        self._poll_takeaway_badge()

    def _build_layout(self):
        for w in self.root.winfo_children():
            w.destroy()

        # ============================================================
        # 1. HEADER SUPERIOR REORGANIZADO Y RESPONSIVO (ÉNFASIS EN BLANCO)
        # ============================================================
        header = tk.Frame(self.root, bg=COLOR_WHITE, height=60, padx=18, pady=10, relief="solid", bd=1)
        header.pack(fill="x", side="top")

        # Zona Izquierda: Brand
        brand_frame = tk.Frame(header, bg=COLOR_WHITE)
        brand_frame.pack(side="left", fill="y")

        lbl_brand = tk.Label(
            brand_frame,
            text="MOSTAZA POS",
            font=(FONT_FAMILY, 15, "bold"),
            fg=COLOR_ROJO,
            bg=COLOR_WHITE
        )
        lbl_brand.pack(side="left", padx=(0, 8))

        lbl_badge = tk.Label(
            brand_frame,
            text=f"CAJA {TERMINAL_NUMERO:02d} · {TERMINAL_NOMBRE.split(' - ')[-1].upper()}",
            font=(FONT_FAMILY, 8, "bold"),
            fg="#FFFFFF",
            bg=TERMINAL_BADGE_COLOR,
            padx=8,
            pady=3
        )
        lbl_badge.pack(side="left")

        # Zona Centro: Cajero, Turno, Caja Status y Reloj
        center_frame = tk.Frame(header, bg=COLOR_WHITE)
        center_frame.pack(side="left", fill="both", expand=True, padx=15)

        self.lbl_cajero = tk.Label(
            center_frame,
            text=f"👤 {self.cajero_info.get('nombre_completo', 'Operador')}",
            font=(FONT_FAMILY, 9, "bold"),
            fg=COLOR_TEXT,
            bg=COLOR_WHITE
        )
        self.lbl_cajero.pack(side="left", padx=8)

        turno_nombre = self.cajero_info.get('turno', 'Turno General')
        self.lbl_turno = tk.Label(
            center_frame,
            text=f"⏰ {turno_nombre}",
            font=(FONT_FAMILY, 9, "bold"),
            fg="#B45309",
            bg=COLOR_WHITE
        )
        self.lbl_turno.pack(side="left", padx=8)

        self.lbl_caja_status = tk.Label(
            center_frame,
            text="💰 Caja: Conectando...",
            font=(FONT_FAMILY, 9, "bold"),
            fg="#15803D",
            bg=COLOR_WHITE
        )
        self.lbl_caja_status.pack(side="left", padx=8)

        self.lbl_clock = tk.Label(
            center_frame,
            text="--:--:--",
            font=("Courier New", 12, "bold"),
            fg=COLOR_ROJO,
            bg="#F1F5F9",
            padx=10,
            pady=3
        )
        self.lbl_clock.pack(side="right", padx=10)

        # Zona Derecha: Botones de Acción
        actions_frame = tk.Frame(header, bg=COLOR_WHITE)
        actions_frame.pack(side="right", fill="y")

        self.btn_fullscreen = tk.Button(
            actions_frame,
            text="🗗 Salir (F11)" if self.is_fullscreen else "🗖 Pantalla Completa (F11)",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#FEF3C7" if self.is_fullscreen else "#F1F5F9",
            fg="#92400E" if self.is_fullscreen else COLOR_TEXT,
            activebackground="#E2E8F0",
            relief="solid",
            bd=1,
            cursor="hand2",
            padx=10,
            pady=4,
            command=self._toggle_fullscreen
        )
        self.btn_fullscreen.pack(side="left", padx=3)

        btn_arqueo = tk.Button(
            actions_frame,
            text="📊 Arqueo Caja",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#F1F5F9",
            fg=COLOR_TEXT,
            activebackground="#E2E8F0",
            relief="solid",
            bd=1,
            cursor="hand2",
            padx=10,
            pady=4,
            command=self._abrir_ventana_arqueo
        )
        btn_arqueo.pack(side="left", padx=3)

        self.btn_takeaway = tk.Button(
            actions_frame,
            text="🛍️ Take Away (F4)",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#F1F5F9",
            fg=COLOR_TEXT,
            activebackground="#E2E8F0",
            relief="solid",
            bd=1,
            cursor="hand2",
            padx=10,
            pady=4,
            command=self._abrir_ventana_takeaway
        )
        self.btn_takeaway.pack(side="left", padx=3)

        btn_tickets = tk.Button(
            actions_frame,
            text="🧾 Facturas / Tickets",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#F1F5F9",
            fg=COLOR_TEXT,
            activebackground="#E2E8F0",
            relief="solid",
            bd=1,
            cursor="hand2",
            padx=10,
            pady=4,
            command=self._abrir_ventana_tickets
        )
        btn_tickets.pack(side="left", padx=3)

        btn_logout = tk.Button(
            actions_frame,
            text="🚪 Cerrar Turno",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#FEE2E2",
            fg="#991B1B",
            activebackground="#FECACA",
            relief="solid",
            bd=1,
            cursor="hand2",
            padx=12,
            pady=4,
            command=self._cerrar_sesion
        )
        btn_logout.pack(side="left", padx=(3, 0))

        # ============================================================
        # 2. WORKSPACE EN 2 COLUMNAS (CATÁLOGO + COMANDA)
        # ============================================================
        workspace = tk.Frame(self.root, bg=COLOR_LIGHT_BG)
        workspace.pack(fill="both", expand=True)

        # Columna Izquierda: Catálogo
        self.catalog_col = tk.Frame(workspace, bg=COLOR_WHITE, relief="solid", bd=1)
        self.catalog_col.pack(side="left", fill="both", expand=True, padx=(12, 6), pady=10)

        self._build_catalog_panel()

        # Columna Derecha: Ticket y Comanda
        self.ticket_col = tk.Frame(workspace, bg=COLOR_WHITE, width=440, relief="solid", bd=1)
        self.ticket_col.pack(side="right", fill="y", padx=(6, 12), pady=10)
        self.ticket_col.pack_propagate(False)

        self._build_ticket_panel()

    # ------------------------------------------------------------
    # PANEL DE CATÁLOGO (IZQUIERDA)
    # ------------------------------------------------------------
    def _build_catalog_panel(self):
        # Barra Superior de Búsqueda
        search_frame = tk.Frame(self.catalog_col, bg=COLOR_WHITE, padx=12, pady=10)
        search_frame.pack(fill="x")

        lbl_s = tk.Label(search_frame, text="🔍 Buscar (F2):", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_WHITE)
        lbl_s.pack(side="left", padx=(0, 8))

        self.entry_search = tk.Entry(search_frame, font=(FONT_FAMILY, 11), bg=COLOR_LIGHT_BG, relief="solid", bd=1)
        self.entry_search.pack(side="left", fill="x", expand=True, ipady=4)
        self.entry_search.bind("<KeyRelease>", lambda e: self._filtrar_productos(self.entry_search.get()))

        btn_clear_s = tk.Button(search_frame, text="✕ Limpiar", font=(FONT_FAMILY, 8, "bold"), bg="#E2E8F0", relief="flat", command=self._limpiar_busqueda, padx=8)
        btn_clear_s.pack(side="left", padx=(6, 0))

        # Pestañas de Categorías
        self.cat_frame = tk.Frame(self.catalog_col, bg="#F1F5F9", padx=8, pady=6)
        self.cat_frame.pack(fill="x")

        # Scrollable Canvas para Productos
        prod_container = tk.Frame(self.catalog_col, bg=COLOR_WHITE)
        prod_container.pack(fill="both", expand=True, padx=8, pady=8)

        self.canvas_prod = tk.Canvas(prod_container, bg=COLOR_WHITE, highlightthickness=0)
        self.scroll_prod = ttk.Scrollbar(prod_container, orient="vertical", command=self.canvas_prod.yview)
        self.grid_prod = tk.Frame(self.canvas_prod, bg=COLOR_WHITE)

        self.grid_prod.bind(
            "<Configure>",
            lambda e: self.canvas_prod.configure(scrollregion=self.canvas_prod.bbox("all"))
        )
        self.canvas_window_id = self.canvas_prod.create_window((0, 0), window=self.grid_prod, anchor="nw")
        self.canvas_prod.configure(yscrollcommand=self.scroll_prod.set)

        def _on_canvas_configure(e):
            self.canvas_prod.itemconfig(self.canvas_window_id, width=e.width)
            w = e.width
            cols = 5 if w >= 1150 else (4 if w >= 860 else (3 if w >= 560 else 2))
            if getattr(self, '_current_cols', None) != cols:
                self._current_cols = cols
                if getattr(self, 'catalogo', None):
                    self._render_productos()

        self.canvas_prod.bind("<Configure>", _on_canvas_configure)

        self.canvas_prod.pack(side="left", fill="both", expand=True)
        self.scroll_prod.pack(side="right", fill="y")

        self.canvas_prod.bind_all("<MouseWheel>", lambda e: self.canvas_prod.yview_scroll(int(-1*(e.delta/120)), "units"))

    # ------------------------------------------------------------
    # PANEL DE TICKET / COMANDA (DERECHA)
    # ------------------------------------------------------------
    def _build_ticket_panel(self):
        header_t = tk.Frame(self.ticket_col, bg="#F8FAFC", padx=12, pady=10, relief="solid", bd=1)
        header_t.pack(fill="x")

        lbl_m = tk.Label(header_t, text="MODALIDAD DE ENTREGA", font=(FONT_FAMILY, 9, "bold"), fg=COLOR_TEXT_MUTED, bg="#F8FAFC")
        lbl_m.pack(anchor="w", pady=(0, 6))

        mode_btns = tk.Frame(header_t, bg="#F8FAFC")
        mode_btns.pack(fill="x")

        self.btn_mode_take = tk.Button(
            mode_btns, text="🛍 Mostrador (Take Away)", font=(FONT_FAMILY, 9, "bold"),
            bg=COLOR_DARK, fg=COLOR_WHITE, relief="flat", cursor="hand2",
            pady=6, command=lambda: self._cambiar_modalidad("takeaway")
        )
        self.btn_mode_take.pack(side="left", fill="x", expand=True, padx=2)

        self.btn_mode_salon = tk.Button(
            mode_btns, text="🪑 En el Salón (Mesa)", font=(FONT_FAMILY, 9, "bold"),
            bg="#E2E8F0", fg=COLOR_TEXT, relief="flat", cursor="hand2",
            pady=6, command=lambda: self._cambiar_modalidad("salon")
        )
        self.btn_mode_salon.pack(side="left", fill="x", expand=True, padx=2)

        # Selector Interactivo de Mesa
        self.frame_mesa = tk.Frame(header_t, bg="#F8FAFC")

        self.btn_selector_mesa = tk.Button(
            self.frame_mesa,
            text="🗺️ Mesa 1 · Salón Principal (4p) [Cambiar]",
            font=(FONT_FAMILY, 9, "bold"),
            bg="#FEF3C7",
            fg="#92400E",
            relief="solid",
            bd=1,
            cursor="hand2",
            pady=5,
            command=self._abrir_modal_mesas
        )
        self.btn_selector_mesa.pack(fill="x", pady=(6, 0))

        # Búsqueda de Cliente Club Mostaza
        club_frame = tk.Frame(header_t, bg="#F8FAFC")
        club_frame.pack(fill="x", pady=(10, 0))

        self.entry_cliente = tk.Entry(club_frame, font=(FONT_FAMILY, 9), bg=COLOR_WHITE, relief="solid", bd=1)
        self.entry_cliente.insert(0, "DNI o Email Cliente Club...")
        self.entry_cliente.pack(side="left", fill="x", expand=True, ipady=4, padx=(0, 4))
        self.entry_cliente.bind("<FocusIn>", lambda e: self.entry_cliente.delete(0, tk.END) if "..." in self.entry_cliente.get() else None)
        self.entry_cliente.bind("<Return>", lambda e: self._buscar_cliente_club())

        btn_club = tk.Button(
            club_frame, text="⭐ Club", font=(FONT_FAMILY, 8, "bold"),
            bg=COLOR_BLUE, fg=COLOR_WHITE, relief="flat", cursor="hand2",
            padx=8, command=self._buscar_cliente_club
        )
        btn_club.pack(side="left")

        self.lbl_cliente_info = tk.Label(header_t, text="", font=(FONT_FAMILY, 8, "bold"), fg="#166534", bg="#DCFCE7", pady=2)

        # Contenedor Scrollable de Ítems
        items_frame = tk.Frame(self.ticket_col, bg=COLOR_WHITE)
        items_frame.pack(fill="both", expand=True, padx=6, pady=6)

        self.canvas_items = tk.Canvas(items_frame, bg=COLOR_WHITE, highlightthickness=0)
        self.scroll_items = ttk.Scrollbar(items_frame, orient="vertical", command=self.canvas_items.yview)
        self.list_items = tk.Frame(self.canvas_items, bg=COLOR_WHITE)

        self.list_items.bind(
            "<Configure>",
            lambda e: self.canvas_items.configure(scrollregion=self.canvas_items.bbox("all"))
        )
        self.items_window_id = self.canvas_items.create_window((0, 0), window=self.list_items, anchor="nw")
        self.canvas_items.configure(yscrollcommand=self.scroll_items.set)

        self.canvas_items.bind(
            "<Configure>",
            lambda e: self.canvas_items.itemconfig(self.items_window_id, width=e.width)
        )

        self.canvas_items.pack(side="left", fill="both", expand=True)
        self.scroll_items.pack(side="right", fill="y")

        # Resumen Financiero y Botones
        totals_frame = tk.Frame(self.ticket_col, bg="#F8FAFC", padx=14, pady=10, relief="solid", bd=1)
        totals_frame.pack(fill="x", side="bottom")

        # Subtotal
        f_sub = tk.Frame(totals_frame, bg="#F8FAFC")
        f_sub.pack(fill="x", pady=1)
        tk.Label(f_sub, text="Subtotal:", font=(FONT_FAMILY, 9), bg="#F8FAFC", fg=COLOR_TEXT_MUTED).pack(side="left")
        self.lbl_subtotal = tk.Label(f_sub, text="$0", font=(FONT_FAMILY, 9, "bold"), bg="#F8FAFC")
        self.lbl_subtotal.pack(side="right")

        # Descuento
        self.f_desc = tk.Frame(totals_frame, bg="#F8FAFC")
        tk.Label(self.f_desc, text="Descuento:", font=(FONT_FAMILY, 9), bg="#F8FAFC", fg="#166534").pack(side="left")
        self.lbl_descuento = tk.Label(self.f_desc, text="$0", font=(FONT_FAMILY, 9, "bold"), bg="#F8FAFC", fg="#166534")
        self.lbl_descuento.pack(side="right")

        # Total Grande
        sep_tot = tk.Frame(totals_frame, height=1, bg=COLOR_BORDER)
        sep_tot.pack(fill="x", pady=4)

        f_tot = tk.Frame(totals_frame, bg="#F8FAFC")
        f_tot.pack(fill="x", pady=2)
        tk.Label(f_tot, text="TOTAL:", font=(FONT_FAMILY, 11, "bold"), bg="#F8FAFC", fg=COLOR_DARK).pack(side="left")
        self.lbl_total = tk.Label(f_tot, text="$0", font=(FONT_FAMILY, 17, "bold"), bg="#F8FAFC", fg=COLOR_ROJO)
        self.lbl_total.pack(side="right")

        # Botones Finales
        btns_action = tk.Frame(totals_frame, bg="#F8FAFC")
        btns_action.pack(fill="x", pady=(8, 0))

        btn_vaciar = tk.Button(
            btns_action, text="🗑 Vaciar (F8)", font=(FONT_FAMILY, 9, "bold"),
            bg="#EF4444", fg=COLOR_WHITE, activebackground="#DC2626",
            relief="flat", cursor="hand2", padx=8, pady=8,
            command=self._vaciar_comanda
        )
        btn_vaciar.pack(side="left", padx=(0, 6))

        btn_cobrar = tk.Button(
            btns_action, text="💳 COBRAR Y FACTURAR (F10)", font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO, fg=COLOR_WHITE, activebackground=COLOR_ROJO_OSCURO,
            relief="flat", cursor="hand2", pady=8,
            command=self._abrir_modal_cobro
        )
        btn_cobrar.pack(side="left", fill="x", expand=True)

    # ------------------------------------------------------------
    # CARGA DE DATOS DESDE LA API
    # ------------------------------------------------------------
    def _load_data(self):
        def run_fetch():
            res_cat = api.obtener_catalogo()
            res_caja = api.obtener_estado_caja()
            res_mesas = api.obtener_mesas()
            self.root.after(0, lambda: self._on_data_loaded(res_cat, res_caja, res_mesas))

        threading.Thread(target=run_fetch, daemon=True).start()

    def _on_data_loaded(self, res_cat, res_caja, res_mesas):
        if res_cat.get("ok"):
            self.categorias = res_cat.get("categorias", {})
            self.catalogo = res_cat.get("productos_por_categoria", {})
            self._render_categorias()
            self._render_productos()

        if res_caja.get("ok") and res_caja.get("abierta"):
            c = res_caja.get("caja", {})
            self.caja_info = c
            self.lbl_caja_status.config(
                text=f"💰 Ventas Turno: {c.get('total_ventas_formateado', '$0')}"
            )
        else:
            self.lbl_caja_status.config(text="⚠️ Caja Cerrada")

        if res_mesas.get("ok"):
            self.mesas_datos = res_mesas.get("mesas", [])
            self.secciones_datos = res_mesas.get("secciones", [])

    def _render_categorias(self):
        for w in self.cat_frame.winfo_children():
            w.destroy()

        for slug, cat in self.categorias.items():
            is_active = (slug == self.categoria_activa)
            btn = tk.Button(
                self.cat_frame,
                text=cat.get("nombre", slug.title()),
                font=(FONT_FAMILY, 9, "bold" if is_active else "normal"),
                bg=COLOR_ROJO if is_active else "#E2E8F0",
                fg=COLOR_WHITE if is_active else COLOR_TEXT,
                activebackground=COLOR_ROJO_OSCURO,
                relief="flat",
                cursor="hand2",
                padx=10,
                pady=4,
                command=lambda s=slug: self._seleccionar_categoria(s)
            )
            btn.pack(side="left", padx=2)

    def _seleccionar_categoria(self, slug):
        self.categoria_activa = slug
        self._render_categorias()
        self._render_productos()

    def _render_productos(self):
        for w in self.grid_prod.winfo_children():
            w.destroy()

        if self.filtro_texto:
            q = self.filtro_texto.lower()
            prods = []
            for plist in self.catalogo.values():
                for p in plist:
                    if q in p["nombre"].lower() or (p.get("descripcion") and q in p["descripcion"].lower()):
                        prods.append(p)
        else:
            prods = self.catalogo.get(self.categoria_activa, [])

        if not prods:
            lbl_none = tk.Label(self.grid_prod, text="No se encontraron productos.", font=(FONT_FAMILY, 11), fg=COLOR_TEXT_MUTED, bg=COLOR_WHITE)
            lbl_none.pack(pady=40)
            return

        w = self.canvas_prod.winfo_width()
        if w > 100:
            cols = 5 if w >= 1150 else (4 if w >= 860 else (3 if w >= 560 else 2))
            self._current_cols = cols
        else:
            cols = getattr(self, '_current_cols', 3)

        for idx, prod in enumerate(prods):
            r = idx // cols
            c = idx % cols

            card = tk.Frame(self.grid_prod, bg="#FFFFFF", relief="solid", bd=1, padx=8, pady=8, cursor="hand2")
            card.grid(row=r, column=c, padx=5, pady=5, sticky="nsew")

            lbl_pname = tk.Label(
                card,
                text=prod["nombre"],
                font=(FONT_FAMILY, 9, "bold"),
                fg=COLOR_TEXT,
                bg="#FFFFFF",
                wraplength=190,
                justify="center"
            )
            lbl_pname.pack(fill="x", pady=(0, 4))

            lbl_price = tk.Label(
                card,
                text=f"${int(prod['precio']):,}".replace(",", "."),
                font=(FONT_FAMILY, 11, "bold"),
                fg=COLOR_ROJO,
                bg="#FFFFFF"
            )
            lbl_price.pack()

            for widget in [card, lbl_pname, lbl_price]:
                widget.bind("<Button-1>", lambda e, p=prod: self._agregar_a_comanda(p))

        for c_idx in range(6):
            if c_idx < cols:
                self.grid_prod.columnconfigure(c_idx, weight=1)
            else:
                self.grid_prod.columnconfigure(c_idx, weight=0)

    def _filtrar_productos(self, texto):
        self.filtro_texto = texto.strip()
        self._render_productos()

    def _limpiar_busqueda(self):
        self.entry_search.delete(0, tk.END)
        self.filtro_texto = ""
        self._render_productos()

    # ------------------------------------------------------------
    # GESTIÓN DE LA COMANDA
    # ------------------------------------------------------------
    def _agregar_a_comanda(self, prod):
        existente = next((it for it in self.comanda if it["idproducto"] == prod["idproducto"] and not it.get("modificadores")), None)
        if existente:
            existente["cantidad"] += 1
        else:
            self.comanda.append({
                "idproducto": prod["idproducto"],
                "nombre": prod["nombre"],
                "precio": float(prod["precio"]),
                "cantidad": 1,
                "modificadores": ""
            })
        self._render_comanda()

    def _modificar_cantidad(self, index, delta):
        self.comanda[index]["cantidad"] += delta
        if self.comanda[index]["cantidad"] <= 0:
            self.comanda.pop(index)
        self._render_comanda()

    def _eliminar_item(self, index):
        self.comanda.pop(index)
        self._render_comanda()

    def _editar_modificador(self, index):
        actual = self.comanda[index].get("modificadores", "")
        nuevo = simpledialog.askstring(
            "Modificador / Nota de Cocina",
            f"Aclaración para {self.comanda[index]['nombre']}:\n(ej: Sin cebolla, extra aderezo, sin sal)",
            initialvalue=actual,
            parent=self.root
        )
        if nuevo is not None:
            self.comanda[index]["modificadores"] = nuevo.strip()
            self._render_comanda()

    def _vaciar_comanda(self):
        if not self.comanda:
            return
        if messagebox.askyesno("Vaciar Comanda", "¿Deseas eliminar todos los productos de la comanda actual?"):
            self.comanda.clear()
            self.cliente_club = None
            self.descuento = 0.0
            self.lbl_cliente_info.pack_forget()
            self._render_comanda()

    def _render_comanda(self):
        for w in self.list_items.winfo_children():
            w.destroy()

        if not self.comanda:
            lbl_empty = tk.Label(
                self.list_items,
                text="🛒 Comanda Vacía\nHacé clic en los productos del menú para agregar.",
                font=(FONT_FAMILY, 9),
                fg=COLOR_TEXT_MUTED,
                bg=COLOR_WHITE,
                justify="center"
            )
            lbl_empty.pack(pady=40)
            self.lbl_subtotal.config(text="$0")
            self.lbl_total.config(text="$0")
            self.f_desc.pack_forget()
            return

        subtotal = 0.0

        for idx, it in enumerate(self.comanda):
            item_tot = it["precio"] * it["cantidad"]
            subtotal += item_tot

            row = tk.Frame(self.list_items, bg=COLOR_WHITE, pady=4, relief="solid", bd=0)
            row.pack(fill="x", pady=2, padx=4)

            info_f = tk.Frame(row, bg=COLOR_WHITE)
            info_f.pack(side="left", fill="x", expand=True)

            lbl_title = tk.Label(info_f, text=it["nombre"], font=(FONT_FAMILY, 9, "bold"), fg=COLOR_TEXT, bg=COLOR_WHITE, anchor="w", wraplength=170, justify="left")
            lbl_title.pack(fill="x")

            if it.get("modificadores"):
                lbl_mod = tk.Label(info_f, text=f"↳ {it['modificadores']}", font=(FONT_FAMILY, 8), fg=COLOR_ROJO, bg=COLOR_WHITE, anchor="w", wraplength=170)
                lbl_mod.pack(fill="x")

            btn_nota = tk.Button(info_f, text="+ Nota cocina", font=(FONT_FAMILY, 7, "bold"), fg=COLOR_BLUE, bg=COLOR_WHITE, relief="flat", cursor="hand2", command=lambda i=idx: self._editar_modificador(i))
            btn_nota.pack(anchor="w")

            qty_f = tk.Frame(row, bg=COLOR_WHITE)
            qty_f.pack(side="left", padx=4)

            btn_minus = tk.Button(qty_f, text="-", font=(FONT_FAMILY, 8, "bold"), bg="#F1F5F9", width=2, relief="flat", command=lambda i=idx: self._modificar_cantidad(i, -1))
            btn_minus.pack(side="left")

            lbl_q = tk.Label(qty_f, text=str(it["cantidad"]), font=(FONT_FAMILY, 9, "bold"), width=3, bg=COLOR_WHITE)
            lbl_q.pack(side="left")

            btn_plus = tk.Button(qty_f, text="+", font=(FONT_FAMILY, 8, "bold"), bg="#F1F5F9", width=2, relief="flat", command=lambda i=idx: self._modificar_cantidad(i, 1))
            btn_plus.pack(side="left")

            lbl_sum = tk.Label(row, text=f"${int(item_tot):,}".replace(",", "."), font=(FONT_FAMILY, 9, "bold"), width=8, anchor="e", bg=COLOR_WHITE)
            lbl_sum.pack(side="left", padx=2)

            btn_del = tk.Button(row, text="✕", font=(FONT_FAMILY, 8, "bold"), fg="#EF4444", bg=COLOR_WHITE, relief="flat", cursor="hand2", command=lambda i=idx: self._eliminar_item(i))
            btn_del.pack(side="left", padx=(2, 0))

        total = max(0.0, subtotal - self.descuento)
        self.lbl_subtotal.config(text=f"${int(subtotal):,}".replace(",", "."))
        if self.descuento > 0:
            self.lbl_descuento.config(text=f"-${int(self.descuento):,}".replace(",", "."))
            self.f_desc.pack(fill="x", pady=1, after=self.lbl_subtotal.master)
        else:
            self.f_desc.pack_forget()

        self.lbl_total.config(text=f"${int(total):,}".replace(",", "."))

    # ------------------------------------------------------------
    # MODALIDAD Y SELECTOR INTERACTIVO DE MESAS
    # ------------------------------------------------------------
    def _cambiar_modalidad(self, modo):
        self.tipo_entrega = modo
        for btn in [self.btn_mode_take, self.btn_mode_salon]:
            btn.config(bg="#E2E8F0", fg=COLOR_TEXT)

        self.frame_mesa.pack_forget()

        if modo == "takeaway":
            self.btn_mode_take.config(bg=COLOR_DARK, fg=COLOR_WHITE)
        elif modo == "salon":
            self.btn_mode_salon.config(bg=COLOR_DARK, fg=COLOR_WHITE)
            self.frame_mesa.pack(fill="x", pady=(6, 0))
            self._actualizar_texto_mesa_boton()

    def _actualizar_texto_mesa_boton(self):
        self.btn_selector_mesa.config(
            text=f"🗺️ Mesa {self.mesa_info['numero']} · {self.mesa_info['seccion']} ({self.mesa_info['capacidad']}p) [Cambiar]"
        )

    def _abrir_modal_mesas(self):
        m_win = tk.Toplevel(self.root)
        m_win.title("Mapa Interactivo de Mesas por Secciones · Mostaza POS")
        m_win.geometry("720x580")
        m_win.configure(bg=COLOR_WHITE)
        m_win.grab_set()

        h_f = tk.Frame(m_win, bg=COLOR_WHITE, padx=15, pady=12, relief="solid", bd=1)
        h_f.pack(fill="x")

        tk.Label(h_f, text="🗺️ SELECCIÓN INTERACTIVA DE MESAS EN TIEMPO REAL", font=(FONT_FAMILY, 11, "bold"), fg=COLOR_ROJO, bg=COLOR_WHITE).pack(side="left")

        leg_f = tk.Frame(h_f, bg=COLOR_WHITE)
        leg_f.pack(side="right")
        tk.Label(leg_f, text="● Disponible", font=(FONT_FAMILY, 8, "bold"), fg="#16A34A", bg=COLOR_WHITE).pack(side="left", padx=4)
        tk.Label(leg_f, text="● Ocupada", font=(FONT_FAMILY, 8, "bold"), fg="#DC2626", bg=COLOR_WHITE).pack(side="left", padx=4)

        tabs_f = tk.Frame(m_win, bg="#F1F5F9", padx=10, pady=8)
        tabs_f.pack(fill="x")

        seccion_activa_var = tk.StringVar(value="todas")
        grid_mesas_frame = tk.Frame(m_win, bg=COLOR_WHITE, padx=15, pady=12)
        grid_mesas_frame.pack(fill="both", expand=True)

        def render_mesas_modal():
            for w in grid_mesas_frame.winfo_children():
                w.destroy()

            sec_filtro = seccion_activa_var.get()
            mesas_a_mostrar = self.mesas_datos
            if sec_filtro != "todas":
                mesas_a_mostrar = [m for m in self.mesas_datos if str(m.get("idseccion")) == sec_filtro]

            cols = 4
            for idx, m in enumerate(mesas_a_mostrar):
                r = idx // cols
                c = idx % cols

                num = m.get("numero", idx+1)
                ocupada = m.get("ocupada", False)
                sec_nom = m.get("seccion_nombre", "Salón")
                cap = m.get("capacidad", 4)
                is_current = (num == self.numero_mesa)

                bg_card = "#FFFBEB" if is_current else ("#FEF2F2" if ocupada else "#F0FDF4")

                card = tk.Frame(grid_mesas_frame, bg=bg_card, relief="solid", bd=2 if is_current else 1, padx=8, pady=8, cursor="hand2" if not ocupada else "arrow")
                card.grid(row=r, column=c, padx=6, pady=6, sticky="nsew")

                tk.Label(card, text=f"🪑 Mesa {num}", font=(FONT_FAMILY, 10, "bold"), fg=COLOR_DARK, bg=bg_card).pack()
                tk.Label(card, text=f"{sec_nom} · {cap}p", font=(FONT_FAMILY, 8), fg=COLOR_TEXT_MUTED, bg=bg_card).pack()

                if ocupada:
                    tot_txt = m.get("info", {}).get("total_formateado", "Comanda activa")
                    tk.Label(card, text=f"OCUPADA ({tot_txt})", font=(FONT_FAMILY, 7, "bold"), fg="#DC2626", bg=bg_card).pack(pady=(4, 0))
                else:
                    estado_lbl = "✓ SELECCIONADA" if is_current else "DISPONIBLE"
                    tk.Label(card, text=estado_lbl, font=(FONT_FAMILY, 8, "bold"), fg="#B45309" if is_current else "#166534", bg=bg_card).pack(pady=(4, 0))

                if not ocupada:
                    def make_click(n=num, sn=sec_nom, cp=cap):
                        return lambda e: select_and_close(n, sn, cp)
                    for widget in [card] + card.winfo_children():
                        widget.bind("<Button-1>", make_click(num, sec_nom, cap))

            for col in range(cols):
                grid_mesas_frame.columnconfigure(col, weight=1)

        def select_and_close(n, sn, cp):
            self.numero_mesa = n
            self.mesa_info = {"numero": n, "seccion": sn, "capacidad": cp}
            self._actualizar_texto_mesa_boton()
            m_win.destroy()

        def set_tab_seccion(val):
            seccion_activa_var.set(str(val))
            render_mesas_modal()

        btn_all = tk.Button(tabs_f, text="Todas las Secciones", font=(FONT_FAMILY, 9, "bold"), bg="#E2E8F0", relief="flat", command=lambda: set_tab_seccion("todas"), padx=10, pady=3)
        btn_all.pack(side="left", padx=2)

        for s in self.secciones_datos:
            b = tk.Button(tabs_f, text=s.get("nombre"), font=(FONT_FAMILY, 9, "bold"), bg="#E2E8F0", relief="flat", command=lambda sid=s.get("idseccion"): set_tab_seccion(sid), padx=10, pady=3)
            b.pack(side="left", padx=2)

        def reload_live():
            res_m = api.obtener_mesas()
            if res_m.get("ok"):
                self.mesas_datos = res_m.get("mesas", [])
                render_mesas_modal()

        btn_refresh = tk.Button(tabs_f, text="🔄 Actualizar", font=(FONT_FAMILY, 8, "bold"), bg="#334155", fg=COLOR_WHITE, relief="flat", command=reload_live, padx=8)
        btn_refresh.pack(side="right")

        render_mesas_modal()

    def _buscar_cliente_club(self):
        q = self.entry_cliente.get().strip()
        if not q or "..." in q:
            return

        res = api.buscar_cliente(q)
        if res.get("ok") and res.get("clientes"):
            c = res["clientes"][0]
            self.cliente_club = c
            self.lbl_cliente_info.config(
                text=f"⭐ {c['nombre_completo']} · Puntos: {c['puntos']} pts"
            )
            self.lbl_cliente_info.pack(fill="x", pady=(4, 0))
        else:
            messagebox.showinfo("Club Mostaza", "Cliente no encontrado. Se creará la venta como Consumidor Final.")
            self.cliente_club = None
            self.lbl_cliente_info.pack_forget()

    # ------------------------------------------------------------
    # MODAL DE COBRO Y FACTURACIÓN FISCAL
    # ------------------------------------------------------------
    def _abrir_modal_cobro(self):
        if not self.comanda:
            messagebox.showwarning("Atención", "Agregá productos a la comanda antes de cobrar.")
            return

        subtotal = sum(it["precio"] * it["cantidad"] for it in self.comanda)
        total = max(0.0, subtotal - self.descuento)

        modal = tk.Toplevel(self.root)
        modal.title("Facturar y Cobrar Comanda · Mostaza POS")
        modal.geometry("640x780")
        modal.resizable(False, False)
        modal.configure(bg=COLOR_WHITE)
        modal.grab_set()

        modal.update_idletasks()
        x = max(10, self.root.winfo_x() + (self.root.winfo_width() // 2) - 320)
        y = max(10, self.root.winfo_y() + (self.root.winfo_height() // 2) - 390)
        modal.geometry(f"+{x}+{y}")

        tot_frame = tk.Frame(modal, bg=COLOR_LIGHT_BG, padx=20, pady=12, relief="solid", bd=1)
        tot_frame.pack(fill="x", padx=20, pady=12)

        tk.Label(tot_frame, text="TOTAL A FACTURAR Y COBRAR:", font=(FONT_FAMILY, 10, "bold"), fg=COLOR_TEXT_MUTED, bg=COLOR_LIGHT_BG).pack()
        lbl_m_tot = tk.Label(tot_frame, text=f"${int(total):,}".replace(",", "."), font=(FONT_FAMILY, 24, "bold"), fg=COLOR_ROJO, bg=COLOR_LIGHT_BG)
        lbl_m_tot.pack()

        pay_tabs = tk.Frame(modal, bg=COLOR_WHITE)
        pay_tabs.pack(fill="x", padx=20, pady=(0, 10))

        metodo_var = tk.StringVar(value="efectivo")

        def set_metodo(m):
            metodo_var.set(m)
            f_efectivo.pack_forget()
            f_tarjeta.pack_forget()
            f_mp.pack_forget()
            f_transf.pack_forget()
            if m == "efectivo": f_efectivo.pack(fill="both", expand=True, padx=20)
            elif m == "tarjeta": f_tarjeta.pack(fill="both", expand=True, padx=20)
            elif m == "mercadopago": f_mp.pack(fill="both", expand=True, padx=20)
            elif m == "transferencia": f_transf.pack(fill="both", expand=True, padx=20)

        for m_slug, m_name in [("efectivo", "💵 Efectivo"), ("tarjeta", "💳 Tarjeta POS"), ("mercadopago", "📱 Mercado Pago"), ("transferencia", "🏦 Transferencia")]:
            rb = tk.Radiobutton(
                pay_tabs, text=m_name, variable=metodo_var, value=m_slug,
                font=(FONT_FAMILY, 9, "bold"), indicatoron=0, bg="#F1F5F9",
                selectcolor=COLOR_DARK, fg=COLOR_TEXT, relief="flat",
                command=lambda s=m_slug: set_metodo(s)
            )
            rb.pack(side="left", fill="x", expand=True, padx=2, ipady=6)

        # 1. Contenedor Efectivo
        f_efectivo = tk.Frame(modal, bg=COLOR_WHITE)
        f_efectivo.pack(fill="both", expand=True, padx=20)

        tk.Label(f_efectivo, text="¿Con cuánto abona el cliente? ($):", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_WHITE).pack(anchor="w", pady=(4, 4))
        
        entry_paga_con = tk.Entry(f_efectivo, font=("Courier New", 18, "bold"), justify="center", bg=COLOR_LIGHT_BG, relief="solid", bd=2)
        entry_paga_con.insert(0, str(int(total)))
        entry_paga_con.pack(fill="x", ipady=4, pady=(0, 8))

        lbl_vuelto = tk.Label(f_efectivo, text="VUELTO A ENTREGAR: $0", font=(FONT_FAMILY, 12, "bold"), fg="#166534", bg="#DCFCE7", pady=6)
        lbl_vuelto.pack(fill="x", pady=(0, 8))

        def calc_vuelto():
            try:
                paga = float(entry_paga_con.get().replace(".", "").replace(",", "."))
                vuelto = max(0.0, paga - total)
                lbl_vuelto.config(text=f"VUELTO A ENTREGAR: ${int(vuelto):,}".replace(",", "."))
            except ValueError:
                lbl_vuelto.config(text="VUELTO A ENTREGAR: $0")

        entry_paga_con.bind("<KeyRelease>", lambda e: calc_vuelto())

        # Billetes Rápidos Grid
        bills_frame = tk.Frame(f_efectivo, bg=COLOR_WHITE)
        bills_frame.pack(fill="x")

        def set_bill(val):
            if val == "exacto": entry_paga_con.delete(0, tk.END); entry_paga_con.insert(0, str(int(total)))
            else: entry_paga_con.delete(0, tk.END); entry_paga_con.insert(0, str(val))
            calc_vuelto()

        bills = [("Exacto", "exacto"), ("$2.000", 2000), ("$5.000", 5000), ("$10.000", 10000), ("$20.000", 20000), ("$50.000", 50000)]
        for idx, (lbl, val) in enumerate(bills):
            r = idx // 3
            c = idx % 3
            b = tk.Button(bills_frame, text=lbl, font=(FONT_FAMILY, 9, "bold"), bg="#F1F5F9", relief="flat", command=lambda v=val: set_bill(v))
            b.grid(row=r, column=c, padx=3, pady=3, sticky="nsew", ipady=5)
        for c in range(3): bills_frame.columnconfigure(c, weight=1)

        # 2. Contenedor Tarjeta (Mismos datos requeridos que en la web)
        f_tarjeta = tk.Frame(modal, bg=COLOR_WHITE)
        tk.Label(f_tarjeta, text="💳 Pago con Tarjeta de Débito / Crédito", font=(FONT_FAMILY, 11, "bold"), fg=COLOR_BLUE, bg=COLOR_WHITE).pack(pady=(4, 2))

        # Botones Rápidos de Prueba (igual a la web)
        f_test_cards = tk.Frame(f_tarjeta, bg=COLOR_WHITE)
        f_test_cards.pack(fill="x", pady=(2, 6))

        def autocompletar_tarjeta_prueba(tipo):
            entry_card_num.delete(0, tk.END)
            entry_card_name.delete(0, tk.END)
            entry_card_dni.delete(0, tk.END)
            entry_card_exp.delete(0, tk.END)
            entry_card_cvv.delete(0, tk.END)
            entry_card_auth.delete(0, tk.END)
            if tipo == "visa":
                entry_card_num.insert(0, "4500 1234 5678 9010")
                entry_card_name.insert(0, "JUAN PEREZ")
                entry_card_dni.insert(0, "38123456")
                entry_card_exp.insert(0, "12/28")
                entry_card_cvv.insert(0, "456")
                auth_code = f"AUTH-VISA-{random.randint(100000, 999999)}"
                entry_card_auth.insert(0, auth_code)
            else:
                entry_card_num.insert(0, "5400 9876 5432 1098")
                entry_card_name.insert(0, "MARIA GOMEZ")
                entry_card_dni.insert(0, "38123456")
                entry_card_exp.insert(0, "08/29")
                entry_card_cvv.insert(0, "789")
                auth_code = f"AUTH-MC-{random.randint(100000, 999999)}"
                entry_card_auth.insert(0, auth_code)

        btn_visa = tk.Button(
            f_test_cards, text="💳 Visa de Prueba", font=(FONT_FAMILY, 8, "bold"),
            bg="#E0F2FE", fg="#0369A1", relief="flat", cursor="hand2",
            command=lambda: autocompletar_tarjeta_prueba("visa")
        )
        btn_visa.pack(side="left", fill="x", expand=True, padx=2)

        btn_mc = tk.Button(
            f_test_cards, text="💳 Mastercard de Prueba", font=(FONT_FAMILY, 8, "bold"),
            bg="#FEF3C7", fg="#92400E", relief="flat", cursor="hand2",
            command=lambda: autocompletar_tarjeta_prueba("mastercard")
        )
        btn_mc.pack(side="left", fill="x", expand=True, padx=2)

        f_tarj_inputs = tk.Frame(f_tarjeta, bg=COLOR_WHITE)
        f_tarj_inputs.pack(fill="x", pady=2)

        # Número de Tarjeta
        tk.Label(f_tarj_inputs, text="Número de Tarjeta *:", font=(FONT_FAMILY, 8, "bold"), bg=COLOR_WHITE).grid(row=0, column=0, sticky="w", pady=2)
        entry_card_num = tk.Entry(f_tarj_inputs, font=("Courier New", 10, "bold"), relief="solid", bd=1)
        entry_card_num.insert(0, "4500 1234 5678 9010")
        entry_card_num.grid(row=0, column=1, columnspan=3, sticky="ew", padx=(6, 0), pady=2)

        # Nombre y Apellido
        nombre_def = self.cliente_club.get("nombre_completo", "JUAN PEREZ") if self.cliente_club else "JUAN PEREZ"
        tk.Label(f_tarj_inputs, text="Nombre y Apellido *:", font=(FONT_FAMILY, 8, "bold"), bg=COLOR_WHITE).grid(row=1, column=0, sticky="w", pady=2)
        entry_card_name = tk.Entry(f_tarj_inputs, font=(FONT_FAMILY, 9), relief="solid", bd=1)
        entry_card_name.insert(0, nombre_def)
        entry_card_name.grid(row=1, column=1, columnspan=3, sticky="ew", padx=(6, 0), pady=2)

        # DNI
        dni_def = self.cliente_club.get("dni", "38123456") if self.cliente_club else "38123456"
        tk.Label(f_tarj_inputs, text="DNI del Titular *:", font=(FONT_FAMILY, 8, "bold"), bg=COLOR_WHITE).grid(row=2, column=0, sticky="w", pady=2)
        entry_card_dni = tk.Entry(f_tarj_inputs, font=("Courier New", 9), relief="solid", bd=1)
        entry_card_dni.insert(0, dni_def)
        entry_card_dni.grid(row=2, column=1, columnspan=3, sticky="ew", padx=(6, 0), pady=2)

        # Vencimiento y CVV (en la misma fila)
        tk.Label(f_tarj_inputs, text="Vence (MM/AA) *:", font=(FONT_FAMILY, 8, "bold"), bg=COLOR_WHITE).grid(row=3, column=0, sticky="w", pady=2)
        entry_card_exp = tk.Entry(f_tarj_inputs, font=("Courier New", 9), width=8, relief="solid", bd=1)
        entry_card_exp.insert(0, "12/28")
        entry_card_exp.grid(row=3, column=1, sticky="w", padx=(6, 10), pady=2)

        tk.Label(f_tarj_inputs, text="CVV *:", font=(FONT_FAMILY, 8, "bold"), bg=COLOR_WHITE).grid(row=3, column=2, sticky="e", pady=2)
        entry_card_cvv = tk.Entry(f_tarj_inputs, font=("Courier New", 9), width=6, show="*", relief="solid", bd=1)
        entry_card_cvv.insert(0, "456")
        entry_card_cvv.grid(row=3, column=3, sticky="w", padx=(6, 0), pady=2)

        # Cuotas disponibles
        tk.Label(f_tarj_inputs, text="Cuotas disponibles:", font=(FONT_FAMILY, 8, "bold"), bg=COLOR_WHITE).grid(row=4, column=0, sticky="w", pady=2)
        combo_cuotas = ttk.Combobox(
            f_tarj_inputs,
            values=["1 pago sin interés (Precio contado)", "3 cuotas fijas", "6 cuotas fijas"],
            state="readonly", font=(FONT_FAMILY, 8)
        )
        combo_cuotas.current(0)
        combo_cuotas.grid(row=4, column=1, columnspan=3, sticky="ew", padx=(6, 0), pady=2)

        # Nº Autorización POS
        tk.Label(f_tarj_inputs, text="Nº Autorización POS:", font=(FONT_FAMILY, 8, "bold"), bg=COLOR_WHITE).grid(row=5, column=0, sticky="w", pady=2)
        f_auth_line = tk.Frame(f_tarj_inputs, bg=COLOR_WHITE)
        f_auth_line.grid(row=5, column=1, columnspan=3, sticky="ew", padx=(6, 0), pady=2)

        entry_card_auth = tk.Entry(f_auth_line, font=("Courier New", 9, "bold"), relief="solid", bd=1)
        entry_card_auth.insert(0, f"AUTH-POS-{random.randint(100000, 999999)}")
        entry_card_auth.pack(side="left", fill="x", expand=True)

        def simular_pos():
            rand_auth = f"AUTH-POS-{random.randint(100000, 999999)}"
            entry_card_auth.delete(0, tk.END)
            entry_card_auth.insert(0, rand_auth)
            messagebox.showinfo("POS Terminal", f"¡Aprobación POS exitosa!\nCódigo de autorización: {rand_auth}")

        btn_sim_pos = tk.Button(
            f_auth_line, text="⚡ Generar", font=(FONT_FAMILY, 8, "bold"),
            bg="#E0F2FE", fg="#0369A1", activebackground="#BAE6FD", relief="flat", cursor="hand2",
            command=simular_pos
        )
        btn_sim_pos.pack(side="right", padx=(4, 0))

        f_tarj_inputs.columnconfigure(1, weight=1)

        # 3. Contenedor Mercado Pago
        f_mp = tk.Frame(modal, bg=COLOR_WHITE)
        tk.Label(f_mp, text="📱 QR Oficial Interoperable (Transferencias 3.0)", font=(FONT_FAMILY, 12, "bold"), fg="#009EE3", bg=COLOR_WHITE).pack(pady=(4, 2))
        tk.Label(f_mp, text="Compatible con Mercado Pago, MODO, Cuenta DNI, BNA+, Galicia, etc.", font=(FONT_FAMILY, 8), fg=COLOR_TEXT_MUTED, bg=COLOR_WHITE).pack()

        qr_container = tk.Frame(f_mp, bg="#F8FAFC", relief="solid", bd=1, padx=6, pady=6)
        qr_container.pack(pady=6)

        lbl_qr_img = tk.Label(qr_container, text="Generando QR EMVCo...", font=(FONT_FAMILY, 9, "italic"), fg=COLOR_TEXT_MUTED, bg="#F8FAFC", width=22, height=9)
        lbl_qr_img.pack()

        def cargar_qr_mp_async():
            def run():
                try:
                    img_data = obtener_qr_image_bytes(monto=total)
                    if img_data:
                        def apply_img():
                            if modal.winfo_exists():
                                modal.qr_photo = tk.PhotoImage(data=img_data)
                                lbl_qr_img.config(image=modal.qr_photo, text="", width=160, height=160)
                        modal.after(0, apply_img)
                    else:
                        def apply_err():
                            if modal.winfo_exists():
                                lbl_qr_img.config(text="QR EMVCo Generado\nUsar Alias/CVU", width=22, height=9)
                        modal.after(0, apply_err)
                except Exception as ex:
                    print("Error async QR:", ex)
            t = threading.Thread(target=run, daemon=True)
            t.start()

        cargar_qr_mp_async()

        mp_info_box = tk.Frame(f_mp, bg="#F0F9FF", relief="solid", bd=1, padx=8, pady=4)
        mp_info_box.pack(fill="x", pady=(2, 6))
        tk.Label(mp_info_box, text=f"Titular: {BANCO_TITULAR}  |  CUIT: {BANCO_CUIT}", font=(FONT_FAMILY, 8, "bold"), fg="#0369A1", bg="#F0F9FF").pack()
        tk.Label(mp_info_box, text=f"Alias: {BANCO_ALIAS}  |  CVU: {BANCO_CVU}", font=(FONT_FAMILY, 8), fg="#0C4A6E", bg="#F0F9FF").pack()

        f_mp_actions = tk.Frame(f_mp, bg=COLOR_WHITE)
        f_mp_actions.pack(fill="x", pady=2)
        tk.Label(f_mp_actions, text="Nº Operación MP:", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_WHITE).pack(side="left")
        entry_mp_op = tk.Entry(f_mp_actions, font=("Courier New", 10, "bold"), relief="solid", bd=1)
        entry_mp_op.pack(side="left", fill="x", expand=True, padx=(6, 4))

        def autocompletar_mp():
            op_id = f"MP-{random.randint(100000000, 999999999)}"
            entry_mp_op.delete(0, tk.END)
            entry_mp_op.insert(0, op_id)
            messagebox.showinfo("Mercado Pago", f"¡Pago acreditado en cuenta!\nID Operación: {op_id}")

        btn_auto_mp = tk.Button(f_mp_actions, text="⚡ Acreditar", font=(FONT_FAMILY, 8, "bold"), bg="#DCFCE7", fg="#166534", relief="flat", cursor="hand2", command=autocompletar_mp)
        btn_auto_mp.pack(side="right", padx=(2, 2))

        btn_link_mp = tk.Button(f_mp_actions, text="🌐 Link", font=(FONT_FAMILY, 8, "bold"), bg="#E0F2FE", fg="#0369A1", relief="flat", cursor="hand2", command=lambda: webbrowser.open(MP_LINK_PAGO))
        btn_link_mp.pack(side="right")

        # 4. Contenedor Transferencia
        f_transf = tk.Frame(modal, bg=COLOR_WHITE)
        tk.Label(f_transf, text="🏦 Transferencia Bancaria Inmediata", font=(FONT_FAMILY, 12, "bold"), fg=COLOR_DARK, bg=COLOR_WHITE).pack(pady=(6, 4))
        
        bank_box = tk.Frame(f_transf, bg="#FAF5EA", relief="solid", bd=1, padx=12, pady=8)
        bank_box.pack(fill="x", pady=(0, 8))
        tk.Label(bank_box, text=f"Banco/Billetera: {BANCO_NOMBRE}", font=(FONT_FAMILY, 9, "bold"), fg=COLOR_DARK, bg="#FAF5EA").pack(anchor="w")
        tk.Label(bank_box, text=f"Titular: {BANCO_TITULAR}", font=(FONT_FAMILY, 8), fg=COLOR_TEXT, bg="#FAF5EA").pack(anchor="w")
        tk.Label(bank_box, text=f"CUIT: {BANCO_CUIT}", font=(FONT_FAMILY, 8), fg=COLOR_TEXT, bg="#FAF5EA").pack(anchor="w")
        tk.Label(bank_box, text=f"Alias: {BANCO_ALIAS}", font=(FONT_FAMILY, 9, "bold"), fg=COLOR_ROJO, bg="#FAF5EA").pack(anchor="w")
        tk.Label(bank_box, text=f"CVU: {BANCO_CVU}", font=(FONT_FAMILY, 8, "bold"), fg=COLOR_DARK, bg="#FAF5EA").pack(anchor="w")

        f_transf_ref = tk.Frame(f_transf, bg=COLOR_WHITE)
        f_transf_ref.pack(fill="x", pady=4)
        tk.Label(f_transf_ref, text="Nº Comprobante:", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_WHITE).pack(side="left")
        entry_transf_ref = tk.Entry(f_transf_ref, font=("Courier New", 10, "bold"), relief="solid", bd=1)
        entry_transf_ref.pack(side="left", fill="x", expand=True, padx=(6, 4))

        def test_voucher_transf():
            v_id = f"TRANSF-{datetime.datetime.now().strftime('%Y%m%d')}-{random.randint(1000, 9999)}"
            entry_transf_ref.delete(0, tk.END)
            entry_transf_ref.insert(0, v_id)
            messagebox.showinfo("Comprobante Transferencia", f"Comprobante asignado:\n{v_id}")

        btn_test_transf = tk.Button(f_transf_ref, text="💡 Asignar", font=(FONT_FAMILY, 8, "bold"), bg="#FEF3C7", fg="#92400E", relief="flat", cursor="hand2", command=test_voucher_transf)
        btn_test_transf.pack(side="right")
        
        tk.Label(f_transf, text="Verificar la acreditación en línea antes de confirmar.", font=(FONT_FAMILY, 8, "italic"), fg=COLOR_TEXT_MUTED, bg=COLOR_WHITE).pack(pady=(4, 0))

        # Botón Final Cobrar
        btn_confirmar = tk.Button(
            modal,
            text="🖨 CONFIRMAR Y EMITIR FACTURA FISCAL (ENTER)",
            font=(FONT_FAMILY, 11, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            activebackground=COLOR_ROJO_OSCURO,
            relief="flat",
            cursor="hand2",
            command=lambda: self._procesar_venta(
                modal,
                metodo_var.get(),
                entry_paga_con.get(),
                total,
                mp_op=entry_mp_op.get(),
                card_num=entry_card_num.get(),
                card_name=entry_card_name.get(),
                card_dni=entry_card_dni.get(),
                card_exp=entry_card_exp.get(),
                card_cvv=entry_card_cvv.get(),
                card_auth=entry_card_auth.get(),
                card_cuotas=combo_cuotas.get(),
                transf_ref=entry_transf_ref.get()
            )
        )
        btn_confirmar.pack(fill="x", padx=20, pady=15, ipady=10, side="bottom")
        modal.bind("<Return>", lambda e: btn_confirmar.invoke())

    def _procesar_venta(self, modal, metodo_pago, paga_con_str, total, mp_op="", card_num="", card_name="", card_dni="", card_exp="", card_cvv="", card_auth="", card_cuotas="1 Pago", transf_ref=""):
        try:
            paga_con = float(paga_con_str.replace(".", "").replace(",", ".")) if metodo_pago == "efectivo" else total
        except ValueError:
            paga_con = total

        mesa_num = self.numero_mesa if self.tipo_entrega == "salon" else None

        num_cuotas = 1
        if "3" in str(card_cuotas):
            num_cuotas = 3
        elif "6" in str(card_cuotas):
            num_cuotas = 6

        # Validaciones de Tarjeta idénticas a la web
        if metodo_pago == "tarjeta":
            num_clean = card_num.replace(" ", "").replace("-", "")
            if len(num_clean) < 15:
                messagebox.showwarning("Datos de Tarjeta Incompletos", "Por favor ingresá un número de tarjeta válido (16 dígitos).")
                return
            if not card_name.strip():
                messagebox.showwarning("Datos de Tarjeta Incompletos", "Por favor ingresá el nombre y apellido del titular como figura en el plástico.")
                return
            if not card_dni.strip():
                messagebox.showwarning("Datos de Tarjeta Incompletos", "Por favor ingresá el DNI del titular de la tarjeta.")
                return
            if not card_exp.strip() or "/" not in card_exp:
                messagebox.showwarning("Datos de Tarjeta Incompletos", "Por favor ingresá la fecha de vencimiento en formato MM/AA.")
                return
            if len(card_cvv.strip()) < 3:
                messagebox.showwarning("Datos de Tarjeta Incompletos", "Por favor ingresá el código de seguridad (CVV de 3 o 4 dígitos).")
                return
            if not card_auth.strip():
                card_auth = f"AUTH-POS-{random.randint(100000, 999999)}"

        payload = {
            "numero_caja": TERMINAL_NUMERO,
            "terminal_nombre": TERMINAL_NOMBRE,
            "idempleado_cajero": self.cajero_info.get("idempleado") or self.cajero_info.get("id", 1),
            "cajero_id": self.cajero_info.get("idempleado") or self.cajero_info.get("id", 1),
            "cajero_nombre": self.cajero_info.get("nombre_completo", "Cajero"),
            "items": self.comanda,
            "tipo_entrega": self.tipo_entrega,
            "numero_mesa": mesa_num,
            "idusuario": self.cliente_club.get("idusuario") if self.cliente_club else None,
            "nombre_cliente": (card_name.strip() if metodo_pago == "tarjeta" and card_name.strip() else (self.cliente_club.get("nombre_completo") if self.cliente_club else "Consumidor Final")),
            "cuit_dni_cliente": (card_dni.strip() if metodo_pago == "tarjeta" and card_dni.strip() else (self.cliente_club.get("dni", "0") if self.cliente_club else "0")),
            "metodo_pago": metodo_pago,
            "mp_payment_id": mp_op.strip() if mp_op else None,
            "tarjeta_numero": card_num.strip() if metodo_pago == "tarjeta" else None,
            "tarjeta_nombre": card_name.strip() if metodo_pago == "tarjeta" else None,
            "tarjeta_dni": card_dni.strip() if metodo_pago == "tarjeta" else None,
            "tarjeta_auth_code": card_auth.strip() if card_auth else None,
            "tarjeta_cuotas": num_cuotas,
            "comprobante_transferencia": transf_ref.strip() if transf_ref else None,
            "tipo_comprobante": "Factura B",
            "descuento": self.descuento,
            "paga_con": paga_con,
            "notas": f"Venta POS Mostrador/Salón ({self.tipo_entrega.upper()})"
        }

        res = api.crear_venta(payload)
        if res.get("ok"):
            modal.destroy()
            self._mostrar_ticket_termico(res)
            self.comanda.clear()
            self.cliente_club = None
            self.descuento = 0.0
            self.lbl_cliente_info.pack_forget()
            self._render_comanda()
            self._load_data()
        else:
            messagebox.showerror("Error al procesar", res.get("error", "No se pudo registrar la venta."))

    # ------------------------------------------------------------
    # VISTA PREVIA DE FACTURA FISCAL / TICKET TÉRMICO
    # ------------------------------------------------------------
    def _mostrar_ticket_termico(self, data):
        t_win = tk.Toplevel(self.root)
        num_fac = data.get("numero_factura", f"FAC-B-0001-{data.get('idpedido', 0):08d}")
        t_win.title(f"Factura #{num_fac} · Mostaza POS")
        t_win.geometry("400x560")
        t_win.configure(bg="#333333")

        paper = tk.Frame(t_win, bg="#FFFFFF", padx=15, pady=15)
        paper.pack(fill="both", expand=True, padx=12, pady=12)

        cae_num = data.get("cae", "74123456789012")
        cae_vto = data.get("cae_vto", datetime.datetime.now().strftime("%d/%m/%Y"))

        pago_det = data.get('metodo_pago', '').upper()
        if data.get("mp_payment_id"):
            pago_det += f" (Op #{data.get('mp_payment_id')})"
        elif data.get("tarjeta_auth_code"):
            cuotas_txt = f" {data.get('tarjeta_cuotas')}C" if data.get('tarjeta_cuotas', 1) > 1 else ""
            pago_det += f"{cuotas_txt} (Auth #{data.get('tarjeta_auth_code')})"
        elif data.get("comprobante_transferencia"):
            pago_det += f" (Comp #{data.get('comprobante_transferencia')})"

        ticket_text = f"""
========================================
             MOSTAZA SA
  EPET Nº 7 · Sistema Fiscal POS
 CUIT: 30-71234567-9 · IVA Resp. Inscripto
========================================
FACTURA B: {num_fac}
Fecha: {data.get('fecha')}
Cajero: {data.get('cajero', self.cajero_info.get('nombre_completo', 'Operador'))}
Cliente: {data.get('cliente', 'Consumidor Final')}
Modalidad: {data.get('tipo_entrega', '').upper()} {f"(Mesa {data.get('numero_mesa')})" if data.get('numero_mesa') else ''}
Pago: {pago_det}
----------------------------------------
"""
        for it in data.get("items", []):
            cant = it.get('cantidad', 1)
            desc = it.get('descripcion', '')[:18]
            subt = int(it.get('subtotal', 0))
            ticket_text += f"{cant}x {desc:<18} ${subt:>7}\n"
            if it.get("modificadores"):
                ticket_text += f"   ↳ {it['modificadores']}\n"

        neto = data.get('neto_gravado_formateado', '$' + str(int(data.get('total', 0) / 1.21)))
        iva = data.get('iva_21_formateado', '$' + str(int(data.get('total', 0) - (data.get('total', 0) / 1.21))))

        ticket_text += f"""----------------------------------------
Subtotal Neto (21%):       {neto:>11}
IVA Discriminado (21%):     {iva:>11}
TOTAL FACTURA:             {data.get('total_formateado'):>11}
"""
        if data.get("metodo_pago") == "efectivo":
            ticket_text += f"""Abonó con:                 ${int(data.get('paga_con', 0)):>10,}
Vuelto:                    {data.get('vuelto_formateado'):>11}
"""
        elif data.get("mp_payment_id"):
            ticket_text += f"Comprobante MP:            {str(data.get('mp_payment_id')):>11}\n"
        elif data.get("tarjeta_auth_code") or data.get("metodo_pago") == "tarjeta":
            last4 = data.get("tarjeta_last4", "XXXX")
            nom_tarj = data.get("tarjeta_nombre", "")
            cuotas_cant = data.get("tarjeta_cuotas", 1)
            ticket_text += f"Tarjeta:                   **** **** **** {last4}\n"
            if nom_tarj:
                ticket_text += f"Titular:                   {nom_tarj[:24]}\n"
            ticket_text += f"Plan Cuotas:               {cuotas_cant} cuota(s)\n"
            ticket_text += f"Voucher POS / Auth:        {str(data.get('tarjeta_auth_code', 'APROBADO')):>11}\n"
        elif data.get("comprobante_transferencia"):
            ticket_text += f"Transf. Bancaria:          {str(data.get('comprobante_transferencia')):>11}\n"
        ticket_text += f"""----------------------------------------
CAE Nº: {cae_num}
Vto. CAE: {cae_vto}
========================================
       ¡GRACIAS POR SU COMPRA!
========================================
"""

        txt_widget = tk.Text(paper, font=("Courier New", 9), bg="#FFFFFF", relief="flat")
        txt_widget.insert(tk.END, ticket_text)
        txt_widget.config(state="disabled")
        txt_widget.pack(fill="both", expand=True)

        btn_close = tk.Button(paper, text="✓ Cerrar e Imprimir", font=(FONT_FAMILY, 10, "bold"), bg=COLOR_ROJO, fg=COLOR_WHITE, relief="flat", command=t_win.destroy)
        btn_close.pack(fill="x", pady=(10, 0))

    # ------------------------------------------------------------
    # RELOJ DIGITAL Y ATAJOS
    # ------------------------------------------------------------
    def _start_clock(self):
        def update():
            now = datetime.datetime.now().strftime("%H:%M:%S")
            self.lbl_clock.config(text=now)
            self.root.after(1000, update)
        update()

    def _setup_shortcuts(self):
        self.root.bind("<F2>", lambda e: self.entry_search.focus_set())
        self.root.bind("<F4>", lambda e: self._abrir_ventana_takeaway())
        self.root.bind("<F8>", lambda e: self._vaciar_comanda())
        self.root.bind("<F10>", lambda e: self._abrir_modal_cobro())
        self.root.bind("<F11>", self._toggle_fullscreen)
        self.root.bind("<Escape>", self._salir_fullscreen)

    def _toggle_fullscreen(self, event=None):
        self.is_fullscreen = not self.is_fullscreen
        self.root.attributes("-fullscreen", self.is_fullscreen)
        if self.is_fullscreen:
            if hasattr(self, "btn_fullscreen"):
                self.btn_fullscreen.config(
                    text="🗗 Salir (F11)",
                    bg="#FEF3C7",
                    fg="#92400E"
                )
        else:
            if hasattr(self, "btn_fullscreen"):
                self.btn_fullscreen.config(
                    text="🗖 Pantalla Completa (F11)",
                    bg="#F1F5F9",
                    fg=COLOR_TEXT
                )
            try:
                self.root.state("zoomed")
            except Exception:
                pass

    def _salir_fullscreen(self, event=None):
        if self.is_fullscreen:
            self._toggle_fullscreen()

    def _abrir_ventana_arqueo(self):
        from arqueo_window import ArqueoWindow
        ArqueoWindow(self.root, self.cajero_info, on_update=self._load_data)

    def _abrir_ventana_takeaway(self):
        from takeaway_window import TakeAwayWindow
        TakeAwayWindow(self.root, self.cajero_info, on_update=self._poll_takeaway_badge)

    def _poll_takeaway_badge(self):
        def fetch():
            try:
                res = api.obtener_pedidos_takeaway()
                if res.get("ok"):
                    peds = res.get("pedidos", [])
                    cant_listos = sum(1 for p in peds if p.get("estado") in ["Listo", "Listo para Despacho"])
                    self.root.after(0, lambda: self._update_takeaway_badge(cant_listos))
            except Exception:
                pass
        threading.Thread(target=fetch, daemon=True).start()
        # Reprogramar polling cada 3.5s
        try:
            self.root.after(3500, self._poll_takeaway_badge)
        except Exception:
            pass

    def _update_takeaway_badge(self, cant_listos):
        if not hasattr(self, "btn_takeaway") or not self.btn_takeaway.winfo_exists():
            return
        if cant_listos > 0:
            self.btn_takeaway.configure(
                text=f"🛍️ Take Away (F4) [● {cant_listos} LISTOS]",
                bg="#DCFCE7",
                fg="#166534"
            )
        else:
            self.btn_takeaway.configure(
                text="🛍️ Take Away (F4)",
                bg="#F1F5F9",
                fg=COLOR_TEXT
            )

    def _abrir_ventana_tickets(self):
        from tickets_window import TicketsWindow
        TicketsWindow(self.root, self.cajero_info)

    def _cerrar_sesion(self):
        if messagebox.askyesno("Cerrar Turno y Salir", "¿Deseas finalizar tu jornada laboral y registrar tu hora de salida?"):
            if self.is_fullscreen:
                try:
                    self.root.attributes("-fullscreen", False)
                    self.is_fullscreen = False
                except Exception:
                    pass
            emp_id = self.cajero_info.get("id")
            reg_id = self.cajero_info.get("idregistro_turno")
            try:
                res = api.cerrar_turno(emp_id, reg_id)
                if res.get("ok") and res.get("resumen"):
                    r = res["resumen"]
                    hs = r.get("horas_trabajadas", 0)
                    h_sal = r.get("hora_salida", datetime.datetime.now().strftime("%H:%M:%S"))
                    messagebox.showinfo(
                        "Turno Finalizado",
                        f"¡Jornada registrada exitosamente!\n\n"
                        f"👤 Empleado: {self.cajero_info.get('nombre_completo')}\n"
                        f"⏰ Turno: {r.get('turno_nombre', self.cajero_info.get('turno', 'Turno'))}\n"
                        f"📥 Entrada: {self.cajero_info.get('hora_entrada', '--:--')}\n"
                        f"📤 Salida: {h_sal}\n"
                        f"⏱ Horas trabajadas: {hs} hs\n\n"
                        f"¡Hasta la próxima!"
                    )
            except Exception as e:
                print("Error al registrar salida:", e)
            self.on_logout()
