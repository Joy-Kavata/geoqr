<?php
include 'config.php';

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $conf_pass = $_POST['confirm_password'];

    if (empty($full_name) || empty($email) || empty($pass)) {
        $error = "Please input all required fields!";
    } elseif ($pass !== $conf_pass) {
        $error = "Passwords do not match!";
    //} elseif (strlen($pass) < 8) {
        //$error = "Password must be at least 8 characters!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    } else {
        // Check if email already exists
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();

        if ($check_email->num_rows > 0) {
            $error = "Email already exists! Please use a different email.";
        } else {
            $role_id = 1; // Default role: student
            $password_hash = password_hash($pass, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $full_name, $email, $password_hash, $role_id);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login_form.php' style='color: #C9A84C; font-weight: bold;'>login</a>.";
            } else {
                $error = "Registration failed: " . $stmt->error;
            }
            $stmt->close();
        }
        $check_email->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - GeoQR | KCA University</title>
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
        
        /* ===== SIGNUP CONTAINER ===== */
        .signup-container {
            background: #FFFFFF;
            border-radius: 16px;
            padding: 45px 40px;
            max-width: 460px;
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
        .signup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .signup-header .logo-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 10px;
        }
        
        .signup-header .uni-name {
            font-size: 14px;
            color: #1A2A4A;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        
        .signup-header .uni-name .gold {
            color: #C9A84C;
        }
        
        .signup-header h1 {
            font-size: 28px;
            color: #1A2A4A;
            font-weight: 300;
            margin-top: 5px;
        }
        
        .signup-header h1 span {
            font-weight: 700;
            color: #C9A84C;
        }
        
        .signup-header .subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        /* ===== MESSAGES ===== */
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            font-size: 14px;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            font-size: 14px;
            display: <?php echo $success ? 'block' : 'none'; ?>;
        }
        
        .success-message a {
            color: #C9A84C;
            font-weight: bold;
            text-decoration: none;
        }
        
        .success-message a:hover {
            text-decoration: underline;
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
        
        /* ===== SIGNUP BUTTON ===== */
        .signup-btn {
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
        
        .signup-btn:hover {
            background: #1A2A4A;
            color: #C9A84C;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(201, 168, 76, 0.35);
        }
        
        .signup-btn:active {
            transform: translateY(0);
        }
        
        /* ===== FOOTER ===== */
        .signup-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
        }
        
        .signup-footer p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .signup-footer a {
            color: #1A2A4A;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .signup-footer a:hover {
            color: #C9A84C;
            text-decoration: underline;
        }
        
        .signup-footer .uni-footer {
            margin-top: 15px;
            font-size: 12px;
            color: #b0b0b0;
        }
        
        .signup-footer .uni-footer .brand {
            color: #1A2A4A;
            font-weight: 600;
        }
        
        .signup-footer .uni-footer .gold {
            color: #C9A84C;
        }
        
        /* ===== PASSWORD HINT ===== */
        .password-hint {
            font-size: 12px;
            color: #95a5a6;
            margin-top: -8px;
            margin-bottom: 10px;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .signup-container {
                padding: 30px 20px;
            }
            .signup-header h1 {
                font-size: 22px;
            }
            .signup-header .uni-name {
                font-size: 12px;
            }
            .form-group input {
                padding: 12px 12px 12px 38px;
                font-size: 14px;
            }
            .signup-btn {
                font-size: 16px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>

<div class="signup-container">
    
    <!-- ===== HEADER ===== -->
    <div class="signup-header">
        <span class="logo-icon">🎓</span>
        <div class="uni-name">KCA <span class="gold">UNIVERSITY</span></div>
        <h1>Geo<span>QR</span></h1>
        <div class="subtitle">Create Your Account</div>
    </div>
    
    <!-- ===== ERROR MESSAGE ===== -->
    <?php if ($error): ?>
        <div class="error-message" style="display: <?php echo $error ? 'block' : 'none'; ?>;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- ===== SUCCESS MESSAGE ===== -->
    <?php if ($success): ?>
        <div class="success-message"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- ===== SIGNUP FORM ===== -->
    <form action="signup_form.php" method="post">
        
        <div class="form-group">
            <p style="margin-bottom: 15px; color: #7f8c8d;">Please fill this form to create an account!</p>
            <label>Full Name</label>
            <div class="input-wrapper">
                <span class="input-icon"></span>
                <input type="text" name="full_name" placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Email Address</label>
            <div class="input-wrapper">
                <span class="input-icon"></span>
                <input type="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Password</label>
            <div class="input-wrapper">
                <span class="input-icon"></span>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Confirm Password</label>
            <div class="input-wrapper">
                <span class="input-icon"></span>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
        </div>
        
        <button type="submit" class="signup-btn">Sign Up</button>
        
    </form>
    
    <!-- ===== FOOTER ===== -->
    <div class="signup-footer">
        <p>Already have an account? <a href="login_form.php">Login here</a></p>
        <!--<div class="uni-footer">
            <span class="brand">KCA UNIVERSITY</span> • 
            <span class="gold">GeoQR</span> • 
            v1.0-
        </div>-->
    </div>
    
</div>

</body>
</html>