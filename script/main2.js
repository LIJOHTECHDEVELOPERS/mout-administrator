document.addEventListener('DOMContentLoaded', function () {
    // Toggle sidebar visibility
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.querySelector('#content nav .bx-menu');

    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            sidebar.classList.toggle('hide');
        });
    } else {
        console.error('Toggle button not found');
    }

    // Load content for each tab
    const tabMapping = {
        'dashboard-tab': 'dashboard.php',
        'mission-users-tab': 'mission_users.php',
        'message-page-tab': 'send_message.php',
        'associates-tab': 'associates.php',
        'members-tab': 'members.php',
        'accounts-tab': 'accounts.php'
    };

    for (let [tabId, url] of Object.entries(tabMapping)) {
        const tabElement = document.getElementById(tabId);
        if (tabElement) {
            tabElement.addEventListener('click', function () {
                loadContent(url, tabId);
            });
        } else {
            console.warn(`Tab element with ID ${tabId} not found`);
        }
    }

    // Mode switch functionality
    const switchMode = document.getElementById('switch-mode');
    if (switchMode) {
        switchMode.addEventListener('change', function () {
            document.body.classList.toggle('light-mode');
            switchMode.checked = document.body.classList.contains('light-mode');
        });
    }

    // Search functionality for Mission Users
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const searchInput = document.getElementById('search-input-users');
            if (searchInput) {
                loadContent('mission_users.php?search=' + searchInput.value, 'mission-users-tab');
            }
        });
    }

    // Export to PDF functionality
    const exportPdfBtn = document.getElementById('export-pdf');
    if (exportPdfBtn) {
        exportPdfBtn.addEventListener('click', function () {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.text('Dashboard Data', 10, 10);
            const table = document.getElementById('users-table');
            if (table) {
                doc.autoTable({ html: table });
                doc.save('dashboard.pdf');
            }
        });
    }

    // Export to Excel functionality
    const exportXlsBtn = document.getElementById('export-xls');
    if (exportXlsBtn) {
        exportXlsBtn.addEventListener('click', function () {
            const table = document.getElementById('users-table');
            if (table) {
                const wb = XLSX.utils.book_new();
                const ws = XLSX.utils.table_to_sheet(table);
                XLSX.utils.book_append_sheet(wb, ws, 'Users');
                XLSX.writeFile(wb, 'dashboard.xlsx');
            }
        });
    }

    // Load content function
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
});
