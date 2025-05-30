<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');
require_once './vendor/autoload.php';

use Stripe\StripeClient;

$method = $_SERVER['REQUEST_METHOD'];

function convertDateOfBirth($date)
{
    $months = [
        'enero' => '01',
        'febrero' => '02',
        'marzo' => '03',
        'abril' => '04',
        'mayo' => '05',
        'junio' => '06',
        'julio' => '07',
        'agosto' => '08',
        'septiembre' => '09',
        'octubre' => '10',
        'noviembre' => '11',
        'diciembre' => '12'
    ];

    $dateParts = explode(' de ', $date);
    $day = $dateParts[0];
    $month = $months[$dateParts[1]];
    $year = $dateParts[2];

    return "$year-$month-$day";
}

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (
        isset($params['navios_user_full_name']) &&
        isset($params['navios_user_date_of_birth']) &&
        isset($params['navios_user_email']) &&
        isset($params['navios_user_country_code']) &&
        isset($params['navios_user_type'])
    ) {
        $full_name = $params['navios_user_full_name'];
        $date_of_birth = convertDateOfBirth($params['navios_user_date_of_birth']);
        $email = $params['navios_user_email'];
        $country_code = $params['navios_user_country_code'];
        $type = $params['navios_user_type'];
        $date_created = date('Y-m-d H:i:s');
        $active = 1;

        // Query to get the secret API key from the database
        $sql = "SELECT a.navios_environments_keys_key_string 
                FROM navios_environments_keys as a
                INNER JOIN navios_environments as b 
                    ON b.navios_environment_type = a.navios_environments_keys_title 
                    AND b.navios_environment_test = a.navios_environments_keys_test
                WHERE a.navios_environments_keys_type = 'secret'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $secretKey = $row['navios_environments_keys_key_string'];

            // Create Stripe customer
            $stripe = new StripeClient($secretKey);
            $customer = $stripe->customers->create([
                'name' => $full_name,
                'email' => $email
            ]);
            $stripe_id = $customer->id;

            // Insert data into navios_users
            $sql = "INSERT INTO navios_users (
                        navios_user_email, 
                        navios_user_full_name, 
                        navios_user_date_of_birth, 
                        navios_user_country_code, 
                        navios_user_stripe_id, 
                        navios_user_type, 
                        navios_user_created, 
                        navios_user_active
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssi",
                $email,
                $full_name,
                $date_of_birth,
                $country_code,
                $stripe_id,
                $type,
                $date_created,
                $active
            );

            if ($stmt->execute()) {
                // Fetch and return the user data
                $sql = "SELECT a.navios_user_id, a.navios_user_email, a.navios_user_full_name, a.navios_user_date_of_birth, a.navios_user_phone_number, a.navios_user_phone_number_code, a.navios_user_country_code, a.navios_user_stripe_id, a.navios_user_type, a.navios_user_created, a.navios_user_active 
                        FROM navios_users as a
                        WHERE a.navios_user_email = ?";
                $stmt2 = $conn->prepare($sql);
                $stmt2->bind_param("s", $email);
                $stmt2->execute();
                $userData = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();

                echo json_encode($userData);
            } else {
                echo json_encode(false);
            }
            $stmt->close();
        } else {
            echo json_encode(['error' => 'API key not found']);
        }
    } else {
        echo json_encode(false);
    }
} else {
    echo json_encode(false);
}

$conn->close();
?>