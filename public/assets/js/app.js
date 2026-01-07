
const API_BASE_URL = '/api';

// Helper para peticiones AJAX
async function apiRequest(url, options = {}) {
    try {
        const response = await fetch(API_BASE_URL + url, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...options.headers
            }
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.message || 'Error en la petición');
        }
        
        return data;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Mostrar notificación
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Formatear moneda
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0
    }).format(amount);
}

// Formatear número
function formatNumber(number, decimals = 2) {
    return new Intl.NumberFormat('es-CO', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

// Formatear porcentaje
function formatPercentage(value, decimals = 5) {
    if (value === null || value === undefined || value === '') return '0.00000%';
    const numValue = parseFloat(value);
    if (isNaN(numValue)) return '0.00000%';

    // El valor ya viene como decimal (ej: 0.01100 = 1.1%)
    // Multiplicamos por 100 para obtener el porcentaje
    const percentage = (numValue * 100).toFixed(decimals);
    return `${percentage}%`;
}

// Formatear fecha
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('es-CO');
}

// Confirmar acción
function confirmAction(message) {
    return confirm(message);
}

// Mostrar loading
function showLoading(element) {
    element.innerHTML = '<div class="spinner"></div>';
}

// Exportar a Excel (redireccionar)
function exportToExcel(url) {
    window.location.href = url;
}

// Inicialización al cargar el DOM
document.addEventListener('DOMContentLoaded', function() {
    // Agregar clase active al menú actual
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('.sidebar-menu a');
    
    menuLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
    
    // Manejar formularios con AJAX
    const ajaxForms = document.querySelectorAll('form[data-ajax="true"]');
    
    ajaxForms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const data = Object.fromEntries(formData);
            
            try {
                const result = await apiRequest(form.action, {
                    method: form.method,
                    body: JSON.stringify(data)
                });
                
                showNotification(result.message, 'success');
                
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1000);
                }
            } catch (error) {
                showNotification(error.message, 'danger');
            }
        });
    });
});