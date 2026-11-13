<?php
// db.php — Database Connection & Automatic Schema Migration for XAMPP

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration (Standard XAMPP default settings)
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_log_db');

/**
 * Returns a PDO connection to the database.
 * If the database or tables do not exist yet, they are automatically created!
 */
function get_db_connection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // Step 1: Connect to MySQL server without database first to ensure DB exists
        $dsnServer = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
        $serverPdo = new PDO($dsnServer, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");

        // Step 2: Connect to the specific database
        $dsnDb = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsnDb, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Step 3: Ensure tables exist
        initialize_database_tables($pdo);

        return $pdo;
    } catch (PDOException $e) {
        if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed: ' . $e->getMessage()
            ]);
            exit;
        }
        die("<h3>Database Connection Error</h3><p>" . htmlspecialchars($e->getMessage()) . "</p><p>Please ensure MySQL is running in your XAMPP Control Panel.</p>");
    }
}

/**
 * Automatically creates all tables if not present.
 */
function initialize_database_tables($pdo) {
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $schemaFile = __DIR__ . '/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        }
    }
}

/**
 * Default student rosters for Sem 1 through Sem 8 (used when new faculty registers)
 */
function get_system_default_rosters() {
    return [
        "Sem 1" => [
            ["roll" => "PHS/26/101", "name" => "Aarav Mukherjee"],
            ["roll" => "PHS/26/102", "name" => "Aditi Roy"],
            ["roll" => "PHS/26/103", "name" => "Aniket Banerjee"],
            ["roll" => "PHS/26/104", "name" => "Debolina Ghosh"],
            ["roll" => "PHS/26/105", "name" => "Ishaan Chakraborty"],
            ["roll" => "PHS/26/106", "name" => "Kritika Sen"],
            ["roll" => "PHS/26/107", "name" => "Mayank Agarwal"],
            ["roll" => "PHS/26/108", "name" => "Nandini Das"],
            ["roll" => "PHS/26/109", "name" => "Pratik Mondal"],
            ["roll" => "PHS/26/110", "name" => "Riya Saha"],
            ["roll" => "PHS/26/111", "name" => "Sagnik Bose"],
            ["roll" => "PHS/26/112", "name" => "Tanvi Dutta"]
        ],
        "Sem 2" => [
            ["roll" => "PHS/25/201", "name" => "Abhishek Verma"],
            ["roll" => "PHS/25/202", "name" => "Ananya Chatterjee"],
            ["roll" => "PHS/25/203", "name" => "Arjun Sengupta"],
            ["roll" => "PHS/25/204", "name" => "Bishal Karmakar"],
            ["roll" => "PHS/25/205", "name" => "Diya Majumdar"],
            ["roll" => "PHS/25/206", "name" => "Gaurav Sharma"],
            ["roll" => "PHS/25/207", "name" => "Koyel Mitra"],
            ["roll" => "PHS/25/208", "name" => "Nikhil Paul"],
            ["roll" => "PHS/25/209", "name" => "Poulomi Sarkar"],
            ["roll" => "PHS/25/210", "name" => "Rohan Guha"]
        ],
        "Sem 3" => [
            ["roll" => "PHS/25/301", "name" => "Aakash Bhattacharya"],
            ["roll" => "PHS/25/302", "name" => "Ankita Ganguly"],
            ["roll" => "PHS/25/303", "name" => "Arnab Goswami"],
            ["roll" => "PHS/25/304", "name" => "Bipasha Halder"],
            ["roll" => "PHS/25/305", "name" => "Chirantan Dey"],
            ["roll" => "PHS/25/306", "name" => "Debjit Kundu"],
            ["roll" => "PHS/25/307", "name" => "Gargi Lahiri"],
            ["roll" => "PHS/25/308", "name" => "Himanshu Shaw"],
            ["roll" => "PHS/25/309", "name" => "Juhi Biswas"],
            ["roll" => "PHS/25/310", "name" => "Kaustav Nandi"],
            ["roll" => "PHS/25/311", "name" => "Madhurima Roy"],
            ["roll" => "PHS/25/312", "name" => "Neeladri Sinha"],
            ["roll" => "PHS/25/313", "name" => "Payel Pal"],
            ["roll" => "PHS/25/314", "name" => "Ritwick Chowdhury"]
        ],
        "Sem 4" => [
            ["roll" => "PHS/24/401", "name" => "Aditya Narayan"],
            ["roll" => "PHS/24/402", "name" => "Amrita Samanta"],
            ["roll" => "PHS/24/403", "name" => "Avijit Bera"],
            ["roll" => "PHS/24/404", "name" => "Bidisha Kar"],
            ["roll" => "PHS/24/405", "name" => "Deepayan Jana"],
            ["roll" => "PHS/24/406", "name" => "Indrani Hazra"],
            ["roll" => "PHS/24/407", "name" => "Kunal Singh"],
            ["roll" => "PHS/24/408", "name" => "Moumita Maity"],
            ["roll" => "PHS/24/409", "name" => "Partha Sarathi"],
            ["roll" => "PHS/24/410", "name" => "Shreya Ghoshal"]
        ],
        "Sem 5" => [
            ["roll" => "PHS/24/501", "name" => "Aritra Bhowmik"],
            ["roll" => "PHS/24/502", "name" => "Bipasha Basu"],
            ["roll" => "PHS/24/503", "name" => "Devjyoti Mallick"],
            ["roll" => "PHS/24/504", "name" => "Esha Gupta"],
            ["roll" => "PHS/24/505", "name" => "Mainak Bagchi"],
            ["roll" => "PHS/24/506", "name" => "Pallabi Patra"],
            ["roll" => "PHS/24/507", "name" => "Rajdeep Sikdar"],
            ["roll" => "PHS/24/508", "name" => "Sohini Moitra"],
            ["roll" => "PHS/24/509", "name" => "Sourav Dasgupta"],
            ["roll" => "PHS/24/510", "name" => "Subhamoy Panja"],
            ["roll" => "PHS/24/511", "name" => "Trisha Konar"],
            ["roll" => "PHS/24/512", "name" => "Vikramaditya Ray"]
        ],
        "Sem 6" => [
            ["roll" => "PHS/23/601", "name" => "Aniruddha Roy"],
            ["roll" => "PHS/23/602", "name" => "Barnali Mandal"],
            ["roll" => "PHS/23/603", "name" => "Joydeep Dutta"],
            ["roll" => "PHS/23/604", "name" => "Monami Ghosh"],
            ["roll" => "PHS/23/605", "name" => "Prasenjit Kumar"],
            ["roll" => "PHS/23/606", "name" => "Rimpa Chakraborty"],
            ["roll" => "PHS/23/607", "name" => "Sabyasachi Sen"],
            ["roll" => "PHS/23/608", "name" => "Tania Parveen"]
        ],
        "Sem 7" => [
            ["roll" => "PHS/23/701", "name" => "Arkajyoti Saha"],
            ["roll" => "PHS/23/702", "name" => "Chandreyee Paul"],
            ["roll" => "PHS/23/703", "name" => "Dipankar Roy"],
            ["roll" => "PHS/23/704", "name" => "Lopamudra Mitra"],
            ["roll" => "PHS/23/705", "name" => "Nirmalya Seal"],
            ["roll" => "PHS/23/706", "name" => "Priyanka Sardar"],
            ["roll" => "PHS/23/707", "name" => "Rupam Islam"],
            ["roll" => "PHS/23/708", "name" => "Sayani Gupta"],
            ["roll" => "PHS/23/709", "name" => "Suman Kalyan"],
            ["roll" => "PHS/23/710", "name" => "Ushasi Ray"]
        ],
        "Sem 8" => [
            ["roll" => "PHS/22/801", "name" => "Abir Chatterjee"],
            ["roll" => "PHS/22/802", "name" => "Debleena Sen"],
            ["roll" => "PHS/22/803", "name" => "Parambrata Kar"],
            ["roll" => "PHS/22/804", "name" => "Paoli Dam"],
            ["roll" => "PHS/22/805", "name" => "Raima Sen"],
            ["roll" => "PHS/22/806", "name" => "Riddhi Sen"],
            ["roll" => "PHS/22/807", "name" => "Soham Majumdar"],
            ["roll" => "PHS/22/808", "name" => "Swastika Mukherjee"]
        ]
    ];
}

/**
 * Initializes semester student rosters for a new user
 */
function seed_user_rosters($pdo, $userId) {
    $rosters = get_system_default_rosters();
    $insert = $pdo->prepare("
        INSERT INTO `semester_rosters` (`user_id`, `semester`, `roll_no`, `student_name`)
        VALUES (:user_id, :semester, :roll_no, :student_name)
    ");
    foreach ($rosters as $sem => $students) {
        foreach ($students as $s) {
            $insert->execute([
                ':user_id' => $userId,
                ':semester' => $sem,
                ':roll_no' => $s['roll'],
                ':student_name' => $s['name']
            ]);
        }
    }
}
