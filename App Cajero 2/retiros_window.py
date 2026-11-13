"""Mostaza POS · Retiros Take Away (tablero en vivo).
Columnas: En preparación → ¡Listos para retirar! → Retirados. Búsqueda por #código/cliente,
confirmación de retiro con checklist y cancelación con devolución. Refresca solo cada 3 s."""
import threading
import tkinter as tk
from datetime import datetime
from config import TERMINAL_NOMBRE
from api_client import api
from theme import T, font, Btn, chip, card, toast, scroll_column

LISTO = ("Listo", "Listo para Despacho")
COCINA = ("Recibido", "En Cocina", "En Preparación", "En Preparacion")

class RetirosWindow:
    _last_origen = "todos"
    _last_query = ""

    def __init__(self, parent, cajero_info=None, on_update=None):
        self.cajero, self.on_update, self.active = cajero_info or {}, on_update, True
        self.pedidos = []
        self.origen = RetirosWindow._last_origen
        self._sig = None
        self._listos_vistos = None
        self.win = tk.Toplevel(parent)
        self.win.title(f"Retiros Take Away · {TERMINAL_NOMBRE}")
        self.win.geometry("1180x720"); self.win.minsize(980, 560); self.win.configure(bg=T["bg"])
        self.win.protocol("WM_DELETE_WINDOW", self._close)
        self.q = tk.StringVar(value=RetirosWindow._last_query)
        self.q.trace_add("write", lambda *a: self._on_q_write())
        self._build(); self._load(); self._tick()

    def _on_q_write(self):
        RetirosWindow._last_query = self.q.get()
        self._render(force=True)

    # ---------- layout ----------
    def _build(self):
        w = self.win
        top = tk.Frame(w, bg=T["red"], padx=20, pady=12); top.pack(fill="x")
        tk.Label(top, text="🛍  Retiros · Take Away", bg=T["red"], fg="#FFF", font=font(18, True)).pack(side="left")
        self.kpi = tk.Label(top, text="", bg=T["red"], fg=T["mustard"], font=font(12, True)); self.kpi.pack(side="right")

        bar = tk.Frame(w, bg=T["bg"], padx=20, pady=12); bar.pack(fill="x")
        sf = tk.Frame(bar, bg=T["surface"], highlightbackground=T["line"], highlightthickness=1); sf.pack(side="left")
        tk.Label(sf, text="🔎", bg=T["surface"], font=font(12)).pack(side="left", padx=(10, 2))
        self.entry = tk.Entry(sf, textvariable=self.q, font=font(13), bd=0, width=26, bg=T["surface"], fg=T["ink"], insertbackground=T["ink"])
        self.entry.pack(side="left", ipady=8, padx=(0, 10)); self.entry.focus_set()
        self.entry.bind("<Return>", self._enter_busqueda)
        tk.Label(bar, text="Escribí #código o nombre · Enter = entregar", bg=T["bg"], fg=T["muted"], font=font(9)).pack(side="left", padx=10)
        self.seg = {}
        for k, t in (("presencial", "🏪 Caja"), ("online", "🌐 Online"), ("todos", "Todos")):
            b = tk.Label(bar, text=t, font=font(10, True), padx=14, pady=7, cursor="hand2")
            b.pack(side="right", padx=2); b.bind("<Button-1>", lambda e, k=k: self._set_origen(k)); self.seg[k] = b
        self._paint_seg()

        body = tk.Frame(w, bg=T["bg"], padx=14); body.pack(fill="both", expand=True)
        self.cols = {}
        for i, (k, title, col) in enumerate((("cocina", "🍳 En preparación", T["amber"]),
                                             ("listo", "🔔 ¡Listos para retirar!", T["green"]),
                                             ("fin", "✓ Retirados / Cancelados", T["muted"]))):
            body.columnconfigure(i, weight=1, uniform="c")
            box = tk.Frame(body, bg=T["bg"], padx=6); box.grid(row=0, column=i, sticky="nsew")
            body.rowconfigure(0, weight=1)
            h = tk.Label(box, text=title, bg=T["bg"], fg=col, font=font(13, True), anchor="w"); h.pack(fill="x", pady=(0, 6))
            holder = tk.Frame(box, bg=T["bg"]); holder.pack(fill="both", expand=True)
            self.cols[k] = (h, title, scroll_column(holder))
        self.foot = tk.Label(w, text="", bg=T["bg"], fg=T["muted"], font=font(9), anchor="e", padx=20); self.foot.pack(fill="x")

    def _paint_seg(self):
        for k, b in self.seg.items():
            on = k == self.origen
            b.configure(bg=T["ink"] if on else "#F3ECE0", fg="#FFF" if on else T["ink"])

    def _set_origen(self, k):
        self.origen = k; RetirosWindow._last_origen = k; self._paint_seg(); self._render(force=True)

    # ---------- datos ----------
    def _load(self):
        def run():
            res = api.obtener_pedidos_takeaway()
            if self.active: self.win.after(0, lambda: self._loaded(res))
        threading.Thread(target=run, daemon=True).start()

    def _loaded(self, res):
        if not self.active: return
        if res.get("ok"):
            self.pedidos = res.get("pedidos", []); self.foot.configure(text=f"Actualizado {datetime.now():%H:%M:%S}")
            ids = {str(p.get("idpedido")) for p in self.pedidos if p.get("estado") in LISTO}
            if self._listos_vistos is not None and ids - self._listos_vistos:
                toast(self.win, "🔔 ¡Hay pedidos nuevos listos para retirar!", "ok", 4000); self.win.bell()
            self._listos_vistos = ids
            self._render()
        else:
            toast(self.win, res.get("error", "Sin conexión con el servidor"), "err")

    def _tick(self):
        if not self.active: return
        self._load(); self.win.after(3000, self._tick)

    # ---------- render ----------
    @staticmethod
    def _espera(p):
        try:
            h, m = map(int, str(p.get("hora", "")).split(":")[:2])
            n = datetime.now(); return max(0, int((n - n.replace(hour=h, minute=m, second=0)).total_seconds() // 60))
        except Exception:
            return None

    def _visibles(self):
        q = self.q.get().strip().lower().lstrip("#")
        out = []
        for p in self.pedidos:
            pres = bool(p.get("idempleado_cajero"))
            if (self.origen == "online" and pres) or (self.origen == "presencial" and not pres): continue
            if q and q not in str(p.get("idpedido")) and q not in str(p.get("cliente", "")).lower(): continue
            out.append(p)
        return out

    def _render(self, force=False):
        vis = self._visibles()
        mins = datetime.now().strftime("%H:%M")
        sig = (tuple((p.get("idpedido"), p.get("estado")) for p in vis), mins)
        if sig == self._sig and not force: return
        self._sig = sig
        groups = {"cocina": [], "listo": [], "fin": []}
        for p in vis:
            e = p.get("estado", "")
            groups["listo" if e in LISTO else "cocina" if e in COCINA else "fin"].append(p)
        for k, (h, title, fr) in self.cols.items():
            for c in fr.winfo_children(): c.destroy()
            h.configure(text=f"{title}  ({len(groups[k])})")
            if not groups[k]:
                tk.Label(fr, text="Sin pedidos", bg=T["bg"], fg=T["muted"], font=font(10)).pack(pady=20)
            for p in groups[k]: self._card(fr, p, k)
        self.kpi.configure(text=f"🔔 {len(groups['listo'])} listos   ·   🍳 {len(groups['cocina'])} en cocina")

    def _card(self, parent, p, col):
        pid, esp = p.get("idpedido"), self._espera(p)
        accent = {"cocina": T["amber"], "listo": T["green"]}.get(col, T["line"])
        if p.get("estado") == "Cancelado": accent = T["bad"]
        outer, b = card(parent, accent=accent); outer.pack(fill="x", pady=5, padx=2)
        r1 = tk.Frame(b, bg=T["surface"]); r1.pack(fill="x")
        tk.Label(r1, text=f"#{pid}", bg=T["surface"], fg=T["ink"], font=font(16, True)).pack(side="left")
        pres = bool(p.get("idempleado_cajero"))
        chip(r1, "🏪 Caja" if pres else "🌐 Online", T["blue"], T["blue_bg"]).pack(side="right")
        if col != "fin" and esp is not None:
            f, bg = (T["green"], T["green_bg"]) if esp < 10 else (T["amber"], T["amber_bg"]) if esp < 20 else (T["bad"], T["bad_bg"])
            chip(r1, f"⏱ {esp} min", f, bg).pack(side="right", padx=6)
        tk.Label(b, text=p.get("cliente") or "Consumidor Final", bg=T["surface"], fg=T["ink"], font=font(11, True), anchor="w").pack(fill="x")
        its = p.get("items", [])
        txt = "\n".join(f"{i.get('cantidad', 1)}× {i.get('descripcion', '')}" for i in its[:3]) or "Productos varios"
        if len(its) > 3: txt += f"\n+{len(its) - 3} más…"
        tk.Label(b, text=txt, bg=T["surface"], fg=T["muted"], font=font(10), justify="left", anchor="w").pack(fill="x", pady=(2, 4))
        r2 = tk.Frame(b, bg=T["surface"]); r2.pack(fill="x")
        tk.Label(r2, text=p.get("total_formateado", f"${p.get('total', 0):,.0f}"), bg=T["surface"], fg=T["red"], font=font(14, True)).pack(side="left")
        chip(r2, str(p.get("metodo_pago", "")).upper(), T["muted"], "#F3ECE0").pack(side="left", padx=8)
        if col == "listo":
            Btn(r2, "Entregar ✓", lambda p=p: self._confirmar(p), "success", pady=6).pack(side="right")
        if col != "fin":
            Btn(r2, "✕" if col == "listo" else "Cancelar", lambda p=p: self._cancelar(p), "danger", size=9, pady=6, padx=10).pack(side="right", padx=6)
        if col == "fin":
            canc = p.get("estado") == "Cancelado"
            chip(r2, "❌ Cancelado" if canc else "✓ Retirado", T["bad"] if canc else T["green"], T["bad_bg"] if canc else T["green_bg"]).pack(side="right")

    # ---------- acciones ----------
    def _enter_busqueda(self, e=None):
        cand = [p for p in self._visibles() if p.get("estado") in LISTO]
        if len(cand) == 1: self._confirmar(cand[0])
        else: toast(self.win, "Escribí un código que coincida con un solo pedido listo", "warn")

    def _modal(self, title, w=420, h=420):
        m = tk.Toplevel(self.win); m.title(title); m.configure(bg=T["surface"], padx=22, pady=18)
        m.geometry(f"{w}x{h}"); m.transient(self.win); m.grab_set(); return m

    def _confirmar(self, p):
        m = self._modal(f"Entregar #{p.get('idpedido')}", 420, 440)
        tk.Label(m, text=f"Entregar pedido #{p.get('idpedido')}", bg=T["surface"], font=font(16, True)).pack(anchor="w")
        tk.Label(m, text=f"Cliente: {p.get('cliente') or 'Consumidor Final'}", bg=T["surface"], fg=T["muted"], font=font(11)).pack(anchor="w", pady=(2, 10))
        tk.Label(m, text="Verificá con el cliente:", bg=T["surface"], font=font(10, True)).pack(anchor="w")
        for i in p.get("items", []):
            tk.Label(m, text=f"☐  {i.get('cantidad', 1)}× {i.get('descripcion', '')}", bg=T["surface"], font=font(12), anchor="w").pack(fill="x", pady=1)
        row = tk.Frame(m, bg=T["surface"]); row.pack(side="bottom", fill="x")
        Btn(row, "Volver", m.destroy, "ghost").pack(side="left")
        Btn(row, "Confirmar retiro ✓", lambda: self._do_entrega(p, m), "success", size=12).pack(side="right")

    def _do_entrega(self, p, m):
        m.destroy(); pid = p.get("idpedido")
        def run():
            res = api.cambiar_estado_pedido(pid, "Entregado", empleado_nombre=self.cajero.get("nombre_completo", "Cajero"),
                                            idempleado=self.cajero.get("id"), rol="Cajero",
                                            detalles=f"Retirado en mostrador Take Away de {TERMINAL_NOMBRE}")
            self.win.after(0, lambda: self._after(res, f"✓ Pedido #{pid} entregado"))
        threading.Thread(target=run, daemon=True).start()

    def _cancelar(self, p):
        m = self._modal(f"Cancelar #{p.get('idpedido')}", 420, 330)
        tk.Label(m, text=f"Cancelar pedido #{p.get('idpedido')}", bg=T["surface"], fg=T["bad"], font=font(16, True)).pack(anchor="w")
        tk.Label(m, text="Motivo", bg=T["surface"], fg=T["muted"], font=font(10, True)).pack(anchor="w", pady=(12, 2))
        mot = tk.Entry(m, font=font(12), bg="#FBF7F0", bd=1, relief="solid"); mot.pack(fill="x", ipady=6); mot.insert(0, "Cancelado por cliente en mostrador")
        dev = tk.BooleanVar(value=True)
        tk.Checkbutton(m, text="Devolver el dinero al cliente", variable=dev, bg=T["surface"], font=font(11), activebackground=T["surface"]).pack(anchor="w", pady=12)
        row = tk.Frame(m, bg=T["surface"]); row.pack(side="bottom", fill="x")
        Btn(row, "Volver", m.destroy, "ghost").pack(side="left")
        Btn(row, "Cancelar pedido", lambda: self._do_cancel(p, m, mot.get(), dev.get()), "primary").pack(side="right")

    def _do_cancel(self, p, m, motivo, devolver):
        m.destroy(); pid = p.get("idpedido")
        def run():
            res = api.cancelar_pedido_con_devolucion(pid, motivo=motivo, devolver_dinero=devolver,
                      cajero_nombre=self.cajero.get("nombre_completo", "Cajero"), idempleado=self.cajero.get("id"))
            self.win.after(0, lambda: self._after(res, f"Pedido #{pid} cancelado"))
        threading.Thread(target=run, daemon=True).start()

    def _after(self, res, okmsg):
        if res.get("ok"):
            toast(self.win, okmsg, "ok"); self.q.set(""); self._load()
            if self.on_update: self.on_update()
        else:
            toast(self.win, res.get("error", "No se pudo completar la acción"), "err", 4000)

    def _close(self):
        self.active = False
        if self.on_update: self.on_update()
        self.win.destroy()
