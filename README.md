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

4. **Visit** `http://localhost/foodorder/` in your browser.

### Using PHP Built-in Server (Dev)
```bash
cd foodorder
php -S localhost:8080
```
Then open: http://localhost:8080



## 📸 Pages Overview

- `/index.html` — Beautiful landing page
- `/register.php` — Two-column registration with photo upload
- `/login.php` — Clean sign-in with validation
- `/dashboard.php` — Full app: sidebar, menu grid, cart drawer, orders, profile
- `/logout.php` — Signs out and redirects
