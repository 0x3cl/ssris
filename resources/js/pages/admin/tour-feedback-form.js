import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import ConfirmActionModal from '../../components/ConfirmActionModal';
import { useQueryTab } from '../../utils/query-tab';

const LOGS_PER_PAGE = 5;

export default defineComponent({
    name: 'AdminTourFeedbackForm',
    components: { AdminShell, ConfirmActionModal, Head, Link },
    props: {
        serviceRequest: { type: Object, required: true },
        tourRequest: { type: Object, required: true },
        logs: { type: Array, default: () => [] },
        feedbackLinks: { type: Array, default: () => [] },
    },
    setup(props) {
        const activeTab = useQueryTab(['service-request', 'feedback', 'logs'], 'service-request');
        const sending = ref(false);
        const showReminderConfirm = ref(false);
        const generatingLink = ref(false);
        const logsPage = ref(1);

        const isCompleted = computed(() => props.serviceRequest.status_value === 'completed');

        const sendReminder = () => {
            if (!showReminderConfirm.value || sending.value) return;
            showReminderConfirm.value = false;
            sending.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/tour-request/feedback/remind`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    sending.value = false;
                },
            });
        };

        const generateLink = () => {
            generatingLink.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/tour-request/feedback/generate-link`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    generatingLink.value = false;
                },
            });
        };

        const totalLogPages = computed(() => Math.max(1, Math.ceil(props.logs.length / LOGS_PER_PAGE)));
        const pagedLogs = computed(() => {
            const start = (logsPage.value - 1) * LOGS_PER_PAGE;

            return props.logs.slice(start, start + LOGS_PER_PAGE);
        });

        return {
            activeTab,
            showReminderConfirm,
            generateLink,
            generatingLink,
            isCompleted,
            logsPage,
            pagedLogs,
            sendReminder,
            sending,
            totalLogPages,
        };
    },
    template: `
        <Head :title="isCompleted ? 'Request details' : 'Review feedback'" />
        <AdminShell active="requests" :title="isCompleted ? 'Request Details' : 'Review Feedback'">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Plant tour services</p>
                        <div class="mt-1 flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }}</h2>
                            <span v-if="isCompleted" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>Completed
                            </span>
                        </div>
                        <p class="mt-1 text-slate-600">{{ isCompleted ? 'View the request and client feedback.' : 'Review the request and client feedback.' }}</p>
                    </div>
                    <Link href="/admin/requests" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </Link>
                </div>

                <div class="mt-7 flex flex-wrap gap-2 border-b border-slate-200">
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'service-request' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'service-request'">
                        Service request
                    </button>
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'feedback' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'feedback'">
                        Feedback
                    </button>
                    <button type="button" class="ml-auto border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'logs' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'logs'">
                        Logs
                    </button>
                </div>

                <template v-if="activeTab === 'service-request'">
                    <div class="mt-7 space-y-8">
                        <div class="flex flex-wrap justify-end gap-3">
                            <a :href="'/admin/requests/' + serviceRequest.id + '/tour-request/pdf'" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Download request PDF
                            </a>
                            <a :href="'/admin/requests/' + serviceRequest.id + '/tour-request/confirmation/pdf'" class="inline-flex items-center gap-2 rounded-lg bg-[#07559e] px-4 py-2 text-sm font-bold text-white hover:bg-[#06457f]">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Download confirmation PDF
                            </a>
                        </div>
                        <section>
                            <h3 class="text-base font-bold text-slate-900">Section 1 &middot; Customer information</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Full name</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.fullname }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Client type</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.type_client }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.email }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Mobile number</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.mobile_no }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Company / School</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.company_or_school || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Address</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.address }}</dd></div>
                            </dl>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Tour request details</h3>
                            <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Desired date of tour</dt><dd class="mt-1 text-slate-700">{{ tourRequest.visit_date || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Desired time of tour</dt><dd class="mt-1 text-slate-700">{{ tourRequest.visit_time || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Number of persons</dt><dd class="mt-1 text-slate-700">{{ tourRequest.no_persons ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Number of group(s)/batch(es)</dt><dd class="mt-1 text-slate-700">{{ tourRequest.no_groups ?? '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Facilities visited</dt><dd class="mt-1 text-slate-700">{{ [...tourRequest.testing_lab, ...tourRequest.pilot_plant, ...tourRequest.others].join(', ') || '—' }}</dd></div>
                            </dl>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 3 &middot; Signatories</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Prepared by</dt><dd class="mt-1 text-slate-700">{{ tourRequest.prepared_by || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Noted by</dt><dd class="mt-1 text-slate-700">{{ tourRequest.noted_by || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Conforme</dt><dd class="mt-1 text-slate-700">{{ tourRequest.conforme_primary || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Conforme (secondary)</dt><dd class="mt-1 text-slate-700">{{ tourRequest.conforme_secondary || '—' }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="mt-8 flex justify-end border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="activeTab = 'feedback'">
                            Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>

                <template v-else-if="activeTab === 'feedback'">
                    <div class="mt-7">
                        <h3 class="text-base font-bold text-slate-900">Client feedback</h3>

                        <div v-if="!isCompleted" class="mt-5 space-y-4">
                            <div class="rounded-xl bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-700">
                                Send the client a reminder to fill out the Customer Satisfaction Feedback for their plant tour.
                            </div>
                            <div class="flex flex-wrap gap-3">
                                <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-400" :disabled="sending" @click="showReminderConfirm = true">
                                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ sending ? 'Sending…' : 'Send reminder' }}
                                </button>
                                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="generatingLink" @click="generateLink">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>{{ generatingLink ? 'Generating…' : 'Generate feedback form' }}
                                </button>
                            </div>
                        </div>
                        <div v-else class="mt-5 rounded-xl bg-emerald-50 px-5 py-4 text-sm font-semibold text-emerald-700">
                            This request has been completed. The feedback links below are shown for reference only.
                        </div>

                        <div v-if="feedbackLinks.length" class="mt-6 border-t border-slate-100 pt-5">
                            <h4 class="text-sm font-bold text-slate-900">Generated links</h4>
                            <ul class="mt-3 space-y-3">
                                <li v-for="link in feedbackLinks" :key="link.id" class="flex w-full flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="break-all font-mono text-xs text-slate-700">{{ link.url }}</p>
                                        <p class="mt-1 text-xs font-semibold uppercase tracking-wide" :class="link.is_submitted ? 'text-emerald-600' : (link.is_expired ? 'text-rose-600' : 'text-slate-400')">
                                            {{ link.is_submitted ? 'Submitted' : (link.is_expired ? 'Expired' : 'Valid until ' + link.expires_at) }}
                                        </p>
                                    </div>
                                    <Link v-if="link.response_url" :href="link.response_url" class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold uppercase tracking-wide text-emerald-700 hover:bg-emerald-100">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>View response
                                    </Link>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-start border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'service-request'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                        </button>
                    </div>
                </template>

                <template v-else>
                    <div class="mt-7">
                        <h3 class="text-base font-bold text-slate-900">Activity logs</h3>
                        <ol v-if="pagedLogs.length" class="mt-5 space-y-5">
                            <li v-for="log in pagedLogs" :key="log.id" class="border-l-2 border-sky-100 pl-4">
                                <p class="text-sm font-bold text-slate-900">{{ log.action }}</p>
                                <p class="mt-0.5 text-sm text-slate-600">{{ log.description }}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ log.actor_name }} · {{ log.created_at }}</p>
                            </li>
                        </ol>
                        <p v-else class="mt-5 text-sm text-slate-500">No logs recorded for this request yet.</p>
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
                </template>
            </section>

            <ConfirmActionModal
                :open="showReminderConfirm"
                title="Send feedback reminder?"
                :message="'Continuing will send a feedback reminder email to ' + serviceRequest.client.email + '. Would you like to proceed?'"
                icon="fa-solid fa-envelope"
                confirm-label="Send reminder"
                :processing="sending"
                @close="showReminderConfirm = false"
                @confirm="sendReminder"
            />
        </AdminShell>
    `,
});
