<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
require_once('db_cnn/cnn.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = [];

    // Port Point Types
    $sql = "SELECT a.navios_port_point_type_id, a.navios_port_point_type_title FROM navios_port_point_types as a WHERE a.navios_port_point_type_active=1";
    $result = $conn->query($sql);
    $data['port_point_types'] = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['port_point_types'][] = $row;
        }
    }

    // Anchorage Types
    $sql = "SELECT a.navios_port_anchorage_type_id, a.navios_port_anchorage_type_title FROM navios_port_anchorage_types as a WHERE a.navios_port_anchorage_type_active=1";
    $result = $conn->query($sql);
    $data['anchorage_types'] = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['anchorage_types'][] = $row;
        }
    }

    // Mooring Types
    $sql = "SELECT a.navios_port_mooring_type_id, a.navios_port_mooring_type_title FROM navios_port_mooring_types as a WHERE a.navios_port_mooring_type_active=1";
    $result = $conn->query($sql);
    $data['mooring_types'] = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['mooring_types'][] = $row;
        }
    }

    // Seabed Types
    $sql = "SELECT a.navios_port_seabed_type_id, a.navios_port_seabed_type_title FROM navios_port_seabed_types as a WHERE a.navios_port_seabed_type_active=1";
    $result = $conn->query($sql);
    $data['seabed_types'] = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['seabed_types'][] = $row;
        }
    }

    // Service Types
    $sql = "SELECT a.navios_port_service_type_id, a.navios_port_service_type_title FROM navios_port_service_types as a WHERE a.navios_port_service_type_active=1";
    $result = $conn->query($sql);
    $data['service_types'] = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['service_types'][] = $row;
        }
    }

    // Port Types
    $sql = "SELECT a.navios_port_type_id, a.navios_port_type_active FROM navios_port_types as a WHERE a.navios_port_type_active=1";
    $result = $conn->query($sql);
    $data['port_types'] = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data['port_types'][] = $row;
        }
    }

    echo json_encode($data);
} else {
    echo json_encode(["message" => "Invalid request method"]);
}

$conn->close();
?>