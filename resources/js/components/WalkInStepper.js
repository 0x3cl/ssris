import { defineComponent } from 'vue';

export default defineComponent({
    name: 'WalkInStepper',
    props: {
        currentStep: {
            type: Number,
            required: true,
        },
    },
    template: `
        <ol class="flex items-start" aria-label="Walk-in request steps">
            <li class="flex flex-1 items-start"><div class="flex flex-col items-center"><span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="currentStep >= 1 ? 'bg-[#00aeef] text-white shadow-lg shadow-sky-200' : 'bg-slate-100 text-slate-400'">1</span><span class="mt-2 whitespace-nowrap text-xs font-medium" :class="currentStep >= 1 ? 'text-[#008dcc]' : 'text-slate-400'">Choose service</span></div><span class="mt-3 h-1 flex-1 rounded-full" :class="currentStep >= 2 ? 'bg-[#00aeef]' : 'bg-slate-100'"></span></li>
            <li class="flex flex-1 items-start"><div class="flex flex-col items-center"><span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="currentStep >= 2 ? 'bg-[#00aeef] text-white shadow-lg shadow-sky-200' : 'bg-slate-100 text-slate-400'">2</span><span class="mt-2 whitespace-nowrap text-xs font-medium" :class="currentStep >= 2 ? 'text-[#008dcc]' : 'text-slate-400'">Client details</span></div><span class="mt-3 h-1 flex-1 rounded-full" :class="currentStep === 3 ? 'bg-[#00aeef]' : 'bg-slate-100'"></span></li>
            <li class="flex flex-col items-center"><span class="flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold" :class="currentStep === 3 ? 'bg-[#00aeef] text-white shadow-lg shadow-sky-200' : 'bg-slate-100 text-slate-400'">3</span><span class="mt-2 whitespace-nowrap text-xs font-medium" :class="currentStep === 3 ? 'text-[#008dcc]' : 'text-slate-400'">Review</span></li>
        </ol>
    `,
});
