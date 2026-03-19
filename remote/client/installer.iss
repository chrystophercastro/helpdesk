; ============================================
; HelpDesk Remote - Inno Setup Installer Script
; ============================================
; Compilar: ISCC.exe installer.iss
; Requer:   Inno Setup 6+ (https://jrsoftware.org/isinfo.php)
;
; Arquivos necessarios (relativos a este .iss):
;   dist\HelpDesk_Remote.exe  (compilado com PyInstaller)
;   config.json               (gerado pelo sistema ou manualmente)
; ============================================

#define MyAppName      "HelpDesk Remote"
#define MyAppVersion   "2.0"
#define MyAppPublisher "HelpDesk TI"
#define MyAppExeName   "HelpDesk_Remote.exe"
#define MyAppMutex     "HelpDeskRemoteClientMutex"

[Setup]
AppId={{7B3F8E2A-5C1D-4A9E-B6F0-8D2E1C3A5B7F}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppVerName={#MyAppName} {#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={localappdata}\HelpDeskRemote
DefaultGroupName={#MyAppName}
DisableProgramGroupPage=yes
DisableDirPage=yes
OutputDir=output
OutputBaseFilename=HelpDesk_Remote_Setup
Compression=lzma2
SolidCompression=yes
PrivilegesRequired=lowest
AppMutex={#MyAppMutex}
UninstallDisplayName={#MyAppName}
CloseApplications=force
RestartApplications=no
SetupLogging=yes
VersionInfoVersion=2.0.0.0
VersionInfoProductName={#MyAppName}
VersionInfoDescription=HelpDesk Remote - Agente de Acesso Remoto
WizardStyle=modern

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Criar atalho na &Area de Trabalho"; GroupDescription: "Icones adicionais:"
Name: "autostart"; Description: "Iniciar automaticamente com o &Windows"; GroupDescription: "Configuracoes:"; Flags: checkedonce

[Files]
Source: "dist\{#MyAppExeName}"; DestDir: "{app}"; Flags: ignoreversion
Source: "config.json"; DestDir: "{app}"; Flags: ignoreversion

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{group}\Desinstalar {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon

[Registry]
Root: HKCU; Subkey: "Software\Microsoft\Windows\CurrentVersion\Run"; ValueType: string; ValueName: "HelpDeskRemote"; ValueData: """{app}\{#MyAppExeName}"""; Flags: uninsdeletevalue; Tasks: autostart

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "{cm:LaunchProgram,{#StringChange(MyAppName, '&', '&&')}}"; Flags: nowait postinstall skipifsilent

[UninstallRun]
Filename: "taskkill.exe"; Parameters: "/F /IM {#MyAppExeName}"; Flags: runhidden; RunOnceId: "KillApp"

[UninstallDelete]
Type: files; Name: "{app}\client.log"
Type: files; Name: "{app}\logs\*"

[Code]
procedure CurStepChanged(CurStep: TSetupStep);
var
  ResultCode: Integer;
begin
  if CurStep = ssInstall then
  begin
    Exec('taskkill.exe', '/F /IM {#MyAppExeName}', '', SW_HIDE, ewWaitUntilTerminated, ResultCode);
    Sleep(500);
  end;
end;
