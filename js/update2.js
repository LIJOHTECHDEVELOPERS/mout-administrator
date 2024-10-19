document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle functionality
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.querySelector('#content nav .bx-menu');
    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            if (sidebar) {
                sidebar.classList.toggle('hide');
            }
        });
    }

    // Dynamic content loading
    const tabs = {
        'dashboard-tab': 'dashboard.php',
        'mission-users-tab': 'mission_users.php',
        'message-page-tab': 'message_page.php',
        'associates-tab': 'associates.php',
        'members-tab': 'members.php'
    };

    Object.keys(tabs).forEach(tabId => {
        const tabElement = document.getElementById(tabId);
        if (tabElement) {
            tabElement.addEventListener('click', function() {
                loadContent(tabs[tabId], tabId); // Fixed typo here
            });
        }
    });

    // Light/Dark mode switch
    const switchMode = document.getElementById('switch-mode');
    if (switchMode) {
        switchMode.addEventListener('change', function() {
            document.body.classList.toggle('light-mode');
            switchMode.checked = document.body.classList.contains('light-mode');
        });
    }

    // Search functionality
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchInput = document.getElementById('search-input-users');
            if (searchInput) {
                loadContent('mission_users.php?search=' + searchInput.value, 'mission-users-tab');
            }
        });
    }

    // Export to PDF
    const exportPdfBtn = document.getElementById('export-pdf');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            const usersTable = document.getElementById('users-table');
            if (usersTable) {
                doc.autoTable({ html: usersTable });
                doc.save('users.pdf');
            }
        });
    }

    // Export to Excel
    const exportXlsBtn = document.getElementById('export-xls');
    if (exportXlsBtn) {
        exportXlsBtn.addEventListener('click', function () {
            const table = document.getElementById('users-table');
            if (table) {
                const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet JS" });
                XLSX.writeFile(wb, 'users.xlsx');
            }
        });
    }

    // Add associate functionality (To be implemented)
    const addAssociateBtn = document.getElementById('add-associate-btn');
    if (addAssociateBtn) {
        addAssociateBtn.addEventListener('click', function() {
            // Show a form or modal to add a new associate
            // Handle form submission to add_associates.php
        });
    }

    // Excel import functionality (To be implemented)
    const importExcelBtn = document.getElementById('import-excel-btn');
    if (importExcelBtn) {
        importExcelBtn.addEventListener('click', function() {
            // Implement Excel import functionality
        });
    }

    // Export Associates to Excel
    const exportExcelBtn = document.getElementById('export-excel-btn');
    if (exportExcelBtn) {
        exportExcelBtn.addEventListener('click', function() {
            const table = document.getElementById('associates-table');
            if (table) {
                const wb = XLSX.utils.table_to_book(table, { sheet: "Sheet JS" });
                XLSX.writeFile(wb, 'associates.xlsx');
            }
        });
    }

    // Search functionality for associates
    const searchFormAssociates = document.querySelector('.search-form');
    if (searchFormAssociates) {
        searchFormAssociates.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchInput = document.getElementById('search-input-associates');
            if (searchInput) {
                loadContent('associates.php?search=' + searchInput.value, 'associates-tab');
            }
        });
    }
});

function loadContent(url, tabId) {
    fetch(url)
        .then(response => response.text())
        .then(html => {
            const mainContent = document.getElementById('main-content');
            if (mainContent) {
                mainContent.innerHTML = html;
                document.querySelectorAll('.side-menu li').forEach(li => li.classList.remove('active'));
                const activeTab = document.getElementById(tabId);
                if (activeTab) {
                    activeTab.classList.add('active');
                }
            }
        })
        .catch(error => console.error('Error loading content:', error));
}
