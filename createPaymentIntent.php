<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

require_once('vendor/autoload.php');
require_once('db_cnn/cnn.php'); // Include your database connection

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $amount = $data['amount'];
    $customerId = isset($data['customer_id']) ? $data['customer_id'] : null;

    // Query to get the API key from the database
    $sql = "SELECT a.key_string 
            FROM platforms_keys as a
            INNER JOIN platforms_environments as b on b.type = a.title AND b.test = a.test
            WHERE a.type = 'secret'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $apiKey = $row['key_string'];

        \Stripe\Stripe::setApiKey($apiKey); // Set the Stripe API key from the database

        try {
            $paymentIntentData = [
                'amount' => $amount,
                'currency' => 'mxn',
            ];

            if ($customerId) {
                $paymentIntentData['customer'] = $customerId;
            }

            $paymentIntent = \Stripe\PaymentIntent::create($paymentIntentData);

            echo json_encode(['clientSecret' => $paymentIntent->client_secret]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'API key not found']);
    }

    $conn->close();
} else {
    echo json_encode(['message' => 'Invalid request method']);
}
?>