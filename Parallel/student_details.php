<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'mongo_connection.php';
header('Content-Type: application/json');

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$semester_id = isset($_GET['semester_id']) ? intval($_GET['semester_id']) : null;

if (!$student_id || !$semester_id) {
    echo json_encode(['error' => true, 'message' => 'Missing student_id or semester_id']);
    exit;
}

// Get the student's grades for the semester
$studentGrades = $db->grades->findOne(['StudentID' => $student_id, 'SemesterID' => $semester_id]);
if (!$studentGrades) {
    echo json_encode(['error' => true, 'message' => 'No grades found for this student/semester']);
    exit;
}

// Convert BSONArray to PHP array
$grades = is_array($studentGrades['Grades']) ? $studentGrades['Grades'] : $studentGrades['Grades']->getArrayCopy();
$subjectCodes = is_array($studentGrades['SubjectCodes']) ? $studentGrades['SubjectCodes'] : $studentGrades['SubjectCodes']->getArrayCopy();

// Get subject descriptions
$subjectsCursor = $db->subjects->find(['_id' => ['$in' => $subjectCodes]]);
$subjectDescriptions = [];
foreach ($subjectsCursor as $subj) {
    $subjectDescriptions[$subj['_id']] = $subj['Description'];
}

// Fetch precomputed GPA and zscore
$studentGPA = $db->student_gpas->findOne([
    'student_id' => $student_id,
    'semester_id' => $semester_id
]);
$gpa = $studentGPA['gpa'] ?? null;
$zscore = $studentGPA['zscore'] ?? null;

// Fetch precomputed semester metrics
$semesterMetrics = $db->semester_metrics->findOne(['semester_id' => $semester_id]);
$class_average = $semesterMetrics['average_grade'] ?? null;

// Fetch precomputed class averages for subjects
$classAveragesCursor = $db->class_averages->find(['semester_id' => $semester_id]);
$classAverages = [];
foreach ($classAveragesCursor as $doc) {
    $classAverages[$doc['subject_code']] = $doc['average_grade'];
}

// Build subjects array with class averages per subject
$subjects = [];
foreach ($subjectCodes as $i => $code) {
    $studentGrade = $grades[$i];
    $subject_class_avg = $classAverages[$code] ?? null;
    $rate = ($studentGrade > $subject_class_avg) ? 'Higher' : (($studentGrade < $subject_class_avg) ? 'Lower' : 'Equal');
    $subjects[] = [
        'code' => $code,
        'description' => $subjectDescriptions[$code] ?? $code,
        'grade' => $studentGrade,
        'class_average' => $subject_class_avg,
        'rate' => $rate
    ];
}

echo json_encode([
    'student_id' => $student_id,
    'gpa' => $gpa,
    'zscore' => $zscore,
    'class_average' => $class_average,
    'subjects' => $subjects
]);
