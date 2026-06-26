<?php
// ===== SET TIMEZONE =====
date_default_timezone_set('Africa/Nairobi');

session_start();
include 'config.php';

// Check if user is lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login_form.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$unit_id = isset($_GET['unit_id']) ? $_GET['unit_id'] : 0;

// Get the unit name
$unit_query = "SELECT unit_name FROM units WHERE user_id = ? AND unit_id = ?";
$stmt = $conn->prepare($unit_query);
$stmt->bind_param("ii", $lecturer_id, $unit_id);
$stmt->execute();
$unit_result = $stmt->get_result();

if ($unit_result->num_rows == 0) {
    die("Invalid unit or you don't have permission.");
}
$unit = $unit_result->fetch_assoc();
$unit_name = $unit['unit_name'];

// Get geofences for this lecturer's units
$geofences_query = "SELECT g.geofence_id, g.name, u.unit_name 
                    FROM geofences g 
                    JOIN units u ON g.unit_id = u.unit_id 
                    WHERE u.user_id = ?";
$stmt = $conn->prepare($geofences_query);
$stmt->bind_param("i", $lecturer_id);
$stmt->execute();
$geofences = $stmt->get_result();

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $unit_id = $_POST['unit_id'];
    $geofence_id = $_POST['geofence_id'] ?? null;
    $start_time = $_POST['start_time'];
    $duration = $_POST['duration'];
    
    // Calculate end time using PHP (NOT MySQL)
    $end_time = date('Y-m-d H:i:s', strtotime($start_time . ' + ' . $duration . ' minutes'));
    
    // Debug - check the times
    error_log("Start time: " . $start_time);
    error_log("End time: " . $end_time);
    error_log("Current PHP time: " . date('Y-m-d H:i:s'));
    
    $stmt = $conn->prepare("INSERT INTO attendance_sessions (unit_id, geofence_id, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissi", $unit_id, $geofence_id, $start_time, $end_time, $lecturer_id);
    
    if ($stmt->execute()) {
        // ============================================================
        // Get the newly created session ID and redirect to QR code page
        // ============================================================
        $session_id = $stmt->insert_id;
        $_SESSION['message'] = "Attendance session created successfully!";
        $_SESSION['message_type'] = "success";
        
        // Redirect to QR code page
        header("Location: generate_qr.php?session_id=" . $session_id);
        exit();
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
        $_SESSION['message_type'] = "error";
        header("Location: create_session.php?unit_id=" . $unit_id);
        exit();
    }
    $stmt->close();
}

// Get messages
$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Attendance Session - GeoQR | KCA University</title>
    <style>
        /* ===== KCA UNIVERSITY THEME ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            padding: 20px; 
        }
        
        .container { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #FFFFFF; 
            padding: 35px 40px; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
            border-top: 6px solid #C9A84C;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* ===== HEADER ===== */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }
        
        .header h1 { 
            color: #1A2A4A; 
            font-size: 24px; 
            font-weight: 300;
        }
        
        .header h1 span { 
            font-weight: 700; 
            color: #C9A84C;
        }
        
        .header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .header .logo {
            font-size: 32px;
        }
        
        /* ===== UNIT NAME ===== */
        .unit-name {
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #C9A84C;
        }
        
        .unit-name strong {
            color: #1A2A4A;
            font-size: 18px;
        }
        
        /* ===== INFO BOX ===== */
        .info-box {
            background: #e8f4fd;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            font-size: 14px;
            color: #2c3e50;
        }
        
        /* ===== MESSAGES ===== */
        .message { 
            padding: 14px 18px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-weight: 500;
        }
        
        .message.success { 
            background: #d4edda; 
            color: #155724; 
            border-left: 4px solid #28a745;
        }
        
        .message.error { 
            background: #f8d7da; 
            color: #721c24; 
            border-left: 4px solid #dc3545;
        }
        
        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 18px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #1A2A4A;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input, 
        .form-group select { 
            width: 100%; 
            padding: 12px 15px; 
            border: 2px solid #e0e0e0; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: #fafafa;
        }
        
        .form-group input:focus, 
        .form-group select:focus { 
            border-color: #C9A84C; 
            outline: none;
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.15);
        }
        
        .form-group .hint {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 5px;
        }
        
        /* ===== CURRENT TIME DISPLAY ===== */
        .current-time {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .current-time strong {
            color: #1A2A4A;
        }
        
        /* ===== BUTTONS ===== */
        .btn { 
            background: #1A2A4A; 
            color: #FFFFFF; 
            padding: 14px 20px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            margin-top: 5px;
        }
        
        .btn:hover { 
            background: #C9A84C; 
            color: #1A2A4A;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 168, 76, 0.4);
        }
        
        .btn-success {
            background: #C9A84C;
            color: #1A2A4A;
        }
        
        .btn-success:hover {
            background: #1A2A4A;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(26, 42, 74, 0.4);
        }
        
        .back { 
            display: inline-block; 
            margin-top: 15px; 
            color: #C9A84C; 
            text-decoration: none; 
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .back:hover { 
            color: #1A2A4A; 
            text-decoration: underline; 
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .container { padding: 25px 20px; }
            .header h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="container">
    
    <!-- ===== HEADER ===== -->
    <div class="header">
        <div>
            <h1><span>Start</span> Attendance</h1>
            <div class="subtitle">Create a new attendance session</div>
        </div>
        <span class="logo">🎓</span>
    </div>
    
    <!-- ===== UNIT NAME ===== -->
    <div class="unit-name">
        <strong><?php echo htmlspecialchars($unit_name); ?></strong>
    </div>
    
    <!-- ===== CURRENT TIME ===== -->
    <div class="current-time">
        Current server time: <strong><?php echo date('Y-m-d H:i:s'); ?></strong>
    </div>
    
    <!-- ===== INFO BOX ===== -->
    <div class="info-box">
        Create an attendance session for your class. 
        Students will scan the QR code to mark attendance.
        <br><br>
        <strong>Tip:</strong> After creating the session, you will be shown a QR code to display to your students.
    </div>
    
    <!-- ===== MESSAGES ===== -->
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <!-- ===== FORM ===== -->
    <form method="post">
        <input type="hidden" name="unit_id" value="<?php echo $unit_id; ?>">
        
        <!-- Geofence Selection -->
        <div class="form-group">
            <label>Select Geofence (Location)</label>
            <select name="geofence_id">
                <option value="">-- No Geofence --</option>
                <?php while ($geo = $geofences->fetch_assoc()): ?>
                    <option value="<?php echo $geo['geofence_id']; ?>">
                        <?php echo htmlspecialchars($geo['name'] . ' (' . $geo['unit_name'] . ')'); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <div class="hint">Students must be within this location to check in.</div>
        </div>
        
        <!-- Start Time -->
        <div class="form-group">
            <label>Start Time <span class="required">*</span></label>
            <input type="datetime-local" name="start_time" required
                   value="<?php echo date('Y-m-d\TH:i'); ?>">
            <div class="hint">When should the attendance session start?</div>
        </div>
        
        <!-- Duration -->
        <div class="form-group">
            <label>Duration (minutes) <span class="required">*</span></label>
            <input type="number" name="duration" value="60" min="5" max="180" required>
            <div class="hint">How long should the attendance session last? (5 - 180 minutes)</div>
        </div>
        
        <!-- Submit Button -->
        <button type="submit" class="btn btn-success">Create Session</button>
    </form>
    
    <!-- ===== BACK LINK ===== -->
    <a href="lecturer_dashboard.php" class="back">Back to Dashboard</a>
    
</div>
</body>
</html>