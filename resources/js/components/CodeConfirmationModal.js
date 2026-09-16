import { computed, defineComponent, ref, watch } from 'vue';

const tones = {
    primary: { icon: 'bg-sky-100 text-[#07559e]', button: 'bg-[#00aeef] hover:bg-[#008dcc]' },
    danger: { icon: 'bg-rose-100 text-rose-600', button: 'bg-rose-600 hover:bg-rose-700' },
};

export default defineComponent({
    name: 'CodeConfirmationModal',
    emits: ['close', 'confirm'],
    props: {
        confirmLabel: { type: String, default: 'Confirm' },
        icon: { type: String, default: 'fa-solid fa-shield-halved' },
        message: { type: String, default: 'Enter the four-digit code below to confirm this action.' },
        open: { type: Boolean, default: false },
        processing: { type: Boolean, default: false },
        title: { type: String, required: true },
        tone: { type: String, default: 'primary' },
    },
    setup(props, { emit }) {
        const challenge = ref('');
        const code = ref('');
        const error = ref('');
        const loading = ref(false);

        const toneClasses = computed(() => tones[props.tone] ?? tones.primary);

        const loadChallenge = async () => {
            challenge.value = '';
            code.value = '';
            error.value = '';
            loading.value = true;
            try {
                const response = await fetch('/admin/delete-challenge', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                });
                const data = await response.json();
                if (!response.ok) throw new Error();
                challenge.value = data.challenge;
            } catch {
                error.value = 'We could not prepare the confirmation code. Please try again.';
            } finally {
                loading.value = false;
            }
        };
        const confirm = async () => {
            if (code.value !== challenge.value) {
                await loadChallenge();
                error.value = 'The confirmation code did not match. A new code has been generated — please try again.';
                return;
            }
            emit('confirm', code.value);
        };
        watch(() => props.open, (isOpen) => { if (isOpen) loadChallenge(); });

        return { challenge, code, confirm, error, loading, toneClasses };
    },
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="code-confirmation-title">
                <section class="w-full max-w-xl rounded-3xl bg-white p-7 shadow-2xl sm:p-9">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-2xl" :class="toneClasses.icon">
                            <i :class="icon" aria-hidden="true"></i>
                        </div>
                        <button type="button" class="text-2xl leading-none text-slate-400 hover:text-slate-700" :disabled="processing" aria-label="Close" @click="$emit('close')">&times;</button>
                    </div>
                    <h2 id="code-confirmation-title" class="mt-5 text-2xl font-bold text-slate-900">{{ title }}</h2>
                    <p class="mt-2 leading-7 text-slate-600">{{ message }}</p>
                    <div class="mt-6 rounded-xl bg-sky-50 px-5 py-4 text-center">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#07559e]">Confirmation code</p>
                        <p v-if="loading" class="mt-2 text-sm text-slate-500">Preparing code…</p>
                        <p v-else class="mt-1 text-3xl font-bold tracking-[0.35em] text-[#07559e]">{{ challenge }}</p>
                    </div>
                    <label class="mt-5 block">
                        <span class="text-sm font-semibold text-slate-700">Enter confirmation code</span>
                        <input v-model="code" inputmode="numeric" maxlength="4" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-xl font-bold tracking-[0.3em] outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :disabled="loading || processing" @input="code = code.replace(/\\D/g, '')" />
                        <p v-if="error" class="mt-2 text-sm text-rose-600">{{ error }}</p>
                    </label>
                    <div class="mt-7 flex flex-wrap justify-end gap-3">
                        <button type="button" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50" :disabled="processing" @click="$emit('close')">
                            Cancel
                        </button>
                        <button type="button" class="rounded-lg px-5 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60" :class="toneClasses.button" :disabled="loading || processing || code.length !== 4" @click="confirm">
                            {{ processing ? 'Please wait…' : confirmLabel }}
                        </button>
                    </div>
                </section>
            </div>
        </Transition>
    `,
});
