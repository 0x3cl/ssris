import { Head } from '@inertiajs/vue3';
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'AdminFeedbackVisualization',
    components: { Head },
    props: {
        dimensions: { type: Array, required: true },
        questions: { type: Array, required: true },
        ratings: { type: Array, required: true },
    },
    setup() {
        return { feedbackPdfUrl: '/admin/feedback-builder/visualize/pdf' };
    },
    template: `
        <Head title="Customer Satisfaction Feedback" />
        <main class="min-h-screen bg-slate-100 p-5 text-slate-900 sm:p-8 print:bg-white print:p-0">
            <style media="print">
                @page { margin: 0; }
                #app > header, #app > footer, #app > div > header, #app > div > footer { display: none !important; }
                body * { visibility: hidden; }
                #feedback-print-form, #feedback-print-form * { visibility: visible; }
                #feedback-print-form { position: absolute; top: 0; left: 0; width: 100%; max-width: none; margin: 0; padding: 10mm !important; }
                .feedback-print-question, #feedback-print-form footer { break-inside: avoid-page; page-break-inside: avoid; }
            </style>
            <section id="feedback-print-form" class="mx-auto max-w-[1600px] bg-white p-6 shadow-sm sm:p-10 print:max-w-none print:p-0 print:shadow-none">
                <div class="flex flex-wrap items-start justify-between gap-5 border-b-2 border-[#07559e] pb-6 print:hidden">
                    <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Feedback Builder</p><h1 class="mt-1 text-2xl font-bold text-slate-900">Customer Satisfaction Feedback</h1><p class="mt-2 text-sm text-slate-600">Preview of the client survey form.</p></div>
                    <a :href="feedbackPdfUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-[#07559e] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#043d78]"><i class="fa-solid fa-print" aria-hidden="true"></i>Print PDF</a>
                </div>
                <header class="hidden border-b-2 border-[#07559e] pb-5 print:block"><p class="text-center text-xs font-bold uppercase tracking-[0.2em] text-[#07559e]">Philippine Textile Research Institute</p><h1 class="mt-2 text-center text-2xl font-bold uppercase">Customer Satisfaction Feedback</h1></header>
                <section class="mt-7 border border-slate-400 p-4 text-sm print:mt-5">
                    <div class="grid gap-x-8 gap-y-4 sm:grid-cols-2"><p><span class="font-bold">PSR No.:</span> <span class="inline-block w-64 border-b border-slate-900"></span></p><p><span class="font-bold">Date:</span> <span class="inline-block w-64 border-b border-slate-900"></span></p><p class="sm:col-span-2"><span class="font-bold">Customer's Name</span> <span class="italic">(optional)</span>: <span class="inline-block w-2/3 border-b border-slate-900"></span> <span class="ml-4 font-bold">Company/School:</span> <span class="inline-block w-48 border-b border-slate-900"></span></p><p class="sm:col-span-2"><span class="font-bold">Address:</span> <span class="inline-block w-2/3 border-b border-slate-900"></span> <span class="ml-4 font-bold">Gender:</span> <span class="ml-2 inline-block h-4 w-4 border border-slate-900 align-text-bottom"></span> Male <span class="ml-3 inline-block h-4 w-4 border border-slate-900 align-text-bottom"></span> Female</p><p class="sm:col-span-2"><span class="font-bold">Age:</span> <span v-for="range in ['Less than 20 years old', '21–30 years old', '31–40 years old', '41–60 years old', 'Above 60 years old']" :key="range" class="ml-4 inline-flex items-center gap-1"><span class="inline-block h-4 w-4 border border-slate-900"></span>{{ range }}</span></p><p class="sm:col-span-2"><span class="font-bold">Type of Service:</span> <span v-for="service in ['R&D Services', 'Laboratory Services', 'Textile Processing']" :key="service" class="ml-5 inline-flex items-center gap-1"><span class="inline-block h-4 w-4 border border-slate-900"></span>{{ service }}</span></p></div>
                </section>
                <p class="mt-6 text-sm leading-6 text-slate-700">We value your opinion. Please rate each statement by marking one response, with the highest rating indicating your highest level of satisfaction.</p>
                <div class="mt-5"><table class="w-full border-collapse text-left text-sm"><thead><tr class="bg-[#07559e] text-white"><th class="w-40 border border-[#07559e] px-3 py-3 font-bold">Dimension</th><th class="min-w-80 border border-[#07559e] px-3 py-3 font-bold">Description</th><th v-for="rating in ratings" :key="rating.id" class="min-w-[7.5rem] whitespace-nowrap border border-[#07559e] px-3 py-3 text-center text-xs font-bold"><span class="font-bold">{{ rating.value }}</span><span class="ml-1 font-medium normal-case">{{ rating.name }}</span></th><th v-if="ratings.length === 0" class="border border-[#07559e] px-3 py-3 text-center">Rating</th></tr></thead><tbody><template v-for="dimension in dimensions" :key="dimension.id"><tr v-for="(item, itemIndex) in dimension.items" :key="item.id"><td v-if="itemIndex === 0" :rowspan="dimension.items.length" class="border border-slate-300 bg-slate-50 px-3 py-3 align-top font-bold uppercase tracking-wide text-[#07559e]">{{ dimension.name }}</td><td class="border border-slate-300 px-3 py-3 leading-5 text-slate-700">{{ item.description }}</td><td v-for="rating in ratings" :key="rating.id" class="border border-slate-300 px-2 py-3 text-center"><span class="inline-block h-4 w-4 rounded-full border border-slate-500" :aria-label="rating.name"></span></td><td v-if="ratings.length === 0" class="border border-slate-300 px-3 py-3"></td></tr><tr v-if="dimension.items.length === 0"><td class="border border-slate-300 bg-slate-50 px-3 py-3 font-bold uppercase tracking-wide text-[#07559e]">{{ dimension.name }}</td><td :colspan="Math.max(ratings.length, 1) + 1" class="border border-slate-300 px-3 py-3 italic text-slate-400">No descriptions configured.</td></tr></template><tr v-if="dimensions.length === 0"><td :colspan="Math.max(ratings.length, 1) + 2" class="border border-slate-300 px-3 py-8 text-center text-slate-500">No survey dimensions configured.</td></tr></tbody></table></div>
                <section v-if="questions.length" class="mt-8 space-y-5"><div v-for="question in questions" :key="question.id" class="feedback-print-question min-h-32 border border-slate-400 px-4 py-3"><p class="text-base font-semibold italic text-slate-800">{{ question.name }}:</p></div></section>
                <footer class="mt-10 border-t border-slate-300 pt-5 text-center text-xs text-slate-500">Thank you for taking the time to share your feedback.</footer>
            </section>
        </main>
    `,
});
