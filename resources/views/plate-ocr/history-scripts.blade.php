<script>
function exportHistory() {
    const rows = Array.from(document.querySelectorAll('#historyTableBody tr'));
    const data = [['Plate Number', 'Detection Time', 'Status', 'Confidence']];

    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        if (!cells.length) return;

        data.push([
            cells[0]?.textContent.trim(),
            cells[1]?.textContent.trim(),
            cells[2]?.textContent.trim(),
            cells[3]?.textContent.trim(),
        ]);
    });

    const csv = data.map(row => row.map(col => `"${col.replace(/\"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');

    link.href = URL.createObjectURL(blob);
    link.download = 'plate-detection-' + new Date().getTime() + '.csv';
    link.click();
}

function clearHistory() {
    if (confirm('Are you sure you want to clear all history? This cannot be undone.')) {
        document.getElementById('historyTableBody').innerHTML = 
            '<tr><td colspan="5" style="text-align: center; padding: 2rem; color: #999;">History cleared</td></tr>';
    }
}

function loadMore() {
    const params = new URLSearchParams(window.location.search);
    const current = parseInt(params.get('page') || '1', 10);
    params.set('page', current + 1);
    window.location.search = params.toString();
}
</script>
