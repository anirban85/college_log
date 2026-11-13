<?php
// api.php — REST API for College Log Entries, Attendance Rosters & Timings
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';
$pdo = get_db_connection();

// Helper to return JSON
function json_out($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Parse request data (support both JSON body and standard form parameters)
$raw = file_get_contents('php://input');
$body = [];
if (!empty($raw)) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $body = $decoded;
    }
}
$req = array_merge($_GET, $_POST, $body);
$action = $req['action'] ?? '';

// Active user check — strictly require authentication
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    json_out([
        'success' => false,
        'error' => 'You must be signed in to perform this action.',
        'logged_in' => false
    ], 401);
}

// Fetch user profile
$userStmt = $pdo->prepare("SELECT `id`, `username`, `full_name`, `email`, `department` FROM `users` WHERE `id` = :id");
$userStmt->execute([':id' => $userId]);
$currentUser = $userStmt->fetch();

if (!$currentUser) {
    $_SESSION = [];
    session_destroy();
    json_out(['success' => false, 'error' => 'User profile not found. Please sign in again.', 'logged_in' => false], 401);
}

// -------------------------------------------------------------
// 1. GET ALL STATE (TIMINGS, ROSTERS, ACTIVITY LOGS, PROFILE)
// -------------------------------------------------------------
if ($action === 'get_state') {
    // 1. Daily timings for this specific user
    $timingStmt = $pdo->prepare("SELECT `log_date`, `arrival_time`, `departure_time` FROM `daily_timings` WHERE `user_id` = :uid ORDER BY `log_date` DESC");
    $timingStmt->execute([':uid' => $userId]);
    $dailyTimings = [];
    while ($row = $timingStmt->fetch()) {
        $dailyTimings[$row['log_date']] = [
            'arrival' => $row['arrival_time'],
            'departure' => $row['departure_time']
        ];
    }

    // 2. Rosters for this specific user
    $rosterStmt = $pdo->prepare("SELECT `semester`, `roll_no`, `student_name` FROM `semester_rosters` WHERE `user_id` = :uid ORDER BY `semester` ASC, `id` ASC");
    $rosterStmt->execute([':uid' => $userId]);
    $semesterRosters = [
        "Sem 1" => [], "Sem 2" => [], "Sem 3" => [], "Sem 4" => [],
        "Sem 5" => [], "Sem 6" => [], "Sem 7" => [], "Sem 8" => []
    ];
    while ($row = $rosterStmt->fetch()) {
        $sem = $row['semester'];
        if (!isset($semesterRosters[$sem])) {
            $semesterRosters[$sem] = [];
        }
        $semesterRosters[$sem][] = [
            'roll' => $row['roll_no'],
            'name' => $row['student_name']
        ];
    }

    // 3. Activity Logs for this specific user
    $logStmt = $pdo->prepare("
        SELECT `id`, `log_code`, `log_date`, `arrival_time`, `departure_time`,
               `activity_type`, `semester`, `slot_start`, `slot_end`, `subject_or_exam`,
               `room_or_hall`, `topic_or_duty`, `remarks`, `student_count`,
               `present_count`, `absent_count`, `attendance_json`, `created_at`
        FROM `activity_logs`
        WHERE `user_id` = :uid
        ORDER BY `log_date` DESC, `slot_start` DESC, `id` DESC
    ");
    $logStmt->execute([':uid' => $userId]);
    $activityLogs = [];

    while ($row = $logStmt->fetch()) {
        $attList = [];
        if (!empty($row['attendance_json'])) {
            $decoded = json_decode($row['attendance_json'], true);
            if (is_array($decoded)) {
                $attList = $decoded;
            }
        }

        $activityLogs[] = [
            'id' => $row['log_code'],
            'db_id' => (int)$row['id'],
            'date' => $row['log_date'],
            'arrivalTime' => $row['arrival_time'],
            'departureTime' => $row['departure_time'],
            'activityType' => $row['activity_type'],
            'semester' => $row['semester'],
            'slotStart' => $row['slot_start'],
            'slotEnd' => $row['slot_end'],
            'subjectOrExam' => $row['subject_or_exam'],
            'roomOrHall' => $row['room_or_hall'],
            'topicOrRole' => $row['topic_or_duty'] ?? '',
            'remarks' => $row['remarks'] ?? '',
            'totalStudents' => (int)$row['student_count'],
            'presentCount' => (int)$row['present_count'],
            'absentCount' => (int)$row['absent_count'],
            'attendanceList' => $attList,
            'createdAt' => $row['created_at']
        ];
    }

    json_out([
        'success' => true,
        'user' => $currentUser,
        'dailyTimings' => $dailyTimings,
        'semesterRosters' => $semesterRosters,
        'activityLogs' => $activityLogs
    ]);
}

// -------------------------------------------------------------
// 2. SAVE DAILY TIMING (ARRIVAL & DEPARTURE)
// -------------------------------------------------------------
if ($action === 'save_daily_timing') {
    $logDate = trim($req['log_date'] ?? '');
    $arrivalTime = trim($req['arrival_time'] ?? '09:15');
    $departureTime = trim($req['departure_time'] ?? '16:45');

    if (empty($logDate)) {
        json_out(['success' => false, 'error' => 'Log date is required.'], 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO `daily_timings` (`user_id`, `log_date`, `arrival_time`, `departure_time`)
        VALUES (:uid, :log_date, :arr, :dep)
        ON DUPLICATE KEY UPDATE `arrival_time` = VALUES(`arrival_time`), `departure_time` = VALUES(`departure_time`)
    ");
    $stmt->execute([
        ':uid' => $userId,
        ':log_date' => $logDate,
        ':arr' => $arrivalTime,
        ':dep' => $departureTime
    ]);

    // Also update all existing logs on that date with the new arrival/departure times
    $updLogs = $pdo->prepare("
        UPDATE `activity_logs`
        SET `arrival_time` = :arr, `departure_time` = :dep
        WHERE `user_id` = :uid AND `log_date` = :log_date
    ");
    $updLogs->execute([
        ':arr' => $arrivalTime,
        ':dep' => $departureTime,
        ':uid' => $userId,
        ':log_date' => $logDate
    ]);

    json_out([
        'success' => true,
        'message' => 'Daily campus arrival & departure timing updated in MySQL.',
        'timing' => [
            'date' => $logDate,
            'arrival' => $arrivalTime,
            'departure' => $departureTime
        ]
    ]);
}

// -------------------------------------------------------------
// 3. SAVE OR UPDATE ACTIVITY LOG (CLASS / EXAM / TUTORIAL)
// -------------------------------------------------------------
if ($action === 'save_log') {
    $logDate = trim($req['log_date'] ?? '');
    $arrivalTime = trim($req['arrival_time'] ?? '09:15');
    $departureTime = trim($req['departure_time'] ?? '16:45');
    $activityType = trim($req['activity_type'] ?? 'Lecture Class');
    $semester = trim($req['semester'] ?? 'Sem 3');
    $slotStart = trim($req['slot_start'] ?? '');
    $slotEnd = trim($req['slot_end'] ?? '');
    $subjectOrExam = trim($req['subject_or_exam'] ?? '');
    $roomOrHall = trim($req['room_or_hall'] ?? '');
    $topicOrDuty = trim($req['topic_or_duty'] ?? '');
    $remarks = trim($req['remarks'] ?? '');
    $studentCount = (int)($req['student_count'] ?? 0);
    $presentCount = (int)($req['present_count'] ?? 0);
    $absentCount = (int)($req['absent_count'] ?? 0);
    $attendanceList = $req['attendance_list'] ?? [];
    $logCode = trim($req['log_code'] ?? '');

    if (empty($logDate) || empty($slotStart) || empty($slotEnd) || empty($subjectOrExam)) {
        json_out(['success' => false, 'error' => 'Please fill in all required fields (Date, Slot, Subject/Course).'], 400);
    }

    if (empty($logCode)) {
        $logCode = 'LOG-' . rand(10000, 99999);
    }

    // 1. Ensure daily timing is recorded
    $insTiming = $pdo->prepare("
        INSERT INTO `daily_timings` (`user_id`, `log_date`, `arrival_time`, `departure_time`)
        VALUES (:uid, :log_date, :arr, :dep)
        ON DUPLICATE KEY UPDATE `arrival_time` = VALUES(`arrival_time`), `departure_time` = VALUES(`departure_time`)
    ");
    $insTiming->execute([
        ':uid' => $userId,
        ':log_date' => $logDate,
        ':arr' => $arrivalTime,
        ':dep' => $departureTime
    ]);

    // 2. Check if updating existing log or creating new
    $existingStmt = $pdo->prepare("SELECT `id` FROM `activity_logs` WHERE `user_id` = :uid AND `log_code` = :code");
    $existingStmt->execute([':uid' => $userId, ':code' => $logCode]);
    $existingLog = $existingStmt->fetch();

    $attJson = json_encode($attendanceList, JSON_UNESCAPED_UNICODE);

    if ($existingLog) {
        // UPDATE EXISTING LOG
        $upd = $pdo->prepare("
            UPDATE `activity_logs` SET
                `log_date` = :log_date,
                `arrival_time` = :arrival_time,
                `departure_time` = :departure_time,
                `activity_type` = :activity_type,
                `semester` = :semester,
                `slot_start` = :slot_start,
                `slot_end` = :slot_end,
                `subject_or_exam` = :subject_or_exam,
                `room_or_hall` = :room_or_hall,
                `topic_or_duty` = :topic_or_duty,
                `remarks` = :remarks,
                `student_count` = :student_count,
                `present_count` = :present_count,
                `absent_count` = :absent_count,
                `attendance_json` = :attendance_json
            WHERE `id` = :id AND `user_id` = :user_id
        ");
        $upd->execute([
            ':log_date' => $logDate,
            ':arrival_time' => $arrivalTime,
            ':departure_time' => $departureTime,
            ':activity_type' => $activityType,
            ':semester' => $semester,
            ':slot_start' => $slotStart,
            ':slot_end' => $slotEnd,
            ':subject_or_exam' => $subjectOrExam,
            ':room_or_hall' => $roomOrHall,
            ':topic_or_duty' => $topicOrDuty,
            ':remarks' => $remarks,
            ':student_count' => $studentCount,
            ':present_count' => $presentCount,
            ':absent_count' => $absentCount,
            ':attendance_json' => $attJson,
            ':id' => $existingLog['id'],
            ':user_id' => $userId
        ]);
        $insertedDbId = (int)$existingLog['id'];
        $message = "Log entry {$logCode} updated successfully in MySQL.";
    } else {
        // INSERT NEW LOG
        $stmt = $pdo->prepare("
            INSERT INTO `activity_logs` (
                `log_code`, `user_id`, `log_date`, `arrival_time`, `departure_time`,
                `activity_type`, `semester`, `slot_start`, `slot_end`, `subject_or_exam`,
                `room_or_hall`, `topic_or_duty`, `remarks`, `student_count`, `present_count`,
                `absent_count`, `attendance_json`
            ) VALUES (
                :log_code, :user_id, :log_date, :arrival_time, :departure_time,
                :activity_type, :semester, :slot_start, :slot_end, :subject_or_exam,
                :room_or_hall, :topic_or_duty, :remarks, :student_count, :present_count,
                :absent_count, :attendance_json
            )
        ");

        $stmt->execute([
            ':log_code' => $logCode,
            ':user_id' => $userId,
            ':log_date' => $logDate,
            ':arrival_time' => $arrivalTime,
            ':departure_time' => $departureTime,
            ':activity_type' => $activityType,
            ':semester' => $semester,
            ':slot_start' => $slotStart,
            ':slot_end' => $slotEnd,
            ':subject_or_exam' => $subjectOrExam,
            ':room_or_hall' => $roomOrHall,
            ':topic_or_duty' => $topicOrDuty,
            ':remarks' => $remarks,
            ':student_count' => $studentCount,
            ':present_count' => $presentCount,
            ':absent_count' => $absentCount,
            ':attendance_json' => $attJson
        ]);
        $insertedDbId = (int)$pdo->lastInsertId();
        $message = "Log entry {$logCode} saved successfully to MySQL database.";
    }

    $savedLog = [
        'id' => $logCode,
        'db_id' => $insertedDbId,
        'date' => $logDate,
        'arrivalTime' => $arrivalTime,
        'departureTime' => $departureTime,
        'activityType' => $activityType,
        'semester' => $semester,
        'slotStart' => $slotStart,
        'slotEnd' => $slotEnd,
        'subjectOrExam' => $subjectOrExam,
        'roomOrHall' => $roomOrHall,
        'topicOrRole' => $topicOrDuty,
        'remarks' => $remarks,
        'totalStudents' => $studentCount,
        'presentCount' => $presentCount,
        'absentCount' => $absentCount,
        'attendanceList' => $attendanceList,
        'createdAt' => date('Y-m-d H:i:s')
    ];

    json_out([
        'success' => true,
        'message' => $message,
        'log' => $savedLog
    ]);
}

// -------------------------------------------------------------
// 4. DELETE ACTIVITY LOG (REQUIRES USER PASSWORD)
// -------------------------------------------------------------
if ($action === 'delete_log') {
    $logCode = trim($req['log_id'] ?? $req['log_code'] ?? '');
    $password = $req['password'] ?? '';

    if (empty($logCode)) {
        json_out(['success' => false, 'error' => 'Log identifier is required.'], 400);
    }

    if (empty($password)) {
        json_out(['success' => false, 'error' => 'Please enter your password to confirm deletion.'], 400);
    }

    // Security check: verify password against current user's hash
    $userStmt = $pdo->prepare("SELECT `password_hash` FROM `users` WHERE `id` = :uid");
    $userStmt->execute([':uid' => $userId]);
    $uRow = $userStmt->fetch();

    if (!$uRow || !password_verify($password, $uRow['password_hash'])) {
        json_out(['success' => false, 'error' => 'Incorrect password entered. Deletion was cancelled for security.'], 403);
    }

    // Execute deletion strictly restricted to the logged in user
    $stmt = $pdo->prepare("DELETE FROM `activity_logs` WHERE `user_id` = :uid AND (`log_code` = :code OR `id` = :id)");
    $stmt->execute([
        ':uid' => $userId,
        ':code' => $logCode,
        ':id' => is_numeric($logCode) ? (int)$logCode : -1
    ]);

    json_out(['success' => true, 'message' => "Log entry {$logCode} has been permanently deleted from MySQL."]);
}

// -------------------------------------------------------------
// 5. ADD STUDENT TO SEMESTER ROSTER
// -------------------------------------------------------------
if ($action === 'add_roster_student') {
    $semester = trim($req['semester'] ?? '');
    $rollNo = trim($req['roll_no'] ?? '');
    $name = trim($req['student_name'] ?? '');

    if (empty($semester) || empty($rollNo) || empty($name)) {
        json_out(['success' => false, 'error' => 'Semester, Roll Number and Student Name are required.'], 400);
    }

    // Check duplicate roll number in same semester
    $checkStmt = $pdo->prepare("SELECT `id` FROM `semester_rosters` WHERE `user_id` = :uid AND `semester` = :sem AND LOWER(TRIM(`roll_no`)) = LOWER(:roll)");
    $checkStmt->execute([
        ':uid' => $userId,
        ':sem' => $semester,
        ':roll' => $rollNo
    ]);
    if ($checkStmt->fetch()) {
        json_out(['success' => false, 'error' => "Student with Roll Number '{$rollNo}' already exists in {$semester}."], 409);
    }

    $ins = $pdo->prepare("
        INSERT INTO `semester_rosters` (`user_id`, `semester`, `roll_no`, `student_name`)
        VALUES (:uid, :sem, :roll, :name)
    ");
    $ins->execute([
        ':uid' => $userId,
        ':sem' => $semester,
        ':roll' => $rollNo,
        ':name' => $name
    ]);

    json_out([
        'success' => true,
        'message' => "Student {$name} ({$rollNo}) added to {$semester} roster.",
        'student' => ['roll' => $rollNo, 'name' => $name]
    ]);
}

// -------------------------------------------------------------
// 6. EDIT STUDENT IN SEMESTER ROSTER
// -------------------------------------------------------------
if ($action === 'edit_roster_student') {
    $semester = trim($req['semester'] ?? '');
    $oldRoll = trim($req['old_roll_no'] ?? '');
    $newRoll = trim($req['new_roll_no'] ?? '');
    $newName = trim($req['new_student_name'] ?? '');

    if (empty($semester) || empty($oldRoll) || empty($newRoll) || empty($newName)) {
        json_out(['success' => false, 'error' => 'Semester, current Roll Number, new Roll Number and Name are required.'], 400);
    }

    // If changing roll number, check if the new roll number is already used in this semester
    if (strtolower($oldRoll) !== strtolower($newRoll)) {
        $checkStmt = $pdo->prepare("SELECT `id` FROM `semester_rosters` WHERE `user_id` = :uid AND `semester` = :sem AND LOWER(TRIM(`roll_no`)) = LOWER(:roll)");
        $checkStmt->execute([
            ':uid' => $userId,
            ':sem' => $semester,
            ':roll' => $newRoll
        ]);
        if ($checkStmt->fetch()) {
            json_out(['success' => false, 'error' => "Another student with Roll Number '{$newRoll}' already exists in {$semester}."], 409);
        }
    }

    $upd = $pdo->prepare("
        UPDATE `semester_rosters`
        SET `roll_no` = :new_roll, `student_name` = :new_name
        WHERE `user_id` = :uid AND `semester` = :sem AND LOWER(TRIM(`roll_no`)) = LOWER(:old_roll)
    ");
    $upd->execute([
        ':new_roll' => $newRoll,
        ':new_name' => $newName,
        ':uid' => $userId,
        ':sem' => $semester,
        ':old_roll' => $oldRoll
    ]);

    json_out([
        'success' => true,
        'message' => "Student details updated to {$newName} ({$newRoll}).",
        'student' => ['roll' => $newRoll, 'name' => $newName]
    ]);
}

// -------------------------------------------------------------
// 7. REMOVE STUDENT FROM SEMESTER ROSTER (REQUIRES USER PASSWORD)
// -------------------------------------------------------------
if ($action === 'remove_roster_student') {
    $semester = trim($req['semester'] ?? '');
    $rollNo = trim($req['roll_no'] ?? '');
    $password = $req['password'] ?? '';

    if (empty($semester) || empty($rollNo)) {
        json_out(['success' => false, 'error' => 'Semester and Roll Number are required.'], 400);
    }

    if (empty($password)) {
        json_out(['success' => false, 'error' => 'Please enter your password to confirm student removal.'], 400);
    }

    // Security check: verify password against current user's hash
    $userStmt = $pdo->prepare("SELECT `password_hash` FROM `users` WHERE `id` = :uid");
    $userStmt->execute([':uid' => $userId]);
    $uRow = $userStmt->fetch();

    if (!$uRow || !password_verify($password, $uRow['password_hash'])) {
        json_out(['success' => false, 'error' => 'Incorrect password entered. Removal was cancelled for security.'], 403);
    }

    $stmt = $pdo->prepare("DELETE FROM `semester_rosters` WHERE `user_id` = :uid AND `semester` = :sem AND LOWER(TRIM(`roll_no`)) = LOWER(:roll)");
    $stmt->execute([
        ':uid' => $userId,
        ':sem' => $semester,
        ':roll' => $rollNo
    ]);

    json_out(['success' => true, 'message' => "Student {$rollNo} removed from {$semester} roster."]);
}

// Fallback
json_out(['success' => false, 'error' => 'Invalid or unknown API action.'], 400);
