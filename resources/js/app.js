import Alpine from 'alpinejs'

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? ''

/** Fire-and-forget POST — never blocks the interface. */
const post = (url, body = {}) =>
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(body),
    }).catch(() => {})

window.cmTrack = (name, label = null, lead = null) => post('/track', { name, label, lead })

Alpine.data('quiz', (config) => ({
    questions: config.questions ?? [],
    answers: { ...(config.saved?.answers ?? {}) },
    index: 0,
    stage: 'questions', // questions | lead
    submitting: false,
    routes: config.routes,
    labels: config.labels ?? {},
    syncTimer: null,

    init() {
        const saved = Number(config.saved?.index ?? 0)
        this.index = Math.min(Math.max(saved, 0), Math.max(this.questions.length - 1, 0))

        // A validation error bounces the visitor back to the lead step.
        if (config.hasErrors) {
            this.stage = 'lead'
        }

        this.$watch('index', () => this.scrollTop())
        this.$watch('stage', () => this.scrollTop())
    },

    get total() {
        return this.questions.length
    },

    get question() {
        return this.questions[this.index] ?? null
    },

    get progress() {
        const done = this.stage === 'lead' ? this.total : this.index
        return Math.round((done / Math.max(this.total, 1)) * 100)
    },

    get stepLabel() {
        if (this.stage === 'lead') return this.labels.lastStep ?? ''

        return (this.labels.step ?? ':current / :total')
            .replace(':current', this.index + 1)
            .replace(':total', this.total)
    },

    /** Flattened hidden inputs so the final POST carries every answer. */
    get fields() {
        const out = []
        for (const [key, value] of Object.entries(this.answers)) {
            if (Array.isArray(value)) {
                value.forEach((v, i) => out.push({ name: `answers[${key}][${i}]`, value: v }))
            } else if (value !== null && value !== undefined && value !== '') {
                out.push({ name: `answers[${key}]`, value })
            }
        }
        return out
    },

    /** Keyboard: ← next, → previous (RTL), 1-9 picks an option. */
    onKey(event) {
        if (this.stage !== 'questions') return
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) return

        if (event.key === 'ArrowLeft' || event.key === 'Enter') return this.next()
        if (event.key === 'ArrowRight') return this.previous()

        const digit = Number(event.key)
        if (Number.isInteger(digit) && digit > 0 && this.question?.options?.[digit - 1]) {
            this.select(this.question, this.question.options[digit - 1])
        }
    },

    isSelected(questionKey, optionKey) {
        const value = this.answers[questionKey]
        return Array.isArray(value) ? value.includes(optionKey) : value === optionKey
    },

    detailKey(questionKey) {
        return `${questionKey}_detail`
    },

    /** Does the current selection require a free-text detail (e.g. "other sector")? */
    needsDetail(question) {
        if (!question) return null
        const selected = question.options?.filter((o) => this.isSelected(question.key, o.key) && o.requires_detail)
        return selected?.length ? selected[0] : null
    },

    select(question, option) {
        if (question.type === 'multiple') {
            const current = Array.isArray(this.answers[question.key]) ? [...this.answers[question.key]] : []
            const at = current.indexOf(option.key)
            at === -1 ? current.push(option.key) : current.splice(at, 1)
            this.answers[question.key] = current
        } else {
            this.answers[question.key] = option.key
            if (!option.requires_detail) {
                delete this.answers[this.detailKey(question.key)]
            }
        }

        this.sync()
        this.haptic()

        // Single-choice questions advance on their own — unless a detail is needed.
        if (question.type === 'single' && !option.requires_detail) {
            setTimeout(() => this.next(), 260)
        }
    },

    canAdvance() {
        const question = this.question
        if (!question || !question.required) return true

        const value = this.answers[question.key]
        const answered = Array.isArray(value) ? value.length > 0 : !!value
        if (!answered) return false

        const detail = this.needsDetail(question)
        if (detail) {
            const text = this.answers[this.detailKey(question.key)]
            return !!(text && String(text).trim().length > 1)
        }

        return true
    },

    next() {
        if (!this.canAdvance()) return

        post(this.routes.progress, { question: this.question?.key ?? '' })

        if (this.index < this.total - 1) {
            this.index += 1
            this.sync()
            return
        }

        this.stage = 'lead'
        this.sync()
        post(this.routes.completed)
    },

    previous() {
        if (this.stage === 'lead') {
            this.stage = 'questions'
            this.index = this.total - 1
            return
        }

        if (this.index > 0) {
            this.index -= 1
            this.sync()
            return
        }

        window.location.href = this.routes.landing
    },

    /** Persist progress server-side so a refresh never loses answers. */
    sync() {
        clearTimeout(this.syncTimer)
        this.syncTimer = setTimeout(() => {
            post(this.routes.sync, { answers: this.answers, index: this.index })
        }, 220)
    },

    submit(event) {
        if (this.submitting) {
            event.preventDefault()
            return
        }
        this.submitting = true
    },

    scrollTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    },

    haptic() {
        if (navigator.vibrate) navigator.vibrate(8)
    },
}))

Alpine.data('phoneField', (config) => ({
    countries: config.countries ?? [],
    locale: config.locale ?? 'ar',
    search: '',
    open: false,
    selected: null,

    init() {
        const preferred = config.value || config.defaultDial
        this.selected =
            this.countries.find((c) => c.dial === preferred) ??
            this.countries.find((c) => c.iso === config.defaultIso) ??
            this.countries[0]
    },

    /** Country name in the interface language. */
    label(country) {
        return this.locale === 'en' ? country.name_en : country.name_ar
    },

    get filtered() {
        const term = this.search.trim().toLowerCase()
        if (!term) return this.countries
        return this.countries.filter(
            (c) =>
                c.name_ar.includes(term) ||
                c.name_en.toLowerCase().includes(term) ||
                c.dial.includes(term) ||
                c.iso.toLowerCase() === term,
        )
    },

    choose(country) {
        this.selected = country
        this.open = false
        this.search = ''
        this.$nextTick(() => this.$refs.phone?.focus())
    },

    toggle() {
        this.open = !this.open
        if (this.open) this.$nextTick(() => this.$refs.search?.focus())
    },
}))

window.Alpine = Alpine
Alpine.start()

/**
 * Scroll-driven portal transition.
 *
 * The stage is sticky; as the section scrolls past, the artwork scales toward
 * the doorway and a white wash hands over to the section below. Everything is
 * written as custom properties, so CSS owns the presentation and this file only
 * reports progress. No dependencies, no scroll hijacking — the page scrolls
 * natively and the effect is purely decorative.
 */
;(function () {
    var section = document.getElementById('portal')
    if (!section) return

    var stage = section.querySelector('.portal__stage')
    if (!stage) return

    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)')

    // 1671px-wide source: 4.5x is the most it can take before the zoom turns
    // mushy, and the wash starts early enough to cover the softest frames.
    var MAX_SCALE = 4.5
    var MAX_SCALE_SMALL = 3 // smaller composited layer for mobile GPUs
    var SMALL_VIEWPORT = 640
    var WASH_START = 0.45
    var WASH_END = 0.85

    /*
     | The headline's white->dark flip is a switch, not a ramp.
     |
     | Interpolating the text from white to dark while the background goes from
     | dark to white walks both through mid-tone at the same time, and contrast
     | collapses in the middle (measured: 1.6:1). Flipping at a single point --
     | with the scrim leaving at the same instant, while the artwork is still
     | visible -- keeps every scroll position on one side or the other:
     | white on the dark scrim before, dark on the brightening wash after.
     | CSS transitions the two over 140ms so it still reads as a tonal shift.
     */
    var TINT_SNAP = 0.66
    var ORIGIN_X = 51 // measured: centre of the doorway aperture
    var ORIGIN_Y = 39 // measured: the glow inside it

    stage.style.setProperty('--origin-x', ORIGIN_X + '%')
    stage.style.setProperty('--origin-y', ORIGIN_Y + '%')

    var ticking = false

    function clamp(v, a, b) {
        return v < a ? a : v > b ? b : v
    }

    function range(v, a, b) {
        return clamp((v - a) / (b - a), 0, 1)
    }

    function maxScale() {
        return window.innerWidth < SMALL_VIEWPORT ? MAX_SCALE_SMALL : MAX_SCALE
    }

    function reset() {
        stage.style.setProperty('--scale', '1')
        stage.style.setProperty('--wash', '0')
        stage.style.setProperty('--tint', '0')
        stage.style.setProperty('--title-scale', '1')
    }

    function update() {
        ticking = false

        if (reduced.matches) {
            reset()
            return
        }

        var rect = section.getBoundingClientRect()
        var travel = section.offsetHeight - window.innerHeight
        if (travel <= 0) return

        var progress = clamp(-rect.top / travel, 0, 1)
        var scale = Math.exp(progress * Math.log(maxScale()))

        stage.style.setProperty('--scale', scale.toFixed(4))
        stage.style.setProperty('--wash', range(progress, WASH_START, WASH_END).toFixed(4))
        stage.style.setProperty('--tint', progress >= TINT_SNAP ? '1' : '0')
        stage.style.setProperty('--title-scale', (1 + progress * 0.12).toFixed(4))
    }

    function onScroll() {
        if (!ticking) {
            ticking = true
            requestAnimationFrame(update)
        }
    }

    window.addEventListener('scroll', onScroll, { passive: true })
    window.addEventListener('resize', onScroll, { passive: true })

    if (typeof reduced.addEventListener === 'function') {
        reduced.addEventListener('change', update)
    }

    update()
})()
