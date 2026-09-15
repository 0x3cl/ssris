import { defineComponent } from 'vue';

export default defineComponent({
    name: 'SourceModal',
    props: {
        illustrations: { type: Object, required: true },
        open: { type: Boolean, default: false },
        sources: { type: Array, required: true },
    },
    emits: ['close', 'select'],
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-20 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="source-title">
                <div class="w-full max-w-4xl rounded-3xl bg-white p-7 shadow-2xl sm:p-10">
                    <button type="button" class="float-right cursor-pointer text-xl text-slate-400 hover:text-slate-700" aria-label="Close" @click="$emit('close')">×</button>
                    <h2 id="source-title" class="text-2xl font-semibold text-slate-900">How did you hear about us?</h2>
                    <p class="mt-2 text-slate-600">Choose the source that best matches your answer.</p>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <button v-for="source in sources" :key="source.value" type="button" class="cursor-pointer rounded-2xl border border-slate-200 p-4 text-left transition hover:-translate-y-1 hover:border-[#00aeef] hover:shadow-lg" @click="$emit('select', source.value)">
                            <div class="flex h-24 items-center justify-center rounded-xl bg-sky-50 p-3"><img :src="illustrations[source.value]" :alt="source.label + ' illustration'" class="h-full w-full object-contain"></div>
                            <span class="mt-3 block font-semibold text-slate-900">{{ source.label }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    `,
});
