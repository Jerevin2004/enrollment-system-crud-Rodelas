<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $db = new PDO('sqlite:enrollment.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');

    $input = json_decode(file_get_contents('php://input'), true);
    $entity = $input['entity'] ?? '';
    $id = $input['id'] ?? null;
    if(!$entity || !$id) throw new Exception('Entity and id required');

    switch($entity){
        case 'students':
            $stmt = $db->prepare("DELETE FROM students WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Student deleted']);
            break;
        case 'programs':
            // check students referencing this program
            $stmt = $db->prepare("SELECT COUNT(*) FROM students WHERE program_id=:id");
            $stmt->execute([':id'=>$id]);
            if($stmt->fetchColumn()>0) throw new Exception('Cannot delete program: students are enrolled in it.');
            $stmt = $db->prepare("DELETE FROM programs WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Program deleted']);
            break;
        case 'years':
            // check semesters exist will be cascaded, but be safe
            $stmt = $db->prepare("DELETE FROM years WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Year deleted']);
            break;
        case 'semesters':
            // check subjects referencing
            $stmt = $db->prepare("SELECT COUNT(*) FROM subjects WHERE semester_id=:id");
            $stmt->execute([':id'=>$id]);
            if($stmt->fetchColumn()>0) throw new Exception('Cannot delete semester: subjects exist for it.');
            $stmt = $db->prepare("DELETE FROM semesters WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Semester deleted']);
            break;
        case 'subjects':
            $stmt = $db->prepare("SELECT COUNT(*) FROM enrollments WHERE subject_id=:id");
            $stmt->execute([':id'=>$id]);
            if($stmt->fetchColumn()>0) throw new Exception('Cannot delete subject: students are enrolled in it.');
            $stmt = $db->prepare("DELETE FROM subjects WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Subject deleted']);
            break;
        case 'enrollments':
            $stmt = $db->prepare("DELETE FROM enrollments WHERE id=:id");
            $stmt->execute([':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Enrollment removed']);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown entity']);
    }

} catch (PDOException $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
} catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
