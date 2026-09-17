import { Head, Link, useForm } from '@inertiajs/vue3';
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'AdminResetPassword',
    components: { Head, Link },
    props: { token: { type: String, required: true }, email: { type: String, default: '' } },
    setup(props) {
        const form = useForm({
            token: props.token,
            email: props.email,
            password: '',
            password_confirmation: '',
        });
        const submit = () => form.post('/admin/reset-password');

        return { form, submit };
    },
    template: `
        <Head title="Reset password" />
        <main class="bg-slate-50 px-5 py-10 sm:px-8 sm:py-14 lg:px-12 lg:py-16">
            <section class="mx-auto grid w-full max-w-[1500px] gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-stretch xl:gap-14">
                <aside class="min-h-72 overflow-hidden rounded-2xl bg-sky-50 lg:min-h-[560px]"><img src="/assets/landing/left-panel-bg.png" alt="PTRI Registration Information System" class="h-full w-full object-cover" /></aside>
                <div class="flex items-stretch justify-center">
                    <form class="flex min-h-72 w-full flex-col justify-center rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-11 lg:min-h-[560px]" @submit.prevent="submit">
                        <div class="flex items-center gap-3 border-b border-slate-200 pb-5"><div class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-lg text-[#07559e]"><i class="fa-solid fa-lock" aria-hidden="true"></i></div><div><p class="text-xs font-semibold uppercase tracking-[0.14em] text-[#07559e]">Administration</p><h1 class="text-xl font-semibold text-slate-900">Reset password</h1></div></div>
                        <p class="mt-5 text-sm text-slate-600">Choose a new password for your administrator account. Fields marked with <span class="font-semibold text-rose-600">*</span> are required.</p>
                        <label class="mt-8 block"><span class="required-label text-sm font-semibold text-slate-700">Email address</span><input v-model="form.email" type="email" autocomplete="email" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" /><p v-if="form.errors.email" class="mt-1 text-sm text-rose-600">{{ form.errors.email }}</p></label>
                        <label class="mt-5 block"><span class="required-label text-sm font-semibold text-slate-700">New password</span><input v-model="form.password" type="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" /><p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p></label>
                        <label class="mt-5 block"><span class="required-label text-sm font-semibold text-slate-700">Confirm new password</span><input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" /></label>
                        <button type="submit" :disabled="form.processing" class="mt-8 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:bg-slate-400">Reset password<i class="fa-solid fa-check" aria-hidden="true"></i></button>
                        <Link href="/admin/login" class="mt-5 text-center text-sm font-semibold text-[#07559e] hover:text-[#043d78]"><i class="fa-solid fa-arrow-left mr-1" aria-hidden="true"></i>Back to sign in</Link>
                    </form>
                </div>
            </section>
        </main>
    `,
});
