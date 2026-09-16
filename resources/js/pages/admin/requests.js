import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, onBeforeUnmount, reactive, ref } from 'vue';
import AdminIndexControls from '../../components/AdminIndexControls';
import AdminPagination from '../../components/AdminPagination';
import AdminShell from '../../components/AdminShell';
import AppointmentConfirmModal from '../../components/AppointmentConfirmModal';
import CodeConfirmationModal from '../../components/CodeConfirmationModal';
import ConfirmActionModal from '../../components/ConfirmActionModal';
import RequestDetailsModal from '../../components/RequestDetailsModal';
import { formatDateTime } from '../../utils/format-date';

const needsAppointmentConfirmation = (request) => request.type === 'appointment' && !request.is_appointment_approved;

export default defineComponent({
    name: 'AdminRequests',
    components: { AdminIndexControls, AdminPagination, AdminShell, AppointmentConfirmModal, CodeConfirmationModal, ConfirmActionModal, Head, Link, RequestDetailsModal },
    props: { filters: { type: Object, required: true }, requests: { type: Object, required: true }, statuses: { type: Array, required: true }, services: { type: Array, required: true } },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const selectedRequest = ref(null);
        const proceeding = reactive({ processing: false, request: null });
        // step: null (closed) | 'details' (choose approve/reschedule/cancel) | 'captcha' (confirmation code)
        const confirmingAppointment = reactive({ processing: false, request: null, step: null, pendingAction: null });
        let searchTimer;

        const load = () => router.get('/admin/requests', filters, { preserveState: true, replace: true, preserveScroll: true });
        const search = () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(load, 350);
        };
        const logsCache = new Map();
        const open = (request) => {
            selectedRequest.value = { ...request, logs: logsCache.get(request.id) ?? null };

            if (logsCache.has(request.id)) return;

            fetch(`/admin/requests/${request.id}/logs`, { headers: { Accept: 'application/json' } })
                .then((response) => response.json())
                .then((data) => {
                    logsCache.set(request.id, data.logs);
                    if (selectedRequest.value?.id === request.id) {
                        selectedRequest.value = { ...selectedRequest.value, logs: data.logs };
                    }
                })
                .catch(() => {
                    if (selectedRequest.value?.id === request.id) {
                        selectedRequest.value = { ...selectedRequest.value, logs: [] };
                    }
                });
        };
        const page = (url) => {
            if (url) router.get(url, {}, { preserveScroll: true });
        };
        const proceed = (request) => {
            if (needsAppointmentConfirmation(request)) {
                confirmingAppointment.request = request;
                confirmingAppointment.step = 'details';

                return;
            }

            if (request.service_value === 'rnd-services') {
                router.get(`/admin/requests/${request.id}/rdd-request`);

                return;
            }

            if (request.service_value === 'processing-services') {
                router.get(`/admin/requests/${request.id}/processing-request`);

                return;
            }

            if (request.service_value === 'lab-services') {
                router.get(`/admin/requests/${request.id}/lab-request`);

                return;
            }

            if (request.service_value === 'training-services') {
                router.get(`/admin/requests/${request.id}/training-request`);

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
        const closeAppointmentFlow = () => {
            confirmingAppointment.request = null;
            confirmingAppointment.step = null;
            confirmingAppointment.pendingAction = null;
        };
        const continueToCaptcha = (payload) => {
            confirmingAppointment.pendingAction = payload;
            confirmingAppointment.step = 'captcha';
        };
        const captchaTitle = computed(() => confirmingAppointment.pendingAction?.type === 'cancel'
            ? 'Cancel this appointment request?'
            : (confirmingAppointment.pendingAction?.reschedule ? 'Confirm the rescheduled appointment?' : 'Confirm this appointment?'));
        const captchaMessage = computed(() => {
            const request = confirmingAppointment.request;
            const action = confirmingAppointment.pendingAction;
            if (!request || !action) return '';

            if (action.type === 'cancel') {
                return `Service request #${request.id} will be marked as cancelled. Enter the four-digit code below to confirm.`;
            }

            return action.reschedule
                ? `Service request #${request.id} will be rescheduled to ${formatDateTime(action.appointment_date, action.appointment_time)} and confirmed. Enter the four-digit code below to confirm.`
                : `Service request #${request.id} will be confirmed as scheduled. Enter the four-digit code below to confirm.`;
        });
        const captchaConfirmLabel = computed(() => (confirmingAppointment.pendingAction?.type === 'cancel' ? 'Cancel Appointment' : 'Confirm Appointment'));
        const captchaTone = computed(() => (confirmingAppointment.pendingAction?.type === 'cancel' ? 'danger' : 'primary'));
        const confirmAppointment = (code) => {
            const request = confirmingAppointment.request;
            const action = confirmingAppointment.pendingAction;
            if (!request || !action) return;

            const url = action.type === 'cancel'
                ? `/admin/requests/${request.id}/cancel-appointment`
                : `/admin/requests/${request.id}/approve-appointment`;
            const data = action.type === 'cancel'
                ? { reason: action.reason, confirmation_code: code }
                : { reschedule: action.reschedule, appointment_date: action.appointment_date, appointment_time: action.appointment_time, reason: action.reason, confirmation_code: code };

            confirmingAppointment.processing = true;
            router.patch(url, data, {
                preserveScroll: true,
                onSuccess: () => {
                    closeAppointmentFlow();
                },
                onError: () => {
                    confirmingAppointment.step = 'details';
                },
                onFinish: () => {
                    confirmingAppointment.processing = false;
                },
            });
        };

        onBeforeUnmount(() => clearTimeout(searchTimer));

        return {
            captchaConfirmLabel,
            captchaMessage,
            captchaTitle,
            captchaTone,
            closeAppointmentFlow,
            confirmAppointment,
            confirmingAppointment,
            confirmProceed,
            continueToCaptcha,
            filters,
            needsAppointmentConfirmation,
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
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">All service requests</h2>
                    <p class="mt-1 text-slate-600">Search and filter only the entries you need.</p>
                </div>
                <div class="mt-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" search-placeholder="Search client, email, service, or description" @search="search" />
                </div>
                <div class="mt-5 flex flex-wrap gap-2 border-b border-slate-200">
                    <button v-for="tab in [{ value: '', label: 'All requests' }, { value: 'walk-in', label: 'Walk-in' }, { value: 'appointment', label: 'Appointment' }]" :key="tab.value" type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="filters.type === tab.value ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="filters.type = tab.value; load()">
                        {{ tab.label }}
                    </button>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <select v-model="filters.service" class="rounded-xl border border-slate-300 px-4 py-3 text-slate-700" @change="load">
                        <option value="">All services</option>
                        <option v-for="service in services" :key="service.value" :value="service.value">{{ service.label }}</option>
                    </select>
                    <select v-model="filters.status" class="rounded-xl border border-slate-300 px-4 py-3 text-slate-700" @change="load">
                        <option value="">All statuses</option>
                        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                    <div class="relative">
                        <input v-model="filters.date" type="date" class="w-full rounded-xl border border-slate-300 py-3 pl-4 pr-4 text-slate-700" @change="load" />
                        <button v-if="filters.date" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Clear date filter" @click="filters.date = ''; load()">
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        </button>
                    </div>
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
                                    <span v-if="needsAppointmentConfirmation(request)" class="ml-2 inline-flex items-center gap-1 text-xs font-bold text-amber-600" title="Awaiting appointment confirmation">
                                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>Unconfirmed
                                    </span>
                                    <span v-else-if="request.type === 'appointment'" class="ml-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-600" title="Appointment confirmed">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>Confirmed
                                    </span>
                                </td>
                                <td class="px-4 py-4"><span class="text-sm font-semibold text-slate-700">{{ request.status }}</span></td>
                                <td class="px-4 py-4 text-sm text-slate-500">{{ request.created_at }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100" @click="open(request)">
                                            <i class="fa-solid fa-eye" aria-hidden="true"></i>View
                                        </button>
                                        <button v-if="request.status_value === 'pending'" type="button" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-emerald-700 hover:bg-emerald-50" @click="proceed(request)">
                                            <i :class="needsAppointmentConfirmation(request) ? 'fa-solid fa-bolt' : 'fa-solid fa-arrow-right'" aria-hidden="true"></i>{{ needsAppointmentConfirmation(request) ? 'Take Action' : 'Proceed' }}
                                        </button>
                                        <Link v-if="request.status_value === 'for-payment' && request.service_value === 'rnd-services'" :href="'/admin/requests/' + request.id + '/rdd-request/payment?tab=payment-verification'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-50">
                                            <i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i>Verify Payment
                                        </Link>
                                        <Link v-if="request.status_value === 'awaiting-feedback' && request.service_value === 'rnd-services'" :href="'/admin/requests/' + request.id + '/rdd-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-violet-700 hover:bg-violet-50">
                                            <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>Review Feedback
                                        </Link>
                                        <Link v-if="request.status_value === 'completed' && request.service_value === 'rnd-services'" :href="'/admin/requests/' + request.id + '/rdd-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>More Info
                                        </Link>
                                        <Link v-if="request.status_value === 'for-payment' && request.service_value === 'processing-services'" :href="'/admin/requests/' + request.id + '/processing-request/payment?tab=payment-verification'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-50">
                                            <i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i>Verify Payment
                                        </Link>
                                        <Link v-if="request.status_value === 'awaiting-feedback' && request.service_value === 'processing-services'" :href="'/admin/requests/' + request.id + '/processing-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-violet-700 hover:bg-violet-50">
                                            <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>Review Feedback
                                        </Link>
                                        <Link v-if="request.status_value === 'completed' && request.service_value === 'processing-services'" :href="'/admin/requests/' + request.id + '/processing-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>More Info
                                        </Link>
                                        <Link v-if="request.status_value === 'for-payment' && request.service_value === 'lab-services'" :href="'/admin/requests/' + request.id + '/lab-request/payment?tab=payment-verification'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-50">
                                            <i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i>Verify Payment
                                        </Link>
                                        <Link v-if="request.status_value === 'awaiting-feedback' && request.service_value === 'lab-services'" :href="'/admin/requests/' + request.id + '/lab-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-violet-700 hover:bg-violet-50">
                                            <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>Review Feedback
                                        </Link>
                                        <Link v-if="request.status_value === 'completed' && request.service_value === 'lab-services'" :href="'/admin/requests/' + request.id + '/lab-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>More Info
                                        </Link>
                                        <Link v-if="request.status_value === 'for-service-fee' && request.service_value === 'training-services'" :href="'/admin/requests/' + request.id + '/training-request/fee'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-sky-700 hover:bg-sky-50">
                                            <i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i>Service Fee
                                        </Link>
                                        <Link v-if="request.status_value === 'for-payment' && request.service_value === 'training-services'" :href="'/admin/requests/' + request.id + '/training-request/payment?tab=payment-verification'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-amber-700 hover:bg-amber-50">
                                            <i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i>Verify Payment
                                        </Link>
                                        <Link v-if="request.status_value === 'awaiting-feedback' && request.service_value === 'training-services'" :href="'/admin/requests/' + request.id + '/training-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-violet-700 hover:bg-violet-50">
                                            <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>Review Feedback
                                        </Link>
                                        <Link v-if="request.status_value === 'completed' && request.service_value === 'training-services'" :href="'/admin/requests/' + request.id + '/training-request/feedback?tab=feedback'" class="inline-flex items-center whitespace-nowrap gap-2 rounded-lg px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-100">
                                            <i class="fa-solid fa-circle-info" aria-hidden="true"></i>More Info
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="requests.data.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center text-slate-500">No service requests match the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <AdminPagination :pagination="requests" @page="page" />
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
            <AppointmentConfirmModal
                :open="confirmingAppointment.step === 'details'"
                :request="confirmingAppointment.request"
                @close="closeAppointmentFlow"
                @continue="continueToCaptcha"
            />
            <CodeConfirmationModal
                :open="confirmingAppointment.step === 'captcha'"
                :processing="confirmingAppointment.processing"
                :title="captchaTitle"
                :message="captchaMessage"
                :confirm-label="captchaConfirmLabel"
                :tone="captchaTone"
                icon="fa-solid fa-shield-halved"
                @close="confirmingAppointment.step = 'details'"
                @confirm="confirmAppointment"
            />
        </AdminShell>
    `,
});
