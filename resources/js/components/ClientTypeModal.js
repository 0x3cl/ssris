import { defineComponent } from 'vue';

export default defineComponent({
    name: 'ClientTypeModal',
    props: {
        clientTypes: { type: Array, required: true },
        illustrations: { type: Object, required: true },
        open: { type: Boolean, default: false },
    },
    emits: ['close', 'select'],
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="open" class="fixed inset-0 z-20 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="client-type-title">
            <div class="w-full max-w-4xl rounded-3xl bg-white p-7 shadow-2xl sm:p-10">
                <button type="button" class="float-right cursor-pointer text-xl text-slate-400 hover:text-slate-700" aria-label="Close" @click="$emit('close')">×</button>
                <h2 id="client-type-title" class="text-2xl font-semibold text-slate-900">Choose client type</h2>
                <p class="mt-2 text-slate-600">Select the option that best describes you or your organization.</p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <button v-for="type in clientTypes" :key="type.value" type="button" class="group cursor-pointer rounded-2xl border border-slate-200 p-4 text-left transition hover:-translate-y-1 hover:border-[#3d68b1] hover:shadow-lg" @click="$emit('select', type.value)">
                        <div class="flex h-24 items-center justify-center rounded-xl bg-sky-50 p-3"><img :src="illustrations[type.value]" :alt="type.label + ' illustration'" class="h-full w-full object-contain"></div>
                        <span class="mt-3 block font-semibold text-slate-900">{{ type.label }}</span>
                    </button>
                </div>
            </div>
        </div>
        </Transition>
    `,
});
