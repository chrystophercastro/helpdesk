# -*- mode: python ; coding: utf-8 -*-


a = Analysis(
    ['helpdesk_remote.py'],
    pathex=[],
    binaries=[('turbojpeg.dll', '.')],
    datas=[],
    hiddenimports=['pystray._win32', 'PIL._tkinter_finder', 'turbojpeg', 'numpy', 'dxcam', 'comtypes', 'dxcam.core'],
    hookspath=[],
    hooksconfig={},
    runtime_hooks=[],
    excludes=[],
    noarchive=False,
    optimize=0,
)
pyz = PYZ(a.pure)

exe = EXE(
    pyz,
    a.scripts,
    a.binaries,
    a.datas,
    [],
    name='HelpDesk_Remote',
    debug=False,
    bootloader_ignore_signals=False,
    strip=False,
    upx=True,
    upx_exclude=[],
    runtime_tmpdir=None,
    console=False,
    disable_windowed_traceback=False,
    argv_emulation=False,
    target_arch=None,
    codesign_identity=None,
    entitlements_file=None,
)
