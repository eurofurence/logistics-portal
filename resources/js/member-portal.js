import { createInertiaApp } from '@inertiajs/vue3'
import axios from 'axios'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'
import '../css/member-portal.css'

axios.defaults.xsrfCookieName = 'MEMBER-XSRF-TOKEN'

window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload()
})

createInertiaApp({
    resolve: (name) => resolvePageComponent('./Pages/' + name + '.vue', import.meta.glob('./Pages/ClubMembers/{Access,Profile}.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) }).use(plugin).mount(el)
    },
    progress: { color: '#0f766e' },
})
