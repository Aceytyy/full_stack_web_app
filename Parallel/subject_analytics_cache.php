<?php
require_once 'mongo_connection.php';

$pipeline = [
    ['$unwind' => [
        'path' => '$SubjectCodes',
        'includeArrayIndex' => 'subjectIdx'
    ]],
    ['$addFields' => [
        'SubjectCode' => '$SubjectCodes',
        'Grade' => ['$arrayElemAt' => ['$Grades', '$subjectIdx']]
    ]],
    // Add $match here if you want to filter
    ['$group' => [
        '_id' => [
            'subject_code' => '$SubjectCode',
            'semester_id' => '$SemesterID'
        ],
        'average_grade' => ['$avg' => '$Grade'],
        'passing_rate' => ['$avg' => ['$cond' => [['$gte' => ['$Grade', 75]], 1, 0]]],
        'failing_rate' => ['$avg' => ['$cond' => [['$lt' => ['$Grade', 75]], 1, 0]]],
        'at_risk_rate' => ['$avg' => ['$cond' => [['$lt' => ['$Grade', 80]], 1, 0]]],
        'student_count' => ['$sum' => 1],
        'grade_100' => ['$sum' => ['$cond' => [['$eq' => ['$Grade', 100]], 1, 0]]],
        'grade_90_99' => ['$sum' => ['$cond' => [['$and' => [['$gte' => ['$Grade', 90]], ['$lt' => ['$Grade', 100]]]], 1, 0]]],
        'grade_80_89' => ['$sum' => ['$cond' => [['$and' => [['$gte' => ['$Grade', 80]], ['$lt' => ['$Grade', 90]]]], 1, 0]]],
        'grade_75_79' => ['$sum' => ['$cond' => [['$and' => [['$gte' => ['$Grade', 75]], ['$lt' => ['$Grade', 80]]]], 1, 0]]],
        'grade_below_75' => ['$sum' => ['$cond' => [['$lt' => ['$Grade', 75]], 1, 0]]],
    ]],
    ['$project' => [
        '_id' => 0,
        'subject_code' => '$_id.subject_code',
        'semester_id' => '$_id.semester_id',
        'average_grade' => ['$round' => ['$average_grade', 2]],
        'passing_rate' => ['$round' => ['$passing_rate', 2]],
        'failing_rate' => ['$round' => ['$failing_rate', 2]],
        'at_risk_rate' => ['$round' => ['$at_risk_rate', 2]],
        'student_count' => 1,
        'grade_distribution' => [
            '100' => '$grade_100',
            '90-99' => '$grade_90_99',
            '80-89' => '$grade_80_89',
            '75-79' => '$grade_75_79',
            '<75' => '$grade_below_75'
        ]
    ]]
];

$results = iterator_to_array($db->grades->aggregate($pipeline), false);

foreach ($results as $doc) {
    $db->subject_analytics_cache->replaceOne(
        ['subject_code' => $doc['subject_code'], 'semester_id' => $doc['semester_id']],
        $doc,
        ['upsert' => true]
    );
}

echo "Cache built successfully!";
?>
