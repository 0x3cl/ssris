import { defineComponent } from 'vue';

export default defineComponent({
    name: 'LoadingModal',
    props: {
        message: { type: String, default: 'Submitting your request. This will only take a moment.' },
        open: { type: Boolean, default: false },
        title: { type: String, default: 'Please wait' },
    },
    template: `
        <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/45 p-4" role="alert" aria-live="assertive" aria-busy="true">
                <section class="w-full max-w-sm rounded-3xl bg-white p-8 text-center shadow-2xl">
                    <div class="mx-auto h-14 w-14 animate-spin rounded-full border-4 border-sky-100 border-t-[#00aeef]" aria-hidden="true"></div>
                    <h2 class="mt-6 text-xl font-bold text-slate-900">{{ title }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ message }}</p>
                </section>
            </div>
        </Transition>
    `,
});
