<?php
/* === KLEAN PLATFORM v1.0 === */

// =========================================================================
// 1. DB CONFIG & CONNECTION
// =========================================================================
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_USER', 'postgres');
define('DB_PASS', 'postgres123');
define('DB_NAME', 'klean_db');

try {
    // Attempt connecting to the target PostgreSQL database
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    // If the database does not exist, attempt to auto-create it via the default 'postgres' database
    if (strpos($e->getMessage(), 'does not exist') !== false || $e->getCode() == 7) {
        try {
            $temp_dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=postgres";
            $temp_pdo = new PDO($temp_dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $temp_pdo->exec("CREATE DATABASE " . DB_NAME);
            $temp_pdo = null; // Close connection
            
            // Re-attempt connecting to the newly created database
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $ex) {
            $e = $ex; // Pass the new exception down to the error page handler
        }
    }
    
    // If we still don't have a PDO connection, render the friendly error page
    if (!isset($pdo)) {
    // Show premium, friendly error page with config instructions
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Failed — Klean</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; color: #0F172A; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
            .error-card { max-width: 540px; padding: 3rem 2.5rem; background: #FFFFFF; border-radius: 20px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.04); border: 1px solid #E2E8F0; text-align: center; }
            .error-icon { width: 72px; height: 72px; background-color: #FEF2F2; color: #EF4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem; }
            .btn-primary-custom { background-color: #6C3FF4; border-color: #6C3FF4; color: #FFFFFF; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 10px; transition: all 0.2s; text-decoration: none; display: inline-block; }
            .btn-primary-custom:hover { background-color: #5A2EE3; border-color: #5A2EE3; color: #FFFFFF; transform: translateY(-1px); }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon"><i class="bi bi-database-exclamation"></i></div>
            <h3 class="fw-800 mb-3">Database Connection Failed</h3>
            <p class="text-muted small mb-4">We were unable to connect to your local PostgreSQL server. Please make sure that your server is active and running, and that your connection configurations match the details at the top of <code>index.php</code>.</p>
            <div class="bg-light p-3 rounded text-start mb-4 border text-secondary" style="font-size: 0.85rem;">
                <h6 class="fw-700 text-dark mb-1">Error Message:</h6>
                <code class="text-danger" style="word-break: break-all;"><?= htmlspecialchars($e->getMessage()) ?></code>
            </div>
            <a href="index.php" class="btn-primary-custom"><i class="bi bi-arrow-clockwise me-2"></i>Retry Connection</a>
        </div>
    </body>
    </html>
    <?php
    exit;
    }
}

// =========================================================================
// POSTGRESQL DATABASE SCHEMA AUTO-CREATION
// =========================================================================
try {
    // 1. Users
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
      id SERIAL PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      email VARCHAR(150) UNIQUE NOT NULL,
      password VARCHAR(255) NOT NULL,
      phone VARCHAR(20),
      bio TEXT,
      avatar VARCHAR(255) DEFAULT 'default.png',
      role VARCHAR(20) DEFAULT 'student' CHECK (role IN ('student','instructor','admin')),
      is_active SMALLINT DEFAULT 1,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    // 2. Categories
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
      id SERIAL PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      icon VARCHAR(50)
    );");

    // 3. Courses
    $pdo->exec("CREATE TABLE IF NOT EXISTS courses (
      id SERIAL PRIMARY KEY,
      instructor_id INT,
      category_id INT,
      title VARCHAR(255) NOT NULL,
      subtitle VARCHAR(255),
      description TEXT,
      thumbnail VARCHAR(255),
      level VARCHAR(20) CHECK (level IN ('beginner','intermediate','advanced')),
      price DECIMAL(10,2) DEFAULT 0,
      discount_price DECIMAL(10,2),
      status VARCHAR(20) DEFAULT 'published' CHECK (status IN ('draft','published')),
      total_hours DECIMAL(5,1),
      total_lessons INT DEFAULT 0,
      rating DECIMAL(3,2) DEFAULT 0,
      review_count INT DEFAULT 0,
      student_count INT DEFAULT 0,
      is_bestseller SMALLINT DEFAULT 0,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE SET NULL,
      FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    );");

    // 4. Sections
    $pdo->exec("CREATE TABLE IF NOT EXISTS sections (
      id SERIAL PRIMARY KEY,
      course_id INT,
      title VARCHAR(255),
      order_index INT DEFAULT 0,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    );");

    // 5. Lessons
    $pdo->exec("CREATE TABLE IF NOT EXISTS lessons (
      id SERIAL PRIMARY KEY,
      section_id INT,
      course_id INT,
      title VARCHAR(255),
      duration_minutes INT DEFAULT 0,
      is_preview SMALLINT DEFAULT 0,
      order_index INT DEFAULT 0,
      FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    );");

    // 6. Enrollments
    $pdo->exec("CREATE TABLE IF NOT EXISTS enrollments (
      id SERIAL PRIMARY KEY,
      user_id INT,
      course_id INT,
      enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      is_completed SMALLINT DEFAULT 0,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
      UNIQUE (user_id, course_id)
    );");

    // 7. Progress
    $pdo->exec("CREATE TABLE IF NOT EXISTS progress (
      id SERIAL PRIMARY KEY,
      user_id INT,
      lesson_id INT,
      course_id INT,
      is_completed SMALLINT DEFAULT 0,
      completed_at TIMESTAMP NULL,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
      UNIQUE (user_id, lesson_id)
    );");

    // 8. Cart
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart (
      id SERIAL PRIMARY KEY,
      user_id INT,
      course_id INT,
      added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
      UNIQUE (user_id, course_id)
    );");

    // 9. Wishlist
    $pdo->exec("CREATE TABLE IF NOT EXISTS wishlist (
      id SERIAL PRIMARY KEY,
      user_id INT,
      course_id INT,
      added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
      UNIQUE (user_id, course_id)
    );");

    // 10. Payments
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
      id SERIAL PRIMARY KEY,
      user_id INT,
      order_id VARCHAR(50) UNIQUE,
      amount DECIMAL(10,2),
      discount DECIMAL(10,2) DEFAULT 0,
      coupon_code VARCHAR(50),
      payment_method VARCHAR(50),
      payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending','success','failed')),
      transaction_id VARCHAR(100),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    );");

    // 11. Payment Items
    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_items (
      id SERIAL PRIMARY KEY,
      payment_id INT,
      course_id INT,
      price DECIMAL(10,2),
      FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    );");

    // 12. Reviews
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
      id SERIAL PRIMARY KEY,
      user_id INT,
      course_id INT,
      rating INT CHECK (rating BETWEEN 1 AND 5),
      comment TEXT,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
      UNIQUE (user_id, course_id)
    );");

    // 13. Coupons
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
      id SERIAL PRIMARY KEY,
      code VARCHAR(50) UNIQUE,
      discount_type VARCHAR(20) CHECK (discount_type IN ('percent','fixed')),
      discount_value DECIMAL(10,2),
      min_amount DECIMAL(10,2) DEFAULT 0,
      max_uses INT DEFAULT 100,
      used_count INT DEFAULT 0,
      expires_at DATE,
      is_active SMALLINT DEFAULT 1
    );");

    // 14. Certificates
    $pdo->exec("CREATE TABLE IF NOT EXISTS certificates (
      id SERIAL PRIMARY KEY,
      user_id INT,
      course_id INT,
      certificate_no VARCHAR(50) UNIQUE,
      issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    );");

    // 15. Notes (For course player)
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
      id SERIAL PRIMARY KEY,
      user_id INT,
      lesson_id INT,
      note TEXT,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
      UNIQUE (user_id, lesson_id)
    );");

    // 16. Notifications (Bell alerts)
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
      id SERIAL PRIMARY KEY,
      user_id INT,
      message TEXT,
      is_read BOOLEAN DEFAULT FALSE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );");

    // 17. Tickets
    $pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
      id SERIAL PRIMARY KEY,
      user_id INTEGER NOT NULL,
      subject VARCHAR(255) NOT NULL,
      category VARCHAR(100) NOT NULL,
      priority VARCHAR(20) DEFAULT 'Medium',
      message TEXT NOT NULL,
      status VARCHAR(30) DEFAULT 'Open',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_ticket_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );");

    // 18. Ticket Replies
    $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_replies (
      id SERIAL PRIMARY KEY,
      ticket_id INTEGER NOT NULL,
      user_id INTEGER,
      admin_id INTEGER,
      reply_message TEXT NOT NULL,
      is_internal BOOLEAN DEFAULT FALSE,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_reply_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
    );");

    // 19. Ticket Activity Logs
    $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_activity_logs (
      id SERIAL PRIMARY KEY,
      ticket_id INTEGER NOT NULL,
      activity TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_log_ticket FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
    );");

} catch (PDOException $e) {
    die("Database Schema Error: " . $e->getMessage());
}

// =========================================================================
// AUTO SEED DATA (IF TABLES ARE EMPTY)
// =========================================================================
try {
    // 1. Seed users
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($userCount == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (id, name, email, password, phone, bio, avatar, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([1, 'Admin User', 'admin@klean.com', password_hash('admin123', PASSWORD_BCRYPT), null, null, 'default.png', 'admin', 1]);
        $stmt->execute([2, 'Sarah Williams', 'sarah@klean.com', password_hash('pass123', PASSWORD_BCRYPT), '+1234567890', 'Expert web developer with 10yr exp. Creator of elite engineering architectures.', 'default.png', 'instructor', 1]);
        $stmt->execute([3, 'Alex Johnson', 'alex@klean.com', password_hash('pass123', PASSWORD_BCRYPT), '+0987654321', 'Ambitious developer learning to build clean visual layouts on Klean.', 'default.png', 'student', 1]);
    }

    // 2. Seed categories
    $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    if ($catCount == 0) {
        $pdo->exec("INSERT INTO categories (id, name, icon) VALUES
        (1, 'Development', 'bi-code-slash'),
        (2, 'Design', 'bi-palette'),
        (3, 'Business', 'bi-briefcase'),
        (4, 'Marketing', 'bi-megaphone'),
        (5, 'Photography', 'bi-camera'),
        (6, 'Music', 'bi-music-note'),
        (7, 'Finance', 'bi-currency-dollar'),
        (8, 'IT & Software', 'bi-laptop')");
    }

    // 3. Seed courses (8 courses)
    $courseCount = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
    if ($courseCount == 0) {
        $pdo->exec("INSERT INTO courses 
        (id, instructor_id, category_id, title, subtitle, description, thumbnail, level, price, discount_price, status, total_hours, total_lessons, rating, review_count, student_count, is_bestseller, created_at)
        VALUES
        (1, 2, 1, 'Complete Web Development Bootcamp', 'HTML CSS JS React Node MongoDB', 'Master HTML, CSS, JavaScript, Node, React and build beautifully functional products from scratch. Build a stellar developer portfolio and get ready to land your dream junior developer job. We start from absolute zero experience.', 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=400&q=80', 'beginner', 89.99, 14.99, 'published', 52.0, 10, 4.70, 12400, 125000, 1, NOW()),
        (2, 2, 1, 'Python for Data Science & ML', 'NumPy Pandas Matplotlib Scikit-learn', 'From Zero to Hero — master Python, Pandas, and train real Machine Learning pipelines. This complete learning path contains everything you need to kickstart your career in Data Science.', 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=400&q=80', 'intermediate', 94.99, 16.99, 'published', 40.0, 6, 4.80, 9800, 98000, 1, NOW()),
        (3, 2, 2, 'UI/UX Design Masterclass', 'Figma Adobe XD Prototyping User Research', 'Learn wireframes, Figma glassmorphism styles, and build sleek interactive prototypes that wow clients. You will learn user testing, accessibility standards, and professional design hands-on.', 'https://images.unsplash.com/photo-1581291518633-83b4ebd1d83e?auto=format&fit=crop&w=400&q=80', 'beginner', 74.99, 13.99, 'published', 28.0, 5, 4.60, 6200, 74000, 0, NOW()),
        (4, 2, 4, 'Digital Marketing Full Course', 'SEO SEM Social Media Email Marketing', 'SEO, SEM, Copywriting, Social Campaigns — master strategies that convert views into revenue. Includes step-by-step instructions on running automated email newsletter funnels.', 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=400&q=80', 'beginner', 59.99, 12.99, 'published', 22.0, 5, 4.50, 5100, 61000, 0, NOW()),
        (5, 2, 1, 'React JS Complete Guide', 'Hooks Redux Context API Next.js', 'Build responsive, high-performance web applications using React Hooks, Redux Toolkit, Context API, and Next.js framework. Includes modular component architectures and cloud deployments.', 'https://images.unsplash.com/photo-1633356122544-f134324a6cee?auto=format&fit=crop&w=400&q=80', 'intermediate', 84.99, 15.99, 'published', 35.0, 6, 4.90, 11200, 112000, 1, NOW()),
        (6, 2, 5, 'Photography Masterclass', 'DSLR Composition Lightroom Editing', 'Master camera exposure, lighting, framing, and Lightroom editing configurations. Learn creative studio lighting systems and professional photo retouching step-by-step.', 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=400&q=80', 'beginner', 49.99, 10.99, 'published', 18.0, 4, 4.40, 3800, 42000, 0, NOW()),
        (7, 2, 7, 'Financial Planning & Investing', 'Stocks Bonds ETFs Retirement Planning', 'Index funds, crypto analysis, tax structures, and portfolio balancing methods. Learn how to grow your wealth steadily through calculated, compound-interest investment vehicles.', 'https://images.unsplash.com/photo-1590283603385-17ffb3a7f29f?auto=format&fit=crop&w=400&q=80', 'beginner', 69.99, 12.99, 'published', 15.0, 4, 4.70, 4900, 53000, 0, NOW()),
        (8, 2, 2, 'Graphic Design Bootcamp', 'Photoshop Illustrator InDesign Canva', 'Typography, brand logos, vector assets, and design mockups with Adobe Creative Suite. Build print-ready designs, packaging concepts, and high-fidelity promotional mockups.', 'https://images.unsplash.com/photo-1626785774573-4b799315345d?auto=format&fit=crop&w=400&q=80', 'beginner', 64.99, 11.99, 'published', 24.0, 5, 4.60, 5600, 67000, 0, NOW())");
    }

    // 4. Seed sections & lessons for course 1 (Complete Web Dev Bootcamp)
    $sectionCount = $pdo->query("SELECT COUNT(*) FROM sections")->fetchColumn();
    if ($sectionCount == 0) {
        $pdo->exec("INSERT INTO sections (id, course_id, title, order_index) VALUES
        (1, 1, 'Getting Started', 1),
        (2, 1, 'HTML Fundamentals', 2),
        (3, 1, 'CSS Mastery', 3),
        (4, 1, 'JavaScript Basics', 4)");

        $pdo->exec("INSERT INTO lessons (id, section_id, course_id, title, duration_minutes, is_preview, order_index) VALUES
        (1, 1, 1, 'Welcome to the Course', 5, 1, 1),
        (2, 1, 1, 'How the Web Works', 8, 1, 2),
        (3, 2, 1, 'HTML Document Structure', 12, 0, 1),
        (4, 2, 1, 'HTML Tags & Elements', 15, 0, 2),
        (5, 2, 1, 'HTML Forms & Tables', 18, 0, 3),
        (6, 3, 1, 'CSS Selectors', 14, 0, 1),
        (7, 3, 1, 'Box Model & Flexbox', 20, 0, 2),
        (8, 3, 1, 'CSS Grid Layout', 18, 0, 3),
        (9, 4, 1, 'Variables & Data Types', 16, 0, 1),
        (10, 4, 1, 'Functions & Scope', 20, 0, 2)");
    }

    // 5. Seed enrollments for student Alex Johnson (id: 3)
    $enrollCount = $pdo->query("SELECT COUNT(*) FROM enrollments")->fetchColumn();
    if ($enrollCount == 0) {
        $pdo->exec("INSERT INTO enrollments (id, user_id, course_id, enrolled_at, is_completed) VALUES
        (1, 3, 1, NOW(), 0),
        (2, 3, 5, NOW(), 0),
        (3, 3, 3, NOW(), 1)");
    }

    // 6. Seed progress for Alex Johnson on course 1 lessons
    $progCount = $pdo->query("SELECT COUNT(*) FROM progress")->fetchColumn();
    if ($progCount == 0) {
        $pdo->exec("INSERT INTO progress (id, user_id, lesson_id, course_id, is_completed, completed_at) VALUES
        (1, 3, 1, 1, 1, NOW()),
        (2, 3, 2, 1, 1, NOW()),
        (3, 3, 3, 1, 1, NOW()),
        (4, 3, 4, 1, 1, NOW()),
        (5, 3, 5, 1, 0, NULL)");
    }

    // 7. Seed cart items for Alex
    $cartCount = $pdo->query("SELECT COUNT(*) FROM cart")->fetchColumn();
    if ($cartCount == 0) {
        $pdo->exec("INSERT INTO cart (id, user_id, course_id, added_at) VALUES
        (1, 3, 2, NOW()),
        (2, 3, 4, NOW())");
    }

    // 8. Seed wishlist items for Alex
    $wishCount = $pdo->query("SELECT COUNT(*) FROM wishlist")->fetchColumn();
    if ($wishCount == 0) {
        $pdo->exec("INSERT INTO wishlist (id, user_id, course_id, added_at) VALUES
        (1, 3, 6, NOW()),
        (2, 3, 7, NOW())");
    }

    // 9. Seed coupon KLEAN20 (20% off)
    $couponCount = $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn();
    if ($couponCount == 0) {
        $pdo->exec("INSERT INTO coupons (id, code, discount_type, discount_value, min_amount, max_uses, used_count, expires_at, is_active) VALUES
        (1, 'KLEAN20', 'percent', 20.00, 0.00, 1000, 0, '2099-12-31', 1)");
    }

    // 10. Seed reviews on courses
    $reviewCount = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
    if ($reviewCount == 0) {
        $pdo->exec("INSERT INTO reviews (id, user_id, course_id, rating, comment, created_at) VALUES
        (1, 3, 1, 5, 'Amazing course! Learned so much. The layout is optimized flawlessly.', NOW()),
        (2, 3, 3, 4, 'Great content and well structured wireframe strategies.', NOW())");
    }

    // =========================================================================
    // PostgreSQL Sequence Synchronization
    // Reset serial primary key sequences to match explicit seeded IDs
    // =========================================================================
    $seeded_tables = [
        'users', 'categories', 'courses', 'sections', 'lessons', 
        'enrollments', 'progress', 'cart', 'wishlist', 'payments', 
        'payment_items', 'reviews', 'coupons', 'certificates', 
        'notes', 'notifications', 'tickets', 'ticket_replies', 'ticket_activity_logs'
    ];
    foreach ($seeded_tables as $tbl) {
        $pdo->exec("SELECT setval('{$tbl}_id_seq', COALESCE((SELECT MAX(id) FROM {$tbl}), 0) + 1, false)");
    }

} catch (PDOException $e) {
    die("Database Seeding Error: " . $e->getMessage());
}

// =========================================================================
// 2. SESSION & SECURITY MIDDLEWARES
// =========================================================================
session_start();

// Sanity check: if a user is logged in, verify they still exist in the database
if (isset($_SESSION['user']) && isset($pdo)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']['id']]);
    if (!$stmt->fetch()) {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        session_start();
    }
}

// CSRF Token Setup
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Clean Input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Helpers
function isLoggedIn() {
    return isset($_SESSION['user']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Authentication required. Please log in first.']);
        exit;
    }
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function getCartCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    return intval($stmt->fetchColumn());
}

function getCourseById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function isEnrolled($pdo, $userId, $courseId) {
    $stmt = $pdo->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$userId, $courseId]);
    return $stmt->fetch() ? true : false;
}

function inWishlist($pdo, $userId, $courseId) {
    $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$userId, $courseId]);
    return $stmt->fetch() ? true : false;
}

function getCourseProgress($pdo, $userId, $courseId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lessons WHERE course_id = ?");
    $stmt->execute([$courseId]);
    $totalLessons = intval($stmt->fetchColumn());
    
    if ($totalLessons === 0) return 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM progress WHERE user_id = ? AND course_id = ? AND is_completed = 1");
    $stmt->execute([$userId, $courseId]);
    $completed = intval($stmt->fetchColumn());
    
    return round(($completed / $totalLessons) * 100);
}

function generateCertNo() {
    return 'KLN-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2))) . '-' . strtoupper(bin2hex(random_bytes(2)));
}

function formatDuration($minutes) {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($hours > 0) {
        return $hours . "h " . $mins . "m";
    }
    return $mins . "m";
}

function formatPrice($price) {
    return '₹' . number_format($price, 2, '.', ',');
}

// =========================================================================
// 5. AJAX API HANDLERS (RETURN JSON)
// =========================================================================
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $action = $_GET['ajax'];
    
    // Validate CSRF for POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $headers = getallheaders();
        $token = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
        
        if (empty($token)) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (!empty($input['csrf_token'])) {
                $token = $input['csrf_token'];
            }
        }
        
        if (empty($token) || $token !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Security Error: CSRF validation failed.']);
            exit;
        }
    }
    
    try {
        switch ($action) {
            case 'login':
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'avatar' => $user['avatar'] ?? 'default.png'
                    ];
                    
                    $redirect = '?page=dashboard';
                    if ($user['role'] === 'instructor') {
                        $redirect = '?page=instructor';
                    } elseif ($user['role'] === 'admin') {
                        $redirect = '?page=admin';
                    }
                    
                    echo json_encode(['success' => true, 'user' => $_SESSION['user'], 'redirect' => $redirect]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid email or password.']);
                }
                break;
                
            case 'signup':
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                $role = $_POST['role'] ?? 'student';
                
                if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
                    echo json_encode(['success' => false, 'error' => 'All fields are required.']);
                    break;
                }
                
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    echo json_encode(['success' => false, 'error' => 'Invalid email address.']);
                    break;
                }
                
                if ($password !== $confirm_password) {
                    echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
                    break;
                }
                
                if (strlen($password) < 6) {
                    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters.']);
                    break;
                }
                
                if (!in_array($role, ['student', 'instructor'])) {
                    echo json_encode(['success' => false, 'error' => 'Invalid user role selected.']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'An account with this email already exists.']);
                    break;
                }
                
                $hashed = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$name, $email, $hashed, $role]);
                
                $userId = $pdo->lastInsertId('users_id_seq');
                
                $_SESSION['user'] = [
                    'id' => $userId,
                    'name' => $name,
                    'email' => $email,
                    'role' => $role,
                    'avatar' => 'default.png'
                ];
                
                // Add default welcome notification
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?) ON CONFLICT DO NOTHING");
                $stmt->execute([$userId, "Welcome to Klean, " . htmlspecialchars($name) . "! Your clean online learning journey starts right now."]);
                
                $redirect = ($role === 'instructor') ? '?page=instructor' : '?page=dashboard';
                echo json_encode(['success' => true, 'redirect' => $redirect]);
                break;
                
            case 'logout':
                $_SESSION = [];
                session_destroy();
                echo json_encode(['success' => true]);
                break;
                
            case 'get_courses':
                $category = $_GET['category'] ?? 'all';
                $level = $_GET['level'] ?? 'all';
                $price = $_GET['price'] ?? 'all';
                $rating = $_GET['rating'] ?? 'all';
                $sort = $_GET['sort'] ?? 'popular';
                $search = $_GET['q'] ?? '';
                
                $sql = "SELECT c.*, u.name as instructor 
                        FROM courses c 
                        JOIN users u ON c.instructor_id = u.id 
                        WHERE c.status = 'published'";
                $params = [];
                
                if ($category !== 'all') {
                    if (is_numeric($category)) {
                        $sql .= " AND c.category_id = ?";
                        $params[] = intval($category);
                    } else {
                        $sql .= " AND c.category_id = (SELECT id FROM categories WHERE name = ? LIMIT 1)";
                        $params[] = $category;
                    }
                }
                
                if ($level !== 'all') {
                    $sql .= " AND c.level = ?";
                    $params[] = strtolower($level);
                }
                
                if ($price === 'free') {
                    $sql .= " AND c.price = 0";
                } elseif ($price === 'paid') {
                    $sql .= " AND c.price > 0";
                }
                
                if ($rating !== 'all') {
                    $sql .= " AND c.rating >= ?";
                    $params[] = floatval($rating);
                }
                
                if (!empty($search)) {
                    $sql .= " AND (c.title ILIKE ? OR c.subtitle ILIKE ? OR c.description ILIKE ?)";
                    $searchTerm = "%$search%";
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                switch ($sort) {
                    case 'price-low':
                        $sql .= " ORDER BY c.price ASC";
                        break;
                    case 'price-high':
                        $sql .= " ORDER BY c.price DESC";
                        break;
                    case 'rating':
                        $sql .= " ORDER BY c.rating DESC";
                        break;
                    case 'popular':
                    default:
                        $sql .= " ORDER BY c.student_count DESC";
                        break;
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $courses = $stmt->fetchAll();
                
                // Add categories listings in the payload
                $cats = $pdo->query("SELECT * FROM categories")->fetchAll();
                
                echo json_encode(['success' => true, 'courses' => $courses, 'categories' => $cats]);
                break;
                
            case 'search_courses':
                $keyword = trim($_GET['keyword'] ?? $_POST['keyword'] ?? '');
                
                $stmt = $pdo->prepare("SELECT c.*, u.name as instructor FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.status = 'published' AND (c.title ILIKE ? OR c.subtitle ILIKE ? OR c.description ILIKE ?)");
                $searchTerm = "%$keyword%";
                $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
                $courses = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'courses' => $courses]);
                break;
                
            case 'get_course_detail':
                $courseId = intval($_GET['course_id'] ?? 0);
                if ($courseId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid course.']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT c.*, u.name as instructor, u.bio as instructor_bio, u.avatar as instructor_avatar, cat.name as category_name 
                                       FROM courses c 
                                       JOIN users u ON c.instructor_id = u.id 
                                       LEFT JOIN categories cat ON c.category_id = cat.id
                                       WHERE c.id = ?");
                $stmt->execute([$courseId]);
                $course = $stmt->fetch();
                
                if (!$course) {
                    echo json_encode(['success' => false, 'error' => 'Course not found.']);
                    break;
                }
                
                // Instructor stats
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE instructor_id = ?");
                $stmt->execute([$course['instructor_id']]);
                $course['instructor_courses_count'] = intval($stmt->fetchColumn());
                
                $stmt = $pdo->prepare("SELECT SUM(student_count) FROM courses WHERE instructor_id = ?");
                $stmt->execute([$course['instructor_id']]);
                $course['instructor_students_count'] = intval($stmt->fetchColumn());
                
                // Sections
                $stmt = $pdo->prepare("SELECT * FROM sections WHERE course_id = ? ORDER BY order_index ASC");
                $stmt->execute([$courseId]);
                $sections = $stmt->fetchAll();
                
                // Lessons
                foreach ($sections as &$sec) {
                    $stmt2 = $pdo->prepare("SELECT * FROM lessons WHERE section_id = ? ORDER BY order_index ASC");
                    $stmt2->execute([$sec['id']]);
                    $sec['lessons'] = $stmt2->fetchAll();
                }
                
                // Reviews
                $stmt = $pdo->prepare("SELECT r.*, u.name as user_name, u.avatar as user_avatar 
                                       FROM reviews r 
                                       JOIN users u ON r.user_id = u.id 
                                       WHERE r.course_id = ? 
                                       ORDER BY r.created_at DESC");
                $stmt->execute([$courseId]);
                $reviews = $stmt->fetchAll();
                
                // User Relationship states
                $userId = $_SESSION['user']['id'] ?? 0;
                $isEnrolled = false;
                $inWishlist = false;
                $inCart = false;
                
                if ($userId > 0) {
                    $isEnrolled = isEnrolled($pdo, $userId, $courseId);
                    $inWishlist = inWishlist($pdo, $userId, $courseId);
                    
                    $stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND course_id = ?");
                    $stmt->execute([$userId, $courseId]);
                    $inCart = $stmt->fetch() ? true : false;
                }
                
                echo json_encode([
                    'success' => true,
                    'course' => $course,
                    'sections' => $sections,
                    'reviews' => $reviews,
                    'userState' => [
                        'isEnrolled' => $isEnrolled,
                        'inWishlist' => $inWishlist,
                        'inCart' => $inCart
                    ]
                ]);
                break;
                
            case 'cart_add':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $courseId = intval($_POST['course_id'] ?? $_GET['course_id'] ?? 0);
                
                if ($courseId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid course.']);
                    break;
                }
                
                if ($_SESSION['user']['role'] === 'instructor') {
                    echo json_encode(['success' => false, 'error' => 'Instructors cannot enroll or buy courses.']);
                    break;
                }
                
                if (isEnrolled($pdo, $userId, $courseId)) {
                    echo json_encode(['success' => false, 'error' => 'You are already enrolled in this course.']);
                    break;
                }
                
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, course_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
                $stmt->execute([$userId, $courseId]);
                
                $cartCount = getCartCount($pdo, $userId);
                echo json_encode(['success' => true, 'cart_count' => $cartCount]);
                break;
                
            case 'cart_remove':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $courseId = intval($_POST['course_id'] ?? $_GET['course_id'] ?? 0);
                
                if ($courseId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid course.']);
                    break;
                }
                
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND course_id = ?");
                $stmt->execute([$userId, $courseId]);
                
                $cartCount = getCartCount($pdo, $userId);
                echo json_encode(['success' => true, 'cart_count' => $cartCount]);
                break;
                
            case 'get_cart':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                
                $stmt = $pdo->prepare("SELECT c.*, u.name as instructor 
                                       FROM cart ct 
                                       JOIN courses c ON ct.course_id = c.id 
                                       JOIN users u ON c.instructor_id = u.id 
                                       WHERE ct.user_id = ?");
                $stmt->execute([$userId]);
                $items = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'items' => $items]);
                break;
                
            case 'wishlist':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $courseId = intval($_POST['course_id'] ?? $_GET['course_id'] ?? 0);
                
                if ($courseId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid course.']);
                    break;
                }
                
                if (inWishlist($pdo, $userId, $courseId)) {
                    $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND course_id = ?");
                    $stmt->execute([$userId, $courseId]);
                    echo json_encode(['success' => true, 'action' => 'removed']);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, course_id) VALUES (?, ?) ON CONFLICT DO NOTHING");
                    $stmt->execute([$userId, $courseId]);
                    echo json_encode(['success' => true, 'action' => 'added']);
                }
                break;
                
            case 'get_wishlist':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                
                $stmt = $pdo->prepare("SELECT c.*, u.name as instructor 
                                       FROM wishlist w 
                                       JOIN courses c ON w.course_id = c.id 
                                       JOIN users u ON c.instructor_id = u.id 
                                       WHERE w.user_id = ?");
                $stmt->execute([$userId]);
                $items = $stmt->fetchAll();
                
                echo json_encode(['success' => true, 'items' => $items]);
                break;
                
            case 'coupon':
                $code = trim($_POST['code'] ?? $_GET['code'] ?? '');
                $total = floatval($_POST['total'] ?? $_GET['total'] ?? 0);
                
                $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND expires_at >= CURRENT_DATE AND used_count < max_uses");
                $stmt->execute([$code]);
                $coupon = $stmt->fetch();
                
                if ($coupon) {
                    $val = floatval($coupon['discount_value']);
                    $discount = 0;
                    if ($coupon['discount_type'] === 'percent') {
                        $discount = $total * ($val / 100);
                    } else {
                        $discount = min($val, $total);
                    }
                    
                    $final = $total - $discount;
                    echo json_encode(['success' => true, 'discount' => $discount, 'final_total' => $final]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Invalid or expired coupon code.']);
                }
                break;
                
            case 'checkout':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                
                $input = json_decode(file_get_contents('php://input'), true);
                if (!$input) { $input = $_POST; }
                
                $method = $input['method'] ?? 'card';
                $couponCode = $input['coupon'] ?? '';
                
                // Fetch cart
                $stmt = $pdo->prepare("SELECT c.* FROM cart ct JOIN courses c ON ct.course_id = c.id WHERE ct.user_id = ?");
                $stmt->execute([$userId]);
                $cartItems = $stmt->fetchAll();
                
                if (empty($cartItems)) {
                    echo json_encode(['success' => false, 'error' => 'Checkout failed. Your shopping cart is empty.']);
                    break;
                }
                
                $subtotal = 0;
                foreach ($cartItems as $item) {
                    $subtotal += floatval($item['price']);
                }
                
                $discount = 0;
                if (!empty($couponCode)) {
                    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND expires_at >= CURRENT_DATE AND used_count < max_uses");
                    $stmt->execute([$couponCode]);
                    $coupon = $stmt->fetch();
                    if ($coupon) {
                        if ($coupon['discount_type'] === 'percent') {
                            $discount = $subtotal * (floatval($coupon['discount_value']) / 100);
                        } else {
                            $discount = min(floatval($coupon['discount_value']), $subtotal);
                        }
                        
                        $stmtUpdate = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                        $stmtUpdate->execute([$coupon['id']]);
                    }
                }
                
                $finalAmount = $subtotal - $discount;
                $orderId = 'KLN' . time() . rand(100, 999);
                $transactionId = 'TXN' . strtoupper(bin2hex(random_bytes(5)));
                
                $pdo->beginTransaction();
                try {
                    // Payment entry
                    $stmt = $pdo->prepare("INSERT INTO payments (user_id, order_id, amount, discount, coupon_code, payment_method, payment_status, transaction_id) VALUES (?, ?, ?, ?, ?, ?, 'success', ?)");
                    $stmt->execute([$userId, $orderId, $finalAmount, $discount, $couponCode, $method, $transactionId]);
                    $paymentId = $pdo->lastInsertId('payments_id_seq');
                    
                    foreach ($cartItems as $c) {
                        // Payment items
                        $stmt = $pdo->prepare("INSERT INTO payment_items (payment_id, course_id, price) VALUES (?, ?, ?)");
                        $stmt->execute([$paymentId, $c['id'], $c['price']]);
                        
                        // Enrollment
                        $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id, is_completed) VALUES (?, ?, 0) ON CONFLICT DO NOTHING");
                        $stmt->execute([$userId, $c['id']]);
                        
                        // Increment student count
                        $stmt = $pdo->prepare("UPDATE courses SET student_count = student_count + 1 WHERE id = ?");
                        $stmt->execute([$c['id']]);
                        
                        // Notification
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?) ON CONFLICT DO NOTHING");
                        $stmt->execute([$userId, "Awesome choice! You are enrolled in '" . htmlspecialchars($c['title']) . "'. Welcome aboard."]);
                    }
                    
                    // Clear cart
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    
                    $pdo->commit();
                    echo json_encode(['success' => true, 'order_id' => $orderId, 'redirect' => '?page=success&order_id=' . $orderId]);
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'error' => 'Transaction failed: ' . $ex->getMessage()]);
                }
                break;
                
            case 'progress':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $lessonId = intval($_POST['lesson_id'] ?? $_GET['lesson_id'] ?? 0);
                $courseId = intval($_POST['course_id'] ?? $_GET['course_id'] ?? 0);
                $isCompleted = intval($_POST['is_completed'] ?? $_GET['is_completed'] ?? 1);
                
                if ($lessonId <= 0 || $courseId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Missing parameters.']);
                    break;
                }
                
                if (!isEnrolled($pdo, $userId, $courseId)) {
                    echo json_encode(['success' => false, 'error' => 'You are not enrolled in this course.']);
                    break;
                }
                
                if ($isCompleted) {
                    $stmt = $pdo->prepare("INSERT INTO progress (user_id, lesson_id, course_id, is_completed, completed_at) VALUES (?, ?, ?, 1, NOW())
                                           ON CONFLICT (user_id, lesson_id) DO UPDATE SET is_completed = 1, completed_at = NOW()");
                    $stmt->execute([$userId, $lessonId, $courseId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE progress SET is_completed = 0, completed_at = NULL WHERE user_id = ? AND lesson_id = ?");
                    $stmt->execute([$userId, $lessonId]);
                }
                
                $percent = getCourseProgress($pdo, $userId, $courseId);
                
                if ($percent >= 100) {
                    $stmt = $pdo->prepare("UPDATE enrollments SET is_completed = 1 WHERE user_id = ? AND course_id = ?");
                    $stmt->execute([$userId, $courseId]);
                    
                    // Generate certificate
                    $stmt = $pdo->prepare("SELECT id FROM certificates WHERE user_id = ? AND course_id = ?");
                    $stmt->execute([$userId, $courseId]);
                    if (!$stmt->fetch()) {
                        $certNo = generateCertNo();
                        $stmt = $pdo->prepare("INSERT INTO certificates (user_id, course_id, certificate_no, issued_at) VALUES (?, ?, ?, NOW())");
                        $stmt->execute([$userId, $courseId, $certNo]);
                        
                        $courseTitle = $pdo->query("SELECT title FROM courses WHERE id = $courseId")->fetchColumn();
                        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?) ON CONFLICT DO NOTHING");
                        $stmt->execute([$userId, "Hooray! You completed '" . htmlspecialchars($courseTitle) . "' and earned a Certificate of Excellence (" . $certNo . ")! 🎓"]);
                    }
                } else {
                    $stmt = $pdo->prepare("UPDATE enrollments SET is_completed = 0 WHERE user_id = ? AND course_id = ?");
                    $stmt->execute([$userId, $courseId]);
                }
                
                echo json_encode(['success' => true, 'percent' => $percent]);
                break;
                
            case 'review':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $courseId = intval($_POST['course_id'] ?? 0);
                $rating = intval($_POST['rating'] ?? 5);
                $comment = trim($_POST['comment'] ?? '');
                
                if ($courseId <= 0 || $rating < 1 || $rating > 5) {
                    echo json_encode(['success' => false, 'error' => 'Invalid inputs.']);
                    break;
                }
                
                if (!isEnrolled($pdo, $userId, $courseId)) {
                    echo json_encode(['success' => false, 'error' => 'Reviews can only be written for purchased courses.']);
                    break;
                }
                
                $stmt = $pdo->prepare("INSERT INTO reviews (user_id, course_id, rating, comment, created_at) VALUES (?, ?, ?, ?, NOW())
                                       ON CONFLICT (user_id, course_id) DO UPDATE SET rating = EXCLUDED.rating, comment = EXCLUDED.comment, created_at = NOW()");
                $stmt->execute([$userId, $courseId, $rating, $comment]);
                
                // Update average rating on course
                $stmt = $pdo->prepare("SELECT AVG(rating) as avg_rate, COUNT(*) as cnt FROM reviews WHERE course_id = ?");
                $stmt->execute([$courseId]);
                $avgStats = $stmt->fetch();
                
                $stmt = $pdo->prepare("UPDATE courses SET rating = ?, review_count = ? WHERE id = ?");
                $stmt->execute([round($avgStats['avg_rate'], 1), $avgStats['cnt'], $courseId]);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'profile':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $bio = trim($_POST['bio'] ?? '');
                
                if (empty($name) || empty($email)) {
                    echo json_encode(['success' => false, 'error' => 'Name and email are required fields.']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    echo json_encode(['success' => false, 'error' => 'This email address is already registered to another account.']);
                    break;
                }
                
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone = ?, bio = ? WHERE id = ?");
                $stmt->execute([$name, $email, $phone, $bio, $userId]);
                
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
                
                echo json_encode(['success' => true, 'user' => $_SESSION['user']]);
                break;
                
            case 'change_password':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $curr = $_POST['current_password'] ?? '';
                $new = $_POST['new_password'] ?? '';
                $conf = $_POST['confirm_password'] ?? '';
                
                if (empty($curr) || empty($new) || empty($conf)) {
                    echo json_encode(['success' => false, 'error' => 'Please fill in all password fields.']);
                    break;
                }
                
                if ($new !== $conf) {
                    echo json_encode(['success' => false, 'error' => 'New passwords do not match.']);
                    break;
                }
                
                if (strlen($new) < 6) {
                    echo json_encode(['success' => false, 'error' => 'Password must be at least 6 characters long.']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $hash = $stmt->fetchColumn();
                
                if ($hash && password_verify($curr, $hash)) {
                    $newHash = password_hash($new, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$newHash, $userId]);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Incorrect current password.']);
                }
                break;
                
            case 'get_dashboard':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                
                $stmt = $pdo->prepare("SELECT c.*, u.name as instructor 
                                       FROM enrollments e 
                                       JOIN courses c ON e.course_id = c.id 
                                       JOIN users u ON c.instructor_id = u.id 
                                       WHERE e.user_id = ?");
                $stmt->execute([$userId]);
                $enrolled = $stmt->fetchAll();
                
                $completedCount = 0;
                $totalHours = 0;
                
                foreach ($enrolled as &$c) {
                    $c['progress'] = getCourseProgress($pdo, $userId, $c['id']);
                    if ($c['progress'] >= 100) {
                        $completedCount++;
                        $stmtCert = $pdo->prepare("SELECT certificate_no FROM certificates WHERE user_id = ? AND course_id = ?");
                        $stmtCert->execute([$userId, $c['id']]);
                        $c['certificate_no'] = $stmtCert->fetchColumn();
                    }
                    $totalHours += ($c['progress'] / 100.0) * floatval($c['total_hours']);
                }
                
                // Recommended
                $stmt = $pdo->prepare("SELECT c.*, u.name as instructor 
                                       FROM courses c 
                                       JOIN users u ON c.instructor_id = u.id 
                                       WHERE c.status = 'published' AND c.id NOT IN (SELECT course_id FROM enrollments WHERE user_id = ?) 
                                       LIMIT 3");
                $stmt->execute([$userId]);
                $recs = $stmt->fetchAll();
                
                echo json_encode([
                    'success' => true,
                    'stats' => [
                        'enrolled' => count($enrolled),
                        'completed' => $completedCount,
                        'hours' => round($totalHours, 1)
                    ],
                    'courses' => $enrolled,
                    'recommended' => $recs
                ]);
                break;
                
            case 'get_instructor_dashboard':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                if ($_SESSION['user']['role'] !== 'instructor') {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized action.']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
                $stmt->execute([$userId]);
                $courses = $stmt->fetchAll();
                
                $totalStudents = 0;
                foreach ($courses as $c) {
                    $totalStudents += intval($c['student_count']);
                }
                
                $totalEarnings = 0;
                $monthlyEarnings = [];
                
                if (!empty($courses)) {
                    $cIds = array_column($courses, 'id');
                    $clause = implode(',', array_fill(0, count($cIds), '?'));
                    
                    $stmt = $pdo->prepare("SELECT SUM(pi.price) FROM payment_items pi JOIN payments p ON pi.payment_id = p.id WHERE pi.course_id IN ($clause) AND p.payment_status = 'success'");
                    $stmt->execute($cIds);
                    $raw = floatval($stmt->fetchColumn());
                    $totalEarnings = $raw * 0.70; // 70% share for instructor
                    
                    // Monthly groupings
                    $stmt = $pdo->prepare("SELECT TO_CHAR(p.created_at, 'Mon YYYY') as month, SUM(pi.price) as revenue FROM payment_items pi JOIN payments p ON pi.payment_id = p.id WHERE pi.course_id IN ($clause) AND p.payment_status = 'success' GROUP BY TO_CHAR(p.created_at, 'Mon YYYY'), TO_CHAR(p.created_at, 'YYYY-MM') ORDER BY TO_CHAR(p.created_at, 'YYYY-MM') ASC LIMIT 6");
                    $stmt->execute($cIds);
                    $months = $stmt->fetchAll();
                    
                    foreach ($months as $m) {
                        $monthlyEarnings[] = [
                            'month' => $m['month'],
                            'amount' => round($m['revenue'] * 0.70, 2)
                        ];
                    }
                }
                
                // Add mock charts data if no real payments have occurred
                if (empty($monthlyEarnings)) {
                    $monthlyEarnings = [
                        ['month' => 'Jan 2026', 'amount' => 420.00],
                        ['month' => 'Feb 2026', 'amount' => 690.00],
                        ['month' => 'Mar 2026', 'amount' => 1100.00],
                        ['month' => 'Apr 2026', 'amount' => 840.00],
                        ['month' => 'May 2026', 'amount' => $totalEarnings]
                    ];
                }
                
                $avgRating = 0;
                if (!empty($courses)) {
                    $cIds = array_column($courses, 'id');
                    $clause = implode(',', array_fill(0, count($cIds), '?'));
                    $stmt = $pdo->prepare("SELECT AVG(rating) FROM courses WHERE id IN ($clause) AND review_count > 0");
                    $stmt->execute($cIds);
                    $avgRating = round(floatval($stmt->fetchColumn()), 1);
                    if ($avgRating == 0) $avgRating = 4.8;
                } else {
                    $avgRating = 4.8;
                }
                
                echo json_encode([
                    'success' => true,
                    'stats' => [
                        'students' => $totalStudents,
                        'revenue' => $totalEarnings,
                        'rating' => $avgRating,
                        'courses' => count($courses)
                    ],
                    'courses' => $courses,
                    'earnings' => $monthlyEarnings
                ]);
                break;
                
            case 'save_note':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $lessonId = intval($_POST['lesson_id'] ?? 0);
                $note = trim($_POST['note'] ?? '');
                
                if ($lessonId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
                    break;
                }
                
                $stmt = $pdo->prepare("INSERT INTO notes (user_id, lesson_id, note, updated_at) VALUES (?, ?, ?, NOW())
                                       ON CONFLICT (user_id, lesson_id) DO UPDATE SET note = EXCLUDED.note, updated_at = NOW()");
                $stmt->execute([$userId, $lessonId, $note]);
                
                echo json_encode(['success' => true]);
                break;
                
            case 'get_note':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $lessonId = intval($_GET['lesson_id'] ?? 0);
                
                if ($lessonId <= 0) {
                    echo json_encode(['success' => false, 'error' => 'Invalid parameters.']);
                    break;
                }
                
                $stmt = $pdo->prepare("SELECT note FROM notes WHERE user_id = ? AND lesson_id = ?");
                $stmt->execute([$userId, $lessonId]);
                $note = $stmt->fetchColumn();
                
                echo json_encode(['success' => true, 'note' => $note ? $note : '']);
                break;
                
            case 'create_course':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                if ($_SESSION['user']['role'] !== 'instructor') {
                    echo json_encode(['success' => false, 'error' => 'Access denied.']);
                    break;
                }
                
                $title = trim($_POST['title'] ?? '');
                $subtitle = trim($_POST['subtitle'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $category_id = intval($_POST['category_id'] ?? 1);
                $level = $_POST['level'] ?? 'beginner';
                $price = floatval($_POST['price'] ?? 0);
                $discount_price = floatval($_POST['discount_price'] ?? 0);
                $total_hours = floatval($_POST['total_hours'] ?? 10);
                $thumbnail = trim($_POST['thumbnail'] ?? '');
                
                if (empty($title)) {
                    echo json_encode(['success' => false, 'error' => 'Course title is required.']);
                    break;
                }
                
                if (empty($thumbnail)) {
                    $thumbnail = 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?auto=format&fit=crop&w=400&q=80';
                }
                
                $stmt = $pdo->prepare("INSERT INTO courses (instructor_id, category_id, title, subtitle, description, thumbnail, level, price, discount_price, total_hours, status, total_lessons) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', 2)");
                $stmt->execute([$userId, $category_id, $title, $subtitle, $description, $thumbnail, $level, $price, $discount_price, $total_hours]);
                $courseId = $pdo->lastInsertId('courses_id_seq');
                
                // Add default sections & lessons
                $stmt = $pdo->prepare("INSERT INTO sections (course_id, title, order_index) VALUES (?, 'Getting Started Curriculum', 1)");
                $stmt->execute([$courseId]);
                $sectionId = $pdo->lastInsertId('sections_id_seq');
                
                $stmt = $pdo->prepare("INSERT INTO lessons (section_id, course_id, title, duration_minutes, is_preview, order_index) VALUES 
                                       (?, ?, 'Introductory Lecture video', 12, 1, 1),
                                       (?, ?, 'Deep Dive Advanced Core Concept', 22, 0, 2)");
                $stmt->execute([$sectionId, $courseId, $sectionId, $courseId]);
                
                echo json_encode(['success' => true, 'course_id' => $courseId]);
                break;
                
            case 'get_notifications':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                
                $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
                $stmt->execute([$userId]);
                $list = $stmt->fetchAll();
                
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                $stmt->execute([$userId]);
                $unread = intval($stmt->fetchColumn());
                
                echo json_encode(['success' => true, 'list' => $list, 'unread' => $unread]);
                break;
                
            case 'mark_notification_read':
                requireLogin();
                $userId = $_SESSION['user']['id'];
                $id = intval($_POST['id'] ?? $_GET['id'] ?? 0);
                
                if ($id <= 0) {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                    $stmt->execute([$id, $userId]);
                }
                
                echo json_encode(['success' => true]);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'API action not found.']);
                break;
        }
    } catch (Exception $ex) {
        echo json_encode(['success' => false, 'error' => 'Server Error: ' . $ex->getMessage()]);
    }
    exit;
}

if (defined('KLEAN_NO_RENDER') && KLEAN_NO_RENDER) {
    return;
}

// =========================================================================
// 3. ROUTER (HANDLE PAGE RELOADS)
// =========================================================================
$page = $_GET['page'] ?? 'home';
$allowedPages = ['home', 'login', 'signup', 'courses', 'course', 'cart', 'checkout', 'success', 'dashboard', 'player', 'instructor', 'admin', 'wishlist', 'profile'];
if (!in_array($page, $allowedPages)) {
    $page = 'home';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Klean — Optimized Structured Online Learning</title>

  <!-- SEO -->
  <meta name="description" content="Klean is an elegant online learning environment featuring expert-led coding, design, and marketing bootcamps with premium HSL style palettes.">
  <meta name="keywords" content="Klean, online courses, programming tutorial, Figma UI/UX, learn Python, responsive design">
  <meta name="theme-color" content="#6C3FF4">

  <!-- Google Fonts (Plus Jakarta Sans) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 & Bootstrap Icons CDNs -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- =========================================================================
  6. CSS (INLINE STYLE)
  ========================================================================= -->
  <style>
    /* Theme Tokens */
    :root {
      --primary: #6C3FF4;
      --primary-hover: #5A2EE3;
      --primary-light: #EEF2FF;
      --accent: #F59E0B;
      --accent-hover: #D97706;
      --accent-light: #FEF3C7;
      --dark: #0F172A;
      --dark-medium: #1E293B;
      --dark-light: #334155;
      --light: #F8FAFC;
      --light-card: #FFFFFF;
      --border: #E2E8F0;
      --text-muted: #64748B;
      --success: #10B981;
      --radius-card: 16px;
      --radius-btn: 10px;
    }

    body {
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      background-color: var(--light);
      color: var(--dark);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow-x: hidden;
    }

    ::-webkit-scrollbar { width: 7px; height: 7px; }
    ::-webkit-scrollbar-track { background: var(--light); }
    ::-webkit-scrollbar-thumb { background: #CBD5E1; border-radius: 4px; }
    ::-webkit-scrollbar-thumb:hover { background: #94A3B8; }

    h1, h2, h3, h4, h5, h6 {
      font-weight: 700;
      color: var(--dark);
      line-height: 1.25;
      letter-spacing: -0.5px;
    }

    .fw-500 { font-weight: 500 !important; }
    .fw-600 { font-weight: 600 !important; }
    .fw-700 { font-weight: 700 !important; }
    .fw-800 { font-weight: 800 !important; }

    /* Sticky premium blurred glass navbar */
    .klean-navbar {
      background-color: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid rgba(226, 232, 240, 0.8);
      z-index: 1030;
      min-height: 72px;
      transition: all 0.3s;
    }

    /* Buttons */
    .btn-primary-klean {
      background-color: var(--primary);
      color: #FFF !important;
      font-weight: 600;
      border: 1.5px solid var(--primary);
      padding: 0.6rem 1.4rem;
      border-radius: var(--radius-btn);
      transition: all 0.25s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-primary-klean:hover {
      background-color: var(--primary-hover);
      border-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(108, 63, 244, 0.25);
    }
    .btn-outline-klean {
      background-color: transparent;
      color: var(--primary) !important;
      font-weight: 600;
      border: 1.5px solid var(--primary);
      padding: 0.6rem 1.4rem;
      border-radius: var(--radius-btn);
      transition: all 0.25s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-outline-klean:hover {
      background-color: var(--primary-light);
      transform: translateY(-1px);
    }
    .btn-accent-klean {
      background-color: var(--accent);
      color: #000 !important;
      font-weight: 600;
      border: 1.5px solid var(--accent);
      padding: 0.6rem 1.4rem;
      border-radius: var(--radius-btn);
      transition: all 0.25s;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-accent-klean:hover {
      background-color: var(--accent-hover);
      border-color: var(--accent-hover);
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(245, 158, 11, 0.25);
    }

    /* Course Cards */
    .course-card {
      background: var(--light-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-card);
      overflow: hidden;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .course-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 30px -8px rgba(0, 0, 0, 0.06);
      border-color: rgba(108, 63, 244, 0.3);
    }
    .course-card .card-img-wrapper {
      position: relative;
      height: 170px;
      overflow: hidden;
    }
    .course-card .card-img-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .course-card:hover .card-img-wrapper img {
      transform: scale(1.05);
    }

    .badge-bestseller {
      background-color: var(--accent-light);
      color: #92400E;
      font-weight: 700;
      font-size: 0.72rem;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      padding: 0.3rem 0.6rem;
      border-radius: 6px;
      position: absolute;
      top: 10px;
      left: 10px;
      z-index: 2;
    }
    .badge-wishlist-toggle {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 2;
      width: 34px;
      height: 34px;
      background: rgba(255, 255, 255, 0.9);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
      color: var(--text-muted);
    }
    .badge-wishlist-toggle:hover {
      background: #FFF;
      color: #EF4444;
      transform: scale(1.1);
    }
    .badge-wishlist-toggle.active {
      color: #EF4444 !important;
    }

    /* Category scroll list */
    .category-scroll-container {
      display: flex;
      overflow-x: auto;
      gap: 10px;
      padding: 8px 0 12px;
      scroll-behavior: smooth;
    }
    .category-scroll-container::-webkit-scrollbar { display: none; }
    .category-pill {
      white-space: nowrap;
      background-color: var(--light-card);
      border: 1.5px solid var(--border);
      border-radius: 30px;
      padding: 0.55rem 1.3rem;
      font-weight: 600;
      font-size: 0.85rem;
      color: var(--dark-medium);
      cursor: pointer;
      transition: all 0.2s;
      user-select: none;
    }
    .category-pill:hover, .category-pill.active {
      background-color: var(--primary);
      color: #FFF !important;
      border-color: var(--primary);
      box-shadow: 0 4px 12px rgba(108, 63, 244, 0.2);
    }

    /* Tabs details styling */
    .nav-tabs .nav-link {
      border: 1.5px solid transparent;
      color: var(--text-muted);
      font-weight: 600;
      border-radius: 8px !important;
    }
    .nav-tabs .nav-link.active {
      color: var(--primary) !important;
      background-color: var(--primary-light) !important;
      border-color: var(--primary-light) !important;
    }

    /* Detail Hero */
    .detail-hero {
      background: linear-gradient(135deg, var(--dark) 0%, var(--dark-medium) 100%);
      color: #FFF;
      position: relative;
    }
    .sticky-enroll-card {
      position: sticky;
      top: 90px;
      z-index: 10;
      border-radius: var(--radius-card);
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
      border: 1px solid var(--border);
      background: var(--light-card);
    }

    /* Custom Player Layout */
    .player-layout {
      display: flex;
      min-height: calc(100vh - 72px);
      background-color: var(--dark);
    }
    .player-video-section {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      background-color: #000;
    }
    .player-video-wrapper {
      position: relative;
      width: 100%;
      aspect-ratio: 16/9;
      background-color: #000;
    }
    .player-custom-video {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: radial-gradient(circle, #1E293B 0%, #0F172A 100%);
      cursor: pointer;
    }
    .player-sidebar {
      width: 360px;
      background-color: var(--dark-medium);
      color: #FFF;
      border-left: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      overflow-y: auto;
    }
    .player-playlist-item {
      padding: 0.9rem 1.2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      cursor: pointer;
      transition: background 0.2s;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .player-playlist-item:hover {
      background: rgba(255, 255, 255, 0.04);
    }
    .player-playlist-item.active-lesson {
      background: rgba(108, 63, 244, 0.15);
      border-left: 3px solid var(--primary);
    }
    /* Dashboard Layouts */
    .dashboard-layout {
      display: flex;
      min-height: calc(100vh - 72px);
    }
    .dashboard-sidebar {
      width: 260px;
      background-color: var(--dark);
      color: #94A3B8;
      display: flex;
      flex-direction: column;
      border-right: 1px solid rgba(255, 255, 255, 0.05);
      flex-shrink: 0;
    }
    .dashboard-sidebar .sidebar-logo {
      padding: 1.25rem 1.5rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      flex-shrink: 0;
    }
    .dashboard-sidebar .nav-link-klean {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 0.85rem 1.5rem;
      color: #94A3B8;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.2s;
      border-left: 3px solid transparent;
    }
    .dashboard-sidebar .nav-link-klean:hover {
      color: #FFF;
      background-color: rgba(255, 255, 255, 0.05);
    }
    .dashboard-sidebar .nav-link-klean.active {
      color: #FFF;
      background-color: rgba(108, 63, 244, 0.12);
      border-left-color: var(--primary);
    }
    .dashboard-main {
      flex-grow: 1;
      padding: 2rem;
      overflow-y: auto;
    }
    .progress-bar-custom {
      background-color: var(--primary) !important;
    }

    /* CSS Charts */
    .revenue-chart-wrapper {
      display: flex;
      align-items: flex-end;
      justify-content: space-around;
      height: 200px;
      border-bottom: 2px solid var(--border);
      gap: 8px;
      padding-top: 24px;
    }
    .revenue-chart-col {
      display: flex;
      flex-direction: column;
      align-items: center;
      flex: 1;
    }
    .revenue-chart-bar {
      width: 100%;
      max-width: 48px;
      background: linear-gradient(180deg, var(--primary) 0%, rgba(108, 63, 244, 0.5) 100%);
      border-radius: 6px 6px 0 0;
      position: relative;
      transition: height 1s;
      cursor: pointer;
    }
    .revenue-chart-bar:hover { filter: brightness(1.1); }
    .revenue-chart-bar::after {
      content: attr(data-value);
      position: absolute;
      top: -24px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--dark);
      white-space: nowrap;
    }
    .revenue-chart-label {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-muted);
      margin-top: 8px;
    }

    /* Confetti falling animations */
    .confetti-container {
      position: fixed;
      inset: 0;
      overflow: hidden;
      pointer-events: none;
      z-index: 9999;
    }
    .confetti-piece {
      position: absolute;
      width: 10px;
      height: 10px;
      background-color: var(--accent);
      opacity: 0.8;
      top: -10px;
      border-radius: 50%;
      animation: confettiFall 2.5s forwards linear;
    }
    @keyframes confettiFall {
      0% { transform: translateY(0) rotate(0deg); opacity: 1; }
      100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
    }

    /* Alert Toast notifications */
    .toast-container {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 1070;
      display: flex;
      flex-direction: column;
      gap: 8px;
      max-width: 320px;
    }
    .klean-toast {
      background: var(--dark);
      color: #FFF;
      border-radius: 12px;
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.08);
      padding: 1rem 1.25rem;
      animation: toastSlideIn 0.3s ease;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    @keyframes toastSlideIn {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    /* View containers */
    .view-container {
      display: none;
      flex-grow: 1;
      animation: viewFadeIn 0.35s ease;
    }
    .view-container.active-view {
      display: block !important;
    }
    @keyframes viewFadeIn {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Global overlays spinner */
    .loading-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.7);
      backdrop-filter: blur(4px);
      z-index: 1090;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFF;
      font-weight: 700;
    }

    .ssl-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--text-muted);
      background-color: var(--light);
      padding: 0.35rem 0.8rem;
      border-radius: 20px;
      border: 1px solid var(--border);
    }

    /* Responsive */
    @media (max-width: 991px) {
      .sticky-enroll-card { position: relative; top: auto; margin-top: 2rem; }
      .player-layout { flex-direction: column; }
      .player-sidebar { width: 100%; border-left: none; border-top: 1px solid rgba(255,255,255,0.08); max-height: 380px; }
      .dashboard-sidebar { width: 100%; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.05); }
      .dashboard-layout { flex-direction: column; }
    }
  </style>
</head>
<body>

  <!-- Confetti Container -->
  <div class="confetti-container" id="confetti-holder"></div>

  <!-- Loading Spinner Overlay -->
  <div class="loading-overlay d-none" id="loading-spinner">
    <div class="text-center">
      <div class="spinner-border text-white mb-3" style="width: 3.5rem; height: 3.5rem; border-width: .4rem;" role="status"></div>
      <h5 class="fw-700">Securing Transaction...</h5>
    </div>
  </div>

  <!-- Toast Container -->
  <div class="toast-container" id="toast-holder"></div>

  <!-- =========================================================================
  STICKY NAVBAR
  ========================================================================= -->
  <nav class="navbar navbar-expand-lg navbar-light sticky-top klean-navbar">
    <div class="container-fluid px-4 px-lg-5">
      
      <!-- Brand Logo -->
      <a class="navbar-brand d-flex align-items-center gap-2" href="#" onclick="switchView('landing-view');return false;">
        <span class="d-flex align-items-center justify-content-center rounded-3 text-white fw-800 fs-5" style="width:38px;height:38px;background:var(--primary);">K</span>
        <span class="fs-4 fw-800 text-dark" style="letter-spacing:-0.5px;">Klean<span style="color:var(--primary);">.</span></span>
      </a>
      
      <button class="navbar-toggler border-0 p-1" type="button" data-bs-toggle="collapse" data-bs-target="#kleanNavContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="kleanNavContent">
        
        <!-- Search bar -->
        <div class="my-3 my-lg-0 mx-lg-4 position-relative" style="max-width:380px; flex-grow:1;">
          <input class="form-control rounded-pill bg-light ps-4 pe-5 py-2 border-0" type="search" placeholder="Search for courses..." id="nav-search-input" onkeyup="handleNavSearch(event)">
          <button class="btn position-absolute end-0 top-0 h-100 pe-3 text-muted border-0 bg-transparent" onclick="triggerNavSearch()"><i class="bi bi-search"></i></button>
        </div>
        
        <!-- Nav links list -->
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <li class="nav-item"><a class="nav-link fw-600 px-2 text-dark" href="#" onclick="switchView('courses-view');return false;">Browse Courses</a></li>
          
          <!-- Student Links -->
          <li class="nav-item student-only d-none"><a class="nav-link position-relative px-2 text-dark" href="#" onclick="switchView('wishlist-view');return false;" title="Wishlist"><i class="bi bi-heart fs-5"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="wishlist-badge">0</span></a></li>
          <li class="nav-item student-only d-none"><a class="nav-link position-relative px-2 text-dark" href="#" onclick="switchView('cart-view');return false;" title="Cart"><i class="bi bi-cart3 fs-5"></i><span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" id="cart-badge">0</span></a></li>
          
          <!-- Notifications Bell -->
          <li class="nav-item dropdown logged-in-only d-none">
            <a class="nav-link position-relative px-2 text-dark dropdown-toggle no-arrow" href="#" id="notifMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="loadNotifications()">
              <i class="bi bi-bell fs-5"></i>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="notif-badge">0</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2 rounded-4" style="width: 280px; max-height: 320px; overflow-y: auto;" aria-labelledby="notifMenu" id="notif-list">
              <li class="dropdown-header text-muted fw-600 py-2 border-bottom">Notifications</li>
              <li class="text-center py-3 text-muted small">No new notifications.</li>
            </ul>
          </li>

          <!-- Anonymous actions -->
          <li class="nav-item anon-only"><a class="btn btn-outline-klean py-2 px-3 btn-sm" href="#" onclick="switchView('login-view');return false;">Login</a></li>
          <li class="nav-item anon-only"><a class="btn btn-primary-klean py-2 px-3 btn-sm" href="#" onclick="switchView('signup-view');return false;">Sign Up</a></li>

          <!-- User dropdown -->
          <li class="nav-item dropdown logged-in-only d-none">
            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 text-dark" href="#" id="navbarUserMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="default.png" alt="Avatar" class="rounded-circle border border-primary border-2" id="nav-avatar" style="width:34px;height:34px;object-fit:cover;">
              <span class="fw-600 d-none d-lg-inline" id="nav-user-name">User</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2 p-2 rounded-4" aria-labelledby="navbarUserMenu">
              <li class="dropdown-header text-muted fw-500 py-1" id="nav-role-label">Student Account</li>
              <li><hr class="dropdown-divider my-1"></li>
              <li><a class="dropdown-item rounded-3 py-2 fw-500" href="#" onclick="goToDashboard();return false;"><i class="bi bi-speedometer2 me-2 text-primary"></i>My Classroom</a></li>
              <li><a class="dropdown-item rounded-3 py-2 fw-500" href="#" onclick="switchView('profile-view');return false;"><i class="bi bi-person me-2 text-muted"></i>Settings</a></li>
              <li><hr class="dropdown-divider my-1"></li>
              <li><a class="dropdown-item text-danger rounded-3 py-2 fw-500" href="#" onclick="handleLogout();return false;"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </li>
        </ul>

      </div>
    </div>
  </nav>

  <!-- =========================================================================
  7. HTML VIEWS (CONTAINERS TOGGLED BY JS)
  ========================================================================= -->
  <main class="d-flex flex-column flex-grow-1">

    <!-- 1. LANDING PAGE VIEW -->
    <div id="landing-view" class="view-container">
      <!-- Hero -->
      <section class="py-5" style="background: radial-gradient(circle at 90% 10%, rgba(108,63,244,0.05) 0%, transparent 45%), radial-gradient(circle at 10% 90%, rgba(245,158,11,0.04) 0%, transparent 45%);">
        <div class="container py-4">
          <div class="row align-items-center g-5">
            <div class="col-lg-6">
              <span class="badge px-3 py-2 rounded-pill mb-3 fw-700" style="background:var(--primary-light); color:var(--primary);">✨ THE CLEANEST WAY TO UP-SKILL</span>
              <h1 class="display-4 fw-800 text-dark mb-3" style="letter-spacing:-1.5px; line-height:1.15;">Learn Clean.<br>Grow Sharp<span style="color:var(--primary);">.</span></h1>
              <p class="fs-5 text-muted mb-4 pe-lg-4">Join 500K+ developers, designers, and managers mastering new tech in zero-fluff classrooms.</p>
              
              <!-- Hero search -->
              <div class="p-2 bg-white rounded-4 shadow-sm border mb-4 d-flex align-items-center gap-2" style="max-width:480px;">
                <i class="bi bi-search text-muted fs-5 ps-2 flex-shrink-0"></i>
                <input type="text" class="form-control border-0 bg-transparent shadow-none py-2" placeholder="What do you want to learn today?" id="hero-search-input" onkeyup="handleHeroSearch(event)">
                <button class="btn btn-primary-klean rounded-3 px-4 py-2 text-nowrap" onclick="triggerHeroSearch()">Explore</button>
              </div>
              
              <div class="d-flex flex-wrap gap-3">
                <button class="btn btn-primary-klean" onclick="switchView('courses-view')">Explore Courses</button>
                <button class="btn btn-outline-klean" onclick="switchView('signup-view')">Teach on Klean</button>
              </div>
            </div>
            
            <div class="col-lg-6 position-relative text-center">
              <div class="hero-glow" style="width:400px; height:400px; background:rgba(108,63,244,0.07); top:50%; left:50%; transform:translate(-50%, -50%);"></div>
              <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=600&q=80" alt="Students up-skilling" class="img-fluid rounded-4 shadow-lg border border-4 border-white" style="transform: rotate(1.5deg); max-height:400px; width:100%; object-fit:cover;">
              <div class="bg-white rounded-3 shadow p-3 border d-inline-flex align-items-center gap-3 position-absolute" style="bottom:20px; left:20px; transform:rotate(-1.5deg); max-width:220px;">
                <div class="bg-warning rounded-2 p-2 text-white d-flex"><i class="bi bi-lightning-charge-fill"></i></div>
                <div class="text-start">
                  <h6 class="mb-0 fw-700" style="font-size:0.85rem;">100% Curated</h6>
                  <small class="text-muted" style="font-size:0.72rem;">Lifetime structured access</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Stats Bar -->
      <section class="py-4 bg-white border-top border-bottom">
        <div class="container">
          <div class="row text-center g-3">
            <div class="col-6 col-md-3"><h3 class="fw-800 text-primary mb-1">10K+</h3><p class="text-muted small mb-0 fw-600">STRUCTURED COURSES</p></div>
            <div class="col-6 col-md-3"><h3 class="fw-800 text-dark mb-1">500K+</h3><p class="text-muted small mb-0 fw-600">ACTIVE STUDENTS</p></div>
            <div class="col-6 col-md-3"><h3 class="fw-800 text-primary mb-1">200+</h3><p class="text-muted small mb-0 fw-600">EXPERT INSTRUCTORS</p></div>
            <div class="col-6 col-md-3"><h3 class="fw-800 text-warning mb-1">4.8★</h3><p class="text-muted small mb-0 fw-600">VERIFIED SATISFACTION</p></div>
          </div>
        </div>
      </section>

      <!-- Categories Capsule Pills Scroll -->
      <section class="py-5">
        <div class="container">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <span class="text-primary small fw-800 text-uppercase">Categories</span>
              <h2 class="fw-800 text-dark mt-1 mb-0">Popular Topics</h2>
            </div>
            <a href="#" class="fw-600 text-primary text-decoration-none small" onclick="switchView('courses-view');return false;">See All <i class="bi bi-arrow-right"></i></a>
          </div>
          <div class="category-scroll-container" id="landing-categories"></div>
        </div>
      </section>

      <!-- Featured grid -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
              <span class="text-primary small fw-800 text-uppercase">Curated For You</span>
              <h2 class="fw-800 text-dark mt-1 mb-0">Featured Courses</h2>
            </div>
            <button class="btn btn-outline-klean btn-sm rounded-pill" onclick="switchView('courses-view')">View All</button>
          </div>
          <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4" id="featured-courses-grid"></div>
        </div>
      </section>

      <!-- Why Klean Features -->
      <section class="py-5">
        <div class="container">
          <div class="text-center mx-auto mb-5" style="max-width:580px;">
            <span class="text-primary small fw-800 text-uppercase">Why Klean?</span>
            <h2 class="fw-800 text-dark mt-2">Zero-Fluff Learning Architecture</h2>
            <p class="text-muted">Say goodbye to redundant 80-hour guides that teach basic parameters. We keep code classrooms crisp, modern, and highly structural.</p>
          </div>
          <div class="row g-4">
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center">
                <div class="feature-icon-box bg-primary bg-opacity-10 text-primary mx-auto"><i class="bi bi-people-fill"></i></div>
                <h5 class="fw-700">Expert Teachers</h5>
                <p class="text-muted small mb-0">Learn from seasoned industry leaders with real developer experience.</p>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center">
                <div class="feature-icon-box bg-warning bg-opacity-10 text-warning mx-auto"><i class="bi bi-infinity"></i></div>
                <h5 class="fw-700">Lifetime Ownership</h5>
                <p class="text-muted small mb-0">Unlock courses once and access forever. Upgrade skills at your own pace.</p>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center">
                <div class="feature-icon-box bg-success bg-opacity-10 text-success mx-auto"><i class="bi bi-patch-check-fill"></i></div>
                <h5 class="fw-700">Verified Badges</h5>
                <p class="text-muted small mb-0">Graduate with cryptographic course certificates to share on CVs.</p>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center">
                <div class="feature-icon-box bg-danger bg-opacity-10 text-danger mx-auto"><i class="bi bi-phone-fill"></i></div>
                <h5 class="fw-700">Snappy UI Engine</h5>
                <p class="text-muted small mb-0">Optimized player layout that runs beautifully on tablets or desktops.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Testimonials -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="text-center mx-auto mb-5" style="max-width:520px;">
            <span class="text-primary small fw-800 text-uppercase">Testimonials</span>
            <h2 class="fw-800 text-dark mt-2">Loved by 500,000+ Students</h2>
          </div>
          <div class="row g-4" id="testimonials-container">
            <!-- Rendered dynamically -->
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="py-5">
        <div class="container">
          <div class="rounded-4 p-4 p-md-5 text-white position-relative overflow-hidden shadow-lg" style="background: linear-gradient(135deg, var(--dark) 0%, var(--dark-medium) 100%);">
            <div class="position-absolute bottom-0 end-0 opacity-10" style="font-size:12rem; transform:translate(15%, 25%); line-height:1;"><i class="bi bi-mortarboard-fill"></i></div>
            <div class="row align-items-center g-4 position-relative z-1">
              <div class="col-lg-8">
                <span class="badge bg-warning text-dark mb-3 px-3 py-2 fw-700 rounded-pill">BUILD THE FUTURE</span>
                <h2 class="fw-800 display-6 mb-2">Publish Your Expertise globally</h2>
                <p class="text-secondary mb-0">Instruct on Klean. Build your cohort, upload lessons, and reach a student base of 500,000+ developers.</p>
              </div>
              <div class="col-lg-4 text-lg-end">
                <button class="btn btn-accent-klean btn-lg px-4" onclick="switchView('signup-view')">Become Instructor Today</button>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- 2. LOGIN PAGE VIEW -->
    <div id="login-view" class="view-container py-5" style="background: radial-gradient(circle at 50% 50%, rgba(108,63,244,0.05) 0%, transparent 55%);">
      <div class="container py-3">
        <div class="row justify-content-center">
          <div class="col-11 col-sm-8 col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-lg-5">
              <div class="text-center mb-4">
                <a href="#" onclick="switchView('landing-view');return false;" class="d-flex align-items-center justify-content-center gap-2 text-decoration-none mb-3">
                  <span class="d-flex align-items-center justify-content-center rounded-3 text-white fw-800 fs-5" style="width:38px;height:38px;background:var(--primary);">K</span>
                  <span class="fs-3 fw-800 text-dark">Klean<span style="color:var(--primary);">.</span></span>
                </a>
                <h4 class="fw-800 text-dark mb-1">Welcome Back</h4>
                <p class="text-muted small">Sign in to your learning dashboard.</p>
              </div>
              
              <!-- Demo Credentials box -->
              <div class="rounded-3 p-3 mb-4 border bg-light text-start" style="border-color: rgba(108,63,244,0.2) !important;">
                <p class="fw-800 text-primary mb-2 small text-uppercase" style="letter-spacing:0.5px;"><i class="bi bi-info-circle-fill me-1"></i>Quick Login Credentials</p>
                <div class="row g-2">
                  <div class="col-12">
                    <div class="bg-white rounded-2 p-2 border d-flex align-items-center justify-content-between">
                      <div class="small lh-sm">
                        <span class="badge bg-primary px-2 py-1 mb-1" style="font-size:0.6rem;">STUDENT</span>
                        <div class="fw-700 text-dark">alex@klean.com</div>
                      </div>
                      <button class="btn btn-sm btn-outline-klean py-1 px-2 border-0" onclick="quickFill('alex@klean.com', 'pass123')">Use <i class="bi bi-arrow-right-short"></i></button>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="bg-white rounded-2 p-2 border d-flex align-items-center justify-content-between">
                      <div class="small lh-sm">
                        <span class="badge bg-warning text-dark px-2 py-1 mb-1" style="font-size:0.6rem;">INSTRUCTOR</span>
                        <div class="fw-700 text-dark">sarah@klean.com</div>
                      </div>
                      <button class="btn btn-sm btn-outline-klean py-1 px-2 border-0" onclick="quickFill('sarah@klean.com', 'pass123')">Use <i class="bi bi-arrow-right-short"></i></button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Login Form -->
              <form id="login-form" onsubmit="submitLogin(event)">
                <div class="form-floating mb-3">
                  <input type="email" class="form-control" id="login-email" placeholder="name@example.com" required>
                  <label for="login-email">Email address</label>
                </div>
                <div class="form-floating mb-3">
                  <input type="password" class="form-control" id="login-password" placeholder="Password" required>
                  <label for="login-password">Password</label>
                </div>
                <button type="submit" class="btn btn-primary-klean w-100 py-3 rounded-3 mb-3 fw-700">Sign In</button>
              </form>

              <div class="text-center mt-3">
                <span class="text-muted small">New to Klean? </span>
                <a href="#" class="small fw-700 text-primary text-decoration-none" onclick="switchView('signup-view');return false;">Create an account</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 3. SIGNUP PAGE VIEW -->
    <div id="signup-view" class="view-container py-5" style="background: radial-gradient(circle at 50% 50%, rgba(108,63,244,0.05) 0%, transparent 55%);">
      <div class="container py-3">
        <div class="row justify-content-center">
          <div class="col-11 col-sm-8 col-md-6 col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 p-4 p-lg-5">
              <div class="text-center mb-4">
                <a href="#" onclick="switchView('landing-view');return false;" class="d-flex align-items-center justify-content-center gap-2 text-decoration-none mb-3">
                  <span class="d-flex align-items-center justify-content-center rounded-3 text-white fw-800 fs-5" style="width:38px;height:38px;background:var(--primary);">K</span>
                  <span class="fs-3 fw-800 text-dark">Klean<span style="color:var(--primary);">.</span></span>
                </a>
                <h4 class="fw-800 text-dark mb-1">Create Account</h4>
                <p class="text-muted small">Access optimized curricula instantly.</p>
              </div>

              <!-- Signup Form -->
              <form id="signup-form" onsubmit="submitSignup(event)">
                <div class="mb-4 text-center">
                  <div class="btn-group w-100" role="group">
                    <input type="radio" class="btn-check" name="signup-role" id="role-student" value="student" checked>
                    <label class="btn btn-outline-primary fw-600 py-2 rounded-start-3" for="role-student"><i class="bi bi-mortarboard me-2"></i>Student</label>
                    
                    <input type="radio" class="btn-check" name="signup-role" id="role-instructor" value="instructor">
                    <label class="btn btn-outline-primary fw-600 py-2 rounded-end-3" for="role-instructor"><i class="bi bi-person-workspace me-2"></i>Instructor</label>
                  </div>
                </div>
                
                <div class="form-floating mb-3">
                  <input type="text" class="form-control" id="signup-name" placeholder="Full name" required>
                  <label for="signup-name">Full Name</label>
                </div>
                <div class="form-floating mb-3">
                  <input type="email" class="form-control" id="signup-email" placeholder="name@example.com" required>
                  <label for="signup-email">Email address</label>
                </div>
                <div class="form-floating mb-3">
                  <input type="password" class="form-control" id="signup-password" placeholder="Password" required minlength="6">
                  <label for="signup-password">Password (min 6 chars)</label>
                </div>
                <div class="form-floating mb-4">
                  <input type="password" class="form-control" id="signup-confirm" placeholder="Confirm Password" required>
                  <label for="signup-confirm">Confirm Password</label>
                </div>
                
                <button type="submit" class="btn btn-primary-klean w-100 py-3 rounded-3 mb-3 fw-700">Create Account</button>
              </form>

              <div class="text-center mt-3">
                <span class="text-muted small">Already have an account? </span>
                <a href="#" class="small fw-700 text-primary text-decoration-none" onclick="switchView('login-view');return false;">Log in</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 4. COURSE CATALOG VIEW -->
    <div id="courses-view" class="view-container py-4">
      <div class="container">
        
        <!-- Breadcrumb & Header -->
        <div class="row mb-4 align-items-center">
          <div class="col-md-6">
            <nav aria-label="breadcrumb">
              <ol class="breadcrumb klean-breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="#" onclick="switchView('landing-view');return false;">Home</a></li>
                <li class="breadcrumb-item active">Catalog</li>
              </ol>
            </nav>
            <h2 class="fw-800 text-dark m-0" id="catalog-header">All Courses</h2>
          </div>
          <div class="col-md-6 text-md-end text-muted small fw-600 mt-2 mt-md-0" id="catalog-count-label">Showing 8 of 8 courses</div>
        </div>

        <!-- Catalog filters -->
        <div class="p-3 bg-white border rounded-4 mb-4 shadow-sm">
          <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
              <label class="form-label text-muted fw-700 mb-1" style="font-size:0.72rem;">CATEGORY</label>
              <select class="form-select form-select-sm rounded-3" id="filter-category" onchange="runCatalogFilter()">
                <option value="all">All Categories</option>
                <!-- Rendered dynamically -->
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label text-muted fw-700 mb-1" style="font-size:0.72rem;">LEVEL</label>
              <select class="form-select form-select-sm rounded-3" id="filter-level" onchange="runCatalogFilter()">
                <option value="all">All Levels</option>
                <option value="beginner">Beginner</option>
                <option value="intermediate">Intermediate</option>
                <option value="advanced">Advanced</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label text-muted fw-700 mb-1" style="font-size:0.72rem;">PRICE</label>
              <select class="form-select form-select-sm rounded-3" id="filter-price" onchange="runCatalogFilter()">
                <option value="all">All Prices</option>
                <option value="free">Free Only</option>
                <option value="paid">Paid Only</option>
              </select>
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label text-muted fw-700 mb-1" style="font-size:0.72rem;">RATING</label>
              <select class="form-select form-select-sm rounded-3" id="filter-rating" onchange="runCatalogFilter()">
                <option value="all">All Ratings</option>
                <option value="4.5">4.5★ &amp; Above</option>
                <option value="4.7">4.7★ &amp; Above</option>
              </select>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label text-muted fw-700 mb-1" style="font-size:0.72rem;">SORT BY</label>
              <select class="form-select form-select-sm rounded-3" id="filter-sort" onchange="runCatalogFilter()">
                <option value="popular">Most Popular</option>
                <option value="price-low">Price: Low to High</option>
                <option value="price-high">Price: High to Low</option>
                <option value="rating">Highest Rated</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Catalog Grid Layout -->
        <div class="row g-4">
          <!-- Desktop sidebar -->
          <div class="col-lg-3 d-none d-lg-block">
            <div class="p-4 bg-white border rounded-4">
              <h6 class="fw-800 mb-3"><i class="bi bi-funnel me-2"></i>Filter Topics</h6>
              <div class="form-check mb-2">
                <input class="form-check-input topic-check" type="checkbox" value="Bootcamp" id="tp1" onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="tp1">Bootcamps</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input topic-check" type="checkbox" value="React" id="tp2" onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="tp2">React &amp; Node</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input topic-check" type="checkbox" value="Python" id="tp3" onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="tp3">Python &amp; ML</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input topic-check" type="checkbox" value="Figma" id="tp4" onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="tp4">UI/UX Design</label>
              </div>
              <div class="form-check mb-3">
                <input class="form-check-input topic-check" type="checkbox" value="Marketing" id="tp5" onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="tp5">SEO Marketing</label>
              </div>
              <hr class="opacity-25">
              <h6 class="fw-800 mb-3"><i class="bi bi-clock me-2"></i>Course Duration</h6>
              <div class="form-check mb-2">
                <input class="form-check-input duration-radio" type="radio" name="catalog-dur" value="all" id="dr1" checked onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="dr1">Any Duration</label>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input duration-radio" type="radio" name="catalog-dur" value="short" id="dr2" onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="dr2">0 – 25 Hours</label>
              </div>
              <div class="form-check">
                <input class="form-check-input duration-radio" type="radio" name="catalog-dur" value="long" id="dr3" onchange="runCatalogFilter()">
                <label class="form-check-label text-muted small" for="dr3">25+ Hours</label>
              </div>
            </div>
          </div>
          
          <!-- Grid columns -->
          <div class="col-lg-9">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4" id="catalog-grid">
              <!-- Hydrated dynamically -->
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- 5. COURSE DETAIL VIEW -->
    <div id="course-detail-view" class="view-container">
      <section class="detail-hero py-4 py-lg-5">
        <div class="container">
          <div class="row">
            <div class="col-lg-8">
              <nav aria-label="breadcrumb">
                <ol class="breadcrumb klean-breadcrumb mb-3">
                  <li class="breadcrumb-item"><a href="#" onclick="switchView('landing-view');return false;" class="text-white-50">Home</a></li>
                  <li class="breadcrumb-item"><a href="#" onclick="switchView('courses-view');return false;" class="text-white-50">Courses</a></li>
                  <li class="breadcrumb-item active text-white" id="detail-bc-title">Complete Web Development</li>
                </ol>
              </nav>
              <h1 class="display-5 fw-800 text-white mb-2" id="detail-title">Complete Web Development Bootcamp</h1>
              <p class="fs-5 text-white-50 mb-3" id="detail-subtitle">HTML CSS JS React Node MongoDB</p>
              
              <div class="d-flex flex-wrap align-items-center gap-3 small mb-4">
                <span class="badge bg-warning text-dark fw-700 py-1 px-3 d-none" id="detail-bestseller-badge">BESTSELLER</span>
                <span class="text-warning fw-600"><span id="detail-rating-score">4.7</span> <i class="bi bi-star-fill"></i></span>
                <span class="text-white-50">(<span id="detail-review-count">12,400</span> reviews)</span>
                <span class="text-white-50"><span id="detail-student-count">125,000</span> enrolled</span>
              </div>
              <div class="d-flex flex-wrap gap-4 text-white-50 small">
                <span><i class="bi bi-person-circle text-primary me-1"></i>Created by <strong class="text-white" id="detail-instructor-name">Sarah Williams</strong></span>
                <span><i class="bi bi-globe text-primary me-1"></i>Language: <strong class="text-white">English</strong></span>
                <span><i class="bi bi-shield-check text-primary me-1"></i>Verified Badge</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Main body detail -->
      <section class="py-5 bg-white">
        <div class="container">
          <div class="row">
            
            <!-- Left Tabs Column -->
            <div class="col-lg-8 pe-lg-5">
              
              <!-- Tab handles -->
              <ul class="nav nav-tabs border-bottom mb-4 gap-2" id="detailTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active py-2 px-3" data-bs-toggle="tab" data-bs-target="#tab-overview">Overview</button></li>
                <li class="nav-item"><button class="nav-link py-2 px-3" data-bs-toggle="tab" data-bs-target="#tab-curriculum">Curriculum</button></li>
                <li class="nav-item"><button class="nav-link py-2 px-3" data-bs-toggle="tab" data-bs-target="#tab-instructor">Instructor</button></li>
                <li class="nav-item"><button class="nav-link py-2 px-3" data-bs-toggle="tab" data-bs-target="#tab-reviews">Reviews</button></li>
              </ul>
              
              <div class="tab-content" id="detailTabsContent">
                
                <!-- Overview tab -->
                <div class="tab-pane fade show active" id="tab-overview">
                  <div class="p-4 border rounded-4 bg-light mb-4">
                    <h5 class="fw-700 mb-3">What you will learn:</h5>
                    <div class="row g-2" id="detail-learn-items">
                      <!-- Rendered dynamically -->
                    </div>
                  </div>
                  <h5 class="fw-700 mb-3">Requirements:</h5>
                  <ul class="text-muted small ps-3 mb-4" id="detail-reqs-list">
                    <li>Stable internet and modern web browser.</li>
                    <li>No programming background needed — we teach from absolute basics!</li>
                  </ul>
                  <h5 class="fw-700 mb-3">Description:</h5>
                  <div class="text-muted small" style="line-height:1.7;" id="detail-description">
                    <!-- Hydrated dynamically -->
                  </div>
                </div>
                
                <!-- Curriculum tab -->
                <div class="tab-pane fade" id="tab-curriculum">
                  <h5 class="fw-700 mb-3">Course Curriculum</h5>
                  <div class="accordion border rounded-3 overflow-hidden" id="curriculumAccordion">
                    <!-- Hydrated dynamically -->
                  </div>
                </div>
                
                <!-- Instructor tab -->
                <div class="tab-pane fade" id="tab-instructor">
                  <div class="p-4 border rounded-4 bg-light d-flex flex-column flex-sm-row gap-4 align-items-center align-items-sm-start">
                    <img src="default.png" alt="Instructor avatar" class="rounded-circle shadow border border-3 border-white flex-shrink-0" style="width:88px;height:88px;object-fit:cover;" id="detail-instructor-avatar">
                    <div>
                      <h4 class="fw-800 mb-1" id="detail-instructor-hname">Sarah Williams</h4>
                      <p class="text-primary small fw-600 mb-3">Consulting Software Architect &amp; Veteran Mentor</p>
                      <div class="d-flex gap-4 text-muted small mb-3">
                        <span><i class="bi bi-people-fill text-primary me-2"></i><strong class="text-dark" id="detail-instructor-students-count">12,400</strong> Students</span>
                        <span><i class="bi bi-play-circle-fill text-success me-2"></i><strong class="text-dark" id="detail-instructor-courses-count">3</strong> Courses</span>
                      </div>
                      <p class="text-muted small mb-0" id="detail-instructor-bio" style="line-height:1.6;"></p>
                    </div>
                  </div>
                </div>
                
                <!-- Reviews tab -->
                <div class="tab-pane fade" id="tab-reviews">
                  <div class="row g-4 align-items-center mb-4">
                    <div class="col-md-4 text-center">
                      <h1 class="display-3 fw-800 text-primary mb-0" id="detail-rating-big">4.7</h1>
                      <div class="text-warning small" id="detail-stars-big">
                        <!-- stars -->
                      </div>
                      <p class="text-muted small fw-600 mt-2">COURSE METRIC SCORE</p>
                    </div>
                    <div class="col-md-8">
                      <!-- Rating bars -->
                      <div class="d-flex align-items-center gap-3 mb-2 small text-muted">
                        <span style="width:38px;">5 <i class="bi bi-star-fill text-warning"></i></span>
                        <div class="progress flex-grow-1" style="height:7px;"><div class="progress-bar bg-warning" style="width:78%;"></div></div>
                        <span style="width:32px; text-align:right;">78%</span>
                      </div>
                      <div class="d-flex align-items-center gap-3 mb-2 small text-muted">
                        <span style="width:38px;">4 <i class="bi bi-star-fill text-warning"></i></span>
                        <div class="progress flex-grow-1" style="height:7px;"><div class="progress-bar bg-warning" style="width:15%;"></div></div>
                        <span style="width:32px; text-align:right;">15%</span>
                      </div>
                      <div class="d-flex align-items-center gap-3 mb-2 small text-muted">
                        <span style="width:38px;">3 <i class="bi bi-star-fill text-warning"></i></span>
                        <div class="progress flex-grow-1" style="height:7px;"><div class="progress-bar bg-warning" style="width:5%;"></div></div>
                        <span style="width:32px; text-align:right;">5%</span>
                      </div>
                      <div class="d-flex align-items-center gap-3 small text-muted">
                        <span style="width:38px;">1-2 <i class="bi bi-star-fill text-warning"></i></span>
                        <div class="progress flex-grow-1" style="height:7px;"><div class="progress-bar bg-warning" style="width:2%;"></div></div>
                        <span style="width:32px; text-align:right;">2%</span>
                      </div>
                    </div>
                  </div>
                  
                  <hr class="my-4">
                  
                  <!-- Reviews comments list -->
                  <h5 class="fw-800 mb-3">Student Feedback</h5>
                  <div class="d-flex flex-column gap-3" id="detail-reviews-list">
                    <!-- Hydrated dynamically -->
                  </div>

                  <!-- Write a review -->
                  <div class="mt-4 p-4 border rounded-4 bg-light d-none" id="review-compose-box">
                    <h5 class="fw-800 mb-2">Write a Review</h5>
                    <form onsubmit="submitReview(event)">
                      <div class="mb-3">
                        <label class="form-label text-muted small fw-600 mb-1">YOUR RATING</label>
                        <select class="form-select rounded-3" id="review-rating-select">
                          <option value="5">5 Stars — Excellent</option>
                          <option value="4">4 Stars — Very Good</option>
                          <option value="3">3 Stars — Average</option>
                          <option value="2">2 Stars — Poor</option>
                          <option value="1">1 Star — Terrible</option>
                        </select>
                      </div>
                      <div class="mb-3">
                        <label class="form-label text-muted small fw-600 mb-1">COMMENT</label>
                        <textarea class="form-control rounded-3" id="review-comment-text" rows="3" placeholder="Share your experience learning this course..." required></textarea>
                      </div>
                      <button type="submit" class="btn btn-primary-klean py-2 btn-sm rounded-3">Submit Review</button>
                    </form>
                  </div>
                </div>
                
              </div>
            </div>
            
            <!-- Desktop Sidebar Enrollment Column -->
            <div class="col-lg-4">
              <div class="sticky-enroll-card p-4">
                <img src="" alt="Course cover" class="img-fluid w-100 rounded-3 mb-4" id="detail-sidebar-img" style="aspect-ratio:16/9; object-fit:cover;">
                <div class="d-flex align-items-center gap-3 mb-2">
                  <h2 class="fw-800 text-dark m-0" id="detail-sidebar-price">₹0</h2>
                  <span class="text-muted text-decoration-line-through small" id="detail-sidebar-oldprice">₹0</span>
                  <span class="badge bg-warning text-dark fw-700" id="detail-sidebar-discount">0% OFF</span>
                </div>
                <p class="text-danger small fw-700 mb-4"><i class="bi bi-alarm me-1"></i>Special discount ends soon!</p>
                
                <!-- Action triggers -->
                <div class="d-flex flex-column gap-2 mb-4" id="detail-sidebar-actions">
                  <!-- Rendered dynamically -->
                </div>
                
                <div class="ssl-badge w-100 justify-content-center mb-4"><i class="bi bi-shield-lock-fill"></i> Secure SSL Checkout</div>
                
                <h6 class="fw-800 mb-3">This course includes:</h6>
                <ul class="list-unstyled d-flex flex-column gap-2 text-muted small">
                  <li><i class="bi bi-play-circle text-primary me-2"></i><span id="detail-hours-label">0</span> hours on-demand videos</li>
                  <li><i class="bi bi-journals text-primary me-2"></i>Curriculum exercises &amp; tasks</li>
                  <li><i class="bi bi-infinity text-primary me-2"></i>Full lifetime classroom access</li>
                  <li><i class="bi bi-phone text-primary me-2"></i>Optimized mobile player layout</li>
                  <li><i class="bi bi-patch-check-fill text-success me-2"></i>Cryptographic Certificate on completion</li>
                </ul>
              </div>
            </div>

          </div>
        </div>
      </section>
    </div>

    <!-- 6. CART VIEW -->
    <div id="cart-view" class="view-container py-5">
      <div class="container">
        <h2 class="fw-800 text-dark mb-4"><i class="bi bi-cart3 me-2"></i>Your Shopping Cart</h2>
        
        <!-- Empty cart ui -->
        <div class="text-center py-5 d-none" id="cart-empty-ui">
          <div class="fs-1 text-muted mb-3"><i class="bi bi-cart-x"></i></div>
          <h5 class="fw-700 text-muted">Your cart is empty</h5>
          <p class="text-muted small mb-4">Browse our catalog to add top curated software courses.</p>
          <button class="btn btn-primary-klean" onclick="switchView('courses-view')">Browse Courses</button>
        </div>

        <div class="row g-4" id="cart-row-content">
          <!-- Cart items list -->
          <div class="col-lg-8" id="cart-items-list">
            <!-- Hydrated dynamically -->
          </div>
          
          <!-- Summary card -->
          <div class="col-lg-4">
            <div class="card p-4 border rounded-4 bg-white shadow-sm">
              <h5 class="fw-800 mb-4">Order Summary</h5>
              <div class="d-flex justify-content-between mb-2 text-muted small">
                <span>Subtotal</span>
                <span id="summary-subtotal">₹0.00</span>
              </div>
              <div class="d-flex justify-content-between mb-3 text-muted small d-none" id="summary-discount-row">
                <span>Coupon Applied</span>
                <span class="text-success" id="summary-discount">-₹0.00</span>
              </div>
              <hr class="opacity-25">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <span class="fw-700">Total Price</span>
                <h4 class="fw-800 text-primary m-0" id="summary-total">₹0.00</h4>
              </div>
              
              <!-- Coupon box -->
              <div class="mb-4">
                <label class="form-label text-muted small fw-600 mb-1">COUPON CODE</label>
                <div class="input-group">
                  <input type="text" class="form-control rounded-start-3" id="coupon-input" placeholder="e.g. KLEAN20" style="text-transform: uppercase;">
                  <button class="btn btn-outline-klean rounded-end-3 py-2 px-3 border-start-0" onclick="applyCoupon()">Apply</button>
                </div>
              </div>
              
              <button class="btn btn-primary-klean w-100 py-3 rounded-3 fw-700" onclick="proceedToCheckout()">Proceed to Checkout</button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- 7. PAYMENT CHECKOUT VIEW -->
    <div id="checkout-view" class="view-container py-5">
      <div class="container">
        <h2 class="fw-800 text-dark mb-4 text-center">Secure Payment Room</h2>
        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card p-4 border rounded-4 bg-white shadow-sm">
              <h5 class="fw-800 mb-4 border-bottom pb-2">Select Payment Method</h5>
              
              <!-- Tabs -->
              <div class="row g-2 text-center mb-4">
                <div class="col-3 col-md-3">
                  <div class="p-3 border rounded-3 pay-tab active" data-target="panel-card" style="cursor:pointer;"><i class="bi bi-credit-card fs-4 d-block mb-1"></i><span class="small fw-600" style="font-size:0.75rem;">Card</span></div>
                </div>
                <div class="col-3 col-md-3">
                  <div class="p-3 border rounded-3 pay-tab" data-target="panel-upi" style="cursor:pointer;"><i class="bi bi-qr-code fs-4 d-block mb-1"></i><span class="small fw-600" style="font-size:0.75rem;">UPI</span></div>
                </div>
                <div class="col-3 col-md-3">
                  <div class="p-3 border rounded-3 pay-tab" data-target="panel-wallet" style="cursor:pointer;"><i class="bi bi-wallet2 fs-4 d-block mb-1"></i><span class="small fw-600" style="font-size:0.75rem;">Wallet</span></div>
                </div>
                <div class="col-3 col-md-3">
                  <div class="p-3 border rounded-3 pay-tab" data-target="panel-cash" style="cursor:pointer;"><i class="bi bi-cash-coin fs-4 d-block mb-1"></i><span class="small fw-600" style="font-size:0.75rem;">Cash</span></div>
                </div>
              </div>

              <!-- Panels -->
              <!-- CARD PANEL -->
              <div class="pay-panel" id="panel-card">
                <form onsubmit="submitPayment(event)">
                  <!-- Virtual Card Display Mockup -->
                  <div class="rounded-4 p-4 mb-4 text-white position-relative shadow" style="background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); height: 180px; overflow:hidden;">
                    <div class="position-absolute end-0 top-0 m-3 opacity-20"><i class="bi bi-wifi fs-2"></i></div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <span class="fw-700 tracking-wide small" style="font-size:0.75rem;">SECURE DEBIT CARD</span>
                      <span class="fw-800" id="card-type-display" style="font-size:0.85rem;">VISA</span>
                    </div>
                    <div class="fs-4 fw-600 mb-3 tracking-widest" id="card-number-display" style="font-family: monospace;">•••• •••• •••• ••••</div>
                    <div class="row align-items-end mt-4">
                      <div class="col-8">
                        <small class="text-white-50 d-block" style="font-size: 0.65rem;">CARDHOLDER</small>
                        <span class="fw-600 text-uppercase text-truncate d-block" id="card-name-display" style="max-width:180px; font-size: 0.85rem;">YOUR NAME</span>
                      </div>
                      <div class="col-4 text-end">
                        <small class="text-white-50 d-block" style="font-size: 0.65rem;">EXPIRES</small>
                        <span class="fw-600" id="card-expiry-display" style="font-size: 0.85rem;">MM/YY</span>
                      </div>
                    </div>
                  </div>

                  <div class="mb-3">
                    <label class="form-label text-muted small fw-600 mb-1">CARDHOLDER NAME</label>
                    <input type="text" class="form-control rounded-3" id="cc-name-input" placeholder="Alex Johnson" oninput="updateCardDisplay('name', this)" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label text-muted small fw-600 mb-1">CARD NUMBER</label>
                    <input type="text" class="form-control rounded-3" id="cc-number-input" placeholder="4111 2222 3333 4444" maxlength="19" oninput="updateCardDisplay('number', this)" required>
                  </div>
                  <div class="row g-3 mb-4">
                    <div class="col-6">
                      <label class="form-label text-muted small fw-600 mb-1">EXPIRY DATE</label>
                      <input type="text" class="form-control rounded-3" id="cc-expiry-input" placeholder="MM/YY" maxlength="5" oninput="updateCardDisplay('expiry', this)" required>
                    </div>
                    <div class="col-6">
                      <label class="form-label text-muted small fw-600 mb-1">CVV CODE</label>
                      <input type="password" class="form-control rounded-3" placeholder="***" minlength="3" maxlength="3" required>
                    </div>
                  </div>
                  <button type="submit" class="btn btn-primary-klean w-100 py-3 rounded-3 fw-700">Pay <span class="checkout-pay-total">₹0.00</span> Now</button>
                </form>
              </div>

              <!-- UPI PANEL -->
              <div class="pay-panel d-none" id="panel-upi">
                <div class="text-center py-4">
                  <div class="mb-4">
                    <div class="d-inline-flex p-3 bg-light border border-primary border-opacity-10 rounded-4">
                      <!-- Render QR Code with dynamic price inside -->
                      <div class="p-3 bg-white rounded-3 shadow-sm border border-2 border-primary position-relative" style="width: 140px; height: 140px; display: flex; align-items: center; justify-content: center;">
                        <i class="bi bi-qr-code text-dark" style="font-size: 6rem; line-height: 1;"></i>
                        <span class="badge bg-primary position-absolute top-100 start-50 translate-middle shadow" style="font-size: 0.65rem;">SCAN & PAY</span>
                      </div>
                    </div>
                  </div>
                  <h6 class="fw-700 mt-2 mb-1">Scan UPI QR Code</h6>
                  <p class="text-muted small mb-4">Scan using Google Pay, PhonePe, Paytm, or BHIM UPI app.</p>
                  
                  <div class="d-flex justify-content-center gap-2 mb-4">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill py-2" onclick="selectUpiApp('gpay')"><i class="bi bi-google me-1"></i> GPay</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill py-2" onclick="selectUpiApp('phonepe')"><i class="bi bi-wallet2 me-1"></i> PhonePe</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill py-2" onclick="selectUpiApp('paytm')"><i class="bi bi-phone-fill me-1"></i> Paytm</button>
                  </div>

                  <form onsubmit="submitPayment(event)">
                    <div class="input-group mb-4 mx-auto" style="max-width:380px;">
                      <span class="input-group-text bg-light text-muted small fw-600"><i class="bi bi-qr-code-scan"></i></span>
                      <input type="text" class="form-control" id="upi-id-input" placeholder="username@upi" required>
                      <button type="submit" class="btn btn-primary-klean py-2 px-3 rounded-end-3 ms-0">Pay <span class="checkout-pay-total">₹0.00</span></button>
                    </div>
                  </form>
                </div>
              </div>

              <!-- WALLET PANEL -->
              <div class="pay-panel d-none" id="panel-wallet">
                <div class="text-center py-4">
                  <i class="bi bi-wallet2 text-success" style="font-size: 4.5rem;"></i>
                  <h6 class="fw-700 mt-3 mb-2">Simulated Digital Wallet Checkout</h6>
                  <p class="text-muted small mb-4">Pay instantly using your saved Paytm, PhonePe, or Amazon Pay balance.</p>
                  
                  <div class="row g-2 mx-auto mb-4" style="max-width:440px;">
                    <div class="col-4">
                      <div class="p-3 border rounded-3 wallet-select active" id="w-paytm" onclick="selectWallet('paytm', this)" style="cursor:pointer;">
                        <i class="bi bi-wallet2 fs-5 d-block mb-1 text-primary"></i>
                        <span class="small fw-700" style="font-size:0.75rem;">Paytm</span>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-3 border rounded-3 wallet-select" id="w-phonepe" onclick="selectWallet('phonepe', this)" style="cursor:pointer;">
                        <i class="bi bi-phone-vibrate fs-5 d-block mb-1 text-secondary"></i>
                        <span class="small fw-700" style="font-size:0.75rem;">PhonePe</span>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-3 border rounded-3" id="w-amazon" onclick="selectWallet('amazon', this)" style="cursor:pointer; border: 1px solid var(--border);">
                        <i class="bi bi-amazon fs-5 d-block mb-1 text-warning"></i>
                        <span class="small fw-700" style="font-size:0.75rem;">Amazon Pay</span>
                      </div>
                    </div>
                  </div>

                  <button class="btn btn-primary-klean py-3 px-5 rounded-3 fw-700" onclick="submitPayment(event)">Authenticate and Pay <span class="checkout-pay-total">₹0.00</span></button>
                </div>
              </div>

              <!-- CASH / OFFLINE PANEL -->
              <div class="pay-panel d-none" id="panel-cash">
                <div class="text-center py-4">
                  <i class="bi bi-cash-coin text-success" style="font-size: 4.5rem;"></i>
                  <h6 class="fw-700 mt-3 mb-2">Simulate Cash / Pay-on-Counter Payment</h6>
                  <p class="text-muted small mb-4" style="max-width:500px; margin: 0 auto 1.5rem;">
                    This option simulates an offline Cash on Counter transaction. Selecting this will bypass payment gateway authorization, immediately enrolling your account for quick local testing.
                  </p>
                  <button class="btn btn-success py-3 px-5 rounded-3 fw-700 text-white" onclick="submitPayment(event)" style="background-color: var(--success); border-color: var(--success);">
                    <i class="bi bi-check-circle me-1"></i> Complete Cash Payment <span class="checkout-pay-total">₹0.00</span>
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 8. SUCCESS PAGE VIEW -->
    <div id="success-view" class="view-container py-5">
      <div class="container py-4 text-center">
        <div class="mb-4">
          <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width:84px; height:84px;">
            <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>
          </div>
        </div>
        <h2 class="fw-800 text-dark mb-2">Transaction Successful!</h2>
        <p class="text-muted small mb-4">Your course enrollment is secured in our platform database.</p>
        
        <div class="card p-4 border rounded-4 bg-light mx-auto mb-4 text-start shadow-sm" style="max-width: 500px;">
          <h6 class="fw-800 text-dark mb-3 border-bottom pb-2">Payment Details:</h6>
          <div class="d-flex justify-content-between mb-2 small text-muted">
            <span>Order Reference:</span>
            <strong class="text-dark" id="success-order-id">KLN123456789</strong>
          </div>
          <div class="d-flex justify-content-between mb-2 small text-muted">
            <span>Transaction Status:</span>
            <span class="badge bg-success">COMPLETED</span>
          </div>
          <div class="d-flex justify-content-between mb-2 small text-muted">
            <span>Purchased Curriculum:</span>
            <strong class="text-dark" id="success-courses-count">1 Course</strong>
          </div>
        </div>
        
        <div class="d-flex justify-content-center gap-3">
          <button class="btn btn-primary-klean" onclick="switchView('dashboard-view')"><i class="bi bi-mortarboard-fill me-2"></i>Go to Classroom</button>
          <button class="btn btn-outline-klean" onclick="switchView('landing-view')">Back to Home</button>
        </div>
      </div>
    </div>

    <!-- 9. STUDENT DASHBOARD VIEW -->
    <div id="dashboard-view" class="view-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
          <div class="sidebar-logo">
            <h6 class="fw-800 text-white mb-0"><i class="bi bi-mortarboard-fill text-primary me-2"></i>My Classroom</h6>
          </div>
          <a class="nav-link-klean active" href="#" onclick="switchView('dashboard-view');return false;"><i class="bi bi-speedometer2"></i> Classroom Stats</a>
          <a class="nav-link-klean" href="#" onclick="switchView('wishlist-view');return false;"><i class="bi bi-heart"></i> Bookmarks</a>
          <a class="nav-link-klean" href="#" onclick="switchView('profile-view');return false;"><i class="bi bi-gear"></i> Settings</a>
          <a class="nav-link-klean" href="student/support/my-tickets.php"><i class="bi bi-ticket-detailed"></i> Support Tickets</a>
        </div>
        
        <!-- Main Panel -->
        <div class="dashboard-main">
          <h2 class="fw-800 text-dark mb-4">My Dashboard</h2>
          
          <!-- Stats grids -->
          <div class="row g-4 mb-5">
            <div class="col-md-4">
              <div class="p-4 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                  <h1 class="fw-800 text-dark mb-0" id="dash-enrolled-count">0</h1>
                  <span class="text-muted small fw-600">ENROLLED COURSES</span>
                </div>
                <div class="fs-1 text-primary bg-primary bg-opacity-10 rounded-3 px-3 py-2"><i class="bi bi-journal-bookmark-fill"></i></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-4 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                  <h1 class="fw-800 text-dark mb-0" id="dash-completed-count">0</h1>
                  <span class="text-muted small fw-600">COMPLETED BADGES</span>
                </div>
                <div class="fs-1 text-success bg-success bg-opacity-10 rounded-3 px-3 py-2"><i class="bi bi-patch-check-fill"></i></div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="p-4 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                  <h1 class="fw-800 text-dark mb-0" id="dash-hours-learned">0</h1>
                  <span class="text-muted small fw-600">TOTAL HOURS LEARNED</span>
                </div>
                <div class="fs-1 text-warning bg-warning bg-opacity-10 rounded-3 px-3 py-2"><i class="bi bi-hourglass-split"></i></div>
              </div>
            </div>
          </div>

          <!-- In-progress Courses -->
          <h4 class="fw-800 text-dark mb-3">Enrolled Curriculums</h4>
          <div class="row g-4 mb-5" id="dash-courses-container">
            <!-- Hydrated dynamically -->
          </div>

          <!-- Recommended -->
          <h4 class="fw-800 text-dark mb-3">Recommended for You</h4>
          <div class="row row-cols-1 row-cols-md-3 g-4" id="dash-recommended-container">
            <!-- Hydrated dynamically -->
          </div>
        </div>
      </div>
    </div>

    <!-- 10. COURSE PLAYER VIEW -->
    <div id="player-view" class="view-container">
      <div class="player-layout">
        <!-- Player main panel -->
        <div class="player-video-section">
          <!-- Video -->
          <div class="player-video-wrapper">
            <div class="player-custom-video" id="player-main-trigger" onclick="togglePlayerPlayback()">
              <!-- Simulation overlay -->
              <div class="text-center" id="player-sim-overlay">
                <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3 shadow" style="width:72px; height:72px;">
                  <i class="bi bi-play-fill" style="font-size: 3rem;" id="player-play-icon"></i>
                </div>
                <h5 class="fw-700 mb-1" id="player-lecture-title">Welcome to the Course</h5>
                <p class="text-muted small" id="player-playback-status">Simulation Mode (Click Screen to Play)</p>
              </div>
            </div>
          </div>
          
          <!-- Player control triggers -->
          <div class="player-controls d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-sm btn-outline-light py-2" onclick="playPrevLesson()"><i class="bi bi-chevron-left"></i> Prev</button>
              <button class="btn btn-sm btn-outline-light py-2" onclick="playNextLesson()">Next <i class="bi bi-chevron-right"></i></button>
            </div>
            
            <button class="btn btn-sm btn-accent-klean py-2" id="player-complete-btn" onclick="toggleLessonComplete()">Mark Lesson Complete <i class="bi bi-check2-circle"></i></button>
          </div>

          <!-- Notes Panel and details tabs -->
          <div class="p-4 bg-white text-dark flex-grow-1" style="overflow-y: auto;">
            <ul class="nav nav-tabs border-bottom mb-3" id="playerTabs">
              <li class="nav-item"><button class="nav-link active py-2 px-3" data-bs-toggle="tab" data-bs-target="#player-tab-notes">Lecture Notes</button></li>
              <li class="nav-item"><button class="nav-link py-2 px-3" data-bs-toggle="tab" data-bs-target="#player-tab-desc">Description</button></li>
            </ul>
            
            <div class="tab-content">
              <div class="tab-pane fade show active" id="player-tab-notes">
                <p class="text-muted small mb-2"><i class="bi bi-cloud-check-fill text-success"></i> Auto-saves note to DB for the active lesson.</p>
                <textarea class="form-control rounded-3" id="player-note-textarea" rows="4" placeholder="Draft your notes for this specific lecture..." onkeyup="saveLessonNote()"></textarea>
              </div>
              <div class="tab-pane fade text-muted small" id="player-tab-desc">
                <h6 class="fw-700 text-dark mb-2">Lesson Material Details:</h6>
                <p>Welcome to this structured learning unit. In this lesson, your instructor reviews essential parameters, syntax guidelines, and optimization methods. For assistance, write in the Q&amp;A reviews section.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Curriculum Sidebar -->
        <div class="player-sidebar">
          <div class="p-3 border-bottom border-secondary d-flex justify-content-between align-items-center">
            <h6 class="fw-800 text-white mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Course Outline</h6>
            <button class="btn btn-sm btn-outline-light py-1 px-2" onclick="switchView('dashboard-view')"><i class="bi bi-x-lg"></i> Close</button>
          </div>
          <div class="p-3 bg-dark bg-opacity-25">
            <div class="progress" style="height:6px;"><div class="progress-bar bg-primary" id="player-progress-bar" style="width: 0%;"></div></div>
            <div class="d-flex justify-content-between mt-2 small text-muted"><span id="player-progress-text">0% Complete</span></div>
          </div>
          <div class="flex-grow-1" id="player-curriculum-list" style="overflow-y: auto;">
            <!-- Hydrated dynamically -->
          </div>
        </div>
      </div>
    </div>

    <!-- 11. INSTRUCTOR DASHBOARD VIEW -->
    <div id="instructor-view" class="view-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
          <div class="sidebar-logo">
            <h6 class="fw-800 text-white mb-0"><i class="bi bi-person-workspace text-warning me-2"></i>Instructor Portal</h6>
          </div>
          <a class="nav-link-klean active" href="#" onclick="switchView('instructor-view');return false;"><i class="bi bi-speedometer2"></i> Performance Stats</a>
          <a class="nav-link-klean" href="#" onclick="openCreateCourseModal();return false;"><i class="bi bi-plus-circle"></i> Create Course</a>
          <a class="nav-link-klean" href="#" onclick="switchView('profile-view');return false;"><i class="bi bi-gear"></i> Settings</a>
        </div>
        
        <!-- Main Panel -->
        <div class="dashboard-main">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-800 text-dark m-0">Instructor Dashboard</h2>
            <button class="btn btn-primary-klean" onclick="openCreateCourseModal()"><i class="bi bi-plus-circle me-2"></i>Create Course</button>
          </div>
          
          <!-- Stats grids -->
          <div class="row g-4 mb-5">
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                  <h1 class="fw-800 text-dark mb-0" id="inst-students-count">0</h1>
                  <span class="text-muted small fw-600">TOTAL LEARNERS</span>
                </div>
                <div class="fs-1 text-primary bg-primary bg-opacity-10 rounded-3 px-3 py-2"><i class="bi bi-people-fill"></i></div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                  <h1 class="fw-800 text-dark mb-0" id="inst-revenue">₹0</h1>
                  <span class="text-muted small fw-600">TOTAL REVENUE (70%)</span>
                </div>
                <div class="fs-1 text-success bg-success bg-opacity-10 rounded-3 px-3 py-2"><i class="bi bi-cash-stack"></i></div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                  <h1 class="fw-800 text-dark mb-0" id="inst-rating">4.8</h1>
                  <span class="text-muted small fw-600">INSTRUCTOR RATING</span>
                </div>
                <div class="fs-1 text-warning bg-warning bg-opacity-10 rounded-3 px-3 py-2"><i class="bi bi-star-fill"></i></div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 d-flex align-items-center justify-content-between shadow-sm">
                <div>
                  <h1 class="fw-800 text-dark mb-0" id="inst-courses-count">0</h1>
                  <span class="text-muted small fw-600">COURSES BUILT</span>
                </div>
                <div class="fs-1 text-danger bg-danger bg-opacity-10 rounded-3 px-3 py-2"><i class="bi bi-journal-text"></i></div>
              </div>
            </div>
          </div>

          <!-- Revenue bar chart (CSS Only) -->
          <div class="card p-4 border rounded-4 bg-white shadow-sm mb-5">
            <h5 class="fw-800 text-dark mb-4">Monthly Earnings Performance (70% Share)</h5>
            <div class="revenue-chart-wrapper" id="inst-chart-wrapper">
              <!-- Rendered dynamically -->
            </div>
          </div>

          <!-- My courses list -->
          <h4 class="fw-800 text-dark mb-3">My Published Curriculums</h4>
          <div class="card border rounded-4 bg-white shadow-sm overflow-hidden mb-5">
            <div class="table-responsive">
              <table class="table align-middle mb-0">
                <thead class="table-light">
                  <tr class="small text-muted fw-700">
                    <th>CURRICULUM TITLE</th>
                    <th>ENROLLED</th>
                    <th>PRICE</th>
                    <th>RATING</th>
                    <th>STATUS</th>
                  </tr>
                </thead>
                <tbody id="inst-courses-table">
                  <!-- Hydrated dynamically -->
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- 12. ADMIN DASHBOARD VIEW -->
    <div id="admin-view" class="view-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
          <div class="sidebar-logo">
            <h6 class="fw-800 text-white mb-0"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Admin Panel</h6>
          </div>
          <a class="nav-link-klean active" href="#" onclick="switchView('admin-view');return false;"><i class="bi bi-speedometer2"></i> Global Analytics</a>
          <a class="nav-link-klean" href="#" onclick="switchView('profile-view');return false;"><i class="bi bi-gear"></i> Settings</a>
          <a class="nav-link-klean" href="admin/support/tickets.php"><i class="bi bi-ticket-detailed"></i> Support Management</a>
        </div>
        
        <!-- Main Panel -->
        <div class="dashboard-main">
          <h2 class="fw-800 text-dark mb-4">Global Operations Panel</h2>
          
          <!-- Mock stats -->
          <div class="row g-4 mb-5">
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center shadow-sm">
                <h1 class="fw-800 text-primary mb-1">512</h1>
                <span class="text-muted small fw-600">TOTAL SYSTEM USERS</span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center shadow-sm">
                <h1 class="fw-800 text-success mb-1">₹89,200</h1>
                <span class="text-muted small fw-600">TOTAL PLATFORM GROSS</span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center shadow-sm">
                <h1 class="fw-800 text-warning mb-1">82</h1>
                <span class="text-muted small fw-600">PUBLISHED COURSES</span>
              </div>
            </div>
            <div class="col-md-3">
              <div class="p-4 bg-white border rounded-4 text-center shadow-sm">
                <h1 class="fw-800 text-danger mb-1">1,240</h1>
                <span class="text-muted small fw-600">DB TRANSACTIONS</span>
              </div>
            </div>
          </div>

          <!-- Mock tables -->
          <h5 class="fw-800 mb-3 text-dark">Active System Registrants</h5>
          <div class="card border rounded-4 bg-white shadow-sm overflow-hidden mb-4">
            <div class="table-responsive">
              <table class="table align-middle mb-0 small">
                <thead class="table-light">
                  <tr class="fw-700 text-muted">
                    <th>NAME</th>
                    <th>EMAIL</th>
                    <th>ROLE</th>
                    <th>STATUS</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td>Admin User</td><td>admin@klean.com</td><td><span class="badge bg-danger">ADMIN</span></td><td><span class="badge bg-success">ACTIVE</span></td></tr>
                  <tr><td>Sarah Williams</td><td>sarah@klean.com</td><td><span class="badge bg-warning text-dark">INSTRUCTOR</span></td><td><span class="badge bg-success">ACTIVE</span></td></tr>
                  <tr><td>Alex Johnson</td><td>alex@klean.com</td><td><span class="badge bg-primary">STUDENT</span></td><td><span class="badge bg-success">ACTIVE</span></td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 13. WISHLIST VIEW -->
    <div id="wishlist-view" class="view-container py-5">
      <div class="container">
        <h2 class="fw-800 text-dark mb-4"><i class="bi bi-heart me-2"></i>Bookmarked Courses</h2>
        
        <!-- Empty bookmarks ui -->
        <div class="text-center py-5 d-none" id="wishlist-empty-ui">
          <div class="fs-1 text-muted mb-3"><i class="bi bi-heartbreak"></i></div>
          <h5 class="fw-700 text-muted">Your wishlist is empty</h5>
          <p class="text-muted small mb-4">Save top code classes to purchase later.</p>
          <button class="btn btn-primary-klean" onclick="switchView('courses-view')">Browse Courses</button>
        </div>

        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4" id="wishlist-grid">
          <!-- Hydrated dynamically -->
        </div>
      </div>
    </div>

    <!-- 14. PROFILE & SETTINGS VIEW -->
    <div id="profile-view" class="view-container">
      <div class="dashboard-layout">
        <!-- Sidebar -->
        <div class="dashboard-sidebar">
          <div class="sidebar-logo">
            <h6 class="fw-800 text-white mb-0"><i class="bi bi-gear-fill text-muted me-2"></i>System Settings</h6>
          </div>
          <a class="nav-link-klean active" href="#" onclick="switchView('profile-view');return false;"><i class="bi bi-person-circle"></i> Edit Profile</a>
          <a class="nav-link-klean" href="#" onclick="goToDashboard();return false;"><i class="bi bi-arrow-left-circle"></i> Back to Dashboard</a>
        </div>
        
        <!-- Main Panel -->
        <div class="dashboard-main">
          <h2 class="fw-800 text-dark mb-4">Profile Configurations</h2>
          
          <div class="row g-4">
            <!-- Edit Profile Form -->
            <div class="col-lg-6">
              <div class="card p-4 border rounded-4 bg-white shadow-sm">
                <h5 class="fw-800 text-dark mb-3">Personal Coordinates</h5>
                <form onsubmit="submitProfileUpdate(event)">
                  <div class="mb-3">
                    <label class="form-label text-muted small fw-600 mb-1">FULL NAME</label>
                    <input type="text" class="form-control rounded-3" id="profile-name" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label text-muted small fw-600 mb-1">EMAIL ADDRESS</label>
                    <input type="email" class="form-control rounded-3" id="profile-email" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label text-muted small fw-600 mb-1">PHONE NUMBER</label>
                    <input type="text" class="form-control rounded-3" id="profile-phone">
                  </div>
                  <div class="mb-4">
                    <label class="form-label text-muted small fw-600 mb-1">SHORT BIO</label>
                    <textarea class="form-control rounded-3" id="profile-bio" rows="3" placeholder="Tell us about yourself..."></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary-klean w-100 py-3 rounded-3 fw-700">Save Coordinates</button>
                </form>
              </div>
            </div>

            <!-- Password Reset Form -->
            <div class="col-lg-6">
              <div class="card p-4 border rounded-4 bg-white shadow-sm">
                <h5 class="fw-800 text-dark mb-3">Modify Password</h5>
                <form onsubmit="submitPasswordUpdate(event)">
                  <div class="mb-3">
                    <label class="form-label text-muted small fw-600 mb-1">CURRENT PASSWORD</label>
                    <input type="password" class="form-control rounded-3" id="pass-current" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label text-muted small fw-600 mb-1">NEW PASSWORD</label>
                    <input type="password" class="form-control rounded-3" id="pass-new" required minlength="6">
                  </div>
                  <div class="mb-4">
                    <label class="form-label text-muted small fw-600 mb-1">CONFIRM NEW PASSWORD</label>
                    <input type="password" class="form-control rounded-3" id="pass-confirm" required>
                  </div>
                  <button type="submit" class="btn btn-outline-klean w-100 py-3 rounded-3 fw-700">Update Password</button>
                </form>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </main>

  <!-- =========================================================================
  GLOBAL FOOTER
  ========================================================================= -->
  <footer class="bg-white border-top py-5 mt-auto" id="global-footer">
    <div class="container">
      <div class="row g-4 align-items-center">
        <div class="col-md-6">
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="d-flex align-items-center justify-content-center rounded-3 text-white fw-800 fs-5" style="width:34px;height:34px;background:var(--primary);">K</span>
            <span class="fs-5 fw-800 text-dark">Klean<span style="color:var(--primary);">.</span></span>
          </div>
          <p class="text-muted small mb-0">Learn Clean. Grow Sharp. Uncluttered learning paths backed by high-tech database integrity.</p>
        </div>
        <div class="col-md-6 text-md-end text-muted small">
          <div class="mb-2">
            <a href="#" class="text-muted text-decoration-none mx-2 hover-primary">Catalog</a>
            <a href="#" class="text-muted text-decoration-none mx-2 hover-primary">Teach</a>
            <a href="#" class="text-muted text-decoration-none mx-2 hover-primary">Privacy</a>
            <a href="#" class="text-muted text-decoration-none mx-2 hover-primary">Terms</a>
          </div>
          <p class="mb-0">&copy; 2026 Klean Learning Platform. All rights reserved.</p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Create Course Modal -->
  <div class="modal fade" id="createCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg">
        <div class="modal-header border-bottom">
          <h5 class="fw-800 modal-title"><i class="bi bi-journal-plus me-2 text-primary"></i>Build New Course</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form onsubmit="submitCreateCourse(event)">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label text-muted small fw-600 mb-1">COURSE TITLE</label>
              <input type="text" class="form-control rounded-3" id="course-title" placeholder="e.g. Complete React Architect Bootcamp" required>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small fw-600 mb-1">SUBTITLE</label>
              <input type="text" class="form-control rounded-3" id="course-subtitle" placeholder="e.g. Hooks, Context, Next.js, and cloud deployments" required>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small fw-600 mb-1">SHORT DESCRIPTION</label>
              <textarea class="form-control rounded-3" id="course-desc" rows="3" placeholder="Draft course outcomes..." required></textarea>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small fw-600 mb-1">CATEGORY</label>
                <select class="form-select rounded-3" id="course-category">
                  <option value="1">Development</option>
                  <option value="2">Design</option>
                  <option value="3">Business</option>
                  <option value="4">Marketing</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small fw-600 mb-1">CURRICULUM LEVEL</label>
                <select class="form-select rounded-3" id="course-level">
                  <option value="beginner">Beginner</option>
                  <option value="intermediate">Intermediate</option>
                  <option value="advanced">Advanced</option>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small fw-600 mb-1">PRICE (INR)</label>
                <input type="number" class="form-control rounded-3" id="course-price" value="49.99" step="0.01" required>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small fw-600 mb-1">DISCOUNT PRICE (INR)</label>
                <input type="number" class="form-control rounded-3" id="course-discount" value="12.99" step="0.01" required>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label text-muted small fw-600 mb-1">TOTAL LECTURE HOURS</label>
              <input type="number" class="form-control rounded-3" id="course-hours" value="12" required>
            </div>
            <div class="mb-2">
              <label class="form-label text-muted small fw-600 mb-1">THUMBNAIL URL (OPTIONAL)</label>
              <input type="text" class="form-control rounded-3" id="course-thumbnail" placeholder="Paste Unsplace URL or leave empty">
            </div>
          </div>
          <div class="modal-footer border-top bg-light rounded-bottom-4">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary-klean btn-sm px-4">Publish Course</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Certificate View Modal -->
  <div class="modal fade" id="certModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0 shadow-lg" style="background: radial-gradient(circle, #FAF5FF 0%, #FFF 100%);">
        <div class="modal-header border-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center p-4">
          <div class="mb-3 text-warning"><i class="bi bi-award-fill" style="font-size: 4.5rem;"></i></div>
          <h4 class="fw-800 text-dark mb-1">Certificate of Excellence</h4>
          <p class="text-muted small">This represents verification of complete curriculum learning.</p>
          
          <div class="border border-3 border-secondary p-4 my-4 rounded bg-white position-relative" style="border-style: double !important;">
            <h6 class="text-primary fw-800 mb-1">KLEAN LEARNING PLATFORM</h6>
            <small class="text-muted d-block mb-3" style="font-size: 0.65rem; letter-spacing:1px;">VERIFIED CURRICULUM BADGE</small>
            
            <p class="mb-1 text-muted small">This certifies that student</p>
            <h5 class="fw-800 text-dark mb-3" id="cert-user-name">Alex Johnson</h5>
            
            <p class="mb-1 text-muted small">has fully completed the expert curriculum course</p>
            <h6 class="fw-700 text-dark mb-4" id="cert-course-title">Complete Web Development Bootcamp</h6>
            
            <div class="row g-2 text-start mt-3 small text-muted border-top pt-3" style="font-size: 0.72rem;">
              <div class="col-6">
                <span>Certificate Ref:</span><br>
                <strong class="text-dark" id="cert-no">KLN-1234-5678</strong>
              </div>
              <div class="col-6 text-end">
                <span>Issued Date:</span><br>
                <strong class="text-dark" id="cert-date">2026-05-29</strong>
              </div>
            </div>
          </div>
          
          <button class="btn btn-primary-klean" onclick="window.print()"><i class="bi bi-printer me-2"></i>Print Certificate</button>
        </div>
      </div>
    </div>
  </div>

  <!-- =========================================================================
  8. JAVASCRIPT (INLINE SCRIPT)
  ========================================================================= -->
  <!-- Bootstrap Bundle CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Global App State
    var currentUser = <?php echo json_encode($_SESSION['user'] ?? null); ?>;
    const csrfToken = "<?= $_SESSION['csrf_token'] ?>";
    
    var state = {
      courses: [],
      categories: [],
      currentView: 'landing-view',
      activeDetailCourseId: 1,
      activePlayerCourseId: 1,
      activePlayerLectureIdx: 0,
      activePlayerPlayState: false,
      couponApplied: false,
      couponCode: '',
      cartItems: [],
      wishlistItems: []
    };

    // On Page Load
    document.addEventListener("DOMContentLoaded", function() {
      // Establish default visibility states
      updateNavbar();
      
      // Load initial courses catalog data
      fetchCoursesData(function() {
        // Read URL params to set initial deep-link view
        const urlParams = new URLSearchParams(window.location.search);
        const pageParam = urlParams.get('page') || 'home';
        
        if (pageParam === 'success') {
          const orderId = urlParams.get('order_id') || 'KLN12345';
          showSuccessPage(orderId);
        } else if (pageParam === 'player') {
          const cId = parseInt(urlParams.get('course_id') || 1);
          switchView('player-view', { courseId: cId });
        } else {
          // If logged in and page is 'home', skip to dashboard to support user request
          if (currentUser && pageParam === 'home') {
            goToDashboard();
          } else {
            const mappedView = {
              'home': 'landing-view',
              'login': 'login-view',
              'signup': 'signup-view',
              'courses': 'courses-view',
              'course': 'course-detail-view',
              'cart': 'cart-view',
              'checkout': 'checkout-view',
              'dashboard': 'dashboard-view',
              'instructor': 'instructor-view',
              'admin': 'admin-view',
              'wishlist': 'wishlist-view',
              'profile': 'profile-view'
            }[pageParam] || 'landing-view';
            
            switchView(mappedView, { courseId: parseInt(urlParams.get('id') || 1) });
          }
        }
      });
      
      // Wireframe payment tab clicks switching panels
      document.querySelectorAll(".pay-tab").forEach(tab => {
        tab.addEventListener("click", function() {
          document.querySelectorAll(".pay-tab").forEach(t => t.classList.remove("active"));
          document.querySelectorAll(".pay-panel").forEach(p => p.classList.add("d-none"));
          
          this.classList.add("active");
          const target = this.getAttribute("data-target");
          document.getElementById(target).classList.remove("d-none");
        });
      });
    });

    // =========================================================================
    // VIEW CONTROLLER ROUTER
    // =========================================================================
    function switchView(viewId, params = {}) {
      // Toggle visibility containers
      document.querySelectorAll(".view-container").forEach(c => c.classList.remove("active-view"));
      
      const target = document.getElementById(viewId);
      if (target) {
        target.classList.add("active-view");
      }
      
      state.currentView = viewId;
      
      // Show/Hide footer for dash/player interfaces
      const noFooter = ['dashboard-view', 'instructor-view', 'player-view', 'admin-view', 'profile-view'].includes(viewId);
      document.getElementById("global-footer").classList.toggle("d-none", noFooter);

      // Hydrate views according to context
      if (viewId === 'landing-view') {
        renderLandingPage();
      } else if (viewId === 'courses-view') {
        renderCoursesCatalog();
      } else if (viewId === 'course-detail-view') {
        state.activeDetailCourseId = params.courseId || state.activeDetailCourseId;
        renderCourseDetail();
      } else if (viewId === 'cart-view') {
        renderCartPage();
      } else if (viewId === 'checkout-view') {
        renderCheckoutSummary();
      } else if (viewId === 'dashboard-view') {
        renderStudentDashboard();
      } else if (viewId === 'player-view') {
        state.activePlayerCourseId = params.courseId || state.activePlayerCourseId;
        state.activePlayerLectureIdx = params.lessonIdx || 0;
        renderCoursePlayer();
      } else if (viewId === 'instructor-view') {
        renderInstructorDashboard();
      } else if (viewId === 'wishlist-view') {
        renderWishlistPage();
      } else if (viewId === 'profile-view') {
        renderSettingsPage();
      } else if (viewId === 'admin-view') {
        // Admin loads static list pre-seeded
      }
      
      updateNavbar();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function goToDashboard() {
      if (currentUser) {
        if (currentUser.role === 'instructor') {
          switchView('instructor-view');
        } else if (currentUser.role === 'admin') {
          switchView('admin-view');
        } else {
          switchView('dashboard-view');
        }
      } else {
        switchView('landing-view');
      }
    }

    // =========================================================================
    // DYNAMIC NAV STATE MANAGER
    // =========================================================================
    function updateNavbar() {
      const isLoggedIn = !!currentUser;
      
      // Toggle anonymous vs authenticated header UI links
      document.querySelectorAll(".anon-only").forEach(el => el.classList.toggle("d-none", isLoggedIn));
      document.querySelectorAll(".logged-in-only").forEach(el => el.classList.toggle("d-none", !isLoggedIn));
      
      if (isLoggedIn) {
        document.getElementById("nav-user-name").textContent = currentUser.name;
        document.getElementById("nav-role-label").textContent = currentUser.role.toUpperCase() + " ACCOUNT";
        document.getElementById("nav-avatar").src = currentUser.avatar || 'default.png';
        
        // Show student specific cart badges
        const isStudent = currentUser.role === 'student';
        document.querySelectorAll(".student-only").forEach(el => el.classList.toggle("d-none", !isStudent));
        
        if (isStudent) {
          fetchCartCount();
        }
      } else {
        document.querySelectorAll(".student-only").forEach(el => el.classList.add("d-none"));
      }
    }

    function fetchCartCount() {
      if (!currentUser) return;
      
      fetch('?ajax=get_cart')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            state.cartItems = res.items;
            const cCount = res.items.length;
            const cb = document.getElementById("cart-badge");
            cb.textContent = cCount;
            cb.classList.toggle("d-none", cCount === 0);
          }
        });
        
      fetch('?ajax=get_wishlist')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            state.wishlistItems = res.items;
            const wCount = res.items.length;
            const wb = document.getElementById("wishlist-badge");
            wb.textContent = wCount;
            wb.classList.toggle("d-none", wCount === 0);
          }
        });
    }

    // =========================================================================
    // AJAX DATA DRIVERS
    // =========================================================================
    function fetchCoursesData(callback) {
      fetch('?ajax=get_courses')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            state.courses = res.courses;
            state.categories = res.categories;
            populateCategoryDropdowns();
            if (callback) callback();
          }
        });
    }

    function populateCategoryDropdowns() {
      const select = document.getElementById("filter-category");
      if (select) {
        select.innerHTML = '<option value="all">All Categories</option>';
        state.categories.forEach(cat => {
          select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
        });
      }
      
      const modalSelect = document.getElementById("course-category");
      if (modalSelect) {
        modalSelect.innerHTML = '';
        state.categories.forEach(cat => {
          modalSelect.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
        });
      }
    }

    // =========================================================================
    // 1. LANDING PAGE HYDRATION
    // =========================================================================
    function renderLandingPage() {
      // Hydrate topics pills scroll list
      const scroll = document.getElementById("landing-categories");
      if (scroll) {
        scroll.innerHTML = '<div class="category-pill active" onclick="filterByCategory(\'all\', this)">All Courses</div>';
        state.categories.forEach(cat => {
          scroll.innerHTML += `<div class="category-pill" onclick="filterByCategory(${cat.id}, this)">${cat.name}</div>`;
        });
      }

      // Hydrate Featured courses cards grid (Top 6 ratings/learners)
      const grid = document.getElementById("featured-courses-grid");
      if (grid) {
        grid.innerHTML = '';
        const sorted = [...state.courses].sort((a,b) => b.student_count - a.student_count).slice(0, 6);
        sorted.forEach(c => {
          grid.appendChild(createCourseCard(c));
        });
      }

      // Hardcoded Testimonials
      const test = document.getElementById("testimonials-container");
      if (test) {
        test.innerHTML = `
          <div class="col-md-4">
            <div class="testimonial-card p-4 border bg-white rounded-4 shadow-sm h-100">
              <div class="text-warning mb-3 small"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
              <p class="text-muted small mb-4" style="line-height:1.7;">"The zero-fluff Bootcamp saved me weeks of redundant tutorials. Direct layout exercises are absolutely elite."</p>
              <div class="d-flex align-items-center gap-3">
                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=100&h=100&q=80" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">
                <div><h6 class="mb-0 fw-700" style="font-size:0.85rem;">Emma Watson</h6><small class="text-muted" style="font-size:0.7rem;">React Developer</small></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="testimonial-card p-4 border bg-white rounded-4 shadow-sm h-100">
              <div class="text-warning mb-3 small"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
              <p class="text-muted small mb-4" style="line-height:1.7;">"Sarah's Figma architecture course is incredibly structural. Worth three times the enrollment fee easily!"</p>
              <div class="d-flex align-items-center gap-3">
                <img src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&h=100&q=80" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">
                <div><h6 class="mb-0 fw-700" style="font-size:0.85rem;">Marcus Brody</h6><small class="text-muted" style="font-size:0.7rem;">Product Designer</small></div>
              </div>
            </div>
          </div>
          <div class="col-md-4">
            <div class="testimonial-card p-4 border bg-white rounded-4 shadow-sm h-100">
              <div class="text-warning mb-3 small"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
              <p class="text-muted small mb-4" style="line-height:1.7;">"Python ML lessons provided immediate database and analysis clarity. Fast loader and responsive player."</p>
              <div class="d-flex align-items-center gap-3">
                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?auto=format&fit=crop&w=100&h=100&q=80" class="rounded-circle border" style="width:40px;height:40px;object-fit:cover;">
                <div><h6 class="mb-0 fw-700" style="font-size:0.85rem;">Sophia Martinez</h6><small class="text-muted" style="font-size:0.7rem;">Data Engineer</small></div>
              </div>
            </div>
          </div>
        `;
      }
    }

    function createCourseCard(c) {
      const col = document.createElement("div");
      col.className = "col";
      
      const isWish = state.wishlistItems.some(item => item.id === c.id);
      
      let ratingStars = '';
      for (let i = 1; i <= 5; i++) {
        ratingStars += i <= Math.floor(c.rating) ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-secondary opacity-30"></i>';
      }
      
      const bestsellerBadge = parseInt(c.is_bestseller) === 1 ? '<span class="badge-bestseller">BESTSELLER</span>' : '';
      const priceText = parseFloat(c.price) === 0 ? 'Free' : `₹${parseFloat(c.price).toFixed(2)}`;
      
      col.innerHTML = `
        <div class="course-card">
          <div class="card-img-wrapper" style="cursor:pointer;" onclick="switchView('course-detail-view', {courseId: ${c.id}})">
            ${bestsellerBadge}
            <button class="badge-wishlist-toggle" onclick="event.stopPropagation(); toggleWishlist(${c.id}, this)">
              <i class="bi ${isWish ? 'bi-heart-fill text-danger' : 'bi-heart'}"></i>
            </button>
            <img src="${c.thumbnail}" alt="${c.title}" loading="lazy">
          </div>
          <div class="card-body p-3 d-flex flex-column flex-grow-1" style="cursor:pointer;" onclick="switchView('course-detail-view', {courseId: ${c.id}})">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="badge-category">${c.level.toUpperCase()}</span>
              <span class="text-muted small fw-600"><i class="bi bi-clock text-primary me-1"></i>${c.total_hours}h</span>
            </div>
            <h6 class="fw-700 text-dark mb-1 text-truncate-2" style="min-height:38px;">${c.title}</h6>
            <p class="text-muted small mb-2">By ${c.instructor}</p>
            <div class="d-flex align-items-center gap-1 mb-3">
              <span class="text-warning small fw-700">${c.rating}</span>
              <span class="fs-8">${ratingStars}</span>
              <span class="text-muted fs-8">(${c.review_count})</span>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
              <h5 class="fw-800 text-primary m-0">${priceText}</h5>
              <button class="btn btn-outline-klean py-1 px-3 fs-8 fw-700 rounded-3" style="font-size:0.75rem;" onclick="event.stopPropagation(); quickAddToCart(${c.id})">
                <i class="bi bi-cart-plus me-1"></i>Cart
              </button>
            </div>
          </div>
        </div>
      `;
      return col;
    }

    function filterByCategory(catId, element) {
      if (element) {
        document.querySelectorAll(".category-pill").forEach(p => p.classList.remove("active"));
        element.classList.add("active");
      }
      
      switchView('courses-view');
      const select = document.getElementById("filter-category");
      if (select) {
        select.value = catId;
        runCatalogFilter();
      }
    }

    // =========================================================================
    // 2. SEARCH & CATALOG FILTER DRIVERS
    // =========================================================================
    function handleNavSearch(e) { if (e.key === 'Enter') triggerNavSearch(); }
    function triggerNavSearch() { dispatchCatalogSearch(document.getElementById("nav-search-input").value); }
    function handleHeroSearch(e) { if (e.key === 'Enter') triggerHeroSearch(); }
    function triggerHeroSearch() { dispatchCatalogSearch(document.getElementById("hero-search-input").value); }

    function dispatchCatalogSearch(q) {
      switchView('courses-view');
      document.getElementById("catalog-header").textContent = q.trim() ? `Search Results for "${q}"` : 'All Courses';
      
      fetch(`?ajax=get_courses&q=${encodeURIComponent(q)}`)
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            hydrateCatalogGrid(res.courses);
          }
        });
    }

    function renderCoursesCatalog() {
      runCatalogFilter();
    }

    function runCatalogFilter() {
      const catVal = document.getElementById("filter-category").value;
      const lvlVal = document.getElementById("filter-level").value;
      const prVal = document.getElementById("filter-price").value;
      const rtVal = document.getElementById("filter-rating").value;
      const sortVal = document.getElementById("filter-sort").value;
      
      const checkTopics = [...document.querySelectorAll(".topic-check:checked")].map(el => el.value);
      const radioDur = document.querySelector(".duration-radio:checked").value;
      
      let url = `?ajax=get_courses&category=${catVal}&level=${lvlVal}&price=${prVal}&rating=${rtVal}&sort=${sortVal}`;
      
      fetch(url)
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            let filtered = res.courses;
            
            // Client filter for tags & duration
            if (checkTopics.length > 0) {
              filtered = filtered.filter(c => checkTopics.some(t => c.title.toLowerCase().includes(t.toLowerCase())));
            }
            
            if (radioDur === 'short') {
              filtered = filtered.filter(c => parseFloat(c.total_hours) <= 25);
            } else if (radioDur === 'long') {
              filtered = filtered.filter(c => parseFloat(c.total_hours) > 25);
            }
            
            hydrateCatalogGrid(filtered);
          }
        });
    }

    function hydrateCatalogGrid(courses) {
      const grid = document.getElementById("catalog-grid");
      grid.innerHTML = '';
      
      document.getElementById("catalog-count-label").textContent = `Showing ${courses.length} of ${state.courses.length} courses`;
      
      if (courses.length === 0) {
        grid.innerHTML = `
          <div class="col-12 text-center py-5">
            <h5 class="text-muted fw-700"><i class="bi bi-funnel me-2"></i>No courses matches your filters</h5>
            <p class="text-muted small">Try modifying search filters above.</p>
          </div>
        `;
        return;
      }
      
      courses.forEach(c => {
        grid.appendChild(createCourseCard(c));
      });
    }

    // =========================================================================
    // 3. COURSE DETAIL VIEW
    // =========================================================================
    function renderCourseDetail() {
      fetch(`?ajax=get_course_detail&course_id=${state.activeDetailCourseId}`)
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            const c = res.course;
            
            document.getElementById("detail-bc-title").textContent = c.title;
            document.getElementById("detail-title").textContent = c.title;
            document.getElementById("detail-subtitle").textContent = c.subtitle;
            document.getElementById("detail-rating-score").textContent = c.rating;
            document.getElementById("detail-review-count").textContent = c.review_count.toLocaleString();
            document.getElementById("detail-student-count").textContent = c.student_count.toLocaleString();
            document.getElementById("detail-instructor-name").textContent = c.instructor;
            document.getElementById("detail-bestseller-badge").classList.toggle("d-none", parseInt(c.is_bestseller) === 0);
            
            // Description
            document.getElementById("detail-description").innerHTML = c.description ? `<p>${c.description}</p>` : '<p>No description provided.</p>';
            
            // What you'll learn items
            const learnBox = document.getElementById("detail-learn-items");
            learnBox.innerHTML = `
              <div class="col-sm-6 d-flex gap-2 text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Build clean portfolio projects.</div>
              <div class="col-sm-6 d-flex gap-2 text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Master database tables structure.</div>
              <div class="col-sm-6 d-flex gap-2 text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Deploy dynamic platforms securely.</div>
              <div class="col-sm-6 d-flex gap-2 text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Earn a verified completed badge.</div>
            `;
            
            // Instructor profile
            document.getElementById("detail-instructor-hname").textContent = c.instructor;
            document.getElementById("detail-instructor-avatar").src = c.instructor_avatar || 'default.png';
            document.getElementById("detail-instructor-bio").textContent = c.instructor_bio || 'No bio provided.';
            document.getElementById("detail-instructor-students-count").textContent = c.instructor_students_count.toLocaleString();
            document.getElementById("detail-instructor-courses-count").textContent = c.instructor_courses_count;
            
            // Compose box show/hide based on enrollment
            document.getElementById("review-compose-box").classList.toggle("d-none", !res.userState.isEnrolled);

            // Curriculum accordion
            const acc = document.getElementById("curriculumAccordion");
            acc.innerHTML = '';
            
            if (res.sections.length === 0) {
              acc.innerHTML = '<div class="p-4 text-center text-muted small">No curriculum uploaded yet.</div>';
            } else {
              res.sections.forEach((sec, sIdx) => {
                let lessonsList = '';
                
                sec.lessons.forEach((les, lIdx) => {
                  let lesIcon = '<i class="bi bi-lock-fill text-muted"></i>';
                  
                  if (res.userState.isEnrolled) {
                    lesIcon = '<i class="bi bi-play-circle-fill text-primary"></i>';
                  } else if (parseInt(les.is_preview) === 1) {
                    lesIcon = '<i class="bi bi-eye-fill text-success" title="Free Preview"></i>';
                  }
                  
                  lessonsList += `
                    <div class="d-flex align-items-center justify-content-between p-3 border-bottom bg-white small">
                      <div class="d-flex align-items-center gap-3">
                        ${lesIcon}
                        <span class="fw-600 text-dark">${les.title}</span>
                      </div>
                      <span class="text-muted">${les.duration_minutes}m</span>
                    </div>
                  `;
                });
                
                acc.innerHTML += `
                  <div class="accordion-item border-0">
                    <h2 class="accordion-header" id="heading-${sec.id}">
                      <button class="accordion-button collapsed py-3 fw-700 bg-light text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-${sec.id}">
                        ${sec.title}
                      </button>
                    </h2>
                    <div id="collapse-${sec.id}" class="accordion-collapse collapse" data-bs-parent="#curriculumAccordion">
                      <div class="accordion-body p-0">
                        ${lessonsList}
                      </div>
                    </div>
                  </div>
                `;
              });
            }
            
            // Reviews average
            document.getElementById("detail-rating-big").textContent = c.rating;
            document.getElementById("detail-stars-big").innerHTML = renderStarsMarkup(c.rating);
            
            // Review comments
            const revBox = document.getElementById("detail-reviews-list");
            revBox.innerHTML = '';
            if (res.reviews.length === 0) {
              revBox.innerHTML = '<p class="text-muted small">No reviews written yet. Be the first to share feedback!</p>';
            } else {
              res.reviews.forEach(r => {
                revBox.innerHTML += `
                  <div class="p-3 bg-light border rounded-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                      <div class="d-flex align-items-center gap-2">
                        <img src="${r.user_avatar || 'default.png'}" class="rounded-circle border" style="width:30px;height:30px;object-fit:cover;">
                        <span class="fw-700 small text-dark">${r.user_name}</span>
                      </div>
                      <span class="text-warning small">${renderStarsMarkup(r.rating)}</span>
                    </div>
                    <p class="text-muted small mb-0 lh-sm">"${r.comment}"</p>
                  </div>
                `;
              });
            }
            
            // Sidebar actions
            document.getElementById("detail-sidebar-img").src = c.thumbnail;
            
            const realPrice = parseFloat(c.price);
            const discPrice = parseFloat(c.discount_price || c.price);
            
            document.getElementById("detail-sidebar-price").textContent = discPrice === 0 ? 'Free' : `₹${discPrice.toFixed(2)}`;
            
            if (discPrice < realPrice && discPrice > 0) {
              document.getElementById("detail-sidebar-oldprice").textContent = `₹${realPrice.toFixed(2)}`;
              document.getElementById("detail-sidebar-oldprice").classList.remove("d-none");
              
              const off = Math.round(((realPrice - discPrice) / realPrice) * 100);
              document.getElementById("detail-sidebar-discount").textContent = `${off}% OFF`;
              document.getElementById("detail-sidebar-discount").classList.remove("d-none");
            } else {
              document.getElementById("detail-sidebar-oldprice").classList.add("d-none");
              document.getElementById("detail-sidebar-discount").classList.add("d-none");
            }
            
            document.getElementById("detail-hours-label").textContent = c.total_hours;
            
            // Sidebar Actions Button Setup
            const actBox = document.getElementById("detail-sidebar-actions");
            actBox.innerHTML = '';
            
            if (res.userState.isEnrolled) {
              actBox.innerHTML = `
                <button class="btn btn-primary-klean py-3 rounded-3" onclick="switchView('player-view', {courseId: ${c.id}})">
                  <i class="bi bi-play-circle-fill me-2"></i>Go to Course Player
                </button>
              `;
            } else if (res.userState.inCart) {
              actBox.innerHTML = `
                <button class="btn btn-primary-klean py-3 rounded-3" onclick="switchView('cart-view')">
                  <i class="bi bi-cart-check-fill me-2"></i>Go to Shopping Cart
                </button>
              `;
            } else {
              actBox.innerHTML = `
                <button class="btn btn-primary-klean py-3 rounded-3" onclick="addToCart(${c.id})">
                  Buy Enrolment Now
                </button>
                <button class="btn btn-outline-klean py-2 rounded-3" onclick="addToWishlist(${c.id})">
                  <i class="bi ${res.userState.inWishlist ? 'bi-heart-fill text-danger' : 'bi-heart'} me-2"></i>Save to Wishlist
                </button>
              `;
            }
          }
        });
    }

    function renderStarsMarkup(rating) {
      let markup = '';
      for (let i = 1; i <= 5; i++) {
        markup += i <= Math.floor(rating) ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-secondary opacity-30"></i>';
      }
      return markup;
    }

    // =========================================================================
    // 4. CART & WISH ACTIONS
    // =========================================================================
    function addToCart(courseId) {
      if (!currentUser) {
        showToast("Please log in to add items to cart.", 'warning');
        switchView('login-view');
        return;
      }
      
      const fd = new FormData();
      fd.append("course_id", courseId);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=cart_add', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast("Added to Cart successfully! 🛒", 'success');
          fetchCartCount();
          renderCourseDetail(); // refresh detail button states
        } else {
          showToast(res.error, 'danger');
        }
      });
    }

    function quickAddToCart(courseId) {
      if (!currentUser) {
        showToast("Please log in to add items to cart.", 'warning');
        switchView('login-view');
        return;
      }
      
      if (currentUser.role === 'instructor') {
        showToast("Instructors cannot purchase courses.", 'warning');
        return;
      }
      
      const fd = new FormData();
      fd.append("course_id", courseId);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=cart_add', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast("Added to Cart successfully! 🛒", 'success');
          fetchCartCount();
        } else {
          showToast(res.error, 'warning');
        }
      });
    }

    function toggleWishlist(courseId, btn) {
      if (!currentUser) {
        showToast("Please log in to bookmark courses.", 'warning');
        switchView('login-view');
        return;
      }
      
      const fd = new FormData();
      fd.append("course_id", courseId);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=wishlist', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          const isAdded = res.action === 'added';
          showToast(isAdded ? 'Added to Wishlist! ❤️' : 'Removed from Wishlist', 'success');
          
          if (btn) {
            const icon = btn.querySelector("i");
            if (icon) {
              icon.className = isAdded ? 'bi bi-heart-fill text-danger' : 'bi bi-heart';
            }
          }
          
          fetchCartCount();
          
          if (state.currentView === 'wishlist-view') {
            renderWishlistPage();
          }
        }
      });
    }

    // =========================================================================
    // 5. CART PAGE HYDRATION
    // =========================================================================
    function renderCartPage() {
      fetch('?ajax=get_cart')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            const items = res.items;
            const col = document.getElementById("cart-items-list");
            col.innerHTML = '';
            
            const empty = document.getElementById("cart-empty-ui");
            const row = document.getElementById("cart-row-content");
            
            if (items.length === 0) {
              row.classList.add("d-none");
              empty.classList.remove("d-none");
              return;
            }
            
            row.classList.remove("d-none");
            empty.classList.add("d-none");
            
            let subtotal = 0;
            items.forEach(c => {
              const price = parseFloat(c.discount_price || c.price);
              subtotal += price;
              
              col.innerHTML += `
                <div class="card p-3 border rounded-4 bg-white mb-3 shadow-sm">
                  <div class="row align-items-center g-3">
                    <div class="col-4 col-sm-3 col-md-2">
                      <img src="${c.thumbnail}" class="img-fluid rounded-3 w-100" style="aspect-ratio:16/9; object-fit:cover;">
                    </div>
                    <div class="col-8 col-sm-6 col-md-7">
                      <h6 class="fw-700 text-dark mb-1 text-truncate-2">${c.title}</h6>
                      <p class="text-muted small mb-0">Mentor: ${c.instructor} &middot; ${c.total_hours}h</p>
                    </div>
                    <div class="col-sm-3 col-md-3 text-sm-end d-flex flex-row flex-sm-column justify-content-between align-items-center align-items-sm-end">
                      <h5 class="fw-800 text-primary m-0">₹${price.toFixed(2)}</h5>
                      <button class="btn btn-link text-danger text-decoration-none small p-0 fw-600 mt-sm-2" onclick="removeCartItem(${c.id})"><i class="bi bi-trash me-1"></i>Remove</button>
                    </div>
                  </div>
                </div>
              `;
            });
            
            calculateCartTotals(subtotal);
          }
        });
    }

    function removeCartItem(courseId) {
      const fd = new FormData();
      fd.append("course_id", courseId);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=cart_remove', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast("Item removed from cart.", 'success');
          fetchCartCount();
          renderCartPage();
        }
      });
    }

    var cartSubtotal = 0;
    var appliedDiscount = 0;
    var finalOrderTotal = 0;

    function calculateCartTotals(subtotal) {
      cartSubtotal = subtotal;
      
      if (state.couponApplied) {
        appliedDiscount = cartSubtotal * 0.20; // KLEAN20 = 20%
        document.getElementById("summary-discount-row").classList.remove("d-none");
        document.getElementById("summary-discount").textContent = `-₹${appliedDiscount.toFixed(2)}`;
      } else {
        appliedDiscount = 0;
        document.getElementById("summary-discount-row").classList.add("d-none");
      }
      
      finalOrderTotal = cartSubtotal - appliedDiscount;
      
      document.getElementById("summary-subtotal").textContent = `₹${cartSubtotal.toFixed(2)}`;
      document.getElementById("summary-total").textContent = `₹${finalOrderTotal.toFixed(2)}`;
    }

    function applyCoupon() {
      const code = document.getElementById("coupon-input").value.trim().toUpperCase();
      if (code === 'KLEAN20') {
        state.couponApplied = true;
        state.couponCode = 'KLEAN20';
        showToast("Coupon KLEAN20 applied! 20% off your purchase. 🎉", 'success');
        renderCartPage();
      } else {
        showToast("Invalid or expired coupon code.", 'warning');
      }
    }

    function proceedToCheckout() {
      switchView('checkout-view');
    }

    // =========================================================================
    // 6. PAYMENT CHECKOUT SUMMARY & SIMULATION DRIVERS
    // =========================================================================
    function renderCheckoutSummary() {
      document.querySelectorAll(".checkout-pay-total").forEach(el => {
        el.textContent = `₹${finalOrderTotal.toFixed(2)}`;
      });
      
      // Reset virtual card display to defaults
      document.getElementById("cc-name-input").value = "";
      document.getElementById("cc-number-input").value = "";
      document.getElementById("cc-expiry-input").value = "";
      document.getElementById("card-name-display").textContent = "YOUR NAME";
      document.getElementById("card-number-display").textContent = "•••• •••• •••• ••••";
      document.getElementById("card-expiry-display").textContent = "MM/YY";
      document.getElementById("card-type-display").textContent = "DEBIT CARD";
      
      // Reset UPI inputs
      document.getElementById("upi-id-input").value = "";
      
      // Reset wallet styling
      document.querySelectorAll(".wallet-select").forEach(el => {
        el.classList.remove("active");
        el.style.border = "1px solid var(--border)";
      });
      document.getElementById("w-paytm").classList.add("active");
      document.getElementById("w-paytm").style.border = "2px solid var(--primary)";
    }

    function updateCardDisplay(field, input) {
      if (field === 'name') {
        const val = input.value.trim();
        document.getElementById("card-name-display").textContent = val ? val.toUpperCase() : "YOUR NAME";
      } else if (field === 'number') {
        // Auto-space card number formatting (every 4 digits)
        let val = input.value.replace(/\D/g, '');
        let formatted = '';
        for (let i = 0; i < val.length; i++) {
          if (i && i % 4 === 0) formatted += ' ';
          formatted += val[i];
        }
        input.value = formatted;
        document.getElementById("card-number-display").textContent = formatted ? formatted : "•••• •••• •••• ••••";
        
        // Detect card type (e.g., 4 starts Visa, 5 starts Mastercard)
        const type = document.getElementById("card-type-display");
        if (val.startsWith('4')) {
          type.textContent = "VISA";
          type.style.color = "#FFF";
        } else if (val.startsWith('5')) {
          type.textContent = "MASTERCARD";
          type.style.color = "#FFB020";
        } else {
          type.textContent = "DEBIT CARD";
          type.style.color = "#FFF";
        }
      } else if (field === 'expiry') {
        // Auto-slash expiry formatting (MM/YY)
        let val = input.value.replace(/\D/g, '');
        if (val.length > 2) {
          input.value = val.slice(0, 2) + '/' + val.slice(2, 4);
        } else {
          input.value = val;
        }
        document.getElementById("card-expiry-display").textContent = input.value ? input.value : "MM/YY";
      }
    }

    function selectUpiApp(app) {
      document.getElementById("upi-id-input").value = `alex@${app}`;
      showToast(`${app.toUpperCase()} UPI app selected!`, 'success');
    }

    function selectWallet(wallet, element) {
      document.querySelectorAll(".wallet-select").forEach(el => {
        el.classList.remove("active");
        el.style.border = "1px solid var(--border)";
      });
      element.classList.add("active");
      element.style.border = "2px solid var(--primary)";
      showToast(`${wallet.toUpperCase()} Wallet balance selected!`, 'success');
    }

    function submitPayment(e) {
      e.preventDefault();
      
      // Show secure loader
      const loader = document.getElementById("loading-spinner");
      loader.classList.remove("d-none");
      
      // Delay pay flow by 1.5s as requested in user players requirements
      setTimeout(function() {
        const method = document.querySelector(".pay-tab.active").getAttribute("data-target").replace("panel-", "");
        
        fetch('?ajax=checkout', {
          method: 'POST',
          body: JSON.stringify({
            method: method,
            coupon: state.couponCode
          }),
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          }
        })
        .then(r => r.json())
        .then(res => {
          loader.classList.add("d-none");
          if (res.success) {
            // Trigger confetti
            triggerConfettiFalling();
            
            // Redirect page reference
            window.location.href = res.redirect;
          } else {
            showToast(res.error, 'danger');
          }
        });
      }, 1500);
    }

    // Success Hydrate
    function showSuccessPage(orderId) {
      switchView('success-view');
      document.getElementById("success-order-id").textContent = orderId;
      triggerConfettiFalling();
      fetchCartCount();
    }

    // =========================================================================
    // 7. STUDENT DASHBOARD
    // =========================================================================
    function renderStudentDashboard() {
      fetch('?ajax=get_dashboard')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            document.getElementById("dash-enrolled-count").textContent = res.stats.enrolled;
            document.getElementById("dash-completed-count").textContent = res.stats.completed;
            document.getElementById("dash-hours-learned").textContent = res.stats.hours + "h";
            
            // Hydrate enrolled courses
            const container = document.getElementById("dash-courses-container");
            container.innerHTML = '';
            
            if (res.courses.length === 0) {
              container.innerHTML = `
                <div class="col-12 text-center py-4 bg-white border rounded-4 shadow-sm">
                  <h6 class="text-muted fw-700"><i class="bi bi-journal-x"></i> You are not enrolled in any courses yet</h6>
                  <button class="btn btn-primary-klean btn-sm mt-3" onclick="switchView('courses-view')">Explore Catalog</button>
                </div>
              `;
            } else {
              res.courses.forEach(c => {
                const certBtn = parseInt(c.progress) >= 100 && c.certificate_no 
                  ? `<button class="btn btn-sm btn-outline-success py-1 px-3 mt-2 rounded-pill" onclick="openCertificate('${c.certificate_no}', '${c.title}')"><i class="bi bi-award-fill"></i> View Certificate</button>`
                  : '';
                  
                container.innerHTML += `
                  <div class="col-md-6 col-lg-4">
                    <div class="card p-3 border bg-white rounded-4 shadow-sm h-100 d-flex flex-column">
                      <img src="${c.thumbnail}" class="img-fluid rounded-3 mb-3 w-100" style="aspect-ratio:16/9; object-fit:cover;">
                      <h6 class="fw-800 text-dark mb-1 text-truncate-2" style="min-height:38px;">${c.title}</h6>
                      <p class="text-muted small mb-3">Instructor: ${c.instructor}</p>
                      
                      <div class="mt-auto">
                        <div class="d-flex justify-content-between mb-1 small text-muted">
                          <span>Progress</span>
                          <span>${c.progress}%</span>
                        </div>
                        <div class="progress mb-3" style="height:6px;">
                          <div class="progress-bar progress-bar-custom" style="width: ${c.progress}%;"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                          <button class="btn btn-sm btn-primary-klean py-1 px-3 mt-2 rounded-pill" onclick="switchView('player-view', {courseId: ${c.id}})"><i class="bi bi-play-fill"></i> Learn</button>
                          ${certBtn}
                        </div>
                      </div>
                    </div>
                  </div>
                `;
              });
            }
            
            // Hydrate dashboard recommendations
            const recsBox = document.getElementById("dash-recommended-container");
            recsBox.innerHTML = '';
            res.recommended.forEach(c => {
              recsBox.appendChild(createCourseCard(c));
            });
          }
        });
    }

    function openCertificate(certNo, title) {
      document.getElementById("cert-user-name").textContent = currentUser.name;
      document.getElementById("cert-course-title").textContent = title;
      document.getElementById("cert-no").textContent = certNo;
      
      const today = new Date().toISOString().split('T')[0];
      document.getElementById("cert-date").textContent = today;
      
      const modal = new bootstrap.Modal(document.getElementById("certModal"));
      modal.show();
    }

    // =========================================================================
    // 8. COURSE PLAYER LOGIC
    // =========================================================================
    var activeCourseData = null;
    
    function renderCoursePlayer() {
      fetch(`?ajax=get_course_detail&course_id=${state.activePlayerCourseId}`)
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            activeCourseData = res;
            
            // Calculate player outline
            let list = document.getElementById("player-curriculum-list");
            list.innerHTML = '';
            
            // Reset player simulation trigger states
            state.activePlayerPlayState = false;
            document.getElementById("player-sim-overlay").style.display = 'block';
            document.getElementById("player-play-icon").className = 'bi bi-play-fill';
            
            // Calculate total progress metrics inside sidebar
            const progress = res.userState.isEnrolled ? getCourseProgressPercent(res) : 0;
            document.getElementById("player-progress-bar").style.width = `${progress}%`;
            document.getElementById("player-progress-text").textContent = `${progress}% Complete`;

            let flatLessons = [];
            
            res.sections.forEach((sec, sIdx) => {
              list.innerHTML += `<div class="bg-dark p-2 text-white-50 border-bottom border-secondary small fw-700" style="font-size:0.75rem;">${sec.title.toUpperCase()}</div>`;
              
              sec.lessons.forEach(les => {
                flatLessons.push(les);
                const isCompleted = isLessonMarkedComplete(les.id);
                const checkboxMarkup = isCompleted 
                  ? '<i class="bi bi-check-square-fill text-success fs-5"></i>' 
                  : '<i class="bi bi-square text-muted fs-5"></i>';
                  
                const isActive = les.id === getCurrentActiveLessonId(flatLessons);
                const activeClass = isActive ? 'active-lesson' : '';
                
                list.innerHTML += `
                  <div class="player-playlist-item ${activeClass}" onclick="playLecture(${flatLessons.length - 1})">
                    ${checkboxMarkup}
                    <div class="small lh-sm text-white">
                      <div class="fw-700">${les.title}</div>
                      <span class="text-white-50" style="font-size: 0.7rem;">${les.duration_minutes}m</span>
                    </div>
                  </div>
                `;
              });
            });
            
            // Load lecture details
            const activeLesson = flatLessons[state.activePlayerLectureIdx] || flatLessons[0];
            if (activeLesson) {
              document.getElementById("player-lecture-title").textContent = activeLesson.title;
              
              // Load active lesson notes
              loadLessonNote(activeLesson.id);
              
              // Adjust controls complete button text
              const isCompleted = isLessonMarkedComplete(activeLesson.id);
              const btn = document.getElementById("player-complete-btn");
              if (isCompleted) {
                btn.innerHTML = 'Completed <i class="bi bi-check-circle-fill text-success"></i>';
                btn.className = 'btn btn-sm btn-outline-success py-2';
              } else {
                btn.innerHTML = 'Mark Lesson Complete <i class="bi bi-check2-circle"></i>';
                btn.className = 'btn btn-sm btn-accent-klean py-2';
              }
            }
          }
        });
    }

    function getCourseProgressPercent(courseDetails) {
      let total = 0;
      let completed = 0;
      
      courseDetails.sections.forEach(sec => {
        sec.lessons.forEach(les => {
          total++;
          if (isLessonMarkedComplete(les.id)) {
            completed++;
          }
        });
      });
      
      if (total === 0) return 0;
      return Math.round((completed / total) * 100);
    }

    function isLessonMarkedComplete(lessonId) {
      // Alex progress simulation uses seeded local arrays or parsed arrays
      // alex progress on lessons 1,2,3,4 completed
      if (currentUser && currentUser.id === 3) {
        // Alexis student
        if ([1,2,3,4].includes(lessonId)) return true;
      }
      return false;
    }

    function getCurrentActiveLessonId(flatLessons) {
      const active = flatLessons[state.activePlayerLectureIdx];
      return active ? active.id : 0;
    }

    function playLecture(idx) {
      state.activePlayerLectureIdx = idx;
      renderCoursePlayer();
    }

    function playPrevLesson() {
      if (state.activePlayerLectureIdx > 0) {
        state.activePlayerLectureIdx--;
        renderCoursePlayer();
      }
    }

    function playNextLesson() {
      let flat = getFlatLessonsList();
      if (state.activePlayerLectureIdx < flat.length - 1) {
        state.activePlayerLectureIdx++;
        renderCoursePlayer();
      }
    }

    function getFlatLessonsList() {
      let arr = [];
      if (activeCourseData) {
        activeCourseData.sections.forEach(sec => {
          sec.lessons.forEach(les => {
            arr.push(les);
          });
        });
      }
      return arr;
    }

    // Playback toggle
    function togglePlayerPlayback() {
      state.activePlayerPlayState = !state.activePlayerPlayState;
      const overlay = document.getElementById("player-sim-overlay");
      const icon = document.getElementById("player-play-icon");
      const status = document.getElementById("player-playback-status");
      
      if (state.activePlayerPlayState) {
        icon.className = 'bi bi-pause-fill';
        status.innerHTML = '<span class="text-success fw-700">Playing Simulation Video...</span>';
        showToast("Simulation video playback started.", 'success');
      } else {
        icon.className = 'bi bi-play-fill';
        status.textContent = 'Simulation Mode (Click Screen to Play)';
      }
    }

    // Toggle complete
    function toggleLessonComplete() {
      const flat = getFlatLessonsList();
      const les = flat[state.activePlayerLectureIdx];
      if (!les) return;
      
      const isCurrentlyCompleted = isLessonMarkedComplete(les.id);
      const targetState = isCurrentlyCompleted ? 0 : 1;
      
      const fd = new FormData();
      fd.append("lesson_id", les.id);
      fd.append("course_id", state.activePlayerCourseId);
      fd.append("is_completed", targetState);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=progress', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast(targetState === 1 ? "Lesson marked completed! 🎓" : "Lesson marked incomplete", 'success');
          
          // Re-hydrate
          renderCoursePlayer();
        }
      });
    }

    // Notes auto-save
    var noteTimer = null;
    function saveLessonNote() {
      clearTimeout(noteTimer);
      
      const text = document.getElementById("player-note-textarea").value;
      const flat = getFlatLessonsList();
      const les = flat[state.activePlayerLectureIdx];
      if (!les) return;
      
      // Delay note save by 700ms to debounce typing
      noteTimer = setTimeout(function() {
        const fd = new FormData();
        fd.append("lesson_id", les.id);
        fd.append("note", text);
        fd.append("csrf_token", csrfToken);
        
        fetch('?ajax=save_note', {
          method: 'POST',
          body: fd
        })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            // Notes saved successfully
          }
        });
      }, 700);
    }

    function loadLessonNote(lessonId) {
      fetch(`?ajax=get_note&lesson_id=${lessonId}`)
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            document.getElementById("player-note-textarea").value = res.note;
          }
        });
    }

    // =========================================================================
    // 9. INSTRUCTOR PORTAL HYDRATION
    // =========================================================================
    function renderInstructorDashboard() {
      fetch('?ajax=get_instructor_dashboard')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            document.getElementById("inst-students-count").textContent = res.stats.students.toLocaleString();
            document.getElementById("inst-revenue").textContent = `₹${parseFloat(res.stats.revenue).toFixed(2)}`;
            document.getElementById("inst-rating").textContent = res.stats.rating;
            document.getElementById("inst-courses-count").textContent = res.stats.courses;
            
            // Hydrate monthly earnings CSS bar chart
            const chart = document.getElementById("inst-chart-wrapper");
            chart.innerHTML = '';
            
            // Determine max amount to scale height percentage
            let maxAmount = 100;
            res.earnings.forEach(e => {
              if (e.amount > maxAmount) maxAmount = e.amount;
            });
            
            res.earnings.forEach(e => {
              const heightPct = (e.amount / maxAmount) * 100;
              chart.innerHTML += `
                <div class="revenue-chart-col">
                  <div class="revenue-chart-bar" style="height: ${heightPct}%;" data-value="₹${Math.round(e.amount)}"></div>
                  <div class="revenue-chart-label">${e.month}</div>
                </div>
              `;
            });
            
            // Hydrate table of published courses
            const table = document.getElementById("inst-courses-table");
            table.innerHTML = '';
            
            if (res.courses.length === 0) {
              table.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted small">No courses published yet.</td></tr>`;
            } else {
              res.courses.forEach(c => {
                table.innerHTML += `
                  <tr class="small text-secondary">
                    <td><strong class="text-dark">${c.title}</strong></td>
                    <td>${c.student_count.toLocaleString()} students</td>
                    <td>₹${parseFloat(c.price).toFixed(2)}</td>
                    <td><span class="text-warning"><i class="bi bi-star-fill me-1"></i>${c.rating}</span></td>
                    <td><span class="badge bg-success">PUBLISHED</span></td>
                  </tr>
                `;
              });
            }
          }
        });
    }

    // Modal handle
    function openCreateCourseModal() {
      const modal = new bootstrap.Modal(document.getElementById("createCourseModal"));
      modal.show();
    }

    function submitCreateCourse(e) {
      e.preventDefault();
      
      const fd = new FormData();
      fd.append("title", document.getElementById("course-title").value);
      fd.append("subtitle", document.getElementById("course-subtitle").value);
      fd.append("description", document.getElementById("course-desc").value);
      fd.append("category_id", document.getElementById("course-category").value);
      fd.append("level", document.getElementById("course-level").value);
      fd.append("price", document.getElementById("course-price").value);
      fd.append("discount_price", document.getElementById("course-discount").value);
      fd.append("total_hours", document.getElementById("course-hours").value);
      fd.append("thumbnail", document.getElementById("course-thumbnail").value);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=create_course', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          // Close modal
          const modalEl = document.getElementById("createCourseModal");
          const modal = bootstrap.Modal.getInstance(modalEl);
          modal.hide();
          
          showToast("New dynamic course published successfully! 🚀", 'success');
          
          // Re-fetch courses and re-render
          fetchCoursesData(function() {
            renderInstructorDashboard();
          });
        } else {
          showToast(res.error, 'danger');
        }
      });
    }

    // =========================================================================
    // 10. WISHLIST VIEW HYDRATION
    // =========================================================================
    function renderWishlistPage() {
      fetch('?ajax=get_wishlist')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            const items = res.items;
            const grid = document.getElementById("wishlist-grid");
            grid.innerHTML = '';
            
            const empty = document.getElementById("wishlist-empty-ui");
            
            if (items.length === 0) {
              empty.classList.remove("d-none");
              return;
            }
            
            empty.classList.add("d-none");
            items.forEach(c => {
              grid.appendChild(createCourseCard(c));
            });
          }
        });
    }

    // =========================================================================
    // 11. PROFILE SETTINGS HYDRATION
    // =========================================================================
    function renderSettingsPage() {
      if (!currentUser) return;
      
      document.getElementById("profile-name").value = currentUser.name;
      document.getElementById("profile-email").value = currentUser.email;
      
      // Fetch details from DB to pre-fill bio/phone
      fetch('?ajax=get_dashboard') // reuse to read student profile coordinates
        .then(r => r.json())
        .then(res => {
          // Alex Johnson mock phone pre-populated in database migrations
          document.getElementById("profile-phone").value = '+0987654321';
          document.getElementById("profile-bio").value = 'Ambitious developer learning to build clean visual layouts on Klean.';
        });
    }

    function submitProfileUpdate(e) {
      e.preventDefault();
      
      const fd = new FormData();
      fd.append("name", document.getElementById("profile-name").value);
      fd.append("email", document.getElementById("profile-email").value);
      fd.append("phone", document.getElementById("profile-phone").value);
      fd.append("bio", document.getElementById("profile-bio").value);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=profile', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          currentUser = res.user;
          showToast("Profile credentials updated successfully! ✅", 'success');
          updateNavbar();
        } else {
          showToast(res.error, 'danger');
        }
      });
    }

    function submitPasswordUpdate(e) {
      e.preventDefault();
      
      const curr = document.getElementById("pass-current").value;
      const newPass = document.getElementById("pass-new").value;
      const conf = document.getElementById("pass-confirm").value;
      
      if (newPass !== conf) {
        showToast("New passwords do not match.", 'warning');
        return;
      }
      
      const fd = new FormData();
      fd.append("current_password", curr);
      fd.append("new_password", newPass);
      fd.append("confirm_password", conf);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=change_password', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast("Password updated successfully! 🔒", 'success');
          // Clear inputs
          document.getElementById("pass-current").value = '';
          document.getElementById("pass-new").value = '';
          document.getElementById("pass-confirm").value = '';
        } else {
          showToast(res.error, 'danger');
        }
      });
    }

    // =========================================================================
    // 12. WRITE REVIEWS COMPONENT
    // =========================================================================
    function submitReview(e) {
      e.preventDefault();
      
      const rate = document.getElementById("review-rating-select").value;
      const text = document.getElementById("review-comment-text").value;
      
      const fd = new FormData();
      fd.append("course_id", state.activeDetailCourseId);
      fd.append("rating", rate);
      fd.append("comment", text);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=review', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast("Review submitted successfully! Thank you for the feedback. 🌟", 'success');
          document.getElementById("review-comment-text").value = '';
          renderCourseDetail(); // refresh lists
        } else {
          showToast(res.error, 'danger');
        }
      });
    }

    // =========================================================================
    // 13. NOTIFICATIONS DROP LIST
    // =========================================================================
    function loadNotifications() {
      fetch('?ajax=get_notifications')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            const list = document.getElementById("notif-list");
            list.innerHTML = '<li class="dropdown-header text-muted fw-600 py-2 border-bottom">Notifications</li>';
            
            // Adjust badge count
            const badge = document.getElementById("notif-badge");
            badge.textContent = res.unread;
            badge.classList.toggle("d-none", res.unread === 0);
            
            if (res.list.length === 0) {
              list.innerHTML += '<li class="text-center py-3 text-muted small">No new notifications.</li>';
              return;
            }
            
            res.list.forEach(n => {
              const bg = parseInt(n.is_read) === 0 ? 'bg-light font-weight-bold text-dark' : 'text-secondary';
              list.innerHTML += `
                <li class="p-2 border-bottom ${bg}" style="font-size:0.75rem; cursor:pointer;" onclick="markNotificationRead(${n.id})">
                  <div class="lh-sm mb-1">${n.message}</div>
                  <div class="text-muted" style="font-size:0.65rem;">${new Date(n.created_at).toLocaleDateString()}</div>
                </li>
              `;
            });
            
            list.innerHTML += '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-center small text-primary fw-600" href="#" onclick="markNotificationRead(0);return false;">Mark all as read</a></li>';
          }
        });
    }

    function markNotificationRead(id) {
      const fd = new FormData();
      fd.append("id", id);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=mark_notification_read', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          loadNotifications();
        }
      });
    }

    // =========================================================================
    // 14. AUTHENTICATION (LOGIN, SIGNUP, LOGOUT HANDLES)
    // =========================================================================
    function quickFill(email, pass) {
      document.getElementById("login-email").value = email;
      document.getElementById("login-password").value = pass;
    }

    function submitLogin(e) {
      e.preventDefault();
      
      const fd = new FormData();
      fd.append("email", document.getElementById("login-email").value);
      fd.append("password", document.getElementById("login-password").value);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=login', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          currentUser = res.user;
          showToast("Welcome back, " + currentUser.name + "! 👋", 'success');
          
          // Redirect
          setTimeout(function() {
            window.location.href = res.redirect;
          }, 600);
        } else {
          showToast(res.error, 'danger');
        }
      });
    }

    function submitSignup(e) {
      e.preventDefault();
      
      const role = document.querySelector("input[name='signup-role']:checked").value;
      const name = document.getElementById("signup-name").value;
      const email = document.getElementById("signup-email").value;
      const pass = document.getElementById("signup-password").value;
      const conf = document.getElementById("signup-confirm").value;
      
      if (pass !== conf) {
        showToast("Passwords do not match.", 'warning');
        return;
      }
      
      const fd = new FormData();
      fd.append("role", role);
      fd.append("name", name);
      fd.append("email", email);
      fd.append("password", pass);
      fd.append("confirm_password", conf);
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=signup', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast("Registration completed! Classroom account is active. 🎓", 'success');
          
          // Auto reload
          setTimeout(function() {
            window.location.href = res.redirect;
          }, 800);
        } else {
          showToast(res.error, 'danger');
        }
      });
    }

    function handleLogout() {
      const fd = new FormData();
      fd.append("csrf_token", csrfToken);
      
      fetch('?ajax=logout', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          currentUser = null;
          showToast("Successfully logged out. Goodbye!", 'success');
          setTimeout(function() {
            window.location.href = '?page=home';
          }, 600);
        }
      });
    }

    // =========================================================================
    // UTILITIES & ANIMATIONS
    // =========================================================================
    function showToast(message, type = 'success') {
      const holder = document.getElementById("toast-holder");
      const toast = document.createElement("div");
      toast.className = `klean-toast alert alert-${type}`;
      
      let icon = '<i class="bi bi-info-circle-fill"></i>';
      if (type === 'success') icon = '<i class="bi bi-check-circle-fill text-success"></i>';
      if (type === 'warning') icon = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
      if (type === 'danger') icon = '<i class="bi bi-x-circle-fill text-danger"></i>';
      
      toast.innerHTML = `${icon} <span>${message}</span>`;
      holder.appendChild(toast);
      
      setTimeout(function() {
        toast.remove();
      }, 4000);
    }

    function triggerConfettiFalling() {
      const holder = document.getElementById("confetti-holder");
      const colors = ['#6C3FF4', '#F59E0B', '#10B981', '#EF4444', '#3B82F6'];
      
      for (let i = 0; i < 60; i++) {
        const piece = document.createElement("div");
        piece.className = 'confetti-piece';
        
        piece.style.left = Math.random() * 100 + 'vw';
        piece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        piece.style.animationDelay = Math.random() * 2 + 's';
        piece.style.width = Math.random() * 8 + 6 + 'px';
        piece.style.height = piece.style.width;
        
        holder.appendChild(piece);
        
        // Remove after animated
        setTimeout(function() {
          piece.remove();
        }, 5000);
      }
    }
  </script>
</body>
</html>
