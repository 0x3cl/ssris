import { Head, router } from '@inertiajs/vue3';
import { defineComponent, onBeforeUnmount, reactive, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import ConfirmActionModal from '../../components/ConfirmActionModal';
import RequestDetailsModal from '../../components/RequestDetailsModal';

export default defineComponent({
    name: 'AdminRequests',
    components: { AdminShell, ConfirmActionModal, Head, RequestDetailsModal },
    props: { filters: { type: Object, required: true }, requests: { type: Object, required: true }, statuses: { type: Array, required: true } },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const selectedRequest = ref(null);
        const proceeding = reactive({ processing: false, request: null });
        let searchTimer;

        const load = () => router.get('/admin/requests', filters, { preserveState: true, replace: true, preserveScroll: true });
        const search = () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(load, 350);
        };
        const open = (request) => {
            selectedRequest.value = request;
        };
        const page = (url) => {
            if (url) router.get(url, {}, { preserveScroll: true });
        };
        const proceed = (request) => {
            if (request.service_value === 'rnd-services') {
                router.get(`/admin/requests/${request.id}/rdd-request`);

                return;
            }

            proceeding.request = request;
        };
        const confirmProceed = () => {
            if (!proceeding.request) return;

            proceeding.processing = true;
            router.patch(`/admin/requests/${proceeding.request.id}/proceed`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    proceeding.request = null;
                },
                onFinish: () => {
                    proceeding.processing = false;
                },
            });
        };

        onBeforeUnmount(() => clearTimeout(searchTimer));

        return {
            confirmProceed,
            filters,
            load,
            open,
            page,
            proceed,
            proceeding,
            search,
            selectedRequest,
        };
    },
    template: `
        <Head title="Service requests" />
        <AdminShell active="requests" title="Service Requests">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-end justify-between gap-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">All service requests</h2>
                        <p class="mt-1 text-slate-600">Search and filter only the entries you need.</p>
                    </div>
                    <label class="text-sm font-semibold text-slate-700">
                        Entries
                        <select v-model="filters.entries" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="load">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                    </label>
                </div>
                <div class="mt-7 flex flex-wrap gap-2 border-b border-slate-200">
                    <button v-for="tab in [{ value: '', label: 'All requests' }, { value: 'walk-in', label: 'Walk-in' }, { value: 'appointment', label: 'Appointment' }]" :key="tab.value" type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="filters.type === tab.value ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="filters.type = tab.value; load()">
                        {{ tab.label }}
                    </button>
                </div>
                <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_220px]">
                    <label>
                        <span class="sr-only">Search requests</span>
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                            <input v-model="filters.search" class="w-full rounded-xl border border-slate-300 py-3 pl-11 pr-4 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" placeholder="Search client, email, service, or description" @input="search" />
                        </div>
                    </label>
                    <select v-model="filters.status" class="rounded-xl border border-slate-300 px-4 py-3 text-slate-700" @change="load">
                        <option value="">All statuses</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left">
                        <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-4">ID</th>
                                <th class="px-4 py-4">Client</th>
                                <th class="px-4 py-4">Service</th>
                                <th class="px-4 py-4">Type</th>
                                <th class="px-4 py-4">Status</th>
                                <th class="px-4 py-4">Submitted</th>
                                <th class="px-4 py-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="request in requests.data" :key="request.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                <td class="px-4 py-4 text-sm font-semibold text-slate-500">#{{ request.id }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ request.client?.fullname }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ request.client?.email }}</p>
                                </td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ request.service }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-3 py-1 text-xs font-bold uppercase" :class="request.type === 'appointment' ? 'bg-violet-100 text-violet-700' : 'bg-sky-100 text-[#07559e]'">{{ request.type }}</span>
                                </td>
                                <td class="px-4 py-4"><span class="text-sm font-semibold text-slate-700">{{ request.status }}</span></td>
                                <td class="px-4 py-4 text-sm text-slate-500">{{ request.created_at }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100" @click="open(request)">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>View
                                        </button>
                                        <button v-if="request.status_value === 'pending'" type="button" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50" @click="proceed(request)">
                                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>Proceed
                                        </button>
                                        <a v-if="request.status_value === 'for-payment' && request.service_value === 'rnd-services'" :href="'/admin/requests/' + request.id + '/rdd-request/payment'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-50">
                                            <i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i>Verify Payment
                                        </a>
                                        <a v-if="request.status_value === 'awaiting-feedback' && request.service_value === 'rnd-services'" :href="'/admin/requests/' + request.id + '/rdd-request/feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-violet-700 hover:bg-violet-50">
                                            <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>Review Feedback
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="requests.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">No service requests match the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <footer class="mt-6 flex flex-wrap items-center justify-between gap-4 text-sm text-slate-600">
                    <p>Showing {{ requests.from ?? 0 }}–{{ requests.to ?? 0 }} of {{ requests.total }} entries</p>
                    <nav class="flex gap-2" aria-label="Pagination">
                        <button v-for="link in requests.links" :key="link.label" type="button" :disabled="!link.url || link.active" class="rounded-lg border px-3 py-2" :class="link.active ? 'border-[#00aeef] bg-sky-50 font-bold text-[#07559e]' : 'border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40'" @click="page(link.url)">
                            <span v-html="link.label"></span>
                        </button>
                    </nav>
                </footer>
            </section>
            <RequestDetailsModal :open="Boolean(selectedRequest)" :request="selectedRequest" @close="selectedRequest = null" />
            <ConfirmActionModal
                :open="Boolean(proceeding.request)"
                :processing="proceeding.processing"
                title="Move this request to for payment?"
                :message="proceeding.request ? ('Service request #' + proceeding.request.id + ' will move from pending to for payment.') : ''"
                confirm-label="Proceed"
                icon="fa-solid fa-arrow-right"
                @close="proceeding.request = null"
                @confirm="confirmProceed"
            />
        </AdminShell>
    `,
});
