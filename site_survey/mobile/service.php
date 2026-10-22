<?php
function surveyMobileText(array $data, string $key, int $limit, bool $required = false): string {
    $value = $data[$key] ?? '';
    if (!is_string($value)) throw new InvalidArgumentException('Invalid '.$key);
    $value = trim($value);
    if (($required && $value === '') || mb_strlen($value) > $limit) throw new InvalidArgumentException('Check '.$key.' (maximum '.$limit.' characters).');
    return $value;
}
function surveyMobileValidate(array $data): array {
    $id = surveyMobileText($data,'id',64,true);
    if (!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$id)) throw new InvalidArgumentException('Invalid draft ID.');
    $date = surveyMobileText($data,'survey_date',10,true);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d',$date);
    if (!$parsed || $parsed->format('Y-m-d') !== $date) throw new InvalidArgumentException('Enter a valid survey date.');
    $customerId = filter_var($data['customer_id'] ?? 0,FILTER_VALIDATE_INT);
    if ($customerId === false || $customerId < 0) throw new InvalidArgumentException('Invalid customer.');
    $result = ['id'=>$id,'customer_id'=>$customerId,'survey_date'=>$date,
        'name'=>surveyMobileText($data,'name',100,$customerId===0),
        'phone'=>surveyMobileText($data,'phone',50),
        'address'=>surveyMobileText($data,'address',5000,true),
        'notes'=>surveyMobileText($data,'notes',10000),'items'=>[],'photos'=>[]];
    $items = $data['items'] ?? null;
    if (!is_array($items) || !array_is_list($items) || count($items)<1 || count($items)>100) throw new InvalidArgumentException('Add 1–100 measurements.');
    foreach ($items as $item) {
        if (!is_array($item)) throw new InvalidArgumentException('Invalid measurement.');
        $row = [];
        foreach (['item_name','glass_type','frame_color','opening_type'] as $field) $row[$field]=surveyMobileText($item,$field,100,$field==='item_name');
        $row['notes']=surveyMobileText($item,'notes',5000);
        foreach (['width','height'] as $field) {
            $v=$item[$field]??null;
            if (!is_numeric($v) || !is_finite((float)$v) || (float)$v<=0 || (float)$v>99999999.99) throw new InvalidArgumentException('Enter positive width and height in millimetres.');
            $row[$field]=round((float)$v,2);
        }
        $row['quantity']=filter_var($item['quantity']??null,FILTER_VALIDATE_INT);
        if (!$row['quantity'] || $row['quantity']<1 || $row['quantity']>10000) throw new InvalidArgumentException('Quantity must be a whole number from 1 to 10,000.');
        $result['items'][]=$row;
    }
    $photos=$data['photos']??[];
    if (!is_array($photos) || !array_is_list($photos) || count($photos)>6) throw new InvalidArgumentException('Use at most six photos.');
    foreach ($photos as $photo) {
        if (!is_string($photo) || !str_starts_with($photo,'data:image/jpeg;base64,')) throw new InvalidArgumentException('Photos must be JPEG images.');
        $binary=base64_decode(substr($photo,23),true);
        if ($binary===false || strlen($binary)>600000) throw new InvalidArgumentException('A photo is too large.');
        $info=@getimagesizefromstring($binary);
        if (!$info || $info['mime']!=='image/jpeg' || $info[0]>1600 || $info[1]>1600) throw new InvalidArgumentException('Invalid photo dimensions or type.');
        $result['photos'][]=$binary;
    }
    return $result;
}
// Caller owns the transaction: survey, measurements, photos and retry receipt
// are committed together, or none are saved.
function surveyMobilePersist(mysqli $conn, array $data, int $userId, string $hash): int {
    $stmt=$conn->prepare('SELECT user_id,payload_hash,survey_id FROM survey_mobile_sync WHERE draft_id=?');
    $stmt->execute([$data['id']]);
    if ($old=$stmt->get_result()->fetch_assoc()) {
        if ((int)$old['user_id']!==$userId || !hash_equals($old['payload_hash'],$hash)) throw new InvalidArgumentException('This draft ID is already used by a different submission.');
        return (int)$old['survey_id'];
    }
    $stmt=$conn->prepare('INSERT INTO survey_mobile_sync (draft_id,user_id,payload_hash) VALUES (?,?,?)');
    $stmt->execute([$data['id'],$userId,$hash]);
    $customerId=$data['customer_id'];
    if ($customerId) {
        $stmt=$conn->prepare('SELECT id FROM customers WHERE id=?'); $stmt->execute([$customerId]);
        if (!$stmt->get_result()->fetch_assoc()) throw new InvalidArgumentException('Customer no longer exists. Refresh the customer list before making a new draft.');
    } else {
        $stmt=$conn->prepare('INSERT INTO customers (name,phone,address) VALUES (?,?,?)');
        $stmt->execute([$data['name'],$data['phone'],$data['address']]); $customerId=$conn->insert_id;
    }
    $stmt=$conn->prepare("INSERT INTO site_surveys (customer_id,survey_date,address,notes,status,created_by) VALUES (?,?,?,?,'Pending',?)");
    $stmt->execute([$customerId,$data['survey_date'],$data['address'],$data['notes'],$userId]); $surveyId=$conn->insert_id;
    $stmt=$conn->prepare('INSERT INTO survey_measurements (survey_id,item_name,width,height,quantity,glass_type,frame_color,opening_type,notes) VALUES (?,?,?,?,?,?,?,?,?)');
    foreach ($data['items'] as $r) $stmt->execute([$surveyId,$r['item_name'],$r['width'],$r['height'],$r['quantity'],$r['glass_type'],$r['frame_color'],$r['opening_type'],$r['notes']]);
    $stmt=$conn->prepare("INSERT INTO survey_mobile_photos (survey_id,mime_type,image_data) VALUES (?,'image/jpeg',?)");
    foreach ($data['photos'] as $photo) $stmt->execute([$surveyId,$photo]);
    $stmt=$conn->prepare('UPDATE survey_mobile_sync SET survey_id=? WHERE draft_id=?'); $stmt->execute([$surveyId,$data['id']]);
    return $surveyId;
}
