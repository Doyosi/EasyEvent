//@DOYOSI_EASY_EVENT_STUB_WIDGET
/**
 * EasyEventWidget
 * Render a small event list.
 *
 * @param {Object} options - Initialization options.
 * @param {string|HTMLElement} options.target - Selector or element to mount into.
 * @param {string} [options.endpoint='/api/easy-events'] - Endpoint returning JSON events.
 * @param {number} [options.limit=5] - Maximum events to render.
 *
 * @example
 * import { EasyEventWidget } from '@/modules/EasyEventWidget'
 * const widget = new EasyEventWidget({ target: '#eventsBox', limit: 10 })
 * widget.init()
 */
export class EasyEventWidget {
    constructor({ target, endpoint = '/api/easy-events', limit = 5 } = {}) {
        this.el = typeof target === 'string' ? document.querySelector(target) : target
        this.endpoint = endpoint
        this.limit = limit
    }

    async init() {
        if (!this.el) return
        const events = await this.fetchEvents()
        this.render(events.slice(0, this.limit))
    }

    async fetchEvents() {
        try {
            const res = await fetch(this.endpoint, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            if (!res.ok) throw new Error('Failed to load events')
            return await res.json()
        } catch (e) {
            console.error('[EasyEventWidget]', e)
            return []
        }
    }

    render(items) {
        if (!this.el) return

        if (!items.length) {
            this.el.innerHTML = '<div class="p-3 text-sm opacity-70">No events.</div>'
            return
        }

        const list = items.map(i => `
      <li class="p-3 rounded border">
        <div class="font-semibold">${i.title ?? ''}</div>
        <div class="text-sm opacity-70">${i.starts_at ?? ''}</div>
      </li>
    `).join('')

        this.el.innerHTML = `<ul class="space-y-2">${list}</ul>`
    }
}
