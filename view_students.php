<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login_form.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$unit_id = isset($_GET['unit_id']) ? $_GET['unit_id'] : 0;

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

$students_query = "SELECT u.user_id, u.full_name, u.email, 
                   (SELECT COUNT(*) FROM attendance_logs al 
                    JOIN attendance_sessions s ON al.session_id = s.session_id 
                    WHERE s.unit_id = ? AND al.user_id = u.user_id) as attended,
                   (SELECT COUNT(*) FROM attendance_sessions WHERE unit_id = ?) as total_sessions
                   FROM users u
                   JOIN enrollment e ON u.user_id = e.user_id
                   WHERE e.unit_id = ? AND u.role_id = 1
                   ORDER BY u.full_name ASC";

$stmt = $conn->prepare($students_query);
$stmt->bind_param("iii", $unit_id, $unit_id, $unit_id);
$stmt->execute();
$students = $stmt->get_result();

$total_students = $students->num_rows;

$total_sessions_query = "SELECT COUNT(*) as total FROM attendance_sessions WHERE unit_id = ?";
$stmt = $conn->prepare($total_sessions_query);
$stmt->bind_param("i", $unit_id);
$stmt->execute();
$total_sessions_result = $stmt->get_result();
$total_sessions = $total_sessions_result->fetch_assoc()['total'] ?? 0;
$stmt->close();

$avg_attendance = 0;
if ($total_students > 0 && $total_sessions > 0) {
    $avg_query = "SELECT AVG(attended) as avg_att FROM (
        SELECT (SELECT COUNT(*) FROM attendance_logs al 
                JOIN attendance_sessions s ON al.session_id = s.session_id 
                WHERE s.unit_id = ? AND al.user_id = u.user_id) as attended
        FROM users u
        WHERE u.role_id = 1
    ) as subquery";
    $stmt = $conn->prepare($avg_query);
    $stmt->bind_param("i", $unit_id);
    $stmt->execute();
    $avg_result = $stmt->get_result();
    $avg_row = $avg_result->fetch_assoc();
    $avg_attendance = round($avg_row['avg_att'] ?? 0);
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Students - <?php echo htmlspecialchars($unit_name); ?></title>
    <style>
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f0f2f5; 
            padding: 20px; 
        }
        .container { max-width: 1000px; margin: 0 auto; }
        
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
        .header h1 { font-size: 24px; font-weight: 300; }
        .header h1 span { font-weight: 600; color: #C9A84C; }
        .header h2 { font-size: 16px; font-weight: normal; opacity: 0.8; margin-top: 5px; }
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
            margin-bottom: 20px; 
            padding-bottom: 12px; 
            border-bottom: 2px solid #f0f2f5;
            font-size: 18px;
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); 
            gap: 15px; 
            margin-bottom: 25px; 
        }
        .stat-box { 
            background: #FFFFFF; 
            padding: 20px; 
            border-radius: 10px; 
            text-align: center; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-top: 4px solid #C9A84C;
            transition: transform 0.2s;
        }
        .stat-box:hover { transform: translateY(-3px); }
        .stat-box .number { font-size: 28px; font-weight: 700; color: #1A2A4A; }
        .stat-box .label { font-size: 13px; color: #7f8c8d; margin-top: 5px; }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 14px;
        }
        th, td { 
            padding: 12px 10px; 
            text-align: left; 
            border-bottom: 1px solid #ecf0f1; 
        }
        th { 
            background: #f8f9fa; 
            font-weight: 600; 
            color: #1A2A4A;
            border-bottom: 2px solid #C9A84C;
        }
        tr:hover { background: #f8f9fa; }
        
        .attendance-bar { 
            width: 100%; 
            height: 8px; 
            background: #ecf0f1; 
            border-radius: 4px; 
            overflow: hidden; 
            margin-top: 5px; 
        }
        .attendance-bar .fill { 
            height: 100%; 
            background: #27ae60; 
            border-radius: 4px; 
            transition: width 0.5s ease; 
        }
        .attendance-bar .fill.low { background: #e74c3c; }
        .attendance-bar .fill.medium { background: #f39c12; }
        
        .no-data { 
            color: #95a5a6; 
            text-align: center; 
            padding: 30px; 
            font-style: italic;
        }
        
        .back-link { 
            display: inline-block; 
            color: #C9A84C; 
            text-decoration: none; 
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .back-link:hover { 
            color: #1A2A4A; 
            text-decoration: underline; 
        }
        
        .student-id {
            font-size: 11px;
            color: #95a5a6;
            display: block;
        }
        .student-name {
            font-weight: 600;
            color: #1A2A4A;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            .header h1 { font-size: 20px; }
        }
        
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
    </style>
</head>
<body>
<div class="container">
    
    <div class="header">
        <div>
            <h1>Enrolled Students</h1>
            <h2><?php echo htmlspecialchars($unit_name); ?></h2>
        </div>
        <a href="lecturer_dashboard.php" class="back">← Dashboard</a>
    </div>

    <div class="stats-grid">
        <div class="stat-box">
            <div class="number"><?php echo $total_students; ?></div>
            <div class="label">Total Students</div>
        </div>
        <div class="stat-box">
            <div class="number"><?php echo $total_sessions; ?></div>
            <div class="label">Total Sessions</div>
        </div>
        <?php if ($total_students > 0 && $total_sessions > 0): ?>
            <div class="stat-box">
                <div class="number"><?php echo $avg_attendance; ?>%</div>
                <div class="label">Avg Attendance</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h3>Student List</h3>
        <?php if ($students->num_rows > 0): ?>
            <table>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Email</th>
                    <th>Sessions Attended</th>
                    <th>Attendance</th>
                </tr>
                <?php $count = 1; while ($student = $students->fetch_assoc()): 
                    $attended = $student['attended'] ?? 0;
                    $total = $total_sessions > 0 ? $total_sessions : 0;
                    $percentage = $total > 0 ? round(($attended / $total) * 100) : 0;
                    $bar_class = $percentage >= 80 ? '' : ($percentage >= 50 ? 'medium' : 'low');
                ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td>
                            <span class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></span>
                            <span class="student-id">ID: <?php echo $student['user_id']; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo $attended; ?> / <?php echo $total; ?></td>
                        <td style="min-width: 120px;">
                            <?php echo $percentage; ?>%
                            <div class="attendance-bar">
                                <div class="fill <?php echo $bar_class; ?>" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <p style="margin-top: 15px; color: #7f8c8d; font-size: 13px;">
                Total: <?php echo $students->num_rows; ?> students enrolled
            </p>
        <?php else: ?>
            <div class="no-data">No students enrolled in this unit yet.</div>
        <?php endif; ?>
    </div>

    <a href="lecturer_dashboard.php" class="back-link">← Back to Dashboard</a>
    
    <!--
    <div class="footer">
        <span class="brand">KCA UNIVERSITY</span> • 
        <span class="gold">GeoQR</span> • 
        Smart Attendance Management System
    </div> -->
    
</div>
</body>
</html>