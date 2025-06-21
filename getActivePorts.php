<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $requestBody = file_get_contents('php://input');
    $params = json_decode($requestBody, true);

    // Require location filter
    if (empty($params['location']) || 
        !isset($params['location']['lat_min']) || 
        !isset($params['location']['lat_max']) || 
        !isset($params['location']['lng_min']) || 
        !isset($params['location']['lng_max'])) {
        echo json_encode(["message" => "Location filter is required"]);
        $conn->close();
        exit;
    }

    // Filters
    $filters = [];
    $types = [];
    $sqlWhere = "WHERE a.navios_port_active = 1";

    // Location is required
    $filters[] = "(a.navios_port_latitude BETWEEN ? AND ? AND a.navios_port_longitude BETWEEN ? AND ?)";
    $types[] = "dddd";

    if (!empty($params['navios_port_title'])) {
        $filters[] = "a.navios_port_title LIKE ?";
        $types[] = "s";
    }
    if (!empty($params['navios_port_type'])) {
        $filters[] = "a.navios_port_type = ?";
        $types[] = "i";
    }

    if (count($filters) > 0) {
        $sqlWhere .= " AND " . implode(" AND ", $filters);
    }

    $sql = "SELECT a.navios_port_id, a.navios_port_title, a.navios_port_price, a.navios_port_place, a.navios_port_description, a.navios_port_type, a.navios_port_latitude, a.navios_port_longitude, a.navios_port_active, b.navios_port_type_title 
            FROM navios_ports as a 
            INNER JOIN navios_port_types as b ON b.navios_port_type_id = a.navios_port_type 
            $sqlWhere";

    $stmt = $conn->prepare($sql);

    // Bind parameters dynamically
    $bindTypes = implode('', $types);
    $bindValues = [];
    $bindValues[] = $params['location']['lat_min'];
    $bindValues[] = $params['location']['lat_max'];
    $bindValues[] = $params['location']['lng_min'];
    $bindValues[] = $params['location']['lng_max'];
    if (!empty($params['navios_port_title'])) {
        $bindValues[] = "%" . $params['navios_port_title'] . "%";
    }
    if (!empty($params['navios_port_type'])) {
        $bindValues[] = $params['navios_port_type'];
    }
    $stmt->bind_param($bindTypes, ...$bindValues);

    $stmt->execute();
    $result = $stmt->get_result();

    $ports = [];
    while ($row = $result->fetch_assoc()) {
        $port_id = $row['navios_port_id'];
        $skip = false;

        // Anchorage type filter (array)
        if (!empty($params['navios_port_anchorage_type_id']) && is_array($params['navios_port_anchorage_type_id'])) {
            $in = implode(',', array_fill(0, count($params['navios_port_anchorage_type_id']), '?'));
            $sqlCheck = "SELECT 1 FROM navios_port_anchorages WHERE navios_port_id=? AND navios_port_anchorage_type_id IN ($in) LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $typesCheck = str_repeat('i', count($params['navios_port_anchorage_type_id']) + 1);
            $bindCheck = array_merge([$port_id], $params['navios_port_anchorage_type_id']);
            $stmtCheck->bind_param($typesCheck, ...$bindCheck);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows == 0) $skip = true;
            $stmtCheck->close();
        }

        // Mooring type filter (array)
        if (!empty($params['navios_port_mooring_type_id']) && is_array($params['navios_port_mooring_type_id'])) {
            $in = implode(',', array_fill(0, count($params['navios_port_mooring_type_id']), '?'));
            $sqlCheck = "SELECT 1 FROM navios_port_moorings WHERE navios_port_id=? AND navios_port_mooring_type_id IN ($in) LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $typesCheck = str_repeat('i', count($params['navios_port_mooring_type_id']) + 1);
            $bindCheck = array_merge([$port_id], $params['navios_port_mooring_type_id']);
            $stmtCheck->bind_param($typesCheck, ...$bindCheck);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows == 0) $skip = true;
            $stmtCheck->close();
        }

        // Point type filter (array)
        if (!empty($params['navios_port_point_type_id']) && is_array($params['navios_port_point_type_id'])) {
            $in = implode(',', array_fill(0, count($params['navios_port_point_type_id']), '?'));
            $sqlCheck = "SELECT 1 FROM navios_port_points WHERE navios_port_id=? AND navios_port_point_type_id IN ($in) LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $typesCheck = str_repeat('i', count($params['navios_port_point_type_id']) + 1);
            $bindCheck = array_merge([$port_id], $params['navios_port_point_type_id']);
            $stmtCheck->bind_param($typesCheck, ...$bindCheck);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows == 0) $skip = true;
            $stmtCheck->close();
        }

        // Seabed type filter (array)
        if (!empty($params['navios_port_seabed_type_id']) && is_array($params['navios_port_seabed_type_id'])) {
            $in = implode(',', array_fill(0, count($params['navios_port_seabed_type_id']), '?'));
            $sqlCheck = "SELECT 1 FROM navios_port_seabeds WHERE navios_port_id=? AND navios_port_seabed_type_id IN ($in) LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $typesCheck = str_repeat('i', count($params['navios_port_seabed_type_id']) + 1);
            $bindCheck = array_merge([$port_id], $params['navios_port_seabed_type_id']);
            $stmtCheck->bind_param($typesCheck, ...$bindCheck);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows == 0) $skip = true;
            $stmtCheck->close();
        }

        // Service type filter (array)
        if (!empty($params['navios_port_service_type_id']) && is_array($params['navios_port_service_type_id'])) {
            $in = implode(',', array_fill(0, count($params['navios_port_service_type_id']), '?'));
            $sqlCheck = "SELECT 1 FROM navios_port_services WHERE navios_port_id=? AND navios_port_service_type_id IN ($in) LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $typesCheck = str_repeat('i', count($params['navios_port_service_type_id']) + 1);
            $bindCheck = array_merge([$port_id], $params['navios_port_service_type_id']);
            $stmtCheck->bind_param($typesCheck, ...$bindCheck);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if ($stmtCheck->num_rows == 0) $skip = true;
            $stmtCheck->close();
        }

        if ($skip) continue;

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

        // Get ratings and comments for this port
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

    // Filter by average_rating if requested
    if (isset($params['average_rating'])) {
        $ports = array_filter($ports, function($port) use ($params) {
            return isset($port['average_rating']) && $port['average_rating'] >= floatval($params['average_rating']);
        });
        $ports = array_values($ports);
    }

    echo json_encode($ports);
} else {
    echo json_encode(["message" => "Invalid request method"]);
}

$conn->close();
?>