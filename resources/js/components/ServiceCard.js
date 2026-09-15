import { defineComponent } from 'vue';

export default defineComponent({
    name: 'ServiceCard',
    props: {
        service: {
            type: Object,
            required: true,
        },
        illustration: {
            type: String,
            required: true,
        },
        selected: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['select'],
    template: `
        <button type="button" class="group cursor-pointer rounded-2xl border p-4 text-left shadow-sm transition duration-200 focus-visible:ring-4 focus-visible:ring-sky-200 focus-visible:outline-none" :class="selected ? 'border-[#3d68b1] bg-sky-50 ring-1 ring-[#3d68b1] shadow-sky-100' : 'border-slate-200 bg-white hover:-translate-y-1 hover:border-[#3d68b1] hover:shadow-lg'" @click="$emit('select', service.value)">
            <div class="flex aspect-[4/3] items-center justify-center p-3"><img :src="illustration" :alt="service.label + ' illustration'" class="h-full w-full object-contain"></div>
            <span class="mt-4 block font-bold leading-snug tracking-wide text-slate-900 uppercase">{{ service.label }}</span>
            <span class="mt-2 block text-sm font-medium text-[#008dcc] opacity-0 transition group-hover:opacity-100">Select service →</span>
        </button>
    `,
});
