<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // You can set the number of random ports to return
    $limit = 10;
    if (isset($_GET['limit']) && is_numeric($_GET['limit'])) {
        $limit = intval($_GET['limit']);
    }

    $sql = "SELECT a.*, b.navios_port_type_title 
            FROM navios_ports as a 
            INNER JOIN navios_port_types as b ON b.navios_port_type_id = a.navios_port_type 
            WHERE a.navios_port_active = 1
            ORDER BY RAND()
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $ports = [];
    while ($row = $result->fetch_assoc()) {
        $port_id = $row['navios_port_id'];

        // Anchorages
        $sqlAnchorages = "SELECT a.navios_port_anchorage_id, a.navios_port_anchorage_type_id, a.navios_port_anchorage_created, b.navios_port_anchorage_type_title
                          FROM navios_port_anchorages as a
                          INNER JOIN navios_port_anchorage_types as b ON b.navios_port_anchorage_type_id=a.navios_port_anchorage_type_id
                          WHERE a.navios_port_id=?";
        $stmtAnchorages = $conn->prepare($sqlAnchorages);
        $stmtAnchorages->bind_param("i", $port_id);
        $stmtAnchorages->execute();
        $anchoragesResult = $stmtAnchorages->get_result();
        $anchorages = [];
        while ($anchorageRow = $anchoragesResult->fetch_assoc()) {
            $anchorages[] = $anchorageRow;
        }
        $stmtAnchorages->close();
        $row['anchorages'] = $anchorages;

        // Moorings
        $sqlMoorings = "SELECT a.navios_port_mooring_id, a.navios_port_mooring_type_id, a.navios_port_mooring_created, b.navios_port_mooring_type_title
                        FROM navios_port_moorings as a
                        INNER JOIN navios_port_mooring_types as b ON b.navios_port_mooring_type_id=a.navios_port_mooring_type_id
                        WHERE a.navios_port_id=?";
        $stmtMoorings = $conn->prepare($sqlMoorings);
        $stmtMoorings->bind_param("i", $port_id);
        $stmtMoorings->execute();
        $mooringsResult = $stmtMoorings->get_result();
        $moorings = [];
        while ($mooringRow = $mooringsResult->fetch_assoc()) {
            $moorings[] = $mooringRow;
        }
        $stmtMoorings->close();
        $row['moorings'] = $moorings;

        // Points
        $sqlPoints = "SELECT a.navios_port_point_id, a.navios_port_point_type_id, a.navios_port_point_created, b.navios_port_point_type_title
                      FROM navios_port_points as a
                      INNER JOIN navios_port_point_types as b ON b.navios_port_point_type_id=a.navios_port_point_type_id
                      WHERE a.navios_port_id=?";
        $stmtPoints = $conn->prepare($sqlPoints);
        $stmtPoints->bind_param("i", $port_id);
        $stmtPoints->execute();
        $pointsResult = $stmtPoints->get_result();
        $points = [];
        while ($pointRow = $pointsResult->fetch_assoc()) {
            $points[] = $pointRow;
        }
        $stmtPoints->close();
        $row['points'] = $points;

        // Seabeds
        $sqlSeabeds = "SELECT a.navios_port_seabed_id, a.navios_port_seabed_type_id, a.navios_port_seabed_created, b.navios_port_seabed_type_title
                       FROM navios_port_seabeds as a
                       INNER JOIN navios_port_seabed_types as b ON b.navios_port_seabed_type_id=a.navios_port_seabed_type_id
                       WHERE a.navios_port_id=?";
        $stmtSeabeds = $conn->prepare($sqlSeabeds);
        $stmtSeabeds->bind_param("i", $port_id);
        $stmtSeabeds->execute();
        $seabedsResult = $stmtSeabeds->get_result();
        $seabeds = [];
        while ($seabedRow = $seabedsResult->fetch_assoc()) {
            $seabeds[] = $seabedRow;
        }
        $stmtSeabeds->close();
        $row['seabeds'] = $seabeds;

        // Services
        $sqlServices = "SELECT a.navios_port_service_id, a.navios_port_service_type_id, a.navios_port_service_created, b.navios_port_service_type_title
                        FROM navios_port_services as a
                        INNER JOIN navios_port_service_types as b ON b.navios_port_service_type_id=a.navios_port_service_type_id
                        WHERE a.navios_port_id=?";
        $stmtServices = $conn->prepare($sqlServices);
        $stmtServices->bind_param("i", $port_id);
        $stmtServices->execute();
        $servicesResult = $stmtServices->get_result();
        $services = [];
        while ($serviceRow = $servicesResult->fetch_assoc()) {
            $services[] = $serviceRow;
        }
        $stmtServices->close();
        $row['services'] = $services;

        // Ratings & Comments
        $sqlRatings = "SELECT navios_ports_ratings_id, navios_port_id, navios_ports_ratings_rate, navios_ports_ratings_rate_comment, navios_ports_ratings_created 
                       FROM navios_ports_ratings 
                       WHERE navios_port_id = ?";
        $stmtRatings = $conn->prepare($sqlRatings);
        $stmtRatings->bind_param("i", $port_id);
        $stmtRatings->execute();
        $ratingsResult = $stmtRatings->get_result();

        $ratings = [];
        $comments = [];
        $sum = 0;
        $count = 0;
        while ($ratingRow = $ratingsResult->fetch_assoc()) {
            $ratings[] = $ratingRow;
            if (is_numeric($ratingRow['navios_ports_ratings_rate'])) {
                $sum += floatval($ratingRow['navios_ports_ratings_rate']);
                $count++;
            }
            if (!empty($ratingRow['navios_ports_ratings_rate_comment'])) {
                $comments[] = [
                    'comment' => $ratingRow['navios_ports_ratings_rate_comment'],
                    'created' => $ratingRow['navios_ports_ratings_created']
                ];
            }
        }
        $stmtRatings->close();

        $average_rating = $count > 0 ? round($sum / $count, 2) : null;

        $row['average_rating'] = $average_rating;
        $row['comments'] = $comments;
        $allImages = [
            "https://t3.ftcdn.net/jpg/03/91/30/54/360_F_391305437_W3R9yZLAJTkYQ3aAAWmAZhTkPdwXdPOz.jpg",
            "https://media.gq.com.mx/photos/60b0fcbefe7c1331bb811feb/master/pass/PLAYA.jpg",
            "https://data.pixiz.com/output/user/frame/preview/api/big/1/1/5/5/3185511_0ad1e.jpg",
            "https://upload.wikimedia.org/wikipedia/commons/6/68/Muelle_Miraflores_%281%29.JPG",
            "https://vanguardia.com.mx/binrepository/1200x680/0c0/0d0/down-right/11604/WFYW/muelle-de-matanchen-nayarit_1-4941957_20230224173913.jpg"
        ];

        // Pick 3 random images for each port
        $randomImages = $allImages;
        shuffle($randomImages);
        $row['images'] = array_slice($randomImages, 0, 3);

        $ports[] = $row;
    }
    $stmt->close();

    echo json_encode($ports);
} else {
    echo json_encode(["message" => "Invalid request method"]);
}

if ($conn) {
    $conn->close();
}
?>