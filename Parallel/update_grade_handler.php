<?php
require_once 'mongo_connection.php';
require_once 'vendor/autoload.php';
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Accept both POST and JSON input
$input = $_POST;
if (empty($input)) {
    $input = json_decode(file_get_contents('php://input'), true);
}

$student_id = intval($input['student_id'] ?? 0);
$semester_id = intval($input['semester_id'] ?? 0);
$subject_code = $input['subject_code'] ?? '';
$new_grade = floatval($input['new_grade'] ?? 0);
$email = $input['email'] ?? '';

if (!$student_id || !$semester_id || !$subject_code || !$email) {
    echo json_encode(["error" => true, "message" => "Missing required fields."]);
    exit;
}

// Find the grades document for this student and semester
$grades_doc = $db->grades->findOne([
    "StudentID" => $student_id,
    "SemesterID" => $semester_id
]);

if (!$grades_doc) {
    echo json_encode(["error" => true, "message" => "No grades found for this student and semester."]);
    exit;
}

// Get arrays
$subject_codes = is_array($grades_doc['SubjectCodes']) ? $grades_doc['SubjectCodes'] : $grades_doc['SubjectCodes']->getArrayCopy();
$grades = is_array($grades_doc['Grades']) ? $grades_doc['Grades'] : $grades_doc['Grades']->getArrayCopy();

// Find the index of the subject
$index = array_search($subject_code, $subject_codes);
if ($index === false) {
    echo json_encode(["error" => true, "message" => "Subject not found in student's record."]);
    exit;
}

// Update the grade in the array
$grades[$index] = $new_grade;

// Update the document in MongoDB
$result = $db->grades->updateOne(
    [
        "StudentID" => $student_id,
        "SemesterID" => $semester_id
    ],
    [
        '$set' => [
            "Grades" => $grades
        ]
    ]
);

if ($result->getModifiedCount() > 0) {
    echo json_encode(["success" => true, "message" => "Grade updated successfully."]);
} else {
    echo json_encode(["error" => true, "message" => "Grade update failed or no change made."]);
}

// Step 3: Recalculate GPA
$total_units = 0;
$weighted_score = 0;

foreach ($subject_codes as $i => $code) {
    $subject = $db->subjects->findOne(["_id" => $code]);
    $units = $subject['Units'] ?? 1;
    $grade = $grades[$i];
    $total_units += $units;
    $weighted_score += $grade * $units;
}

$weighted_avg = $total_units > 0 ? $weighted_score / $total_units : 0;

// Convert to GPA (PH GPA scale)
function convert_to_gpa($grade) {
    if ($grade >= 95) return 1.0;
    if ($grade >= 90) return 1.25;
    if ($grade >= 85) return 2.25;
    if ($grade >= 83) return 2.75;
    if ($grade >= 80) return 3.0;
    return 5.0;
}

$gpa = convert_to_gpa($weighted_avg);

// Step 4: Update GPA Summary
$db->student_gpas->updateOne(
    ["student_id" => $student_id],
    ['$set' => [
        "gpa" => round($gpa, 2),
        "weighted_average" => round($weighted_avg, 2),
        "last_updated" => new MongoDB\BSON\UTCDateTime()
    ]],
    ["upsert" => true]
);

// Step 5: Send Email using PHPMailer
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'g3paprikapower@gmail.com'; // Sender Gmail
    $mail->Password = 'vqyp gpam lnio lezr'; // Use app password, NOT Gmail password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('g3paprikapower@gmail.com', 'Student Grades Dashboard');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = "Grade Update Notification";
    $mail->Body = "Hello,<br><br>Your grade for <strong>$subject_code</strong> has been updated to <strong>$new_grade</strong>.<br>
                   Your new GPA is <strong>" . round($gpa, 2) . "</strong>.<br><br>Thank you.";

    $mail->send();

    echo json_encode([
        "success" => true,
        "message" => "Grade updated, GPA recalculated, and email sent.",
        "gpa" => round($gpa, 2),
        "weighted_average" => round($weighted_avg, 2)
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => true,
        "message" => "Grade updated and GPA recalculated, but email failed: " . $mail->ErrorInfo,
        "gpa" => round($gpa, 2),
        "weighted_average" => round($weighted_avg, 2)
    ]);
}
?>
