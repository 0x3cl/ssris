import { Head } from '@inertiajs/vue3';
import { defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';

export default defineComponent({
    name: 'AdminModule',
    components: { AdminShell, Head },
    props: { module: { type: String, required: true }, title: { type: String, required: true } },
    template: `
        <Head :title="title" />
        <AdminShell :active="module" :title="title">
            <section class="border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-xl font-semibold text-slate-900">{{ title }}</h2>
                <p class="mt-2 max-w-2xl text-slate-600">This module is ready for its management tools and data views.</p>
            </section>
        </AdminShell>
    `,
});
