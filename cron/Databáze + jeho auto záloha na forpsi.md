# 🚀 DEPLOY NA FORPSI - KOMPLETNÍ NÁVOD

## 📋 PŘED NAHRÁNÍM

### 1. Příprava souborů
```bash
# Zabal celý projekt (BEZ těchto složek):
- node_modules/
- .git/
- .env (pokud máš)
```

### 2. Export databáze
```sql
-- V phpMyAdmin (localhost):
1. Vyber databázi blog_pro
2. Export → Quick → SQL → Go
3. Ulož jako: blog_pro_export.sql
```

---

## 🌐 FORPSI - NOVÝ HOSTING

### Krok 1: Objednej hosting
1. Přihlaš se na [forpsi.cz](https://www.forpsi.cz)
2. Webhosting → **Linux Optimal** (min. 5 GB, MySQL 8.0)
3. Vyber doménu nebo subdoménu
4. Objednávka dokončena ✅

---

## 📤 NAHRÁNÍ SOUBORŮ

### Krok 2: FTP připojení

**FTP údaje najdeš:**
Zákaznická zóna → Webhosting → Detail → FTP přístup

**Použij FileZilla nebo WinSCP:**
```
Server: ftp.tvojadomena.cz
Username: tvuj_ftp_username
Password: tvoje_ftp_heslo
Port: 21
```

### Krok 3: Nahraj soubory
```
Místní PC                  →  Forpsi server
blog-pro/                  →  /www/
├── admin/                 →  /www/admin/
├── includes/              →  /www/includes/
├── public/                →  /www/public/
├── uploads/               →  /www/uploads/
├── backups/               →  /www/backups/
├── cron/                  →  /www/cron/
└── ...                    →  /www/...
```

**⚠️ Důležité složky - nastav práva:**
```
uploads/  → 755 nebo 777 (zapisovatelná)
backups/  → 755 nebo 777 (zapisovatelná)
```

---

## 🗄️ DATABÁZE

### Krok 4: Vytvoř MySQL databázi

**V Zákaznické zóně Forpsi:**
1. MySQL databáze → **Vytvořit novou databázi**
2. Název: např. `db12345_blogpro`
3. Uživatel: např. `db12345_user`
4. Heslo: (vygeneruj silné heslo)
5. **Ulož údaje!** ✍️

### Krok 5: Import databáze

**Přes phpMyAdmin (Forpsi):**
1. Zákaznická zóna → phpMyAdmin → Otevřít
2. Vyber svou databázi vlevo
3. Import → Choose File → `blog_pro_export.sql`
4. Encoding: `utf8mb4`
5. Go → ✅ Import successful

---

## ⚙️ KONFIGURACE

### Krok 6: Uprav config.php

**Připoj se přes FTP → otevři `/www/config.php`:**

```php
// ===== DATABÁZE =====
define('DB_HOST', 'mysql1234.forpsi.com'); // ← ZMĚŇ!
define('DB_NAME', 'db12345_blogpro');      // ← ZMĚŇ!
define('DB_USER', 'db12345_user');         // ← ZMĚŇ!
define('DB_PASS', 'tvoje_heslo_z_kroku_4'); // ← ZMĚŇ!
define('DB_CHARSET', 'utf8mb4');

// ===== URL =====
define('SITE_URL', 'https://tvojadomena.cz/'); // ← ZMĚŇ!
define('ADMIN_URL', SITE_URL . 'admin/');

// Zbytek nech stejný...
```

**💾 Ulož soubor**

---

## 🔒 ZABEZPEČENÍ

### Krok 7: .htaccess ochrana

**Vytvoř `/www/.htaccess`:**
```apache
# Skrýt citlivé soubory
<FilesMatch "^(config\.php|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Ochrana proti direct access
Options -Indexes

# Přesměrování na HTTPS
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### Krok 8: Zabezpeč admin login

**V `admin/.htaccess`:**
```apache
# IP whitelist (volitelné)
<Limit GET POST>
    order deny,allow
    deny from all
    allow from 123.456.789.0  # Tvoje IP
    allow from all             # Nebo povolit všem
</Limit>
```

---

## ⏰ CRON JOB (Automatické zálohy)

### Krok 9: Nastav Cron

**V Zákaznické zóně Forpsi:**
1. Webhosting → **Cron** → Přidat nový
2. Název: `Blog Pro Auto Backup`
3. Příkaz:
   ```bash
   /usr/bin/php /data/web/virtuals/12345/virtual/www/cron/backup-scheduler.php
   ```
   *(Změní se podle tvého virtual ID - najdeš v detailu hostingu)*

4. Interval: **Každou hodinu** (`0 * * * *`)
5. Email notifikace: tvůj@email.cz (volitelné)
6. Ulož ✅

**🔍 Zjisti správnou cestu:**
Vytvoř testovací soubor `/www/test_path.php`:
```php
<?php
echo __FILE__;
?>
```
Otevři: `https://tvojadomena.cz/test_path.php`
Uvidíš cestu typu: `/data/web/virtuals/12345/virtual/www/test_path.php`

---

## ✅ TESTOVÁNÍ

### Krok 10: Ověř že vše funguje

**1. Web načte?**
```
https://tvojadomena.cz/
```
✅ Měla by se zobrazit hlavní stránka

**2. Admin funguje?**
```
https://tvojadomena.cz/admin/
```
✅ Přihlašovací formulář

**3. Databáze připojena?**
Přihlaš se do adminu → měly by se zobrazit příspěvky

**4. Obrázky fungují?**
Zkontroluj že se načítají z `/uploads/`

**5. Zálohy fungují?**
Admin → Nastavení → Zálohy → Vytvořit zálohu
✅ Měla by se stáhnout

**6. Cron běží?**
Počkej hodinu → zkontroluj v Nastavení → Zálohy
✅ Měla by se objevit automatická záloha

---

## 🐛 ČASTÉ PROBLÉMY

### ❌ "Database connection failed"
- Zkontroluj DB_HOST (mysql1234.forpsi.com)
- Zkontroluj DB_USER a DB_PASS
- Zkontroluj že databáze existuje v phpMyAdmin

### ❌ "Permission denied" při nahrávání
- Nastav práva složek: `chmod 755 uploads/`
- V FileZilla: pravý klik → File permissions → 755

### ❌ "Internal Server Error"
- Zkontroluj `.htaccess` syntaxi
- Zkontroluj PHP verzi (min. 8.0)
- Podívej se do error_log v Zákaznické zóně

### ❌ Obrázky se nenačítají
- Zkontroluj že `/uploads/` je nahrána
- Zkontroluj práva (755)
- Zkontroluj cesty v config.php (SITE_URL)

### ❌ Cron neběží
- Zkontroluj cestu k PHP (`/usr/bin/php`)
- Zkontroluj cestu k souboru (virtual path)
- Zkontroluj cron log v Zákaznické zóně

---

## 📧 NASTAVENÍ EMAILŮ (VOLITELNÉ)

### Pro reset hesla, notifikace:

**V `config.php` přidej:**
```php
// Email nastavení
define('SMTP_HOST', 'smtp.forpsi.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'info@tvojadomena.cz');
define('SMTP_PASS', 'tvoje_email_heslo');
define('FROM_EMAIL', 'info@tvojadomena.cz');
define('FROM_NAME', 'Blog Pro');
```

---

## 🔄 AKTUALIZACE V BUDOUCNU

### Jak aktualizovat web:

1. **Záloha aktuální verze**
   - Stáhni vše přes FTP
   - Export databáze v phpMyAdmin

2. **Nahrání změn**
   - Nahraj nové/změněné soubory přes FTP
   - Nepřepisuj `config.php` (má jiné údaje než lokálně!)

3. **Databázové změny**
   - Spusť SQL skripty v phpMyAdmin (pokud jsou)

---

## 📞 PODPORA FORPSI

Web: https://www.forpsi.cz/podpora/
Email: info@forpsi.cz
Tel: +420 606 606 050

---

## ✅ CHECKLIST PŘED SPUŠTĚNÍM

- [ ] Soubory nahrány přes FTP
- [ ] Databáze vytvořena a naimportována
- [ ] config.php upraven (DB údaje + URL)
- [ ] .htaccess vytvořen
- [ ] Složky uploads/ a backups/ zapisovatelné (755)
- [ ] Cron job nastaven
- [ ] Web načte (frontend)
- [ ] Admin funguje (přihlášení)
- [ ] Obrázky se zobrazují
- [ ] Zálohy lze vytvořit
- [ ] SSL certifikát aktivní (HTTPS)

---

🎉 **HOTOVO! Web je na produkci!**
