//@DOYOSI_EASY_EVENT_STUB_ENTRY
/**
 * EasyEvent front-end entry
 * Load with: @vite('resources/js/easy-event.js')
 */
import { EasyEventWidget } from './modules/EasyEventWidget'

document.addEventListener('DOMContentLoaded', () => {
    const els = document.querySelectorAll('[data-easy-event]')
    els.forEach(el => {
        const limit = Number(el.getAttribute('data-limit') || 5)
        const endpoint = el.getAttribute('data-endpoint') || '/api/easy-events'
        const w = new EasyEventWidget({ target: el, endpoint, limit })
        w.init()
    })
})

window.DoyosiEasyEventWidget = EasyEventWidget
