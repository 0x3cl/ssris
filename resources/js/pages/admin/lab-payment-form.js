import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, reactive, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import ConfirmActionModal from '../../components/ConfirmActionModal';
import CodeConfirmationModal from '../../components/CodeConfirmationModal';
import { useQueryTab } from '../../utils/query-tab';

const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const peso = (amount) => currencyFormatter.format(Number(amount) || 0);

export default defineComponent({
    name: 'AdminLabPaymentForm',
    components: { AdminShell, ConfirmActionModal, CodeConfirmationModal, Head, Link },
    props: { serviceRequest: { type: Object, required: true }, labRequest: { type: Object, required: true } },
    setup(props) {
        const activeTab = useQueryTab(['service-request', 'payment-verification'], 'service-request');
        const form = reactive({
            op_no: props.labRequest.op_no || '',
            or_no: props.labRequest.or_no || '',
            op_attachment: null,
            or_attachment: null,
        });
        const errors = ref({});
        const processing = ref(false);
        const showConfirm = ref(false);
        const sendingReminder = ref(false);
        const showReminderConfirm = ref(false);

        const rowTotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_fee) || 0);

        const validate = () => {
            const newErrors = {};

            if (!form.op_no.trim()) newErrors.op_no = 'Enter the OP number.';
            if (!form.or_no.trim()) newErrors.or_no = 'Enter the OR number.';

            errors.value = newErrors;

            return Object.keys(newErrors).length === 0;
        };

        const openConfirm = () => {
            if (validate()) {
                showConfirm.value = true;
            } else {
                activeTab.value = 'payment-verification';
            }
        };

        const submit = (code) => {
            processing.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/lab-request/payment`, {
                op_no: form.op_no,
                or_no: form.or_no,
                op_attachment: form.op_attachment,
                or_attachment: form.or_attachment,
                confirmation_code: code,
            }, {
                onError: (submitErrors) => {
                    errors.value = submitErrors;
                    showConfirm.value = false;
                    activeTab.value = 'payment-verification';
                },
                onFinish: () => {
                    processing.value = false;
                },
            });
        };

        const sendReminder = () => {
            if (!showReminderConfirm.value || sendingReminder.value) return;
            showReminderConfirm.value = false;
            sendingReminder.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/lab-request/payment/remind`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    sendingReminder.value = false;
                },
            });
        };

        return {
            activeTab,
            showReminderConfirm,
            errors,
            form,
            openConfirm,
            peso,
            processing,
            rowTotal,
            sendingReminder,
            sendReminder,
            showConfirm,
            submit,
        };
    },
    template: `
        <Head title="Verify payment" />
        <AdminShell active="requests" title="Verify Payment">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }} &middot; {{ labRequest.quotation_no }}</h2>
                        <p class="mt-1 text-slate-600">Review the request, then verify the payment details.</p>
                    </div>
                    <Link href="/admin/requests" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </Link>
                </div>

                <div class="mt-7 flex flex-wrap gap-2 border-b border-slate-200">
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'service-request' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'service-request'">
                        Service request
                    </button>
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'payment-verification' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'payment-verification'">
                        Payment verification
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
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="activeTab = 'payment-verification'">
                            Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>

                <template v-else>
                    <div class="mt-7">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900">Payment verification</h3>
                                <p class="mt-1 text-sm text-slate-500">Record the official payment references for {{ labRequest.quotation_no }}.</p>
                            </div>
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="sendingReminder" @click="showReminderConfirm = true">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ sendingReminder ? 'Sending…' : 'Send reminder' }}
                            </button>
                        </div>

                        <div class="mt-5 grid grid-cols-1 items-start gap-5 md:grid-cols-2">
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">OP number</span>
                                <input v-model="form.op_no" class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.op_no" class="mt-1 text-sm text-rose-600">{{ errors.op_no }}</p>
                            </label>
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">OP attachment (optional)</span>
                                <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block h-12 w-full min-w-0 rounded-xl border border-slate-300 p-3 text-sm" @change="form.op_attachment = $event.target.files[0] || null" />
                                <span class="mt-1 block text-xs text-slate-500">PDF, JPG, or PNG. Maximum 5 MB.</span>
                                <p v-if="errors.op_attachment" class="mt-1 text-sm text-rose-600">{{ errors.op_attachment }}</p>
                            </label>
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">OR number</span>
                                <input v-model="form.or_no" class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.or_no" class="mt-1 text-sm text-rose-600">{{ errors.or_no }}</p>
                            </label>
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">OR attachment (optional)</span>
                                <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block h-12 w-full min-w-0 rounded-xl border border-slate-300 p-3 text-sm" @change="form.or_attachment = $event.target.files[0] || null" />
                                <span class="mt-1 block text-xs text-slate-500">PDF, JPG, or PNG. Maximum 5 MB.</span>
                                <p v-if="errors.or_attachment" class="mt-1 text-sm text-rose-600">{{ errors.or_attachment }}</p>
                            </label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'service-request'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="openConfirm">
                            Verify payment<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>
            </section>

            <CodeConfirmationModal
                :open="showConfirm"
                :processing="processing"
                title="Verify this payment?"
                :message="'Confirm OP ' + form.op_no + ' and OR ' + form.or_no + ' for ' + labRequest.quotation_no + '. Enter the four-digit code below to confirm.'"
                confirm-label="Verify payment"
                icon="fa-solid fa-money-check-dollar"
                @close="showConfirm = false"
                @confirm="submit"
            />
            <ConfirmActionModal
                :open="showReminderConfirm"
                title="Send payment reminder?"
                :message="'Continuing will send a payment reminder email to ' + serviceRequest.client.email + '. Would you like to proceed?'"
                icon="fa-solid fa-envelope"
                confirm-label="Send reminder"
                :processing="sendingReminder"
                @close="showReminderConfirm = false"
                @confirm="sendReminder"
            />
        </AdminShell>
    `,
});
