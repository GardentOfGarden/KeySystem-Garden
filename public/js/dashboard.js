class EclipseAuth {
    constructor() {
        this.keys = JSON.parse(localStorage.getItem('eclipse_keys')) || [];
        this.init();
    }

    init() {
        this.renderKeys();
        this.updateStats();
    }

    generateKey() {
        const key = 'ECLIPSE-' + Math.random().toString(36).substr(2, 9).toUpperCase() + 
                   '-' + Math.random().toString(36).substr(2, 9).toUpperCase();
        
        const newKey = {
            id: Date.now(),
            key: key,
            status: 'active',
            created: new Date().toLocaleDateString('ru-RU'),
            expires: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toLocaleDateString('ru-RU'),
            hwid: null,
            used: false
        };

        this.keys.push(newKey);
        this.saveKeys();
        this.renderKeys();
        this.updateStats();
        
        this.showNotification('Ключ успешно сгенерирован!', 'success');
    }

    deleteKey(keyId) {
        if (confirm('Вы уверены, что хотите удалить этот ключ?')) {
            this.keys = this.keys.filter(k => k.id !== keyId);
            this.saveKeys();
            this.renderKeys();
            this.updateStats();
            this.showNotification('Ключ удален', 'success');
        }
    }

    renderKeys() {
        const tbody = document.getElementById('keys-table-body');
        tbody.innerHTML = '';

        this.keys.forEach(key => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${key.key}</td>
                <td><span class="status-${key.status}">${key.status === 'active' ? 'Активен' : 'Неактивен'}</span></td>
                <td>${key.created}</td>
                <td>${key.expires}</td>
                <td>
                    <button onclick="auth.deleteKey(${key.id})" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">Удалить</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    updateStats() {
        // Обновляем статистику на основе реальных данных
        const totalKeys = this.keys.length;
        const usedKeys = this.keys.filter(k => k.used).length;
        
        // Здесь можно обновлять DOM элементы со статистикой
        console.log('Stats updated:', { totalKeys, usedKeys });
    }

    saveKeys() {
        localStorage.setItem('eclipse_keys', JSON.stringify(this.keys));
    }

    showNotification(message, type = 'info') {
        // Простая реализация уведомления
        alert(message);
    }
}

// Инициализация
const auth = new EclipseAuth();

function generateKey() {
    auth.generateKey();
}
