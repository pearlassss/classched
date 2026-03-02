<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'classs schedule db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Set charset
$conn->set_charset('utf8');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Function to generate verification code
function generateCode() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ✅ Real email sender using PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure PHPMailer is installed

function sendVerificationEmail($email, $code, $purpose = 'verification') {
    $mail = new PHPMailer(true);

    try {
        // SMTP settings (Gmail SMTP)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'prestozamariellepearl@gmail.com'; // Your Gmail
        $mail->Password = 'mduldmvurhitjlet';                // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Email details
        $mail->setFrom('prestozamariellepearl@gmail.com', 'Class Schedule Web');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        // Different emails for verification vs password reset
        if ($purpose === 'verification') {
            $mail->Subject = '📧 Email Verification Code - Class Schedule';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9fafb; border-radius: 10px;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                        <h1 style='color: white; margin: 0;'>📚 Class Schedule System</h1>
                    </div>
                    <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px;'>
                        <h2 style='color: #1f2937; margin-bottom: 20px;'>Email Verification</h2>
                        <p style='color: #6b7280; font-size: 16px; line-height: 1.6;'>
                            Thank you for signing up! Please use the verification code below to complete your registration:
                        </p>
                        <div style='background: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; margin: 30px 0;'>
                            <p style='color: #6b7280; font-size: 14px; margin-bottom: 10px;'>Your Verification Code:</p>
                            <h1 style='color: #667eea; font-size: 42px; letter-spacing: 8px; margin: 0;'>$code</h1>
                        </div>
                        <p style='color: #ef4444; font-size: 14px; margin-top: 20px;'>
                            ⏰ This code will expire in <strong>30 minutes</strong>.
                        </p>
                        <p style='color: #6b7280; font-size: 14px; margin-top: 20px;'>
                            If you didn't request this verification code, please ignore this email.
                        </p>
                    </div>
                    <div style='text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;'>
                        <p>Class Schedule System © 2024</p>
                    </div>
                </div>
            ";
        } else {
            $mail->Subject = '🔑 Password Reset Code - Class Schedule';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background: #f9fafb; border-radius: 10px;'>
                    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px 10px 0 0; text-align: center;'>
                        <h1 style='color: white; margin: 0;'>📚 Class Schedule System</h1>
                    </div>
                    <div style='background: white; padding: 30px; border-radius: 0 0 10px 10px;'>
                        <h2 style='color: #1f2937; margin-bottom: 20px;'>Password Reset Request</h2>
                        <p style='color: #6b7280; font-size: 16px; line-height: 1.6;'>
                            We received a request to reset your password. Use the code below to create a new password:
                        </p>
                        <div style='background: #f3f4f6; padding: 20px; border-radius: 8px; text-align: center; margin: 30px 0;'>
                            <p style='color: #6b7280; font-size: 14px; margin-bottom: 10px;'>Your Reset Code:</p>
                            <h1 style='color: #667eea; font-size: 42px; letter-spacing: 8px; margin: 0;'>$code</h1>
                        </div>
                        <p style='color: #ef4444; font-size: 14px; margin-top: 20px;'>
                            ⏰ This code will expire in <strong>30 minutes</strong>.
                        </p>
                        <p style='color: #6b7280; font-size: 14px; margin-top: 20px;'>
                            If you didn't request a password reset, please ignore this email. Your password will remain unchanged.
                        </p>
                    </div>
                    <div style='text-align: center; padding: 20px; color: #9ca3af; font-size: 12px;'>
                        <p>Class Schedule System © 2024</p>
                    </div>
                </div>
            ";
        }

        $mail->send();
        return ['status' => 'success', 'message' => 'Verification code sent to your email! Check your inbox.'];
        
    } catch (Exception $e) {
        // Log error for debugging
        error_log("PHPMailer Error: {$mail->ErrorInfo}");
        return ['status' => 'error', 'message' => "Failed to send email: {$mail->ErrorInfo}"];
    }
}

// Function to check if verification code is expired
function isCodeExpired($expiration) {
    if (!$expiration) return true;
    return strtotime($expiration) < time();
}

?>
