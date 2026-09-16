import { Head, router } from '@inertiajs/vue3';
import { defineComponent, onBeforeUnmount, reactive } from 'vue';
import CodeConfirmationModal from '../../components/CodeConfirmationModal';
import AdminShell from '../../components/AdminShell';
import AdminIndexControls from '../../components/AdminIndexControls';
import AdminPagination from '../../components/AdminPagination';

export default defineComponent({
    name: 'AdminClients',
    components: { CodeConfirmationModal, Head, AdminShell, AdminIndexControls, AdminPagination },
    props: {
        clients: { type: Object, required: true },
        filters: { type: Object, required: true },
    },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const action = reactive({ client: null, processing: false });
        const confirm = (code) => {
            if (!action.client || action.processing) return;
            action.processing = true;
            router.patch('/admin/clients/' + action.client.id + (action.client.archived ? '/restore' : '/archive'), { confirmation_code: code }, {
                preserveScroll: true,
                onSuccess: () => { action.client = null; },
                onFinish: () => { action.processing = false; },
            });
        };
        let timer;
        const search = () => {
            clearTimeout(timer);
            timer = setTimeout(() => router.get('/admin/clients', filters, {
                preserveState: true, preserveScroll: true, replace: true,
            }), 300);
        };
        const page = (url) => {
            clearTimeout(timer);
            if (url) router.get(url, {}, { preserveScroll: true });
        };
        onBeforeUnmount(() => clearTimeout(timer));
        return { filters, search, page, action, confirm };
    },
    template: `
        <Head title="Clients" />
        <AdminShell active="clients" title="Clients">
            <section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Clients module</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-900">Clients</h2>
                <p class="mt-1 text-slate-600">Browse client records, archive inactive clients, or restore archived clients.</p>
                <div class="mt-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search"
                        search-placeholder="Search name, email, phone, or organization" @search="search">
                        <template #filters>
                            <label class="text-sm font-semibold text-slate-700">Status
                                <select v-model="filters.status" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="search">
                                    <option value="active">Active</option><option value="archived">Archived</option>
                                </select>
                            </label>
                        </template>
                    </AdminIndexControls>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[800px] text-left">
                        <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr><th class="px-4 py-4">Client ID</th><th class="px-4 py-4">Client</th><th class="px-4 py-4">Email</th><th class="px-4 py-4">Mobile number</th><th class="px-4 py-4">Client type</th><th class="px-4 py-4">Company / School</th><th class="px-4 py-4 text-right">Actions</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="client in clients.data" :key="client.id" class="border-b border-slate-100 text-sm text-slate-700 hover:bg-sky-50/50">
                                <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-500">#{{ client.id }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ client.fullname }}</td>
                                <td class="px-4 py-4">{{ client.email }}</td>
                                <td class="px-4 py-4">{{ client.mobile_no || '—' }}</td>
                                <td class="px-4 py-4">{{ client.type || '—' }}</td>
                                <td class="px-4 py-4">{{ client.organization || '—' }}</td>
                                <td class="px-4 py-4 text-right">
                                    <a :href="client.requests_url" :aria-label="'View requests for ' + client.fullname" class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100">
                                        <i class="fa-solid fa-folder-open" aria-hidden="true"></i>View requests
                                    </a>
                                    <button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 font-bold hover:bg-slate-100" :class="client.archived ? 'text-emerald-700' : 'text-amber-700'" @click="action.client = client">
                                        <i :class="client.archived ? 'fa-solid fa-rotate-left' : 'fa-solid fa-box-archive'" aria-hidden="true"></i>{{ client.archived ? 'Restore' : 'Archive' }}
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!clients.data.length"><td colspan="7" class="px-4 py-12 text-center text-slate-500">No clients match your search.</td></tr>
                        </tbody>
                    </table>
                </div>
                <AdminPagination :pagination="clients" @page="page" />
            </section>
            <CodeConfirmationModal :open="Boolean(action.client)" :processing="action.processing"
                :title="action.client?.archived ? 'Restore client?' : 'Archive client?'"
                :message="action.client ? (action.client.archived ? 'Restore ' + action.client.fullname + ' to the active clients list?' : 'Archive ' + action.client.fullname + '? Their requests and feedback will remain available.') : ''"
                :confirm-label="action.client?.archived ? 'Restore client' : 'Archive client'"
                :tone="action.client?.archived ? 'primary' : 'danger'"
                :icon="action.client?.archived ? 'fa-solid fa-rotate-left' : 'fa-solid fa-box-archive'"
                @close="action.client = null" @confirm="confirm" />
        </AdminShell>
    `,
});
