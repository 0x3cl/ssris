import { defineComponent } from 'vue';

export default defineComponent({
    name: 'EmailLookupModal',
    props: {
        email: { type: String, required: true },
        error: { type: String, default: '' },
        loading: { type: Boolean, default: false },
        open: { type: Boolean, default: false },
    },
    emits: ['close', 'submit', 'update:email'],
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="open" class="fixed inset-0 z-20 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="email-modal-title">
            <form class="w-full max-w-lg rounded-3xl bg-white p-7 shadow-2xl sm:p-8" @submit.prevent="$emit('submit')">
                <button type="button" class="float-right flex h-10 w-10 items-center justify-center rounded-full text-3xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close" @click="$emit('close')">×</button>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-sky-100 text-xl text-[#008dcc]" aria-hidden="true">✉</div>
                <h2 id="email-modal-title" class="mt-5 text-2xl font-semibold text-slate-900">Before you continue</h2>
                <p class="mt-2 text-slate-600">Enter your email so we can find your existing client record.</p>
                <label class="mt-6 block text-sm font-medium text-slate-700" for="modal-client-email">Email address</label>
                <input id="modal-client-email" :value="email" autocomplete="email" placeholder="you@example.com" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" @input="$emit('update:email', $event.target.value)">
                <p v-if="error" class="mt-2 text-sm text-rose-700" role="alert">{{ error }}</p>
                <button type="submit" :disabled="loading" class="mt-6 w-full cursor-pointer rounded-xl bg-[#00aeef] px-5 py-3 font-semibold text-white hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-400">{{ loading ? 'Checking…' : 'Continue' }}</button>
            </form>
        </div>
        </Transition>
    `,
});
