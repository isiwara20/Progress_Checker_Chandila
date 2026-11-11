<?php
/**
 * Chandila Web Development – Database Connection Helper
 * Uses PHP Data Objects (PDO) with Prepared Statements
 */

if (!defined('CHANDILA_DATABASE_LOADED')) {
    define('CHANDILA_DATABASE_LOADED', true);

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $schemaChecked = false;

    public static function getConnection(): ?PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                // If the database does not exist, let's attempt to auto-create or provide clean error
                if ($e->getCode() == 1049) { // Database unknown
                    try {
                        $tempPdo = new PDO("mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, $options);
                        $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                        
                        // Import database.sql if available
                        $sqlFile = ROOT_PATH . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'database.sql';
                        if (file_exists($sqlFile)) {
                            $sql = file_get_contents($sqlFile);
                            $tempPdo->exec("USE `" . DB_NAME . "`;");
                            $tempPdo->exec($sql);
                        }
                        
                        self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                        self::ensureSchemaCompatibility(self::$instance);
                        return self::$instance;
                    } catch (Exception $autoEx) {
                        self::renderDbError($autoEx->getMessage());
                    }
                } else {
                    self::renderDbError($e->getMessage());
                }
            }
        }

        if (self::$instance !== null && !self::$schemaChecked) {
            self::$schemaChecked = true;
            self::ensureSchemaCompatibility(self::$instance);
        }

        return self::$instance;
    }

    /**
     * Self-healing runtime schema compatibility checks
     */
    private static function ensureSchemaCompatibility(PDO $pdo): void {
        try {
            // 1. Ensure projects.status supports all stages including 'Second Payment Pending'
            $pdo->exec("ALTER TABLE projects MODIFY status VARCHAR(100) NOT NULL DEFAULT 'Request Submitted'");
        } catch (Exception $e) {
            // Ignore if already compatible or permissions restricted
        }

        try {
            // 2. Ensure users.role supports 'customer_care'
            $pdo->exec("ALTER TABLE users MODIFY role VARCHAR(50) NOT NULL DEFAULT 'developer'");
        } catch (Exception $e) {
            // Ignore if already compatible or permissions restricted
        }

        try {
            // 3. Ensure a default Customer Care agent account exists
            $checkStmt = $pdo->query("SELECT id FROM users WHERE role = 'customer_care' LIMIT 1");
            if (!$checkStmt->fetch()) {
                $carePass = password_hash('Care@12345', PASSWORD_BCRYPT);
                $insCare = $pdo->prepare("
                    INSERT INTO users (username, email, password_hash, full_name, phone, role, skills, status, created_at)
                    VALUES ('care', 'care@chandilaweb.com', ?, 'Customer Care Desk', '+94 76 843 3340', 'customer_care', 'Customer Relations & Order Intake', 'active', NOW())
                ");
                $insCare->execute([$carePass]);
            }
        } catch (Exception $e) {
            // Ignore if already inserted or restricted
        }

        try {
            // 4. Ensure care_working_hours table exists
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `care_working_hours` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `user_id` INT(11) NOT NULL,
                    `work_date` DATE NOT NULL,
                    `shift_type` VARCHAR(100) NOT NULL DEFAULT 'Standard Full Day',
                    `start_time` DATETIME NOT NULL,
                    `end_time` DATETIME DEFAULT NULL,
                    `total_hours` DECIMAL(5,2) DEFAULT NULL,
                    `status` ENUM('In Progress', 'Completed', 'Approved') NOT NULL DEFAULT 'In Progress',
                    `tasks_summary` TEXT DEFAULT NULL,
                    `notes` TEXT DEFAULT NULL,
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    INDEX `idx_care_user` (`user_id`),
                    INDEX `idx_care_date` (`work_date`),
                    INDEX `idx_care_status` (`status`),
                    CONSTRAINT `fk_care_hours_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Exception $e) {
            // Fallback without foreign key in case user table is MyISAM or constrained
            try {
                $pdo->exec("
                    CREATE TABLE IF NOT EXISTS `care_working_hours` (
                        `id` INT(11) NOT NULL AUTO_INCREMENT,
                        `user_id` INT(11) NOT NULL,
                        `work_date` DATE NOT NULL,
                        `shift_type` VARCHAR(100) NOT NULL DEFAULT 'Standard Full Day',
                        `start_time` DATETIME NOT NULL,
                        `end_time` DATETIME DEFAULT NULL,
                        `total_hours` DECIMAL(5,2) DEFAULT NULL,
                        `status` ENUM('In Progress', 'Completed', 'Approved') NOT NULL DEFAULT 'In Progress',
                        `tasks_summary` TEXT DEFAULT NULL,
                        `notes` TEXT DEFAULT NULL,
                        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        PRIMARY KEY (`id`),
                        INDEX `idx_care_user` (`user_id`),
                        INDEX `idx_care_date` (`work_date`),
                        INDEX `idx_care_status` (`status`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ");
            } catch (Exception $e2) {}
        }
    }

    private static function renderDbError(string $message): void {
        http_response_code(500);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Database Connection Error - Chandila Web Development</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { background-color: #f8fafc; color: #0f172a; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; align-items: center; min-height: 100vh; }
                .error-card { background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; box-shadow: 0 10px 30px rgba(15,23,42,0.06); }
                .code-box { background: #f1f5f9; border-radius: 8px; padding: 12px; font-family: monospace; color: #2563eb; border: 1px solid #cbd5e1; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="error-card p-4 p-md-5">
                            <div class="d-flex align-items-center mb-4">
                                <span class="fs-1 text-warning me-3">⚠️</span>
                                <div>
                                    <h3 class="mb-1 text-dark fw-bold">Database Connection Required</h3>
                                    <p class="text-muted mb-0">Chandila Web Development Management System</p>
                                </div>
                            </div>
                            <div class="alert alert-danger border-danger text-danger bg-danger bg-opacity-10 mb-4">
                                <strong>Error:</strong> <?php echo htmlspecialchars($message); ?>
                            </div>
                            <h5 class="text-primary fw-bold mb-3">Quick Setup Instructions:</h5>
                            <ol class="text-dark mb-4">
                                <li class="mb-2">Open <strong>XAMPP Control Panel</strong> and verify MySQL is running.</li>
                                <li class="mb-2">Open <a href="http://localhost/phpmyadmin" target="_blank" class="text-primary fw-bold">phpMyAdmin</a> in your browser.</li>
                                <li class="mb-2">Click <strong>Import</strong> tab.</li>
                                <li class="mb-2">Choose the SQL file located at:
                                    <div class="code-box mt-2 mb-2">database/database.sql</div>
                                </li>
                                <li class="mb-2">Click <strong>Import</strong> at the bottom. The database <code>chandila_pms</code> and all sample data will be created instantly.</li>
                            </ol>
                            <div class="text-center">
                                <a href="javascript:location.reload();" class="btn btn-primary px-4 py-2">
                                    🔄 Retry Connection
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

if (!function_exists('getDB')) {
    function getDB(): ?PDO {
        return Database::getConnection();
    }
}
}


