<?php
session_start();
require_once 'config.php';
require_once 'classes/Auth.php';
require_once 'classes/ReportGenerator.php';

$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    http_response_code(403);
    exit('Unauthorized');
}

$user = $auth->getUser();
$reportGenerator = new ReportGenerator($db, $user);
$reports = $reportGenerator->getReports();
?>

<table class="table mt-4">
    <thead>
        <tr>
            <th>User</th>
            <th>Docket</th>
            <th>Spiritual Year</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reports as $report): ?>
            <tr>
                <td><?php echo htmlspecialchars($report['user_name']); ?></td>
                <td><?php echo htmlspecialchars($report['docket_name']); ?></td>
                <td><?php echo htmlspecialchars($report['spiritual_year']); ?></td>
                <td><?php echo ucfirst(htmlspecialchars($report['status'])); ?></td>
                <td><?php echo htmlspecialchars($report['created_at']); ?></td>
                <td>
                    <a href="index.php?download=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">Download</a>
                    <button class="btn btn-sm btn-info preview-report" data-id="<?php echo $report['id']; ?>">Preview</button>
                    <?php if ($report['status'] === 'draft'): ?>
                        <button class="btn btn-sm btn-success complete-report" data-id="<?php echo $report['id']; ?>">Complete</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>