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
        'message-page-tab': 'message_page.php',
        'associates-tab': 'associates.php',
        'members-tab': 'members.php'  // Corrected to ensure it's pointing to the right file
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
                } else {
                    console.error('Content area not found');
                }
            })
            .catch(error => console.error('Error loading page:', error));
    }
});


    