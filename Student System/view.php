<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $db = new PDO('sqlite:enrollment.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $db->exec('PRAGMA foreign_keys = ON');

    
    initialize($db);

    $entity = isset($_GET['entity']) ? $_GET['entity'] : '';
    switch($entity){
        case 'students':
            
            $stmt = $db->prepare("SELECT s.id, s.name, s.program_id, s.allowance, p.name as program_name FROM students s LEFT JOIN programs p ON s.program_id = p.id ORDER BY s.id");
            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success'=>true,'data'=>$data]);
            break;
        case 'programs':
            $stmt = $db->prepare("SELECT * FROM programs ORDER BY id");
            $stmt->execute();
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        case 'years':
            $stmt = $db->prepare("SELECT * FROM years ORDER BY id");
            $stmt->execute();
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        case 'semesters':
            $stmt = $db->prepare("SELECT * FROM semesters ORDER BY id");
            $stmt->execute();
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        case 'subjects':
            $stmt = $db->prepare("SELECT sub.*, sem.name as semester_name FROM subjects sub LEFT JOIN semesters sem ON sub.semester_id = sem.id ORDER BY sub.id");
            $stmt->execute();
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        case 'enrollments':
            $stmt = $db->prepare("SELECT e.id, e.student_id, e.subject_id FROM enrollments e ORDER BY e.id");
            $stmt->execute();
            echo json_encode(['success'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Invalid entity']);
    }

} catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}

function initialize($db){
    
    $db->exec("
        CREATE TABLE IF NOT EXISTS programs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE,
            institute TEXT
        );
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS years (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        );
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS semesters (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            year_id INTEGER NOT NULL,
            FOREIGN KEY(year_id) REFERENCES years(id) ON DELETE CASCADE
        );
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS subjects (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            semester_id INTEGER NOT NULL,
            FOREIGN KEY(semester_id) REFERENCES semesters(id) ON DELETE CASCADE
        );
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS students (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            program_id INTEGER,
            allowance REAL DEFAULT 0,
            FOREIGN KEY(program_id) REFERENCES programs(id) ON DELETE SET NULL
        );
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS enrollments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_id INTEGER NOT NULL,
            subject_id INTEGER NOT NULL,
            UNIQUE(student_id, subject_id),
            FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
            FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE
        );
    ");

    
    $check = $db->query("SELECT COUNT(*) FROM programs")->fetchColumn();
    if($check == 0){
        $db->exec("
            INSERT INTO programs (name, institute) VALUES
             ('BS Information System', 'ILIS'),
             ('BLIS ', 'ILIS'),
             ('BS Educ', 'EDUC')
        ");
    }
    $chkY = $db->query("SELECT COUNT(*) FROM years")->fetchColumn();
    if($chkY == 0){
        $db->exec("INSERT INTO years (name) VALUES ('2024-2025'), ('2025-2026')");
    }
    $chkS = $db->query("SELECT COUNT(*) FROM semesters")->fetchColumn();
    if($chkS == 0){
        $db->exec("INSERT INTO semesters (name, year_id) VALUES ('First Semester',1), ('Second Semester',1)");
    }
    $chkSub = $db->query("SELECT COUNT(*) FROM subjects")->fetchColumn();
    if($chkSub == 0){
        $db->exec("INSERT INTO subjects (name, semester_id) VALUES ('Introduction to IT',1), ('Programming 1',1), ('Discrete Math',2)");
    }
    $chkStu = $db->query("SELECT COUNT(*) FROM students")->fetchColumn();
    if($chkStu == 0){
        $db->exec("INSERT INTO students (name, program_id, allowance) VALUES ('Juan Dela Cruz',1,1000), ('Maria Clara',2,1200)");
    }
}
