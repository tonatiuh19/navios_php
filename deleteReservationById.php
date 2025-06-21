<?php
// filepath: /Users/felixgomez/Code/navios_php/deleteReservation.php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (empty($data['navios_users_reservation_id'])) {
        echo json_encode(['error' => 'Missing navios_users_reservation_id']);
        $conn->close();
        exit;
    }

    $sql = "DELETE FROM navios_users_reservations WHERE navios_users_reservation_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $data['navios_users_reservation_id']);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'deleted_id' => $data['navios_users_reservation_id']]);
        } else {
            echo json_encode(['error' => 'Reservation not found']);
        }
    } else {
        echo json_encode(['error' => 'Delete failed']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>