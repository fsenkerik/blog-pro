# Blog Pro - Instalační průvodce

## 🎯 Co je Blog Pro?

Profesionální blog systém s:
- ✅ MySQL databází
- ✅ Pokročilou bezpečností (CSRF, XSS, rate limiting)
- ✅ SEO optimalizací (meta tagy, slugs, sitemap)
- ✅ WYSIWYG editorem (Quill - bez API klíče!)
- ✅ Automatickým backupem
- ✅ Role systémem (Admin/Editor)
- ✅ Draft režimem
- ✅ Vyhledáváním
- ✅ Image optimalizací

---

## 📋 Požadavky

- MAMP (nebo jiný lokální server s PHP 7.4+ a MySQL 5.7+)
- PHP extensions: PDO, GD, mbstring
- 50MB volného místa

---

## 🚀 Instalace - Krok za krokem

### 1. Zkopírujte soubory

**Mac:**
```
/Applications/MAMP/htdocs/blog/
```

**Windows:**
```
C:\MAMP\htdocs\blog\
```

### 2. Spusťte MAMP

1. Otevřete MAMP
2. Klikněte "Start Servers"
3. Zkontrolujte, že běží MySQL a Apache

### 3. Vytvořte databázi

1. Otevřete prohlížeč: `http://localhost:8888/phpMyAdmin`
2. Přihlaste se: 
   - Username: `root`
   - Password: `root`
3. Klikněte na "Import"
4. Vyberte soubor `database.sql` ze složky blog
5. Klikněte "Provedení"

✅ **Databáze vytvořena!**

### 4. Nastavte konfiguraci

Otevřete soubor `config.php` a zkontrolujte/upravte:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'blog_pro');
define('DB_USER', 'root');
define('DB_PASS', 'root'); // MAMP výchozí heslo

// URLs - ZMĚŇTE pokud používáte jiný port nebo složku!
define('BASE_URL', 'http://localhost:8888/blog/');
```

### 5. Nastavte oprávnění

Ujistěte se, že tyto složky jsou zapisovatelné:
- `uploads/` (CHMOD 755)
- `backups/` (CHMOD 755)

### 6. Otevřete admin panel

```
http://localhost:8888/blog/admin/login.php
```

**Přihlašovací údaje:**
- Admin: `admin1` / `heslo123`
- Editor: `editor1` / `heslo456`

⚠️ **DŮLEŽITÉ: Změňte hesla v Nastavení!**

---

## 📝 První kroky

### 1. Změňte heslo

1. Přihlaste se
2. Klikněte na své jméno vpravo nahoře → Nastavení
3. Změňte heslo

### 2. Vytvořte první příspěvek

1. Klikněte "Nový příspěvek"
2. Vyplňte:
   - **Titulek**
   - **Kategorie** (Akce 2025, Novinky, atd.)
   - **Obsah** (použijte editor s tlačítky)
   - **Obrázek** (volitelné)
   - **SEO pole** (meta title, description)
3. Vyberte stav:
   - **Koncept** = neukazuje se na webu
   - **Publikováno** = viditelné na webu
4. Klikněte "Uložit"

### 3. Zobrazení na webu

Otevřete:
```
http://localhost:8888/blog/
```

---

## 🔧 Pokročilé nastavení

### Změna vzhledu

Upravte soubor:
```
assets/css/admin.css    # Admin panel
assets/css/frontend.css # Web
```

### Změna SEO údajů

V `config.php`:
```php
define('SITE_NAME', 'Váš blog');
define('SITE_DESCRIPTION', 'Popis vašeho blogu');
```

### Automatický backup

Systém automaticky vytváří zálohy. Nastavení:
```php
define('AUTO_BACKUP_ENABLED', true);
define('BACKUP_RETENTION_DAYS', 30);
```

---

## 🌐 Nahrání na produkci (Forpsi)

### 1. Upravte config.php

```php
// Database - získejte údaje z Forpsi
define('DB_HOST', 'mysql.example.cz');
define('DB_NAME', 'vase_db');
define('DB_USER', 'vase_username');
define('DB_PASS', 'vase_heslo');

// URLs
define('BASE_URL', 'https://vasedomena.cz/');

// Error reporting - VYPNOUT na produkci!
error_reporting(0);
ini_set('display_errors', 0);
```

### 2. Vytvořte databázi na Forpsi

1. Přihlaste se do Forpsi
2. Vytvořte MySQL databázi
3. Nahrajte `database.sql` přes phpMyAdmin

### 3. Nahrajte soubory

Přes FTP nahrajte všechny soubory

### 4. Nastavte oprávnění

- `uploads/` → 755
- `backups/` → 755

### 5. Generujte sitemap

```
https://vasedomena.cz/admin/settings.php
→ Generovat Sitemap
```

---

## ❓ Řešení problémů

### Problém: "Nelze se připojit k databázi"

**Řešení:**
1. Zkontrolujte MySQL v MAMPu (běží?)
2. Zkontrolujte údaje v `config.php`
3. Zkontrolujte, zda existuje databáze `blog_pro`

### Problém: "Stránka se nenačítá"

**Řešení:**
1. Zkontrolujte Apache v MAMPu (běží?)
2. Zkontrolujte `BASE_URL` v `config.php`
3. Zkontrolujte port (obvykle 8888)

### Problém: "Nelze nahrát obrázek"

**Řešení:**
1. Zkontrolujte oprávnění složky `uploads/` (755)
2. Zkontrolujte velikost (max 5MB)
3. Zkontrolujte formát (jpg, png, gif, webp)

### Problém: "CSRF token error"

**Řešení:**
1. Vyčistěte cache prohlížeče
2. Odhlaste se a přihlaste znovu
3. Zkontrolujte, zda fungují sessions

---

## 📚 Struktura databáze

```
users          - Uživatelé
posts          - Příspěvky
categories     - Kategorie
sessions       - Aktivní sessions
error_logs     - Chybové logy
backups        - Seznam záloh
```

---

## 🆘 Potřebujete pomoc?

1. Zkontrolujte error.log v hlavní složce
2. Zkontrolujte error_logs tabulku v databázi
3. Zapněte error reporting v config.php:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

---

## ✨ Hotovo!

Gratulujeme! Máte profesionální blog systém 🎉

**Užijte si blogování!**
