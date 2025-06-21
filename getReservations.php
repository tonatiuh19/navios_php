<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (empty($data['navios_user_id'])) {
        echo json_encode(['error' => 'Missing navios_user_id']);
        $conn->close();
        exit;
    }

    $sql = "SELECT navios_users_reservation_id, navios_user_id, navios_port_id, navios_users_reservation_start_date, navios_users_reservation_end_date, status, navios_users_reservation_created, navios_users_reservation_stripe_id 
            FROM navios_users_reservations
            WHERE navios_user_id = ?";

    $types = "i";
    $params = [$data['navios_user_id']];

    if (isset($data['status'])) {
        $sql .= " AND status = ?";
        $types .= "i";
        $params[] = $data['status'];
    }

    $sql .= " ORDER BY navios_users_reservation_start_date ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $reservations = [];
    while ($row = $result->fetch_assoc()) {
        // Get all info of the port for this reservation
        $sqlPort = "SELECT a.*, b.navios_port_type_title 
                    FROM navios_ports as a
                    INNER JOIN navios_port_types as b ON b.navios_port_type_id = a.navios_port_type
                    WHERE a.navios_port_id = ?";
        $stmtPort = $conn->prepare($sqlPort);
        $stmtPort->bind_param("i", $row['navios_port_id']);
        $stmtPort->execute();
        $portResult = $stmtPort->get_result();
        $port = $portResult->fetch_assoc();
        $stmtPort->close();

        // Anchorages
        $sqlAnchorages = "SELECT a.navios_port_anchorage_id, a.navios_port_anchorage_type_id, a.navios_port_anchorage_created, b.navios_port_anchorage_type_title
                          FROM navios_port_anchorages as a
                          INNER JOIN navios_port_anchorage_types as b ON b.navios_port_anchorage_type_id=a.navios_port_anchorage_type_id
                          WHERE a.navios_port_id=?";
        $stmtAnchorages = $conn->prepare($sqlAnchorages);
        $stmtAnchorages->bind_param("i", $row['navios_port_id']);
        $stmtAnchorages->execute();
        $anchoragesResult = $stmtAnchorages->get_result();
        $anchorages = [];
        while ($anchorageRow = $anchoragesResult->fetch_assoc()) {
            $anchorages[] = $anchorageRow;
        }
        $stmtAnchorages->close();
        $port['anchorages'] = $anchorages;

        // Moorings
        $sqlMoorings = "SELECT a.navios_port_mooring_id, a.navios_port_mooring_type_id, a.navios_port_mooring_created, b.navios_port_mooring_type_title
                        FROM navios_port_moorings as a
                        INNER JOIN navios_port_mooring_types as b ON b.navios_port_mooring_type_id=a.navios_port_mooring_type_id
                        WHERE a.navios_port_id=?";
        $stmtMoorings = $conn->prepare($sqlMoorings);
        $stmtMoorings->bind_param("i", $row['navios_port_id']);
        $stmtMoorings->execute();
        $mooringsResult = $stmtMoorings->get_result();
        $moorings = [];
        while ($mooringRow = $mooringsResult->fetch_assoc()) {
            $moorings[] = $mooringRow;
        }
        $stmtMoorings->close();
        $port['moorings'] = $moorings;

        // Points
        $sqlPoints = "SELECT a.navios_port_point_id, a.navios_port_point_type_id, a.navios_port_point_created, b.navios_port_point_type_title
                      FROM navios_port_points as a
                      INNER JOIN navios_port_point_types as b ON b.navios_port_point_type_id=a.navios_port_point_type_id
                      WHERE a.navios_port_id=?";
        $stmtPoints = $conn->prepare($sqlPoints);
        $stmtPoints->bind_param("i", $row['navios_port_id']);
        $stmtPoints->execute();
        $pointsResult = $stmtPoints->get_result();
        $points = [];
        while ($pointRow = $pointsResult->fetch_assoc()) {
            $points[] = $pointRow;
        }
        $stmtPoints->close();
        $port['points'] = $points;

        // Seabeds
        $sqlSeabeds = "SELECT a.navios_port_seabed_id, a.navios_port_seabed_type_id, a.navios_port_seabed_created, b.navios_port_seabed_type_title
                       FROM navios_port_seabeds as a
                       INNER JOIN navios_port_seabed_types as b ON b.navios_port_seabed_type_id=a.navios_port_seabed_type_id
                       WHERE a.navios_port_id=?";
        $stmtSeabeds = $conn->prepare($sqlSeabeds);
        $stmtSeabeds->bind_param("i", $row['navios_port_id']);
        $stmtSeabeds->execute();
        $seabedsResult = $stmtSeabeds->get_result();
        $seabeds = [];
        while ($seabedRow = $seabedsResult->fetch_assoc()) {
            $seabeds[] = $seabedRow;
        }
        $stmtSeabeds->close();
        $port['seabeds'] = $seabeds;

        // Services
        $sqlServices = "SELECT a.navios_port_service_id, a.navios_port_service_type_id, a.navios_port_service_created, b.navios_port_service_type_title
                        FROM navios_port_services as a
                        INNER JOIN navios_port_service_types as b ON b.navios_port_service_type_id=a.navios_port_service_type_id
                        WHERE a.navios_port_id=?";
        $stmtServices = $conn->prepare($sqlServices);
        $stmtServices->bind_param("i", $row['navios_port_id']);
        $stmtServices->execute();
        $servicesResult = $stmtServices->get_result();
        $services = [];
        while ($serviceRow = $servicesResult->fetch_assoc()) {
            $services[] = $serviceRow;
        }
        $stmtServices->close();
        $port['services'] = $services;

        // Images
        $allImages = [
            "https://t3.ftcdn.net/jpg/03/91/30/54/360_F_391305437_W3R9yZLAJTkYQ3aAAWmAZhTkPdwXdPOz.jpg",
            "https://media.gq.com.mx/photos/60b0fcbefe7c1331bb811feb/master/pass/PLAYA.jpg",
            "https://data.pixiz.com/output/user/frame/preview/api/big/1/1/5/5/3185511_0ad1e.jpg",
            "https://upload.wikimedia.org/wikipedia/commons/6/68/Muelle_Miraflores_%281%29.JPG",
            "https://vanguardia.com.mx/binrepository/1200x680/0c0/0d0/down-right/11604/WFYW/muelle-de-matanchen-nayarit_1-4941957_20230224173913.jpg"
        ];
        $randomImages = $allImages;
        shuffle($randomImages);
        $port['images'] = array_slice($randomImages, 0, 3);

        $row['port'] = $port;
        $reservations[] = $row;
    }

    echo json_encode($reservations);
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
?>