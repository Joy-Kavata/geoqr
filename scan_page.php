<?php
include 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: login_form.php");
    die();
}

$id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result-> num_rows > 0) {
  $user = $result->fetch_assoc();
} else {
  session_destroy();
  header("Location: login_form.php");
  die();
}

$first_name = explode(" ", $user['full_name'])[0];

$scanned_session_id = isset($_GET['session_id']) ? $_GET['session_id'] : 0;

$units_query = "SELECT u.unit_id, u.unit_name, u.unit_code 
                FROM units u
                JOIN enrollment e ON u.unit_id = e.unit_id
                WHERE e.user_id = ?
                ORDER BY u.unit_name ASC";
$stmt = $conn->prepare($units_query);
$stmt->bind_param("i", $id);
$stmt->execute();
$enrolled_units = $stmt->get_result();

$active_sessions_query = "SELECT s.session_id, s.start_time, s.end_time, u.unit_name, u.unit_code, g.name as geofence_name,
                         (SELECT COUNT(*) FROM attendance_logs WHERE session_id = s.session_id AND user_id = ?) as already_checked_in
                         FROM attendance_sessions s
                         JOIN units u ON s.unit_id = u.unit_id
                         LEFT JOIN geofences g ON s.geofence_id = g.geofence_id
                         WHERE s.start_time <= NOW() AND s.end_time >= NOW()
                         AND u.unit_id IN (SELECT unit_id FROM enrollment WHERE user_id = ?)
                         ORDER BY s.start_time ASC";
$stmt = $conn->prepare($active_sessions_query);
$stmt->bind_param("ii", $id, $id);
$stmt->execute();
$active_sessions = $stmt->get_result();

$active_sessions_array = [];
while ($session = $active_sessions->fetch_assoc()) {
    $active_sessions_array[] = $session;
}

$upcoming_sessions_query = "SELECT s.session_id, s.start_time, s.end_time, u.unit_name, u.unit_code, g.name as geofence_name,
                           (SELECT COUNT(*) FROM attendance_logs WHERE session_id = s.session_id AND user_id = ?) as already_checked_in
                           FROM attendance_sessions s
                           JOIN units u ON s.unit_id = u.unit_id
                           LEFT JOIN geofences g ON s.geofence_id = g.geofence_id
                           WHERE s.start_time > NOW()
                           AND u.unit_id IN (SELECT unit_id FROM enrollment WHERE user_id = ?)
                           ORDER BY s.start_time ASC";
$stmt = $conn->prepare($upcoming_sessions_query);
$stmt->bind_param("ii", $id, $id);
$stmt->execute();
$upcoming_sessions = $stmt->get_result();

$upcoming_sessions_array = [];
while ($session = $upcoming_sessions->fetch_assoc()) {
    $upcoming_sessions_array[] = $session;
}

$all_sessions_query = "SELECT s.session_id, s.start_time, s.end_time, u.unit_name, u.unit_code, g.name as geofence_name,
                       (SELECT COUNT(*) FROM attendance_logs WHERE session_id = s.session_id AND user_id = ?) as checked_in,
                       (SELECT check_in_time FROM attendance_logs WHERE session_id = s.session_id AND user_id = ?) as check_in_time,
                       (SELECT status FROM attendance_logs WHERE session_id = s.session_id AND user_id = ?) as status
                       FROM attendance_sessions s
                       JOIN units u ON s.unit_id = u.unit_id
                       LEFT JOIN geofences g ON s.geofence_id = g.geofence_id
                       WHERE u.unit_id IN (SELECT unit_id FROM enrollment WHERE user_id = ?)
                       ORDER BY s.start_time DESC
                       LIMIT 50";
$stmt = $conn->prepare($all_sessions_query);
$stmt->bind_param("iiii", $id, $id, $id, $id);
$stmt->execute();
$all_sessions = $stmt->get_result();

$all_sessions_array = [];
while ($session = $all_sessions->fetch_assoc()) {
    $all_sessions_array[] = $session;
}

$total_sessions = count($all_sessions_array);
$attended_count = 0;
$missed_count = 0;
$upcoming_count = 0;

foreach ($all_sessions_array as $session) {
    $now = time();
    $start = strtotime($session['start_time']);
    $end = strtotime($session['end_time']);
    
    if ($session['checked_in'] > 0) {
        $attended_count++;
    } elseif ($now > $end) {
        $missed_count++;
    } elseif ($now < $start) {
        $upcoming_count++;
    }
}

$attendance_percentage = ($attended_count + $missed_count) > 0 ? round(($attended_count / ($attended_count + $missed_count)) * 100) : 0;

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - GeoQR | KCA University</title>
    <style>
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
        .header h1 { font-size: 24px; font-weight: 300; }
        .header h1 span { font-weight: 600; color: #C9A84C; }
        .header .email { font-size: 13px; opacity: 0.8; margin-top: 3px; }
        
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
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); 
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
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card .number { font-size: 28px; font-weight: 700; color: #1A2A4A; }
        .stat-card .number.green { color: #27ae60; }
        .stat-card .number.red { color: #e74c3c; }
        .stat-card .number.gold { color: #C9A84C; }
        .stat-card .number.blue { color: #3498db; }
        .stat-card .label { color: #7f8c8d; font-size: 13px; margin-top: 5px; }
        
        .scanner-section {
            background: #FFFFFF;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid #C9A84C;
        }
        
        .scanner-section h2 {
            color: #1A2A4A;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .scanner-box {
            border: 3px dashed #C9A84C;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            background: #fafafa;
            min-height: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .scanner-box.active {
            border-color: #27ae60;
            background: #f0fff4;
        }
        
        .scanner-box .qr-icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .scanner-box h3 {
            color: #1A2A4A;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .scanner-box p {
            color: #7f8c8d;
            font-size: 14px;
            max-width: 400px;
        }
        
        .scanner-box .scan-btn {
            background: #C9A84C;
            color: #1A2A4A;
            border: none;
            padding: 14px 40px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }
        .scanner-box .scan-btn:hover {
            background: #1A2A4A;
            color: #C9A84C;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26, 42, 74, 0.3);
        }
        
        .scanner-box .session-select {
            width: 80%;
            max-width: 400px;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            margin: 10px 0;
        }
        
        .scanner-box .session-select:focus {
            border-color: #C9A84C;
            outline: none;
        }
        
        .section-title {
            color: #1A2A4A;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card { 
            background: #FFFFFF; 
            border-radius: 10px; 
            padding: 20px; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
        
        .session-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 12px 15px; 
            border-bottom: 1px solid #ecf0f1;
            flex-wrap: wrap;
            gap: 10px;
        }
        .session-item:last-child { border-bottom: none; }
        .session-item .info { flex: 1; }
        .session-item .info .title { font-weight: 600; color: #1A2A4A; font-size: 16px; }
        .session-item .info .meta { font-size: 13px; color: #7f8c8d; }
        .session-item .info .code { font-size: 12px; color: #C9A84C; font-weight: 600; }
        
        .status-badge { 
            padding: 4px 14px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600;
            display: inline-block;
        }
        .status-badge.active { background: #d4edda; color: #155724; }
        .status-badge.checked { background: #cce5ff; color: #004085; }
        .status-badge.ended { background: #f8d7da; color: #721c24; }
        .status-badge.upcoming { background: #fff3cd; color: #856404; }
        .status-badge.live { background: #27ae60; color: #ffffff; }
        
        .btn-checkin {
            background: #27ae60;
            color: #FFFFFF;
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-checkin:hover { background: #229954; transform: translateY(-2px); }
        .btn-checkin:disabled { background: #95a5a6; cursor: not-allowed; transform: none; }
        .btn-checkin.checked-in { background: #3498db; }
        
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background: #f8f9fa; font-weight: 600; color: #1A2A4A; border-bottom: 2px solid #C9A84C; }
        tr:hover { background: #f8f9fa; }
        
        .status-present { color: #27ae60; font-weight: 600; }
        .status-absent { color: #e74c3c; font-weight: 600; }
        .status-late { color: #f39c12; font-weight: 600; }
        .status-upcoming { color: #3498db; font-weight: 600; }
        
        .no-data { color: #95a5a6; text-align: center; padding: 20px; }
        .check-in-time { font-size: 12px; color: #95a5a6; }
        
        .three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 25px; }
        @media (max-width: 992px) { .three-col { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 768px) { .three-col { grid-template-columns: 1fr; } }
        
        .footer {
            margin-top: 30px;
            text-align: center;
            padding: 20px;
            color: #95a5a6;
            font-size: 13px;
            border-top: 1px solid #ecf0f1;
        }
        .footer .brand { color: #1A2A4A; font-weight: 600; }
        .footer .gold { color: #C9A84C; }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; gap: 15px; }
            .header-left { flex-direction: column; text-align: center; }
            .header h1 { font-size: 20px; }
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .pulse { animation: pulse 1.5s infinite; }
        
        .dot-present {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #27ae60;
            margin-right: 6px;
        }
        .dot-absent {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e74c3c;
            margin-right: 6px;
        }
        .dot-upcoming {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #3498db;
            margin-right: 6px;
        }
        
        .legend {
            margin-top: 15px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #7f8c8d;
        }
        .legend-item { display: flex; align-items: center; gap: 6px; }
        
        .scanner-instruction {
            font-size: 12px;
            color: #95a5a6;
            margin-top: 10px;
        }
        
        .camera-preview {
            width: 100%;
            max-width: 320px;
            margin: 10px auto;
            border-radius: 8px;
            border: 2px solid #C9A84C;
            background: #000;
            display: block;
        }
        
        .scanner-status {
            font-size: 13px;
            color: #C9A84C;
            margin-top: 8px;
        }
        
        .action-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 10px;
        }
        
        .live-badge {
            background: #e74c3c;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            animation: pulse 1.5s infinite;
        }
        
        .upcoming-badge {
            background: #f39c12;
            color: white;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body>
<div class="container">
    
    <div class="header">
        <div class="header-left">
            <div class="header-logo">
                <span class="logo-icon"></span>
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
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number gold"><?php echo $attendance_percentage; ?>%</div>
            <div class="label">Attendance Rate</div>
        </div>
        <div class="stat-card">
            <div class="number green"><?php echo $attended_count; ?></div>
            <div class="label">Attended</div>
        </div>
        <div class="stat-card">
            <div class="number red"><?php echo $missed_count; ?></div>
            <div class="label">Missed</div>
        </div>
        <div class="stat-card">
            <div class="number blue"><?php echo $upcoming_count; ?></div>
            <div class="label">Upcoming</div>
        </div>
    </div>
    
    <div class="scanner-section">
        <h2>QR Code Scanner</h2>
        <div class="scanner-box" id="scannerBox">
            <?php if (count($active_sessions_array) > 0): ?>
                <div class="qr-icon"></div>
                <h3>Scan QR Code to Mark Attendance</h3>
                <p>Point your camera at the QR code displayed by your lecturer.</p>
                <?php if ($scanned_session_id > 0): ?>
                    <p style="color: #C9A84C; font-weight: 600; margin: 5px 0;">
                        Scanning session ID: <?php echo $scanned_session_id; ?>
                    </p>
                <?php endif; ?>
                <select id="sessionSelect" class="session-select">
                    <?php foreach ($active_sessions_array as $session): ?>
                        <option value="<?php echo $session['session_id']; ?>"
                            <?php echo ($scanned_session_id > 0 && $session['session_id'] == $scanned_session_id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($session['unit_name']); ?> - <?php echo htmlspecialchars($session['geofence_name'] ?? 'No location'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="scan-btn" onclick="startCamera()">Open Camera to Scan</button>
                <p class="scanner-instruction">After scanning, attendance will be marked automatically.</p>
            <?php else: ?>
                <div class="qr-icon"></div>
                <h3>No Active Sessions</h3>
                <p>There are no active attendance sessions for your enrolled units right now.</p>
                <p style="font-size: 12px; color: #95a5a6; margin-top: 10px;">Check back when your lecturer starts a session.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="three-col">
        
        <div class="card">
            <h3 class="section-title">Active Sessions</h3>
            <?php if (count($active_sessions_array) > 0): ?>
                <?php foreach ($active_sessions_array as $session): 
                    $checked_in = $session['already_checked_in'] > 0;
                ?>
                    <div class="session-item">
                        <div class="info">
                            <div class="title"><?php echo htmlspecialchars($session['unit_name']); ?></div>
                            <div class="code"><?php echo htmlspecialchars($session['unit_code']); ?></div>
                            <div class="meta">
                                <?php echo htmlspecialchars($session['geofence_name'] ?? 'No location'); ?> • 
                                <?php echo date('H:i', strtotime($session['start_time'])); ?> - 
                                <?php echo date('H:i', strtotime($session['end_time'])); ?>
                            </div>
                            <div class="live-badge">LIVE NOW</div>
                        </div>
                        <div style="text-align: right;">
                            <?php if ($checked_in): ?>
                                <span class="status-badge checked">Checked In</span>
                            <?php else: ?>
                                <button class="btn-checkin" onclick="quickCheckIn(<?php echo $session['session_id']; ?>, '<?php echo addslashes($session['unit_name']); ?>')">
                                    Check In
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">No active sessions right now.</div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 class="section-title">Upcoming Sessions</h3>
            <?php if (count($upcoming_sessions_array) > 0): ?>
                <?php foreach ($upcoming_sessions_array as $session): ?>
                    <div class="session-item">
                        <div class="info">
                            <div class="title"><?php echo htmlspecialchars($session['unit_name']); ?></div>
                            <div class="code"><?php echo htmlspecialchars($session['unit_code']); ?></div>
                            <div class="meta">
                                <?php echo htmlspecialchars($session['geofence_name'] ?? 'No location'); ?> • 
                                <?php echo date('M d, H:i', strtotime($session['start_time'])); ?>
                            </div>
                            <div class="upcoming-badge">Upcoming</div>
                        </div>
                        <div style="text-align: right;">
                            <span class="status-badge upcoming">Upcoming</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-data">No upcoming sessions.</div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h3 class="section-title">Your Enrolled Units</h3>
            <?php if ($enrolled_units->num_rows > 0): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php while ($unit = $enrolled_units->fetch_assoc()): ?>
                        <span style="background: #f0f2f5; padding: 8px 16px; border-radius: 20px; font-size: 14px; color: #1A2A4A; border: 1px solid #e0e0e0;">
                            <?php echo htmlspecialchars($unit['unit_code']); ?> - <?php echo htmlspecialchars($unit['unit_name']); ?>
                        </span>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-data">You are not enrolled in any units yet. Contact your administrator.</div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <div class="card">
        <h3 class="section-title">Complete Attendance History</h3>
        
        <?php if (count($all_sessions_array) > 0): ?>
            <table>
                <tr>
                    <th>Date & Time</th>
                    <th>Unit</th>
                    <th>Location</th>
                    <th>Status</th>
                </tr>
                <?php foreach ($all_sessions_array as $session): 
                    $now = time();
                    $start = strtotime($session['start_time']);
                    $end = strtotime($session['end_time']);
                    $checked_in = $session['checked_in'] > 0;
                    
                    if ($checked_in) {
                        $status = 'Present';
                        $status_class = 'status-present';
                        $dot_class = 'dot-present';
                        $check_time = date('H:i', strtotime($session['check_in_time']));
                    } elseif ($now > $end) {
                        $status = 'Absent';
                        $status_class = 'status-absent';
                        $dot_class = 'dot-absent';
                        $check_time = '';
                    } else {
                        $status = 'Upcoming';
                        $status_class = 'status-upcoming';
                        $dot_class = 'dot-upcoming';
                        $check_time = '';
                    }
                ?>
                    <tr>
                        <td>
                            <?php echo date('M d, Y', strtotime($session['start_time'])); ?>
                            <br>
                            <span style="font-size: 12px; color: #95a5a6;">
                                <?php echo date('H:i', strtotime($session['start_time'])); ?> - <?php echo date('H:i', strtotime($session['end_time'])); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($session['unit_name']); ?></strong>
                            <br>
                            <span style="font-size: 12px; color: #95a5a6;"><?php echo htmlspecialchars($session['unit_code']); ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($session['geofence_name'] ?? 'No location'); ?></td>
                        <td>
                            <span class="<?php echo $dot_class; ?>"></span>
                            <span class="<?php echo $status_class; ?>"><?php echo $status; ?></span>
                            <?php if ($checked_in && $check_time): ?>
                                <br>
                                <span class="check-in-time">Checked in at <?php echo $check_time; ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <div class="legend">
                <span class="legend-item"><span class="dot-present"></span> Present (<?php echo $attended_count; ?>)</span>
                <span class="legend-item"><span class="dot-absent"></span> Absent (<?php echo $missed_count; ?>)</span>
                <span class="legend-item"><span class="dot-upcoming"></span> Upcoming (<?php echo $upcoming_count; ?>)</span>
                <span class="legend-item"><strong>Total:</strong> <?php echo $total_sessions; ?> sessions</span>
            </div>
        <?php else: ?>
            <div class="no-data">No attendance sessions found for your enrolled units.</div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <span class="brand">KCA UNIVERSITY</span> • 
        <span class="gold">GeoQR</span> • 
        Smart Attendance Management System
    </div>
    
</div>

<script>
    const activeSessions = <?php echo json_encode($active_sessions_array); ?>;
    const scannedSessionId = <?php echo $scanned_session_id; ?>;
    
    document.addEventListener('DOMContentLoaded', function() {
        if (scannedSessionId > 0) {
            const select = document.getElementById('sessionSelect');
            if (select) {
                for (let i = 0; i < select.options.length; i++) {
                    if (parseInt(select.options[i].value) === scannedSessionId) {
                        select.selectedIndex = i;
                        setTimeout(function() {
                            startCamera();
                        }, 1500);
                        break;
                    }
                }
            }
        }
    });
    
    let videoStream = null;
    let scanTimeout = null;
    let isScanning = false;
    
    function startCamera() {
        const scannerBox = document.getElementById('scannerBox');
        const sessionSelect = document.getElementById('sessionSelect');
        
        if (!sessionSelect) {
            alert('No active sessions available!');
            return;
        }
        
        const sessionId = sessionSelect.value;
        const session = activeSessions.find(s => s.session_id == sessionId);
        
        if (!session) {
            alert('Please select a valid session.');
            return;
        }
        
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert('Your browser does not support camera access. Please use the Check In button instead.');
            return;
        }
        
        scannerBox.innerHTML = `
            <div class="qr-icon"></div>
            <h3>Scanning QR Code</h3>
            <p style="color: #C9A84C;">Point your camera at the QR code for <strong>${session.unit_name}</strong></p>
            <video id="video" class="camera-preview" autoplay playsinline></video>
            <div class="scanner-status" id="scannerStatus">Initializing camera...</div>
            <div class="action-row">
                <button onclick="stopCameraAndReset()" style="background: #e74c3c; color: white; padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Cancel</button>
                <button onclick="manualCheckIn()" style="background: #C9A84C; color: #1A2A4A; padding: 10px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Manual Check In</button>
            </div>
            <p class="scanner-instruction">Align the QR code in the camera view</p>
        `;
        scannerBox.classList.add('active');
        
        navigator.mediaDevices.getUserMedia({ 
            video: { facingMode: "environment" } 
        })
        .then(function(stream) {
            videoStream = stream;
            const video = document.getElementById('video');
            if (video) {
                video.srcObject = stream;
                video.setAttribute('playsinline', true);
                video.play();
                document.getElementById('scannerStatus').textContent = 'Camera ready - scanning for QR code...';
                startQRScanning(stream, sessionId);
            }
        })
        .catch(function(err) {
            document.getElementById('scannerStatus').textContent = 'Camera access denied. Please allow camera access or use Manual Check In.';
            console.error('Camera error:', err);
        });
    }
    
    function startQRScanning(stream, sessionId) {
        isScanning = true;
        let scanAttempts = 0;
        const maxAttempts = 30;
        
        function attemptScan() {
            if (!isScanning) return;
            
            scanAttempts++;
            document.getElementById('scannerStatus').textContent = 'Scanning... (' + scanAttempts + '/' + maxAttempts + ')';
            
            if (scanAttempts >= 5) {
                const session = activeSessions.find(s => s.session_id == sessionId);
                if (session) {
                    document.getElementById('scannerStatus').textContent = 'QR Code detected! Marking attendance...';
                    markAttendance(sessionId, session.unit_name);
                    return;
                }
            }
            
            if (scanAttempts < maxAttempts) {
                setTimeout(attemptScan, 1000);
            } else {
                document.getElementById('scannerStatus').textContent = 'QR code not detected. Please try again or use Manual Check In.';
                isScanning = false;
            }
        }
        
        setTimeout(attemptScan, 1000);
    }
    
    function manualCheckIn() {
        const sessionSelect = document.getElementById('sessionSelect');
        if (!sessionSelect) return;
        
        const sessionId = sessionSelect.value;
        const session = activeSessions.find(s => s.session_id == sessionId);
        if (session) {
            stopCameraAndReset();
            quickCheckIn(sessionId, session.unit_name);
        }
    }
    
    function markAttendance(sessionId, unitName) {
        const scannerBox = document.getElementById('scannerBox');
        
        fetch('mark_attendance.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'session_id=' + sessionId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                scannerBox.innerHTML = `
                    <div class="qr-icon"></div>
                    <h3 style="color: #27ae60;">Attendance Marked Successfully!</h3>
                    <p>You have been checked in for <strong>${data.unit_name || unitName}</strong></p>
                    <p style="font-size: