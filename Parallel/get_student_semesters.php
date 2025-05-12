<?php
header('Access-Control-Allow-Origin: http://localhost:5173');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
if (!$student_id) {
    echo json_encode([]);
    exit;
}

// Use correct field names and types
$semesters = $db->grades->distinct('SemesterID', ['StudentID' => $student_id]);
if (!$semesters) {
    echo json_encode([]);
    exit;
}

// Get semester details
$semester_docs = $db->semesters->find(['_id' => ['$in' => $semesters]]);
$result = [];
foreach ($semester_docs as $sem) {
    $result[] = [
        'semester_id' => $sem['_id'],
        'semester' => $sem['Semester'],
        'school_year' => $sem['SchoolYear']
    ];
}
echo json_encode($result);
