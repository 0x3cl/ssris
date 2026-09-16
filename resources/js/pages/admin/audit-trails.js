import { Head, router } from '@inertiajs/vue3';
import { defineComponent, onBeforeUnmount, reactive, ref } from 'vue';
import AdminIndexControls from '../../components/AdminIndexControls';
import AdminPagination from '../../components/AdminPagination';
import AdminShell from '../../components/AdminShell';

const eventTones = {
    created: 'bg-emerald-100 text-emerald-700',
    updated: 'bg-sky-100 text-[#07559e]',
    deleted: 'bg-rose-100 text-rose-700',
    restored: 'bg-amber-100 text-amber-700',
    login: 'bg-violet-100 text-violet-700',
    logout: 'bg-slate-200 text-slate-700',
};

export default defineComponent({
    name: 'AdminAuditTrails',
    components: { AdminIndexControls, AdminPagination, AdminShell, Head },
    props: {
        filters: { type: Object, required: true },
        audits: { type: Object, required: true },
        events: { type: Array, required: true },
    },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const expanded = ref(null);
        let searchTimer;

        const load = () => router.get('/admin/audit-trails', filters, { preserveState: true, replace: true, preserveScroll: true });
        const search = () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(load, 350);
        };
        const page = (url) => {
            if (url) router.get(url, {}, { preserveScroll: true });
        };
        const toggle = (id) => {
            expanded.value = expanded.value === id ? null : id;
        };
        const toneFor = (event) => eventTones[event] ?? 'bg-slate-100 text-slate-700';

        onBeforeUnmount(() => clearTimeout(searchTimer));

        return { expanded, filters, load, page, search, toggle, toneFor };
    },
    template: `
        <Head title="Audit Trails" />
        <AdminShell active="audit-trails" title="Audit Trails">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Audit trails module</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">System activity log</h2>
                    <p class="mt-1 text-slate-600">Review every recorded action made across the system.</p>
                </div>
                <div class="mt-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" search-placeholder="Search by user or record" @search="search">
                        <template #filters>
                            <select v-model="filters.event" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700" @change="load">
                                <option value="">All events</option>
                                <option v-for="event in events" :key="event" :value="event">{{ event }}</option>
                            </select>
                            <div class="relative">
                                <input v-model="filters.date" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700" @change="load" />
                                <button v-if="filters.date" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Clear date filter" @click="filters.date = ''; load()">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                        </template>
                    </AdminIndexControls>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left">
                        <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-4">Date &amp; time</th>
                                <th class="px-4 py-4">User</th>
                                <th class="px-4 py-4">Event</th>
                                <th class="px-4 py-4">Record</th>
                                <th class="px-4 py-4">IP address</th>
                                <th class="px-4 py-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="audit in audits.data" :key="audit.id">
                                <tr class="border-b border-slate-100 hover:bg-sky-50/50">
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ audit.created_at }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ audit.user }}</td>
                                    <td class="px-4 py-4 text-sm"><span class="rounded-full px-3 py-1 text-xs font-bold uppercase" :class="toneFor(audit.event)">{{ audit.event }}</span></td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ audit.model }} #{{ audit.model_id }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ audit.ip_address || '—' }}</td>
                                    <td class="px-4 py-4 text-right text-sm">
                                        <button type="button" class="font-semibold text-[#07559e] hover:text-[#043d78]" @click="toggle(audit.id)">
                                            {{ expanded === audit.id ? 'Hide' : 'View changes' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="expanded === audit.id" class="border-b border-slate-100 bg-slate-50">
                                    <td colspan="6" class="px-4 py-4">
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div class="flex flex-col">
                                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Before</p>
                                                <pre class="min-h-0 max-h-64 flex-1 overflow-auto rounded-lg bg-white p-3 text-xs text-slate-700 shadow-sm">{{ Object.keys(audit.old_values || {}).length ? JSON.stringify(audit.old_values, null, 2) : '—' }}</pre>
                                            </div>
                                            <div class="flex flex-col">
                                                <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">After</p>
                                                <pre class="min-h-0 max-h-64 flex-1 overflow-auto rounded-lg bg-white p-3 text-xs text-slate-700 shadow-sm">{{ Object.keys(audit.new_values || {}).length ? JSON.stringify(audit.new_values, null, 2) : '—' }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="audits.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">No audit trail entries match the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <AdminPagination :pagination="audits" @page="page" />
            </section>
        </AdminShell>
    `,
});
