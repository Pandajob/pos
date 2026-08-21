# POS System - Setup Guide

## ขั้นตอนการติดตั้ง (XAMPP)

### 1. ติดตั้ง CodeIgniter 4

```bash
# ดาวน์โหลด CI4 ผ่าน Composer
composer create-project codeigniter4/appstarter pos-temp

# หรือดาวน์โหลด zip จาก https://codeigniter.com/download
```

### 2. วางไฟล์โปรเจกต์

```
C:/xampp/htdocs/pos/          ← ทั้งหมดของ CI4
├── app/                       ← copy folder นี้จาก Project/POS/app/
├── public/
├── system/
├── .env                       ← copy จาก Project/POS/.env
└── ...
```

**สรุป:** copy `app/` folder และ `.env` จาก `C:\Users\Pandajob\Documents\Project\POS\`
ไปทับใน CI4 ที่ download มา แล้ววางที่ `C:\xampp\htdocs\pos\`

### 3. สร้าง Database

เปิด phpMyAdmin → http://localhost/phpmyadmin  
Import ไฟล์ → `database.sql`

หรือรัน SQL ใน phpMyAdmin:
```sql
SOURCE C:/Users/Pandajob/Documents/Project/POS/database.sql;
```

### 4. แก้ไข .env

เปิดไฟล์ `.env` ใน htdocs/pos/ และแก้ไข:
```
app.baseURL = 'http://localhost/pos/public/'
database.default.password =        ← ใส่รหัสผ่าน MySQL ถ้ามี
```

### 5. ตั้งค่า writable permissions (ถ้าจำเป็น)

```bash
chmod -R 777 writable/   # Linux/Mac เท่านั้น
# Windows ไม่จำเป็น
```

### 6. เปิดเว็บ

```
http://localhost/pos/public/
```

---

## โครงสร้างไฟล์ที่สร้าง

```
app/
├── Config/
│   └── Routes.php              ← เส้นทาง URL ทั้งหมด
├── Controllers/
│   ├── Dashboard.php           ← หน้าแดชบอร์ด
│   ├── Products.php            ← จัดการสินค้า (CRUD)
│   ├── Sales.php               ← หน้าขาย + cart API
│   ├── Receipt.php             ← ใบเสร็จ
│   └── Reports.php             ← รายงานยอดขาย
├── Database/Migrations/        ← สร้าง table อัตโนมัติ
├── Models/
│   ├── ProductModel.php
│   ├── SaleModel.php
│   └── SaleItemModel.php
└── Views/
    ├── templates/header.php    ← layout header (sidebar)
    ├── templates/footer.php    ← layout footer (scripts)
    ├── dashboard/index.php
    ├── products/{index,create,edit}.php
    ├── sales/index.php         ← POS หน้าขายหลัก
    ├── receipt/view.php        ← ใบเสร็จ (print-ready)
    └── reports/index.php       ← รายงานรายวัน
```

---

## ฟีเจอร์ทั้งหมด

| หน้า | URL | ฟีเจอร์ |
|------|-----|---------|
| แดชบอร์ด | `/` | ยอดขายวันนี้, สต๊อกต่ำ, บิลล่าสุด |
| หน้าขาย | `/sales` | สแกนบาร์โค้ด, ค้นหา, ตะกร้า, ชำระเงิน |
| สินค้า | `/products` | เพิ่ม/แก้ไข/ลบ สินค้า + บาร์โค้ด |
| ใบเสร็จ | `/receipt/{id}` | ดูและพิมพ์ใบเสร็จ |
| รายงาน | `/reports/daily` | ยอดขายรายวัน, สินค้าขายดี |

---

## บาร์โค้ด Scanner (USB)

USB Barcode Scanner ทำงานเหมือน keyboard - พิมพ์ตัวเลขเร็วมากแล้วกด Enter

ระบบรองรับ 2 แบบ:
1. **สแกนที่ช่อง input** - โฟกัสที่ช่องบาร์โค้ดแล้วสแกน
2. **สแกนทุกที่** - แม้ไม่ได้โฟกัส input ก็รับบาร์โค้ดได้ (global keydown listener)

สินค้าที่พบจะถูกเพิ่มในตะกร้าทันที (quantity = 1 อัตโนมัติ)

---

## รัน Migration (วิธีอื่น)

```bash
cd C:/xampp/htdocs/pos
php spark migrate
```

---

## Composer (ถ้าใช้)

```bash
composer install
```

ถ้า copy ทั้งโฟลเดอร์ POS (รวม `vendor/`) ไป สิ่งที่ต้องทำในเครื่องใหม่มีแค่:

1. **ติดตั้ง XAMPP**
2. **วาง POS** ใน `C:\xampp\htdocs\`
3. **เปิด** `extension=intl` ใน `php.ini`
4. **Import** ไฟล์ `pos_db.sql` ใน phpMyAdmin
5. **Restart Apache**

เท่านั้นเลยครับ เปิด `http://localhost/pos/public/` ใช้งานได้เลย