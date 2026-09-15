import { computed, defineComponent } from 'vue';

const tones = {
    primary: { icon: 'bg-sky-100 text-[#07559e]', button: 'bg-[#00aeef] hover:bg-[#008dcc]' },
    danger: { icon: 'bg-rose-100 text-rose-600', button: 'bg-rose-600 hover:bg-rose-700' },
};

export default defineComponent({
    name: 'ConfirmActionModal',
    props: {
        cancelLabel: { type: String, default: 'Cancel' },
        confirmLabel: { type: String, default: 'Confirm' },
        icon: { type: String, default: 'fa-solid fa-circle-question' },
        message: { type: String, default: '' },
        open: { type: Boolean, default: false },
        processing: { type: Boolean, default: false },
        title: { type: String, required: true },
        tone: { type: String, default: 'primary' },
    },
    emits: ['close', 'confirm'],
    setup(props) {
        const toneClasses = computed(() => tones[props.tone] ?? tones.primary);

        return { toneClasses };
    },
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="confirm-action-title">
                <section class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl sm:p-9">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full text-2xl" :class="toneClasses.icon">
                        <i :class="icon" aria-hidden="true"></i>
                    </div>
                    <h2 id="confirm-action-title" class="mt-5 text-2xl font-bold text-slate-900">{{ title }}</h2>
                    <p v-if="message" class="mt-2 leading-7 text-slate-600">{{ message }}</p>
                    <div class="mt-7 flex flex-wrap justify-end gap-3">
                        <button type="button" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50" :disabled="processing" @click="$emit('close')">
                            {{ cancelLabel }}
                        </button>
                        <button type="button" class="rounded-lg px-5 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60" :class="toneClasses.button" :disabled="processing" @click="$emit('confirm')">
                            {{ processing ? 'Please wait…' : confirmLabel }}
                        </button>
                    </div>
                </section>
            </div>
        </Transition>
    `,
});
