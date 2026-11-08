POS MANAGEMENT SYSTEM - XAMPP INSTALLATION

REQUIREMENTS
- XAMPP with Apache and MySQL
- PHP 8.0+ recommended
- Existing MySQL database named: pos_system
- Existing tables: users, categories, products, sales, sale_items

INSTALLATION
1. Extract the "pos_system" folder into C:\xampp\htdocs\
2. Open XAMPP Control Panel.
3. Start Apache and MySQL.
4. Confirm phpMyAdmin has a database named pos_system and the five required tables.
5. Open config/db.php and check the MySQL username/password.
   Default XAMPP settings: username=root, password=blank.
6. Visit http://localhost/pos_system/setup_admin.php
7. Create the first administrator account (password must be at least 8 characters).
8. Login at http://localhost/pos_system/login.php

FEATURES
- Admin dashboard with today's sales and low-stock count
- Product add/edit/delete and stock management
- Category management
- Admin and cashier account creation
- Cashier checkout with stock validation
- Sales history
- Printable receipt
- Password hashing, prepared SQL statements, session login, and CSRF tokens

IMPORTANT
- Do NOT run database/schema.sql if your five tables already exist; it is included as a reference/setup script.
- The system uses your existing database. It does not include or overwrite your database data.
- Delete setup_admin.php after creating your first admin account, or restrict access to it.
- Delete test_db.php if you create one; this project does not need it.
- This is a local learning/project starter. Before public deployment, add HTTPS, backups,
  stricter operational controls, and a security review.
- Bootstrap is loaded from a CDN, so an internet connection is needed for its styling.
