$(document).ready(function() {
    loadAccounts();

    $('#withdrawalForm').submit(function(e) {
        e.preventDefault();
        processWithdrawal();
    });
});

function loadAccounts() {
    $.ajax({
        url: 'get_accounts.php',
        type: 'GET',
        data: {
            role: '<?php echo $userRole; ?>',
            user_id: '<?php echo $userId; ?>'
        },
        dataType: 'json',
        success: function(accounts) {
            updateAccountCards(accounts);
            updateAccountSelect(accounts);
        },
        error: function(xhr, status, error) {
            console.error("Error fetching accounts:", error);
        }
    });
}

function updateAccountCards(accounts) {
    var cardsHtml = '';
    accounts.forEach(function(account) {
        cardsHtml += `
            <div class="card">
                <div class="card-icon"><i class="${account.icon}"></i></div>
                <div class="card-title">${account.name}</div>
                <div class="card-balance">Ksh${account.balance.toFixed(2)}</div>
            </div>
        `;
    });
    $('#accountCards').html(cardsHtml);
}

function updateAccountSelect(accounts) {
    var selectHtml = '<option value="">Select Account</option>';
    accounts.forEach(function(account) {
        selectHtml += `<option value="${account.id}">${account.name}</option>`;
    });
    $('#accountSelect').html(selectHtml);
}

function processWithdrawal() {
const accountId = $('#accountSelect').val();
const amount = $('#withdrawalAmount').val();
const phone = $('#withdrawalPhone').val(); // Get phone number

$.ajax({
url: 'process_withdrawal.php',
method: 'POST',
data: {
    accountId: accountId,
    amount: amount,
    phone: phone // Include phone number in the data
},
success: function(response) {
    try {
        const res = JSON.parse(response);
        if (res.success) {
            Swal.fire('Success', res.message, 'success');
            loadAccounts();
            $('#withdrawalForm')[0].reset();
        } else {
            Swal.fire('Error', res.message, 'error');
        }
    } catch (e) {
        // Catch parsing error and show raw response for debugging
        console.error('Failed to parse JSON:', response);
        Swal.fire('Error', 'Server returned an invalid response. Check the console for more details.', 'error');
    }
},
error: function() {
    Swal.fire('Error', 'Failed to process withdrawal', 'error');
}
});
}
