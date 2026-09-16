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
        return this.stage === 'lead' ? 'الخطوة الأخيرة' : `السؤال ${this.index + 1} من ${this.total}`
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
