<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
header('Content-Type: application/json');

require_once 'mongo_connection.php';

$school_year = isset($_GET['school_year']) ? intval($_GET['school_year']) : null;

try {
    // Fetch all semesters for the selected school year
    $semesters = iterator_to_array($db->semesters->find(['SchoolYear' => $school_year], [
        'projection' => ['_id' => 1, 'Semester' => 1, 'SchoolYear' => 1]
    ]), false);

    // Map semester_id to name
    $semesterMap = [];
    foreach ($semesters as $sem) {
        $semesterMap[$sem['_id']] = $sem['Semester'];
    }

    // Fetch metrics for these semesters
    $metrics = iterator_to_array($db->semester_metrics->find([
        'semester_id' => ['$in' => array_keys($semesterMap)]
    ]), false);

    // Build response
    $data = [];
    foreach ($metrics as $m) {
        $data[] = [
            'semester' => $semesterMap[$m['semester_id']] ?? $m['semester_id'],
            'average_grade' => round($m['average_grade'], 6),
            'passing_rate' => round($m['passing_rate'], 2),
            'at_risk_rate' => round($m['at_risk_rate'], 2),
            'top_grade' => $m['top_grade'] ?? 0,
            'count_90_100' => $m['count_90_100'] ?? 0,
            'count_80_89' => $m['count_80_89'] ?? 0,
            'count_70_79' => $m['count_70_79'] ?? 0,
            'count_lt_70' => $m['count_lt_70'] ?? 0
        ];
    }

    echo json_encode([
        'school_year' => $school_year,
        'semesters' => $data
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
?> 