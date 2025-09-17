<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $db = new PDO('sqlite:enrollment.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');

    // ensure schema
    require_once 'view.php'; // but view.php already handles initialize; to avoid re-run, instead replicate initialize
} catch (Exception $e){
    // ignore; we'll open again below with initialize
}

try {
    $db = new PDO('sqlite:enrollment.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');

    // ensure initialization
    include_once 'view.php';

    $input = json_decode(file_get_contents('php://input'), true);
    $entity = $input['entity'] ?? '';
    $payload = $input['payload'] ?? [];

    if(!$entity) throw new Exception('Entity required');

    switch($entity){
        case 'students':
            $stmt = $db->prepare("INSERT INTO students (name, program_id, allowance) VALUES (:name, :program_id, :allowance)");
            $stmt->execute([
                ':name'=>$payload['name'] ?? '',
                ':program_id'=> $payload['program_id'] ?? null,
                ':allowance'=> $payload['allowance'] ?? 0
            ]);
            echo json_encode(['success'=>true,'message'=>'Student added','data'=>['id'=>$db->lastInsertId()]]);
            break;
        case 'programs':
            $stmt = $db->prepare("INSERT INTO programs (name, institute) VALUES (:name, :institute)");
            $stmt->execute([':name'=>$payload['name']??'Unnamed', ':institute'=>$payload['institute']??null]);
            echo json_encode(['success'=>true,'message'=>'Program added','data'=>['id'=>$db->lastInsertId()]]);
            break;
        case 'years':
            $stmt = $db->prepare("INSERT INTO years (name) VALUES (:name)");
            $stmt->execute([':name'=>$payload['name']??'']);
            echo json_encode(['success'=>true,'message'=>'Year added','data'=>['id'=>$db->lastInsertId()]]);
            break;
        case 'semesters':
            $stmt = $db->prepare("INSERT INTO semesters (name, year_id) VALUES (:name, :year_id)");
            $stmt->execute([':name'=>$payload['name']??'',':year_id'=>$payload['year_id']]);
            echo json_encode(['success'=>true,'message'=>'Semester added','data'=>['id'=>$db->lastInsertId()]]);
            break;
        case 'subjects':
            $stmt = $db->prepare("INSERT INTO subjects (name, semester_id) VALUES (:name, :semester_id)");
            $stmt->execute([':name'=>$payload['name']??'',':semester_id'=>$payload['semester_id']]);
            echo json_encode(['success'=>true,'message'=>'Subject added','data'=>['id'=>$db->lastInsertId()]]);
            break;
        case 'enrollments':
            $stmt = $db->prepare("INSERT INTO enrollments (student_id, subject_id) VALUES (:student_id, :subject_id)");
            $stmt->execute([':student_id'=>$payload['student_id'], ':subject_id'=>$payload['subject_id']]);
            echo json_encode(['success'=>true,'message'=>'Student enrolled','data'=>['id'=>$db->lastInsertId()]]);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown entity']);
    }
} catch (PDOException $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
} catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
