// Toggle sidebar visibility
document.getElementById('sidebar-toggle').addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('active');
    document.getElementById('content').classList.toggle('expanded');
});

// Handle search functionality (assuming AJAX is not used here)
document.getElementById('search-input').addEventListener('input', function () {
    const query = this.value.toLowerCase();
    const rows = document.querySelectorAll('#users-table tbody tr');

    rows.forEach(row => {
        const name = row.cells[1].textContent.toLowerCase();
        const email = row.cells[4].textContent.toLowerCase();
        row.style.display = name.includes(query) || email.includes(query) ? '' : 'none';
    });
});

// Export to PDF
document.getElementById('export-pdf').addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    doc.text('Dashboard Data', 10, 10);
    doc.save('dashboard.pdf');
});

// Export to XLS
document.getElementById('export-xls').addEventListener('click', () => {
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(document.getElementById('users-table'));
    XLSX.utils.book_append_sheet(wb, ws, 'Users');
    XLSX.writeFile(wb, 'dashboard.xlsx');
});
