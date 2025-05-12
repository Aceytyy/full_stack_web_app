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

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$skip = ($page - 1) * $limit;
$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : null;

try {
    // Match condition: any grade < 80
    $match = ["Grades" => ['$elemMatch' => ['$lt' => 80]]];
    if ($semester_id) {
        $match["SemesterID"] = $semester_id;
    }

    // Aggregation pipeline
    $pipeline = [
        ["\$match" => $match],
        ["\$lookup" => [
            "from" => "students",
            "localField" => "StudentID",
            "foreignField" => "_id",
            "as" => "student"
        ]],
        ["\$unwind" => "\$student"],
        ["\$project" => [
            "_id" => 0,
            "student_id" => "\$StudentID",
            "name" => "\$student.Name",
            "course" => "\$student.Course",
            "semester_id" => "\$SemesterID"
        ]],
        ["\$skip" => $skip],
        ["\$limit" => $limit]
    ];

    $results = iterator_to_array($db->grades->aggregate($pipeline), false);
    $total = $db->grades->countDocuments($match);

    // Fetch semesters for dropdown
    $sem_cursor = $db->semesters->find([], ['projection' => ['_id' => 1, 'Semester' => 1, 'SchoolYear' => 1]]);
    $semesters = [];
    foreach ($sem_cursor as $sem) {
        $semesters[] = [
            "id" => $sem["_id"],
            "label" => "{$sem['Semester']} - SY {$sem['SchoolYear']}"
        ];
    }

    $atRiskStudents = [];

    foreach ($results as $student) {
        $reasons = [];
        $reasons[] = "Has a grade below 80 in this semester";
        // You can add more logic if you fetch more data

        $atRiskStudents[] = [
            "student_id" => $student['student_id'],
            "name" => $student['name'],
            "course" => $student['course'],
            "semester_id" => $student['semester_id'],
            "risk_reasons" => $reasons,
        ];
    }

    echo json_encode([
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "semesters" => $semesters,
        "data" => $atRiskStudents
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
?>
