import { Head } from '@inertiajs/vue3';
import { defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';

export default defineComponent({
    components: { Head, AdminShell },
    props: { client: Object, request: Object },
    template: `
        <Head title="Service request" />
        <AdminShell active="clients" title="Service request">
            <section class="w-full border border-slate-200 bg-white p-5 sm:p-7">
                <a :href="'/admin/clients/service-requests?client_id=' + client.id" class="text-sm font-semibold text-[#07559e]">← Back to client requests</a>
                <h2 class="mt-5 text-2xl font-bold">Request #{{ request.id }} · {{ request.service }}</h2>
                <dl class="mt-7 grid gap-6 sm:grid-cols-2">
                    <div><dt class="text-sm text-slate-500">Client</dt><dd class="mt-1 font-semibold">{{ client.fullname }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Email</dt><dd class="mt-1">{{ client.email }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Status</dt><dd class="mt-1">{{ request.status }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Request type</dt><dd class="mt-1">{{ request.type }}</dd></div>
                    <div><dt class="text-sm text-slate-500">Date submitted</dt><dd class="mt-1">{{ request.date }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-sm text-slate-500">Description</dt><dd class="mt-1 whitespace-pre-wrap">{{ request.description }}</dd></div>
                </dl>
            </section>
        </AdminShell>
    `,
});
