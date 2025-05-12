<?php
require_once __DIR__ . '/../vendor/autoload.php';

class Database {
    private static $instance = null;
    private $client;
    private $db;
    
    private function __construct() {
        try {
            $this->client = new MongoDB\Client("mongodb://localhost:27017");
            $this->db = $this->client->CSELEC3DB;
        } catch (Exception $e) {
            die("❌ MongoDB connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getCollection($collectionName) {
        return $this->db->$collectionName;
    }
    
    // Collection getters
    public function getStudentsCollection() {
        return $this->getCollection('students');
    }
    
    public function getGradesCollection() {
        return $this->getCollection('grades');
    }
    
    public function getSubjectsCollection() {
        return $this->getCollection('subjects');
    }
    
    public function getSemestersCollection() {
        return $this->getCollection('semesters');
    }
    
    public function getSchoolYearsCollection() {
        return $this->getCollection('school_years');
    }
    
    public function getPerformanceAnalyticsCollection() {
        return $this->getCollection('performance_analytics');
    }
} 