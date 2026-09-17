import { Head } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';

const AGE_BRACKETS = ['less than 20 yrs old', '21-30 yrs old', '31-50 yrs old', '51-59 yrs old', '60 yrs old and above'];

const ageBracket = (age) => {
    if (age === null || age === undefined) {
        return null;
    }
    if (age <= 20) {
        return 'less than 20 yrs old';
    }
    if (age <= 30) {
        return '21-30 yrs old';
    }
    if (age <= 50) {
        return '31-50 yrs old';
    }
    if (age <= 59) {
        return '51-59 yrs old';
    }
    return '60 yrs old and above';
};

export default defineComponent({
    name: 'AdminFeedbackResponse',
    components: { AdminShell, Head },
    props: {
        client: { type: Object, required: true },
        submittedAt: { type: String, required: true },
        dimensions: { type: Array, required: true },
        ratings: { type: Array, required: true },
        questions: { type: Array, required: true },
        responseRatings: { type: Object, required: true },
        responseAnswers: { type: Object, required: true },
        showEmoji: { type: Boolean, default: false },
        pdfUrl: { type: String, required: true },
    },
    setup(props) {
        const tableRows = computed(() => props.dimensions.flatMap((dimension) => dimension.items.map((item, index) => ({
            itemId: item.id,
            description: item.description,
            dimensionName: dimension.name,
            isFirstInDimension: index === 0,
            rowSpan: dimension.items.length,
        }))));

        const clientAgeBracket = computed(() => ageBracket(props.client.age));

        return { ageBrackets: AGE_BRACKETS, clientAgeBracket, tableRows };
    },
    template: `
        <Head title="Feedback response" />
        <AdminShell active="requests" title="Feedback Response">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                    <p class="mt-1 text-slate-600">Submitted on {{ submittedAt }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <a :href="pdfUrl" class="inline-flex items-center gap-2 rounded-lg bg-[#07559e] px-4 py-2 text-sm font-bold text-white hover:bg-[#043d78]">
                        <i class="fa-solid fa-download" aria-hidden="true"></i>Download PDF
                    </a>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" onclick="history.back()">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to request
                    </button>
                </div>
            </div>
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
                        <p><span class="font-bold">Date:</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ submittedAt }}</span></p>
                        <p class="sm:col-span-2"><span class="font-bold">Customer's Name</span> <span class="italic">(optional)</span>: <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.fullname }}</span> <span class="ml-4 font-bold">Company/School:</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.company_or_school || '—' }}</span></p>
                        <p class="sm:col-span-2"><span class="font-bold">Address:</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.address }}</span> <span class="ml-4 font-bold">Gender:</span> <span class="ml-2 inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900 align-text-bottom" :class="{ 'bg-slate-900': client.gender?.toLowerCase() === 'male' }"></span> Male</span> <span class="ml-3 inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900 align-text-bottom" :class="{ 'bg-slate-900': client.gender?.toLowerCase() === 'female' }"></span> Female</span></p>
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs sm:col-span-2"><span class="text-sm font-bold">Age:</span> <span v-for="range in ageBrackets" :key="range" class="inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900" :class="{ 'bg-slate-900': clientAgeBracket === range }"></span>{{ range }}</span></p>
                        <p class="sm:col-span-2"><span class="font-bold">Type of Service:</span> <span class="ml-1 inline-block border-b border-slate-900 px-1">{{ client.service }}</span></p>
                    </div>
                </section>

                <p class="mt-6 text-sm italic leading-6 text-slate-700">We value your opinion! Please rate your experience with us, 5 being the highest. Thank you!</p>

                <div class="mt-5 overflow-x-auto">
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
                                <th v-for="rating in ratings" :key="rating.id" class="border border-slate-900 px-2 py-3 text-center align-top text-xs leading-tight font-bold"><span v-if="showEmoji && rating.emoji" class="block text-base leading-normal">{{ rating.emoji }}</span><span v-else class="font-bold">{{ rating.value }}</span><span class="block font-medium normal-case">{{ rating.name }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in tableRows" :key="row.itemId">
                                <td v-if="row.isFirstInDimension" :rowspan="row.rowSpan" class="border border-slate-900 px-3 py-3 align-top text-sm font-bold break-words uppercase tracking-wide text-slate-900">{{ row.dimensionName }}</td>
                                <td class="border border-slate-900 px-3 py-3 text-sm leading-6 text-slate-700">{{ row.description }}</td>
                                <td v-for="rating in ratings" :key="rating.id" class="border border-slate-900 px-2 py-3 text-center">
                                    <input type="radio" disabled :checked="String(responseRatings[row.itemId]) === String(rating.value)" class="h-4 w-4 appearance-none border border-slate-900 checked:bg-slate-900" :aria-label="rating.name" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <section v-if="questions.length" class="mt-8 space-y-5">
                    <div v-for="question in questions" :key="question.id">
                        <p class="text-sm font-semibold text-slate-900">{{ question.name }}</p>
                        <p class="mt-2 whitespace-pre-wrap border border-slate-900 px-4 py-3 text-sm text-slate-700">{{ responseAnswers[question.id] || '—' }}</p>
                    </div>
                </section>

                <footer class="mt-10 border-t border-slate-300 pt-5 text-center text-xs text-slate-500">Thank you for taking the time to share your feedback.</footer>
            </section>
        </AdminShell>
    `,
});
