"""Mostaza POS · Sistema de diseño (tokens + componentes reutilizables)."""
import tkinter as tk
from tkinter import ttk
import config as _c

# --- Rebranding global: se aplica ANTES de que las demás ventanas lean config ---
_c.COLOR_LIGHT_BG, _c.COLOR_BORDER, _c.COLOR_BORDER_LIGHT = "#FBF7F0", "#EDE5D8", "#F5EFE4"
_c.COLOR_TEXT, _c.COLOR_TEXT_MUTED, _c.COLOR_DARK = "#1F1A17", "#7A6F66", "#1F1A17"
_c.COLOR_GREEN, _c.COLOR_GREEN_BG = "#15803D", "#DCFCE7"
from config import COLOR_ROJO, COLOR_ROJO_OSCURO, COLOR_AMARILLO, FONT_FAMILY

T = dict(bg="#FBF7F0", surface="#FFFFFF", ink="#1F1A17", muted="#7A6F66", line="#EDE5D8",
         red=COLOR_ROJO, red_d=COLOR_ROJO_OSCURO, mustard=COLOR_AMARILLO, mustard_bg="#FFF3D1",
         green="#15803D", green_bg="#DCFCE7", amber="#B45309", amber_bg="#FEF3C7",
         bad="#B91C1C", bad_bg="#FEE2E2", blue="#1D4ED8", blue_bg="#DBEAFE")

def font(size=11, bold=False):
    return (FONT_FAMILY, size, "bold" if bold else "normal")

_KINDS = {  # (bg, fg, hover)
    "primary": (T["red"], "#FFFFFF", T["red_d"]),
    "success": (T["green"], "#FFFFFF", "#166534"),
    "ghost":   ("#F3ECE0", T["ink"], "#E8DDCB"),
    "danger":  (T["bad_bg"], T["bad"], "#FECACA"),
}

class Btn(tk.Label):
    """Botón plano con hover y cursor de mano."""
    def __init__(self, parent, text, command=None, kind="primary", size=11, padx=16, pady=8, **kw):
        bg, fg, self._hv = _KINDS[kind]
        super().__init__(parent, text=text, bg=bg, fg=fg, font=font(size, True),
                         padx=padx, pady=pady, cursor="hand2", **kw)
        self._bg, self._cmd = bg, command
        self.bind("<Enter>", lambda e: self.configure(bg=self._hv))
        self.bind("<Leave>", lambda e: self.configure(bg=self._bg))
        self.bind("<Button-1>", lambda e: self._cmd and self._cmd())

def chip(parent, text, fg, bg, size=9):
    return tk.Label(parent, text=text, fg=fg, bg=bg, font=font(size, True), padx=8, pady=2)

def card(parent, accent=None, **kw):
    """Tarjeta blanca con borde suave y franja de color opcional a la izquierda."""
    outer = tk.Frame(parent, bg=T["line"], padx=1, pady=1)
    inner = tk.Frame(outer, bg=T["surface"], **kw)
    inner.pack(fill="both", expand=True)
    if accent:
        tk.Frame(inner, bg=accent, width=5).pack(side="left", fill="y")
    body = tk.Frame(inner, bg=T["surface"], padx=12, pady=10)
    body.pack(side="left", fill="both", expand=True)
    return outer, body

def toast(win, msg, kind="ok", ms=2600):
    colors = {"ok": (T["green"], "#FFF"), "warn": (T["amber"], "#FFF"), "err": (T["bad"], "#FFF")}
    bg, fg = colors.get(kind, colors["ok"])
    t = tk.Label(win, text=msg, bg=bg, fg=fg, font=font(11, True), padx=18, pady=10)
    t.place(relx=0.5, rely=0.94, anchor="s")
    win.after(ms, t.destroy)

def scroll_column(parent):
    """Columna con scroll vertical (rueda del mouse). Devuelve el frame interno."""
    cv = tk.Canvas(parent, bg=T["bg"], highlightthickness=0)
    sb = tk.Scrollbar(parent, orient="vertical", command=cv.yview)
    fr = tk.Frame(cv, bg=T["bg"])
    wid = cv.create_window((0, 0), window=fr, anchor="nw")
    fr.bind("<Configure>", lambda e: cv.configure(scrollregion=cv.bbox("all")))
    cv.bind("<Configure>", lambda e: cv.itemconfigure(wid, width=e.width))
    cv.configure(yscrollcommand=sb.set)
    cv.pack(side="left", fill="both", expand=True); sb.pack(side="right", fill="y")
    def wheel(e):
        cv.yview_scroll(-1 if (getattr(e, "delta", 0) > 0 or e.num == 4) else 1, "units")
    fr.bind("<Enter>", lambda e: (cv.bind_all("<MouseWheel>", wheel), cv.bind_all("<Button-4>", wheel), cv.bind_all("<Button-5>", wheel)))
    return fr


def _shade(hex_color, f):
    """Aclara (f>1) u oscurece (f<1) un color #RRGGBB."""
    try:
        h = hex_color.lstrip("#"); r, g, b = (int(h[i:i + 2], 16) for i in (0, 2, 4))
        return "#%02X%02X%02X" % tuple(max(0, min(255, int(v * f))) for v in (r, g, b))
    except Exception:
        return hex_color

def apply_branding(root):
    """Hover + cursor de mano en TODOS los botones y estilo unificado de tablas/pestañas."""
    root.option_add("*Font", font(11))
    def enter(e):
        w = e.widget
        if str(w.cget("state")) == "disabled": return
        w._bg0 = w.cget("bg"); w.configure(bg=_shade(w._bg0, 0.9 if sum(int(w._bg0.lstrip("#")[i:i+2], 16) for i in (0, 2, 4)) > 380 else 1.18)) if w._bg0.startswith("#") else None
        w.configure(cursor="hand2")
    def leave(e):
        w = e.widget
        if getattr(w, "_bg0", None): w.configure(bg=w._bg0); w._bg0 = None
    root.bind_class("Button", "<Enter>", enter, add="+")
    root.bind_class("Button", "<Leave>", leave, add="+")
    st = ttk.Style(root)
    try: st.theme_use("clam")
    except tk.TclError: pass
    st.configure("Treeview", rowheight=34, font=font(10), background="#FFFFFF", fieldbackground="#FFFFFF",
                 foreground=T["ink"], bordercolor=T["line"], borderwidth=0)
    st.configure("Treeview.Heading", font=font(10, True), background="#F3ECE0", foreground=T["ink"], relief="flat", padding=8)
    st.map("Treeview", background=[("selected", T["mustard_bg"])], foreground=[("selected", T["red"])])
    st.map("Treeview.Heading", background=[("active", "#E8DDCB")])
    st.configure("TNotebook", background=T["bg"], borderwidth=0)
    st.configure("TNotebook.Tab", font=font(10, True), padding=(16, 8), background="#F3ECE0")
    st.map("TNotebook.Tab", background=[("selected", T["surface"])], foreground=[("selected", T["red"])])
