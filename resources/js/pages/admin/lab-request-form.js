import { Head, router } from '@inertiajs/vue3';
import { computed, defineComponent, reactive, ref } from 'vue';
import AdminShell from '../../components/AdminShell';
import ConfirmActionModal from '../../components/ConfirmActionModal';

const blankItem = () => ({ test: '', ulims_test_id: null, method: '', quantity: 1, unit_fee: 0 });

const discountOptions = [
    { value: 100, label: '100%' },
    { value: 20, label: '20%' },
    { value: 0, label: 'N/A' },
];

const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const peso = (amount) => currencyFormatter.format(Number(amount) || 0);

export default defineComponent({
    name: 'AdminLabRequestForm',
    components: { AdminShell, ConfirmActionModal, Head },
    props: {
        serviceRequest: { type: Object, required: true },
        testCategories: { type: Array, default: () => [] },
        sampleTypes: { type: Array, default: () => [] },
        testMethods: { type: Array, default: () => [] },
    },
    setup(props) {
        const currentStep = ref(1);
        const showConfirm = ref(false);
        const form = reactive({
            test_category: '',
            ulims_test_category_id: null,
            sample_type: '',
            ulims_sample_type_id: null,
            due_date: '',
            discount_percentage: 0,
            items: [blankItem()],
        });
        const errors = ref({});
        const processing = ref(false);

        const catalogueUnavailable = computed(() => props.testCategories.length === 0 && props.sampleTypes.length === 0 && props.testMethods.length === 0);

        const minDueDate = computed(() => new Date().toISOString().slice(0, 10));

        const filteredSampleTypes = computed(() => props.sampleTypes.filter((sampleType) => sampleType.testCategoryId === form.ulims_test_category_id));
        const filteredTestMethods = computed(() => props.testMethods.filter((method) => method.sampleType === form.ulims_sample_type_id));

        const onTestCategoryChange = (categoryId) => {
            const category = props.testCategories.find((item) => item.id === categoryId);
            form.ulims_test_category_id = categoryId;
            form.test_category = category?.categoryName ?? '';
            form.ulims_sample_type_id = null;
            form.sample_type = '';
            form.items.forEach((item) => {
                item.test = '';
                item.ulims_test_id = null;
                item.unit_fee = 0;
            });
        };

        const onSampleTypeChange = (sampleTypeId) => {
            const sampleType = props.sampleTypes.find((item) => item.id === sampleTypeId);
            form.ulims_sample_type_id = sampleTypeId;
            form.sample_type = sampleType?.sampleType ?? '';
            form.items.forEach((item) => {
                item.test = '';
                item.ulims_test_id = null;
                item.unit_fee = 0;
            });
        };

        const onTestSelected = (item, testId) => {
            const method = props.testMethods.find((candidate) => candidate.id === testId);
            item.ulims_test_id = testId;
            item.test = method?.testName ?? '';
            item.unit_fee = method?.fee ?? 0;
        };

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

        const validate = () => {
            const newErrors = {};

            if (!form.ulims_test_category_id) {
                newErrors.test_category = 'Choose a test category.';
            }

            if (!form.ulims_sample_type_id) {
                newErrors.sample_type = 'Choose a type of sample.';
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
                if (!item.ulims_test_id) newErrors[`items.${index}.test`] = 'Select a test.';
                if (!item.method.trim()) newErrors[`items.${index}.method`] = 'Enter the test method conditions.';
                if (!item.quantity || Number(item.quantity) < 1) newErrors[`items.${index}.quantity`] = 'Quantity must be at least 1.';
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
            router.post(`/admin/requests/${props.serviceRequest.id}/lab-request`, {
                test_category: form.test_category,
                ulims_test_category_id: form.ulims_test_category_id,
                sample_type: form.sample_type,
                ulims_sample_type_id: form.ulims_sample_type_id,
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
            catalogueUnavailable,
            confirmSubmit,
            currentStep,
            discountAmount,
            discountOptions,
            errors,
            filteredSampleTypes,
            filteredTestMethods,
            form,
            goBack,
            goToReview,
            minDueDate,
            onSampleTypeChange,
            onTestCategoryChange,
            onTestSelected,
            peso,
            processing,
            removeItem,
            rowTotal,
            showConfirm,
            subTotal,
            submit,
            totalFee,
        };
    },
    template: `
        <Head title="Lab request form" />
        <AdminShell active="requests" title="Lab Request Form">
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

                <div v-if="catalogueUnavailable" class="mt-6 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
                    <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
                    <p>Could not load the ULIMS test catalogue. Check the ULIMS configuration or connectivity, then reload this page.</p>
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
                        <p class="mt-1 text-sm text-slate-500">Choose a test category and sample type, then record the requested tests.</p>
                        <p class="mt-1 text-xs text-slate-500">The quotation number is generated automatically when this form is submitted.</p>
                        <div class="mt-5 grid gap-5 md:grid-cols-2">
                            <label>
                                <span class="text-sm font-medium text-slate-700">Test category</span>
                                <select :value="form.ulims_test_category_id" @change="onTestCategoryChange($event.target.value ? Number($event.target.value) : null)" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5">
                                    <option :value="null" disabled>Select a test category</option>
                                    <option v-for="category in testCategories" :key="category.id" :value="category.id">{{ category.categoryName }}</option>
                                </select>
                                <p v-if="errors.test_category" class="mt-1 text-sm text-rose-600">{{ errors.test_category }}</p>
                            </label>
                            <label>
                                <span class="text-sm font-medium text-slate-700">Type of samples</span>
                                <select :value="form.ulims_sample_type_id" @change="onSampleTypeChange($event.target.value ? Number($event.target.value) : null)" :disabled="!form.ulims_test_category_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 disabled:cursor-not-allowed disabled:bg-slate-50">
                                    <option :value="null" disabled>Select a sample type</option>
                                    <option v-for="sampleType in filteredSampleTypes" :key="sampleType.id" :value="sampleType.id">{{ sampleType.sampleType }}</option>
                                </select>
                                <p v-if="errors.sample_type" class="mt-1 text-sm text-rose-600">{{ errors.sample_type }}</p>
                            </label>
                            <label>
                                <span class="text-sm font-medium text-slate-700">Due date</span>
                                <input v-model="form.due_date" type="date" :min="minDueDate" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                <p v-if="errors.due_date" class="mt-1 text-sm text-rose-600">{{ errors.due_date }}</p>
                            </label>
                        </div>

                        <div class="mt-6 overflow-x-auto">
                            <table class="w-full min-w-[900px] text-left">
                                <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-3 py-3">Test/s</th>
                                        <th class="px-3 py-3">Test method conditions</th>
                                        <th class="px-3 py-3">Qty</th>
                                        <th class="px-3 py-3">Unit cost</th>
                                        <th class="px-3 py-3">Total</th>
                                        <th class="px-3 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100 align-top">
                                        <td class="px-3 py-3">
                                            <select :value="item.ulims_test_id" @change="onTestSelected(item, $event.target.value ? Number($event.target.value) : null)" :disabled="!form.ulims_sample_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-50">
                                                <option :value="null" disabled>Select a test method</option>
                                                <option v-for="method in filteredTestMethods" :key="method.id" :value="method.id">{{ method.testName }}</option>
                                            </select>
                                            <p v-if="errors['items.' + index + '.test']" class="mt-1 text-xs text-rose-600">{{ errors['items.' + index + '.test'] }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <input v-model="item.method" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
                                            <p v-if="errors['items.' + index + '.method']" class="mt-1 text-xs text-rose-600">{{ errors['items.' + index + '.method'] }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <input v-model.number="item.quantity" type="number" min="1" class="w-24 rounded-lg border border-slate-300 px-3 py-2" />
                                            <p v-if="errors['items.' + index + '.quantity']" class="mt-1 text-xs text-rose-600">{{ errors['items.' + index + '.quantity'] }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="relative">
                                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">₱</span>
                                                <input :value="item.unit_fee" disabled tabindex="-1" class="w-32 cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 py-2 pl-7 pr-3 text-slate-600" />
                                            </div>
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
                                            <button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100" :disabled="!form.ulims_sample_type_id" @click="addItem">
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
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Test category</dt>
                                    <dd class="mt-1 text-slate-700">{{ form.test_category }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Type of samples</dt>
                                    <dd class="mt-1 text-slate-700">{{ form.sample_type }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Due date</dt>
                                    <dd class="mt-1 text-slate-700">{{ form.due_date }}</dd>
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
                                        <tr v-for="(item, index) in form.items" :key="index" class="border-b border-slate-100">
                                            <td class="px-3 py-2">{{ item.test || '—' }}</td>
                                            <td class="px-3 py-2">{{ item.method || '—' }}</td>
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
                title="Submit this lab request form?"
                message="This will save the request items and move the service request to for payment. Are you sure you want to submit?"
                confirm-label="Submit"
                icon="fa-solid fa-paper-plane"
                @close="showConfirm = false"
                @confirm="submit"
            />
        </AdminShell>
    `,
});
