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

    // Check if date is already in Y-m-d format
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return $date;
    }

    $dateParts = explode(' de ', $date);
    $day = $dateParts[0];
    $month = $months[$dateParts[1]];
    $year = $dateParts[2];

    return "$year-$month-$day";
}

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    if (isset($params['navios_user_id'])) {
        $user_id = $params['navios_user_id'];
        
        // Build dynamic update query
        $updateFields = [];
        $types = "";
        $values = [];

        $allowedFields = [
            'navios_user_full_name' => 's',
            'navios_user_date_of_birth' => 's',
            'navios_user_email' => 's',
            'navios_user_phone_number' => 's',
            'navios_user_phone_number_code' => 'i',
            'navios_user_country_code' => 's',
            'navios_user_type' => 'i',
            'navios_user_active' => 'i'
        ];

        foreach ($allowedFields as $field => $type) {
            if (isset($params[$field])) {
                $updateFields[] = "$field = ?";
                $types .= $type;
                
                if ($field === 'navios_user_date_of_birth') {
                    $values[] = convertDateOfBirth($params[$field]);
                } else {
                    $values[] = $params[$field];
                }
            }
        }

        if (!empty($updateFields)) {
            // Update Stripe customer if email or name changed
            if (isset($params['navios_user_email']) || isset($params['navios_user_full_name'])) {
                // Get current user data to get stripe_id
                $sql = "SELECT navios_user_stripe_id FROM navios_users WHERE navios_user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $userResult = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($userResult && $userResult['navios_user_stripe_id']) {
                    // Get Stripe API key
                    $sql = "SELECT a.navios_environments_keys_key_string 
                            FROM navios_environments_keys as a
                            INNER JOIN navios_environments as b 
                                ON b.navios_environment_type = a.navios_environments_keys_title 
                                AND b.navios_environment_test = a.navios_environments_keys_test
                            WHERE a.navios_environments_keys_type = 'secret'";
                    $keyResult = $conn->query($sql);

                    if ($keyResult && $keyResult->num_rows > 0) {
                        $keyRow = $keyResult->fetch_assoc();
                        $secretKey = $keyRow['navios_environments_keys_key_string'];

                        // Update Stripe customer
                        $stripe = new StripeClient($secretKey);
                        $updateData = [];
                        
                        if (isset($params['navios_user_email'])) {
                            $updateData['email'] = $params['navios_user_email'];
                        }
                        if (isset($params['navios_user_full_name'])) {
                            $updateData['name'] = $params['navios_user_full_name'];
                        }
                        
                        if (!empty($updateData)) {
                            $stripe->customers->update($userResult['navios_user_stripe_id'], $updateData);
                        }
                    }
                }
            }

            // Update database
            $sql = "UPDATE navios_users SET " . implode(', ', $updateFields) . " WHERE navios_user_id = ?";
            $stmt = $conn->prepare($sql);
            
            $types .= "i";
            $values[] = $user_id;
            
            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                // Fetch and return updated user data
                $sql = "SELECT navios_user_id, navios_user_email, navios_user_full_name, navios_user_date_of_birth, 
                               navios_user_phone_number, navios_user_phone_number_code, navios_user_country_code, 
                               navios_user_stripe_id, navios_user_type, navios_user_created, navios_user_active 
                        FROM navios_users WHERE navios_user_id = ?";
                $stmt2 = $conn->prepare($sql);
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $userData = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();

                echo json_encode($userData);
            } else {
                echo json_encode(["error" => "Failed to update user"]);
            }
            $stmt->close();
        } else {
            echo json_encode(["error" => "No valid fields to update"]);
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