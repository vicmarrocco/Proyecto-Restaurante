import sys
import tkinter as tk
from login_window import LoginWindow
from pos_window import POSWindow

class MostazaPOSApp:
    def __init__(self):
        self.root = tk.Tk()
        self.root.title("Mostaza POS · Terminal de Cajero")
        self.cajero_info = None

        self.mostrar_login()

    def mostrar_login(self):
        self.login_win = LoginWindow(self.root, on_login_success=self.on_login_success)

    def on_login_success(self, cajero_info):
        self.cajero_info = cajero_info
        self.mostrar_pos()

    def mostrar_pos(self):
        self.pos_win = POSWindow(self.root, self.cajero_info, on_logout=self.on_logout)

    def on_logout(self):
        self.cajero_info = None
        self.mostrar_login()

    def run(self):
        self.root.mainloop()

if __name__ == "__main__":
    app = MostazaPOSApp()
    app.run()
