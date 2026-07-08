<?php
session_start();
include 'config.php';

$message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
$message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';

unset($_SESSION['message']);
unset($_SESSION['message_type']);

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_geofence'])) {
    $name = $_POST['name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $radius = $_POST['radius'];
    $unit_id = $_POST['unit_id'];
    
    $stmt = $conn->prepare("INSERT INTO geofences (name, latitude, longitude, radius, unit_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sddii", $name, $latitude, $longitude, $radius, $unit_id);
    $stmt->execute();
    $_SESSION['message'] = "Geofence created successfully!";
    $_SESSION['message_type'] = "success";

    header("Location: admin_dashboard.php");
    exit();
}

if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    
    $check = $conn->prepare("SELECT geofence_id FROM geofences WHERE geofence_id = ?");
    $check->bind_param("i", $delete_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        
        $delete = $conn->prepare("DELETE FROM geofences WHERE geofence_id = ?");
        $delete->bind_param("i", $delete_id);
        if ($delete->execute()) {
            $_SESSION['message'] = "Geofence deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting geofence: " . $delete->error;
            $_SESSION['message_type'] = "error";
        }
        $delete->close();
    } else {
        $_SESSION['message'] = "Geofence not found!";
        $_SESSION['message_type'] = "error";
    }
    $check->close();
    
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_geofence'])) {
    $geofence_id = $_POST['geofence_id'];
    $name = $_POST['name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $radius = $_POST['radius'];
    $unit_id = $_POST['unit_id'];
    
    $stmt = $conn->prepare("UPDATE geofences SET name = ?, latitude = ?, longitude = ?, radius = ?, unit_id = ? WHERE geofence_id = ?");
    $stmt->bind_param("sddiii", $name, $latitude, $longitude, $radius, $unit_id, $geofence_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Geofence updated successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error updating geofence: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['enroll_student'])) {
    $user_id = $_POST['user_id'];
    $unit_id = $_POST['unit_id'];
    
    $check = $conn->prepare("SELECT * FROM enrollment WHERE user_id = ? AND unit_id = ?");
    $check->bind_param("ii", $user_id, $unit_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows > 0) {
        $_SESSION['message'] = "Student is already enrolled in this unit!";
        $_SESSION['message_type'] = "error";
    } else {
        $stmt = $conn->prepare("INSERT INTO enrollment (user_id, unit_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $unit_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Student enrolled successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error enrolling student: " . $stmt->error;
            $_SESSION['message_type'] = "error";
        }
        $stmt->close();
    }
    $check->close();
    
    header("Location: admin_dashboard.php");
    exit();
}

if (isset($_GET['remove_enrollment'])) {
    $user_id = $_GET['user_id'];
    $unit_id = $_GET['unit_id'];
    
    $stmt = $conn->prepare("DELETE FROM enrollment WHERE user_id = ? AND unit_id = ?");
    $stmt->bind_param("ii", $user_id, $unit_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = "Student removed from unit successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error removing student: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    
    header("Location: admin_dashboard.php");
    exit();
}

$geofences = $conn->query("SELECT g.*, u.unit_name FROM geofences g LEFT JOIN units u ON g.unit_id = u.unit_id");


$units = $conn->query("SELECT unit_id, unit_name, unit_code FROM units ORDER BY unit_name ASC");
$students = $conn->query("SELECT user_id, full_name, email FROM users WHERE role_id = 1 ORDER BY full_name ASC");
$enrollments_query = "SELECT e.enrollment_id, e.user_id, e.unit_id, e.enrolled_at,
                      u.full_name as student_name, u.email as student_email,
                      un.unit_name, un.unit_code
                      FROM enrollment e
                      JOIN users u ON e.user_id = u.user_id
                      JOIN units un ON e.unit_id = un.unit_id
                      ORDER BY un.unit_name ASC, u.full_name ASC";
$enrollments = $conn->query($enrollments_query);

$enrollment_counts_query = "SELECT un.unit_id, un.unit_name, un.unit_code, 
                            COUNT(e.user_id) as student_count
                            FROM units un
                            LEFT JOIN enrollment e ON un.unit_id = e.unit_id
                            GROUP BY un.unit_id
                            ORDER BY un.unit_name ASC";
$enrollment_counts = $conn->query($enrollment_counts_query);

$total_students = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 1")->fetch_assoc()['count'];
$total_units = $conn->query("SELECT COUNT(*) as count FROM units")->fetch_assoc()['count'];
$total_enrollments = $conn->query("SELECT COUNT(*) as count FROM enrollment")->fetch_assoc()['count'];

$edit_geofence = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_query = $conn->prepare("SELECT * FROM geofences WHERE geofence_id = ?");
    $edit_query->bind_param("i", $edit_id);
    $edit_query->execute();
    $edit_result = $edit_query->get_result();
    $edit_geofence = $edit_result->fetch_assoc();
    $edit_query->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - GeoQR</title>
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
        
        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-logo .logo-icon {
            font-size: 32px;
        }
        
        .header-logo .logo-text {
            font-size: 14px;
            opacity: 0.7;
            letter-spacing: 2px;
        }
        
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
        
        /* ===== MESSAGES ===== */
        .message { 
            padding: 14px 20px; 
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
        
        .stats-grid {
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
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: 700;
            color: #1A2A4A;
        }
        
        .stat-card .label {
            color: #7f8c8d;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .three-col { 
            display: grid; 
            grid-template-columns: 1fr 1fr 1fr; 
            gap: 25px; 
        }
        
        @media (max-width: 992px) { 
            .three-col { 
                grid-template-columns: 1fr 1fr; 
            } 
        }
        
        @media (max-width: 768px) { 
            .three-col { 
                grid-template-columns: 1fr; 
            } 
        }
        
        .card { 
            background: #FFFFFF; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); 
            border-top: 4px solid #C9A84C;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 25px;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .card h2 { 
            color: #1A2A4A; 
            font-size: 20px; 
            margin-bottom: 20px; 
            padding-bottom: 12px; 
            border-bottom: 2px solid #f0f2f5;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card h2 .badge {
            background: #C9A84C;
            color: #1A2A4A;
            font-size: 12px;
            padding: 2px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        input, select { 
            width: 100%; 
            padding: 12px 15px; 
            margin-bottom: 12px; 
            border: 2px solid #e0e0e0; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: #fafafa;
        }
        
        input:focus, select:focus { 
            border-color: #C9A84C; 
            outline: none;
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.15);
        }
        
        .btn { 
            background: #1A2A4A; 
            color: #FFFFFF; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 6px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
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
        
        .btn-danger {
            background: #dc3545;
            color: #FFFFFF;
        }
        
        .btn-danger:hover {
            background: #c82333;
            color: #FFFFFF;
        }
        
        .btn-sm {
            padding: 6px 14px;
            font-size: 13px;
            width: auto;
        }
        
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
        
        tr:hover { 
            background: #f8f9fa; 
        }
        
        .delete-link { 
            color: #dc3545; 
            text-decoration: none; 
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            background: #f8d7da;
            display: inline-block;
            font-size: 13px;
        }
        
        .delete-link:hover { 
            background: #dc3545; 
            color: #FFFFFF;
            text-decoration: none;
        }
        
        .edit-link {
            color: #1A2A4A;
            text-decoration: none;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            background: #e8f4fd;
            display: inline-block;
            font-size: 13px;
        }
        
        .edit-link:hover {
            background: #C9A84C;
            color: #FFFFFF;
            text-decoration: none;
        }
        
        .remove-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            padding: 4px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            background: #f8d7da;
            display: inline-block;
            font-size: 12px;
        }
        
        .remove-link:hover {
            background: #dc3545;
            color: #FFFFFF;
            text-decoration: none;
        }
        
        .no-data { 
            color: #95a5a6; 
            text-align: center; 
            padding: 30px; 
            font-style: italic;
        }
        
        .count-badge {
            background: #1A2A4A;
            color: #FFFFFF;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .count-badge.gold {
            background: #C9A84C;
            color: #1A2A4A;
        }
        
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
            .header h1 { font-size: 20px; }
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
        
        .unit-code {
            font-weight: 600;
            color: #1A2A4A;
        }
        
        .unit-name-small {
            font-size: 11px;
            color: #95a5a6;
            display: block;
        }
        
        .edit-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #C9A84C;
        }
        
        .edit-form h3 {
            color: #1A2A4A;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .edit-form .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        @media (max-width: 768px) {
            .edit-form .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        .edit-form .btn-cancel {
            background: #95a5a6;
            color: #FFFFFF;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .edit-form .btn-cancel:hover {
            background: #7f8c8d;
        }
        
        .edit-form .btn-update {
            background: #C9A84C;
            color: #1A2A4A;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .edit-form .btn-update:hover {
            background: #1A2A4A;
            color: #C9A84C;
        }
    </style>
</head>
<body>
<div class="container">
    
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
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?php echo $total_students; ?></div>
            <div class="label">Total Students</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $total_units; ?></div>
            <div class="label">Total Units</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $total_enrollments; ?></div>
            <div class="label">Total Enrollments</div>
        </div>
        <div class="stat-card">
            <div class="number"><?php echo $geofences->num_rows; ?></div>
            <div class="label">Geofences</div>
        </div>
    </div>
    
    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($edit_geofence): ?>
        <div class="card">
            <div class="edit-form">
                <h3>Edit Geofence</h3>
                <form method="post">
                    <input type="hidden" name="geofence_id" value="<?php echo $edit_geofence['geofence_id']; ?>">
                    
                    <div class="form-row">
                        <div>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($edit_geofence['name']); ?>" placeholder="Geofence Name" required>
                        </div>
                        <div>
                            <input type="number" step="0.000001" name="latitude" value="<?php echo $edit_geofence['latitude']; ?>" placeholder="Latitude" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div>
                            <input type="number" step="0.000001" name="longitude" value="<?php echo $edit_geofence['longitude']; ?>" placeholder="Longitude" required>
                        </div>
                        <div>
                            <input type="number" name="radius" value="<?php echo $edit_geofence['radius']; ?>" placeholder="Radius in meters" required>
                        </div>
                    </div>
                    
                    <select name="unit_id" required>
                        <option value="">Select Unit</option>
                        <?php 
                     
                        $units->data_seek(0);
                        while ($unit = $units->fetch_assoc()): 
                            $selected = ($unit['unit_id'] == $edit_geofence['unit_id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $unit['unit_id']; ?>" <?php echo $selected; ?>>
                                <?php echo htmlspecialchars($unit['unit_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="submit" name="update_geofence" class="btn-update">Update Geofence</button>
                        <a href="admin_dashboard.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="three-col">
        
        <div class="card">
            <h2>Create Geofence</h2>
            <form method="post">
                <input type="text" name="name" placeholder="Geofence Name (e.g., Main Campus)" required>
                <input type="number" step="0.000001" name="latitude" placeholder="Latitude (e.g., -1.286389)" required>
                <input type="number" step="0.000001" name="longitude" placeholder="Longitude (e.g., 36.817223)" required>
                <input type="number" name="radius" placeholder="Radius in meters (e.g., 100)" required>
                <select name="unit_id" required>
                    <option value="">Select Unit</option>
                    <?php 
                    $units->data_seek(0);
                    while ($unit = $units->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $unit['unit_id']; ?>">
                            <?php echo htmlspecialchars($unit['unit_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" name="create_geofence" class="btn btn-success">Create Geofence</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Enroll Student</h2>
            
            <form method="post">
                <select name="user_id" required>
                    <option value="">Select Student</option>
                    <?php 

                    $students->data_seek(0);
                    while ($student = $students->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $student['user_id']; ?>">
                            <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['email'] . ')'); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <select name="unit_id" required>
                    <option value="">Select Unit</option>
                    <?php 

                    $units->data_seek(0);
                    while ($unit = $units->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $unit['unit_id']; ?>">
                            <?php echo htmlspecialchars($unit['unit_code'] . ' - ' . $unit['unit_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <button type="submit" name="enroll_student" class="btn btn-success">Enroll Student</button>
            </form>

            <h3 style="margin-top: 20px; color: #1A2A4A; font-size: 16px;">Enrollment Counts</h3>
            <?php if ($enrollment_counts->num_rows > 0): ?>
                <?php while ($ec = $enrollment_counts->fetch_assoc()): ?>
                    <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #ecf0f1; font-size: 14px;">
                        <span>
                            <?php echo htmlspecialchars($ec['unit_code']); ?> - <?php echo htmlspecialchars($ec['unit_name']); ?>
                        </span>
                        <span class="count-badge gold">
                            <?php echo $ec['student_count']; ?> student<?php echo $ec['student_count'] != 1 ? 's' : ''; ?>
                        </span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">No units found.</div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Enrolled Students</h2>
            
            <?php if ($enrollments->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Unit</th>
                        <th>Action</th>
                    </tr>
                    <?php 
                    // Reset enrollments for this table
                    $enrollments->data_seek(0);
                    $count = 1;
                    while ($enrollment = $enrollments->fetch_assoc()): 
                    ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <span class="student-name"><?php echo htmlspecialchars($enrollment['student_name']); ?></span>
                                <span class="student-id">ID: <?php echo $enrollment['user_id']; ?> • <?php echo htmlspecialchars($enrollment['student_email']); ?></span>
                            </td>
                            <td>
                                <span class="unit-code"><?php echo htmlspecialchars($enrollment['unit_code']); ?></span>
                                <span class="unit-name-small"><?php echo htmlspecialchars($enrollment['unit_name']); ?></span>
                            </td>
                            <td>
                                <a href="admin_dashboard.php?remove_enrollment=1&user_id=<?php echo $enrollment['user_id']; ?>&unit_id=<?php echo $enrollment['unit_id']; ?>" 
                                   onclick="return confirm('Are you sure you want to remove <?php echo addslashes($enrollment['student_name']); ?> (ID: <?php echo $enrollment['user_id']; ?>) from <?php echo addslashes($enrollment['unit_name']); ?>?')" 
                                   class="remove-link">
                                   Remove
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div class="no-data">No students enrolled yet.</div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <div class="two-col">
        
        <div class="card">
            <h2>Existing Geofences <span class="badge"><?php echo $geofences->num_rows; ?></span></h2>
            <?php if ($geofences->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Radius</th>
                        <th>Actions</th>
                    </tr>
                    <?php while ($geo = $geofences->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($geo['name']); ?></td>
                        <td><?php echo htmlspecialchars($geo['unit_name'] ?? 'Unassigned'); ?></td>
                        <td><?php echo $geo['radius']; ?>m</td>
                        <td>
                            <a href="admin_dashboard.php?edit_id=<?php echo $geo['geofence_id']; ?>" class="edit-link">Edit</a>
                            <a href="admin_dashboard.php?delete_id=<?php echo $geo['geofence_id']; ?>" 
                               onclick="return confirm('Are you sure you want to delete geofence: <?php echo addslashes($geo['name']); ?>? This action cannot be undone!')" 
                               class="delete-link">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div class="no-data">No geofences created yet.</div>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Recent Enrollments</h2>
            <?php 
            // Reset the enrollments result set and store in array
            $enrollments->data_seek(0);
            $enrollments_list = [];
            while ($row = $enrollments->fetch_assoc()) {
                $enrollments_list[] = $row;
            }

            $recent_enrollments = array_slice($enrollments_list, 0, 10);
            ?>
            <?php if (count($recent_enrollments) > 0): ?>
                <table>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Unit</th>
                        <th>Date</th>
                    </tr>
                    <?php $count = 1; foreach ($recent_enrollments as $enrollment): ?>
                        <tr>
                            <td><?php echo $count++; ?></td>
                            <td>
                                <span class="student-name"><?php echo htmlspecialchars($enrollment['student_name']); ?></span>
                                <span class="student-id">ID: <?php echo $enrollment['user_id']; ?></span>
                            </td>
                            <td>
                                <span class="unit-code"><?php echo htmlspecialchars($enrollment['unit_code']); ?></span>
                                <span class="unit-name-small"><?php echo htmlspecialchars($enrollment['unit_name']); ?></span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($enrollment['enrolled_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="no-data">No enrollment history yet.</div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <!-- ===== FOOTER ===== -->
    <!--<div class="footer">
        <span class="brand">KCA UNIVERSITY</span> • 
        <span class="gold">GeoQR</span> • 
        Smart Attendance Management System-->
    </div>
    
</div>
</body>
</html>