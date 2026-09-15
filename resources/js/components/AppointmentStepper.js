import { defineComponent } from 'vue';

const steps = ['Booking details', 'Choose service', 'Client details', 'Review'];

export default defineComponent({
    name: 'AppointmentStepper',
    props: {
        currentStep: { type: Number, required: true },
    },
    setup() {
        return { steps };
    },
    template: `
        <ol class="flex items-start" aria-label="Appointment request steps">
            <li v-for="(step, index) in steps" :key="step" class="flex flex-1 items-start last:flex-none">
                <div class="flex flex-col items-center"><span class="flex h-11 w-11 items-center justify-center rounded-full text-base font-bold" :class="currentStep >= index + 1 ? 'bg-[#00aeef] text-white shadow-lg shadow-sky-200' : 'bg-slate-100 text-slate-400'">{{ index + 1 }}</span><span class="mt-3 whitespace-nowrap text-center text-sm font-semibold uppercase" :class="currentStep >= index + 1 ? 'text-[#008dcc]' : 'text-slate-400'">{{ step }}</span></div>
                <span v-if="index < steps.length - 1" class="mt-5 h-1.5 flex-1 rounded-full" :class="currentStep >= index + 2 ? 'bg-[#00aeef]' : 'bg-slate-100'"></span>
            </li>
        </ol>
    `,
});
