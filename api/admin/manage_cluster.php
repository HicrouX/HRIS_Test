<?php
require_once '../config/db.php';
$data = json_decode(file_get_contents("php://input"));
// finalize cluster approval
$stmt = $pdo->prepare("UPDATE team_cluster SET approved_by = ? WHERE team_id = ?");
$stmt->execute([$data->admin_id, $data->team_id]);
echo json_encode(["message" => "Team cluster updated"]);
?>