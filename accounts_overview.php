<?php 
session_start();
include('db.php');
include('report_functions.php');

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" href="assets/img/kaiadmin/favicon.ico" type="image/x-icon" />
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/plugins.min.css" />
    <link rel="stylesheet" href="assets/css/kaiadmin.min.css" />
    <link href="assets/css/custom.css" rel="stylesheet" /> <!-- Move inline CSS to this file -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="main-panel">
            <?php include 'header.php'; ?>
            <div class="container">
                <div class="page-inner">
                    <div class="container">
                        <h1>Accounts Module</h1>
                        <div id="totalBalanceCard" class="col">
                            <div class="card-stats" style="background-color: #ffc107;">
                                <div class="card-body">
                                    <div class="card-icon">
                                        <i class="fas fa-coins"></i>
                                    </div>
                                    <p class="card-category">Total Balance</p>
                                    <h3 class="card-title">Ksh <span id="totalBalance">0.00</span></h3>
                                </div>
                            </div>
                        </div>

                        <div id="accountCardsContainer" class="row">
                            <!-- Account cards dynamically inserted here -->
                        </div>

                        <div class="tabs">
    <button class="tab active" data-tab="add-account">Add Account</button>
    <button class="tab" data-tab="transfer-funds">Transfer Funds</button>
    <button class="tab" data-tab="generate-report">Generate Report</button>
</div>

<div id="add-account" class="tab-content active">
    <h2>Add New Account</h2>
    <form id="addAccountForm">
        <div class="form-group">
            <label for="accountName">Account Name</label>
            <input type="text" id="accountName" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="initialBalance">Initial Balance</label>
            <input type="number" id="initialBalance" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="accountIcon">Account Icon</label>
            <select class="form-control" id="accountIcon" name="accountIcon" required>
                <option value="fas fa-wallet">Wallet</option>
                <option value="fas fa-money-bill-wave">Money</option>
                <option value="fas fa-hand-holding-usd">Offering</option>
                <option value="fas fa-store">Store</option>
                <option value="fas fa-heart">Heart</option>
                <option value="fas fa-piggy-bank">Savings</option>
            </select>
        </div>
        <button type="submit" class="btn">Add Account</button>
    </form>
</div>

<div id="transfer-funds" class="tab-content">
    <h2>Transfer Funds</h2>
    <form id="fundTransferForm">
        <div class="form-group">
            <label for="fromAccount">From Account</label>
            <select id="fromAccount" class="form-control" required>
                <option value="">Select Account</option>
                <!-- Populate dynamically with accounts -->
            </select>
        </div>
        <div class="form-group">
            <label for="toAccount">To Account</label>
            <select id="toAccount" class="form-control" required>
                <option value="">Select Account</option>
                <!-- Populate dynamically with accounts -->
            </select>
        </div>
        <div class="form-group">
            <label for="transferAmount">Amount</label>
            <input type="number" id="transferAmount" class="form-control" required>
        </div>
        <button type="submit" class="btn">Transfer Funds</button>
    </form>
</div>

<div id="generate-report" class="tab-content">
    <h2>Generate Account Report</h2>
    <form id="generateReportForm">
        <div class="form-group">
            <label for="reportAccount">Account</label>
            <select id="reportAccount" class="form-control" required>
                <option value="">Select Account</option>
                <!-- Populate dynamically with accounts -->
            </select>
        </div>
        <div class="form-group">
            <label for="reportPeriod">Report Period</label>
            <input type="date" id="reportPeriod" class="form-control" required>
        </div>
        <button type="submit" class="btn">Generate Report</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- JS Scripts -->
    <script src="assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="assets/js/core/popper.min.js"></script>
    <script src="assets/js/core/bootstrap.min.js"></script>
    <script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="assets/js/kaiadmin.min.js"></script>
    <script src="assets/js/custom.js"></script> <!-- Externalize JS here -->
</body>
</html>
