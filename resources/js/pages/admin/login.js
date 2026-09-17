import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';

export default defineComponent({
    name: 'AdminLogin',
    components: { Head, Link },
    setup() {
        const form = useForm({ username: '', password: '' });
        const submit = () => form.post('/admin/login');
        const page = usePage();
        const success = computed(() => page.props.flash?.success);

        return { form, submit, success };
    },
    template: `
        <Head title="Admin login" />
        <main class="bg-slate-50 px-5 py-10 sm:px-8 sm:py-14 lg:px-12 lg:py-16">
            <section class="mx-auto grid w-full max-w-[1500px] gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-stretch xl:gap-14">
                <aside class="min-h-72 overflow-hidden rounded-2xl bg-sky-50 lg:min-h-[560px]"><img src="/assets/landing/left-panel-bg.png" alt="PTRI Registration Information System" class="h-full w-full object-cover" /></aside>
                <div class="flex items-stretch justify-center">
            <form class="flex min-h-72 w-full flex-col justify-center rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-11 lg:min-h-[560px]" @submit.prevent="submit">
                <div class="flex items-center gap-3 border-b border-slate-200 pb-5"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-lg text-[#07559e]"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></div><div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#07559e]">Administration</p><h1 class="text-xl font-semibold text-slate-900">Sign in</h1></div></div>
                <p class="mt-5 text-sm text-slate-600">Use your authorized PTRI administrator credentials to continue. Fields marked with <span class="font-semibold text-rose-600">*</span> are required.</p>
                <p v-if="success" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ success }}</p>
                <label class="mt-8 block"><span class="required-label text-sm font-semibold text-slate-700">Username</span><input v-model="form.username" autocomplete="username" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" /><p v-if="form.errors.username" class="mt-1 text-sm text-rose-600">{{ form.errors.username }}</p></label>
                <label class="mt-5 block">
                    <span class="flex items-center justify-between"><span class="required-label text-sm font-semibold text-slate-700">Password</span><Link href="/admin/forgot-password" class="text-xs font-semibold text-[#07559e] hover:text-[#043d78]">Forgot password?</Link></span>
                    <input v-model="form.password" type="password" autocomplete="current-password" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" /><p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p>
                </label>
                <button type="submit" :disabled="form.processing" class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:bg-slate-400">Sign in<i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            </form>
                </div>
            </section>
        </main>
    `,
});
