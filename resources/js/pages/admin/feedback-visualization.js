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
            <div class="mx-auto mb-4 flex max-w-[1300px] justify-end print:hidden">
                <a :href="feedbackPdfUrl" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-[#07559e] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#043d78]"><i class="fa-solid fa-print" aria-hidden="true"></i>Print PDF</a>
            </div>
            <section id="feedback-print-form" class="mx-auto max-w-[1300px] bg-white p-6 shadow-sm sm:p-10 print:max-w-none print:p-0 print:shadow-none">
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
                <section class="mt-7 text-sm print:mt-5">
                    <div class="grid gap-x-6 gap-y-4 sm:grid-cols-2"><p><span class="font-bold">PSR No.</span> <span class="ml-1 inline-block w-40 border-b border-slate-900"></span></p><p><span class="font-bold">Date:</span> <span class="ml-1 inline-block w-40 border-b border-slate-900"></span></p><p class="sm:col-span-2"><span class="font-bold">Customer's Name</span> <span class="italic">(optional)</span>: <span class="ml-1 inline-block w-64 border-b border-slate-900"></span> <span class="ml-4 font-bold">Company/School:</span> <span class="ml-1 inline-block w-36 border-b border-slate-900"></span></p><p class="sm:col-span-2"><span class="font-bold">Address:</span> <span class="ml-1 inline-block w-64 border-b border-slate-900"></span> <span class="ml-4 font-bold">Gender:</span> <span class="ml-2 inline-block h-3.5 w-3.5 border border-slate-900 align-text-bottom"></span> Male <span class="ml-3 inline-block h-3.5 w-3.5 border border-slate-900 align-text-bottom"></span> Female</p><p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs sm:col-span-2"><span class="text-sm font-bold">Age:</span> <span v-for="range in ['less than 20 yrs old', '21-30 yrs old', '31-50 yrs old', '51-59 yrs old', '60 yrs old and above']" :key="range" class="inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900"></span>{{ range }}</span></p><p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs sm:col-span-2"><span class="text-sm font-bold">Type of Service:</span> <span v-for="service in ['Spinning', 'Weaving', 'Finishing']" :key="service" class="inline-flex items-center gap-1"><span class="inline-block h-3.5 w-3.5 border border-slate-900"></span>{{ service }}</span></p></div>
                </section>
                <p class="mt-6 text-sm italic leading-6 text-slate-700">We value your opinion! Please rate your experience with us, 5 being the highest. Thank you!</p>
                <div class="mt-5 overflow-x-auto"><table class="w-full table-fixed border-collapse text-left text-sm"><colgroup><col class="w-[15%]"><col class="w-[28%]"><col v-for="rating in (ratings.length ? ratings : [{ id: 'na' }])" :key="rating.id" class="w-[9.5%]"></colgroup><thead><tr class="bg-white text-slate-900"><th class="border border-slate-900 px-3 py-3 text-sm font-bold align-top">Dimension</th><th class="border border-slate-900 px-3 py-3 text-sm font-bold align-top">Description</th><th v-for="rating in ratings" :key="rating.id" class="border border-slate-900 px-2 py-3 text-center align-top text-xs leading-tight font-bold"><span class="font-bold">{{ rating.value }}</span><span class="block font-medium normal-case">{{ rating.name }}</span></th><th v-if="ratings.length === 0" class="border border-slate-900 px-2 py-3 text-center text-xs">Rating</th></tr></thead><tbody><template v-for="dimension in dimensions" :key="dimension.id"><tr v-for="(item, itemIndex) in dimension.items" :key="item.id"><td v-if="itemIndex === 0" :rowspan="dimension.items.length" class="border border-slate-900 px-3 py-3 align-top text-sm font-bold break-words uppercase tracking-wide text-slate-900">{{ dimension.name }}</td><td class="border border-slate-900 px-3 py-3 text-sm leading-6 text-slate-700">{{ item.description }}</td><td v-for="rating in ratings" :key="rating.id" class="border border-slate-900 px-2 py-3 text-center"><span class="inline-block h-4 w-4 border border-slate-900" :aria-label="rating.name"></span></td><td v-if="ratings.length === 0" class="border border-slate-900 px-2 py-3"></td></tr><tr v-if="dimension.items.length === 0"><td class="border border-slate-900 px-3 py-3 text-sm font-bold break-words uppercase tracking-wide text-slate-900">{{ dimension.name }}</td><td :colspan="Math.max(ratings.length, 1) + 1" class="border border-slate-900 px-3 py-3 text-sm italic text-slate-400">No descriptions configured.</td></tr></template><tr v-if="dimensions.length === 0"><td :colspan="Math.max(ratings.length, 1) + 2" class="border border-slate-900 px-3 py-8 text-center text-sm text-slate-500">No survey dimensions configured.</td></tr></tbody></table></div>
                <section v-if="questions.length" class="mt-8 space-y-5"><div v-for="question in questions" :key="question.id" class="feedback-print-question min-h-32 border border-slate-400 px-4 py-3"><p class="text-base font-semibold italic text-slate-800">{{ question.name }}:</p></div></section>
                <footer class="mt-10 border-t border-slate-300 pt-5 text-center text-xs text-slate-500">Thank you for taking the time to share your feedback.</footer>
            </section>
        </main>
    `,
});
