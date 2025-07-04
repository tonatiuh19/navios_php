<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['navios_user_id'])) {
        $user_id = $params['navios_user_id'];
        
        // Get user data
        $sql = "SELECT navios_user_id, navios_user_email, navios_user_full_name, navios_user_date_of_birth, 
                       navios_user_phone_number, navios_user_phone_number_code, navios_user_country_code, 
                       navios_user_stripe_id, navios_user_type, navios_user_created, navios_user_active 
                FROM navios_users WHERE navios_user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($userData) {
            echo json_encode($userData);
        } else {
            echo json_encode(["error" => "User not found"]);
        }
    } else {
        echo json_encode(["error" => "navios_user_id is required"]);
    }
} else {
    echo json_encode(["error" => "Invalid request method"]);
}

if ($conn) {
    $conn->close();
}
?>