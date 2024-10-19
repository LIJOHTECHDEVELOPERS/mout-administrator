document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.querySelector('#content nav .bx-menu');
    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            if (sidebar) {
                sidebar.classList.toggle('hide');
            }
        });
    }

    document.getElementById('dashboard-tab').addEventListener('click', function() {
        loadContent('dashboard.php', 'dashboard-tab');
    });

    document.getElementById('mission-users-tab').addEventListener('click', function() {
        loadContent('mission_users.php', 'mission-users-tab');
    });

    document.getElementById('message-page-tab').addEventListener('click', function() {
        loadContent('message_page.php', 'message-page-tab');
    });

    document.getElementById('associates-tab').addEventListener('click', function() {
        loadContent('associates.php', 'associates-tab');
    });

    document.getElementById('switch-mode').addEventListener('change', function() {
        document.body.classList.toggle('light-mode');
        document.getElementById('switch-mode').checked = document.body.classList.contains('light-mode');
    });

    document.querySelector('.search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchInput = document.getElementById('search-input-users').value;
        loadContent('mission_users.php?search=' + searchInput, 'mission-users-tab');
    });

    document.getElementById('export-pdf').addEventListener('click', function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.autoTable({ html: '#users-table' });
        doc.save('users.pdf');
    });

    document.getElementById('export-xls').addEventListener('click', function () {
        const table = document.getElementById('users-table');
        const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet JS" });
        XLSX.writeFile(wb, 'users.xlsx');
    });

    document.getElementById('add-associate-btn').addEventListener('click', function() {
        // Show a form or modal to add a new associate
        // Handle form submission to add_associates.php
    });

    document.getElementById('import-excel-btn').addEventListener('click', function() {
        // Implement Excel import functionality
    });

    document.getElementById('export-excel-btn').addEventListener('click', function() {
        const table = document.getElementById('associates-table');
        const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet JS" });
        XLSX.writeFile(wb, 'associates.xlsx');
    });

    document.querySelector('.search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchInput = document.getElementById('search-input-associates').value;
        loadContent('associates.php?search=' + searchInput, 'associates-tab');
    });
});

function loadContent(url, tabId) {
    fetch(url)
        .then(response => response.text())
        .then(html => {
            const mainContent = document.getElementById('main-content');
            if (mainContent) {
                mainContent.innerHTML = html;
                document.querySelectorAll('.side-menu li').forEach(li => li.classList.remove('active'));
                document.getElementById(tabId).classList.add('active');
            }
        })
        .catch(error => console.error('Error loading content:', error));
}
