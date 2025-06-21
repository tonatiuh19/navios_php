<?php
// filepath: /Users/felixgomez/Code/navios_php/updateReservation.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, PUT');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required fields
    if (
        empty($data['navios_users_reservation_id']) ||
        empty($data['navios_user_id']) ||
        empty($data['navios_port_id']) ||
        empty($data['navios_users_reservation_start_date']) ||
        empty($data['navios_users_reservation_end_date']) ||
        !isset($data['status']) ||
        empty($data['navios_users_reservation_stripe_id'])
    ) {
        echo json_encode(['error' => 'Missing required fields']);
        $conn->close();
        exit;
    }

    // Update reservation
    $sql = "UPDATE navios_users_reservations SET 
                navios_user_id = ?, 
                navios_port_id = ?, 
                navios_users_reservation_start_date = ?, 
                navios_users_reservation_end_date = ?, 
                status = ?, 
                navios_users_reservation_stripe_id = ?
            WHERE navios_users_reservation_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iissisi",
        $data['navios_user_id'],
        $data['navios_port_id'],
        $data['navios_users_reservation_start_date'],
        $data['navios_users_reservation_end_date'],
        $data['status'],
        $data['navios_users_reservation_stripe_id'],
        $data['navios_users_reservation_id']
    );

    if ($stmt->execute()) {
        // Return the updated reservation
        $sqlOne = "SELECT navios_users_reservation_id, navios_user_id, navios_port_id, navios_users_reservation_start_date, navios_users_reservation_end_date, status, navios_users_reservation_created, navios_users_reservation_stripe_id 
                   FROM navios_users_reservations
                   WHERE navios_users_reservation_id = ?";
        $stmtOne = $conn->prepare($sqlOne);
        $stmtOne->bind_param("i", $data['navios_users_reservation_id']);
        $stmtOne->execute();
        $result = $stmtOne->get_result();
        $reservation = $result->fetch_assoc();
        echo json_encode($reservation);
        $stmtOne->close();
    } else {
        echo json_encode(['error' => 'Update failed']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>