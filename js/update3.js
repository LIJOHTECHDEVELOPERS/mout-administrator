document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.querySelector('#content nav .bx-menu');
    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            if (sidebar) {
                sidebar.classList.toggle('hide');
            }
        });
    }

    // Real-time search functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchText = searchInput.value.toLowerCase();
            const userRows = document.querySelectorAll('#users-table tbody tr');

            userRows.forEach(row => {
                const userName = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                if (userName.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Tab switching
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

    const tabs = {
        'dashboard-tab': 'dashboard.php',
        'mission-users-tab': 'mission_users.php',
        'message-page-tab': 'send_message.php',
        'associates-tab': 'associates.php',
        'members-tab': 'members.php'
    };

    Object.keys(tabs).forEach(tabId => {
        const tabElement = document.getElementById(tabId);
        if (tabElement) {
            tabElement.addEventListener('click', function() {
                loadContent(tabs[tabId], tabId);
            });
        }
    });

    // Dark/Light mode toggle
    const switchMode = document.getElementById('switch-mode');
    if (switchMode) {
        switchMode.addEventListener('change', function() {
            document.body.classList.toggle('light-mode');
            switchMode.checked = document.body.classList.contains('light-mode');
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

    // Export to XLS
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
});
