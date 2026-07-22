import json
import time
import os
import re
import sys
import requests
import threading
import base64
from io import BytesIO
import tkinter as tk
from tkinter import ttk, messagebox, scrolledtext, filedialog
from PIL import Image, ImageTk

CONFIG_FILE = "config.json"

def get_resource_path(relative_path):
    try:
        base_path = sys._MEIPASS
    except Exception:
        base_path = os.path.abspath(".")
    return os.path.join(base_path, relative_path)

class ConfigManager:
    def __init__(self):
        self.config = {
            "api_token": "",
            "base_url": "https://api11.dichat.com.br",
            "file_path": "",
            "interval_sec": 5
        }
        self.load()

    def load(self):
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, "r", encoding="utf-8") as f:
                    data = json.load(f)
                    filtrado = {k: v for k, v in data.items() if k in self.config}
                    self.config.update(filtrado)
            except:
                pass
                
    def save(self, new_config=None):
        if new_config:
            self.config.update(new_config)
        try:
            with open(CONFIG_FILE, "w", encoding="utf-8") as f:
                json.dump(self.config, f, indent=4)
        except Exception:
            pass

class MonitorApp:
    def __init__(self, root):
        self.root = root
        self.root.title("Monitor WhatsApp")
        self.root.geometry("650x550")
        self.root.resizable(False, False)
        
        try:
            self.root.iconbitmap(get_resource_path("ConsysLogo.ico"))
        except:
            pass
        
        self.config_manager = ConfigManager()
        self.config = self.config_manager.config
        
        self.monitoring = False
        self.monitor_thread = None
        
        self.create_notebook()
        
        if self.config["api_token"] and self.config["file_path"]:
            self.root.after(1000, self.iniciar_monitoramento)
            self.root.after(1500, self.gerar_qr_code)

    def create_notebook(self):
        self.notebook = ttk.Notebook(self.root)
        self.notebook.pack(fill=tk.BOTH, expand=True, padx=10, pady=10)
        
        self.tab_main = ttk.Frame(self.notebook)
        self.tab_config = ttk.Frame(self.notebook)
        self.tab_qrcode = ttk.Frame(self.notebook)
        
        self.notebook.add(self.tab_main, text="Monitoramento")
        self.notebook.add(self.tab_config, text="Configurações")
        self.notebook.add(self.tab_qrcode, text="Conectar WhatsApp")
        
        self.build_tab_main()
        self.build_tab_config()
        self.build_tab_qrcode()

    def build_tab_main(self):
        frame_ctrl = ttk.Frame(self.tab_main)
        frame_ctrl.pack(fill=tk.X, padx=10, pady=10)

        self.btn_start = ttk.Button(frame_ctrl, text="▶ Iniciar", command=self.iniciar_monitoramento)
        self.btn_start.pack(side=tk.LEFT, padx=5)

        self.btn_stop = ttk.Button(frame_ctrl, text="⏹ Parar", command=self.parar_monitoramento, state=tk.DISABLED)
        self.btn_stop.pack(side=tk.LEFT, padx=5)
        
        self.lbl_status = ttk.Label(frame_ctrl, text="Situação: Parado", foreground="red")
        self.lbl_status.pack(side=tk.RIGHT, padx=5)

        frame_log = ttk.LabelFrame(self.tab_main, text="Logs de Atividade", padding=10)
        frame_log.pack(fill=tk.BOTH, expand=True, padx=10, pady=5)

        self.txt_log = scrolledtext.ScrolledText(frame_log, wrap=tk.WORD, height=15)
        self.txt_log.pack(fill=tk.BOTH, expand=True)
        self.log("Sistema pronto. O monitoramento automático iniciará se os dados estiverem preenchidos.")

    def log(self, mensagem):
        hora = time.strftime('%H:%M:%S')
        self.txt_log.insert(tk.END, f"[{hora}] {mensagem}\n")
        
        try:
            num_linhas = int(self.txt_log.index('end-1c').split('.')[0])
            if num_linhas > 300:
                self.txt_log.delete("1.0", f"{num_linhas - 300}.0")
        except:
            pass
            
        self.txt_log.see(tk.END)

    def build_tab_config(self):
        frame = ttk.Frame(self.tab_config, padding=10)
        frame.pack(fill=tk.BOTH, expand=True)
        
        ttk.Label(frame, text="Arquivo de Texto (.txt):").grid(row=0, column=0, sticky=tk.W, pady=5)
        self.ent_file = ttk.Entry(frame, width=50)
        self.ent_file.grid(row=0, column=1, pady=5, padx=5)
        self.ent_file.insert(0, self.config.get("file_path", ""))
        ttk.Button(frame, text="Procurar...", command=self.buscar_arquivo).grid(row=0, column=2, pady=5)
        
        ttk.Separator(frame, orient='horizontal').grid(row=1, column=0, columnspan=3, sticky="ew", pady=10)
        
        ttk.Label(frame, text="Token da API (Bearer):").grid(row=2, column=0, sticky=tk.W, pady=2)
        self.ent_api_token = ttk.Entry(frame, width=50)
        self.ent_api_token.grid(row=2, column=1, columnspan=2, pady=2, padx=5, sticky=tk.W)
        self.ent_api_token.insert(0, self.config["api_token"])

        ttk.Label(frame, text="URL Base da API:").grid(row=3, column=0, sticky=tk.W, pady=2)
        self.ent_base_url = ttk.Entry(frame, width=50)
        self.ent_base_url.grid(row=3, column=1, columnspan=2, pady=2, padx=5, sticky=tk.W)
        self.ent_base_url.insert(0, self.config["base_url"])

        ttk.Separator(frame, orient='horizontal').grid(row=4, column=0, columnspan=3, sticky="ew", pady=10)

        ttk.Label(frame, text="Intervalo de Checagem (seg):").grid(row=5, column=0, sticky=tk.W, pady=2)
        self.ent_intervalo = ttk.Entry(frame, width=20)
        self.ent_intervalo.grid(row=5, column=1, columnspan=2, pady=2, padx=5, sticky=tk.W)
        self.ent_intervalo.insert(0, str(self.config["interval_sec"]))

        btn_salvar = ttk.Button(frame, text="💾 Salvar Modificações", command=self.salvar_configs_tela)
        btn_salvar.grid(row=6, column=0, columnspan=3, pady=20)

    def buscar_arquivo(self):
        filePath = filedialog.askopenfilename(title="Selecione o Arquivo de Texto", filetypes=(("Text Files", "*.txt"),("All Files", "*.*")))
        if filePath:
            self.ent_file.delete(0, tk.END)
            self.ent_file.insert(0, filePath)

    def salvar_configs_tela(self):
        try:
            intervalo = int(self.ent_intervalo.get().strip())
        except:
            intervalo = 5
            
        dados = {
            "api_token": self.ent_api_token.get().strip(),
            "base_url": self.ent_base_url.get().strip(),
            "file_path": self.ent_file.get().strip(),
            "interval_sec": intervalo
        }
        self.config_manager.save(dados)
        self.config = self.config_manager.config
        messagebox.showinfo("Sucesso", "Configurações atualizadas!")
        self.log("Novas configurações foram salvas e aplicadas.")

    def build_tab_qrcode(self):
        frame = ttk.Frame(self.tab_qrcode, padding=10)
        frame.pack(fill=tk.BOTH, expand=True)

        self.lbl_qr_status = ttk.Label(frame, text="Aguardando checagem de status...", font=('Helvetica', 11, 'bold'))
        self.lbl_qr_status.pack(pady=10)

        self.btn_check_status = ttk.Button(frame, text="🔄 Verificar Conexão e Gerar QR Code", command=self.gerar_qr_code)
        self.btn_check_status.pack(pady=5)

        self.lbl_qr_image = ttk.Label(frame, text="[ A imagem do código QR aparecerá aqui ]", borderwidth=2, relief="groove")
        self.lbl_qr_image.pack(pady=15)

    def obter_headers_api(self):
        return {
            "Authorization": f"Bearer {self.config['api_token']}",
            "Content-Type": "application/json"
        }

    def montar_url_api(self, path):
        return self.config["base_url"].rstrip("/") + path

    def gerar_qr_code(self):
        if not self.config["api_token"]:
            messagebox.showerror("Erro", "Configure os tokens na aba 'Configurações' primeiro.")
            self.notebook.select(self.tab_config)
            return

        self.btn_check_status.config(state=tk.DISABLED, text="🔄 Verificando...")
        self.lbl_qr_status.config(text="Verificando instância...", foreground="blue")

        def task():
            for tentativa in range(1, 13):
                try:
                    conexao = self.obter_conexao()

                    if conexao is None:
                        self.root.after(0, self.atualizar_ui_qrcode_sucesso, "Token inválido ou conexão não encontrada na empresa.", "red")
                        return

                    if str(conexao.get("status", "")).upper() == "CONNECTED":
                        self.root.after(0, self.atualizar_ui_qrcode_sucesso, "✔️ SUCESSO! Instância Conectada e Pronta.", "green")
                        return

                    def update_status_lbl(t=tentativa):
                        self.lbl_qr_status.config(text=f"Gerando QR Code... (Tentativa {t}/12)", foreground="blue")
                    self.root.after(0, update_status_lbl)

                    base64_img = ""
                    resp_qr = requests.post(self.montar_url_api("/api/whatsappqrcodepro"), headers=self.obter_headers_api(), timeout=15)
                    if resp_qr.status_code == 200:
                        dados = resp_qr.json() if resp_qr.text else {}
                        base64_img = str((((dados.get("qrcode") or {}).get("data") or {}).get("data") or {}).get("QRCode") or "")

                    if base64_img:
                        if "base64," in base64_img:
                            base64_img = base64_img.split("base64,")[1]

                        img_data = base64.b64decode(base64_img)
                        image = Image.open(BytesIO(img_data))
                        image = image.resize((250, 250), Image.LANCZOS)
                        self.qr_photo = ImageTk.PhotoImage(image)

                        self.root.after(0, self.atualizar_ui_qrcode_img, f"❌ DESCONECTADO - Leia agora... (Tentativa {tentativa}/12)", "red", self.qr_photo)

                    if tentativa < 12:
                        time.sleep(5)

                except Exception as e:
                    self.root.after(0, self.atualizar_ui_qrcode_sucesso, f"Erro de Rede: {str(e)}", "red")
                    return

            self.root.after(0, self.atualizar_ui_qrcode_falha)

        threading.Thread(target=task, daemon=True).start()
        
    def atualizar_ui_qrcode_img(self, mensagem, cor, imagem_tk):
        self.lbl_qr_status.config(text=mensagem, foreground=cor)
        self.lbl_qr_image.config(image=imagem_tk, text="")
        self.notebook.select(self.tab_qrcode)

    def atualizar_ui_qrcode_sucesso(self, mensagem, cor):
        self.lbl_qr_status.config(text=mensagem, foreground=cor)
        self.lbl_qr_image.config(image='', text="[ Conectado - Sem necessidade de QR Code ]")
        self.btn_check_status.config(state=tk.NORMAL, text="🔄 Verificar Conexão e Gerar QR Code")

    def atualizar_ui_qrcode_falha(self):
        self.lbl_qr_status.config(text="⏳ Tempo excedido. Instância continua desconectada.", foreground="orange")
        self.lbl_qr_image.config(image='', text="[ QR Code Expirado ]")
        self.btn_check_status.config(state=tk.NORMAL, text="🔄 Tentar Novamente (Gerar NOVO QR Code)")

    def enviar_mensagem(self, telefone, mensagem):
        telefone_sanitizado = re.sub(r"\D", "", telefone)
        payload = {
            "number": telefone_sanitizado,
            "openTicket": "0",
            "queueId": "0",
            "body": mensagem
        }
        try:
            response = requests.post(self.montar_url_api("/api/messages/send"), json=payload, headers=self.obter_headers_api(), timeout=15)
            if response.status_code in (200, 201):
                return True
            else:
                self.root.after(0, self.log, f"   ✗ Erro da API na resposta: {response.text}")
                return False
        except Exception as e:
            self.root.after(0, self.log, f"   ✗ Falha de Conexão: {e}")
            return False

    def obter_conexao(self):
        try:
            resp_status = requests.post(self.montar_url_api("/api/whatsapp-status"), headers=self.obter_headers_api(), timeout=10)
            if resp_status.status_code != 200:
                return None
            for conexao in resp_status.json().get("whatsapps", []):
                if conexao.get("token") == self.config["api_token"]:
                    return conexao
            return None
        except:
            return None

    def verificar_conexao_ativa(self):
        conexao = self.obter_conexao()
        return conexao is not None and str(conexao.get("status", "")).upper() == "CONNECTED"

    def loop_monitoramento(self):
        contador_heartbeat = 0
        
        while self.monitoring:
            file_path = self.config["file_path"]
            
            contador_heartbeat += 1
            if contador_heartbeat >= 5:
                self.root.after(0, self.log, "⏳ Monitoramento ativo. Checando por novas mensagens...")
                contador_heartbeat = 0
            
            try:
                if os.path.exists(file_path) and os.path.getsize(file_path) > 0:
                    proc_path = file_path + ".processing"
                    
                    if os.path.exists(proc_path):
                        os.remove(proc_path)
                    os.rename(file_path, proc_path)
                    
                    with open(proc_path, "r", encoding="latin-1") as f:
                        linhas = f.readlines()
                    
                    if linhas:
                        conectado = self.verificar_conexao_ativa()
                        
                        if not conectado:
                            with open(file_path, "a", encoding="latin-1") as f:
                                for linha in linhas:
                                    f.write(linha)
                            self.root.after(0, self.log, "⚠️ API DESCONECTADA! Envio pausado e fila mantida intacta.")
                        else:
                            linhas_falhas = []
                            for idx, linha in enumerate(linhas):
                                if not self.monitoring:
                                    linhas_falhas.extend(linhas[idx:])
                                    break
                                
                                if not linha.strip():
                                    continue
                                
                                partes = linha.strip('\n').split('|||')
                                if len(partes) >= 2:
                                    telefone = partes[0].strip()
                                    texto = partes[1].replace('\\n', '\n')
                                    
                                    self.root.after(0, self.log, f"► POST API para {telefone}...")
                                    enviado = self.enviar_mensagem(telefone, texto)
                                    
                                    if enviado:
                                        self.root.after(0, self.log, f"   ✓ Mensagem enviada com sucesso.")
                                    else:
                                        linhas_falhas.append(linha)
                                else:
                                    self.root.after(0, self.log, f"⚠️ Linha ignorada por formato inválido: {linha[:20]}...")
                                    
                            if linhas_falhas:
                                with open(file_path, "a", encoding="latin-1") as f:
                                    for lf in linhas_falhas:
                                        f.write(lf)
                    
                    if os.path.exists(proc_path):
                        os.remove(proc_path)
                        
            except Exception as e:
                self.root.after(0, self.log, f"❌ ERRO no processamento: {str(e)}")
                try:
                    if os.path.exists(proc_path):
                        with open(proc_path, "r", encoding="latin-1") as f_proc:
                            dados_perdidos = f_proc.readlines()
                        with open(file_path, "a", encoding="latin-1") as f_orig:
                            for dp in dados_perdidos:
                                f_orig.write(dp)
                        os.remove(proc_path)
                        self.root.after(0, self.log, "   ✓ Recuperação de fila executada com segurança.")
                except Exception as ex_rec:
                    self.root.after(0, self.log, f"❌ ERRO ao tentar restaurar fila: {str(ex_rec)}")
            
            intervalo = self.config["interval_sec"]
            for _ in range(int(intervalo * 2)):
                if not self.monitoring:
                    break
                time.sleep(0.5)

    def iniciar_monitoramento(self):
        if not self.config.get("api_token") or not self.config.get("file_path"):
            self.log("⚠️ ATENÇÃO: Preencha as configurações e salve antes de iniciar.")
            self.notebook.select(self.tab_config)
            return

        self.monitoring = True
        self.btn_start.config(state=tk.DISABLED)
        self.btn_stop.config(state=tk.NORMAL)
        self.lbl_status.config(text=f"Situação: Monitorando ({self.config['interval_sec']}s)", foreground="green")
        
        self.monitor_thread = threading.Thread(target=self.loop_monitoramento, daemon=True)
        self.monitor_thread.start()

    def parar_monitoramento(self):
        self.monitoring = False
        self.btn_start.config(state=tk.NORMAL)
        self.btn_stop.config(state=tk.DISABLED)
        self.lbl_status.config(text="Situação: Parado", foreground="red")
        self.log("Monitoramento Pausado.")

if __name__ == "__main__":
    root = tk.Tk()
    app = MonitorApp(root)
    root.mainloop()