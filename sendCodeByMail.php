<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
require_once './vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['navios_user_id']) && isset($params['email'])) {
        $navios_user_id = $params['navios_user_id'];
        $email = $params['email'];

        // Delete old session codes for this user
        $sql = "DELETE FROM navios_users_sessions WHERE navios_user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $navios_user_id);
        $stmt->execute();
        $stmt->close();

        // Generate a random six-digit session code and ensure it is unique
        do {
            $session_code = rand(100000, 999999);
            $sql = "SELECT COUNT(*) as count FROM navios_users_sessions WHERE navios_users_session_code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $session_code);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } while ($result['count'] > 0);

        // Insert the new session code into navios_users_sessions
        $date_start = date('Y-m-d H:i:s');
        $session_active = 0;
        $sql = "INSERT INTO navios_users_sessions (navios_user_id, navios_users_session_code, navios_users_session_session, navios_users_session_date_start) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $navios_user_id, $session_code, $session_active, $date_start);
        $stmt->execute();
        $stmt->close();

        // Send session code via PHPMailer (very basic email)
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->SMTPDebug = 2;                                     // Enable verbose debug output
            // $mail->isSMTP();                                            // Set mailer to use SMTP
            $mail->Host = 'mail.garbrix.com';  // Specify main and backup SMTP servers
            $mail->SMTPAuth = true;                                   // Enable SMTP authentication
            $mail->Username = 'no-reply@garbrix.com';                     // SMTP username
            $mail->Password = 'Mailer123';                               // SMTP password
            $mail->SMTPSecure = 'ssl';                                  // Enable TLS encryption, `ssl` also accepted
            $mail->Port = 469;                                   // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
            $mail->CharSet = 'UTF-8';

            //Recipients
            $mail->setFrom('no-reply@garbrix.com', 'Navios');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $session_code.' es tu código de verificación de Navios';
            $mail->Body = "Tu código de verificación es: $session_code";

            $mail->send();
            echo json_encode(true);
        } catch (Exception $e) {
            echo json_encode(["message" => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"]);
        }
    } else {
        echo json_encode(false);
    }
} else {
    echo json_encode(false);
}

$conn->close();
?>