document.addEventListener('DOMContentLoaded', () => {
    // Only execute if the flex-table for ChildServiceDataProvider exists
    const childServiceTables = document.querySelectorAll('.flex-table[data-provider="ChildServiceDataProvider"]');
    
    if (childServiceTables.length > 0) {
        setInterval(async () => {
            try {
                // Ping the server to get the updated table HTML
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch('/dataProviders?fetch_html=ChildServiceDataProvider', {
                    method: 'POST',
                    body: formData
                });
                
                // Safety mechanisms for expired sessions and CSRF token rejections
                if (response.status === 401 || response.status === 403) {
                    window.location.reload();
                    return;
                }
                
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }

                if (response.ok) {
                    const html = await response.text();
                    
                    childServiceTables.forEach(table => {
                        const temp = document.createElement('div');
                        temp.innerHTML = html;
                        
                        const newTable = temp.querySelector('.flex-table');
                        if (newTable) {
                            // Update the inner contents seamlessly
                            table.innerHTML = newTable.innerHTML;
                        }
                    });
                }
            } catch (error) {
                console.error("Failed to fetch ChildServiceDataProvider updates", error);
            }
        }, 10000); // 10 second polling
    }
});
