<?php
require 'mongo_connection.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$skip = ($page - 1) * $limit;
$semester_id = isset($_GET['semester_id']) ? (int)$_GET['semester_id'] : null;

$filter = [];
if ($semester_id !== null) {
    $filter['semester_id'] = $semester_id;
}

// Count total matching documents
$total = $db->gpa_summary->countDocuments($filter);

// Fetch paginated data using aggregation (projection + sort + skip + limit)
$pipeline = [
    ['$match' => $filter],
    ['$sort' => ['subject_code' => 1]],
    ['$skip' => $skip],
    ['$limit' => $limit],
    ['$project' => [
        '_id' => 0,
        'subject_code' => 1,
        'subject_description' => 1,
        'semester_id' => 1,
        'average_grade' => ['$round' => ['$average_grade', 2]],
        'passing_rate' => ['$round' => ['$passing_rate', 2]],
        'at_risk_rate' => ['$round' => ['$at_risk_rate', 2]],
        'top_grade' => 1
    ]]
];

$cursor = $db->gpa_summary->aggregate($pipeline);
$data = iterator_to_array($cursor);

header('Content-Type: application/json');
echo json_encode([
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'data' => $data
]);
