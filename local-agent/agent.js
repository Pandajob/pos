/**
 * POS Cash Drawer Local Agent
 * ─────────────────────────────────────────────────────────────────────────────
 * รับ HTTP request จาก browser POS → ส่ง ESC/POS command → เปิดลิ้นชักเงิน
 *
 * รองรับ 3 แบบ:
 *   network  — TCP/IP (เครื่องพิมพ์ต่อ LAN/WiFi)
 *   windows  — Windows Printer (USB ผ่าน Windows Print Spooler)
 *   serial   — Serial/COM port (RS-232)
 *
 * รัน: node agent.js
 * ─────────────────────────────────────────────────────────────────────────────
 */

'use strict';

const http       = require('http');   // ใช้ http ในตัว Node — ไม่ต้องพึ่ง express/node_modules
const net        = require('net');
const { exec, execFile } = require('child_process');
const fs         = require('fs');
const path       = require('path');
const os         = require('os');
const crypto     = require('crypto');

// ── Config ────────────────────────────────────────────────────────────────────
const CONFIG_PATH = path.join(__dirname, 'config.json');

function loadConfig() {
  if (!fs.existsSync(CONFIG_PATH)) {
    const defaults = {
      port:  9991,
      token: 'pos-' + crypto.randomBytes(12).toString('hex'),
      printer: {
        type:        'network',   // 'network' | 'windows' | 'serial'
        host:        '127.0.0.1', // สำหรับ network
        tcpPort:     9100,         // สำหรับ network (มาตรฐาน ESC/POS)
        windowsName: '',           // ชื่อ printer ใน Windows เช่น "XP-80"
        serialPort:  'COM1',       // สำหรับ serial
        baudRate:    9600,
      },
      // pin ที่เชื่อมลิ้นชัก: 0 = pin 2 (พบบ่อย), 1 = pin 5
      drawerPin:  0,
      // pulse duration ของ ESC/POS (t1, t2): เพิ่มถ้าลิ้นชักไม่เปิด
      pulseOn:    25,  // t1 × 2ms
      pulseOff:   250, // t2 × 2ms
    };
    fs.writeFileSync(CONFIG_PATH, JSON.stringify(defaults, null, 2), 'utf8');
    console.log('[agent] สร้างไฟล์ config.json ใหม่ — token:', defaults.token);
    return defaults;
  }
  return JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'));
}

let CFG = loadConfig();

// ── ESC/POS command builder ───────────────────────────────────────────────────
function buildOpenDrawerCmd(pin = 0, t1 = 25, t2 = 250) {
  /**
   * ESC p m t1 t2
   * 0x1B 0x70 m t1 t2
   * m = 0 → pin 2 (RJ11 pin 2-5, พบบ่อยที่สุด)
   * m = 1 → pin 5 (RJ11 pin 2-5, บางยี้ห้อ)
   */
  return Buffer.from([
    0x1B, 0x40,              // ESC @ initialize printer
    0x1B, 0x70, pin, t1, t2, // ESC p — open cash drawer
  ]);
}

// ── Printer backends ──────────────────────────────────────────────────────────

/** Network printer (TCP port 9100) */
function sendNetwork(data) {
  return new Promise((resolve, reject) => {
    const client = new net.Socket();
    const timeout = setTimeout(() => {
      client.destroy();
      reject(new Error(`Timeout connecting to ${CFG.printer.host}:${CFG.printer.tcpPort}`));
    }, 3000);

    client.connect(CFG.printer.tcpPort, CFG.printer.host, () => {
      client.write(data, () => {
        clearTimeout(timeout);
        client.destroy();
        resolve('network ok');
      });
    });

    client.on('error', (err) => {
      clearTimeout(timeout);
      reject(err);
    });
  });
}

// สคริปต์ PowerShell สำหรับส่ง raw bytes → Windows spooler (winspool RawPrint)
// เขียนเป็นไฟล์ .ps1 จริงแล้วรันด้วย -File เพื่อรักษาขึ้นบรรทัดของ here-string @"..."@
// (เวอร์ชันเก่ายุบ \n เป็นช่องว่างผ่าน -Command ทำให้ here-string พัง:
//  "No characters are allowed after a here-string header")
const RAWPRINT_PS = [
  'param([Parameter(Mandatory=$true)][string]$PrinterName,',
  '      [Parameter(Mandatory=$true)][string]$FilePath)',
  '$ErrorActionPreference = "Stop"',
  '$code = @"',
  'using System;',
  'using System.Runtime.InteropServices;',
  'public class RawPrinter {',
  '  [DllImport("winspool.drv", CharSet=CharSet.Ansi, SetLastError=true)]',
  '  public static extern bool OpenPrinter(string src, out IntPtr h, IntPtr pd);',
  '  [DllImport("winspool.drv", SetLastError=true)]',
  '  public static extern bool ClosePrinter(IntPtr h);',
  '  [DllImport("winspool.drv", CharSet=CharSet.Ansi, SetLastError=true)]',
  '  public static extern bool StartDocPrinter(IntPtr h, int level, ref DOCINFO di);',
  '  [DllImport("winspool.drv", SetLastError=true)]',
  '  public static extern bool EndDocPrinter(IntPtr h);',
  '  [DllImport("winspool.drv", SetLastError=true)]',
  '  public static extern bool StartPagePrinter(IntPtr h);',
  '  [DllImport("winspool.drv", SetLastError=true)]',
  '  public static extern bool EndPagePrinter(IntPtr h);',
  '  [DllImport("winspool.drv", SetLastError=true)]',
  '  public static extern bool WritePrinter(IntPtr h, byte[] buf, int count, out int written);',
  '  [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)] public struct DOCINFO {',
  '    [MarshalAs(UnmanagedType.LPStr)] public string pDocName;',
  '    [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;',
  '    [MarshalAs(UnmanagedType.LPStr)] public string pDataType;',
  '  }',
  '  public static string Send(string printer, byte[] bytes) {',
  '    IntPtr h;',
  '    if (!OpenPrinter(printer, out h, IntPtr.Zero)) return "ERR OpenPrinter " + Marshal.GetLastWin32Error();',
  '    DOCINFO di = new DOCINFO(); di.pDocName = "POS Receipt"; di.pDataType = "RAW";',
  '    if (!StartDocPrinter(h, 1, ref di)) { ClosePrinter(h); return "ERR StartDocPrinter " + Marshal.GetLastWin32Error(); }',
  '    StartPagePrinter(h);',
  '    int written;',
  '    bool ok = WritePrinter(h, bytes, bytes.Length, out written);',
  '    EndPagePrinter(h);',
  '    EndDocPrinter(h);',
  '    ClosePrinter(h);',
  '    if (!ok) return "ERR WritePrinter " + Marshal.GetLastWin32Error();',
  '    return "OK " + written;',
  '  }',
  '}',
  '"@',
  'Add-Type -TypeDefinition $code',
  '$bytes = [System.IO.File]::ReadAllBytes($FilePath)',
  '$r = [RawPrinter]::Send($PrinterName, $bytes)',
  'Write-Output $r',
  'if ($r -notlike "OK*") { exit 1 }',
].join('\r\n');

/** Windows Printer (USB ผ่าน Print Spooler) — overrideName ใช้ระบุเครื่องพิมพ์อื่นจาก config */
function sendWindows(data, overrideName) {
  return new Promise((resolve, reject) => {
    const printerName = overrideName || CFG.printer.windowsName;
    if (!printerName) return reject(new Error('ยังไม่ได้เลือกเครื่องพิมพ์ — เลือกในหน้า Settings ของ POS ก่อน'));

    const stamp   = Date.now() + '_' + Math.random().toString(16).slice(2, 8);
    const tmpFile = path.join(os.tmpdir(), `posraw_${stamp}.bin`);
    const ps1File = path.join(os.tmpdir(), `posraw_${stamp}.ps1`);
    const cleanup = () => {
      try { fs.unlinkSync(tmpFile); } catch {}
      try { fs.unlinkSync(ps1File); } catch {}
    };

    try {
      fs.writeFileSync(tmpFile, data);
      fs.writeFileSync(ps1File, RAWPRINT_PS, 'utf8');
    } catch (e) { cleanup(); return reject(e); }

    // execFile = ส่ง args เป็น array ไม่ผ่าน shell → ไม่มีปัญหาเรื่อง quote/ช่องว่างในชื่อเครื่องพิมพ์
    execFile('powershell',
      ['-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-File', ps1File,
       '-PrinterName', printerName, '-FilePath', tmpFile],
      { timeout: 20000, windowsHide: true },
      (err, stdout, stderr) => {
        cleanup();
        const out = (stdout || '').trim();
        if (err) {
          return reject(new Error('พิมพ์ไม่สำเร็จ: ' + (out || (stderr || '').trim() || err.message)));
        }
        if (!/^OK/.test(out)) {
          return reject(new Error('พิมพ์ไม่สำเร็จ: ' + (out || (stderr || '').trim() || 'unknown')));
        }
        resolve('windows ok (' + out + ')');
      }
    );
  });
}

/** Serial / COM port */
function sendSerial(data) {
  return new Promise((resolve, reject) => {
    // ลอง require serialport — optional dependency
    let SerialPort;
    try { SerialPort = require('serialport').SerialPort; }
    catch { return reject(new Error('ไม่ได้ติดตั้ง serialport (npm install serialport)')); }

    const port = new SerialPort({
      path:     CFG.printer.serialPort,
      baudRate: CFG.printer.baudRate,
      autoOpen: false,
    });

    port.open((err) => {
      if (err) return reject(err);
      port.write(data, (err2) => {
        port.close();
        if (err2) return reject(err2);
        resolve('serial ok');
      });
    });
  });
}

// ── ส่งคำสั่งตาม printer type ─────────────────────────────────────────────────
async function openDrawer() {
  const cmd = buildOpenDrawerCmd(CFG.drawerPin, CFG.pulseOn, CFG.pulseOff);
  switch (CFG.printer.type) {
    case 'network': return sendNetwork(cmd);
    case 'windows': return sendWindows(cmd);
    case 'serial':  return sendSerial(cmd);
    default: throw new Error(`printer type ไม่รู้จัก: ${CFG.printer.type}`);
  }
}

// ── รายชื่อเครื่องพิมพ์ใน Windows ────────────────────────────────────────────
function listPrinters() {
  return new Promise((resolve, reject) => {
    exec('powershell -NoProfile -NonInteractive -Command "Get-Printer | ForEach-Object Name"',
      { timeout: 8000 },
      (err, stdout) => {
        if (err) return reject(new Error('อ่านรายชื่อเครื่องพิมพ์ไม่ได้: ' + err.message));
        const names = stdout.split(/\r?\n/).map(s => s.trim()).filter(Boolean);
        resolve(names);
      }
    );
  });
}

// เดาเครื่องพิมพ์ความร้อน (ใบเสร็จ) จากชื่อ
const THERMAL_RE = /(80|58)mm|pos|thermal|receipt|xp-?\d|tm-?t|rp-?\d|gprinter|xprinter|slip/i;
// เครื่องพิมพ์สติกเกอร์/ฉลาก (พูด TSPL/ZPL ไม่ใช่ ESC/POS) — ห้ามเดาเป็นปริ้นสลิป
// ไม่งั้นส่ง ESC/POS ไปแล้วพิมพ์ออกมาเป็นตัวอักษรต่างดาว
const LABEL_RE = /label|sticker|barcode|tsc\b|tspl|zpl|zebra|godex|argox|xp-?[234]\d\d[bt]?\b|dt\d|gk4|gx4/i;
function guessThermalPrinter(names) {
  return names.find(n => THERMAL_RE.test(n) && !LABEL_RE.test(n)) || null;
}

// ── HTTP server (Node ในตัว — ไม่มี dependency) ───────────────────────────────
// router เล็ก ๆ ที่เลียนแบบ express เท่าที่ใช้: req.query/req.body/req.path/req.ip
//                                              res.json() / res.status(n) / res.sendStatus(n)
const ROUTES = { GET: {}, POST: {} };
const app = {
  get:  (p, h) => { ROUTES.GET[p]  = h; },
  post: (p, h) => { ROUTES.POST[p] = h; },
};
const MAX_BODY = 8 * 1024 * 1024; // 8mb — รองรับงานพิมพ์ raster ขนาดใหญ่

const server = http.createServer((req, res) => {
  const parsed = new URL(req.url, 'http://127.0.0.1');
  req.path  = parsed.pathname;
  req.query = Object.fromEntries(parsed.searchParams);
  req.ip    = req.socket.remoteAddress;

  // response helpers แบบ express
  res.json = (obj) => {
    if (!res.headersSent) res.setHeader('Content-Type', 'application/json; charset=utf-8');
    res.end(JSON.stringify(obj));
    return res;
  };
  res.status     = (code) => { res.statusCode = code; return res; };
  res.sendStatus = (code) => { res.statusCode = code; res.end(); return res; };

  // CORS — อนุญาตเฉพาะ localhost
  const origin = req.headers.origin || '';
  if (!origin || /^https?:\/\/localhost(:\d+)?$/.test(origin) || /^https?:\/\/127\.0\.0\.1/.test(origin)) {
    res.setHeader('Access-Control-Allow-Origin', origin || '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET,POST,OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type,X-Agent-Token');
  }
  if (req.method === 'OPTIONS') return res.sendStatus(204);

  // Token auth (ยกเว้น /status, /pair — agent ฟังแค่ 127.0.0.1 อยู่แล้ว)
  if (req.path !== '/status' && req.path !== '/pair') {
    const token = req.headers['x-agent-token'] || req.query.token;
    if (token !== CFG.token) {
      console.warn('[agent] Unauthorized request from', req.ip);
      return res.status(401).json({ ok: false, error: 'Unauthorized' });
    }
  }

  const handler = (ROUTES[req.method] || {})[req.path];
  if (!handler) return res.status(404).json({ ok: false, error: 'Not found' });

  const run = () => Promise.resolve(handler(req, res)).catch((err) => {
    console.error('[agent] handler error:', err.message);
    if (!res.writableEnded) res.status(500).json({ ok: false, error: err.message });
  });

  if (req.method === 'POST') {
    let size = 0;
    const chunks = [];
    req.on('data', (c) => {
      size += c.length;
      if (size > MAX_BODY) {
        res.status(413).json({ ok: false, error: 'payload too large' });
        req.destroy();
        return;
      }
      chunks.push(c);
    });
    req.on('end', () => {
      if (res.writableEnded) return; // ถูกตัดเพราะ payload ใหญ่เกิน
      const raw = Buffer.concat(chunks).toString('utf8');
      try { req.body = raw ? JSON.parse(raw) : {}; }
      catch { return res.status(400).json({ ok: false, error: 'invalid JSON' }); }
      run();
    });
  } else {
    req.body = {};
    run();
  }
});

// ── Routes ────────────────────────────────────────────────────────────────────

/** Health check (ไม่ต้อง token) */
app.get('/status', (req, res) => {
  res.json({
    ok:      true,
    version: '1.0.0',
    printer: { type: CFG.printer.type, host: CFG.printer.host },
  });
});

/** จับคู่อัตโนมัติ — คืน token ให้เว็บ POS บนเครื่องเดียวกัน (agent ฟังแค่ 127.0.0.1) */
app.get('/pair', async (req, res) => {
  console.log('[agent] pair request from', req.ip);
  await autoDetectPrinter(); // ซ่อม config เก่าที่ยังเป็น network 127.0.0.1:9100 ให้ด้วย
  let printers = [];
  let suggested = null;
  try {
    printers  = await listPrinters();
    suggested = guessThermalPrinter(printers);
  } catch (e) { /* ไม่มีสิทธิ์/ไม่ใช่ Windows — ข้าม */ }
  res.json({
    ok:        true,
    token:     CFG.token,
    port:      CFG.port,
    version:   '1.1.0',
    printers,
    suggested, // เครื่องพิมพ์ความร้อนที่เดาได้
  });
});

/** รายชื่อเครื่องพิมพ์ทั้งหมดใน Windows */
app.get('/printers', async (req, res) => {
  try {
    const printers = await listPrinters();
    res.json({ ok: true, printers, suggested: guessThermalPrinter(printers) });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

/**
 * พิมพ์ raw bytes (ESC/POS) ไปยังเครื่องพิมพ์ที่ระบุ
 * body: { printerName?: string, dataBase64: string, openDrawer?: bool }
 */
app.post('/print', async (req, res) => {
  const { printerName, dataBase64, openDrawer: kick = false } = req.body;
  if (!dataBase64) return res.status(400).json({ ok: false, error: 'ไม่มีข้อมูล dataBase64' });

  let data;
  try { data = Buffer.from(dataBase64, 'base64'); }
  catch { return res.status(400).json({ ok: false, error: 'dataBase64 ไม่ถูกต้อง' }); }

  if (kick) {
    // นำหน้าคำสั่งเปิดลิ้นชักไว้ "ก่อน" งานพิมพ์ — บางรุ่นไม่สนใจคำสั่งที่มาหลังตัดกระดาษ
    // (เหมือนลำดับ byte ของ /open-drawer เดี่ยว ๆ ที่ทำงานได้เสมอ) และลิ้นชักเด้งทันทีไม่ต้องรอพิมพ์จบ
    data = Buffer.concat([buildOpenDrawerCmd(CFG.drawerPin, CFG.pulseOn, CFG.pulseOff), data]);
  }

  console.log(`[agent] print | ${data.length} bytes | printer: ${printerName || '(default)'} | drawer: ${kick}`);
  try {
    // ถ้าระบุชื่อเครื่องพิมพ์ → พิมพ์ผ่าน Windows spooler เสมอ
    // ถ้าไม่ระบุ → ใช้ backend ตาม config (network/windows/serial)
    let result;
    if (printerName) {
      result = await sendWindows(data, printerName);
      // จำเครื่องที่พิมพ์สำเร็จไว้ใน config → ใช้กับการเปิดลิ้นชัก/ทดสอบ ที่ไม่ระบุชื่อเครื่อง
      if (CFG.printer.windowsName !== printerName || CFG.printer.type !== 'windows') {
        CFG.printer.type        = 'windows';
        CFG.printer.windowsName = printerName;
        fs.writeFileSync(CONFIG_PATH, JSON.stringify(CFG, null, 2), 'utf8');
        console.log(`[agent] จำเครื่องพิมพ์ "${printerName}" ไว้ใน config แล้ว`);
      }
    } else {
      switch (CFG.printer.type) {
        case 'network': result = await sendNetwork(data); break;
        case 'serial':  result = await sendSerial(data);  break;
        default:        result = await sendWindows(data); break;
      }
    }
    res.json({ ok: true, result });
  } catch (err) {
    console.error('[agent] print ERROR:', err.message);
    res.status(500).json({ ok: false, error: err.message });
  }
});

/** เปิดลิ้นชัก */
app.post('/open-drawer', async (req, res) => {
  const { triggeredBy = 'unknown', reason = '' } = req.body;
  console.log(`[agent] open-drawer | by: ${triggeredBy} | reason: ${reason} | ${new Date().toLocaleString('th-TH')}`);
  try {
    const result = await openDrawer();
    console.log('[agent] drawer opened:', result);
    res.json({ ok: true, result });
  } catch (err) {
    console.error('[agent] ERROR:', err.message);
    res.status(500).json({ ok: false, error: err.message });
  }
});

/** โหลด config ปัจจุบัน */
app.get('/config', (req, res) => {
  const safe = { ...CFG };
  delete safe.token; // ไม่ส่ง token กลับ
  res.json(safe);
});

/** อัพเดต config */
app.post('/config', (req, res) => {
  const allowed = ['printer', 'drawerPin', 'pulseOn', 'pulseOff'];
  for (const key of allowed) {
    if (req.body[key] !== undefined) CFG[key] = req.body[key];
  }
  fs.writeFileSync(CONFIG_PATH, JSON.stringify(CFG, null, 2), 'utf8');
  console.log('[agent] config updated:', JSON.stringify(req.body));
  res.json({ ok: true });
});

/** ทดสอบ pulse โดยไม่เปิดลิ้นชักจริง (ส่ง beep แทน) */
app.post('/test', async (req, res) => {
  console.log('[agent] test request');
  try {
    // ส่งแค่ initialize command เพื่อทดสอบการเชื่อมต่อ ไม่เปิดลิ้นชัก
    const cmd = Buffer.from([0x1B, 0x40]); // ESC @ only
    const overrideName = (req.body && req.body.printerName) || '';
    let result;
    if (overrideName) {
      result = await sendWindows(cmd, overrideName);
    } else switch (CFG.printer.type) {
      case 'network': result = await sendNetwork(cmd); break;
      case 'windows': result = await sendWindows(cmd); break;
      case 'serial':  result = await sendSerial(cmd);  break;
    }
    res.json({ ok: true, result });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
});

// ── Start ─────────────────────────────────────────────────────────────────────
const PORT = CFG.port || 9991;

// auto-detect: ถ้าใช้ windows printer แต่ยังไม่ได้ตั้งชื่อ → เดาเครื่องพิมพ์ความร้อนให้
async function autoDetectPrinter() {
  const p = CFG.printer;
  // config เริ่มต้นเป็น network → 127.0.0.1:9100 ซึ่งไม่มีจริงบนเครื่องส่วนใหญ่
  // (อาการ: connect ECONNREFUSED 127.0.0.1:9100) — ถือว่ายังไม่ได้ตั้งค่า ให้สลับเป็น windows
  const untouchedNetwork = p.type === 'network' && (!p.host || p.host === '127.0.0.1');
  if (!(untouchedNetwork || (p.type === 'windows' && !p.windowsName))) return;
  try {
    const names = await listPrinters();
    const guess = guessThermalPrinter(names);
    if (guess) {
      p.type        = 'windows';
      p.windowsName = guess;
      fs.writeFileSync(CONFIG_PATH, JSON.stringify(CFG, null, 2), 'utf8');
      console.log(`  🔍 Auto-detect: พบเครื่องพิมพ์ "${guess}" — ตั้งค่าให้อัตโนมัติ (windows)`);
    } else if (names.length) {
      // เดาไม่ได้ว่าตัวไหนเป็นปริ้นสลิป แต่ก็อย่าค้างอยู่ที่ network 9100 ซึ่งไม่มีจริง
      if (untouchedNetwork) {
        p.type = 'windows';
        fs.writeFileSync(CONFIG_PATH, JSON.stringify(CFG, null, 2), 'utf8');
      }
      console.log('  🔍 เครื่องพิมพ์ที่พบ:', names.join(', '));
      console.log('     (ยังเดาไม่ได้ว่าตัวไหนเป็นปริ้นสลิป — เลือกได้ในหน้า Settings ของ POS)');
    }
  } catch { /* ข้าม */ }
}

server.on('error', (err) => {
  if (err.code === 'EADDRINUSE') {
    console.error(`\n[agent] พอร์ต ${PORT} ถูกใช้งานอยู่แล้ว — อาจมี agent รันอยู่ก่อนหน้า`);
    console.error('        ปิดตัวเก่าก่อน หรือแก้ "port" ใน config.json\n');
  } else {
    console.error('[agent] server error:', err.message);
  }
  process.exit(1);
});

server.listen(PORT, '127.0.0.1', () => {
  console.log('');
  console.log('╔══════════════════════════════════════════════╗');
  console.log('║     POS Cash Drawer / Print Agent v1.1       ║');
  console.log('╚══════════════════════════════════════════════╝');
  console.log(`  🟢 รัน: http://127.0.0.1:${PORT}`);
  console.log(`  🔑 Token: ${CFG.token}`);
  console.log(`  🖨️  Printer: ${CFG.printer.type} — ${
    CFG.printer.type === 'network'  ? `${CFG.printer.host}:${CFG.printer.tcpPort}` :
    CFG.printer.type === 'windows'  ? (CFG.printer.windowsName || '(ยังไม่ตั้งค่า)') :
    CFG.printer.serialPort
  }`);
  console.log('');
  console.log('  เปิดหน้า POS Settings แล้วกด "ตั้งค่าอัตโนมัติ" — ไม่ต้อง copy token เอง');
  console.log('  แก้ไขปริ้นเตอร์ได้ที่ config.json หรือหน้า Settings ของ POS');
  console.log('');
  autoDetectPrinter();
});
