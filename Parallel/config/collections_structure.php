<?php
// This file shows the structure of MongoDB collections

// Students Collection Structure
$studentsStructure = [
    'student_id' => 'string', // Unique identifier
    'first_name' => 'string',
    'last_name' => 'string',
    'email' => 'string',
    'enrollment_date' => 'date',
    'status' => 'string', // active, inactive, graduated
    'program' => 'string',
    'created_at' => 'date',
    'updated_at' => 'date'
];

// Grades Collection Structure
$gradesStructure = [
    'grade_id' => 'string', // Unique identifier
    'student_id' => 'string', // Reference to students collection
    'subject_id' => 'string', // Reference to subjects collection
    'semester_id' => 'string', // Reference to semesters collection
    'school_year_id' => 'string', // Reference to school_years collection
    'grade' => 'float',
    'remarks' => 'string',
    'created_at' => 'date',
    'updated_at' => 'date'
];

// Subjects Collection Structure
$subjectsStructure = [
    'subject_id' => 'string', // Unique identifier
    'subject_code' => 'string',
    'subject_name' => 'string',
    'units' => 'integer',
    'description' => 'string',
    'created_at' => 'date',
    'updated_at' => 'date'
];

// Semesters Collection Structure
$semestersStructure = [
    'semester_id' => 'string', // Unique identifier
    'semester_name' => 'string', // e.g., "First Semester", "Second Semester"
    'start_date' => 'date',
    'end_date' => 'date',
    'created_at' => 'date',
    'updated_at' => 'date'
];

// School Years Collection Structure
$schoolYearsStructure = [
    'school_year_id' => 'string', // Unique identifier
    'year_name' => 'string', // e.g., "2023-2024"
    'start_date' => 'date',
    'end_date' => 'date',
    'created_at' => 'date',
    'updated_at' => 'date'
];

// Performance Analytics Collection Structure
$performanceAnalyticsStructure = [
    'analytics_id' => 'string', // Unique identifier
    'student_id' => 'string', // Reference to students collection
    'semester_id' => 'string', // Reference to semesters collection
    'school_year_id' => 'string', // Reference to school_years collection
    'gpa' => 'float',
    'total_units' => 'integer',
    'passed_units' => 'integer',
    'failed_units' => 'integer',
    'risk_level' => 'string', // low, medium, high
    'created_at' => 'date',
    'updated_at' => 'date'
]; 