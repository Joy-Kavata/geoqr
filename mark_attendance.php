<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

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

$session_query = "SELECT s.session_id, s.unit_id, s.start_time, s.end_time, 
                  u.unit_name, u.unit_code, g.geofence_id, g.name as geofence_name, g.latitude as geofence_latitude, g.longitude as geofence_longitude, g.radius as geofence_radius
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

$enrollment_check = "SELECT * FROM enrollment WHERE user_id = ? AND unit_id = ?";
$stmt = $conn->prepare($enrollment_check);
$stmt->bind_param("ii", $user_id, $session['unit_id']);
$stmt->execute();
$enrollment_result = $stmt->get_result();

if ($enrollment_result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'You are not enrolled in this unit']);
    exit();
}

$check_query = "SELECT * FROM attendance_logs WHERE session_id = ? AND user_id = ?";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $session_id, $user_id);
$stmt->execute();
$check_result = $stmt->get_result();

if ($check_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already checked in for this session']);
    exit();
}

$latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;

if (!empty($session['geofence_id'])) {
    if ($latitude === null || $longitude === null) {
        echo json_encode(['success' => false, 'message' => 'Location access is required. Please enable location services and try again.']);
        exit();
    }

    $earth_radius = 6371000;
    $lat1 = deg2rad($latitude);
    $lat2 = deg2rad($session['geofence_latitude']);
    $delta_lat = deg2rad($session['geofence_latitude'] - $latitude);
    $delta_lon = deg2rad($session['geofence_longitude'] - $longitude);

    $a = sin($delta_lat / 2) * sin($delta_lat / 2) + cos($lat1) * cos($lat2) * sin($delta_lon / 2) * sin($delta_lon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;

    if ($distance > (float)$session['geofence_radius']) {
        $expected_location = !empty($session['geofence_name']) ? $session['geofence_name'] : 'the assigned class venue';
        $unit_name = !empty($session['unit_name']) ? $session['unit_name'] : 'this class';
        echo json_encode([
            'success' => false,
            'message' => 'You are in the wrong venue. Please move to ' . $expected_location . ' for ' . $unit_name . ' and try again.',
            'expected_location' => $expected_location,
            'unit_name' => $unit_name
        ]);
        exit();
    }
}

$insert = $conn->prepare("INSERT INTO attendance_logs (session_id, user_id, status, latitude, longitude) VALUES (?, ?, 'present', ?, ?)");
$insert->bind_param("iidd", $session_id, $user_id, $latitude, $longitude);

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