'use strict';
/**
 * agent-core.js — Express HTTP server + ESC/POS logic
 * สามารถ require() จาก Electron main หรือรันตรงๆ ด้วย node agent-core.js
 */

const express  = require('express');
const net      = require('net');
const { exec } = require('child_process');
const fs       = require('fs');
const path     = require('path');
const os       = require('os');
const crypto   = require('crypto');
const { EventEmitter } = require('events');

// ── Config ────────────────────────────────────────────────────────────────────
const CONFIG_PATH = path.join(__dirname, 'config.json');

function loadConfig() {
  if (!fs.existsSync(CONFIG_PATH)) {
    const d = {
      port:      9991,
      token:     'pos-' + crypto.randomBytes(12).toString('hex'),
      printer: {
        type:        'network',
        host:        '192.168.1.100',
        tcpPort:     9100,
        windowsName: '',
        serialPort:  'COM1',
        baudRate:    9600,
      },
      drawerPin: 0,
      pulseOn:   25,
      pulseOff:  250,
    };
    fs.writeFileSync(CONFIG_PATH, JSON.stringify(d, null, 2), 'utf8');
    return d;
  }
  try { return JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8')); }
  catch { return loadConfig(); }
}

function saveConfig(cfg) {
  fs.writeFileSync(CONFIG_PATH, JSON.stringify(cfg, null, 2), 'utf8');
}

// ── ESC/POS ───────────────────────────────────────────────────────────────────
function buildDrawerCmd(pin = 0, t1 = 25, t2 = 250) {
  return Buffer.from([0x1B, 0x40, 0x1B, 0x70, pin & 1, t1 & 0xFF, t2 & 0xFF]);
}

function sendNetwork(data, host, port) {
  return new Promise((resolve, reject) => {
    const sock = new net.Socket();
    const timer = setTimeout(() => { sock.destroy(); reject(new Error(`Timeout ${host}:${port}`)); }, 3000);
    sock.connect(port, host, () => {
      sock.write(data, () => { clearTimeout(timer); sock.destroy(); resolve('network ok'); });
    });
    sock.on('error', e => { clearTimeout(timer); reject(e); });
  });
}

function sendWindows(data, printerName) {
  return new Promise((resolve, reject) => {
    if (!printerName) return reject(new Error('ไม่ได้ตั้งค่า windowsName'));
    const tmp = path.join(os.tmpdir(), `cd_${Date.now()}.bin`);
    fs.writeFileSync(tmp, data);
    const ps = `
Add-Type -TypeDefinition @"
using System; using System.Runtime.InteropServices;
public class RawPrint {
  [DllImport("winspool.drv",CharSet=CharSet.Ansi)] public static extern bool OpenPrinter(string n,out IntPtr h,IntPtr d);
  [DllImport("winspool.drv")] public static extern bool ClosePrinter(IntPtr h);
  [StructLayout(LayoutKind.Sequential)] public struct DI { [MarshalAs(UnmanagedType.LPStr)]public string dn,of,dt; }
  [DllImport("winspool.drv",CharSet=CharSet.Ansi)] public static extern bool StartDocPrinter(IntPtr h,int l,ref DI d);
  [DllImport("winspool.drv")] public static extern bool EndDocPrinter(IntPtr h);
  [DllImport("winspool.drv")] public static extern bool StartPagePrinter(IntPtr h);
  [DllImport("winspool.drv")] public static extern bool EndPagePrinter(IntPtr h);
  [DllImport("winspool.drv")] public static extern bool WritePrinter(IntPtr h,byte[] b,int c,out int w);
  public static void Send(string name, byte[] bytes){
    IntPtr h; OpenPrinter(name,out h,IntPtr.Zero);
    DI d=new DI{dn="RAW",dt="RAW"}; StartDocPrinter(h,1,ref d); StartPagePrinter(h);
    int w; WritePrinter(h,bytes,bytes.Length,out w); EndPagePrinter(h); EndDocPrinter(h); ClosePrinter(h);
  }
}
"@
[RawPrint]::Send('${printerName}', [System.IO.File]::ReadAllBytes('${tmp.replace(/\\/g,'\\\\')}'))
`.replace(/\n/g, ';');
    exec(`powershell -NoProfile -NonInteractive -Command "${ps}"`, (err,_,se) => {
      try { fs.unlinkSync(tmp); } catch{}
      if (err) return reject(new Error(se || err.message));
      resolve('windows ok');
    });
  });
}

function sendSerial(data, portName, baudRate) {
  return new Promise((resolve, reject) => {
    let SP;
    try { SP = require('serialport').SerialPort; }
    catch { return reject(new Error('ไม่ได้ติดตั้ง serialport (npm install serialport)')); }
    const p = new SP({ path: portName, baudRate, autoOpen: false });
    p.open(e => {
      if (e) return reject(e);
      p.write(data, e2 => { p.close(); e2 ? reject(e2) : resolve('serial ok'); });
    });
  });
}

// ── AgentServer class ─────────────────────────────────────────────────────────
class AgentServer extends EventEmitter {
  constructor() {
    super();
    this.cfg    = loadConfig();
    this.app    = null;
    this.server = null;
    this.logs   = []; // circular buffer 200 entries
  }

  log(level, msg) {
    const entry = { time: new Date().toLocaleTimeString('th-TH'), level, msg };
    this.logs.push(entry);
    if (this.logs.length > 200) this.logs.shift();
    this.emit('log', entry);
    console[level === 'error' ? 'error' : 'log'](`[${level.toUpperCase()}] ${msg}`);
  }

  async openDrawer() {
    const c   = this.cfg;
    const cmd = buildDrawerCmd(c.drawerPin, c.pulseOn, c.pulseOff);
    switch (c.printer.type) {
      case 'network': return sendNetwork(cmd, c.printer.host, c.printer.tcpPort);
      case 'windows': return sendWindows(cmd, c.printer.windowsName);
      case 'serial':  return sendSerial(cmd, c.printer.serialPort, c.printer.baudRate);
      default: throw new Error(`printer type ไม่รู้จัก: ${c.printer.type}`);
    }
  }

  async testConnection() {
    const c   = this.cfg;
    const cmd = Buffer.from([0x1B, 0x40]); // ESC @ only
    switch (c.printer.type) {
      case 'network': return sendNetwork(cmd, c.printer.host, c.printer.tcpPort);
      case 'windows': return sendWindows(cmd, c.printer.windowsName);
      case 'serial':  return sendSerial(cmd, c.printer.serialPort, c.printer.baudRate);
    }
  }

  reloadConfig() { this.cfg = loadConfig(); }

  updateConfig(patch) {
    Object.assign(this.cfg, patch);
    saveConfig(this.cfg);
    this.reloadConfig();
  }

  start() {
    return new Promise((resolve, reject) => {
      const app = express();
      this.app = app;
      app.use(express.json());

      // CORS — localhost only
      app.use((req, res, next) => {
        const o = req.headers.origin || '';
        if (!o || /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test(o)) {
          res.setHeader('Access-Control-Allow-Origin', o || '*');
          res.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
          res.setHeader('Access-Control-Allow-Headers', 'Content-Type,X-Agent-Token');
        }
        if (req.method === 'OPTIONS') return res.sendStatus(204);
        next();
      });

      // Auth
      app.use((req, res, next) => {
        if (req.path === '/status') return next();
        const tok = req.headers['x-agent-token'] || req.query.token;
        if (tok !== this.cfg.token) {
          this.log('warn', `Unauthorized from ${req.ip} → ${req.path}`);
          return res.status(401).json({ ok: false, error: 'Unauthorized' });
        }
        next();
      });

      app.get('/status', (req, res) => {
        res.json({ ok: true, version: '1.0.0',
          printer: { type: this.cfg.printer.type, host: this.cfg.printer.host } });
      });

      app.post('/open-drawer', async (req, res) => {
        const { triggeredBy = 'unknown', reason = '' } = req.body;
        this.log('info', `open-drawer | by: ${triggeredBy} | ${reason}`);
        try {
          const r = await this.openDrawer();
          this.log('info', `drawer opened: ${r}`);
          this.emit('drawer-opened', { triggeredBy, reason });
          res.json({ ok: true, result: r });
        } catch (e) {
          this.log('error', `open-drawer failed: ${e.message}`);
          res.status(500).json({ ok: false, error: e.message });
        }
      });

      app.post('/test', async (req, res) => {
        this.log('info', 'test connection');
        try {
          const r = await this.testConnection();
          this.log('info', `test ok: ${r}`);
          res.json({ ok: true, result: r });
        } catch (e) {
          this.log('error', `test failed: ${e.message}`);
          res.status(500).json({ ok: false, error: e.message });
        }
      });

      app.get('/config', (req, res) => {
        const safe = JSON.parse(JSON.stringify(this.cfg));
        delete safe.token;
        res.json(safe);
      });

      app.post('/config', (req, res) => {
        const allowed = ['printer', 'drawerPin', 'pulseOn', 'pulseOff'];
        const patch = {};
        for (const k of allowed) { if (req.body[k] !== undefined) patch[k] = req.body[k]; }
        this.updateConfig(patch);
        this.log('info', 'config updated via API');
        res.json({ ok: true });
      });

      this.server = app.listen(this.cfg.port, '127.0.0.1', () => {
        this.log('info', `Agent started on port ${this.cfg.port}`);
        resolve(this.cfg.port);
      });

      this.server.on('error', reject);
    });
  }

  stop() {
    return new Promise(resolve => {
      if (this.server) this.server.close(resolve);
      else resolve();
    });
  }
}

module.exports = AgentServer;

// รัน standalone
if (require.main === module) {
  const srv = new AgentServer();
  srv.on('log', e => console.log(`[${e.level}] ${e.msg}`));
  srv.start().then(p => {
    console.log(`Agent running on http://127.0.0.1:${p}`);
    console.log(`Token: ${srv.cfg.token}`);
  }).catch(console.error);
}
