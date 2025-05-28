
// Update dashboard stats in real-time
function updateDashboardStats() {
    fetch('../api/properties.php?action=get_stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update total property value
                const totalValueElement = document.querySelector('.stat-card.purple h2');
                if (totalValueElement && data.total_value) {
                    totalValueElement.textContent = '$' + Number(data.total_value).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                // Update other stats...
                updatePropertyTypeChart(data);
            }
        })
        .catch(error => console.error('Error updating dashboard:', error));
}

// Update every 30 seconds
setInterval(updateDashboardStats, 30000);

// Also update when page loads
document.addEventListener('DOMContentLoaded', updateDashboardStats);

// Listen for property changes via WebSocket
const ws = new WebSocket('wss://' + window.location.hostname + '/websocket');
ws.onmessage = function(event) {
    const data = JSON.parse(event.data);
    if (data.type === 'property_update') {
        updateDashboardStats();
    }
};
