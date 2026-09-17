import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, defineComponent, ref, watch } from 'vue';
import AdminShell from '../../components/AdminShell.js';
import ConfirmActionModal from '../../components/ConfirmActionModal.js';
import SignatorySelect from '../../components/SignatorySelect.js';
import { useQueryTab } from '../../utils/query-tab.js';

export default defineComponent({
    components: { Head, Link, AdminShell, ConfirmActionModal, SignatorySelect },
    props: {
        serviceRequest: { type: Object, required: true },
        tourRequest: { type: Object, required: true },
        signatoryOptions: { type: Object, required: true },
        actionUrls: { type: Object, required: true },
        today: { type: String, required: true },
        canWrite: { type: Boolean, default: false },
    },
    setup(props) {
        const isPending = computed(() => props.canWrite && props.serviceRequest.status_value === 'pending');
        const canSign = computed(() => props.canWrite && props.serviceRequest.status_value === 'assign-signatories');
        const showSignatories = computed(() => ['assign-signatories', 'for-signature', 'awaiting-feedback', 'completed'].includes(props.serviceRequest.status_value));
        const showForSignature = computed(() => props.serviceRequest.status_value === 'for-signature');
        const activeTab = useQueryTab(showForSignature.value ? ['service-request', 'signatories', 'for-signature'] : (showSignatories.value ? ['service-request', 'signatories'] : ['service-request']), 'service-request');
        watch(() => props.serviceRequest.status_value, (status) => {
            if (status === 'assign-signatories') activeTab.value = 'signatories';
            if (status === 'for-signature') activeTab.value = 'for-signature';
        });
        const modalAction = ref(null);
        const confirmSignatories = ref(false);
        const confirmAccept = ref(false);
        const action = useForm({ visit_date: '', visit_time: '', reason: '' });
        const fields = [
            { key: 'prepared_by', label: 'Prepared by', optional: false },
            { key: 'noted_by', label: 'Noted by', optional: false },
            { key: 'conforme_primary', label: 'Primary conforme', optional: false },
            { key: 'conforme_secondary', label: 'Secondary conforme', optional: false },
            { key: 'conforme_optional', label: 'Optional conforme', optional: true },
        ];
        const signatories = useForm(Object.fromEntries([
            ...fields.map(({ key }) => [key, props.tourRequest[key] ?? '']),
            ['remarks', props.tourRequest.remarks ?? ''],
        ]));
        const openAction = () => {
            action.reset();
            action.clearErrors();
            action.visit_date = props.tourRequest.visit_date_value ?? '';
            action.visit_time = props.tourRequest.visit_time_value ?? '';
            modalAction.value = 'accept';
            confirmAccept.value = false;
        };
        const submitAction = () => action.post(props.actionUrls[modalAction.value], {
            preserveScroll: true,
            onSuccess: () => { modalAction.value = null; confirmAccept.value = false; },
        });
        const continueAction = () => {
            if (modalAction.value === 'accept') {
                confirmAccept.value = true;
            } else {
                submitAction();
            }
        };
        const submitSignatories = () => signatories.post(props.actionUrls.signatories, {
            preserveScroll: true,
            onSuccess: () => { confirmSignatories.value = false; },
            onError: () => { confirmSignatories.value = false; },
        });
        const confirmMarkDone = ref(false);
        const markingDone = ref(false);
        const markDone = () => {
            markingDone.value = true;
            router.post(props.actionUrls.markDone, {}, {
                preserveScroll: true,
                onFinish: () => {
                    markingDone.value = false;
                    confirmMarkDone.value = false;
                },
            });
        };
        return { activeTab, isPending, canSign, showSignatories, showForSignature, fields, signatories, action, modalAction, confirmAccept, confirmSignatories, confirmMarkDone, markingDone, openAction, continueAction, submitAction, submitSignatories, markDone };
    },
    template: `
        <Head title="Plant tour service request" />
        <AdminShell active="requests">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#07559e]">Plant tour services</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }}</h2>
                        <p class="mt-2 text-sm text-slate-500">Review the client's submitted tour request.</p>
                    </div>
                    <Link href="/admin/requests" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-[#07559e] hover:bg-sky-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </Link>
                </div>
                <div class="mt-7 flex border-b border-slate-200">
                    <button type="button" class="border-b-2 px-3 py-3 text-sm font-bold" :class="activeTab === 'service-request' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500'" @click="activeTab = 'service-request'">Service Request</button>
                    <button v-if="showSignatories" type="button" class="border-b-2 px-3 py-3 text-sm font-bold" :class="activeTab === 'signatories' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500'" @click="activeTab = 'signatories'">Signatories</button>
                    <button v-if="showForSignature" type="button" class="border-b-2 px-3 py-3 text-sm font-bold" :class="activeTab === 'for-signature' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500'" @click="activeTab = 'for-signature'">For Signature</button>
                </div>
                <template v-if="activeTab === 'service-request'">
                <div class="mt-7 flex justify-end">
                    <a :href="actionUrls.requestPdf" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Download PDF
                    </a>
                </div>
                <div class="mt-7">
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
                            <label v-if="serviceRequest.description" class="md:col-span-6">
                                <span class="text-sm font-medium text-slate-700">Request description</span>
                                <textarea :value="serviceRequest.description" disabled tabindex="-1" rows="3" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700"></textarea>
                            </label>
                        </div>

                </div>
                <div class="mt-8 border-t border-slate-200 pt-7">
                    <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Tour request details</h3>
                            <dl class="mt-4 grid gap-5 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Desired date of tour</dt><dd class="mt-1 text-slate-700">{{ tourRequest.visit_date || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Desired time of tour</dt><dd class="mt-1 text-slate-700">{{ tourRequest.visit_time || '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Message to us</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ tourRequest.message || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Number of persons</dt><dd class="mt-1 text-slate-700">{{ tourRequest.no_persons ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Number of group(s)/batch(es)</dt><dd class="mt-1 text-slate-700">{{ tourRequest.no_groups ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Testing laboratories</dt><dd class="mt-1 text-slate-700">{{ tourRequest.testing_lab.length ? tourRequest.testing_lab.join(', ') : '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">TSD pilot plant</dt><dd class="mt-1 text-slate-700">{{ tourRequest.pilot_plant.length ? tourRequest.pilot_plant.join(', ') : '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Others (facilities)</dt><dd class="mt-1 text-slate-700">{{ tourRequest.others.length ? tourRequest.others.join(', ') : '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Technology assistance</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ tourRequest.technology_assistance || '—' }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Objective of this visit</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ tourRequest.visit_objectives || '—' }}</dd></div>
                            </dl>

                </div>
                <div v-if="isPending" class="mt-8 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold text-white hover:bg-[#008dcc]" @click="openAction">
                        <i class="fa-solid fa-bolt" aria-hidden="true"></i>Take Action
                    </button>
                </div>
                <div v-else-if="showSignatories" class="mt-8 flex justify-end border-t border-slate-100 pt-7">
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#07559e] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#043d78]" @click="activeTab = 'signatories'">
                        Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
                </template>
                <form v-else-if="activeTab === 'signatories' && showSignatories" class="mt-7" @submit.prevent="confirmSignatories = true">
                    <div class="grid gap-5 md:grid-cols-6">
                        <div v-for="(field, index) in fields" :key="field.key" :class="index < 2 ? 'md:col-span-3' : 'md:col-span-2'">
                            <SignatorySelect :id="'signatory-' + field.key" v-model="signatories[field.key]" :label="field.label" :options="signatoryOptions[field.key]" :optional="field.optional" :disabled="!canSign || signatories.processing" />
                            <p v-if="signatories.errors[field.key]" class="mt-1 text-sm text-rose-600">{{ signatories.errors[field.key] }}</p>
                        </div>
                        <label class="md:col-span-6">
                            <span class="text-sm font-semibold text-slate-700">Remarks (if any)</span>
                            <textarea v-model="signatories.remarks" rows="5" maxlength="5000" :disabled="!canSign || signatories.processing" class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2.5 disabled:bg-slate-50"></textarea>
                            <p v-if="signatories.errors.remarks" class="mt-1 text-sm text-rose-600">{{ signatories.errors.remarks }}</p>
                        </label>
                    </div>
                    <div class="mt-8 flex justify-between border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'service-request'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back
                        </button>
                        <button v-if="canSign" type="submit" :disabled="signatories.processing" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-[#008dcc] disabled:opacity-50">
                            Submit<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                        <button v-else-if="showForSignature" type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#07559e] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#043d78]" @click="activeTab = 'for-signature'">
                            Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </form>
                <div v-else-if="activeTab === 'for-signature'" class="mt-7 rounded-2xl border border-sky-100 bg-sky-50 p-6">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Tour confirmation</h3>
                            <p class="mt-1 text-sm leading-6 text-slate-600">The confirmation is ready with the submitted recipient, visit details, facilities, purpose, and signatories.</p>
                        </div>
                        <a :href="actionUrls.confirmationPdf" class="inline-flex items-center gap-2 rounded-xl bg-[#07559e] px-5 py-3 text-sm font-bold text-white hover:bg-[#06457f]">
                            <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Download confirmation PDF
                        </a>
                    </div>
                    <div class="mt-6 flex flex-wrap justify-between gap-3 border-t border-sky-200 pt-6">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-white" @click="activeTab = 'signatories'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-emerald-700" @click="confirmMarkDone = true">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>Mark tour as done
                        </button>
                    </div>
                </div>
            </section>
            <ConfirmActionModal :open="confirmAccept" title="Accept this tour?" message="This will confirm the tour, send a confirmation email, and move the request to Assign Signatories." confirm-label="Accept Tour" :processing="action.processing" @close="confirmAccept = false" @confirm="submitAction" />
            <ConfirmActionModal :open="confirmSignatories" title="Submit tour signatories?" message="This will save the signatories and move the tour to For Signature." confirm-label="Submit" :processing="signatories.processing" @close="confirmSignatories = false" @confirm="submitSignatories" />
            <ConfirmActionModal :open="confirmMarkDone" title="Mark this tour as done?" message="This will move the request to Awaiting Feedback and send the client a Customer Satisfaction Feedback reminder email." icon="fa-solid fa-check" confirm-label="Mark tour as done" :processing="markingDone" @close="confirmMarkDone = false" @confirm="markDone" />
            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="modalAction && !confirmAccept" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="tour-action-title" @keydown.esc="!action.processing && (modalAction = null)">
                <form class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-3xl bg-white p-7 shadow-2xl sm:p-9" @submit.prevent="continueAction">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-sky-100 text-2xl text-[#07559e]">
                        <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                    </div>
                    <h2 id="tour-action-title" class="mt-5 text-2xl font-bold text-slate-900">Take action on this tour</h2>
                    <p class="mt-2 leading-7 text-slate-600">Service request #{{ serviceRequest.id }} is scheduled for <strong>{{ tourRequest.visit_date || '—' }} {{ tourRequest.visit_time }}</strong>.</p>
                    <fieldset class="mt-5 space-y-2" :disabled="action.processing">
                        <legend class="text-sm font-semibold text-slate-700">What would you like to do?</legend>
                        <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm" :class="modalAction === 'accept' ? 'border-[#00aeef] bg-sky-50' : ''">
                            <input v-model="modalAction" type="radio" value="accept" class="mt-1 h-4 w-4 cursor-pointer accent-[#07559e]" @change="action.clearErrors()" />
                            <span><span class="font-semibold text-slate-900">Accept Tour</span><br /><span class="text-slate-500">Confirm the tour as scheduled, email the client, and proceed to Signatories.</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm" :class="modalAction === 'reschedule' ? 'border-[#00aeef] bg-sky-50' : ''">
                            <input v-model="modalAction" type="radio" value="reschedule" class="mt-1 h-4 w-4 cursor-pointer accent-[#07559e]" @change="action.clearErrors()" />
                            <span><span class="font-semibold text-slate-900">Re-Schedule tour</span><br /><span class="text-slate-500">Propose a new date and time and email the client. The tour remains pending acceptance.</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm" :class="modalAction === 'cancel' ? 'border-rose-500 bg-rose-50' : ''">
                            <input v-model="modalAction" type="radio" value="cancel" class="mt-1 h-4 w-4 cursor-pointer accent-rose-600" @change="action.clearErrors()" />
                            <span><span class="font-semibold text-slate-900">Cancel Tour</span><br /><span class="text-slate-500">Mark the tour as cancelled and email the client with your reason.</span></span>
                        </label>
                    </fieldset>
                    <div v-if="modalAction === 'reschedule'" class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label><span class="text-sm font-semibold text-slate-700">Proposed date</span><input v-model="action.visit_date" type="date" :min="today" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /><p v-if="action.errors.visit_date" class="mt-1 text-sm text-rose-600">{{ action.errors.visit_date }}</p></label>
                        <label><span class="text-sm font-semibold text-slate-700">Proposed time</span><input v-model="action.visit_time" type="time" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /><p v-if="action.errors.visit_time" class="mt-1 text-sm text-rose-600">{{ action.errors.visit_time }}</p></label>
                    </div>
                    <label v-if="modalAction !== 'accept'" class="mt-5 block"><span class="required-label text-sm font-semibold text-slate-700">Reason for {{ modalAction === 'cancel' ? 'cancellation' : 'rescheduling' }}</span><textarea v-model="action.reason" rows="4" required maxlength="1000" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea><p v-if="action.errors.reason" class="mt-1 text-sm text-rose-600">{{ action.errors.reason }}</p></label>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" :disabled="action.processing" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700" @click="modalAction = null">Close</button>
                        <button type="submit" :disabled="action.processing || (modalAction !== 'accept' && !action.reason.trim()) || (modalAction === 'reschedule' && (!action.visit_date || !action.visit_time))" class="rounded-lg px-5 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60" :class="modalAction === 'cancel' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-[#00aeef] hover:bg-[#008dcc]'">{{ action.processing ? 'Submitting…' : 'Continue' }}</button>
                    </div>
                </form>
            </div>
            </Transition>
        </AdminShell>
    `,
});
