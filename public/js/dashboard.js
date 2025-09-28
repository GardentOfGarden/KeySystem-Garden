class EclipseAuth {
    constructor() {
        this.apiBase = 'api/auth.php';
        this.keys = [];
        this.init();
    }

    async init() {
        await this.checkAuth();
        await this.loadKeys();
        this.updateStats();
    }

    async checkAuth() {
        const token = localStorage.getItem('eclipse_token');
        if (!token) {
            window.location.href = 'login.html';
            return;
        }
    }

    async makeRequest(action, data = {}) {
        try {
            const response = await fetch(`${this.apiBase}?action=${action}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            return await response.json();
        } catch (error) {
            console.error('API request failed:', error);
            return { success: false, error: 'Network error' };
        }
    }

    async generateKey() {
        const result = await this.makeRequest('generate_key');
        
        if (result.success) {
            await this.loadKeys();
            this.showNotification(result.data.message, 'success');
        } else {
            this.showNotification(result.error, 'error');
        }
    }

    async deleteKey(keyId) {
        if (confirm('Вы уверены, что хотите удалить этот ключ?')) {
            const result = await this.makeRequest('delete_key', { key_id: keyId });
            
            if (result.success) {
                await this.loadKeys();
                this.showNotification(result.data.message, 'success');
            } else {
                this.showNotification(result.error, 'error');
            }
        }
    }

    async loadKeys() {
        const result = await this.makeRequest('get_keys');
        
        if (result.success) {
            this.keys = result.data.keys || [];
            this.renderKeys(this.keys);
            this.updateStats(this.keys);
        }
    }

    renderKeys(keys) {
        const tbody = document.getElementById('keys-table-body');
        
        if (keys.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray);">
                        Ключей пока нет. Нажмите "Сгенерировать ключ"
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = '';

        keys.forEach(key => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${key.license_key}</td>
                <td><span class="status-active">Активен</span></td>
                <td>${new Date().toLocaleDateString('ru-RU')}</td>
                <td>${key.expires_at ? new Date(key.expires_at).toLocaleDateString('ru-RU') : 'Не ограничен'}</td>
                <td>Нет</td>
                <td>
                    <button onclick="auth.deleteKey('${key.license_key}')" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Удалить</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    updateStats(keys = []) {
        const totalKeys = keys.length;
        const usedKeys = keys.filter(k => k.used).length;
        const activeKeys = keys.filter(k => k.status === 'active').length;
        
        // Обновляем DOM
        document.getElementById('active-keys').textContent = activeKeys;
        document.getElementById('total-keys').textContent = totalKeys;
        document.getElementById('used-keys').textContent = usedKeys;
        document.getElementById('online-users').textContent = usedKeys;
        document.getElementById('license-usage').textContent = totalKeys > 0 ? Math.round((usedKeys / totalKeys) * 100) + '%' : '0%';
        document.getElementById('available-licenses').textContent = totalKeys - usedKeys;
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 10000;
            transition: all 0.3s ease;
            background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#6366f1'};
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

const auth = new EclipseAuth();

function generateKey() {
    auth.generateKey();
}

// Выход из системы
document.querySelector('.login-btn').addEventListener('click', function(e) {
    e.preventDefault();
    localStorage.removeItem('eclipse_token');
    window.location.href = 'login.html';
});
