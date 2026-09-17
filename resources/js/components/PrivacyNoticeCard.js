import { defineComponent, onMounted, ref } from 'vue';

export default defineComponent({
    name: 'PrivacyNoticeCard',
    props: {
        position: { type: String, default: 'right' },
        offsetClass: { type: String, default: '' },
    },
    setup() {
        const visible = ref(false);
        const expanded = ref(false);

        onMounted(() => {
            setTimeout(() => {
                visible.value = true;
            }, 2000);
        });

        return { expanded, visible };
    },
    template: `
        <div>
            <div class="fixed bottom-8 z-30 w-56 transition-all duration-700 ease-out sm:w-64" :class="[offsetClass || (position === 'left' ? 'left-8' : 'right-8'), visible ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-10 opacity-0']">
                <div class="relative overflow-visible rounded-xl border border-slate-200 bg-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl">
                    <button type="button" class="absolute -right-3 -top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white text-xl leading-none text-slate-500 shadow-md ring-1 ring-slate-200 hover:bg-slate-100 hover:text-slate-800" aria-label="Dismiss privacy notice" @click="visible = false">×</button>
                    <button type="button" class="flex w-full flex-col overflow-hidden rounded-xl text-left focus:outline-none focus-visible:ring-4 focus-visible:ring-sky-200" @click="expanded = true">
                        <img src="/assets/privacy-notice.png" alt="" class="h-28 w-full object-cover object-top sm:h-32" />
                        <span class="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700">
                            <i class="fa-solid fa-shield-halved text-[#07559e]" aria-hidden="true"></i>Privacy Notice
                        </span>
                    </button>
                </div>
            </div>

            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="expanded" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" role="dialog" aria-modal="true" aria-label="Privacy Notice" @click.self="expanded = false">
                    <Transition appear enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                        <div class="relative max-h-[90vh] w-full max-w-3xl overflow-visible rounded-2xl bg-white p-2 shadow-2xl">
                            <button type="button" class="absolute -right-3 -top-3 flex h-9 w-9 items-center justify-center rounded-full bg-white text-2xl leading-none text-slate-500 shadow-md ring-1 ring-slate-200 hover:bg-slate-100 hover:text-slate-800" aria-label="Close" @click="expanded = false">×</button>
                            <div class="max-h-[90vh] w-full overflow-y-auto rounded-xl"><img src="/assets/privacy-notice.png" alt="Privacy Notice" class="w-full object-contain" /></div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </div>
    `,
});
