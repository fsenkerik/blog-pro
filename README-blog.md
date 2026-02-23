# Blog Pro - Profesionální blog systém

**Verze:** 1.0  
**Autor:** Vytvořeno pro profesionální použití  
**Licence:** Proprietární

---

## 🎯 Hlavní funkce

### ✅ Databáze & Výkon
- MySQL databáze s prepared statements
- PDO pro bezpečné dotazy
- Indexy pro rychlé vyhledávání
- Transakce pro data integrity

### ✅ Bezpečnost
- **CSRF ochrana** - tokeny pro každý formulář
- **XSS prevence** - sanitizace vstupů a výstupů
- **Rate limiting** - ochrana proti brute force
- **Session management** - bezpečné session v databázi
- **Password hashing** - bcrypt
- **SQL injection ochrana** - prepared statements

### ✅ SEO Optimalizace
- Meta tagy (title, description, keywords)
- Open Graph pro sociální sítě
- Twitter Cards
- Přátelské URL (slugs)
- Automatická sitemap.xml
- Robots.txt generování
- Strukturovaná data (Schema.org)

### ✅ Správa obsahu
- WYSIWYG editor (Quill - bez API klíče)
- Draft režim (koncept/publikováno)
- Kategorie
- Upload a optimalizace obrázků
- Automatické generování excerptů
- Fulltext vyhledávání

### ✅ Uživatelé & Role
- Admin - plná práva
- Editor - omezená práva
- Změna hesla
- Session timeout
- Logování aktivit

### ✅ Backup systém
- Automatické zálohy databáze
- Manuální zálohy
- Stahování záloh
- Automatické čištění starých záloh

### ✅ Další funkce
- Image optimalizace (resize, komprese)
- Error logging
- Responzivní design
- Moderní UI

---

## 📋 Technické požadavky

- **PHP:** 7.4+
- **MySQL:** 5.7+
- **Extensions:** PDO, GD, mbstring
- **Webserver:** Apache nebo Nginx

---

## 📦 Obsah balíčku

```
blog-pro/
├── admin/              # Admin panel
├── assets/             # CSS, JS, obrázky
├── backups/            # Zálohy databáze
├── includes/           # PHP třídy
├── uploads/            # Nahrané obrázky
├── config.php          # Konfigurace
├── database.sql        # SQL pro vytvoření databáze
├── INSTALL.md          # Instalační průvodce
└── README.md           # Tento soubor
```

---

## 🚀 Rychlý start

1. **Rozbalte** soubory do MAMPu
2. **Importujte** database.sql do MySQL
3. **Upravte** config.php (DB údaje, URLs)
4. **Otevřete** admin/login.php
5. **Přihlaste se:** admin1 / heslo123

**Podrobný návod:** Viz INSTALL.md

---

## 🔐 Výchozí účty

| Username | Heslo | Role |
|----------|-------|------|
| admin1   | heslo123 | Admin |
| editor1  | heslo456 | Editor |

⚠️ **ZMĚŇTE HESLA PO PRVNÍM PŘIHLÁŠENÍ!**

---

## 🎨 Přizpůsobení

### Vzhled
- `assets/css/admin.css` - Admin panel
- `assets/css/frontend.css` - Web

### SEO
- `config.php` - SITE_NAME, SITE_DESCRIPTION

### Funkce
- `config.php` - všechna nastavení

---

## 📊 Databázové třídy

| Třída | Účel |
|-------|------|
| Database | PDO connection, prepared statements |
| Auth | Přihlášení, sessions, rate limiting |
| Post | CRUD příspěvků, vyhledávání |
| Category | Správa kategorií |
| User | Správa uživatelů |
| Upload | Upload a optimalizace obrázků |
| Security | CSRF, XSS ochrana |
| SEO | Meta tagy, sitemap |
| Backup | Zálohy databáze |

---

## 🛡️ Bezpečnostní pravidla

1. **Změňte výchozí hesla** ihned po instalaci
2. **Použijte HTTPS** na produkci
3. **Vypněte error display** na produkci
4. **Pravidelně zálohujte** databázi
5. **Aktualizujte PHP** a MySQL
6. **Omezte přístup** ke složkám includes/, backups/

---

## 📈 Výkon

- **Indexy** na všech důležitých sloupcích
- **Prepared statements** - rychlejší a bezpečnější
- **Image optimization** - automatická komprese
- **Session cleanup** - automatické čištění

---

## 🐛 Ladění

### Zapnout error reporting:

V `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Error log:

- Soubor: `error.log` v hlavní složce
- Databáze: tabulka `error_logs`

---

## 📞 Podpora

Pro technické problémy:
1. Přečtěte si INSTALL.md
2. Zkontrolujte error.log
3. Zkontrolujte error_logs v databázi

---

## 📄 Licence

Tento software je určen pro komerční použití.  
Všechna práva vyhrazena.

---

## ✨ Změnový log

### v1.0 (2026-01-22)
- Prvn í vydání
- MySQL databáze
- Bezpečnost (CSRF, XSS, rate limiting)
- SEO optimalizace
- WYSIWYG editor
- Backup systém
- Role systém
- Draft režim
- Vyhledávání
- Image optimalizace

---

**Vytvořeno s ❤️ pro profesionální použití**
