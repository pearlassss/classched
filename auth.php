<?php
require_once 'config.php';
session_start();

$action = $_POST['action'] ?? $_GET['action'] ?? null;

// SIGNUP - Send Verification Code
if ($action === 'signup') {
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Check if email exists
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    if ($check->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email already registered']);
        exit;
    }

    // Generate verification code and set expiration (30 minutes)
    $code = generateCode();
    $expiration = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    // Save user with unverified status
    $sql = "INSERT INTO users (full_name, email, password, verified, verification_code, code_expiration) 
            VALUES ('$full_name', '$email', '$password', FALSE, '$code', '$expiration')";

    if ($conn->query($sql)) {
        $emailResult = sendVerificationEmail($email, $code, 'verification');
        echo json_encode([
            'status' => 'success',
            'message' => 'Verification code sent to your email. Check your inbox!',
            'code' => $emailResult['code'] ?? null // REMOVE IN PRODUCTION
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error creating account: ' . $conn->error]);
    }
    exit;
}

// VERIFY CODE - Complete Signup
if ($action === 'verify_signup') {
    $email = $conn->real_escape_string($_POST['email']);
    $code = $conn->real_escape_string($_POST['code']);

    $result = $conn->query("SELECT id, code_expiration FROM users WHERE email = '$email' AND verification_code = '$code' AND verified = FALSE");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if code is expired
        if (isCodeExpired($user['code_expiration'])) {
            echo json_encode(['status' => 'error', 'message' => 'Verification code expired. Please sign up again.']);
            exit;
        }
        
        // Verify user
        $conn->query("UPDATE users SET verified = TRUE, verification_code = NULL, code_expiration = NULL WHERE email = '$email'");
        echo json_encode(['status' => 'success', 'message' => 'Email verified successfully! You can now login.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid verification code']);
    }
    exit;
}

// LOGIN
if ($action === 'login') {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Try login with email OR name
    $result = $conn->query("SELECT id, full_name, email, password FROM users WHERE (email = '$email' OR full_name = '$email') AND verified = TRUE");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            echo json_encode([
                'status' => 'success', 
                'user_id' => $user['id'], 
                'name' => $user['full_name']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'User not found or email not verified']);
    }
    exit;
}

// CHECK SESSION (used to auto-login after refresh if session still valid)
if ($action === 'check_session') {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])) {
        echo json_encode([
            'status' => 'success',
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    }
    exit;
}

// FORGOT PASSWORD - Send Reset Code
if ($action === 'forgot_password') {
    $email = $conn->real_escape_string($_POST['email']);

    $result = $conn->query("SELECT id FROM users WHERE email = '$email'");

    if ($result->num_rows > 0) {
        $code = generateCode();
        $expiration = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $conn->query("UPDATE users SET verification_code = '$code', code_expiration = '$expiration' WHERE email = '$email'");
        
        $emailResult = sendVerificationEmail($email, $code, 'reset');
        echo json_encode([
            'status' => 'success',
            'message' => 'Password reset code sent to your email. Check your inbox!',
            'code' => $emailResult['code'] ?? null // REMOVE IN PRODUCTION
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email not found']);
    }
    exit;
}

// VERIFY RESET CODE & UPDATE PASSWORD
if ($action === 'reset_password') {
    $email = $conn->real_escape_string($_POST['email']);
    $code = $conn->real_escape_string($_POST['code']);
    $newPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

    $result = $conn->query("SELECT id, code_expiration FROM users WHERE email = '$email' AND verification_code = '$code'");

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if code is expired
        if (isCodeExpired($user['code_expiration'])) {
            echo json_encode(['status' => 'error', 'message' => 'Reset code expired. Please request a new one.']);
            exit;
        }
        
        $conn->query("UPDATE users SET password = '$newPassword', verification_code = NULL, code_expiration = NULL WHERE email = '$email'");
        echo json_encode(['status' => 'success', 'message' => 'Password updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid reset code']);
    }
    exit;
}

// LOGOUT
if ($action === 'logout') {
    $user_name = $_SESSION['user_name'] ?? 'User';
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => "Thank you, $user_name!"]);
    exit;
}

// CHECK ACTIVE SESSION
if ($action === 'session_check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'status' => 'success',
            'user_id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No active session']);
    }
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>