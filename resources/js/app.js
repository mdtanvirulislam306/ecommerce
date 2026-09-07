import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const corePages = import.meta.glob('./Pages/**/*.vue');
const modulePages = import.meta.glob('../../modules/*/Resources/js/Pages/**/*.vue');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const corePath = `./Pages/${name}.vue`;
        if (corePages[corePath]) {
            return resolvePageComponent(corePath, corePages);
        }

        const [module, ...rest] = name.split('/');
        const modulePath = `../../modules/${module}/Resources/js/Pages/${rest.join('/')}.vue`;

        return resolvePageComponent(modulePath, modulePages);
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#F27D42',
        showSpinner: true,
        delay: 0,
        includeCSS: true,
    },
});
