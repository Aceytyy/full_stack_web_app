<?php
require_once 'mongo_connection.php';
header('Content-Type: application/json');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$skip = ($page - 1) * $limit;
$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : null;

try {
    $match = [];
    if ($semester_id) {
        $match["semester_id"] = $semester_id;
    }

    // Aggregation pipeline with pagination
    $pipeline = [];

    if (!empty($match)) {
        $pipeline[] = ["\$match" => $match];
    }

    $pipeline = array_merge($pipeline, [
        ["\$project" => [
            "_id" => 0,
            "student_id" => 1,
            "gpa" => 1,
            "zscore" => 1,
            "semester_id" => 1
        ]],
        ["\$skip" => $skip],
        ["\$limit" => $limit]
    ]);

    $cursor = $db->student_gpas->aggregate($pipeline);
    $results = iterator_to_array($cursor, false);

    // Total count for pagination
    $total = $db->student_gpas->countDocuments($match);

    echo json_encode([
        "page" => $page,
        "limit" => $limit,
        "total" => $total,
        "data" => $results
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
?>
