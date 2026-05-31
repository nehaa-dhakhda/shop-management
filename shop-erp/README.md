# ShopERP — Shop Management System

A complete, clean ERP for small shops built with **PHP + MySQL (XAMPP)**, Bootstrap 5, and Chart.js.

---

## 📦 Modules
| Module | Features |
|---|---|
| **Dashboard** | Stats, monthly sales chart, recent sales, low-stock alerts |
| **Products** | Add/Edit/Delete, categories, stock, low-stock warning |
| **Sales / POS** | Click-to-cart POS, discount, payment method, sales history |
| **Customers** | Add/Edit/Delete customers, search |
| **Purchases** | Record stock-in, auto-update inventory |
| **Suppliers** | Manage suppliers |
| **Reports** | Date-range reports, revenue, profit estimate, top products |

---

## ⚙️ Setup (XAMPP)

### Step 1 — Install XAMPP
Download from https://www.apachefriends.org and install. Start **Apache** and **MySQL**.

### Step 2 — Copy files
Copy the entire `shop-erp` folder to:
```
C:\xampp\htdocs\shop-erp\     (Windows)
/opt/lampp/htdocs/shop-erp/   (Linux)
```

### Step 3 — Import Database
1. Open **http://localhost/phpmyadmin**
2. Click **"New"** → create database `shop_erp` (or let the SQL do it)
3. Click **Import** → choose `database.sql` → click **Go**

### Step 4 — Configure DB (if needed)
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // your MySQL password
define('DB_NAME', 'shop_erp');
```

### Step 5 — Open in Browser
```
http://localhost/shop-erp/
```

---

## 🔑 Default Login
| Field | Value |
|---|---|
| Email | admin@shop.com |
| Password | password |

---

## 📁 File Structure
```
shop-erp/
├── index.php              # Dashboard
├── login.php              # Login page
├── logout.php
├── database.sql           # DB schema + seed data
├── includes/
│   ├── config.php         # DB config + helpers
│   ├── header.php         # Sidebar + topbar layout
│   └── footer.php
├── modules/
│   ├── products.php
│   ├── sales.php          # POS
│   ├── customers.php
│   ├── purchases.php
│   ├── suppliers.php
│   └── reports.php
└── assets/
    ├── css/app.css
    └── js/app.js
```

---

## 🛠️ Tech Stack
- PHP 7.4+ (PDO + prepared statements)
- MySQL via XAMPP
- Bootstrap 5.3
- Bootstrap Icons
- Chart.js 4
- Google Fonts (Plus Jakarta Sans)
- Vanilla JavaScript
