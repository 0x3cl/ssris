import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';

export default defineComponent({
    name: 'AdminForgotPassword',
    components: { Head, Link },
    props: { smtpConfigured: { type: Boolean, required: true } },
    setup(props) {
        const form = useForm({ email: '' });
        const submit = () => {
            if (!props.smtpConfigured) return;
            form.post('/admin/forgot-password');
        };
        const page = usePage();
        const success = computed(() => page.props.flash?.success);

        return { form, submit, success };
    },
    template: `
        <Head title="Forgot password" />
        <main class="bg-slate-50 px-5 py-10 sm:px-8 sm:py-14 lg:px-12 lg:py-16">
            <section class="mx-auto grid w-full max-w-[1500px] gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-stretch xl:gap-14">
                <aside class="min-h-72 overflow-hidden rounded-2xl bg-sky-50 lg:min-h-[560px]"><img src="/assets/landing/left-panel-bg.png" alt="PTRI Registration Information System" class="h-full w-full object-cover" /></aside>
                <div class="flex items-stretch justify-center">
                    <form class="flex min-h-72 w-full flex-col justify-center rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-11 lg:min-h-[560px]" @submit.prevent="submit">
                        <div class="flex items-center gap-3 border-b border-slate-200 pb-5"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-lg text-[#07559e]"><i class="fa-solid fa-key" aria-hidden="true"></i></div><div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#07559e]">Administration</p><h1 class="text-xl font-semibold text-slate-900">Forgot password</h1></div></div>

                        <p v-if="!smtpConfigured" class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">
                            <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>This module is not yet ready: SMTP has not been configured, so password reset emails cannot be sent yet. Please contact your system administrator.
                        </p>
                        <template v-else>
                            <p class="mt-5 text-sm text-slate-600">Enter the email address on your administrator account and we'll send you a link to reset your password.</p>
                            <p v-if="success" class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ success }}</p>
                        </template>

                        <label class="mt-8 block"><span class="required-label text-sm font-semibold text-slate-700">Email address</span><input v-model="form.email" type="email" autocomplete="email" :disabled="!smtpConfigured" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" /><p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p></label>
                        <button type="submit" :disabled="form.processing || !smtpConfigured" class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-400">Send reset link<i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button>
                        <Link href="/admin/login" class="mt-5 text-center text-sm font-semibold text-[#07559e] hover:text-[#043d78]"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i>Back to sign in</Link>
                    </form>
                </div>
            </section>
        </main>
    `,
});
