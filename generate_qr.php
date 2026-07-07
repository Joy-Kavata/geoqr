<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login_form.php");
    exit();
}

$session_id = isset($_GET['session_id']) ? $_GET['session_id'] : 0;

if (!$session_id) {
    die("No session specified.");
}

$session_query = "SELECT s.session_id, s.start_time, s.end_time, u.unit_name, u.unit_code 
                  FROM attendance_sessions s
                  JOIN units u ON s.unit_id = u.unit_id
                  WHERE s.session_id = ?";
$stmt = $conn->prepare($session_query);
$stmt->bind_param("i", $session_id);
$stmt->execute();
$session_result = $stmt->get_result();
$session = $session_result->fetch_assoc();

if (!$session) {
    die("Session not found.");
}

$conn->close();

$base_url = "https://geogr.great-site.net/GeoQr/";

$qr_data = $base_url . "scan_page.php?session_id=" . $session_id;
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);

$now = time();
$end_time = strtotime($session['end_time']);
$session_expired = $now > $end_time;
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Code - GeoQR | KCA University</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #1A2A4A 0%, #2C3E6A 50%, #1A2A4A 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px; 
        }
        
        .qr-container {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 45px 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border-top: 6px solid #C9A84C;
            text-align: center;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .qr-container .logo { font-size: 48px; margin-bottom: 10px; }
        .qr-container .uni-name {
            font-size: 14px;
            color: #1A2A4A;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .qr-container .uni-name .gold { color: #C9A84C; }
        .qr-container h1 {
            font-size: 28px;
            color: #1A2A4A;
            font-weight: 300;
            margin-top: 5px;
        }
        .qr-container h1 span { font-weight: 700; color: #C9A84C; }
        .qr-container h2 {
            color: #1A2A4A;
            font-size: 18px;
            margin: 15px 0;
        }
        
        .qr-container .session-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 4px solid #C9A84C;
        }
        .qr-container .session-info p {
            color: #555;
            font-size: 14px;
            margin: 5px 0;
        }
        .qr-container .session-info strong { color: #1A2A4A; }
        
        .qr-container .qr-image {
            background: white;
            padding: 20px;
            border-radius: 12px;
            display: inline-block;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin: 15px 0;
        }
        .qr-container .qr-image img { max-width: 250px; height: auto; }
        
        .qr-container .instructions {
            background: #e8f4fd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 14px;
            color: #2c3e50;
            border-left: 4px solid #3498db;
            text-align: left;
        }
        .qr-container .instructions ol { padding-left: 20px; margin-top: 5px; }
        .qr-container .instructions ol li { margin: 5px 0; }
        
        .network-info {
            background: #e8f4fd;
            padding: 10px 15px;
            border-radius: 6px;
            margin: 10px 0;
            font-size: 12px;
            color: #555;
            border-left: 4px solid #3498db;
            text-align: left;
        }
        .network-info strong { color: #1A2A4A; }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #C9A84C;
            color: #1A2A4A;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin: 5px;
            border: 2px solid #C9A84C;
        }
        .btn:hover {
            background: #1A2A4A;
            color: #C9A84C;
            transform: translateY(-2px);
        }
        .btn-outline {
            background: transparent;
            color: #1A2A4A;
            border: 2px solid #1A2A4A;
        }
        .btn-outline:hover {
            background: #1A2A4A;
            color: #FFFFFF;
        }
        
        .expired-warning {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            border-left: 4px solid #dc3545;
            margin: 10px 0;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ecf0f1;
            font-size: 12px;
            color: #95a5a6;
        }
        .footer .brand { color: #1A2A4A; font-weight: 600; }
        .footer .gold { color: #C9A84C; }
        
        @media (max-width: 480px) {
            .qr-container { padding: 30px 20px; }
            .qr-container h1 { font-size: 22px; }
            .qr-container .qr-image img { max-width: 180px; }
        }
    </style>
</head>
<body>
<div class="qr-container">
    
    <div class="logo">🎓</div>
    <div class="uni-name">KCA <span class="gold">UNIVERSITY</span></div>
    <h1>Geo<span>QR</span></h1>
    
    <h2>Attendance QR Code</h2>
    
    <div class="session-info">
        <p><strong>Unit:</strong> <?php echo htmlspecialchars($session['unit_name']); ?> (<?php echo htmlspecialchars($session['unit_code']); ?>)</p>
        <p><strong>Date:</strong> <?php echo date('l, M d, Y', strtotime($session['start_time'])); ?></p>
        <p><strong>Time:</strong> <?php echo date('H:i', strtotime($session['start_time'])); ?> - <?php echo date('H:i', strtotime($session['end_time'])); ?></p>
        <p><strong>Session ID:</strong> <?php echo $session_id; ?></p>
    </div>
    
    <div class="network-info">
        <strong>Scan this QR code:</strong><br>
        <?php echo $base_url; ?>
    </div>
    
    <div class="qr-image">
        <img src="<?php echo $qr_code_url; ?>" alt="QR Code for Attendance">
    </div>
    
    <?php if ($session_expired): ?>
        <div class="expired-warning">
            <strong>Session has ended!</strong> This QR code is no longer valid.
        </div>
    <?php endif; ?>
    
    <div class="instructions">
        <strong>How it works:</strong>
        <ol>
            <li>Display this QR code to your students</li>
            <li>Students scan the code using their phone camera</li>
            <li>They will be directed to mark their attendance</li>
            <li><strong>Students must be physically in class</strong></li>
            <li>QR code expires when session ends</li>
        </ol>
    </div>
    
    <div style="margin-top: 15px;">
        <a href="lecturer_dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        <a href="#" onclick="window.location.reload();" class="btn">Refresh QR Code</a>
    </div>
    
    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 6px; font-size: 13px; color: #856404; border-left: 4px solid #ffc107; text-align: left;">
        <strong>Security Notice:</strong> This QR code is session-specific and expires when the session ends. 
        Do not share or download this code. Students must scan directly from the screen.
    </div>
    
    <div class="footer">
        <span class="brand">KCA UNIVERSITY</span> • 
        <span class="gold">GeoQR</span> • 
        Smart Attendance Management System
    </div>
    
</div>
</body>
</html>