document.addEventListener('DOMContentLoaded', function () {
    // Get references to the sidebar and the toggle button
    const sidebar = document.getElementById('sidebar');
    const toggleButton = document.querySelector('#content nav .bx-menu');

    // Toggle the sidebar visibility when the toggle button is clicked
    if (toggleButton) {
        toggleButton.addEventListener('click', function () {
            if (sidebar) {
                sidebar.classList.toggle('hide');
            }
        });
    }

    // Define the mapping between tab IDs and their corresponding page URLs
    const tabs = {
        'dashboard-tab': 'dashboard.php',
        'mission-users-tab': 'mission_users.php',
        'message-page-tab': 'send_message.php',
        'associates-tab': 'associates.php',
        'members-tab': 'members.php'  // Ensure the file name is correct and exists
    };

    // Add click event listeners to the tabs to load the corresponding content dynamically
    Object.keys(tabs).forEach(tabId => {
        const tabElement = document.getElementById(tabId);
        if (tabElement) {
            tabElement.addEventListener('click', function() {
                loadContent(tabs[tabId]);
            });
        }
    });

    // Function to fetch and load content into the content area
    function loadContent(page) {
        fetch(page)
            .then(response => response.text())
            .then(data => {
                document.getElementById('content-area').innerHTML = data;
            })
            .catch(error => console.error('Error loading page:', error));
    }

    // Handle status changes when status buttons are clicked
    document.querySelectorAll('.status-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const currentStatus = this.getAttribute('data-status');
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

            // Send the status update to the server via a POST request
            fetch('index.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id, status: newStatus }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the button and row to reflect the new status
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

    // Handle the modal opening for editing member details
    document.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', function () {
            const row = this.closest('tr');
            const id = row.getAttribute('data-id');
            const name = row.querySelector('td:nth-child(1)').textContent;
            const whatsapp = row.querySelector('td:nth-child(2)').textContent;
            const yearOfStudy = row.querySelector('td:nth-child(3)').textContent;

            // Populate the modal fields with the existing data
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-whatsapp').value = whatsapp;
            document.getElementById('edit-year-of-study').value = yearOfStudy;

            // Display the modal
            document.getElementById('editModal').style.display = 'block';
        });
    });

    // Close the modal when the close button is clicked
    document.getElementById('closeModal').addEventListener('click', function () {
        document.getElementById('editModal').style.display = 'none';
    });

    // Search functionality for filtering table rows based on input
    document.getElementById('search-bar').addEventListener('input', function () {
        const searchText = this.value.toLowerCase();
        document.querySelectorAll('.member-table tbody tr').forEach(row => {
            const name = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            row.style.display = name.includes(searchText) ? '' : 'none';
        });
    });
});
