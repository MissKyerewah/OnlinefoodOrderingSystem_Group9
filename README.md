# 🍕 Sheriffs — Online Food Ordering System

A professional PHP food ordering web application with no database required. All data is stored in flat JSON files.

---

## 📁 Project Structure

```
foodorder/
├── index.html              ← Homepage (public landing page)
├── register.php            ← User registration (with profile photo upload)
├── login.php               ← User sign-in
├── dashboard.php           ← Main app (menu, cart, orders, profile)
├── logout.php              ← Session destroy + redirect
├── .htaccess               ← Apache config
├── includes/
│   ├── auth.php            ← Session, user CRUD helpers
│   └── menu.php            ← Menu items & categories
├── uploads/
│   └── profiles/           ← User profile pictures (auto-created)
└── data/
    └── users.json          ← User accounts + cart + orders (auto-created)
```

---

## 🚀 Setup Instructions

### Requirements
- PHP 7.4+ (PHP 8.x recommended)
- Apache with `mod_rewrite` enabled
- Write permissions on the project folder

### Steps

1. **Copy the project** to your web server's root or a subdirectory:
   ```bash
   cp -r foodorder/ /var/www/html/
   ```

2. **Set permissions** so PHP can write uploads and data:
   ```bash
   chmod -R 755 foodorder/
   chmod -R 777 foodorder/uploads/
   chmod -R 777 foodorder/data/
   ```
   *(Or use `chown www-data:www-data` on Linux/Apache)*

3. **Enable mod_rewrite** (Apache):
   ```bash
   a2enmod rewrite
   service apache2 restart
   ```

4. **Visit** `http://localhost/foodorder/` in your browser.

### Using PHP Built-in Server (Dev)
```bash
cd foodorder
php -S localhost:8080
```
Then open: http://localhost:8080

---

## 🎯 Features

| Feature | Details |
|---|---|
| **Homepage** | Landing page with hero, features, menu preview, how-it-works |
| **Registration** | Name, email, password, phone + profile picture upload |
| **Profile Photos** | Uploaded to `uploads/profiles/`, displayed throughout the app |
| **Login/Logout** | Session-based authentication, secure password hashing |
| **Dashboard** | Menu browse, category filter, cart, orders, profile |
| **Cart** | Add/remove/update quantities, live totals, sliding drawer |
| **Order Placement** | One-click checkout, order confirmation modal |
| **Order History** | All past orders with items, totals, dates |
| **No Database** | Everything stored in `data/users.json` |

---

## 🔒 Security Notes

- Passwords hashed with `password_hash()` (bcrypt)
- Sessions use `httponly` cookies
- File uploads validated by MIME type and size (max 3MB)
- JSON data file is protected from direct browser access via `.htaccess`
- Input is sanitized with `htmlspecialchars()` throughout

---

## 📸 Pages Overview

- `/index.html` — Beautiful landing page
- `/register.php` — Two-column registration with photo upload
- `/login.php` — Clean sign-in with validation
- `/dashboard.php` — Full app: sidebar, menu grid, cart drawer, orders, profile
- `/logout.php` — Signs out and redirects

---

Made with ❤️ — Sheriffs © 2025
