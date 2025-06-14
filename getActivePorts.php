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
    // We'll filter by average rating after fetching, since it's an aggregate

    if (count($filters) > 0) {
        $sqlWhere .= " AND " . implode(" AND ", $filters);
    }

    $sql = "SELECT a.navios_port_id, a.navios_port_title, a.navios_port_price, a.navios_port_description, a.navios_port_type, a.navios_port_latitude, a.navios_port_longitude, a.navios_port_active, b.navios_port_type_title 
            FROM navios_ports as a 
            INNER JOIN navios_port_types as b ON b.navios_port_type_id = a.navios_port_type 
            $sqlWhere";

    $stmt = $conn->prepare($sql);

    // Bind parameters dynamically
    $bindTypes = implode('', $types);
    $bindValues = [];
    // Location is required
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