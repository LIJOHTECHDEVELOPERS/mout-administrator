<?php
// Define role hierarchy and permissions
$roleHierarchy = [
    'admin' => ['manager', 'accountant', 'clerk', 'guest'],
    'manager' => ['accountant', 'clerk', 'guest'],
    'accountant' => ['clerk', 'guest'],
    'clerk' => ['guest'],
    'guest' => []
];

$accountPermissions = [
    'admin' => ['view' => 'all', 'withdraw' => 'all'],
    'manager' => ['view' => 'all', 'withdraw' => ['savings', 'checking']],
    'accountant' => ['view' => ['savings', 'checking', 'investment'], 'withdraw' => ['savings', 'checking']],
    'clerk' => ['view' => ['savings', 'checking'], 'withdraw' => ['savings']],
    'guest' => ['view' => [], 'withdraw' => []]
];

function hasRole($requiredRole) {
    global $roleHierarchy;
    $userRole = $_SESSION['user_role'] ?? 'guest';
    return $userRole === $requiredRole || in_array($requiredRole, $roleHierarchy[$userRole] ?? []);
}

function canViewAccount($accountType) {
    global $accountPermissions;
    $userRole = $_SESSION['user_role'] ?? 'guest';
    $viewPermission = $accountPermissions[$userRole]['view'];
    return $viewPermission === 'all' || in_array($accountType, $viewPermission);
}

function canWithdrawFromAccount($accountType) {
    global $accountPermissions;
    $userRole = $_SESSION['user_role'] ?? 'guest';
    $withdrawPermission = $accountPermissions[$userRole]['withdraw'];
    return $withdrawPermission === 'all' || in_array($accountType, $withdrawPermission);
}
?>