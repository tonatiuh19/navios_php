<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['email'])) {
        $email = $params['email'];

        // Query to check if the user exists by email
        $sql = "SELECT a.navios_user_id, a.navios_user_email, a.navios_user_full_name, a.navios_user_date_of_birth, a.navios_user_phone_number, a.navios_user_phone_number_code, a.navios_user_country_code, a.navios_user_stripe_id, a.navios_user_type, a.navios_user_created, a.navios_user_active  
                FROM navios_users as a
                WHERE a.navios_user_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $userData = $result->fetch_assoc();
            echo json_encode($userData);
        } else {
            echo json_encode(false);
        }
        $stmt->close();
    } else {
        echo json_encode(["message" => "Invalid input data"]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}

$conn->close();
?>