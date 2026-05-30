<?php
/*
=============================================================================
   KLEAN PLATFORM — POSTGRESQL DATABASE EXPLORER
=============================================================================
   This standalone utility lets you verify your connection and browse all
   tables and rows in your PostgreSQL database directly in your browser.
   URL: http://localhost/Klearn/db-test.php
=============================================================================
*/

// 1. Connection Configurations (matches index.php)
$db_host = 'localhost';
$db_port = '5432';
$db_user = 'postgres';
$db_pass = ''; // Set your PostgreSQL password here if configured
$db_name = 'klean_db';

$conn_error = null;
$pdo = null;

try {
    $dsn = "pgsql:host=$db_host;port=$db_port;dbname=$db_name";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    $conn_error = $e->getMessage();
    
    // If database does not exist, let's try to connect to the 'postgres' default database to check if pgsql is running
    if (strpos($conn_error, 'does not exist') !== false) {
        try {
            $test_dsn = "pgsql:host=$db_host;port=$db_port;dbname=postgres";
            $test_pdo = new PDO($test_dsn, $db_user, $db_pass);
            $conn_error_rich = "Connection to PostgreSQL server was SUCCESSFUL, but the database '$db_name' does not exist yet. Please run index.php first to automatically create the database schema!";
        } catch (PDOException $ex) {
            $conn_error_rich = "Cannot connect to PostgreSQL server. Ensure your PostgreSQL service is running in Windows Services, and your port/credentials are correct. Error: " . $e->getMessage();
        }
    } else {
        $conn_error_rich = "Connection Error: " . $e->getMessage();
    }
}

// 2. Fetch Tables List
$tables = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            ORDER BY table_name
        ");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        $tables_error = $e->getMessage();
    }
}

// 3. Selected Table details
$selected_table = isset($_GET['table']) && in_array($_GET['table'], $tables) ? $_GET['table'] : null;
$columns = [];
$rows = [];
$row_count = 0;

if ($pdo && $selected_table) {
    try {
        // Get column metadata
        $col_stmt = $pdo->prepare("
            SELECT column_name, data_type, is_nullable
            FROM information_schema.columns 
            WHERE table_schema = 'public' AND table_name = ?
            ORDER BY ordinal_position
        ");
        $col_stmt->execute([$selected_table]);
        $columns = $col_stmt->fetchAll();

        // Get row count
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM \"$selected_table\"");
        $count_stmt->execute();
        $row_count = $count_stmt->fetchColumn();

        // Get actual rows (limited to 50 for performance)
        $row_stmt = $pdo->prepare("SELECT * FROM \"$selected_table\" LIMIT 50");
        $row_stmt->execute();
        $rows = $row_stmt->fetchAll();
    } catch (PDOException $e) {
        $table_view_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PostgreSQL Database Explorer — Klean</title>
    <!-- Modern Styling & Typography -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6C3FF4;
            --primary-hover: #5A2EE3;
            --bg-color: #F8FAFC;
            --card-border: #E2E8F0;
            --text-main: #0F172A;
            --text-muted: #64748B;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
        }
        .navbar-custom {
            background: linear-gradient(135deg, #1E1B4B 0%, #312E81 100%);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .card-custom {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
            border: 1px solid var(--card-border);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 0.75rem 1.25rem;
            color: var(--text-main);
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }
        .sidebar-item:hover {
            background-color: #F1F5F9;
            color: var(--primary-color);
        }
        .sidebar-item.active {
            background-color: #EEF2FF;
            color: var(--primary-color);
            font-weight: 600;
        }
        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }
        .table thead th {
            background-color: #F8FAFC;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--card-border);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table tbody td {
            font-size: 0.9rem;
            vertical-align: middle;
            border-bottom: 1px solid #F1F5F9;
        }
        .pill-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            border-radius: 50px;
            font-weight: 600;
        }
        .code-box {
            background-color: #0F172A;
            color: #38BDF8;
            padding: 1.25rem;
            border-radius: 12px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.85rem;
            overflow-x: auto;
        }
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom py-3">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center fw-800 fs-4" href="db-test.php" id="nav-brand-link">
                <i class="bi bi-database-fill-gear text-info me-2"></i>
                <span>KLEAN <span class="text-info">PostgreSQL</span> Explorer</span>
            </a>
            <div class="d-flex align-items-center">
                <?php if ($pdo): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill d-flex align-items-center" id="status-badge-active">
                        <span class="status-dot bg-success"></span> Connected to `<?= htmlspecialchars($db_name) ?>`
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill d-flex align-items-center" id="status-badge-failed">
                        <span class="status-dot bg-danger"></span> Connection Failed
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        
        <!-- Connection Error Alert -->
        <?php if ($conn_error): ?>
            <div class="card card-custom border-danger mb-5" id="connection-error-card">
                <div class="card-header bg-danger-subtle text-danger p-4">
                    <h5 class="fw-700 mb-0 d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                        PostgreSQL Connection Failed
                    </h5>
                </div>
                <div class="card-body p-4">
                    <p class="mb-4 text-secondary"><?= htmlspecialchars($conn_error_rich ?? $conn_error) ?></p>
                    <h6 class="fw-700">How to Fix This in XAMPP:</h6>
                    <ol class="text-secondary small">
                        <li class="mb-2"><strong>Uncomment Driver:</strong> Click **Config** next to Apache in XAMPP Control Panel -> select **PHP (php.ini)**. Find <code>;extension=pdo_pgsql</code> and <code>;extension=pgsql</code> and remove the semicolon <code>;</code> in front of them, then restart Apache.</li>
                        <li class="mb-2"><strong>Run PostgreSQL:</strong> Ensure PostgreSQL is installed and running on port <code>5432</code>. You can start/check it via Windows Services (search for <code>Services.msc</code> in the Windows search bar and locate `postgresql-x64-XX`).</li>
                        <li class="mb-2"><strong>Configure Password:</strong> Edit <code>db-test.php</code> at the top and set <code>$db_pass = 'your_actual_password';</code> if your local database has a password.</li>
                    </ol>
                    <hr>
                    <a href="db-test.php" class="btn btn-danger px-4 py-2 fw-600 rounded-3 mt-2"><i class="bi bi-arrow-clockwise me-2"></i>Retry Connection</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($pdo): ?>
            <div class="row">
                <!-- Sidebar: Tables Listing -->
                <div class="col-lg-3 mb-4">
                    <div class="card card-custom p-3" id="sidebar-card">
                        <div class="d-flex align-items-center justify-content-between mb-3 px-2">
                            <h6 class="fw-700 uppercase tracking-wide text-secondary mb-0">Tables (<?= count($tables) ?>)</h6>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= count($tables) ?></span>
                        </div>
                        
                        <?php if (empty($tables)): ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="bi bi-folder2-open fs-2 mb-2 d-block"></i>
                                No tables found.<br>Run <a href="index.php">index.php</a> to auto-generate them!
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column">
                                <?php foreach ($tables as $tbl): ?>
                                    <a href="?table=<?= urlencode($tbl) ?>" class="sidebar-item <?= $selected_table === $tbl ? 'active' : '' ?>" id="table-btn-<?= htmlspecialchars($tbl) ?>">
                                        <i class="bi bi-table me-2 small"></i>
                                        <?= htmlspecialchars($tbl) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Main Work Area -->
                <div class="col-lg-9">
                    
                    <?php if (!$selected_table): ?>
                        <!-- Splash State (No Table Selected) -->
                        <div class="card card-custom p-5 text-center" id="empty-state-card">
                            <div class="py-5">
                                <div class="mb-4 text-primary" style="font-size: 4rem;">
                                    <i class="bi bi-database-fill"></i>
                                </div>
                                <h3 class="fw-800">Connection Successful!</h3>
                                <p class="text-secondary max-width-md mx-auto mb-4" style="max-width: 500px;">
                                    You have successfully established a connection with your local PostgreSQL database server. Select any table from the sidebar to inspect its structure and live row data.
                                </p>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="index.php" class="btn btn-outline-primary px-4 py-2 rounded-3 fw-600">
                                        <i class="bi bi-house-door me-2"></i>Go to App Homepage
                                    </a>
                                    <?php if (!empty($tables)): ?>
                                        <a href="?table=<?= urlencode($tables[0]) ?>" class="btn btn-primary px-4 py-2 rounded-3 fw-600" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                                            <i class="bi bi-eye me-2"></i>Browse First Table
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        
                        <!-- Header with Table Info -->
                        <div class="card card-custom mb-4 p-4 d-flex flex-md-row justify-content-between align-items-md-center gap-3" id="table-header-card">
                            <div>
                                <span class="text-uppercase text-primary fw-700 tracking-wide small" style="font-size: 0.75rem;">Active PostgreSQL Table</span>
                                <h2 class="fw-800 mb-0 d-flex align-items-center">
                                    <i class="bi bi-layout-three-columns text-primary me-2"></i>
                                    <?= htmlspecialchars($selected_table) ?>
                                </h2>
                            </div>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill font-weight-600 fs-7" id="row-count-badge">
                                    <i class="bi bi-layers me-1"></i> <?= number_format($row_count) ?> Total Rows
                                </span>
                            </div>
                        </div>

                        <!-- Tabs: Data Explorer & Table Schema -->
                        <div class="card card-custom" id="explorer-data-card">
                            <div class="card-header bg-white border-bottom p-0">
                                <ul class="nav nav-tabs border-0 px-4 pt-2" id="tab-nav">
                                    <li class="nav-item">
                                        <button class="nav-link active fw-600 border-0 border-bottom border-primary border-3 pb-3 px-3 text-primary" id="data-tab-btn" onclick="switchTab('data-content', 'schema-content', this)">
                                            <i class="bi bi-grid-3x3-gap-fill me-1"></i> Data Preview (Max 50 Rows)
                                        </button>
                                    </li>
                                    <li class="nav-item">
                                        <button class="nav-link fw-600 border-0 text-muted pb-3 px-3" id="schema-tab-btn" onclick="switchTab('schema-content', 'data-content', this)">
                                            <i class="bi bi-info-circle-fill me-1"></i> Schema Structure
                                        </button>
                                    </li>
                                </ul>
                            </div>

                            <!-- Tab 1: Data Preview -->
                            <div id="data-content" class="card-body p-0">
                                <?php if (empty($rows)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox-fill fs-1 mb-2 d-block"></i>
                                        No rows exist in the table <strong><?= htmlspecialchars($selected_table) ?></strong> yet.
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0" id="data-preview-table">
                                            <thead>
                                                <tr>
                                                    <?php foreach (array_keys($rows[0]) as $col): ?>
                                                        <th><?= htmlspecialchars($col) ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rows as $row): ?>
                                                    <tr>
                                                        <?php foreach ($row as $val): ?>
                                                            <td>
                                                                <?php if ($val === null): ?>
                                                                    <em class="text-muted small">null</em>
                                                                <?php elseif (strlen($val) > 120): ?>
                                                                    <span title="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars(substr($val, 0, 120)) ?>...</span>
                                                                <?php else: ?>
                                                                    <?= htmlspecialchars($val) ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="p-3 bg-light border-top text-center text-muted small">
                                        Showing the first 50 results.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Tab 2: Schema Structure -->
                            <div id="schema-content" class="card-body p-4 d-none">
                                <h6 class="fw-700 text-secondary mb-3">Columns Description</h6>
                                <div class="table-responsive border rounded-3 mb-4">
                                    <table class="table table-hover align-middle mb-0" id="schema-structure-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Column Name</th>
                                                <th>Data Type</th>
                                                <th>Nullable</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($columns as $col): ?>
                                                <tr>
                                                    <td class="fw-600 text-dark"><?= htmlspecialchars($col['column_name']) ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary-subtle text-secondary font-weight-500 font-monospace">
                                                            <?= htmlspecialchars($col['data_type']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($col['is_nullable'] === 'YES'): ?>
                                                            <span class="text-success"><i class="bi bi-check-circle-fill me-1"></i> Yes</span>
                                                        <?php else: ?>
                                                            <span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i> No</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <h6 class="fw-700 text-secondary mb-3">PHP Connection snippet to use this table</h6>
                                <div class="code-box position-relative">
                                    <button class="btn btn-sm btn-outline-light position-absolute top-0 end-0 m-2" onclick="copySnippet()" id="copy-btn">
                                        <i class="bi bi-copy"></i> Copy
                                    </button>
                                    <pre class="mb-0" id="php-snippet">
// Querying rows from <?= htmlspecialchars($selected_table) ?> table
$stmt = $pdo->prepare("SELECT * FROM <?= htmlspecialchars($selected_table) ?> LIMIT 10");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo $row['<?= htmlspecialchars($columns[0]['column_name'] ?? 'id') ?>'] . "\n";
}</pre>
                                </div>
                            </div>
                        </div>

                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Scripts -->
    <script>
        function switchTab(showId, hideId, btn) {
            document.getElementById(showId).classList.remove('d-none');
            document.getElementById(hideId).classList.add('d-none');
            
            // Handle buttons classes
            document.querySelectorAll('#tab-nav button').forEach(b => {
                b.classList.remove('active', 'text-primary', 'border-bottom', 'border-primary', 'border-3');
                b.classList.add('text-muted');
            });
            
            btn.classList.add('active', 'text-primary', 'border-bottom', 'border-primary', 'border-3');
            btn.classList.remove('text-muted');
        }

        function copySnippet() {
            var snippet = document.getElementById('php-snippet').innerText;
            navigator.clipboard.writeText(snippet).then(function() {
                var btn = document.getElementById('copy-btn');
                btn.innerHTML = '<i class="bi bi-check-lg"></i> Copied!';
                setTimeout(function() {
                    btn.innerHTML = '<i class="bi bi-copy"></i> Copy';
                }, 2000);
            });
        }
    </script>
</body>
</html>
