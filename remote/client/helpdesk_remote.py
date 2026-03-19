"""
HelpDesk Remote — Client Agent v4.0
====================================
Executa na bandeja do sistema (system tray) do Windows.
Auto-conecta ao servidor configurado.
Mostra código de acesso no ícone de notificação.

v4.0 — DXGI Desktop Duplication API (captura direto da GPU via dxcam)
     — Delta Encoding: só envia regiões que mudaram (economia de 60-95% de banda)
     — Protocolo binário: full frame (JPEG) ou delta frame (header + JPEG região)
     — Canvas-based viewer com renderização parcial
     — Fallback automático: dxcam → mss (GDI)

v3.1 — Zero-copy BGRA pipeline (mss.raw + numpy + turbojpeg BGRX)
v3.0 — Conexão direta sem aprovação, auto-reconexão, qualidade adaptativa

Para desenvolvimento:
    pip install -r requirements.txt
    python helpdesk_remote.py

Para compilar:
    build.bat
"""

import sys
import os
import json
import time
import threading
import logging
import ctypes
import ctypes.wintypes
import hashlib
import io
import base64
import socket
import platform
import subprocess
import shutil
import struct
import zlib
from collections import deque

IS_FROZEN = getattr(sys, 'frozen', False)
APP_NAME = "HelpDesk Remote"
EXE_NAME = "HelpDesk_Remote.exe"
INSTALL_DIR = os.path.join(os.environ.get('LOCALAPPDATA', os.path.expanduser('~')), 'HelpDeskRemote')

# ==========================================
#  Dependências
# ==========================================
try:
    import pystray
    from PIL import Image, ImageDraw, ImageFont
    import mss
    import websocket
    import pyautogui
    import pyperclip
except ImportError as e:
    if IS_FROZEN:
        ctypes.windll.user32.MessageBoxW(0, f"Erro ao iniciar:\n{e}", APP_NAME, 0x10)
    else:
        print(f"[ERRO] {e}\nExecute: pip install -r requirements.txt")
    sys.exit(1)

# TurboJPEG (libjpeg-turbo) — 10-50x mais rápido que PIL para JPEG
_turbojpeg = None
try:
    from turbojpeg import TurboJPEG, TJPF_RGB, TJPF_BGRX, TJSAMP_420, TJFLAG_FASTDCT, TJFLAG_FASTUPSAMPLE

    # Encontrar turbojpeg.dll bundled com o executável
    _tj_lib_path = None
    _tj_search_paths = []

    # 1. Diretório do executável (PyInstaller --onefile extrai para _MEIPASS)
    if IS_FROZEN:
        _tj_search_paths.append(os.path.join(sys._MEIPASS, 'turbojpeg.dll'))
        _tj_search_paths.append(os.path.join(os.path.dirname(sys.executable), 'turbojpeg.dll'))
    # 2. Diretório do script
    _tj_search_paths.append(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'turbojpeg.dll'))
    # 3. Diretório de instalação
    _tj_search_paths.append(os.path.join(INSTALL_DIR, 'turbojpeg.dll'))
    # 4. Locais padrão do libjpeg-turbo no Windows
    _tj_search_paths.append(r'C:\libjpeg-turbo64\bin\turbojpeg.dll')
    _tj_search_paths.append(r'C:\Program Files\libjpeg-turbo64\bin\turbojpeg.dll')

    for _p in _tj_search_paths:
        if os.path.isfile(_p):
            _tj_lib_path = _p
            break

    if _tj_lib_path:
        _turbojpeg = TurboJPEG(lib_path=_tj_lib_path)
        logging.getLogger('HelpDeskRemote').info(f"TurboJPEG OK — {_tj_lib_path}")
    else:
        # Tentar auto-detect (pode funcionar se estiver no PATH do sistema)
        _turbojpeg = TurboJPEG()
        logging.getLogger('HelpDeskRemote').info("TurboJPEG OK — auto-detect")
except ImportError:
    logging.getLogger('HelpDeskRemote').info("TurboJPEG não disponível — usando PIL")
except Exception as _e:
    logging.getLogger('HelpDeskRemote').info(f"TurboJPEG falhou ({_e}) — usando PIL")

# numpy para dirty rectangle detection
_numpy = None
try:
    import numpy as np
    _numpy = np
except ImportError:
    pass

# dxcam para DXGI Desktop Duplication API (captura direto da GPU)
_dxcam = None
try:
    import dxcam
    _dxcam = dxcam
    logging.getLogger('HelpDeskRemote').info("dxcam (DXGI) disponível")
except ImportError:
    logging.getLogger('HelpDeskRemote').info("dxcam não disponível — usando mss (GDI)")
except Exception as _e:
    logging.getLogger('HelpDeskRemote').info(f"dxcam falhou ({_e}) — usando mss (GDI)")

pyautogui.FAILSAFE = False
pyautogui.PAUSE = 0

# ==========================================
#  Logging
# ==========================================
os.makedirs(INSTALL_DIR, exist_ok=True)
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(levelname)s: %(message)s',
    handlers=[
        logging.FileHandler(os.path.join(INSTALL_DIR, 'client.log'), encoding='utf-8'),
        logging.StreamHandler(),
    ]
)
log = logging.getLogger('HelpDeskRemote')


# ==========================================
#  Self-Install + AutoStart
# ==========================================

def get_exe_dir():
    if IS_FROZEN:
        return os.path.dirname(sys.executable)
    return os.path.dirname(os.path.abspath(__file__))


def is_installed():
    if not IS_FROZEN:
        return True
    current = os.path.normpath(sys.executable).lower()
    installed = os.path.normpath(os.path.join(INSTALL_DIR, EXE_NAME)).lower()
    return current == installed


def do_install():
    """Copia o EXE + config para LOCALAPPDATA e configura autostart"""
    try:
        os.makedirs(INSTALL_DIR, exist_ok=True)

        src_exe = sys.executable
        dst_exe = os.path.join(INSTALL_DIR, EXE_NAME)

        if os.path.normpath(src_exe).lower() != os.path.normpath(dst_exe).lower():
            shutil.copy2(src_exe, dst_exe)

        # Copiar config.json
        src_cfg = os.path.join(get_exe_dir(), 'config.json')
        dst_cfg = os.path.join(INSTALL_DIR, 'config.json')
        if os.path.exists(src_cfg):
            shutil.copy2(src_cfg, dst_cfg)

        set_autostart(dst_exe)
        log.info(f"Instalado em: {INSTALL_DIR}")

        ctypes.windll.user32.MessageBoxW(
            0,
            f"{APP_NAME} instalado com sucesso!\n\n"
            f"Local: {INSTALL_DIR}\n\n"
            u"• Iniciará automaticamente com o Windows\n"
            u"• O ícone aparecerá na bandeja do sistema\n"
            u"  (clique \u25B2 ao lado do relógio se não aparecer)",
            f"{APP_NAME} \u2014 Instalação",
            0x40
        )

        subprocess.Popen([dst_exe], cwd=INSTALL_DIR)
        sys.exit(0)

    except Exception as e:
        log.error(f"Erro na instalação: {e}")
        ctypes.windll.user32.MessageBoxW(0, f"Erro na instalação:\n{e}", APP_NAME, 0x10)


def set_autostart(exe_path):
    try:
        import winreg
        key = winreg.OpenKey(
            winreg.HKEY_CURRENT_USER,
            r"Software\Microsoft\Windows\CurrentVersion\Run",
            0, winreg.KEY_SET_VALUE
        )
        winreg.SetValueEx(key, "HelpDeskRemote", 0, winreg.REG_SZ, f'"{exe_path}"')
        winreg.CloseKey(key)
        log.info("Autostart configurado no registro")
    except Exception as e:
        log.error(f"Erro autostart: {e}")


def do_uninstall():
    try:
        import winreg
        key = winreg.OpenKey(
            winreg.HKEY_CURRENT_USER,
            r"Software\Microsoft\Windows\CurrentVersion\Run",
            0, winreg.KEY_SET_VALUE
        )
        try:
            winreg.DeleteValue(key, "HelpDeskRemote")
        except FileNotFoundError:
            pass
        winreg.CloseKey(key)
    except Exception:
        pass


# ==========================================
#  Config
# ==========================================

class Config:
    def __init__(self):
        self.server_url = ''
        self.quality = 40          # JPEG quality (1-100) — lower = faster
        self.fps = 30              # Target FPS
        self.scale = 0.5           # Scale factor (0.3-1.0)
        self.auto_start = True
        self.require_approval = False  # v3.0: desabilitado por padrão
        self.adaptive_quality = True   # v3.0: qualidade adaptativa
        self.min_quality = 15          # v3.0: qualidade mínima
        self.max_quality = 70          # v3.0: qualidade máxima
        self._load()

    def _load(self):
        paths = [
            os.path.join(get_exe_dir(), 'config.json'),
            os.path.join(INSTALL_DIR, 'config.json'),
        ]
        for path in paths:
            if os.path.exists(path):
                try:
                    with open(path, 'r', encoding='utf-8') as f:
                        d = json.load(f)
                    self.server_url = d.get('server_url', self.server_url)
                    self.quality = int(d.get('quality', self.quality))
                    self.fps = int(d.get('fps', self.fps))
                    self.scale = float(d.get('scale', self.scale))
                    self.auto_start = bool(d.get('auto_start', self.auto_start))
                    self.require_approval = bool(d.get('require_approval', self.require_approval))
                    self.adaptive_quality = bool(d.get('adaptive_quality', self.adaptive_quality))
                    self.min_quality = int(d.get('min_quality', self.min_quality))
                    self.max_quality = int(d.get('max_quality', self.max_quality))
                    log.info(f"Config lido de: {path}")
                    return
                except Exception as e:
                    log.warning(f"Erro ao ler config {path}: {e}")

    def save(self):
        path = os.path.join(INSTALL_DIR, 'config.json')
        os.makedirs(os.path.dirname(path), exist_ok=True)
        with open(path, 'w', encoding='utf-8') as f:
            json.dump({
                'server_url': self.server_url,
                'quality': self.quality,
                'fps': self.fps,
                'scale': self.scale,
                'auto_start': self.auto_start,
                'require_approval': self.require_approval,
                'adaptive_quality': self.adaptive_quality,
                'min_quality': self.min_quality,
                'max_quality': self.max_quality,
            }, f, indent=2)


# ==========================================
#  Remote Client (lógica de conexão)
# ==========================================

class RemoteClient:
    def __init__(self, config: Config):
        self.cfg = config
        self.tray = None

        self.ws = None
        self.connected = False
        self.streaming = False
        self.code = ''
        self.viewer_connected = False
        self.approved = False
        self._should_reconnect = True  # v3.0: auto-reconnect flag

        # Performance v4.0
        self._current_quality = config.quality
        self._send_queue = deque(maxlen=2) # Max 2 frames enfileirados (drop old)
        self._send_event = threading.Event()
        self._send_thread = None
        self._frame_times = deque(maxlen=30)
        self._prev_frame = None           # v4.0: frame anterior para delta

        self.last_frame_hash = None
        self.last_clipboard = ''
        self._receiving_files = {}

    # ----- Informações do sistema -----

    def get_system_info(self):
        hostname = socket.gethostname()
        username = os.environ.get('USERNAME', os.environ.get('USER', 'unknown'))
        try:
            s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
            s.connect(("8.8.8.8", 80))
            ip_local = s.getsockname()[0]
            s.close()
        except Exception:
            ip_local = '0.0.0.0'
        try:
            ip = socket.gethostbyname(hostname)
        except Exception:
            ip = ip_local

        os_info = f"{platform.system()} {platform.release()} {platform.machine()}"

        try:
            with mss.mss() as sct:
                mon = sct.monitors[1]
                resolution = [mon['width'], mon['height']]
        except Exception:
            resolution = [1920, 1080]

        return {
            'hostname': hostname,
            'username': username,
            'ip': ip,
            'ip_local': ip_local,
            'os': os_info,
            'resolution': resolution,
        }

    # ----- WebSocket -----

    def connect(self, url=None):
        url = url or self.cfg.server_url
        if not url:
            log.warning("URL do servidor não configurada")
            return

        if not url.startswith('ws://') and not url.startswith('wss://'):
            url = 'ws://' + url
        self.cfg.server_url = url

        def on_open(ws):
            log.info(f"Conectado a {url}")
            self.connected = True
            info = self.get_system_info()
            ws.send(json.dumps({'type': 'register', **info}))
            if self.tray:
                self.tray.update_state('connecting', 'Registrando...')

        def on_message(ws, message):
            if isinstance(message, bytes):
                return
            try:
                self.handle_message(json.loads(message))
            except Exception:
                pass

        def on_error(ws, error):
            log.error(f"WS error: {error}")

        def on_close(ws, code, msg):
            log.info(f"Desconectado do servidor (code={code}, msg={msg})")
            was_connected = self.connected
            self.connected = False
            self.viewer_connected = False
            self.streaming = False
            self.approved = False
            self.code = ''
            self._prev_frame = None
            if self.tray:
                self.tray.update_state('disconnected', 'Desconectado')
                self.tray.set_code('')
            # v3.0: Auto-reconexão será gerenciada pelo loop do TrayApp

        try:
            # Fechar websocket anterior se existir
            if self.ws:
                try:
                    self.ws.close()
                except Exception:
                    pass
                self.ws = None

            self.ws = websocket.WebSocketApp(
                url,
                on_open=on_open,
                on_message=on_message,
                on_error=on_error,
                on_close=on_close,
            )
            t = threading.Thread(target=self.ws.run_forever,
                                 kwargs={'ping_interval': 15, 'ping_timeout': 10},
                                 daemon=True)
            t.start()
        except Exception as e:
            log.error(f"Falha ao conectar: {e}")

    def disconnect(self):
        self._should_reconnect = False
        self.streaming = False
        self.viewer_connected = False
        self.approved = False
        if self.ws:
            try:
                self.ws.send(json.dumps({'type': 'disconnect'}))
            except Exception:
                pass
            try:
                self.ws.close()
            except Exception:
                pass
        self.ws = None
        self.connected = False
        self.code = ''

    def send_json(self, data):
        if self.ws and self.connected:
            try:
                self.ws.send(json.dumps(data))
            except Exception:
                pass

    def send_binary(self, data):
        if self.ws and self.connected:
            try:
                self.ws.send(data, opcode=websocket.ABNF.OPCODE_BINARY)
            except Exception:
                pass

    # ----- Mensagens -----

    def handle_message(self, data):
        t = data.get('type', '')

        if t == 'registered':
            self.code = data.get('code', '')
            log.info(f"Código: {self.code}")
            if self.tray:
                self.tray.set_code(self.code)
                self.tray.update_state('online', 'Online \u2014 Aguardando técnico')
                self.tray.notify("Código de Acesso",
                                 f"Seu código é: {self.code}\nInforme ao técnico de suporte.")

        elif t == 'viewer_connected':
            tecnico = data.get('tecnico', 'Técnico')
            log.info(f"Viewer conectou: {tecnico}")

            # v3.0: Conexão direta sem aprovação (ou com aprovação se configurado)
            if self.cfg.require_approval:
                threading.Thread(target=self._ask_approval, args=(tecnico,), daemon=True).start()
            else:
                # Entrar direto
                self.approved = True
                self.viewer_connected = True
                self.send_json({'type': 'approval', 'approved': True})
                self._start_streaming()
                if self.tray:
                    self.tray.update_state('active', f'Conectado \u2014 {tecnico}')
                    self.tray.notify("Acesso Remoto",
                                     f"Técnico {tecnico} conectado.")

        elif t == 'mouse':
            # v3.0: Sempre processar mouse (sem check de approval quando approval desabilitado)
            if self.viewer_connected:
                self._handle_mouse(data)

        elif t == 'key':
            if self.viewer_connected:
                self._handle_key(data)

        elif t == 'clipboard':
            if self.viewer_connected:
                text = data.get('text', '')
                if text:
                    try:
                        pyperclip.copy(text)
                    except Exception:
                        pass

        elif t == 'settings':
            self.cfg.quality = int(data.get('quality', self.cfg.quality))
            self.cfg.fps = int(data.get('fps', self.cfg.fps))
            self.cfg.scale = float(data.get('scale', self.cfg.scale))
            self._current_quality = self.cfg.quality  # Reset adaptive quality
            log.info(f"Settings atualizados: quality={self.cfg.quality} fps={self.cfg.fps} scale={self.cfg.scale}")

        elif t == 'file_info':
            fid = data.get('id', '')
            self._receiving_files[fid] = {
                'name': data.get('name', 'file'), 'size': data.get('size', 0),
                'total': data.get('total_chunks', 0), 'chunks': {},
            }

        elif t == 'file_chunk':
            fid = data.get('id', '')
            if fid in self._receiving_files:
                self._receiving_files[fid]['chunks'][data.get('index', 0)] = \
                    base64.b64decode(data.get('data', ''))

        elif t == 'file_complete':
            fid = data.get('id', '')
            if fid in self._receiving_files:
                fi = self._receiving_files.pop(fid)
                self._save_received_file(fi)

        elif t == 'disconnected':
            # v3.0: Viewer desconectou — parar streaming, limpar estado, mas manter conexão
            log.info("Viewer desconectou — voltando ao modo aguardando")
            self.viewer_connected = False
            self.streaming = False
            self.approved = False
            self._prev_frame = None
            self._current_quality = self.cfg.quality
            if self.tray:
                self.tray.update_state('online', 'Online \u2014 Aguardando técnico')
                self.tray.notify("Sessão Encerrada", "O técnico se desconectou.")

    def _ask_approval(self, tecnico):
        """Aprovação com MessageBox — só usado se require_approval=True"""
        MB_YESNO = 0x04
        MB_ICONQUESTION = 0x20
        MB_TOPMOST = 0x40000
        MB_SETFOREGROUND = 0x10000
        IDYES = 6

        result = ctypes.windll.user32.MessageBoxW(
            0,
            f"O técnico '{tecnico}' deseja acessar seu computador.\n\n"
            "Deseja permitir o acesso remoto?",
            f"{APP_NAME} \u2014 Solicitação de Acesso",
            MB_YESNO | MB_ICONQUESTION | MB_TOPMOST | MB_SETFOREGROUND
        )

        if result == IDYES:
            self.approved = True
            self.viewer_connected = True
            self.send_json({'type': 'approval', 'approved': True})
            self._start_streaming()
            if self.tray:
                self.tray.update_state('active', f'Conectado \u2014 {tecnico}')
                self.tray.notify("Acesso Remoto Ativo",
                                 f"O técnico {tecnico} está acessando seu computador.")
        else:
            self.send_json({'type': 'approval', 'approved': False})

    # ----- Streaming de tela (v4.0 — DXGI + Delta Encoding) -----

    def _start_streaming(self):
        if self.streaming:
            return
        self.streaming = True
        self._prev_frame = None
        self._current_quality = self.cfg.quality
        self._frame_times.clear()
        self._send_queue.clear()

        # Thread dedicada para envio (não bloqueia captura)
        self._send_event.clear()
        self._send_thread = threading.Thread(target=self._send_loop, daemon=True)
        self._send_thread.start()

        threading.Thread(target=self._streaming_loop, daemon=True).start()
        threading.Thread(target=self._clipboard_loop, daemon=True).start()

    def _send_loop(self):
        """Thread dedicada para envio de frames — não bloqueia captura"""
        while self.streaming and self.connected:
            self._send_event.wait(timeout=0.5)
            self._send_event.clear()
            while self._send_queue:
                try:
                    frame_data = self._send_queue.popleft()
                except IndexError:
                    break
                if self.ws and self.connected:
                    try:
                        self.ws.send(frame_data, opcode=websocket.ABNF.OPCODE_BINARY)
                    except Exception:
                        break

    def _queue_frame(self, jpeg_data):
        """Enfileira frame para envio assíncrono (drop frames antigos)"""
        self._send_queue.append(jpeg_data)
        self._send_event.set()

    def _streaming_loop(self):
        """
        Pipeline de captura v4.0 — DXGI + Delta Encoding
        =================================================
        ANTES (v3.1): mss.grab → .raw BGRA → numpy → turbojpeg → JPEG full frame
                       Total: ~10-40ms | Bandwidth: JPEG inteiro a cada frame

        AGORA (v4.0):
          1. Captura: dxcam (DXGI Desktop Duplication = GPU direto, <1ms)
             Fallback: mss (GDI, ~2-5ms)
          2. Delta: Detecta bounding box de mudanças via numpy subsampling
             Só codifica e envia a região que mudou
          3. Protocolo: Full JPEG (0xFF 0xD8) ou Delta (0x44 0x45 + header + JPEG região)
             Economia de banda: 60-95% em uso normal (só cursor/texto muda)
        """
        use_np = _numpy is not None
        use_tj = _turbojpeg is not None
        scale = self.cfg.scale

        # ====== DXGI Desktop Duplication (via dxcam) ======
        camera = None
        use_dxgi = False
        if _dxcam is not None and use_np:
            try:
                camera = _dxcam.create(output_color="BGRA")
                test_frame = camera.grab()
                if test_frame is not None:
                    use_dxgi = True
                    log.info(f"DXGI capture OK ({test_frame.shape[1]}x{test_frame.shape[0]})")
                else:
                    log.info("DXGI grab retornou None, tentando novamente...")
                    import time as _t; _t.sleep(0.1)
                    test_frame = camera.grab()
                    if test_frame is not None:
                        use_dxgi = True
                        log.info(f"DXGI capture OK retry ({test_frame.shape[1]}x{test_frame.shape[0]})")
                    else:
                        camera = None
            except Exception as e:
                log.info(f"DXGI falhou: {e} — usando mss (GDI)")
                camera = None

        # ====== Fallback: mss (GDI) ======
        sct = None
        monitor = None
        if not use_dxgi:
            sct = mss.mss()
            monitor = sct.monitors[1]

        log.info(f"Streaming v4.0 (dxgi={use_dxgi}, turbojpeg={use_tj}, numpy={use_np}, "
                 f"quality={self._current_quality}, fps={self.cfg.fps}, scale={scale})")

        frame_interval = 1.0 / max(1, self.cfg.fps)
        force_full_interval = 3.0  # Forçar full frame a cada 3s
        last_full_time = 0
        frames_sent = 0
        full_frames = 0
        delta_frames = 0
        bytes_saved = 0
        perf_log_time = time.time()
        prev_frame = None  # Frame anterior (scaled) para delta

        # Delta protocol header: "DE" + fw(u16) + fh(u16) + rx(u16) + ry(u16) + rw(u16) + rh(u16)
        DELTA_MAGIC = b'DE'
        HEADER_SIZE = 14  # 2 + 2+2 + 2+2+2+2

        while self.streaming and self.connected and self.viewer_connected:
            try:
                t0 = time.time()

                # ====== CAPTURA ======
                if use_dxgi:
                    raw_frame = camera.grab()
                    if raw_frame is None:
                        # DXGI retorna None se tela não mudou (desktop idle)
                        time.sleep(0.005)
                        continue
                    # dxcam retorna numpy (H, W, 4) BGRA
                    sh, sw = raw_frame.shape[:2]
                else:
                    screenshot = sct.grab(monitor)
                    sw, sh = screenshot.width, screenshot.height
                    if use_np:
                        raw_frame = _numpy.frombuffer(screenshot.raw, dtype=_numpy.uint8).reshape(sh, sw, 4)
                    else:
                        raw_frame = None

                # ====== SCALE (numpy slicing = instantâneo) ======
                if use_np and raw_frame is not None:
                    if scale < 1.0:
                        step = max(1, round(1.0 / scale))
                        frame = raw_frame[::step, ::step]
                    else:
                        frame = raw_frame
                    fh, fw = frame.shape[:2]
                else:
                    frame = None
                    fh, fw = sh, sw

                quality = self._current_quality
                force_full = (t0 - last_full_time) > force_full_interval
                send_delta = False
                region_jpeg = None
                rx = ry = rw = rh = 0

                # ====== DELTA DETECTION (numpy) ======
                if use_np and frame is not None and prev_frame is not None \
                        and not force_full and prev_frame.shape == frame.shape:
                    # Subsample: comparar 1 pixel a cada 4 (4x mais rápido)
                    sub_c = frame[::4, ::4, :3]
                    sub_p = prev_frame[::4, ::4, :3]

                    if _numpy.array_equal(sub_c, sub_p):
                        # Tela não mudou — dormir e continuar
                        elapsed = time.time() - t0
                        time.sleep(max(0.002, frame_interval - elapsed))
                        continue

                    # Encontrar bounding box das mudanças
                    diff = _numpy.any(sub_c != sub_p, axis=2)
                    rows_any = _numpy.any(diff, axis=1)
                    cols_any = _numpy.any(diff, axis=0)

                    if _numpy.any(rows_any):
                        y_idx = _numpy.where(rows_any)[0]
                        x_idx = _numpy.where(cols_any)[0]

                        # Mapear de subsampled (::4) para coordenadas reais
                        margin = 16  # Margem para evitar artefatos
                        ry = max(0, int(y_idx[0]) * 4 - margin)
                        ey = min(fh, int(y_idx[-1]) * 4 + 4 + margin)
                        rx = max(0, int(x_idx[0]) * 4 - margin)
                        ex = min(fw, int(x_idx[-1]) * 4 + 4 + margin)

                        rw = ex - rx
                        rh = ey - ry
                        region_area = rw * rh
                        total_area = fw * fh

                        if region_area < total_area * 0.55:
                            # Região menor que 55% da tela — enviar delta
                            region = _numpy.ascontiguousarray(frame[ry:ey, rx:ex])

                            if use_tj:
                                region_jpeg = _turbojpeg.encode(
                                    region, quality=quality,
                                    pixel_format=TJPF_BGRX,
                                    jpeg_subsample=TJSAMP_420,
                                    flags=TJFLAG_FASTDCT
                                )
                            else:
                                rgb_r = region[:, :, 2::-1]
                                img_r = Image.fromarray(rgb_r)
                                buf = io.BytesIO()
                                img_r.save(buf, format='JPEG', quality=quality,
                                           optimize=False, subsampling=2)
                                region_jpeg = buf.getvalue()

                            send_delta = True

                # ====== ENVIO ======
                t_enc = time.time()

                if send_delta and region_jpeg is not None:
                    # Delta frame: header + JPEG da região
                    header = struct.pack('<2sHHHHHH',
                                        DELTA_MAGIC, fw, fh, rx, ry, rw, rh)
                    frame_data = header + bytes(region_jpeg)
                    self._queue_frame(frame_data)

                    # Calcular economia de banda
                    if use_tj:
                        est_full = len(region_jpeg) * (fw * fh) // max(1, rw * rh)
                    else:
                        est_full = len(region_jpeg) * 3
                    bytes_saved += max(0, est_full - len(frame_data))

                    delta_frames += 1
                    frames_sent += 1
                    prev_frame = frame.copy()

                else:
                    # Full frame
                    if use_np and frame is not None:
                        if use_tj:
                            arr = _numpy.ascontiguousarray(frame)
                            jpeg_data = _turbojpeg.encode(
                                arr, quality=quality,
                                pixel_format=TJPF_BGRX,
                                jpeg_subsample=TJSAMP_420,
                                flags=TJFLAG_FASTDCT
                            )
                        else:
                            rgb = frame[:, :, 2::-1]
                            img = Image.fromarray(rgb)
                            buf = io.BytesIO()
                            img.save(buf, format='JPEG', quality=quality,
                                     optimize=False, subsampling=2)
                            jpeg_data = buf.getvalue()
                    else:
                        # Sem numpy: fallback PIL completo
                        raw_rgb = screenshot.rgb
                        img = Image.frombytes('RGB', (sw, sh), raw_rgb)
                        if scale < 1.0:
                            new_w = int(sw * scale)
                            new_h = int(sh * scale)
                            img = img.resize((new_w, new_h), Image.BILINEAR)
                        buf = io.BytesIO()
                        img.save(buf, format='JPEG', quality=quality,
                                 optimize=False, subsampling=2)
                        jpeg_data = buf.getvalue()

                    jpeg_data = bytes(jpeg_data)
                    self._queue_frame(jpeg_data)
                    full_frames += 1
                    frames_sent += 1
                    last_full_time = t0
                    if use_np and frame is not None:
                        prev_frame = frame.copy()

                encode_ms = (time.time() - t_enc) * 1000

                # ====== QUALIDADE ADAPTATIVA ======
                if self.cfg.adaptive_quality:
                    target_ms = (1000.0 / max(1, self.cfg.fps)) * 0.5
                    if encode_ms > target_ms * 1.5:
                        self._current_quality = max(self.cfg.min_quality,
                                                    self._current_quality - 3)
                    elif encode_ms < target_ms * 0.3:
                        self._current_quality = min(self.cfg.max_quality,
                                                    self._current_quality + 1)

                # Recalcular interval
                frame_interval = 1.0 / max(1, self.cfg.fps)

                # Timing
                elapsed = time.time() - t0
                self._frame_times.append(elapsed)
                sleep_time = max(0, frame_interval - elapsed)
                if sleep_time > 0:
                    time.sleep(sleep_time)

                # Log de performance a cada 10s
                if time.time() - perf_log_time > 10:
                    avg_ms = sum(self._frame_times) / max(1, len(self._frame_times)) * 1000
                    real_fps = 1000.0 / max(1, avg_ms)
                    delta_pct = delta_frames / max(1, frames_sent) * 100
                    saved_kb = bytes_saved // 1024
                    log.info(f"Perf: {real_fps:.0f} FPS ({delta_pct:.0f}% delta), "
                             f"encode={encode_ms:.0f}ms, q={self._current_quality}, "
                             f"full={full_frames} delta={delta_frames} saved={saved_kb}KB "
                             f"[{'DXGI' if use_dxgi else 'GDI'}]")
                    perf_log_time = time.time()

            except Exception as e:
                log.error(f"Streaming: {e}")
                time.sleep(0.1)

        # Cleanup
        if camera:
            try:
                del camera
            except Exception:
                pass
        if sct:
            try:
                sct.close()
            except Exception:
                pass

        log.info(f"Streaming encerrado ({frames_sent} frames: {full_frames} full + {delta_frames} delta)")

    def _clipboard_loop(self):
        while self.streaming and self.connected and self.viewer_connected:
            try:
                cur = pyperclip.paste()
                if cur and cur != self.last_clipboard:
                    self.last_clipboard = cur
                    self.send_json({'type': 'clipboard', 'text': cur})
            except Exception:
                pass
            time.sleep(1)

    # ----- Input remoto -----

    def _handle_mouse(self, d):
        try:
            x, y = int(d.get('x', 0)), int(d.get('y', 0))
            act = d.get('action', 'move')
            btn = d.get('button', 'left')

            if act == 'move':
                try:
                    pyautogui.moveTo(x, y, _pause=False)
                except Exception:
                    # Fallback: win32 API
                    ctypes.windll.user32.SetCursorPos(x, y)
            elif act == 'click':
                try:
                    pyautogui.click(x, y, button=btn, _pause=False)
                except Exception:
                    self._win32_click(x, y, btn)
            elif act == 'dblclick':
                try:
                    pyautogui.doubleClick(x, y, button=btn, _pause=False)
                except Exception:
                    self._win32_click(x, y, btn)
                    time.sleep(0.05)
                    self._win32_click(x, y, btn)
            elif act == 'rightclick':
                try:
                    pyautogui.rightClick(x, y, _pause=False)
                except Exception:
                    self._win32_click(x, y, 'right')
            elif act == 'mousedown':
                pyautogui.mouseDown(x, y, button=btn, _pause=False)
            elif act == 'mouseup':
                pyautogui.mouseUp(x, y, button=btn, _pause=False)
            elif act == 'scroll':
                delta = int(d.get('delta', 0))
                pyautogui.scroll(delta, x, y, _pause=False)
        except Exception as e:
            log.debug(f"Mouse error: {e}")

    def _win32_click(self, x, y, button='left'):
        """Fallback mouse click via Win32 API"""
        try:
            ctypes.windll.user32.SetCursorPos(x, y)
            if button == 'right':
                ctypes.windll.user32.mouse_event(0x0008, 0, 0, 0, 0)  # RIGHTDOWN
                ctypes.windll.user32.mouse_event(0x0010, 0, 0, 0, 0)  # RIGHTUP
            else:
                ctypes.windll.user32.mouse_event(0x0002, 0, 0, 0, 0)  # LEFTDOWN
                ctypes.windll.user32.mouse_event(0x0004, 0, 0, 0, 0)  # LEFTUP
        except Exception as e:
            log.debug(f"Win32 click error: {e}")

    def _handle_key(self, d):
        try:
            key = d.get('key', '')
            act = d.get('action', 'press')
            log.debug(f"Key: {key} action={act}")
            if act == 'hotkey':
                pyautogui.hotkey(*key.split('+'), _pause=False)
            elif act == 'press':
                pyautogui.press(key, _pause=False)
            elif act == 'type':
                pyautogui.write(key, interval=0.02)
        except Exception as e:
            log.debug(f"Key error: {e}")

    # ----- Transferência de arquivos -----

    def send_file(self, filepath):
        try:
            name = os.path.basename(filepath)
            size = os.path.getsize(filepath)
            fid = hashlib.md5(f"{name}{time.time()}".encode()).hexdigest()[:12]
            chunk_size = 64 * 1024
            total = (size + chunk_size - 1) // chunk_size

            self.send_json({'type': 'file_info', 'id': fid, 'name': name,
                            'size': size, 'total_chunks': total})

            with open(filepath, 'rb') as f:
                idx = 0
                while True:
                    chunk = f.read(chunk_size)
                    if not chunk:
                        break
                    self.send_json({
                        'type': 'file_chunk', 'id': fid, 'index': idx,
                        'data': base64.b64encode(chunk).decode('ascii'), 'total': total,
                    })
                    idx += 1
                    time.sleep(0.01)

            self.send_json({'type': 'file_complete', 'id': fid, 'name': name})
            log.info(f"Arquivo enviado: {name}")
        except Exception as e:
            log.error(f"Erro envio arquivo: {e}")

    def _save_received_file(self, fi):
        downloads = os.path.join(os.path.expanduser("~"), "Downloads")
        os.makedirs(downloads, exist_ok=True)
        name = fi['name']
        path = os.path.join(downloads, name)
        base, ext = os.path.splitext(name)
        c = 1
        while os.path.exists(path):
            path = os.path.join(downloads, f"{base}_{c}{ext}")
            c += 1
        try:
            with open(path, 'wb') as f:
                for i in sorted(fi['chunks'].keys()):
                    f.write(fi['chunks'][i])
            log.info(f"Arquivo recebido: {path}")
            if self.tray:
                self.tray.notify("Arquivo Recebido", os.path.basename(path))
        except Exception as e:
            log.error(f"Erro salvar arquivo: {e}")


# ==========================================
#  System Tray
# ==========================================

class TrayApp:
    def __init__(self):
        self.config = Config()
        self.client = RemoteClient(self.config)
        self.client.tray = self

        self.icon = None
        self._code = ''
        self._state = 'disconnected'
        self._status_text = 'Iniciando...'

    # ----- Ícone -----

    def _create_icon(self, state='disconnected'):
        size = 64
        img = Image.new('RGBA', (size, size), (0, 0, 0, 0))
        draw = ImageDraw.Draw(img)

        colors = {
            'disconnected': (100, 116, 139),
            'connecting':   (245, 158, 11),
            'online':       (16, 185, 129),
            'active':       (59, 130, 246),
        }
        c = colors.get(state, colors['disconnected'])

        # Monitor
        draw.rounded_rectangle([2, 6, 62, 44], radius=5, fill=c)
        draw.rounded_rectangle([6, 10, 58, 40], radius=3, fill=(255, 255, 255, 240))
        # Stand
        draw.polygon([(24, 44), (40, 44), (42, 52), (22, 52)], fill=c)
        draw.rectangle([18, 52, 46, 56], fill=c)
        # Dot de status
        draw.ellipse([24, 18, 40, 34], fill=c)

        return img

    def _tooltip(self):
        code = self._code or '------'
        return f"{APP_NAME}\nCódigo: {code}\n{self._status_text}"

    def update_state(self, state, text):
        self._state = state
        self._status_text = text
        if self.icon:
            self.icon.icon = self._create_icon(state)
            self.icon.title = self._tooltip()

    def set_code(self, code):
        self._code = code
        if self.icon:
            self.icon.title = self._tooltip()

    def notify(self, title, message):
        if self.icon:
            try:
                self.icon.notify(message, title)
            except Exception:
                pass

    # ----- Menu callbacks -----

    def _menu_code_text(self, item):
        return f"Código: {self._code or '------'}"

    def _menu_status_text(self, item):
        return f"Status: {self._status_text}"

    def _on_copy_code(self):
        if self._code:
            try:
                pyperclip.copy(self._code)
                self.notify(APP_NAME, f"Código {self._code} copiado!")
            except Exception:
                pass

    def _on_reconnect(self):
        threading.Thread(target=self._do_reconnect, daemon=True).start()

    def _do_reconnect(self):
        self.update_state('connecting', 'Reconectando...')
        self.client.disconnect()
        time.sleep(1)
        self.client.connect()

    def _on_about(self):
        info = self.client.get_system_info()
        ctypes.windll.user32.MessageBoxW(
            0,
            f"{APP_NAME} v2.0\n\n"
            f"Servidor: {self.config.server_url or '(não configurado)'}\n"
            f"Código: {self._code or '---'}\n\n"
            f"Computador: {info['hostname']}\n"
            f"Usuário: {info['username']}\n"
            f"IP: {info['ip_local']}\n"
            f"OS: {info['os']}\n\n"
            f"Instalado em:\n{INSTALL_DIR}",
            f"Sobre \u2014 {APP_NAME}",
            0x40
        )

    def _on_uninstall(self):
        r = ctypes.windll.user32.MessageBoxW(
            0,
            "Deseja desinstalar o HelpDesk Remote?\n\n"
            "O programa será removido da inicialização automática.",
            f"{APP_NAME} \u2014 Desinstalar",
            0x04 | 0x20
        )
        if r == 6:
            do_uninstall()
            self.notify(APP_NAME, "Desinstalado. O programa será fechado.")
            time.sleep(1)
            self._on_quit()

    def _on_quit(self):
        self.client.disconnect()
        if self.icon:
            self.icon.stop()

    # ----- Auto-connect + Reconnect loop (v3.0: melhorado) -----

    def _auto_connect(self):
        time.sleep(2)
        if not self.config.server_url:
            self.update_state('disconnected', 'Servidor não configurado')
            self.notify(APP_NAME,
                        "Servidor não configurado.\n"
                        "Reinstale o client pelo sistema HelpDesk.")
            return

        self.update_state('connecting', 'Conectando...')
        self.client._should_reconnect = True
        self.client.connect()

        # v3.0: Loop de reconexão com backoff exponencial
        retry_delay = 5
        max_delay = 60
        while self.client._should_reconnect:
            time.sleep(retry_delay)
            if not self.client.connected and self.config.server_url:
                log.info(f"Tentando reconectar (delay={retry_delay}s)...")
                self.update_state('connecting', 'Reconectando...')
                try:
                    self.client.connect()
                except Exception:
                    pass
                # Backoff: aumentar delay progressivamente
                retry_delay = min(retry_delay * 1.5, max_delay)
            else:
                # Conectado — resetar delay
                retry_delay = 5

    # ----- Run -----

    def run(self):
        menu = pystray.Menu(
            pystray.MenuItem(self._menu_code_text, self._on_copy_code),
            pystray.MenuItem(self._menu_status_text, None, enabled=False),
            pystray.Menu.SEPARATOR,
            pystray.MenuItem('Reconectar', self._on_reconnect),
            pystray.MenuItem('Sobre', self._on_about),
            pystray.Menu.SEPARATOR,
            pystray.MenuItem('Desinstalar', self._on_uninstall),
            pystray.MenuItem('Sair', self._on_quit),
        )

        self.icon = pystray.Icon(
            'helpdesk_remote',
            self._create_icon('disconnected'),
            f'{APP_NAME} \u2014 Iniciando...',
            menu
        )

        threading.Thread(target=self._auto_connect, daemon=True).start()
        self.icon.run()


# ==========================================
#  Main
# ==========================================

def main():
    # Mutex — impedir múltiplas instâncias
    mutex_name = "HelpDeskRemoteClientMutex"
    mutex = ctypes.windll.kernel32.CreateMutexW(None, False, mutex_name)
    if ctypes.windll.kernel32.GetLastError() == 183:
        log.info("Outra instância já está rodando.")
        ctypes.windll.user32.MessageBoxW(
            0,
            f"{APP_NAME} já está em execução.\n\n"
            "Verifique o ícone na bandeja do sistema\n"
            u"(clique \u25B2 ao lado do relógio).",
            APP_NAME, 0x40
        )
        sys.exit(0)

    # Argumento --uninstall
    if '--uninstall' in sys.argv:
        do_uninstall()
        print("Desinstalado.")
        sys.exit(0)

    # Self-install (quando EXE roda fora do diretório de instalação)
    if IS_FROZEN and not is_installed():
        r = ctypes.windll.user32.MessageBoxW(
            0,
            "Deseja instalar o HelpDesk Remote?\n\n"
            f"Local: {INSTALL_DIR}\n\n"
            u"• Iniciará automaticamente com o Windows\n"
            u"• Ficará na bandeja do sistema (ao lado do relógio)\n"
            u"• O servidor já está pré-configurado",
            f"{APP_NAME} \u2014 Instalação",
            0x04 | 0x20
        )
        if r == 6:
            do_install()
            return

    app = TrayApp()
    app.run()


if __name__ == '__main__':
    main()
