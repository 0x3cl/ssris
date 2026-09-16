import { Head, router } from '@inertiajs/vue3';
import { computed, defineComponent, reactive, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import ConfirmActionModal from '../../components/ConfirmActionModal';

const blankItem = () => ({ item: '', specification: '', quantity: 1, unit_fee: 0 });

const discountOptions = [
    { value: 100, label: '100%' },
    { value: 20, label: '20%' },
    { value: 0, label: 'N/A' },
];

const referencePrefixes = ['CDABUS', 'NFUS'];

const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const peso = (amount) => currencyFormatter.format(Number(amount) || 0);

export default defineComponent({
    name: 'AdminRddRequestForm',
    components: { AdminShell, ConfirmActionModal, Head },
    props: { serviceRequest: { type: Object, required: true } },
    setup(props) {
        const currentStep = ref(1);
        const showConfirm = ref(false);
        const form = reactive({
            reference_prefix: '',
            due_date: '',
            discount_percentage: 0,
            items: [blankItem()],
        });
        const errors = ref({});
        const processing = ref(false);

        const minDueDate = computed(() => new Date().toISOString().slice(0, 10));

        const rowTotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_fee) || 0);
        const subTotal = computed(() => form.items.reduce((sum, item) => sum + rowTotal(item), 0));
        const discountAmount = computed(() => subTotal.value * (form.discount_percentage / 100));
        const totalFee = computed(() => subTotal.value - discountAmount.value);

        const addItem = () => {
            form.items.push(blankItem());
        };
        const removeItem = (index) => {
            if (form.items.length > 1) form.items.splice(index, 1);
        };

        const clientInitials = computed(() => {
            const parts = (props.serviceRequest.client.fullname || '').trim().split(/\s+/).filter(Boolean);
            if (parts.length === 0) return '';

            return (parts[0][0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
        });

        const validate = () => {
            const newErrors = {};

            if (!form.reference_prefix) {
                newErrors.reference_prefix = 'Choose a reference number series.';
            }

            if (!form.due_date) {
                newErrors.due_date = 'Select a due date.';
            } else if (form.due_date < minDueDate.value) {
                newErrors.due_date = 'Due date cannot be in the past.';
            }

            const discountPercentage = Number(form.discount_percentage);
            if (form.discount_percentage === '' || form.discount_percentage === null || Number.isNaN(discountPercentage) || discountPercentage < 0 || discountPercentage > 100) {
                newErrors.discount_percentage = 'Discount must be between 0 and 100.';
            }

            form.items.forEach((item, index) => {
                if (!item.item.trim()) newErrors[`items.${index}.item`] = 'Enter the service request.';
                if (!item.specification.trim()) newErrors[`items.${index}.specification`] = 'Enter specifications.';
                if (!item.quantity || Number(item.quantity) < 1) newErrors[`items.${index}.quantity`] = 'Quantity must be at least 1.';

                const unitFee = Number(item.unit_fee);
                if (item.unit_fee === '' || item.unit_fee === null || Number.isNaN(unitFee) || unitFee < 0) {
                    newErrors[`items.${index}.unit_fee`] = 'Unit fee must be 0 or more.';
                }
            });

            const hasItemErrors = Object.keys(newErrors).some((key) => key.startsWith('items.'));
            if (!hasItemErrors && subTotal.value <= 0) {
                newErrors.total = 'The total amount cannot be 0. Add at least one item with a quantity and unit fee.';
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
            router.post(`/admin/requests/${props.serviceRequest.id}/rdd-request`, {
                reference_prefix: form.reference_prefix,
                due_date: form.due_date,
                discount_percentage: form.discount_percentage,
                items: form.items,
            }, {
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
            addItem,
            clientInitials,
            confirmSubmit,
            currentStep,
            discountAmount,
            discountOptions,
            errors,
            form,
            goBack,
            goToReview,
            minDueDate,
            peso,
            processing,
            referencePrefixes,
            removeItem,
            rowTotal,
            showConfirm,
            subTotal,
            submit,
            totalFee,
        };
    },
    template: `
        <Head title="R&D request form" />
        <AdminShell active="requests" title="R&D Request Form">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }} &middot; {{ serviceRequest.service }}</h2>
                        <p class="mt-1 text-slate-600">{{ currentStep === 1 ? 'Fill out the receiving officer details, then review before submitting.' : 'Review everything below, then submit.' }}</p>
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
                        <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Receiving officer details</h3>
                        <p class="mt-1 text-sm text-slate-500">Choose a reference number series and record the requested items.</p>
                        <p class="mt-1 text-xs text-slate-500">Generated as PREFIX-{{ clientInitials }}-##### from the series you pick and the client's initials.</p>
                        <div class="mt-5 grid gap-5 md:grid-cols-6">
                            <div class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Customer reference number series</span>
                                <div class="mt-1 flex gap-2">
                                    <button v-for="prefix in referencePrefixes" :key="prefix" type="button" class="flex-1 rounded-xl px-4 py-2.5 text-sm font-bold transition" :class="form.reference_prefix === prefix ? 'bg-[#07559e] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" @click="form.reference_prefix = prefix">
                                        {{ prefix }}
                                    </button>
                                </div>
                                <p v-if="errors.reference_prefix" class="mt-1 text-sm text-rose-600">{{ errors.reference_prefix }}</p>
                            </div>
                            <label class="md:col-span-3">
                                <span class="text-sm font-medium text-slate-700">Due date</span>
                                <input v-model="form.due_date" type="date" :min="minDueDate" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.due_date" class="mt-1 text-sm text-rose-600">{{ errors.due_date }}</p>
                            </label>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full min-w-[800px] text-left">
                                <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-3">Service request</th>
                                        <th class="px-3 py-3">Specifications</th>
                                        <th class="px-3 py-3">Qty</th>
                                        <th class="px-3 py-3">Unit fee</th>
                                        <th class="px-3 py-3">Total fee</th>
                                        <th class="px-3 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 align-top">
                                        <td class="px-3 py-3">
                                            <input v-model="item.item" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                                            <p v-if="errors['items.' + index + '.item']" class="mt-1 text-xs text-rose-600">{{ errors['items.' + index + '.item'] }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <input v-model="item.specification" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                                            <p v-if="errors['items.' + index + '.specification']" class="mt-1 text-xs text-rose-600">{{ errors['items.' + index + '.specification'] }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <input v-model.number="item.quantity" type="number" min="1" class="w-24 rounded-lg border border-slate-300 px-3 py-2" />
                                            <p v-if="errors['items.' + index + '.quantity']" class="mt-1 text-xs text-rose-600">{{ errors['items.' + index + '.quantity'] }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="relative">
                                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">₱</span>
                                                <input v-model.number="item.unit_fee" type="number" min="0" step="0.01" class="w-32 rounded-lg border border-slate-300 py-2 pl-7 pr-3" />
                                            </div>
                                            <p v-if="errors['items.' + index + '.unit_fee']" class="mt-1 text-xs text-rose-600">{{ errors['items.' + index + '.unit_fee'] }}</p>
                                        </td>
                                        <td class="px-3 py-3"><input :value="peso(rowTotal(item))" readonly class="w-32 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600" /></td>
                                        <td class="px-3 py-3">
                                            <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg text-rose-600 hover:bg-rose-50" :disabled="form.items.length === 1" aria-label="Remove item" @click="removeItem(index)">
                                                <i class="fa-solid fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="6" class="px-3 py-3">
                                            <button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100" @click="addItem">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i>Add item
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <p v-if="errors.total" class="mt-4 text-right text-sm font-semibold text-rose-600">{{ errors.total }}</p>

                        <div class="mt-6 flex flex-wrap justify-end">
                            <div class="w-full max-w-md space-y-4">
                                <div class="flex items-center justify-between text-sm font-semibold text-slate-700">
                                    <span>Sub total</span>
                                    <input :value="peso(subTotal)" readonly class="w-32 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-right" />
                                </div>
                                <div>
                                    <span class="text-sm font-semibold text-slate-700">Discount</span>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <button v-for="option in discountOptions" :key="option.value" type="button" class="flex-1 rounded-lg px-4 py-2 text-sm font-bold transition" :class="form.discount_percentage === option.value ? 'bg-[#07559e] text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'" @click="form.discount_percentage = option.value">
                                            {{ option.label }}
                                        </button>
                                        <div class="relative w-24">
                                            <input v-model.number="form.discount_percentage" type="number" min="0" max="100" step="0.01" placeholder="Custom" class="w-full rounded-lg border border-slate-300 py-2 pl-3 pr-6 text-right text-sm font-bold text-slate-700" />
                                            <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-sm text-slate-500">%</span>
                                        </div>
                                    </div>
                                    <p v-if="errors.discount_percentage" class="mt-1 text-sm text-rose-600">{{ errors.discount_percentage }}</p>
                                </div>
                                <div class="flex items-center justify-between border-t border-slate-200 pt-4 text-base font-bold text-slate-900">
                                    <span>Total fee</span>
                                    <input :value="peso(totalFee)" readonly class="w-32 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-right" />
                                </div>
                            </div>
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
                            <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Receiving officer details</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Reference number</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ form.reference_prefix }}-{{ clientInitials }}-##### <span class="font-normal text-slate-500"></span></dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Due date</dt>
                                    <dd class="mt-1 text-slate-700">{{ form.due_date }}</dd>
                                </div>
                            </dl>
                            <div class="mt-6 overflow-x-auto">
                                <table class="w-full min-w-[500px] text-left text-sm">
                                    <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">Item</th>
                                            <th class="px-3 py-2">Specifications</th>
                                            <th class="px-3 py-2">Qty</th>
                                            <th class="px-3 py-2">Unit fee</th>
                                            <th class="px-3 py-2">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100">
                                            <td class="px-3 py-2">{{ item.item || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.specification || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.quantity }}</td>
                                            <td class="px-3 py-2">{{ peso(item.unit_fee) }}</td>
                                            <td class="px-3 py-2">{{ peso(rowTotal(item)) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <dl class="mt-6 ml-auto max-w-sm space-y-2 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Sub total</dt><dd class="font-semibold text-slate-900">{{ peso(subTotal) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Discount ({{ form.discount_percentage }}%)</dt><dd class="font-semibold text-slate-900">-{{ peso(discountAmount) }}</dd></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt class="font-bold text-slate-900">Total fee</dt><dd class="font-bold text-slate-900">{{ peso(totalFee) }}</dd></div>
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
                title="Submit this R&D request form?"
                message="This will save the request items and move the service request to for payment. Are you sure you want to submit?"
                confirm-label="Submit"
                icon="fa-solid fa-paper-plane"
                @close="showConfirm = false"
                @confirm="submit"
            />
        </AdminShell>
    `,
});
