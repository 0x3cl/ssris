import { defineComponent } from 'vue';

export default defineComponent({
    name: 'IllustratedChoiceModal',
    props: {
        choices: { type: Array, required: true },
        open: { type: Boolean, default: false },
        title: { type: String, required: true },
    },
    emits: ['close', 'select'],
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0"><div v-if="open" class="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" :aria-label="title"><div class="w-full max-w-4xl rounded-3xl bg-white p-7 shadow-2xl sm:p-10"><button type="button" class="float-right flex h-10 w-10 items-center justify-center rounded-full text-3xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close" @click="$emit('close')">×</button><h2 class="text-2xl font-semibold text-slate-900">{{ title }}</h2><div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><button v-for="choice in choices" :key="choice.value" type="button" class="cursor-pointer rounded-2xl border border-slate-200 p-4 text-left transition hover:-translate-y-1 hover:border-[#00aeef] hover:shadow-lg" @click="$emit('select', choice.value)"><div class="flex h-24 items-center justify-center p-3"><img :src="choice.illustration" :alt="choice.label + ' illustration'" class="h-full w-full object-contain"></div><span class="mt-3 block font-semibold text-slate-900">{{ choice.label }}</span></button></div></div></div></Transition>
    `,
});
