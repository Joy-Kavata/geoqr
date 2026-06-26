<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Check if user is logged in and is student
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'message' => 'Please login as student']);
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = isset($_POST['session_id']) ? $_POST['session_id'] : 0;

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'No session selected']);
    exit();
}

// Get session details
$session_query = "SELECT s.session_id, s.unit_id, s.start_time, s.end_time, 
                  u.unit_name, u.unit_code, g.name as geofence_name
                  FROM attendance_sessions s
                  JOIN units u ON s.unit_id = u.unit_id
                  LEFT JOIN geofences g ON s.geofence_id = g.geofence_id
                  WHERE s.session_id = ?";
$stmt = $conn->prepare($session_query);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session_result = $stmt->get_result();
$session = $session_result->fetch_assoc();

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Session not found']);
    exit();
}

// Check if session is active
$now = time();
$start = strtotime($session['start_time']);
$end = strtotime($session['end_time']);

if ($now < $start) {
    echo json_encode(['success' => false, 'message' => 'Session has not started yet']);
    exit();
}

if ($now > $end) {
    echo json_encode(['success' => false, 'message' => 'Session has already ended']);
    exit();
}

// Check if student is enrolled in this unit
$enrollment_check = "SELECT * FROM enrollment WHERE user_id = ? AND unit_id = ?";
$stmt = $conn->prepare($enrollment_check);
$stmt->bind_param("ii", $user_id, $session['unit_id']);
$stmt->execute();
$enrollment_result = $stmt->get_result();

if ($enrollment_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'You are not enrolled in this unit']);
    exit();
}

// Check if already checked in
$check_query = "SELECT * FROM attendance_logs WHERE session_id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $session_id, $user_id);
$stmt->execute();
$check_result = $stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already checked in for this session']);
    exit();
}

// Mark attendance
$insert = $conn->prepare("INSERT INTO attendance_logs (session_id, user_id, status) VALUES (?, ?, 'present')");
$insert->bind_param("ii", $session_id, $user_id);

if ($insert->execute()) {
    echo json_encode([
        'success' => true,
        'unit_name' => $session['unit_name'],
        'location' => $session['geofence_name'] ?? 'No location set'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark attendance']);
}

$stmt->close();
$conn->close();
?>