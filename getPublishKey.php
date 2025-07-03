<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $sql = "SELECT a.navios_environments_keys_key_string 
            FROM navios_environments_keys as a
            INNER JOIN navios_environments as b 
                ON b.navios_environment_type = a.navios_environments_keys_title 
                AND b.navios_environment_test = a.navios_environments_keys_test
            WHERE a.navios_environments_keys_type = 'publishable'
            LIMIT 1";

    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            "publishable_key" => $row['navios_environments_keys_key_string']
        ]);
    } else {
        echo json_encode([
            "error" => "Publishable key not found"
        ]);
    }
    
    // Free result set
    if ($result) {
        $result->free();
    }
} else {
    echo json_encode(false);
}

// Always close the connection
if ($conn) {
    $conn->close();
}
?>