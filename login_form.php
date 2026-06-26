<?php
include 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role_id'] = $row['role_id'];
            $_SESSION['full_name'] = $row['full_name'];
            
            if ($row['role_id'] == 1) {
                header("Location: scan_page.php");
            } elseif ($row['role_id'] == 2) {
                header("Location: lecturer_dashboard.php");
            } elseif ($row['role_id'] == 3) {
                header("Location: admin_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "User not found!";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GeoQR | KCA University</title>
    <style>
        /* ===== KCA UNIVERSITY THEME ===== */
        /* Colors: Navy Blue (#1A2A4A), Gold (#C9A84C), White (#FFFFFF) */
        
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
        
        /* ===== LOGIN CONTAINER ===== */
        .login-container {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 45px 40px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            border-top: 6px solid #C9A84C;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* ===== HEADER ===== */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header .logo-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        
        .login-header .uni-name {
            font-size: 14px;
            color: #1A2A4A;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        
        .login-header .uni-name .gold {
            color: #C9A84C;
        }
        
        .login-header h1 {
            font-size: 28px;
            color: #1A2A4A;
            font-weight: 300;
            margin-top: 5px;
        }
        
        .login-header h1 span {
            font-weight: 700;
            color: #C9A84C;
        }
        
        .login-header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* ===== ERROR MESSAGE ===== */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            font-size: 14px;
            display: <?php echo isset($error) ? 'block' : 'none'; ?>;
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
        
        .form-group .input-wrapper {
            position: relative;
        }
        
        .form-group .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 16px;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 14px 14px 44px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #fafafa;
            color: #1A2A4A;
        }
        
        .form-group input:focus {
            border-color: #C9A84C;
            outline: none;
            background: #FFFFFF;
            box-shadow: 0 0 0 4px rgba(201, 168, 76, 0.15);
        }
        
        .form-group input::placeholder {
            color: #b0b0b0;
        }
        
        /* ===== LOGIN BUTTON ===== */
        .login-btn {
            width: 100%;
            padding: 14px;
            background: #C9A84C;
            color: #1A2A4A;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .login-btn:hover {
            background: #1A2A4A;
            color: #C9A84C;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(201, 168, 76, 0.35);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        /* ===== FOOTER ===== */
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }
        
        .login-footer p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .login-footer a {
            color: #1A2A4A;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .login-footer a:hover {
            color: #C9A84C;
            text-decoration: underline;
        }
        
        .login-footer .uni-footer {
            margin-top: 15px;
            font-size: 12px;
            color: #b0b0b0;
        }
        
        .login-footer .uni-footer .brand {
            color: #1A2A4A;
            font-weight: 600;
        }
        
        .login-footer .uni-footer .gold {
            color: #C9A84C;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            .login-header h1 {
                font-size: 22px;
            }
            .login-header .uni-name {
                font-size: 12px;
            }
            .form-group input {
                padding: 12px 12px 12px 38px;
                font-size: 14px;
            }
            .login-btn {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    
    <!-- ===== HEADER ===== -->
    <div class="login-header">
        <span class="logo-icon">🎓</span>
        <div class="uni-name">KCA <span class="gold">UNIVERSITY</span></div>
        <h1>Geo<span>QR</span></h1>
        <div class="subtitle">Smart Attendance Management System</div>
    </div>
    
    <!-- ===== ERROR MESSAGE ===== -->
    <div class="error-message" id="errorMessage">
        <?php echo isset($error) ? $error : ''; ?>
    </div>
    
    <!-- ===== LOGIN FORM ===== -->
    <form action="login_form.php" method="post">
        
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-wrapper">
                <span class="input-icon"></span>
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <div class="input-wrapper">
                <span class="input-icon"></span>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
        </div>
        
        <button type="submit" class="login-btn">Login</button>
        
    </form>
    
    <!-- ===== FOOTER ===== -->
    <div class="login-footer">
        <p>Don't have an account? <a href="signup_form.php">Click here</a></p>
        <div class="uni-footer">
            <span class="brand">KCA UNIVERSITY</span> • 
            <span class="gold">GeoQR</span> • 
            v1.0
        </div>
    </div>
    
</div>

</body>
</html>