import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import AttachmentCard from '../../components/AttachmentCard';
import ConfirmActionModal from '../../components/ConfirmActionModal';
import { useQueryTab } from '../../utils/query-tab';

const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const peso = (amount) => currencyFormatter.format(Number(amount) || 0);
const LOGS_PER_PAGE = 5;

export default defineComponent({
    name: 'AdminLabFeedbackForm',
    components: { AttachmentCard, AdminShell, ConfirmActionModal, Head, Link },
    props: {
        serviceRequest: { type: Object, required: true },
        labRequest: { type: Object, required: true },
        logs: { type: Array, default: () => [] },
        feedbackLinks: { type: Array, default: () => [] },
    },
    setup(props) {
        const activeTab = useQueryTab(['service-request', 'verify-payment', 'feedback', 'logs'], 'service-request');
        const sending = ref(false);
        const showReminderConfirm = ref(false);
        const generatingLink = ref(false);
        const logsPage = ref(1);

        const isCompleted = computed(() => props.serviceRequest.status_value === 'completed');

        const rowTotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_fee) || 0);

        const sendReminder = () => {
            if (!showReminderConfirm.value || sending.value) return;
            showReminderConfirm.value = false;
            sending.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/lab-request/feedback/remind`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    sending.value = false;
                },
            });
        };

        const generateLink = () => {
            generatingLink.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/lab-request/feedback/generate-link`, {}, {
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
            peso,
            rowTotal,
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
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <div class="mt-1 flex flex-wrap items-center gap-3">
                            <h2 class="text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }} &middot; {{ labRequest.quotation_no }}</h2>
                            <span v-if="isCompleted" class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>Completed
                            </span>
                        </div>
                        <p class="mt-1 text-slate-600">{{ isCompleted ? 'View the request, payment, and client feedback.' : 'Review the request, payment, and client feedback.' }}</p>
                    </div>
                    <Link href="/admin/requests" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </Link>
                </div>

                <div class="mt-7 flex flex-wrap gap-2 border-b border-slate-200">
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'service-request' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'service-request'">
                        Service request
                    </button>
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'verify-payment' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'verify-payment'">
                        Verify payment
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
                        <div class="flex justify-end">
                            <a :href="'/admin/requests/' + serviceRequest.id + '/lab-request/pdf'" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Download PDF
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
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Request description</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ serviceRequest.description }}</dd></div>
                            </dl>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Receiving officer details</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Quotation number</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ labRequest.quotation_no }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Due date</dt>
                                    <dd class="mt-1 text-slate-700">{{ labRequest.due_date }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Test category</dt>
                                    <dd class="mt-1 text-slate-700">{{ labRequest.test_category }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Type of samples</dt>
                                    <dd class="mt-1 text-slate-700">{{ labRequest.sample_type }}</dd>
                                </div>
                            </dl>
                            <div class="mt-6 overflow-x-auto">
                                <table class="w-full min-w-[600px] text-left text-sm">
                                    <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">Test/s</th>
                                            <th class="px-3 py-2">Test method conditions</th>
                                            <th class="px-3 py-2">Qty</th>
                                            <th class="px-3 py-2">Unit cost</th>
                                            <th class="px-3 py-2">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in labRequest.items" :key="index" class="border-b border-slate-100">
                                            <td class="px-3 py-2">{{ item.test }}</td>
                                            <td class="px-3 py-2">{{ item.method }}</td>
                                            <td class="px-3 py-2">{{ item.quantity }}</td>
                                            <td class="px-3 py-2">{{ peso(item.unit_fee) }}</td>
                                            <td class="px-3 py-2">{{ peso(rowTotal(item)) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <dl class="mt-6 ml-auto max-w-sm space-y-2 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Sub total</dt><dd class="font-semibold text-slate-900">{{ peso(labRequest.sub_total) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="font-semibold text-slate-900">-{{ peso(labRequest.discount) }}</dd></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt class="font-bold text-slate-900">Total fee</dt><dd class="font-bold text-slate-900">{{ peso(labRequest.total_fee) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="mt-8 flex justify-end border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="activeTab = 'verify-payment'">
                            Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>

                <template v-else-if="activeTab === 'verify-payment'">
                    <div class="mt-7 w-full">
                        <div class="flex items-center gap-3">
                            <h3 class="text-base font-bold text-slate-900">Verified payment details</h3>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>Verified
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Payment for {{ labRequest.quotation_no }} has already been verified.</p>

                        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">OP number</dt>
                                <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700">{{ labRequest.op_no }}</dd><dd class="mt-2"><AttachmentCard :href="labRequest.op_attachment_url" name="OP attachment" /></dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">OR number</dt>
                                <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700">{{ labRequest.or_no }}</dd><dd class="mt-2"><AttachmentCard :href="labRequest.or_attachment_url" name="OR attachment" /></dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'service-request'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                        </button>
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
                                Send the client a reminder to fill out the Customer Satisfaction Feedback and claim their request.
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
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'verify-payment'">
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
