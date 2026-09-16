import { Head } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';

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
    },
    setup(props) {
        const tableRows = computed(() => props.dimensions.flatMap((dimension) => dimension.items.map((item, index) => ({
            itemId: item.id,
            description: item.description,
            dimensionName: dimension.name,
            isFirstInDimension: index === 0,
            rowSpan: dimension.items.length,
        }))));

        return { tableRows };
    },
    template: `
        <Head title="Feedback response" />
        <AdminShell active="requests" title="Feedback Response">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Customer Satisfaction Feedback</h2>
                        <p class="mt-1 text-slate-600">Submitted on {{ submittedAt }}</p>
                    </div>
                    <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" onclick="history.back()">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to request
                    </button>
                </div>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-[#07559e]">Client information</h3>
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

                <section class="mt-6 border border-slate-200 bg-white shadow-sm">
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
                                        <input type="radio" disabled :checked="String(responseRatings[row.itemId]) === String(rating.value)" class="h-4 w-4 accent-[#00aeef]" :aria-label="rating.name" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section v-if="questions.length" class="mt-6 space-y-5 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div v-for="question in questions" :key="question.id">
                        <p class="text-sm font-semibold text-slate-700">{{ question.name }}</p>
                        <p class="mt-2 whitespace-pre-wrap rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">{{ responseAnswers[question.id] || '—' }}</p>
                    </div>
                </section>
            </section>
        </AdminShell>
    `,
});
