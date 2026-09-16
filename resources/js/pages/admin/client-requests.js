import { Head, Link, router } from '@inertiajs/vue3';
import { defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';
import AdminPagination from '../../components/AdminPagination';
import RequestFolderCard from '../../components/RequestFolderCard';

export default defineComponent({
    components: { Head, AdminShell, AdminPagination, Link, RequestFolderCard },
    props: { client: Object, requests: Object },
    setup() {
        return { page: (url) => { if (url) router.get(url, {}, { preserveScroll: true }); } };
    },
    template: `
        <Head title="Client service requests" />
        <AdminShell active="clients" title="Client service requests">
            <section class="w-full border border-slate-200 bg-white p-5 sm:p-7">
                <Link href="/admin/clients" class="text-sm font-semibold text-[#07559e]">← Back to clients</Link>
                <h2 class="mt-5 text-2xl font-bold text-slate-900">{{ client.fullname }}</h2>
                <p class="mt-1 text-sm text-slate-500">{{ client.email }} · {{ requests.total }} requests</p>
                <div class="mt-7 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    <RequestFolderCard v-for="request in requests.data" :key="request.id" :request="request" />
                </div>
                <p v-if="!requests.data.length" class="py-12 text-center text-slate-500">This client has no service requests.</p>
                <AdminPagination :pagination="requests" @page="page" />
            </section>
        </AdminShell>
    `,
});
