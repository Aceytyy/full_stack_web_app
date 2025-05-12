<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// --- ADD THIS: Connect to MongoDB ---
require 'vendor/autoload.php';
$client = new MongoDB\Client("mongodb://localhost:27017");
$db = $client->CSELEC3DB;

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$skip = ($page - 1) * $limit;

$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : null;
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'subject_code';
$order = (isset($_GET['sort_order']) && strtolower($_GET['sort_order']) === 'asc') ? 1 : -1;

try {
    $match = [];
    if ($semester_id) {
        $match["semester_id"] = $semester_id;
    }

    $pipeline = [];

    if (!empty($match)) {
        $pipeline[] = ["\$match" => $match];
    }

    $pipeline = array_merge($pipeline, [
        ["\$project" => [
            "_id" => 0,
            "subject_code" => 1,
            "subject_description" => 1,
            "semester_id" => 1,
            "average_grade" => ["\$round" => ["\$average_grade", 2]],
            "passing_rate" => ["\$round" => ["\$passing_rate", 2]],
            "at_risk_rate" => ["\$round" => ["\$at_risk_rate", 2]],
            "top_grade" => 1
        ]],
        ["\$sort" => [$sort_by => $order]],
        ["\$skip" => $skip],
        ["\$limit" => $limit]
    ]);

    $cursor = $db->class_averages->aggregate($pipeline);
    $results = iterator_to_array($cursor, false);

    // Set nulls to 0 for passing_rate and at_risk_rate, and add failing_rate
    foreach ($results as &$row) {
        if (!isset($row['passing_rate']) || $row['passing_rate'] === null) $row['passing_rate'] = 0;
        if (!isset($row['at_risk_rate']) || $row['at_risk_rate'] === null) $row['at_risk_rate'] = 0;
        $row['failing_rate'] = 1 - $row['passing_rate'];
    }
    unset($row);

    // Total count for pagination
    $total = $db->class_averages->countDocuments($match);

    // Fetch all semesters for dropdown
    $sem_cursor = $db->semesters->find([], ['projection' => ['_id' => 1, 'Semester' => 1, 'SchoolYear' => 1]]);
    $semesters = [];
    foreach ($sem_cursor as $s) {
        $semesters[] = [
            "id" => $s["_id"],
            "label" => $s["Semester"] . " - SY " . $s["SchoolYear"],
            "Semester" => $s["Semester"],
            "SchoolYear" => $s["SchoolYear"]
        ];
    }

    echo json_encode([
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "semesters" => $semesters,
        "data" => $results
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
?>
