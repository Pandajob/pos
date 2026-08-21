'use strict';
const { contextBridge, ipcRenderer } = require('electron');

contextBridge.exposeInMainWorld('agent', {
  // ── ข้อมูล ────────────────────────────────────────────────────────────────
  getConfig:    ()      => ipcRenderer.invoke('get-config'),
  getLogs:      ()      => ipcRenderer.invoke('get-logs'),
  getStatus:    ()      => ipcRenderer.invoke('get-status'),

  // ── Actions ───────────────────────────────────────────────────────────────
  saveConfig:   (cfg)   => ipcRenderer.invoke('save-config', cfg),
  openDrawer:   ()      => ipcRenderer.invoke('open-drawer'),
  testConn:     ()      => ipcRenderer.invoke('test-conn'),
  copyToken:    ()      => ipcRenderer.invoke('copy-token'),
  openLogsDir:  ()      => ipcRenderer.invoke('open-logs-dir'),

  // ── Events จาก main → renderer ───────────────────────────────────────────
  onLog:          (cb)  => ipcRenderer.on('log',           (_, e) => cb(e)),
  onStatusChange: (cb)  => ipcRenderer.on('status-change', (_, s) => cb(s)),
  onDrawerOpened: (cb)  => ipcRenderer.on('drawer-opened', (_, d) => cb(d)),
});
