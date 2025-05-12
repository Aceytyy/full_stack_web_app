<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 20;
    $skip = ($page - 1) * $limit;

    // Build filter
    $filter = [];
    if (!empty($_GET['semester_id'])) {
        $filter['semester_id'] = intval($_GET['semester_id']);
    }
    if (!empty($_GET['subject_code'])) {
        $filter['subject_code'] = $_GET['subject_code'];
    }
    if (!empty($_GET['min_grade'])) {
        $filter['average_grade']['$gte'] = floatval($_GET['min_grade']);
    }
    if (!empty($_GET['max_grade'])) {
        $filter['average_grade']['$lte'] = floatval($_GET['max_grade']);
    }
    // Add more filters as needed

    // Sorting
    $sort_by = !empty($_GET['sort_by']) ? $_GET['sort_by'] : 'average_grade';
    $sort_order = (isset($_GET['sort_order']) && $_GET['sort_order'] === 'asc') ? 1 : -1;
    $options = [
        'skip' => $skip,
        'limit' => $limit,
        'sort' => [$sort_by => $sort_order]
    ];

    $total = $db->subject_analytics_cache->countDocuments($filter);
    $results = iterator_to_array(
        $db->subject_analytics_cache->find($filter, $options),
        false
    );

    echo json_encode([
        "success" => true,
        "data" => $results,
        "page" => $page,
        "limit" => $limit,
        "total" => $total
    ]);
} catch (Exception $e) {
    echo json_encode([
        "error" => true,
        "message" => $e->getMessage()
    ]);
}
?>