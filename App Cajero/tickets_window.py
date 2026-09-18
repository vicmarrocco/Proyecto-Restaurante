"""
App Cajero - Ventana de Historial de Tickets y Reimpresión
Consulta de comprobantes del turno.
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
        self.win.title("Historial de Comprobantes de Hoy")
        self.win.geometry("780x560")
        self.win.minsize(680, 480)
        self.win.configure(bg=COLOR_LIGHT_BG)
        self.win.grab_set()

        self._build_ui()
        self._load_tickets()

    def _build_ui(self):
        # Header
        h = tk.Frame(self.win, bg=COLOR_SIDEBAR, height=50, padx=15)
        h.pack(fill="x")

        tk.Label(h, text="🧾 HISTORIAL DE TICKETS DE HOY", font=(FONT_FAMILY, 12, "bold"), fg=COLOR_WHITE, bg=COLOR_SIDEBAR).pack(side="left")

        btn_ref = tk.Button(h, text="🔄 Actualizar", font=(FONT_FAMILY, 9), bg="#334155", fg=COLOR_WHITE, relief="flat", command=self._load_tickets)
        btn_ref.pack(side="right", pady=10)

        # Contenedor Tabla
        container = tk.Frame(self.win, bg=COLOR_WHITE, padx=15, pady=15, relief="solid", bd=1)
        container.pack(fill="both", expand=True, padx=15, pady=15)

        columns = ("id", "hora", "tipo", "pago", "total", "estado")
        self.tree = ttk.Treeview(container, columns=columns, show="headings", selectmode="browse")

        self.tree.heading("id", text="N.º Comanda")
        self.tree.heading("hora", text="Hora")
        self.tree.heading("tipo", text="Modalidad")
        self.tree.heading("pago", text="Medio de Pago")
        self.tree.heading("total", text="Total")
        self.tree.heading("estado", text="Estado")

        self.tree.column("id", width=90, anchor="center")
        self.tree.column("hora", width=90, anchor="center")
        self.tree.column("tipo", width=120, anchor="center")
        self.tree.column("pago", width=120, anchor="center")
        self.tree.column("total", width=110, anchor="e")
        self.tree.column("estado", width=110, anchor="center")

        scroll = ttk.Scrollbar(container, orient="vertical", command=self.tree.yview)
        self.tree.configure(yscrollcommand=scroll.set)

        self.tree.pack(side="left", fill="both", expand=True)
        scroll.pack(side="right", fill="y")

        # Botón de Reimpresión
        btn_f = tk.Frame(self.win, bg=COLOR_LIGHT_BG, padx=15, pady=10)
        btn_f.pack(fill="x")

        btn_reimprimir = tk.Button(
            btn_f,
            text="🖨 Ver / Reimprimir Ticket Seleccionado",
            font=(FONT_FAMILY, 10, "bold"),
            bg=COLOR_ROJO,
            fg=COLOR_WHITE,
            relief="flat",
            cursor="hand2",
            padx=15,
            pady=8,
            command=self._reimprimir_ticket
        )
        btn_reimprimir.pack(side="right")

    def _load_tickets(self):
        for item in self.tree.get_children():
            self.tree.delete(item)

        def run_fetch():
            res = api._get("/pos/pedidos.php") # Usamos el listado de pedidos
            self.win.after(0, lambda: self._on_tickets_loaded(res))

        threading.Thread(target=run_fetch, daemon=True).start()

    def _on_tickets_loaded(self, res):
        pedidos = res.get("pedidos", []) if res.get("ok") else []

        if not pedidos:
            return

        for p in pedidos:
            tipo_txt = p.get("tipo_entrega", "").upper()
            if p.get("numero_mesa"):
                tipo_txt += f" (Mesa {p['numero_mesa']})"

            self.tree.insert("", "end", values=(
                f"#{p.get('idpedido')}",
                p.get("hora", p.get("fecha", "")),
                tipo_txt,
                p.get("metodo_pago", "").upper(),
                f"${int(p.get('total', 0)):,}".replace(",", "."),
                p.get("estado", "Recibido")
            ))

    def _reimprimir_ticket(self):
        sel = self.tree.selection()
        if not sel:
            messagebox.showinfo("Selección", "Seleccioná un ticket de la lista para ver el comprobante.")
            return

        vals = self.tree.item(sel[0], "values")
        id_pedido = vals[0].replace("#", "")

        # Mostrar ventana con el ticket
        t_win = tk.Toplevel(self.win)
        t_win.title(f"Ticket #{id_pedido} · Reimpresión Mostaza POS")
        t_win.geometry("380x500")
        t_win.configure(bg="#333333")

        paper = tk.Frame(t_win, bg="#FFFFFF", padx=15, pady=15)
        paper.pack(fill="both", expand=True, padx=15, pady=15)

        ticket_text = f"""
================================
         MOSTAZA
  Spider Web · Sistema POS
 CUIT: 30-71234567-9
================================
Comanda N.º: #{id_pedido}
Modalidad: {vals[2]}
Pago: {vals[3]}
--------------------------------
TOTAL FACTURADO:   {vals[4]}
================================
   ¡GRACIAS POR SU COMPRA!
================================
"""
        txt = tk.Text(paper, font=("Courier New", 9), bg="#FFFFFF", relief="flat")
        txt.insert(tk.END, ticket_text)
        txt.config(state="disabled")
        txt.pack(fill="both", expand=True)

        btn_c = tk.Button(paper, text="✓ Cerrar", font=(FONT_FAMILY, 9, "bold"), bg=COLOR_ROJO, fg=COLOR_WHITE, relief="flat", command=t_win.destroy)
        btn_c.pack(fill="x", pady=(10, 0))
