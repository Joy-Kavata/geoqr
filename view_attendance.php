<?php
session_start();
include 'config.php';

// Check if user is lecturer
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login_form.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$unit_id = isset($_GET['unit_id']) ? $_GET['unit_id'] : 0;
$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : null;

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

// If a session is selected, show attendance details
if ($session_id) {
    // Get session details
    $session_query = "SELECT s.session_id, s.start_time, s.end_time, g.name as geofence_name,
                      (SELECT COUNT(*) FROM attendance_logs WHERE session_id = s.session_id) as total_present
                      FROM attendance_sessions s
                      LEFT JOIN geofences g ON s.geofence_id = g.geofence_id
                      WHERE s.session_id = ? AND s.unit_id = ?";
    $stmt = $conn->prepare($session_query);
    $stmt->bind_param("ii", $session_id, $unit_id);
    $stmt->execute();
    $session_result = $stmt->get_result();
    
    if ($session_result->num_rows == 0) {
        die("Session not found.");
    }
    $session = $session_result->fetch_assoc();
    
    // ============================================================
    // GET ALL ENROLLED STUDENTS FOR THIS UNIT
    // ============================================================
    $all_students_query = "SELECT u.user_id, u.full_name, u.email
                           FROM users u
                           JOIN enrollment e ON u.user_id = e.user_id
                           WHERE e.unit_id = ?
                           ORDER BY u.full_name ASC";
    $stmt = $conn->prepare($all_students_query);
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $all_students = $stmt->get_result();
    $total_enrolled = $all_students->num_rows;
    
    // ============================================================
    // GET STUDENTS WHO ATTENDED (PRESENT)
    // ============================================================
    $present_query = "SELECT u.user_id, u.full_name, u.email, al.check_in_time, al.status
                      FROM attendance_logs al
                      JOIN users u ON al.user_id = u.user_id
                      WHERE al.session_id = ?
                      ORDER BY al.check_in_time ASC";
    $stmt = $conn->prepare($present_query);
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $present_students = $stmt->get_result();
    
    // ============================================================
    // CREATE ARRAY OF PRESENT STUDENT IDs
    // ============================================================
    $present_ids = [];
    while ($p = $present_students->fetch_assoc()) {
        $present_ids[] = $p['user_id'];
    }
    
    // Reset the present_students result set for display
    $present_students->data_seek(0);
    
    // ============================================================
    // GET STUDENTS WHO WERE ABSENT (Enrolled but not present)
    // ============================================================
    $absent_students = [];
    $all_students->data_seek(0);
    while ($student = $all_students->fetch_assoc()) {
        if (!in_array($student['user_id'], $present_ids)) {
            $absent_students[] = $student;
        }
    }
    $total_absent = count($absent_students);
    $total_present = count($present_ids);
    $attendance_percentage = $total_enrolled > 0 ? round(($total_present / $total_enrolled) * 100) : 0;

} else {
    // Get all sessions for this unit
    $sessions_query = "SELECT s.session_id, s.start_time, s.end_time, g.name as geofence_name,
                       (SELECT COUNT(*) FROM attendance_logs WHERE session_id = s.session_id) as attendance_count
                       FROM attendance_sessions s
                       LEFT JOIN geofences g ON s.geofence_id = g.geofence_id
                       WHERE s.unit_id = ?
                       ORDER BY s.start_time DESC";
    $stmt = $conn->prepare($sessions_query);
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $sessions = $stmt->get_result();
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Attendance - <?php echo htmlspecialchars($unit_name); ?></title>
    <style>
        /* ===== KCA UNIVERSITY THEME ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            padding: 20px; 
        }
        
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
        }
        
        /* ===== HEADER ===== */
        .header { 
            background: linear-gradient(135deg, #1A2A4A 0%, #2C3E6A 100%);
            color: #FFFFFF; 
            padding: 25px 30px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            box-shadow: 0 4px 15px rgba(26, 42, 74, 0.3);
            border-bottom: 4px solid #C9A84C;
        }
        
        .header h1 { 
            font-size: 24px; 
            font-weight: 300; 
        }
        
        .header h1 span { 
            font-weight: 600; 
            color: #C9A84C;
        }
        
        .header h2 { 
            font-size: 16px; 
            font-weight: normal; 
            opacity: 0.8; 
            margin-top: 5px; 
        }
        
        .back { 
            color: #FFFFFF; 
            background: #C9A84C; 
            padding: 10px 25px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #C9A84C;
        }
        
        .back:hover { 
            background: transparent; 
            color: #C9A84C;
            border-color: #C9A84C;
        }
        
        /* ===== CARDS ===== */
        .card { 
            background: #FFFFFF; 
            border-radius: 12px; 
            padding: 25px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
            border-top: 4px solid #C9A84C;
            margin-bottom: 25px;
        }
        
        .card h3 { 
            color: #1A2A4A; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #f0f2f5; 
            padding-bottom: 10px;
            font-size: 18px;
        }
        
        /* ===== STATS GRID ===== */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); 
            gap: 15px; 
            margin-bottom: 20px; 
        }
        
        .stat-box { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 8px; 
            text-align: center;
            border-top: 4px solid #C9A84C;
        }
        
        .stat-box .number { 
            font-size: 24px; 
            font-weight: 700; 
            color: #1A2A4A; 
        }
        
        .stat-box .number.green { color: #27ae60; }
        .stat-box .number.red { color: #e74c3c; }
        .stat-box .number.gold { color: #C9A84C; }
        
        .stat-box .label { 
            font-size: 12px; 
            color: #7f8c8d; 
            margin-top: 3px;
        }
        
        /* ===== TABLES ===== */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 14px;
        }
        
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #ddd; 
        }
        
        th { 
            background: #f8f9fa; 
            font-weight: 600; 
            color: #1A2A4A;
            border-bottom: 2px solid #C9A84C;
        }
        
        tr:hover { 
            background: #f8f9fa; 
        }
        
        /* ===== STATUS ===== */
        .status-present { color: #27ae60; font-weight: 600; }
        .status-absent { color: #e74c3c; font-weight: 600; }
        .status-late { color: #f39c12; font-weight: 600; }
        
        /* ===== BADGES ===== */
        .badge-present { 
            background: #27ae60; 
            color: white; 
            padding: 2px 14px; 
            border-radius: 20px; 
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 8px;
        }
        
        .badge-absent { 
            background: #e74c3c; 
            color: white; 
            padding: 2px 14px; 
            border-radius: 20px; 
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-left: 8px;
        }
        
        /* ===== BUTTONS ===== */
        .btn { 
            display: inline-block; 
            padding: 8px 20px; 
            background: #C9A84C; 
            color: #1A2A4A; 
            text-decoration: none; 
            border-radius: 6px; 
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #C9A84C;
        }
        
        .btn:hover { 
            background: #1A2A4A; 
            color: #C9A84C;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 42, 74, 0.3);
        }
        
        .btn-qr {
            display: inline-block;
            padding: 8px 20px;
            background: #8e44ad;
            color: #FFFFFF;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #8e44ad;
        }
        
        .btn-qr:hover {
            background: #6c3483;
            color: #FFFFFF;
            border-color: #6c3483;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(142, 68, 173, 0.3);
        }
        
        .btn-outline {
            display: inline-block;
            padding: 8px 20px;
            background: transparent;
            color: #1A2A4A;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #1A2A4A;
        }
        
        .btn-outline:hover {
            background: #1A2A4A;
            color: #FFFFFF;
            transform: translateY(-2px);
        }
        
        /* ===== SESSION ITEMS ===== */
        .session-item { 
            padding: 12px 15px; 
            border-bottom: 1px solid #ecf0f1; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .session-item:last-child { border-bottom: none; }
        
        .session-item .info { flex: 1; }
        
        .session-item .info .title { 
            font-weight: 600; 
            color: #1A2A4A; 
        }
        
        .session-item .info .meta { 
            font-size: 13px; 
            color: #7f8c8d; 
        }
        
        .session-item .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        /* ===== NO DATA ===== */
        .no-data { 
            color: #95a5a6; 
            text-align: center; 
            padding: 20px; 
            font-style: italic;
        }
        
        /* ===== TWO COLUMN LAYOUT ===== */
        .two-col { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 25px; 
        }
        
        @media (max-width: 768px) { 
            .two-col { 
                grid-template-columns: 1fr; 
            } 
        }
        
        /* ===== BACK LINK ===== */
        .back-link { 
            display: inline-block; 
            margin-top: 15px; 
            color: #C9A84C; 
            text-decoration: none; 
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .back-link:hover { 
            color: #1A2A4A; 
            text-decoration: underline; 
        }
        
        /* ===== SUMMARY INFO ===== */
        .summary-info {
            background: #f8f9fa;
            padding: 12px 18px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            color: #555;
            border-left: 4px solid #C9A84C;
        }
        
        .summary-info strong {
            color: #1A2A4A;
        }
        
        /* ===== ACTION BAR ===== */
        .action-bar {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
        }
        
        /* ===== FOOTER ===== */
        .footer {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            color: #95a5a6;
            font-size: 13px;
            border-top: 1px solid #ecf0f1;
        }
        
        .footer .brand {
            color: #1A2A4A;
            font-weight: 600;
        }
        
        .footer .gold {
            color: #C9A84C;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .header h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
<div class="container">
    
    <!-- ===== HEADER ===== -->
    <div class="header">
        <div>
            <h1>View Attendance</h1>
            <h2><?php echo htmlspecialchars($unit_name); ?></h2>
        </div>
        <a href="lecturer_dashboard.php" class="back">Dashboard</a>
    </div>

    <?php if ($session_id && isset($session)): ?>
        <!-- ============================================================ -->
        <!-- SESSION DETAILS VIEW                                          -->
        <!-- ============================================================ -->
        
        <!-- Statistics Summary -->
        <div class="card">
            <h3>Attendance Summary</h3>
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="number"><?php echo $total_enrolled; ?></div>
                    <div class="label">Total Enrolled</div>
                </div>
                <div class="stat-box">
                    <div class="number green"><?php echo $total_present; ?></div>
                    <div class="label">Present</div>
                </div>
                <div class="stat-box">
                    <div class="number red"><?php echo $total_absent; ?></div>
                    <div class="label">Absent</div>
                </div>
                <div class="stat-box">
                    <div class="number gold"><?php echo $attendance_percentage; ?>%</div>
                    <div class="label">Attendance Rate</div>
                </div>
            </div>
            
            <div class="summary-info">
                <strong>Session Info:</strong> 
                <?php echo date('l, M d, Y', strtotime($session['start_time'])); ?> • 
                <?php echo date('H:i', strtotime($session['start_time'])); ?> - 
                <?php echo date('H:i', strtotime($session['end_time'])); ?>
                <?php if ($session['geofence_name']): ?>
                    • Location: <?php echo htmlspecialchars($session['geofence_name']); ?>
                <?php endif; ?>
            </div>
            
            <!-- Action Bar -->
            <div class="action-bar">
                <a href="generate_qr.php?session_id=<?php echo $session_id; ?>" class="btn-qr">QR Code</a>
                <a href="view_attendance.php?unit_id=<?php echo $unit_id; ?>" class="btn-outline">Back to Sessions</a>
            </div>
        </div>
        
        <!-- Two Column: Present + Absent -->
        <div class="two-col">
            
            <!-- PRESENT STUDENTS -->
            <div class="card">
                <h3>Present Students <span class="badge-present"><?php echo $total_present; ?></span></h3>
                <?php if ($total_present > 0): ?>
                    <table>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Time</th>
                        </tr>
                        <?php $count = 1; while ($p = $present_students->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                                <td><?php echo date('H:i', strtotime($p['check_in_time'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php else: ?>
                    <div class="no-data">No students checked in.</div>
                <?php endif; ?>
            </div>
            
            <!-- ABSENT STUDENTS -->
            <div class="card">
                <h3>Absent Students <span class="badge-absent"><?php echo $total_absent; ?></span></h3>
                <?php if ($total_absent > 0): ?>
                    <table>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Email</th>
                        </tr>
                        <?php $count = 1; foreach ($absent_students as $student): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php else: ?>
                    <div class="no-data">All students were present!</div>
                <?php endif; ?>
            </div>
            
        </div>
        
        <a href="view_attendance.php?unit_id=<?php echo $unit_id; ?>" class="back-link">Back to all sessions</a>
        
    <?php else: ?>
        <!-- ============================================================ -->
        <!-- SESSIONS LIST VIEW                                           -->
        <!-- ============================================================ -->
        <div class="card">
            <h3>All Sessions</h3>
            <?php if ($sessions->num_rows > 0): ?>
                <?php while ($s = $sessions->fetch_assoc()): ?>
                    <div class="session-item">
                        <div class="info">
                            <div class="title"><?php echo date('l, M d, Y', strtotime($s['start_time'])); ?></div>
                            <div class="meta">
                                Time: <?php echo date('H:i', strtotime($s['start_time'])); ?> - <?php echo date('H:i', strtotime($s['end_time'])); ?>
                                <?php if ($s['geofence_name']): ?>
                                    • Location: <?php echo htmlspecialchars($s['geofence_name']); ?>
                                <?php endif; ?>
                                • Present: <?php echo $s['attendance_count']; ?>
                            </div>
                        </div>
                        <div class="actions">
                            <a href="view_attendance.php?unit_id=<?php echo $unit_id; ?>&session_id=<?php echo $s['session_id']; ?>" class="btn">View Details</a>
                            <a href="generate_qr.php?session_id=<?php echo $s['session_id']; ?>" class="btn-qr">QR Code</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No attendance sessions created yet for this unit.</div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- ===== FOOTER ===== -->
    <div class="footer">
        <span class="brand">KCA UNIVERSITY</span> • 
        <span class="gold">GeoQR</span> • 
        Smart Attendance Management System
    </div>
    
</div>
</body>
</html>