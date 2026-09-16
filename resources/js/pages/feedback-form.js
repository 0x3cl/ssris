import { Head, useForm } from '@inertiajs/vue3';
import { computed, defineComponent, ref } from 'vue';
import ConfirmActionModal from '../components/ConfirmActionModal';

const AGE_BRACKETS = ['less than 20 yrs old', '21-30 yrs old', '31-50 yrs old', '51-59 yrs old', '60 yrs old and above'];

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

        const today = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        const ageBrackets = AGE_BRACKETS;

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

        return { ageBrackets, allItemsRated, confirmSubmit, form, populate, showConfirm, submit, tableRows, today };
    },
    template: `
        <Head title="Customer Satisfaction Feedback" />
        <main class="min-h-screen bg-slate-100 px-4 py-10 text-slate-900 sm:px-8">
            <section class="mx-auto max-w-[1300px] bg-white p-6 shadow-sm sm:p-10">
                <header class="relative pb-5">
                    <p class="absolute top-0 right-0 text-right text-[10px] leading-tight">TSD Form No. 008<br>Rev 2/31-10-23</p>
                    <div class="flex items-center justify-center gap-6">
                        <img src="/assets/ptri-logo.jpg" alt="PTRI logo" class="h-16 w-16 shrink-0 object-contain" />
                        <div class="text-center">
                            <p class="text-base font-bold uppercase">Philippine Textile Research Institute</p>
                            <p class="mt-1 text-sm font-semibold">Technical Services Division</p>
                            <p class="mt-0.5 text-xs">Gen. Santos Ave., Bicutan, Taguig City</p>
                        </div>
                    </div>
                    <div class="mt-3 border-t-2 border-slate-900"></div>
                    <h1 class="mt-3 text-center text-xl font-bold uppercase tracking-[0.15em]">Customer Satisfaction Feedback</h1>
                    <div class="mt-3 border-t-2 border-slate-900"></div>
                </header>

                <section class="mt-7 text-sm">
                    <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                        <p><span class="font-bold">PSR No.</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.reference_no }}</span></p>
                        <p><span class="font-bold">Date:</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ today }}</span></p>
                        <p class="sm:col-span-2"><span class="font-bold">Customer's Name</span> <span class="italic">(optional)</span>: <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.fullname }}</span> <span class="ml-4 font-bold">Company/School:</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.company_or_school || '—' }}</span></p>
                        <p class="sm:col-span-2"><span class="font-bold">Address:</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.address }}</span> <span class="ml-4 font-bold">Gender:</span> <span class="ml-2 inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900 align-text-bottom" :class="{ 'bg-slate-900': client.gender?.toLowerCase() === 'male' }"></span> Male</span> <span class="ml-3 inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900 align-text-bottom" :class="{ 'bg-slate-900': client.gender?.toLowerCase() === 'female' }"></span> Female</span></p>
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs sm:col-span-2"><span class="text-sm font-bold">Age:</span> <span v-for="range in ageBrackets" :key="range" class="inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900" :class="{ 'bg-slate-900': client.age_bracket === range }"></span>{{ range }}</span></p>
                        <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs sm:col-span-2"><span class="text-sm font-bold">Type of Service:</span> <span v-for="service in ['Spinning', 'Weaving', 'Finishing']" :key="service" class="inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900"></span>{{ service }}</span></p>
                    </div>
                </section>

                <p class="mt-6 text-sm italic leading-6 text-slate-700">We value your opinion! Please rate your experience with us, 5 being the highest. Thank you!</p>

                <form class="mt-5" @submit.prevent="confirmSubmit">
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed border-collapse text-left text-sm">
                            <colgroup>
                                <col class="w-[15%]">
                                <col class="w-[28%]">
                                <col v-for="rating in ratings" :key="rating.id" :style="{ width: (57 / Math.max(ratings.length, 1)).toFixed(2) + '%' }">
                            </colgroup>
                            <thead>
                                <tr class="bg-white text-slate-900">
                                    <th class="border border-slate-900 px-3 py-3 text-sm font-bold align-top">Dimension</th>
                                    <th class="border border-slate-900 px-3 py-3 text-sm font-bold align-top">Description</th>
                                    <th v-for="rating in ratings" :key="rating.id" class="border border-slate-900 px-2 py-3 text-center align-top text-xs leading-tight font-bold"><span class="font-bold">{{ rating.value }}</span><span class="block font-medium normal-case">{{ rating.name }}</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in tableRows" :key="row.itemId">
                                    <td v-if="row.isFirstInDimension" :rowspan="row.rowSpan" class="border border-slate-900 px-3 py-3 align-top text-sm font-bold break-words uppercase tracking-wide text-slate-900">{{ row.dimensionName }}</td>
                                    <td class="border border-slate-900 px-3 py-3 text-sm leading-6 text-slate-700">{{ row.description }}</td>
                                    <td v-for="rating in ratings" :key="rating.id" class="border border-slate-900 px-2 py-3 text-center">
                                        <input type="radio" :name="'item-' + row.itemId" :value="rating.value" v-model="form.ratings[row.itemId]" class="h-4 w-4 appearance-none border border-slate-900 checked:bg-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400" :aria-label="rating.name" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <section v-if="questions.length" class="mt-8 space-y-5">
                        <div v-for="question in questions" :key="question.id">
                            <label class="block">
                                <span class="text-sm font-semibold text-slate-900">{{ question.name }}</span>
                                <textarea v-model="form.answers[question.id]" rows="3" class="mt-2 w-full border border-slate-900 px-4 py-3 text-sm outline-none focus:ring-4 focus:ring-sky-100"></textarea>
                            </label>
                        </div>
                    </section>

                    <p v-if="!allItemsRated" class="mt-5 text-sm font-semibold text-amber-600">Please rate every item above before submitting.</p>
                    <p v-if="form.errors.ratings" class="mt-2 text-sm font-semibold text-rose-600">{{ form.errors.ratings }}</p>

                    <div class="mt-5 flex justify-end border-t border-slate-300 pt-5">
                        <button type="submit" :disabled="form.processing || !allItemsRated" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-6 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>{{ form.processing ? 'Submitting…' : 'Submit feedback' }}
                        </button>
                    </div>
                </form>

                <footer class="mt-10 border-t border-slate-300 pt-5 text-center text-xs text-slate-500">Thank you for taking the time to share your feedback.</footer>
            </section>
            <button v-if="isLocal" type="button" class="fixed bottom-6 right-6 inline-flex items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-2xl transition hover:bg-slate-700" @click="populate">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>Populate
            </button>
        </main>
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
