import { computed, defineComponent, ref, watch } from 'vue';
import { formatDateTime } from '../utils/format-date';

const LOGS_PER_PAGE = 5;

export default defineComponent({
    name: 'RequestDetailsModal',
    props: { open: { type: Boolean, default: false }, request: { type: Object, default: null } },
    emits: ['close'],
    setup(props) {
        const activeTab = ref('details');
        const logsPage = ref(1);

        watch(() => props.request, () => {
            activeTab.value = 'details';
            logsPage.value = 1;
        });

        const totalLogPages = computed(() => Math.max(1, Math.ceil((props.request?.logs?.length ?? 0) / LOGS_PER_PAGE)));
        const pagedLogs = computed(() => {
            const start = (logsPage.value - 1) * LOGS_PER_PAGE;

            return (props.request?.logs ?? []).slice(start, start + LOGS_PER_PAGE);
        });

        return { activeTab, formatDateTime, logsPage, pagedLogs, totalLogPages };
    },
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="open && request" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="request-details-title">
                <section class="flex max-h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                    <header class="flex items-start justify-between gap-4 border-b border-slate-200 p-6 sm:px-8"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-[#07559e]">{{ request.type }}</p><h2 id="request-details-title" class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ request.id }}</h2></div><button type="button" class="flex h-10 w-10 items-center justify-center rounded-full text-3xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close" @click="$emit('close')">×</button></header>
                    <div class="flex gap-2 border-b border-slate-200 px-6 sm:px-8">
                        <button type="button" class="border-b-2 px-3 py-3 text-sm font-bold transition" :class="activeTab === 'details' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'details'">Service Request</button>
                        <button type="button" class="border-b-2 px-3 py-3 text-sm font-bold transition" :class="activeTab === 'actions' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'actions'">Logs</button>
                    </div>
                    <div v-if="activeTab === 'details'" class="overflow-y-auto p-6 sm:p-8">
                        <dl class="grid gap-5 sm:grid-cols-2"><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Client</dt><dd class="mt-1 font-semibold text-slate-900">{{ request.client?.fullname }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-1 text-slate-700">{{ request.client?.email }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Service</dt><dd class="mt-1 text-slate-700">{{ request.service }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Status</dt><dd class="mt-1 capitalize text-slate-700">{{ request.status }}</dd></div><div v-if="request.type === 'appointment'" class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Preferred schedule</dt><dd class="mt-1 text-slate-700">{{ formatDateTime(request.appointment_date, request.appointment_time) }}</dd></div><div v-if="request.type === 'appointment'"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Appointment confirmation</dt><dd class="mt-1 text-slate-700">{{ request.is_appointment_approved ? 'Confirmed' : 'Awaiting confirmation' }}</dd></div><div v-if="request.description" class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Request description</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ request.description }}</dd></div></dl>
                        <div v-if="request.tour_request" class="mt-8 border-t border-slate-200 pt-6">
                            <h3 class="text-sm font-bold uppercase tracking-wide text-[#07559e]">Tour request details</h3>
                            <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Desired date of tour</dt><dd class="mt-1 text-slate-700">{{ request.tour_request.visit_date || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Desired time of tour</dt><dd class="mt-1 text-slate-700">{{ request.tour_request.visit_time || '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Message to us</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ request.tour_request.message || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Number of persons</dt><dd class="mt-1 text-slate-700">{{ request.tour_request.no_persons ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Number of group(s)/batch(es)</dt><dd class="mt-1 text-slate-700">{{ request.tour_request.no_groups ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Testing laboratories</dt><dd class="mt-1 text-slate-700">{{ request.tour_request.testing_lab.length ? request.tour_request.testing_lab.join(', ') : '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">TSD pilot plant</dt><dd class="mt-1 text-slate-700">{{ request.tour_request.pilot_plant.length ? request.tour_request.pilot_plant.join(', ') : '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Others (facilities)</dt><dd class="mt-1 text-slate-700">{{ request.tour_request.others.length ? request.tour_request.others.join(', ') : '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Technology assistance</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ request.tour_request.technology_assistance || '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Objective of this visit</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ request.tour_request.visit_objectives || '—' }}</dd></div>
                            </dl>
                        </div>
                    </div>
                    <div v-else class="flex flex-col overflow-y-auto p-6 sm:p-8">
                        <p v-if="request.logs === null" class="text-sm text-slate-500"><i class="fa-solid fa-spinner fa-spin mr-2" aria-hidden="true"></i>Loading logs…</p>
                        <ol v-else-if="pagedLogs.length" class="space-y-5">
                            <li v-for="log in pagedLogs" :key="log.id" class="border-l-2 border-sky-100 pl-4">
                                <p class="text-sm font-bold text-slate-900">{{ log.action }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">{{ log.description }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ log.actor_name }} · {{ log.created_at }}</p>
                            </li>
                        </ol>
                        <p v-else class="text-sm text-slate-500">No logs recorded for this request yet.</p>
                        <div v-if="totalLogPages > 1" class="mt-6 flex items-center justify-between border-t border-slate-100 pt-5 text-sm">
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" :disabled="logsPage === 1" @click="logsPage--">
                                <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>Previous
                            </button>
                            <span class="font-semibold text-slate-500">Page {{ logsPage }} of {{ totalLogPages }}</span>
                            <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40" :disabled="logsPage === totalLogPages" @click="logsPage++">
                                Next<i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <footer class="border-t border-slate-200 p-5 text-right sm:px-8"><button type="button" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-[#008dcc]" @click="$emit('close')">Close</button></footer>
                </section>
            </div>
        </Transition>
    `,
});
