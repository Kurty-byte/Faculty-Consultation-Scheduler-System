<?php
// Database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character set
mysqli_set_charset($conn, "utf8mb4");

// Function to execute a query
function executeQuery($query, $params = [], $types = "") {
    global $conn;
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($params)) {
        // If types not provided, generate them
        if (empty($types)) {
            $types = str_repeat("s", count($params));
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $executed = mysqli_stmt_execute($stmt);
    
    if (!$executed) {
        error_log("SQL Error: " . mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

// Function to get a single row as associative array
function fetchRow($query, $params = [], $types = "") {
    $result = executeQuery($query, $params, $types);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Function to get multiple rows as associative array
function fetchRows($query, $params = [], $types = "") {
    $result = executeQuery($query, $params, $types);
    $rows = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    
    return $rows;
}

// Function to insert data and return the inserted ID
function insertData($query, $params = [], $types = "") {
    global $conn;
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($params)) {
        // If types not provided, generate them
        if (empty($types)) {
            $types = str_repeat("s", count($params));
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $executed = mysqli_stmt_execute($stmt);
    
    if (!$executed) {
        error_log("SQL Error: " . mysqli_stmt_error($stmt));
        return false;
    }
    
    $insertId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    return $insertId;
}

// Function to update or delete data
function updateOrDeleteData($query, $params = [], $types = "") {
    global $conn;
    
    $stmt = mysqli_prepare($conn, $query);
    
    if (!empty($params)) {
        // If types not provided, generate them
        if (empty($types)) {
            $types = str_repeat("s", count($params));
        }
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $executed = mysqli_stmt_execute($stmt);
    
    if (!$executed) {
        error_log("SQL Error: " . mysqli_stmt_error($stmt));
        return false;
    }
    
    $affectedRows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    return $affectedRows;
}
?>