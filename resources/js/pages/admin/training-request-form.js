import { Head, router } from '@inertiajs/vue3';
import { computed, defineComponent, reactive, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import ConfirmActionModal from '../../components/ConfirmActionModal';

const trainingTypes = [
    { value: 'in-house', label: 'In-house' },
    { value: 'regional', label: 'Regional' },
    { value: 'virtual', label: 'Virtual' },
];

export default defineComponent({
    name: 'AdminTrainingRequestForm',
    components: { AdminShell, ConfirmActionModal, Head },
    props: { serviceRequest: { type: Object, required: true } },
    setup(props) {
        const currentStep = ref(1);
        const showConfirm = ref(false);
        const form = reactive({
            training_course_requested: '',
            estimated_participants: 1,
            proposed_training_date: '',
            proposed_training_venue: '',
            beneficiary_name: '',
            purpose_of_training: '',
            assigned_trainer: '',
            assigned_assistant_trainer: '',
            official_course_title: '',
            approved_training_duration: '',
            training_type: '',
            special_type_details: '',
        });
        const errors = ref({});
        const processing = ref(false);

        const minTrainingDate = computed(() => new Date().toISOString().slice(0, 10));

        const validate = () => {
            const newErrors = {};

            if (!form.training_course_requested.trim()) newErrors.training_course_requested = 'Enter the training course requested.';
            if (!form.estimated_participants || Number(form.estimated_participants) < 1) newErrors.estimated_participants = 'Estimated participants must be at least 1.';
            if (!form.proposed_training_date) {
                newErrors.proposed_training_date = 'Select the proposed training date.';
            } else if (form.proposed_training_date < minTrainingDate.value) {
                newErrors.proposed_training_date = 'Proposed training date cannot be in the past.';
            }
            if (!form.proposed_training_venue.trim()) newErrors.proposed_training_venue = 'Enter the proposed training venue.';
            if (!form.beneficiary_name.trim()) newErrors.beneficiary_name = 'Enter the name of the beneficiary or community.';
            if (!form.purpose_of_training.trim()) newErrors.purpose_of_training = 'Enter the purpose of training.';
            if (!form.assigned_trainer.trim()) newErrors.assigned_trainer = 'Enter the assigned trainer.';
            if (!form.official_course_title.trim()) newErrors.official_course_title = 'Enter the official course title.';
            if (!form.approved_training_duration.trim()) newErrors.approved_training_duration = 'Enter the approved training duration.';
            if (!form.training_type) newErrors.training_type = 'Choose the type of training.';

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
            router.post(`/admin/requests/${props.serviceRequest.id}/training-request`, { ...form }, {
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
            minTrainingDate,
            processing,
            showConfirm,
            submit,
            trainingTypes,
        };
    },
    template: `
        <Head title="Training request form" />
        <AdminShell active="requests" title="Training Request Form">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }} &middot; {{ serviceRequest.service }}</h2>
                        <p class="mt-1 text-slate-600">{{ currentStep === 1 ? 'Fill out the training details, then review before submitting.' : 'Review everything below, then submit.' }}</p>
                    </div>
                    <a href="/admin/requests" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </a>
                </div>

                <template v-if="currentStep === 1">
                    <div class="mt-7 border-t border-slate-200 pt-7">
                        <h3 class="text-base font-bold text-slate-900">Section 1 &middot; Customer information</h3>
                        <p class="mt-1 text-sm text-slate-500">Submitted by the client. These fields cannot be edited here.</p>
                        <div class="mt-5 grid gap-5 md:grid-cols-6">
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Full name</span>
                                <input :value="serviceRequest.client.fullname" disabled tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Client type</span>
                                <input :value="serviceRequest.client.type_client" disabled tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                            </label>
                            <label class="md:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Email</span>
                                <input :value="serviceRequest.client.email" disabled tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                            </label>
                            <label class="md:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Mobile number</span>
                                <input :value="serviceRequest.client.mobile_no" disabled tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                            </label>
                            <label class="md:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Company / School</span>
                                <input :value="serviceRequest.client.company_or_school || '—'" disabled tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                            </label>
                            <label class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">Address</span>
                                <input :value="serviceRequest.client.address" disabled tabindex="-1" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                            </label>
                            <label class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">Request description</span>
                                <textarea :value="serviceRequest.description" disabled tabindex="-1" rows="3" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700"></textarea>
                            </label>
                        </div>
                    </div>

                    <form class="mt-8 border-t border-slate-200 pt-8" @submit.prevent="goToReview">
                        <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Training details</h3>
                        <p class="mt-1 text-sm text-slate-500">Details of the training being requested.</p>
                        <div class="mt-5 grid gap-5 md:grid-cols-6">
                            <label class="md:col-span-4">
                                <span class="text-sm font-medium text-slate-700">Training course requested</span>
                                <input v-model="form.training_course_requested" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.training_course_requested" class="mt-1 text-sm text-rose-600">{{ errors.training_course_requested }}</p>
                            </label>
                            <label class="md:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Estimated number of participants</span>
                                <input v-model.number="form.estimated_participants" type="number" min="1" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.estimated_participants" class="mt-1 text-sm text-rose-600">{{ errors.estimated_participants }}</p>
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Proposed training date</span>
                                <input v-model="form.proposed_training_date" type="date" :min="minTrainingDate" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.proposed_training_date" class="mt-1 text-sm text-rose-600">{{ errors.proposed_training_date }}</p>
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Proposed training venue</span>
                                <input v-model="form.proposed_training_venue" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.proposed_training_venue" class="mt-1 text-sm text-rose-600">{{ errors.proposed_training_venue }}</p>
                            </label>
                            <label class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">Name of beneficiary / community</span>
                                <input v-model="form.beneficiary_name" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.beneficiary_name" class="mt-1 text-sm text-rose-600">{{ errors.beneficiary_name }}</p>
                            </label>
                            <label class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">Purpose of training</span>
                                <textarea v-model="form.purpose_of_training" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
                                <p v-if="errors.purpose_of_training" class="mt-1 text-sm text-rose-600">{{ errors.purpose_of_training }}</p>
                            </label>
                        </div>

                        <h3 class="mt-8 text-base font-bold text-slate-900">Section 3 &middot; To be filled up by TSD Training staff</h3>
                        <div class="mt-5 grid gap-5 md:grid-cols-6">
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Assigned trainer</span>
                                <input v-model="form.assigned_trainer" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.assigned_trainer" class="mt-1 text-sm text-rose-600">{{ errors.assigned_trainer }}</p>
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Assigned assistant trainer (optional)</span>
                                <input v-model="form.assigned_assistant_trainer" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                            </label>
                            <label class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">Official course title</span>
                                <input v-model="form.official_course_title" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.official_course_title" class="mt-1 text-sm text-rose-600">{{ errors.official_course_title }}</p>
                            </label>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Approved training duration</span>
                                <input v-model="form.approved_training_duration" placeholder="e.g. 3 days" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.approved_training_duration" class="mt-1 text-sm text-rose-600">{{ errors.approved_training_duration }}</p>
                            </label>
                            <div class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Type of training</span>
                                <div class="mt-1 flex gap-2">
                                    <button v-for="type in trainingTypes" :key="type.value" type="button" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-bold transition" :class="form.training_type === type.value ? 'bg-[#07559e] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" @click="form.training_type = type.value">
                                        {{ type.label }}
                                    </button>
                                </div>
                                <p v-if="errors.training_type" class="mt-1 text-sm text-rose-600">{{ errors.training_type }}</p>
                            </div>
                            <label class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">If special, please specify (optional)</span>
                                <input v-model="form.special_type_details" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
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
                    <div class="mt-7 space-y-8 border-t border-slate-200 pt-7">
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
                            <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Training details</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Training course requested</dt><dd class="mt-1 text-slate-700">{{ form.training_course_requested }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Estimated number of participants</dt><dd class="mt-1 text-slate-700">{{ form.estimated_participants }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Proposed training date</dt><dd class="mt-1 text-slate-700">{{ form.proposed_training_date }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Proposed training venue</dt><dd class="mt-1 text-slate-700">{{ form.proposed_training_venue }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Name of beneficiary / community</dt><dd class="mt-1 text-slate-700">{{ form.beneficiary_name }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Purpose of training</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ form.purpose_of_training }}</dd></div>
                            </dl>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 3 &middot; To be filled up by TSD Training staff</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Assigned trainer</dt><dd class="mt-1 text-slate-700">{{ form.assigned_trainer }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Assigned assistant trainer</dt><dd class="mt-1 text-slate-700">{{ form.assigned_assistant_trainer || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Official course title</dt><dd class="mt-1 text-slate-700">{{ form.official_course_title }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Approved training duration</dt><dd class="mt-1 text-slate-700">{{ form.approved_training_duration }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Type of training</dt><dd class="mt-1 capitalize text-slate-700">{{ form.training_type }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">If special, specify</dt><dd class="mt-1 text-slate-700">{{ form.special_type_details || '—' }}</dd></div>
                            </dl>
                        </section>
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
                title="Submit this training request form?"
                message="This will save the training details and move the service request to for service fee. Are you sure you want to submit?"
                confirm-label="Submit"
                icon="fa-solid fa-paper-plane"
                @close="showConfirm = false"
                @confirm="submit"
            />
        </AdminShell>
    `,
});
