import { Head, Link, router } from '@inertiajs/vue3';
import { computed, defineComponent, reactive, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import ConfirmActionModal from '../../components/ConfirmActionModal';

const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const peso = (amount) => currencyFormatter.format(Number(amount) || 0);

const nowLocal = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 16);
};

export default defineComponent({
    name: 'AdminTrainingFeeForm',
    components: { AdminShell, ConfirmActionModal, Head, Link },
    props: { serviceRequest: { type: Object, required: true }, trainingRequest: { type: Object, required: true } },
    setup(props) {
        const currentStep = ref(1);
        const showConfirm = ref(false);
        const form = reactive({
            date_time: nowLocal(),
            particulars: '',
            duration: '',
            no_participants: props.trainingRequest.estimated_participants || 1,
            net_amount_due: 0,
        });
        const errors = ref({});
        const processing = ref(false);

        const validate = () => {
            const newErrors = {};

            if (!form.date_time) newErrors.date_time = 'Select the date and time.';
            if (!form.particulars.trim()) newErrors.particulars = 'Enter the particulars.';
            if (!form.duration.trim()) newErrors.duration = 'Enter the duration.';
            if (!form.no_participants || Number(form.no_participants) < 1) newErrors.no_participants = 'Number of participants must be at least 1.';

            const netAmountDue = Number(form.net_amount_due);
            if (form.net_amount_due === '' || form.net_amount_due === null || Number.isNaN(netAmountDue) || netAmountDue < 0) {
                newErrors.net_amount_due = 'Net amount due must be 0 or more.';
            }

            errors.value = newErrors;

            return Object.keys(newErrors).length === 0;
        };

        const goToReview = () => {
            if (validate()) {
                currentStep.value = 2;
            }
        };

        const goBack = () => {
            currentStep.value = 1;
        };

        const confirmSubmit = () => {
            showConfirm.value = true;
        };

        const submit = () => {
            processing.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/training-request/fee`, { ...form }, {
                onError: (submitErrors) => {
                    errors.value = submitErrors;
                    currentStep.value = 1;
                    showConfirm.value = false;
                },
                onFinish: () => {
                    processing.value = false;
                },
            });
        };

        return {
            confirmSubmit,
            currentStep,
            errors,
            form,
            goBack,
            goToReview,
            peso,
            processing,
            showConfirm,
            submit,
        };
    },
    template: `
        <Head title="Training service fee" />
        <AdminShell active="requests" title="Training Service Fee">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }} &middot; {{ serviceRequest.service }}</h2>
                        <p class="mt-1 text-slate-600">{{ currentStep === 1 ? 'Review the training request, then record the service fee.' : 'Review everything below, then submit.' }}</p>
                    </div>
                    <Link href="/admin/requests" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </Link>
                </div>

                <template v-if="currentStep === 1">
                    <div class="mt-7 space-y-8 border-t border-slate-200 pt-7">
                        <section>
                            <h3 class="text-base font-bold text-slate-900">Section 1 &middot; Customer information</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Full name</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.fullname }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.email }}</dd></div>
                            </dl>
                        </section>
                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 2 &amp; 3 &middot; Training details</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Training course requested</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.training_course_requested }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Estimated number of participants</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.estimated_participants }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Proposed training date</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.proposed_training_date }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Proposed training venue</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.proposed_training_venue }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Official course title</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.official_course_title }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Approved training duration</dt><dd class="mt-1 text-slate-700">{{ trainingRequest.approved_training_duration }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <form class="mt-8 border-t border-slate-200 pt-8" @submit.prevent="goToReview">
                        <h3 class="text-base font-bold text-slate-900">Service fee</h3>
                        <p class="mt-1 text-sm text-slate-500">Record the training services fee slip details.</p>
                        <div class="mt-5 grid gap-5 md:grid-cols-6">
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Date / Time</span>
                                <input v-model="form.date_time" type="datetime-local" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.date_time" class="mt-1 text-sm text-rose-600">{{ errors.date_time }}</p>
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Duration</span>
                                <input v-model="form.duration" placeholder="e.g. 3 days" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.duration" class="mt-1 text-sm text-rose-600">{{ errors.duration }}</p>
                            </label>
                            <label class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">Particulars</span>
                                <input v-model="form.particulars" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.particulars" class="mt-1 text-sm text-rose-600">{{ errors.particulars }}</p>
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">No. of participants</span>
                                <input v-model.number="form.no_participants" type="number" min="1" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.no_participants" class="mt-1 text-sm text-rose-600">{{ errors.no_participants }}</p>
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Net amount due</span>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">₱</span>
                                    <input v-model.number="form.net_amount_due" type="number" min="0" step="0.01" class="mt-1 w-full rounded-xl border border-slate-300 py-2.5 pl-7 pr-3" />
                                </div>
                                <p v-if="errors.net_amount_due" class="mt-1 text-sm text-rose-600">{{ errors.net_amount_due }}</p>
                            </label>
                        </div>

                        <div class="mt-8 flex justify-end border-t border-slate-100 pt-7">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]">
                                Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>
                </template>

                <template v-else>
                    <div class="mt-7 border-t border-slate-200 pt-7">
                        <h3 class="text-base font-bold text-slate-900">Service fee</h3>
                        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Date / Time</dt><dd class="mt-1 text-slate-700">{{ form.date_time.replace('T', ' ') }}</dd></div>
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Duration</dt><dd class="mt-1 text-slate-700">{{ form.duration }}</dd></div>
                            <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Particulars</dt><dd class="mt-1 text-slate-700">{{ form.particulars }}</dd></div>
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">No. of participants</dt><dd class="mt-1 text-slate-700">{{ form.no_participants }}</dd></div>
                            <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Net amount due</dt><dd class="mt-1 font-semibold text-slate-900">{{ peso(form.net_amount_due) }}</dd></div>
                        </dl>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" :disabled="processing" @click="goBack">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-400" :disabled="processing" @click="confirmSubmit">
                            {{ processing ? 'Saving…' : 'Submit' }}
                        </button>
                    </div>
                </template>
            </section>

            <ConfirmActionModal
                :open="showConfirm"
                :processing="processing"
                title="Submit this service fee?"
                message="This will save the service fee and move the service request to for payment. Are you sure you want to submit?"
                confirm-label="Submit"
                icon="fa-solid fa-paper-plane"
                @close="showConfirm = false"
                @confirm="submit"
            />
        </AdminShell>
    `,
});
