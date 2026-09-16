import { Head, useForm } from '@inertiajs/vue3';
import { computed, defineComponent, ref } from 'vue';
import ConfirmActionModal from '../components/ConfirmActionModal';

export default defineComponent({
    name: 'PublicFeedbackForm',
    components: { ConfirmActionModal },
    props: {
        token: { type: String, required: true },
        isLocal: { type: Boolean, default: false },
        client: { type: Object, required: true },
        dimensions: { type: Array, required: true },
        ratings: { type: Array, required: true },
        questions: { type: Array, required: true },
    },
    setup(props) {
        const form = useForm({ ratings: {}, answers: {} });
        const showConfirm = ref(false);

        const allItemsRated = computed(() => props.dimensions.every((dimension) => dimension.items.every((item) => form.ratings[item.id])));

        const tableRows = computed(() => props.dimensions.flatMap((dimension) => dimension.items.map((item, index) => ({
            itemId: item.id,
            description: item.description,
            dimensionName: dimension.name,
            isFirstInDimension: index === 0,
            rowSpan: dimension.items.length,
        }))));

        const confirmSubmit = () => {
            showConfirm.value = true;
        };

        const submit = () => {
            form.post(`/feedback/${props.token}`, {
                onFinish: () => {
                    showConfirm.value = false;
                },
            });
        };

        const populate = () => {
            props.dimensions.forEach((dimension) => {
                dimension.items.forEach((item) => {
                    const rating = props.ratings[Math.floor(Math.random() * props.ratings.length)];
                    form.ratings[item.id] = rating.value;
                });
            });
            props.questions.forEach((question) => {
                form.answers[question.id] = 'This is dummy test text generated for testing purposes.';
            });
        };

        return { allItemsRated, confirmSubmit, form, populate, showConfirm, submit, tableRows };
    },
    template: `
        <Head title="Customer Satisfaction Feedback" />
        <div class="min-h-screen overflow-x-hidden bg-slate-50 px-4 py-10 sm:px-6">
            <div class="mx-auto w-full max-w-7xl">
                <header class="border-b-4 border-[#00aeef] bg-white p-6 shadow-sm sm:p-8">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Philippine Textile Research Institute</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-900 sm:text-3xl">Customer Satisfaction Feedback</h1>
                    <p class="mt-2 text-slate-600">We value your opinion! Please rate your experience with us below.</p>
                </header>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-[#07559e]">Client information</h2>
                    <div class="mt-4 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <tbody>
                                <tr class="border-b border-slate-100">
                                    <th class="w-48 whitespace-nowrap px-2 py-2 font-semibold text-slate-500">Full name</th>
                                    <td class="px-2 py-2 text-slate-900">{{ client.fullname }}</td>
                                    <th class="w-48 whitespace-nowrap px-2 py-2 font-semibold text-slate-500">Type of service</th>
                                    <td class="px-2 py-2 text-slate-900">{{ client.service }}</td>
                                </tr>
                                <tr class="border-b border-slate-100">
                                    <th class="whitespace-nowrap px-2 py-2 font-semibold text-slate-500">Email</th>
                                    <td class="px-2 py-2 text-slate-900">{{ client.email }}</td>
                                    <th class="whitespace-nowrap px-2 py-2 font-semibold text-slate-500">Mobile number</th>
                                    <td class="px-2 py-2 text-slate-900">{{ client.mobile_no }}</td>
                                </tr>
                                <tr>
                                    <th class="whitespace-nowrap px-2 py-2 font-semibold text-slate-500">Company / School</th>
                                    <td class="px-2 py-2 text-slate-900">{{ client.company_or_school || '—' }}</td>
                                    <th class="whitespace-nowrap px-2 py-2 font-semibold text-slate-500">Address</th>
                                    <td class="px-2 py-2 text-slate-900">{{ client.address }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <form class="mt-6 space-y-6" @submit.prevent="confirmSubmit">
                    <section class="border border-slate-200 bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse text-left text-sm">
                                <thead>
                                    <tr class="bg-[#07559e] text-white">
                                        <th class="whitespace-nowrap border border-white/20 px-4 py-3 font-bold">Dimension</th>
                                        <th class="border border-white/20 px-4 py-3 font-bold">Description</th>
                                        <th v-for="rating in ratings" :key="rating.id" class="whitespace-nowrap border border-white/20 px-3 py-3 text-center font-bold">{{ rating.value }} {{ rating.name }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in tableRows" :key="row.itemId">
                                        <td v-if="row.isFirstInDimension" :rowspan="row.rowSpan" class="border border-slate-200 bg-slate-50 px-4 py-3 align-top font-bold uppercase text-[#07559e]">{{ row.dimensionName }}</td>
                                        <td class="border border-slate-200 px-4 py-3 text-slate-700">{{ row.description }}</td>
                                        <td v-for="rating in ratings" :key="rating.id" class="border border-slate-200 px-3 py-3 text-center">
                                            <input type="radio" :name="'item-' + row.itemId" :value="rating.value" v-model="form.ratings[row.itemId]" class="h-4 w-4 accent-[#00aeef]" :aria-label="rating.name" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section v-if="questions.length" class="space-y-5 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <div v-for="question in questions" :key="question.id">
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-700">{{ question.name }}</span>
                                <textarea v-model="form.answers[question.id]" rows="3" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100"></textarea>
                            </label>
                        </div>
                    </section>

                    <p v-if="!allItemsRated" class="text-sm font-semibold text-amber-600">Please rate every item above before submitting.</p>
                    <p v-if="form.errors.ratings" class="text-sm font-semibold text-rose-600">{{ form.errors.ratings }}</p>

                    <div class="flex justify-end border-t border-slate-200 pt-5">
                        <button type="submit" :disabled="form.processing || !allItemsRated" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-6 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>{{ form.processing ? 'Submitting…' : 'Submit feedback' }}
                        </button>
                    </div>
                </form>
            </div>
            <button v-if="isLocal" type="button" class="fixed bottom-6 right-6 inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-2xl transition hover:bg-slate-700" @click="populate">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>Populate
            </button>
        </div>
        <ConfirmActionModal
            :open="showConfirm"
            :processing="form.processing"
            title="Submit this feedback?"
            message="You won't be able to change your answers after submitting. Are you sure you want to submit?"
            confirm-label="Submit feedback"
            icon="fa-solid fa-paper-plane"
            @close="showConfirm = false"
            @confirm="submit"
        />
    `,
});
