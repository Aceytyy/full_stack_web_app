<?php
header('Content-Type: application/json');
require 'mongo_connection.php';

try {
    $studentsCollection = $db->students;
    $gpaSummaryCollection = $db->gpa_summary;
    $gradesCollection = $db->grades;

    // Total students count
    $totalStudents = $studentsCollection->countDocuments();

    // Average GPA from gpa_summary
    $avgPipeline = [
        ['$group' => [
            '_id' => null,
            'average_gpa' => ['$avg' => '$gpa']
        ]]
    ];
    $avgResult = $gpaSummaryCollection->aggregate($avgPipeline)->toArray();
    $averageGPA = isset($avgResult[0]['average_gpa']) ? round($avgResult[0]['average_gpa'], 2) : 0;

    // At-risk student count (GPA > 3.0 or grade < 75)
    $atRiskPipeline = [
        ['$match' => [
            '$or' => [
                ['gpa' => ['$gt' => 3.0]],
                ['gpa' => ['$exists' => false]]
            ]
        ]],
        ['$count' => 'count']
    ];
    $atRiskResult = $gpaSummaryCollection->aggregate($atRiskPipeline)->toArray();
    $atRiskCount = $atRiskResult[0]['count'] ?? 0;

    echo json_encode([
        'total_students' => $totalStudents,
        'average_gpa' => $averageGPA,
        'at_risk_students' => $atRiskCount
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage()
    ]);
}
