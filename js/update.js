document.addEventListener('DOMContentLoaded', function () {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebar = document.getElementById('sidebar');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('hide');
        });
    }

    // Tabs and Dynamic Content Loading
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
            tabElement.addEventListener('click', function (e) {
                e.preventDefault();
                loadContent(tabs[tabId]);
                setActiveTab(tabId);
            });
        }
    });

    function setActiveTab(tabId) {
        document.querySelectorAll('.side-menu li').forEach(item => {
            item.classList.remove('active');
        });
        document.getElementById(tabId).classList.add('active');
    }

    function loadContent(page) {
        fetch(page)
            .then(response => response.text())
            .then(data => {
                const mainContent = document.getElementById('main-content');
                if (mainContent) {
                    mainContent.innerHTML = data;
                    initializeForm();
                }
            })
            .catch(error => console.error('Error loading content:', error));
    }

    // Initialize Form Handling
    function initializeForm() {
        const messageForm = document.querySelector('.message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', function (e) {
                e.preventDefault(); // Prevent default form submission

                const formData = new FormData(this);

                $.ajax({
                    type: 'POST',
                    url: 'send_message_handler.php',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        $('#main-content').html(response); // Show success message or reload content
                    },
                    error: function () {
                        alert('An error occurred while sending the message.');
                    }
                });
            });
        }
    }

    // Search Functionality
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchText = this.value.toLowerCase();
            document.querySelectorAll('.member-table tbody tr').forEach(row => {
                const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                row.style.display = name.includes(searchText) ? '' : 'none';
            });
        });
    }

    // Export to PDF
    document.getElementById('export-pdf').addEventListener('click', function () {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        doc.text('Mout Jkuat Evangelistic Cms Portal', 10, 10);
        doc.autoTable({ html: '#my-table' });
        doc.save('members.pdf');
    });

    // Export to XLS
    document.getElementById('export-xls').addEventListener('click', function () {
        const table = document.getElementById('my-table');
        const workbook = XLSX.utils.table_to_book(table, { sheet: "Sheet1" });
        XLSX.writeFile(workbook, 'members.xlsx');
    });

    // Load initial dashboard content
    loadContent('dashboard.php');
    setActiveTab('dashboard-tab');
});
