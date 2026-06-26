<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login_form.php");
    exit();
}

$id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    session_destroy();
    header("Location: login_form.php");
    exit();
}

$first_name = explode(" ", $user['full_name'])[0];

$courses_query = "SELECT unit_id, unit_name, unit_code FROM units WHERE user_id = ?";
$stmt = $conn->prepare($courses_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$courses = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM units WHERE user_id = ?) as total_courses,
    (SELECT COUNT(*) FROM attendance_sessions s JOIN units u ON s.unit_id = u.unit_id WHERE u.user_id = ?) as total_sessions,
    (SELECT COUNT(*) FROM attendance_logs al JOIN attendance_sessions s ON al.session_id = s.session_id JOIN units u ON s.unit_id = u.unit_id WHERE u.user_id = ?) as total_attendance";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iii", $id, $id, $id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get upcoming sessions
$upcoming_query = "SELECT s.session_id, s.start_time, s.end_time, u.unit_name, g.name as geofence_name,
                   (SELECT COUNT(*) FROM attendance_logs WHERE session_id = s.session_id) as attendance_count
                   FROM attendance_sessions s
                   JOIN units u ON s.unit_id = u.unit_id
                   LEFT JOIN geofences g ON s.geofence_id = g.geofence_id
                   WHERE u.user_id = ? AND s.start_time > NOW()
                   ORDER BY s.start_time ASC LIMIT 5";
$stmt = $conn->prepare($upcoming_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$upcoming_sessions = $stmt->get_result();

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lecturer Dashboard - GeoQR | KCA University</title>
    <style>
        /* ===== KCA UNIVERSITY THEME ===== */
        /* Colors: Navy Blue (#1A2A4A), Gold (#C9A84C), White (#FFFFFF) */
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            padding: 20px; 
        }
        
        .container { 
            max-width: 1200px; 
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
        
        .header-left { 
            display: flex; 
            align-items: center; 
            gap: 15px; 
        }
        
        .header-logo .logo-icon { font-size: 32px; }
        .header-logo .logo-text { font-size: 14px; opacity: 0.7; letter-spacing: 2px; }
        
        .header h1 { 
            font-size: 24px; 
            font-weight: 300; 
        }
        
        .header h1 span { 
            font-weight: 600; 
            color: #C9A84C;
        }
        
        .header .email { 
            font-size: 13px; 
            opacity: 0.8; 
            margin-top: 3px; 
        }
        
        .header .logout-btn { 
            background: #C9A84C; 
            color: #1A2A4A; 
            padding: 10px 25px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #C9A84C;
        }
        
        .header .logout-btn:hover { 
            background: transparent; 
            color: #C9A84C;
            border-color: #C9A84C;
        }
        
        /* ===== STATS ===== */
        .stats { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 15px; 
            margin-bottom: 25px; 
        }
        
        .stat-card { 
            background: #FFFFFF; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid #C9A84C;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover { 
            transform: translateY(-3px);
        }
        
        .stat-card h3 { 
            font-size: 28px; 
            font-weight: 700; 
            color: #1A2A4A; 
        }
        
        .stat-card p { 
            color: #7f8c8d; 
            font-size: 13px; 
            margin-top: 5px; 
        }
        
        /* ===== GRID LAYOUT ===== */
        .grid { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 25px; 
        }
        
        @media (max-width: 768px) { 
            .grid { 
                grid-template-columns: 1fr; 
            } 
        }
        
        /* ===== CARDS ===== */
        .card { 
            background: #FFFFFF; 
            border-radius: 12px; 
            padding: 25px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
            border-top: 4px solid #C9A84C;
            margin-bottom: 25px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .card h2 { 
            color: #1A2A4A; 
            font-size: 18px; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #f0f2f5; 
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* ===== COURSE ITEMS ===== */
        .course { 
            background: #f8f9fa; 
            padding: 18px 20px; 
            margin-bottom: 12px; 
            border-radius: 8px; 
            border-left: 4px solid #C9A84C;
            transition: transform 0.2s ease;
        }
        
        .course:hover { 
            transform: translateX(5px);
        }
        
        .course h3 { 
            color: #1A2A4A; 
            font-size: 18px; 
        }
        
        .course .code { 
            color: #7f8c8d; 
            font-size: 13px; 
        }
        
        .course .desc { 
            color: #555; 
            font-size: 14px; 
            margin: 8px 0; 
        }
        
        .course .actions { 
            margin-top: 12px; 
            display: flex; 
            flex-wrap: wrap; 
            gap: 10px; 
        }
        
        .course .actions a { 
            padding: 8px 16px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        /* ===== BUTTONS ===== */
        .btn-primary { 
            background: #1A2A4A; 
            color: #FFFFFF; 
        }
        
        .btn-primary:hover { 
            background: #C9A84C; 
            color: #1A2A4A;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 168, 76, 0.3);
        }
        
        .btn-success { 
            background: #C9A84C; 
            color: #1A2A4A; 
        }
        
        .btn-success:hover { 
            background: #1A2A4A; 
            color: #FFFFFF;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 42, 74, 0.3);
        }
        
        .btn-outline { 
            background: transparent; 
            color: #1A2A4A; 
            border: 2px solid #1A2A4A; 
        }
        
        .btn-outline:hover { 
            background: #1A2A4A; 
            color: #FFFFFF;
            transform: translateY(-2px);
        }
        
        .btn-qr { 
            background: #8e44ad; 
            color: #FFFFFF; 
        }
        
        .btn-qr:hover { 
            background: #7d3c98; 
            color: #FFFFFF;
            transform: translateY(-2px);
        }
        
        /* ===== SESSION ITEMS ===== */
        .session-item { 
            padding: 12px 0; 
            border-bottom: 1px solid #ecf0f1; 
        }
        
        .session-item:last-child { 
            border-bottom: none; 
        }
        
        .session-item .title { 
            font-weight: 600; 
            color: #1A2A4A; 
        }
        
        .session-item .meta { 
            font-size: 13px; 
            color: #7f8c8d; 
        }
        
        .session-item .count { 
            background: #C9A84C; 
            color: #1A2A4A;
            padding: 2px 12px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600;
        }
        
        .session-item .actions {
            margin-top: 8px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .session-item .actions a {
            padding: 4px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .session-item .actions .btn-small-qr {
            background: #8e44ad;
            color: #FFFFFF;
        }
        
        .session-item .actions .btn-small-qr:hover {
            background: #6c3483;
            transform: translateY(-2px);
        }
        
        .session-item .actions .btn-small-view {
            background: #3498db;
            color: #FFFFFF;
        }
        
        .session-item .actions .btn-small-view:hover {
            background: #2471a3;
            transform: translateY(-2px);
        }
        
        /* ===== NO DATA ===== */
        .no-data { 
            color: #95a5a6; 
            text-align: center; 
            padding: 20px; 
            font-style: italic;
        }
        
        /* ===== QUICK ACTIONS ===== */
        .quick-actions { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 10px; 
        }
        
        .quick-actions a { 
            padding: 14px; 
            text-align: center; 
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 600; 
            background: #f8f9fa; 
            color: #1A2A4A; 
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .quick-actions a:hover { 
            background: #1A2A4A; 
            color: #C9A84C;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(26, 42, 74, 0.15);
        }
        
        .quick-actions .primary { 
            background: #1A2A4A; 
            color: #FFFFFF; 
        }
        
        .quick-actions .primary:hover { 
            background: #C9A84C; 
            color: #1A2A4A;
        }
        
        .quick-actions .success { 
            background: #C9A84C; 
            color: #1A2A4A; 
        }
        
        .quick-actions .success:hover { 
            background: #1A2A4A; 
            color: #C9A84C;
        }
        
        .quick-actions .qr-action {
            background: #8e44ad;
            color: #FFFFFF;
        }
        
        .quick-actions .qr-action:hover {
            background: #6c3483;
            color: #FFFFFF;
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
            .header-left {
                flex-direction: column;
                text-align: center;
            }
            .header h1 { 
                font-size: 20px; 
            }
            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            .course .actions {
                flex-direction: column;
            }
            .course .actions a {
                text-align: center;
            }
        }
    </style>
</head>
<body>
<div class="container">
    
    <!-- ===== HEADER ===== -->
    <div class="header">
        <div class="header-left">
            <div class="header-logo">
                <span class="logo-icon">🎓</span>
                <div>
                    <span class="logo-text">KCA UNIVERSITY</span>
                </div>
            </div>
            <div>
                <h1>Welcome, <span><?php echo htmlspecialchars($first_name); ?></span>!</h1>
                <div class="email"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
        </div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <!-- ===== STATS ===== -->
    <div class="stats">
        <div class="stat-card">
            <h3><?php echo $stats['total_courses'] ?? 0; ?></h3>
            <p>My Courses</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['total_sessions'] ?? 0; ?></h3>
            <p>Sessions Created</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $stats['total_attendance'] ?? 0; ?></h3>
            <p>Total Attendance</p>
        </div>
    </div>

    <!-- ===== GRID LAYOUT ===== -->
    <div class="grid">
        
        <!-- ===== LEFT COLUMN ===== -->
        <div>
            <!-- My Courses -->
            <div class="card">
                <h2>My Courses</h2>
                <?php if ($courses->num_rows > 0): ?>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <div class="course">
                            <h3><?php echo htmlspecialchars($course['unit_name']); ?></h3>
                            <div class="code"><?php echo htmlspecialchars($course['unit_code']); ?></div>
                            <div class="desc"><?php echo htmlspecialchars($course['description'] ?? 'No description'); ?></div>
                            <div class="actions">
                                <a href="create_session.php?unit_id=<?php echo $course['unit_id']; ?>" class="btn-success">Start Attendance</a>
                                <a href="view_attendance.php?unit_id=<?php echo $course['unit_id']; ?>" class="btn-primary">View Attendance</a>
                                <a href="view_students.php?unit_id=<?php echo $course['unit_id']; ?>" class="btn-outline">Students</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">You don't have any courses assigned yet. Contact Admin.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ===== RIGHT COLUMN ===== -->
        <div>
            <!-- Upcoming Sessions -->
            <div class="card">
                <h2>Upcoming Sessions</h2>
                <?php if ($upcoming_sessions->num_rows > 0): ?>
                    <?php while ($session = $upcoming_sessions->fetch_assoc()): ?>
                        <div class="session-item">
                            <div class="title"><?php echo htmlspecialchars($session['unit_name']); ?></div>
                            <div class="meta">
                                <?php echo htmlspecialchars($session['geofence_name'] ?? 'No geofence'); ?><br>
                                <?php echo date('M d, Y - H:i', strtotime($session['start_time'])); ?>
                                <span class="count"><?php echo $session['attendance_count']; ?></span>
                            </div>
                            <div class="actions">
                                <a href="generate_qr.php?session_id=<?php echo $session['session_id']; ?>" class="btn-small-qr">QR Code</a>
                                <a href="view_attendance.php?unit_id=<?php echo $course['unit_id'] ?? 0; ?>&session_id=<?php echo $session['session_id']; ?>" class="btn-small-view">View Details</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">No upcoming sessions scheduled.</div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2>Quick Actions</h2>
                <div class="quick-actions">
                    <a href="create_session.php" class="primary">New Session</a>
                    <a href="generate_qr.php" class="qr-action">QR Generator</a>
                    <a href="view_all_attendance.php" class="success">Reports</a>
                    <a href="manage_students.php">Students</a>
                </div>
            </div>
        </div>
        
    </div>
    
    <!-- ===== FOOTER ===== -->
    <div class="footer">
        <span class="brand">KCA UNIVERSITY</span> • 
        <span class="gold">GeoQR</span> • 
        Smart Attendance Management System
    </div>
    
</div>
</body>
</html>