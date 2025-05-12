<?php
require_once 'vendor/autoload.php';
require_once 'mongo_connection.php';

use Twilio\Rest\Client;

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // GET /grade_editor.php?student_id=12345
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
    if (!$student_id) {
        echo json_encode(["error" => true, "message" => "Missing student_id"]);
        exit;
    }

    $student = $db->students->findOne(["_id" => $student_id]);
    if (!$student) {
        echo json_encode(["error" => true, "message" => "Student not found"]);
        exit;
    }

    $grades = $db->grades->findOne(["StudentID" => $student_id]);
    if (!$grades) {
        echo json_encode(["error" => true, "message" => "Grades not found"]);
        exit;
    }

    $subject_data = [];
    foreach ($grades['SubjectCodes'] as $index => $code) {
        $subject = $db->subjects->findOne(["_id" => $code]);
        $subject_data[] = [
            "code" => $code,
            "description" => $subject["Description"] ?? "",
            "grade" => $grades["Grades"][$index],
        ];
    }

    echo json_encode([
        "student_id" => $student["_id"],
        "name" => $student["Name"],
        "phone" => $student["Phone"] ?? "",
        "subjects" => $subject_data
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $student_id = intval($input['student_id'] ?? 0);
    $subject_code = $input['subject_code'] ?? '';
    $new_grade = floatval($input['new_grade'] ?? 0);
    $phone = $input['phone'] ?? '';

    if (!$student_id || !$subject_code || !$new_grade || !$phone) {
        echo json_encode(["error" => true, "message" => "Incomplete input data"]);
        exit;
    }

    $grades_doc = $db->grades->findOne(["StudentID" => $student_id]);
    if (!$grades_doc) {
        echo json_encode(["error" => true, "message" => "Grades record not found"]);
        exit;
    }

    $index = array_search($subject_code, $grades_doc["SubjectCodes"]);
    if ($index === false) {
        echo json_encode(["error" => true, "message" => "Subject not found in student's record"]);
        exit;
    }

    // Update grade
    $grades_doc["Grades"][$index] = $new_grade;
    $db->grades->updateOne(
        ["_id" => $grades_doc["_id"]],
        ['$set' => ["Grades" => $grades_doc["Grades"]]]
    );

    // Recalculate GPA
    $subject_weights = [];
    foreach ($grades_doc["SubjectCodes"] as $code) {
        $subject = $db->subjects->findOne(["_id" => $code]);
        $subject_weights[] = $subject['Units'] ?? 1;
    }

    $total_units = array_sum($subject_weights);
    $weighted_sum = 0;
    foreach ($grades_doc["Grades"] as $i => $grade) {
        $weighted_sum += $grade * $subject_weights[$i];
    }

    $weighted_avg = $total_units > 0 ? round($weighted_sum / $total_units, 2) : 0;
    $gpa = round(convert_to_gpa($weighted_avg), 2);

    $db->student_gpas->updateOne(
        ["student_id" => $student_id],
        ['$set' => [
            "weighted_average" => $weighted_avg,
            "gpa" => $gpa
        ]]
    );

    // Send SMS
    $sid = 'your_twilio_sid';
    $token = 'your_twilio_auth_token';
    $twilio_number = 'your_twilio_phone_number';

    try {
        $twilio = new Client($sid, $token);
        $message = "Your updated grade for $subject_code is $new_grade. Your new GPA is $gpa.";
        $twilio->messages->create($phone, [
            'from' => $twilio_number,
            'body' => $message
        ]);
    } catch (Exception $e) {
        echo json_encode(["error" => true, "message" => "Grade updated but SMS failed: " . $e->getMessage()]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Grade updated and SMS sent",
        "gpa" => $gpa,
        "weighted_average" => $weighted_avg
    ]);
    exit;
}

function convert_to_gpa($grade)
{
    // Adjust this logic to your actual GPA conversion rules
    if ($grade >= 95) return 1.0;
    if ($grade >= 90) return 1.25;
    if ($grade >= 85) return 2.25;
    if ($grade >= 83) return 2.75;
    if ($grade >= 80) return 2.0;
    return 5.0; // Failing
}
?>
