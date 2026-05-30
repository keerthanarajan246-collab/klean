# Klean — Optimized Structured Online Learning Platform
 
Klean is a premium, full-stack Udemy-like online learning platform built as a **Single PHP File (`index.php`)** backed by a **PostgreSQL Database**.
 
This application provides a zero-fluff, highly structural classroom layout featuring modern aesthetics, smooth gradients, responsive navigation grids, glassmorphism UI widgets, CSS-drawn earnings charts, secure checkout transitions, and real-time database integrations.
 
---
 
## 🚀 Key Features
 
- **Auto-Schema Setup**: Zero manual database configurations! The platform connects to your local PostgreSQL host and automatically creates the database (`klean_db`) and all 16 tables with foreign keys and cascade rules on the first run.
- **Auto-Seed Engine**: Automatically hydrates the database with pre-configured mock data, including categories, courses, lessons, active student/instructor accounts, wishlists, shopping carts, coupons, reviews, and notes.
- **Secured AJAX Router**: Processes all form submissions and transactions (registration, login, cart changes, bookmarks, note-taking, and review posting) via POST/GET fetch requests, validating **CSRF tokens** and sanitizing inputs.
- **Snappy Views Manager (14 views in 1 file)**: Snappy Single-Page Application (SPA) view switching, combined with standard server-side deep-linking page requests (e.g. `?page=courses`, `?page=player&course_id=1`).
- **Interactive Player Theater**: Features a mock video player simulator, course outline checklist widgets, progress indicators, and a debounced auto-saving notebook saved in the database.
- **Instructor Earnings Tracker**: Real-time counters showing students enrolled and gross revenue (70% share) mapped alongside a custom monthly income column bar chart rendered in pure CSS.
- **Notifications & Badges**: Bell icon drops unread messages fetched dynamically from the database. Complete 100% of any course to instantly generate a verified cryptographically unique completion certificate.
---
 
## 🛠️ Technology Stack
 
| Layer | Technology |
|-------|-----------|
| Language | PHP 8+ (No external frameworks) |
| Database | PostgreSQL (PDO connection adapters) |
| Styling | Bootstrap 5, Bootstrap Icons, Custom CSS |
| Typography | Plus Jakarta Sans (Google Fonts) |
| Client Logic | Vanilla JavaScript (Fetch API + DOM Hydration) |
 
---
 
## 📂 PostgreSQL Relational Schema (16 Tables)
 
The platform is designed with 16 fully relational database tables:
 
| # | Table | Description |
|---|-------|-------------|
| 1 | `users` | Stores registrants (students, instructors, admins) with BCRYPT hashed passwords |
| 2 | `categories` | Groups courses into Development, Design, Business, etc. |
| 3 | `courses` | Holds curriculum details, ratings, prices, and best-seller flags |
| 4 | `sections` | Logical course segments (e.g., "HTML Fundamentals") |
| 5 | `lessons` | Course lectures linked to sections and player streams |
| 6 | `enrollments` | Maps course purchases to users |
| 7 | `progress` | Tracks completed checkmarks for each student and lesson |
| 8 | `cart` | Tracks courses added to the shopping cart |
| 9 | `wishlist` | Tracks courses bookmarked |
| 10 | `payments` | Logs transactions, discount totals, order references, and payment methods |
| 11 | `payment_items` | Maps purchased course costs to payment order logs |
| 12 | `reviews` | Stores course ratings (1 to 5 stars) and student comments |
| 13 | `coupons` | Stores discount codes (e.g., `KLEAN20` offering 20% off) |
| 14 | `certificates` | Generates certified unique reference numbers for course graduates |
| 15 | `notes` | Holds notebook drafts written in the course player (supports auto-save to DB) |
| 16 | `notifications` | Keeps alert messages shown in the navigation bell |
 
---
 
## 💻 End-to-End Setup & How to Run
 
### Step 1: Install PostgreSQL & Web Server
 
Make sure you have an active local server environment running Apache (or equivalent) and PostgreSQL:
 
- **Web Server**: Apache via [XAMPP](https://www.apachefriends.org/) (macOS/Windows) or standard local configuration.
- **Database**: [PostgreSQL](https://www.postgresql.org/) (standard port `5432`).
- **Database GUI (Optional)**: [pgAdmin](https://www.pgadmin.org/) or DBeaver to easily query or view the schema.
 
### Step 2: Database Creation
 
> ⚡ **Zero Setup Requirement!**
> The platform is engineered to automatically detect if `klean_db` is missing, auto-connect to the system `postgres` database, execute `CREATE DATABASE klean_db`, auto-build the schemas, and seed all 16 relational tables with mock data on the very first load!
> Just ensure your PostgreSQL server is active and the credentials specified in `index.php` have database creation privileges.
 
### Step 3: Deploy Workspace Files
 
1. Create a project directory named `klean` inside your web server's document root:
   - **XAMPP**: `C:/xampp/htdocs/klean/`
   - **WampServer**: `C:/wamp64/www/klean/`
2. Place the `index.php` file inside this `klean` folder.
```
C:/xampp/htdocs/
└── klean/
    └── index.php   ← place your file here
```
 
### Step 4: Check Configuration Settings
 
Open `index.php` in a text editor and review the parameters at the top (under Section 1):
 
```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_USER', 'postgres');
define('DB_PASS', 'postgres'); // Change to your local PostgreSQL password
define('DB_NAME', 'klean_db');
```
 
### Step 5: Run the Application
 
1. Start your local **Apache** and **PostgreSQL** services.
2. Open your web browser and navigate to:
```
http://localhost/klean/
```
 
or
 
```
http://localhost/klean/index.php
```
 
3. The platform will **automatically check connection states, auto-generate `klean_db` if not found, create the tables with correct sequences, and seed the mock data**. You will be redirected straight to the Landing Page.
> ℹ️ If PostgreSQL is not active or credentials are incorrect, a styled warning card will appear on screen to help you troubleshoot.
 
---
 
## 🔑 Pre-Seeded Login Credentials
 
### 1. 🎓 Student Account
 
| Field | Value |
|-------|-------|
| Email | `alex@klean.com` |
| Password | `pass123` |
 
**Features to test:**
- View course player progress (pre-seeded at 50% for Web Development)
- Enrolled courses completed stats (pre-seeded with 1 completed course: *UI/UX Design Masterclass*)
- Click **"View Certificate"** to open a dynamic print-ready completion modal with Alex's cryptographic verification code
- Open cart, enter coupon **`KLEAN20`** to receive a 20% discount
- Open course player and write lesson notes (text saves automatically as you type!)
---
 
### 2. 👩‍🏫 Instructor Account
 
| Field | Value |
|-------|-------|
| Email | `sarah@klean.com` |
| Password | `pass123` |
 
**Features to test:**
- View gross revenue stats (preloaded student counts and revenue shares)
- Check the monthly income bar chart rendered using CSS columns
- Click **"Create Course"** to publish a new custom curriculum — it appears on the landing catalog grid instantly
---
 
### 3. 🛡️ Administrator Account
 
| Field | Value |
|-------|-------|
| Email | `admin@klean.com` |
| Password | `admin123` |
 
**Features to test:**
- View site-wide operations analytics
- Access active registrants table showing account statuses
---
 
## 🔒 Security Operations
 
### CSRF Protection
Every active `POST` transaction carries a token checked on the server side:
 
```php
// index.php — Section 5 (AJAX API Handler)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = getallheaders();
    $token = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
    
    // Fallback check for JSON payloads
    if (empty($token)) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!empty($input['csrf_token'])) {
            $token = $input['csrf_token'];
        }
    }
    
    if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode([
            'success' => false,
            'error'   => 'Security Error: CSRF validation failed.'
        ]);
        exit;
    }
}
```
 
### SQL Injection Prevention
All dynamic SQL queries are prepared and executed using PDO tokens:
 
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```
 
### Password Security
All registration and login endpoints use PHP's standard cryptographic library:
 
```php
// Hashing on signup
password_hash($password, PASSWORD_BCRYPT);
 
// Verifying on login
password_verify($inputPassword, $storedHash);
```
 
### XSS Sanitization
Captured outputs from inputs are sanitized with HTML escape mechanisms:
 
```php
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
```
 
---
 
## 🗂️ Project Structure
 
```
klean/
├── index.php        # Entire application (PHP + HTML + CSS + JS)
└── db-test.php      # Standalone PostgreSQL Database Explorer (Visual Utility)
```
 
The project includes the main application file and a custom database inspection utility:
 
```
index.php
├── Section 1  — DB Config & PDO Connection
├── Section 2  — Session Start & CSRF Token Generation
├── Section 3  — Auto Table Creation (16 tables)
├── Section 4  — Auto Seed Data
├── Section 5  — AJAX Router (handles all fetch() calls)
├── Section 6  — PHP Helper Functions
├── Section 7  — <style> (all custom CSS inline)
├── Section 8  — HTML Views (14 SPA views)
└── Section 9  — <script> (all JS inline)

db-test.php          # Visual utility to inspect database status, tables, and rows
```
 
---
 
## 🎯 Available Views (14 SPA Views)
 
| View ID | Page |
|---------|------|
| `landing-view` | Home / Landing Page |
| `login-view` | Login |
| `signup-view` | Sign Up |
| `courses-view` | Course Catalog + Filters |
| `course-detail-view` | Course Detail Page |
| `cart-view` | Shopping Cart |
| `checkout-view` | Secure Payment Checkout Room |
| `success-view` | Secure Transaction Success Page |
| `dashboard-view` | Student Classroom Dashboard |
| `player-view` | Course Outline Video Player |
| `instructor-view` | Instructor Portal Dashboard |
| `profile-view` | Personal Settings |
| `wishlist-view` | Bookmarks Wishlist |
| `admin-view` | Global Operations Panel |
 
---

## 🛠️ Recent Platform Enhancements (v2.2)

We have recently upgraded the platform with several modern features, bug fixes, and layout alignments to create an elite user experience:

### 1. Standalone Database Explorer (`db-test.php`)
A custom, beautifully styled web-based PostgreSQL browser. Visit `http://localhost/klean/db-test.php` in your browser to:
- Test and debug local PostgreSQL connection parameters.
- View auto-generated tables, column structures, and data types.
- Preview live table rows (up to 50 results) using a responsive, fluid design system.

### 2. Upgraded Payment Room
A fully featured multi-channel checkout room supporting:
- **Debit/Credit Card:** Auto-formats 16-digit card inputs with spaces and expiry dates with slashes. Features a visual debit card mockup that matches user input in real-time and automatically detects card brands (Visa/Mastercard).
- **UPI (Unified Payments Interface):** Dynamically formatted scan-to-pay QR code layout with quick selectors for GPay, PhonePe, and Paytm.
- **Digital Wallets:** Active state style mapping for Paytm, PhonePe, and Amazon Pay.
- **Cash / Offline Checkout:** Fully simulated bypass module allowing immediate course enrollment for quick sandbox testing.

### 3. Layout Alignments & Visual Fixes
- **Navbar Stretch:** Modified the navbar container from centered to fluid (`container-fluid px-4 px-lg-5`) so that the brand logo aligns perfectly with the sidebar and the profile aligns with the right margins.
- **Sidebar Header Spacing:** Added dedicated vertical spacing and alignment rules for `.sidebar-logo` to prevent squished layouts.
- **Progress Bar Customization:** Enabled visible, premium HSL purple progress bars (`.progress-bar-custom`) on student classrooms tracking cards.
- **Self-Healing Session Check:** Added an active user verification check to the session lifecycle. If the database is reset or reseeded, the system automatically redirects the browser to a clean logout rather than crashing with foreign key constraint violations.

---
 
## ⚠️ Common Errors & Fixes
 
| Error | Cause | Fix |
|-------|-------|-----|
| `Connection refused` or `could not connect to server` | PostgreSQL server not running | Start the PostgreSQL service using pgAdmin or system services |
| `password authentication failed for user` | Incorrect DB credentials | Set the correct password in `DB_PASS` in `index.php` |
| `database "klean_db" does not exist` | Auto-creation failed | Ensure `DB_USER` has rights to run `CREATE DATABASE` |
| Blank white page | PHP error hidden | Add `error_reporting(E_ALL);` at top of `index.php` |
| `localhost refused` | Apache not running | Start Apache server in XAMPP/WAMP |
 
---
 
## 📄 License
 
Built for educational and demonstration purposes.  
&copy; 2026 **Klean Learning Inc.** — Built with Bootstrap 5 & Vanilla PHP.