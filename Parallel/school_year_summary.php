<?php
require_once 'mongo_connection.php';
header('Content-Type: application/json');

$selected_sy = isset($_GET['sy']) ? intval($_GET['sy']) : null;

try {
    // Step 1: Fetch all semesters
    $semesters_cursor = $db->semesters->find([], ['projection' => ['_id' => 1, 'Semester' => 1, 'SchoolYear' => 1]]);
    $all_school_years = [];
    $semester_map = [];

    foreach ($semesters_cursor as $sem) {
        $school_year = $sem['SchoolYear'];
        if (!in_array($school_year, $all_school_years)) {
            $all_school_years[] = $school_year;
        }
        $semester_map[$sem['_id']] = $sem;
    }

    sort($all_school_years);

    // Step 2: If no year is selected, return dropdown list
    if (!$selected_sy) {
        echo json_encode(["school_years" => $all_school_years]);
        exit;
    }

    // Step 3: Filter semesters for selected year
    $filtered_semesters = array_filter($semester_map, function ($s) use ($selected_sy) {
        return $s['SchoolYear'] === $selected_sy;
    });

    // Step 4: For each semester, get metrics from `semester_metrics`
    $semester_metrics_list = [];
    foreach ($filtered_semesters as $sem_id => $sem) {
        $metrics = $db->semester_metrics->findOne(["semester_id" => $sem_id]);
        $semester_metrics_list[] = [
            "semester_name" => $sem['Semester'],
            "average_grade" => $metrics["average_grade"] ?? null,
            "passing_rate" => $metrics["passing_rate"] ?? null,
            "top_grade" => $metrics["top_grade"] ?? null,
            "at_risk_rate" => $metrics["at_risk_rate"] ?? null
        ];
    }

    // Step 5: If exactly 2 semesters, calculate comparison
    $changes = [];
    if (count($semester_metrics_list) === 2) {
        $first = $semester_metrics_list[0];
        $second = $semester_metrics_list[1];

        function diff_val($first, $second) {
            return ($first !== null && $second !== null) ? round($first - $second, 2) : null;
        }

        $changes = [
            "average_grade_change" => diff_val($first['average_grade'], $second['average_grade']),
            "passing_rate_change" => diff_val($first['passing_rate'], $second['passing_rate']),
            "top_grade_change" => diff_val($first['top_grade'], $second['top_grade']),
            "at_risk_rate_change" => diff_val($first['at_risk_rate'], $second['at_risk_rate'])
        ];
    }

    echo json_encode([
        "school_year" => $selected_sy,
        "school_years" => $all_school_years,
        "semesters" => array_values($semester_metrics_list),
        "changes" => $changes
    ]);

} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
