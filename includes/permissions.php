<?php

// ========================================
// OPTIMIZED PERMISSION SYSTEM
// ========================================
//
// This version:
// 1. Gets the user's role only once
// 2. Gets ALL permissions only once
// 3. Stores permissions in memory
// 4. hasPermission() does NOT query DB repeatedly
// 5. Admin automatically has all permissions
//
// ========================================


// ========================================
// PERMISSION CACHE
// ========================================

$GLOBALS['permission_cache'] = null;


// ========================================
// LOAD USER PERMISSIONS
// ========================================

function loadUserPermissions()
{
    global $conn;


    // ----------------------------------------
    // Already loaded?
    // ----------------------------------------

    if ($GLOBALS['permission_cache'] !== null) {

        return $GLOBALS['permission_cache'];

    }


    // ----------------------------------------
    // User not logged in
    // ----------------------------------------

    if (!isset($_SESSION['user_id'])) {

        $GLOBALS['permission_cache'] = [
            'is_admin' => false,
            'permissions' => []
        ];

        return $GLOBALS['permission_cache'];

    }


    // ----------------------------------------
    // Get User ID
    // ----------------------------------------

    $user_id = (int) $_SESSION['user_id'];


    if ($user_id <= 0) {

        $GLOBALS['permission_cache'] = [
            'is_admin' => false,
            'permissions' => []
        ];

        return $GLOBALS['permission_cache'];

    }


    // ========================================
    // GET USER ROLE
    // ========================================

    $sql = "

        SELECT

            users.role_id,

            roles.role_name

        FROM users

        LEFT JOIN roles
            ON users.role_id = roles.id

        WHERE users.id = ?

        LIMIT 1

    ";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        $GLOBALS['permission_cache'] = [
            'is_admin' => false,
            'permissions' => []
        ];

        return $GLOBALS['permission_cache'];

    }


    $stmt->bind_param(
        "i",
        $user_id
    );


    if (!$stmt->execute()) {

        $stmt->close();

        $GLOBALS['permission_cache'] = [
            'is_admin' => false,
            'permissions' => []
        ];

        return $GLOBALS['permission_cache'];

    }


    $result = $stmt->get_result();

    $user = $result->fetch_assoc();

    $stmt->close();


    // ----------------------------------------
    // User not found
    // ----------------------------------------

    if (!$user) {

        $GLOBALS['permission_cache'] = [
            'is_admin' => false,
            'permissions' => []
        ];

        return $GLOBALS['permission_cache'];

    }


    // ========================================
    // CHECK ADMIN
    // ========================================

    $role_name = strtolower(
        trim(
            $user['role_name'] ?? ''
        )
    );


    if ($role_name === 'admin') {

        $GLOBALS['permission_cache'] = [

            'is_admin' => true,

            'permissions' => []

        ];

        return $GLOBALS['permission_cache'];

    }


    // ========================================
    // GET ROLE ID
    // ========================================

    $role_id = (int) (
        $user['role_id'] ?? 0
    );


    if ($role_id <= 0) {

        $GLOBALS['permission_cache'] = [

            'is_admin' => false,

            'permissions' => []

        ];

        return $GLOBALS['permission_cache'];

    }


    // ========================================
    // GET ALL ROLE PERMISSIONS
    // ========================================

    $sql = "

        SELECT

            p.permission_name

        FROM role_permissions rp

        INNER JOIN permissions p
            ON rp.permission_id = p.id

        WHERE rp.role_id = ?

    ";


    $stmt = $conn->prepare($sql);


    if (!$stmt) {

        $GLOBALS['permission_cache'] = [

            'is_admin' => false,

            'permissions' => []

        ];

        return $GLOBALS['permission_cache'];

    }


    $stmt->bind_param(
        "i",
        $role_id
    );


    if (!$stmt->execute()) {

        $stmt->close();

        $GLOBALS['permission_cache'] = [

            'is_admin' => false,

            'permissions' => []

        ];

        return $GLOBALS['permission_cache'];

    }


    $result = $stmt->get_result();


    // ========================================
    // BUILD PERMISSION ARRAY
    // ========================================

    $permissions = [];


    while ($row = $result->fetch_assoc()) {

        if (!empty($row['permission_name'])) {

            $permissions[
                $row['permission_name']
            ] = true;

        }

    }


    $stmt->close();


    // ========================================
    // SAVE CACHE
    // ========================================

    $GLOBALS['permission_cache'] = [

        'is_admin' => false,

        'permissions' => $permissions

    ];


    return $GLOBALS['permission_cache'];

}


// ========================================
// CHECK PERMISSION
// ========================================

function hasPermission($permission_name)
{

    // ----------------------------------------
    // Invalid permission name
    // ----------------------------------------

    if (
        !is_string($permission_name)
        ||
        trim($permission_name) === ''
    ) {

        return false;

    }


    // ----------------------------------------
    // Load permission data
    // ----------------------------------------

    $data = loadUserPermissions();


    // ========================================
    // ADMIN
    // ========================================

    if (
        $data['is_admin'] === true
    ) {

        return true;

    }


    // ========================================
    // NORMAL USER
    // ========================================

    return isset(
        $data['permissions'][$permission_name]
    );

}


// ========================================
// REQUIRE PERMISSION
// ========================================

function requirePermission($permission_name)
{

    if (
        !hasPermission(
            $permission_name
        )
    ) {

        http_response_code(403);


        echo '

        <!DOCTYPE html>

        <html>

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>Access Denied</title>

            <style>

                body {

                    margin: 0;

                    font-family:
                        Arial,
                        sans-serif;

                    background:
                        #f4f6f9;

                    display: flex;

                    align-items: center;

                    justify-content: center;

                    min-height: 100vh;

                }


                .access-denied {

                    background: white;

                    padding: 40px;

                    border-radius: 12px;

                    text-align: center;

                    box-shadow:
                        0 4px 20px
                        rgba(0,0,0,0.10);

                    max-width: 450px;

                    width: 90%;

                }


                .access-denied h1 {

                    font-size: 70px;

                    margin: 0;

                    color: #dc3545;

                }


                .access-denied h2 {

                    margin-top: 10px;

                    margin-bottom: 10px;

                }


                .access-denied p {

                    color: #666;

                }


                .access-denied a {

                    display: inline-block;

                    margin-top: 15px;

                    padding:
                        10px 20px;

                    background:
                        #0d6efd;

                    color: white;

                    text-decoration: none;

                    border-radius: 6px;

                }


                .access-denied a:hover {

                    background:
                        #084298;

                }

            </style>

        </head>


        <body>

            <div class="access-denied">

                <h1>403</h1>

                <h2>Access Denied</h2>

                <p>
                    You do not have permission
                    to access this page.
                </p>

                <a href="/dashboard.php">
                    Back to Dashboard
                </a>

            </div>

        </body>

        </html>

        ';


        exit();

    }

}

?>