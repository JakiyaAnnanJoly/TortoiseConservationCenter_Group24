<?php
header("Content-Type: application/json");
include "../db.php";

$action = $_GET['action'] ?? '';

if ($action == "getBreedingEvents") {
    $result = $conn->query("SELECT * FROM breeding_events");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}

elseif ($action == "addBreedingEvent") {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("INSERT INTO breeding_events (breeding_id, breeding_date, offspring_count) VALUES (?,?,?)");
    $stmt->bind_param("ssi", $data['breeding_id'], $data['breeding_date'], $data['offspring_count']);
    $stmt->execute();
    echo json_encode(["success" => true]);
}

elseif ($action == "deleteBreedingEvent") {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM breeding_events WHERE id=$id");
    echo json_encode(["success" => true]);
}

elseif ($action == "getBreedingSeasons") {
    $result = $conn->query("SELECT * FROM breeding_seasons");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
}

elseif ($action == "addBreedingSeason") {
    $data = json_decode(file_get_contents("php://input"), true);
    $stmt = $conn->prepare("INSERT INTO breeding_seasons (start_month, end_month, temperature_range) VALUES (?,?,?)");
    $stmt->bind_param("sss", $data['start_month'], $data['end_month'], $data['temperature_range']);
    $stmt->execute();
    echo json_encode(["success" => true]);
}

elseif ($action == "deleteBreedingSeason") {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM breeding_seasons WHERE id=$id");
    echo json_encode(["success" => true]);
}

else {
    echo json_encode(["error" => "Invalid action"]);
}
