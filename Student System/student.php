<?php
header('Content-Type: application/json; charset=utf-8');
try {
    if(!isset($_GET['id'])) throw new Exception('id required');
    $id = (int)$_GET['id'];
    $db = new PDO('sqlite:enrollment.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA foreign_keys = ON');

    // ensure schema
    include_once 'view.php';

    $stmt = $db->prepare("SELECT id, name, program_id, allowance FROM students WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$row) echo json_encode(['success'=>false,'message'=>'Not found']);
    else echo json_encode(['success'=>true,'data'=>$row]);
} catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
