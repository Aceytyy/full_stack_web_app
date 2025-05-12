<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Content-Type: application/json');

require_once 'mongo_connection.php';

$selected_sy = isset($_GET['sy']) ? intval($_GET['sy']) : null;

try {
    // Get all unique school years
    $semesters = $db->semesters->find([], ['projection' => ['_id' => 1, 'Semester' => 1, 'SchoolYear' => 1]]);
    $all_school_years = [];

    $semester_map = [];
    foreach ($semesters as $sem) {
        $school_year = $sem['SchoolYear'];
        if (!in_array($school_year, $all_school_years)) {
            $all_school_years[] = $school_year;
        }
        $semester_map[$sem['_id']] = $sem;
    }

    sort($all_school_years);

    if (!$selected_sy) {
        echo json_encode(["school_years" => $all_school_years]);
        exit;
    }

    // Filter semesters for selected year
    $filtered_semesters = array_filter($semester_map, function ($s) use ($selected_sy) {
        return $s['SchoolYear'] === $selected_sy;
    });

    $results = [];
    foreach ($filtered_semesters as $sem_id => $sem) {
        $metrics = $db->semester_metrics->findOne(["semester_id" => $sem_id]);

        // If you have an array of all grades for the semester:
        $grades = $metrics["grades"] ?? []; // e.g. [85, 92, 78, 88, ...]
        $dist = [
            "90-100" => 0,
            "80-89" => 0,
            "70-79" => 0,
            "<70"    => 0
        ];
        $at_risk_count = 0;
        foreach ($grades as $g) {
            if ($g >= 90) $dist["90-100"]++;
            elseif ($g >= 80) $dist["80-89"]++;
            elseif ($g >= 70) $dist["70-79"]++;
            else $dist["<70"]++;
            if ($g < 80) $at_risk_count++;
        }
        $total = count($grades);
        $at_risk_rate = $total ? $at_risk_count / $total : 0;

        $results[] = [
            "semester_name" => $sem['Semester'],
            "average_grade" => $metrics["average_grade"] ?? null,
            "passing_rate" => $metrics["passing_rate"] ?? null,
            "top_grade" => $metrics["top_grade"] ?? null,
            "at_risk_rate" => $at_risk_rate,
            "grade_distribution" => $dist
        ];
    }

    echo json_encode([
        "school_year" => $selected_sy,
        "school_years" => $all_school_years,
        "semesters" => array_values($results)
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
