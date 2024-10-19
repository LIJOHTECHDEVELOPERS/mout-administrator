$(document).ready(function () {
    loadAccounts();

    // Tab switching functionality
    $('.tab').click(function() {
        $('.tab').removeClass('active');
        $('.tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + $(this).data('tab')).addClass('active');
    });

    // Form submission handlers
    $('#addAccountForm').on('submit', function (e) {
        e.preventDefault();
        addAccount();
    });

    $('#fundTransferForm').on('submit', function (e) {
        e.preventDefault();
        transferFunds();
    });

    $('#generateReportForm').on('submit', function(e) {
        e.preventDefault();
        generateReport();
    });

    // Show/hide custom date range field
    $('#dateRange').change(function() {
        if ($(this).val() === 'custom') {
            $('#customDateRange').show();
        } else {
            $('#customDateRange').hide();
        }
    });
});

// Function to load accounts and populate data
function loadAccounts() {
    $.ajax({
        url: 'get_accounts.php',
        method: 'GET',
        dataType: 'json',
        success: function (accounts) {
            updateAccountCards(accounts);
            updateTransferOptions(accounts);
        },
        error: function () {
            Swal.fire('Error', 'Failed to load accounts', 'error');
        }
    });
}

// Function to update account cards
function updateAccountCards(accounts) {
    const container = $('#accountCardsContainer');
    container.empty();

    let totalBalance = 0;
    if (accounts.length === 0) {
        container.append('<p>No accounts available</p>');
        return;
    }

    accounts.forEach(account => {
        totalBalance += parseFloat(account.balance);

        // Ensure an icon is displayed, default if missing
        const iconClass = account.icon ? account.icon : 'default-icon-class'; // Default icon

        const card = `
            <div class="col">
                <div class="card-stats">
                    <div class="card-body">
                        <div class="card-icon">
                            <i class="${iconClass}"></i> <!-- Use the icon class or a default one -->
                        </div>
                        <p class="card-category">${account.name}</p>
                        <h3 class="card-title">Ksh ${parseFloat(account.balance).toFixed(2)}</h3>
                    </div>
                </div>
            </div>
        `;
        container.append(card);
    });

    $('#totalBalance').text(totalBalance.toFixed(2));
}

// Function to update transfer form select options
function updateTransferOptions(accounts) {
    const fromSelect = $('#fromAccount');
    const toSelect = $('#toAccount');
    fromSelect.empty();
    toSelect.empty();

    if (accounts.length === 0) {
        fromSelect.append('<option>No accounts available</option>');
        toSelect.append('<option>No accounts available</option>');
        return;
    }

    accounts.forEach(account => {
        fromSelect.append(`<option value="${account.id}">${account.name}</option>`);
        toSelect.append(`<option value="${account.id}">${account.name}</option>`);
    });
}

function addAccount() {
    const accountName = $('#accountName').val().trim();
    const accountBalance = $('#initialBalance').val().trim();
    const accountIcon = $('#accountIcon').val();

    // Client-side validation
    if (!accountName || !accountBalance || isNaN(accountBalance) || !accountIcon) {
        Swal.fire('Error', 'Please enter valid account details', 'error');
        return;
    }

    $.ajax({
        url: './add_account.php',
        method: 'POST',
        data: { name: accountName, balance: accountBalance, icon: accountIcon },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                Swal.fire('Success', 'Account added successfully!', 'success');
                loadAccounts();  // Reload accounts to update the view
                $('#addAccountForm')[0].reset();
            } else {
                Swal.fire('Error', response.error || 'Failed to add account', 'error');
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            console.error('AJAX error:', textStatus, errorThrown);
            Swal.fire('Error', 'Failed to add account. Please try again.', 'error');
        }
    });
}

// Function to generate reports
function generateReport() {
    const reportType = $('#reportType').val();
    const dateRange = $('#dateRange').val();
    const exportFormat = $('#exportFormat').val();
    let startDate = '';
    let endDate = '';

    if (dateRange === 'custom') {
        startDate = $('#startDate').val();
        endDate = $('#endDate').val();
    }

    $.ajax({
        url: 'generate_report.php',
        method: 'POST',
        data: { reportType, dateRange, startDate, endDate, exportFormat },
        xhrFields: { responseType: 'blob' },
        success: function(blob, status, xhr) {
            const filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1];
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            link.click();
        },
        error: function() {
            Swal.fire('Error', 'Failed to generate report', 'error');
        }
    });
}

// Function to handle fund transfers
// Function to handle fund transfers
function transferFunds() {
    const fromAccount = $('#fromAccount').val();
    const toAccount = $('#toAccount').val();
    const amount = $('#transferAmount').val();

    $.ajax({
        url: 'transfer.php',
        method: 'POST',
        data: { from_account_id: fromAccount, to_account_id: toAccount, amount: amount },
        success: function (response) {
            if (response.success) {
                Swal.fire('Success', 'Funds transferred successfully!', 'success');
                loadAccounts();  // Reload accounts to reflect balance changes
                $('#fundTransferForm')[0].reset();
            } else {
                Swal.fire('Error', response.error, 'error');  // Display the specific error message
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            Swal.fire('Error', 'Failed to transfer funds: ' + textStatus + ' - ' + errorThrown, 'error'); // Display AJAX error
        }
    });
}
