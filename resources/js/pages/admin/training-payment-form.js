import { Head, Link, router } from '@inertiajs/vue3';
import { defineComponent, reactive, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import CodeConfirmationModal from '../../components/CodeConfirmationModal';
import ConfirmActionModal from '../../components/ConfirmActionModal';
import { useQueryTab } from '../../utils/query-tab';

const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const peso = (amount) => currencyFormatter.format(Number(amount) || 0);

export default defineComponent({
    name: 'AdminTrainingPaymentForm',
    components: { AdminShell, CodeConfirmationModal, ConfirmActionModal, Head, Link },
    props: { serviceRequest: { type: Object, required: true }, trainingRequest: { type: Object, required: true } },
    setup(props) {
        const activeTab = useQueryTab(['service-request', 'service-fee', 'payment-verification'], 'service-request');
        const form = reactive({
            bill_no: props.trainingRequest.fee.bill_no || '',
            or_no: props.trainingRequest.fee.or_no || '',
            bill_attachment: null,
            or_attachment: null,
        });
        const errors = ref({});
        const processing = ref(false);
        const showConfirm = ref(false);
        const sendingReminder = ref(false);
        const showReminderConfirm = ref(false);

        const validate = () => {
            const newErrors = {};

            if (!form.bill_no.trim()) newErrors.bill_no = 'Enter the bill number.';
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
            router.post(`/admin/requests/${props.serviceRequest.id}/training-request/payment`, {
                bill_no: form.bill_no,
                or_no: form.or_no,
                bill_attachment: form.bill_attachment,
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
            router.post(`/admin/requests/${props.serviceRequest.id}/training-request/payment/remind`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    sendingReminder.value = false;
                },
            });
        };

        return {
            activeTab,
            errors,
            form,
            openConfirm,
            peso,
            processing,
            sendingReminder,
            sendReminder,
            showConfirm,
            showReminderConfirm,
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
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }} &middot; {{ trainingRequest.fee.reference_no }}</h2>
                        <p class="mt-1 text-slate-600">Review the request and service fee, then verify the payment details.</p>
                    </div>
                    <Link href="/admin/requests" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </Link>
                </div>

                <div class="mt-7 flex flex-wrap gap-2 border-b border-slate-200">
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'service-request' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'service-request'">
                        Service request
                    </button>
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'service-fee' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'service-fee'">
                        Service fee
                    </button>
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'payment-verification' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'payment-verification'">
                        Payment verification
                    </button>
                </div>

                <template v-if="activeTab === 'service-request'">
                    <div class="mt-7 space-y-8">
                        <div class="flex justify-end">
                            <a :href="'/admin/requests/' + serviceRequest.id + '/training-request/pdf'" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
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
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Address</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.address }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Request description</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ serviceRequest.description }}</dd></div>
                            </dl>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Training details</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Training course requested</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.training_course_requested }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Estimated number of participants</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.estimated_participants }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Proposed training date</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.proposed_training_date }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Proposed training venue</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.proposed_training_venue }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Name of beneficiary / community</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.beneficiary_name }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Purpose of training</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ trainingRequest.purpose_of_training }}</dd></div>
                            </dl>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 3 &middot; TSD Training staff details</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Assigned trainer</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.assigned_trainer }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Assigned assistant trainer</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.assigned_assistant_trainer || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Official course title</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.official_course_title }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Approved training duration</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.approved_training_duration }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Type of training</dt><dd class="mt-1 capitalize text-slate-700">{{ trainingRequest.training_type }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">If special, specify</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.special_type_details || '—' }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="mt-8 flex justify-end border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="activeTab = 'service-fee'">
                            Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>

                <template v-else-if="activeTab === 'service-fee'">
                    <div class="mt-7">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <h3 class="text-base font-bold text-slate-900">Service fee</h3>
                            <a :href="'/admin/requests/' + serviceRequest.id + '/training-request/fee/pdf'" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Download PDF
                            </a>
                        </div>
                        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Reference number</dt><dd class="mt-1 font-semibold text-slate-900">{{ trainingRequest.fee.reference_no }}</dd></div>
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Date / Time</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.fee.date_time }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Particulars</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.fee.particulars }}</dd></div>
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Duration</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.fee.duration }}</dd></div>
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">No. of participants</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.fee.no_participants }}</dd></div>
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Net amount due</dt><dd class="mt-1 font-bold text-slate-900">{{ peso(trainingRequest.fee.net_amount_due) }}</dd></div>
                        </dl>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'service-request'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                        </button>
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
                                <p class="mt-1 text-sm text-slate-500">Record the official payment references for {{ trainingRequest.fee.reference_no }}.</p>
                            </div>
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-60" :disabled="sendingReminder" @click="showReminderConfirm = true">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ sendingReminder ? 'Sending…' : 'Send reminder' }}
                            </button>
                        </div>

                        <div class="mt-5 grid grid-cols-1 items-start gap-5 md:grid-cols-2">
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">Bill number</span>
                                <input v-model="form.bill_no" class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.bill_no" class="mt-1 text-sm text-rose-600">{{ errors.bill_no }}</p>
                            </label>
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">Bill attachment (optional)</span>
                                <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block h-12 w-full min-w-0 rounded-xl border border-slate-300 p-3 text-sm" @change="form.bill_attachment = $event.target.files[0] || null" />
                                <span class="mt-1 block text-xs text-slate-500">PDF, JPG, or PNG. Maximum 5 MB.</span>
                                <p v-if="errors.bill_attachment" class="mt-1 text-sm text-rose-600">{{ errors.bill_attachment }}</p>
                            </label>
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">O.R. number</span>
                                <input v-model="form.or_no" class="mt-2 h-12 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.or_no" class="mt-1 text-sm text-rose-600">{{ errors.or_no }}</p>
                            </label>
                            <label class="block min-w-0">
                                <span class="text-sm font-medium text-slate-700">O.R. attachment (optional)</span>
                                <input type="file" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block h-12 w-full min-w-0 rounded-xl border border-slate-300 p-3 text-sm" @change="form.or_attachment = $event.target.files[0] || null" />
                                <span class="mt-1 block text-xs text-slate-500">PDF, JPG, or PNG. Maximum 5 MB.</span>
                                <p v-if="errors.or_attachment" class="mt-1 text-sm text-rose-600">{{ errors.or_attachment }}</p>
                            </label>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'service-fee'">
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
                :message="'Confirm Bill No. ' + form.bill_no + ' and O.R. No. ' + form.or_no + ' for ' + trainingRequest.fee.reference_no + '. Enter the four-digit code below to confirm.'"
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
