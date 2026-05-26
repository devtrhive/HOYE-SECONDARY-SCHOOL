<?php
function handleUpload($file) {
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) return null;

    $allowed = ['application/pdf', 'image/jpeg', 'image/png'];
    if (!in_array($file['type'], $allowed)) return null;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = bin2hex(random_bytes(10)) . "_" . time() . "." . $ext;
    $target = __DIR__ . '/../storage/uploads/' . $newName;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return $newName;
    }
    return null;
}