'use strict';
const { app, BrowserWindow, ipcMain, Tray, Menu, nativeImage,
        clipboard, shell, Notification } = require('electron');
const path        = require('path');
const fs          = require('fs');
const AgentServer = require('./agent-core');

// ── Single instance ───────────────────────────────────────────────────────────
if (!app.requestSingleInstanceLock()) { app.quit(); process.exit(0); }

// ── State ─────────────────────────────────────────────────────────────────────
let win    = null;
let tray   = null;
let server = new AgentServer();
let serverRunning = false;

// ── สร้าง icon inline (simple cash drawer icon as PNG data) ──────────────────
const ICON_PATH = path.join(__dirname, 'ui', 'icon.png');

// ── Window ────────────────────────────────────────────────────────────────────
function createWindow() {
  win = new BrowserWindow({
    width:          520,
    height:         680,
    minWidth:       460,
    minHeight:      500,
    resizable:      true,
    title:          'POS Cash Drawer Agent',
    icon:           fs.existsSync(ICON_PATH) ? ICON_PATH : undefined,
    webPreferences: {
      preload:            path.join(__dirname, 'preload.js'),
      contextIsolation:   true,
      nodeIntegration:    false,
    },
  });

  win.loadFile(path.join(__dirname, 'ui', 'index.html'));
  win.setMenuBarVisibility(false);

  // minimize to tray แทน close
  win.on('close', e => {
    if (!app.isQuitting) {
      e.preventDefault();
      win.hide();
      if (Notification.isSupported()) {
        new Notification({ title: 'POS Cash Drawer', body: 'ยังทำงานอยู่ใน System Tray' }).show();
      }
    }
  });
}

// ── Tray ──────────────────────────────────────────────────────────────────────
function createTray() {
  const img = fs.existsSync(ICON_PATH)
    ? nativeImage.createFromPath(ICON_PATH).resize({ width: 16, height: 16 })
    : nativeImage.createEmpty();

  tray = new Tray(img);
  tray.setToolTip('POS Cash Drawer Agent');
  updateTrayMenu();

  tray.on('double-click', () => { win?.show(); win?.focus(); });
}

function updateTrayMenu() {
  if (!tray) return;
  const menu = Menu.buildFromTemplate([
    { label: serverRunning ? '🟢 Agent Online' : '🔴 Agent Offline', enabled: false },
    { type: 'separator' },
    { label: 'เปิดหน้าต่าง', click: () => { win?.show(); win?.focus(); } },
    { label: 'เปิดลิ้นชัก', click: () => triggerOpenDrawer('tray-menu') },
    { type: 'separator' },
    { label: 'ออกจากโปรแกรม', click: () => { app.isQuitting = true; app.quit(); } },
  ]);
  tray.setContextMenu(menu);
}

// ── Start agent server ────────────────────────────────────────────────────────
async function startServer() {
  try {
    await server.start();
    serverRunning = true;
    win?.webContents.send('status-change', { running: true, port: server.cfg.port });
    updateTrayMenu();
  } catch (e) {
    console.error('Server start failed:', e);
    win?.webContents.send('status-change', { running: false, error: e.message });
  }
}

// ── Forward server logs → renderer ───────────────────────────────────────────
server.on('log', entry => {
  win?.webContents.send('log', entry);
});

server.on('drawer-opened', data => {
  win?.webContents.send('drawer-opened', data);
  updateTrayMenu();
});

// ── IPC handlers ──────────────────────────────────────────────────────────────
ipcMain.handle('get-config', () => server.cfg);

ipcMain.handle('get-logs', () => server.logs);

ipcMain.handle('get-status', () => ({
  running: serverRunning,
  port:    server.cfg.port,
}));

ipcMain.handle('save-config', (_, patch) => {
  server.updateConfig(patch);
  server.log('info', 'Config saved from UI');
  win?.webContents.send('status-change', { running: serverRunning, port: server.cfg.port });
  return { ok: true, token: server.cfg.token };
});

ipcMain.handle('open-drawer', async () => {
  return triggerOpenDrawer('ui-button');
});

async function triggerOpenDrawer(triggeredBy) {
  server.log('info', `open-drawer from ${triggeredBy}`);
  try {
    const r = await server.openDrawer();
    server.log('info', `drawer opened: ${r}`);
    server.emit('drawer-opened', { triggeredBy });
    return { ok: true };
  } catch (e) {
    server.log('error', `open-drawer failed: ${e.message}`);
    return { ok: false, error: e.message };
  }
}

ipcMain.handle('test-conn', async () => {
  server.log('info', 'test connection from UI');
  try {
    const r = await server.testConnection();
    server.log('info', `test ok: ${r}`);
    return { ok: true };
  } catch (e) {
    server.log('error', `test failed: ${e.message}`);
    return { ok: false, error: e.message };
  }
});

ipcMain.handle('copy-token', () => {
  clipboard.writeText(server.cfg.token);
  return server.cfg.token;
});

ipcMain.handle('open-logs-dir', () => {
  shell.openPath(__dirname);
});

// ── App lifecycle ─────────────────────────────────────────────────────────────
app.whenReady().then(async () => {
  await startServer();
  createWindow();
  createTray();
});

app.on('second-instance', () => { win?.show(); win?.focus(); });

app.on('window-all-closed', e => e.preventDefault()); // keep running in tray

app.on('before-quit', async () => {
  app.isQuitting = true;
  await server.stop();
});
