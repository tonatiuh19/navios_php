<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Validate required fields
    if (
        empty($data['navios_user_id']) ||
        empty($data['navios_port_id']) ||
        empty($data['navios_users_reservation_start_date']) ||
        empty($data['navios_users_reservation_end_date']) ||
        !isset($data['status'])
    ) {
        echo json_encode(['error' => 'Missing required fields']);
        $conn->close();
        exit;
    }

    // Insert reservation (ignore navios_users_reservation_id, it's auto-increment)
    $sql = "INSERT INTO navios_users_reservations 
        (navios_user_id, navios_port_id, navios_users_reservation_start_date, navios_users_reservation_end_date, status, navios_users_reservation_created, navios_users_reservation_stripe_id)
        VALUES (?, ?, ?, ?, ?, NOW(), ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "iissis",
        $data['navios_user_id'],
        $data['navios_port_id'],
        $data['navios_users_reservation_start_date'],
        $data['navios_users_reservation_end_date'],
        $data['status'],
        $data['navios_users_reservation_stripe_id']
    );

    if ($stmt->execute()) {
        if (!empty($data['return_all'])) {
            // Return all reservations
            $sqlAll = "SELECT navios_users_reservation_id, navios_user_id, navios_port_id, navios_users_reservation_start_date, navios_users_reservation_end_date, status, navios_users_reservation_created, navios_users_reservation_stripe_id 
                    FROM navios_users_reservations
                    ORDER BY navios_users_reservation_start_date ASC";
            $result = $conn->query($sqlAll);
            $reservations = [];
            while ($row = $result->fetch_assoc()) {
                $reservations[] = $row;
            }
            echo json_encode($reservations);
        } else {
            // Return only the inserted reservation
            $inserted_id = $stmt->insert_id;
            $sqlOne = "SELECT navios_users_reservation_id, navios_user_id, navios_port_id, navios_users_reservation_start_date, navios_users_reservation_end_date, status, navios_users_reservation_created, navios_users_reservation_stripe_id 
                    FROM navios_users_reservations
                    WHERE navios_users_reservation_id = ?";
            $stmtOne = $conn->prepare($sqlOne);
            $stmtOne->bind_param("i", $inserted_id);
            $stmtOne->execute();
            $result = $stmtOne->get_result();
            $reservation = $result->fetch_assoc();
            echo json_encode($reservation);
            $stmtOne->close();
        }
    } else {
        echo json_encode(['error' => 'Insert failed']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>