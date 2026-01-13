<?php
$page_title = 'Settings';
require_once 'includes/dashboard_header.php';
require_once 'database.php';

checkRole(['landlord', 'tenant']);

?>

<h1 class="h2 mb-4">Settings</h1>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">General Settings</h5>
    </div>
    <div class="card-body">
        <p>This is a placeholder for general settings.</p>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h5 class="mb-0">Notification Settings</h5>
    </div>
    <div class="card-body">
        <p>This is a placeholder for notification settings.</p>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h5 class="mb-0">Security Settings</h5>
    </div>
    <div class="card-body">
        <p>This is a placeholder for security settings.</p>
    </div>
</div>

<?php
require_once 'includes/dashboard_footer.php';
?>
