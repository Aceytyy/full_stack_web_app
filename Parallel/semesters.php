<?php
require_once 'mongo_connection.php';
header('Content-Type: application/json');

try {
    $semesters_cursor = $db->semesters->find([], ['projection' => ['_id' => 1, 'Semester' => 1, 'SchoolYear' => 1]]);

    $semesters = [];
    foreach ($semesters_cursor as $sem) {
        $semesters[] = [
            "id" => $sem['_id'],
            "label" => $sem['Semester'] . " - SY " . $sem['SchoolYear'],
            "Semester" => $sem['Semester'],
            "SchoolYear" => $sem['SchoolYear']
        ];
    }

    echo json_encode([
        "total" => count($semesters),
        "semesters" => $semesters
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
