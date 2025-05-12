<?php
require_once 'models/DatabaseOperations.php';

$type = $_GET['type'] ?? 'gpa';
$semester = isset($_GET['semester']) ? (int)$_GET['semester'] : null;
header('Content-Type: application/json');

$dbOps = new DatabaseOperations();

try {
    switch ($type) {
        case 'gpa':
            $performance = $dbOps->getStudentPerformance($_GET['studentId'] ?? null);
            echo json_encode($performance);
            break;
            
        case 'atRisk':
            $students = $dbOps->getAllStudents();
            $atRiskStudents = [];
            foreach ($students as $student) {
                $performance = $dbOps->getStudentPerformance($student['student_id']);
                foreach ($performance as $semester) {
                    if ($semester['average'] < 75) {
                        $atRiskStudents[] = [
                            'student_id' => $student['student_id'],
                            'name' => $student['first_name'] . ' ' . $student['last_name'],
                            'semester' => $semester['_id'],
                            'gpa' => $semester['average']
                        ];
                    }
                }
            }
            echo json_encode($atRiskStudents);
            break;
            
        case 'subject':
            $subjectId = $_GET['subjectId'] ?? null;
            if ($subjectId) {
                $analytics = $dbOps->getSubjectAnalytics($subjectId);
                echo json_encode($analytics);
            } else {
                $subjects = $dbOps->getAllSubjects();
                echo json_encode($subjects);
            }
            break;
            
        case 'year':
            $semesters = $dbOps->getAllSemesters();
            $yearData = [];
            foreach ($semesters as $semester) {
                $grades = $dbOps->getStudentGrades(null, $semester['semester_id']);
                $average = array_sum(array_column($grades, 'grade')) / count($grades);
                $yearData[] = [
                    'semester' => $semester['semester_name'],
                    'average' => round($average, 2)
                ];
            }
            echo json_encode($yearData);
            break;
            
        case 'performance':
            $studentId = $_GET['studentId'] ?? null;
            if ($studentId) {
                $performance = $dbOps->getStudentPerformance($studentId);
                echo json_encode($performance);
            } else {
                echo json_encode(['error' => 'Student ID required']);
            }
            break;
            
        case 'summary':
            $students = $dbOps->getAllStudents();
            $totalStudents = count($students);
            
            $allGrades = [];
            $atRiskCount = 0;
            foreach ($students as $student) {
                $grades = $dbOps->getStudentGrades($student['student_id']);
                foreach ($grades as $grade) {
                    $allGrades[] = $grade['grade'];
                    if ($grade['grade'] < 75) {
                        $atRiskCount++;
                    }
                }
            }
            
            $averageGPA = count($allGrades) > 0 ? array_sum($allGrades) / count($allGrades) : 0;
            
            echo json_encode([
                'totalStudents' => $totalStudents,
                'averageGPA' => round($averageGPA, 2),
                'atRiskCount' => $atRiskCount
            ]);
            break;
            
        default:
            echo json_encode(['error' => 'Invalid request type']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
