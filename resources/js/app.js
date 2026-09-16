import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import AppFooter from './components/AppFooter';
import AppNavbar from './components/AppNavbar';

createInertiaApp({
    resolve: (name) => resolvePageComponent(
        `./pages/${name}.js`,
        import.meta.glob('./pages/**/*.js'),
    ),
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h('div', { class: 'flex min-h-screen flex-col' }, [
                h(AppNavbar),
                h('div', { class: 'flex flex-1 flex-col' }, [h(App, props)]),
                h(AppFooter),
            ]),
        })
            .use(plugin)
            .mount(el);
    },
});
