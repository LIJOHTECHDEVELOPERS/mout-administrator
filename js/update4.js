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
                loadContent(tabs[tabId]);
            });
        }
    });

    function loadContent(page) {
        fetch(page)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                const contentArea = document.getElementById('content-area');
                if (contentArea) {
                    contentArea.innerHTML = data;

                    // Re-initialize the form handling when new content is loaded
                    initializeForm();
                } else {
                    console.error('Content area not found');
                }
            })
            .catch(error => console.error('Error loading page:', error));
    }

    function initializeForm() {
        const messageForm = document.querySelector('.message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', function (e) {
                e.preventDefault(); // Prevent default form submission

                const formData = new FormData(this);

                $.ajax({
                    type: 'POST',
                    url: 'send_message_handler.php', // Separate PHP file to handle message sending
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        $('#content-area').html(response); // Show success message or reload content
                    },
                    error: function () {
                        alert('An error occurred while sending the message.');
                    }
                });
            });
        }
    }

    // Status change handling
    document.querySelectorAll('.status-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id, status: newStatus }),
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.setAttribute('data-status', newStatus);
                        this.textContent = newStatus === 'active' ? 'Deactivate' : 'Activate';
                        this.closest('tr').querySelector('.status-col').textContent = newStatus;
                    } else {
                        alert('Failed to update status.');
                    }
                })
                .catch(error => console.error('Error updating status:', error));
        });
    });

    // Modal handling for editing
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            const id = row.getAttribute('data-id');
            const name = row.querySelector('td:nth-child(1)').textContent;
            const whatsapp = row.querySelector('td:nth-child(2)').textContent;
            const yearOfStudy = row.querySelector('td:nth-child(3)').textContent;

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-whatsapp').value = whatsapp;
            document.getElementById('edit-year-of-study').value = yearOfStudy;

            document.getElementById('editModal').style.display = 'block';
        });
    });

    // Close modal
    const closeModalBtn = document.getElementById('closeModal');
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function () {
            document.getElementById('editModal').style.display = 'none';
        });
    }

    // Search functionality
    const searchBar = document.getElementById('search-bar');
    if (searchBar) {
        searchBar.addEventListener('input', function () {
            const searchText = this.value.toLowerCase();
            document.querySelectorAll('.member-table tbody tr').forEach(row => {
                const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                row.style.display = name.includes(searchText) ? '' : 'none';
            });
        });
    }

    // Initialize the form handling for the first load
    initializeForm();
});
