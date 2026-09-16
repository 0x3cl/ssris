import { computed, defineComponent } from 'vue';

const tones = {
    success: 'bg-emerald-100 text-emerald-600',
    info: 'bg-sky-100 text-[#07559e]',
    warning: 'bg-amber-100 text-amber-600',
    error: 'bg-rose-100 text-rose-600',
};

export default defineComponent({
    name: 'FeedbackModal',
    props: {
        actionHref: { type: String, default: '' },
        actionIcon: { type: String, default: 'fa-solid fa-arrow-right' },
        actionLabel: { type: String, default: '' },
        closeLabel: { type: String, default: 'Close' },
        icon: { type: String, default: 'fa-solid fa-circle-check' },
        message: { type: String, required: true },
        open: { type: Boolean, default: false },
        title: { type: String, required: true },
        tone: { type: String, default: 'success' },
    },
    emits: ['close'],
    setup(props) {
        const toneClass = computed(() => tones[props.tone] ?? tones.info);

        return { toneClass };
    },
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="feedback-modal-title">
                <section class="w-full max-w-xl rounded-3xl bg-white p-8 text-center shadow-2xl sm:p-10">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full text-3xl" :class="toneClass" aria-hidden="true"><i :class="icon"></i></div>
                    <h2 id="feedback-modal-title" class="mt-6 text-2xl font-bold text-slate-900 sm:text-3xl">{{ title }}</h2>
                    <p class="mt-3 whitespace-pre-line break-words text-base leading-7 text-slate-600">{{ message }}</p>
                    <a v-if="actionHref && actionLabel" :href="actionHref" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-6 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]"><i :class="actionIcon" aria-hidden="true"></i>{{ actionLabel }}</a>
                    <button v-else type="button" class="mt-8 inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-6 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="$emit('close')">{{ closeLabel }}</button>
                </section>
            </div>
        </Transition>
    `,
});
