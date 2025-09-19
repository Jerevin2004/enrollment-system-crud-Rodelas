<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $db = new PDO('sqlite:enrollment.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');

    $input = json_decode(file_get_contents('php://input'), true);
    $entity = $input['entity'] ?? '';
    $id = $input['id'] ?? null;
    $payload = $input['payload'] ?? [];

    if(!$entity || !$id) throw new Exception('Entity and id required');

    switch($entity){
        case 'students':
            $stmt = $db->prepare("UPDATE students SET name=:name, program_id=:program_id, allowance=:allowance WHERE id=:id");
            $stmt->execute([':name'=>$payload['name']??'',':program_id'=>$payload['program_id']??null,':allowance'=>$payload['allowance']??0,':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Student updated']);
            break;
        case 'programs':
            $stmt = $db->prepare("UPDATE programs SET name=:name, institute=:institute WHERE id=:id");
            $stmt->execute([':name'=>$payload['name']??'',':institute'=>$payload['institute']??null,':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Program updated']);
            break;
        case 'years':
            $stmt = $db->prepare("UPDATE years SET name=:name WHERE id=:id");
            $stmt->execute([':name'=>$payload['name']??'',':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Year updated']);
            break;
        case 'semesters':
            $stmt = $db->prepare("UPDATE semesters SET name=:name, year_id=:year_id WHERE id=:id");
            $stmt->execute([':name'=>$payload['name']??'',':year_id'=>$payload['year_id']??null,':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Semester updated']);
            break;
        case 'subjects':
            $stmt = $db->prepare("UPDATE subjects SET name=:name, semester_id=:semester_id WHERE id=:id");
            $stmt->execute([':name'=>$payload['name']??'',':semester_id'=>$payload['semester_id']??null,':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Subject updated']);
            break;
        case 'enrollments':
           
            $stmt = $db->prepare("UPDATE enrollments SET subject_id=:subject_id WHERE id=:id");
            $stmt->execute([':subject_id'=>$payload['subject_id'], ':id'=>$id]);
            echo json_encode(['success'=>true,'message'=>'Enrollment updated']);
            break;
        default:
            echo json_encode(['success'=>false,'message'=>'Unknown entity']);
    }
} catch (PDOException $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
} catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
