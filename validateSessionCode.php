<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
require_once './vendor/autoload.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['navios_user_id']) && isset($params['code'])) {
        $navios_user_id = $params['navios_user_id'];
        $code = $params['code'];

        // Fetch the session code from navios_users_sessions
        $sql = "SELECT a.navios_users_session_code, a.navios_users_session_session 
                FROM navios_users_sessions as a 
                WHERE a.navios_user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $navios_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $sessionData = $result->fetch_assoc();
            $session_code = $sessionData['navios_users_session_code'];
            $session_active = $sessionData['navios_users_session_session'];
        } else {
            echo json_encode(false);
            exit;
        }
        $stmt->close();

        // Validate the session code
        if ($code == $session_code) {
            // Update the session to true (1)
            $sql = "UPDATE navios_users_sessions SET navios_users_session_session = 1 WHERE navios_user_id = ? AND navios_users_session_code = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $navios_user_id, $code);
            $stmt->execute();
            $stmt->close();

            echo json_encode(true);
        } else {
            echo json_encode(false);
        }
    } else {
        echo json_encode(false);
    }
} else {
    echo json_encode(false);
}

$conn->close();
?>