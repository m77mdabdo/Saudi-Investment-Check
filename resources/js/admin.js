import Alpine from 'alpinejs'
import Chart from 'chart.js/auto'

window.Chart = Chart

Chart.defaults.font.family = "'Tajawal', ui-sans-serif, system-ui, sans-serif"
Chart.defaults.color = '#64748b'
Chart.defaults.plugins.legend.display = false

Alpine.data('adminShell', () => ({
    sidebar: false,
    toast: null,

    init() {
        const flash = document.getElementById('flash-data')
        if (flash) {
            const { type, message } = JSON.parse(flash.textContent)
            if (message) this.notify(message, type)
        }
    },

    notify(message, type = 'success') {
        this.toast = { message, type }
        setTimeout(() => (this.toast = null), 4200)
    },
}))

Alpine.data('confirmAction', (message) => ({
    confirm(event) {
        if (!window.confirm(message)) event.preventDefault()
    },
}))

Alpine.data('copyable', () => ({
    copied: false,
    copy(text) {
        navigator.clipboard?.writeText(text).then(() => {
            this.copied = true
            setTimeout(() => (this.copied = false), 1800)
        })
    },
}))

/** Renders the dashboard charts from JSON embedded in the page. */
window.cmChart = (canvasId, type, data, options = {}) => {
    const el = document.getElementById(canvasId)
    if (!el) return

    return new Chart(el, {
        type,
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            ...options,
        },
    })
}

/**
 * Every admin form gets a submitting state, so no action ever looks like it
 * did nothing. Skips forms that navigate instantly (GET filters).
 */
document.addEventListener('submit', (event) => {
    const form = event.target
    if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() === 'get') return

    const button = form.querySelector('button[type="submit"], button:not([type])')
    if (!button || button.dataset.busy) return

    button.dataset.busy = '1'
    button.dataset.label = button.innerHTML
    button.disabled = true
    button.innerHTML = '<span class="inline-block h-3.5 w-3.5 animate-spin rounded-full border-2 border-current border-t-transparent"></span> جاري الحفظ...'

    // Restore if the browser stays on the page (validation error, back button).
    setTimeout(() => {
        if (!button.isConnected) return
        button.disabled = false
        button.innerHTML = button.dataset.label
        delete button.dataset.busy
    }, 8000)
}, true)

window.Alpine = Alpine
Alpine.start()
