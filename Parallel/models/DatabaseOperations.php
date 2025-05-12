<?php
require_once __DIR__ . '/../config/database.php';

class DatabaseOperations {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Student Operations
    public function getStudent($studentId) {
        return $this->db->getStudentsCollection()->findOne(['student_id' => $studentId]);
    }
    
    public function getAllStudents() {
        return $this->db->getStudentsCollection()->find()->toArray();
    }
    
    // Grade Operations
    public function getStudentGrades($studentId, $semesterId = null) {
        $filter = ['student_id' => $studentId];
        if ($semesterId) {
            $filter['semester_id'] = $semesterId;
        }
        return $this->db->getGradesCollection()->find($filter)->toArray();
    }
    
    public function getSubjectGrades($subjectId, $semesterId = null) {
        $filter = ['subject_id' => $subjectId];
        if ($semesterId) {
            $filter['semester_id'] = $semesterId;
        }
        return $this->db->getGradesCollection()->find($filter)->toArray();
    }
    
    // Subject Operations
    public function getSubject($subjectId) {
        return $this->db->getSubjectsCollection()->findOne(['subject_id' => $subjectId]);
    }
    
    public function getAllSubjects() {
        return $this->db->getSubjectsCollection()->find()->toArray();
    }
    
    // Semester Operations
    public function getSemester($semesterId) {
        return $this->db->getSemestersCollection()->findOne(['semester_id' => $semesterId]);
    }
    
    public function getAllSemesters() {
        return $this->db->getSemestersCollection()->find()->toArray();
    }
    
    // Analytics Operations
    public function getStudentPerformance($studentId) {
        $pipeline = [
            ['$match' => ['StudentID' => (int)$studentId]],
            ['$unwind' => ['path' => '$Grades', 'includeArrayIndex' => 'idx']],
            ['$unwind' => ['path' => '$SubjectCodes', 'includeArrayIndex' => 'sidx']],
            ['$match' => ['$expr' => ['$eq' => ['$idx', '$sidx']]]],
            [
                '$group' => [
                    '_id' => '$SemesterID',
                    'grades' => [
                        '$push' => [
                            'subject' => '$SubjectCodes',
                            'grade' => '$Grades'
                        ]
                    ],
                    'average' => ['$avg' => '$Grades']
                ]
            ]
        ];

        $options = ['allowDiskUse' => true];
        return $this->db->getGradesCollection()->aggregate($pipeline, $options)->toArray();
    }
    
    public function getSubjectAnalytics($subjectId) {
        $pipeline = [
            ['$match' => ['subject_id' => $subjectId]],
            ['$group' => [
                '_id' => '$semester_id',
                'average' => ['$avg' => '$grade'],
                'passing_rate' => [
                    '$avg' => [
                        '$cond' => [['$gte' => ['$grade', 75]], 1, 0]
                    ]
                ],
                'total_students' => ['$sum' => 1]
            ]]
        ];
        
        $options = ['allowDiskUse' => true];
        
        return $this->db->getGradesCollection()->aggregate($pipeline, $options)->toArray();
    }
} 