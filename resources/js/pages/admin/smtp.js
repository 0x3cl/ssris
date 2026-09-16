import { Head, router, useForm } from '@inertiajs/vue3';
import { defineComponent, ref } from 'vue';
import AdminShell from '../../components/AdminShell';

export default defineComponent({
    name: 'AdminSmtp',
    components: { AdminShell, Head },
    props: { setting: { type: Object, default: null } },
    setup(props) {
        const form = useForm({
            host: props.setting?.host ?? '',
            port: props.setting?.port ?? '',
            username: props.setting?.username ?? '',
            password: '',
            from_address: props.setting?.from_address ?? '',
            from_name: props.setting?.from_name ?? '',
        });
        const save = () => form.put('/admin/smtp-configuration');

        const testing = ref(false);
        const testConnection = () => {
            testing.value = true;
            router.post('/admin/smtp-configuration/test', {}, {
                preserveScroll: true,
                onFinish: () => {
                    testing.value = false;
                },
            });
        };

        return { form, save, testing, testConnection };
    },
    template: `<Head title="SMTP Configuration" /><AdminShell active="smtp-configuration" title="SMTP Configuration"><section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">System settings</p><h2 class="mt-1 text-2xl font-bold text-slate-900">Outgoing email server</h2><p class="mt-1 max-w-2xl text-slate-600">Configure the single SMTP connection used to send notices from the Service Requests Information System.</p></div><button type="button" title="Send a test email" :disabled="testing" class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-100 text-xl text-[#07559e] transition hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-60" @click="testConnection"><i class="fa-solid" :class="testing ? 'fa-spinner fa-spin' : 'fa-envelope'" aria-hidden="true"></i></button></div><form class="mt-7 grid gap-5 md:grid-cols-2" @submit.prevent="save"><label><span class="required-label text-sm font-semibold text-slate-700">SMTP host</span><input v-model="form.host" placeholder="smtp.example.com" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.host }" /><p v-if="form.errors.host" class="mt-1 text-sm text-rose-600">{{ form.errors.host }}</p></label><label><span class="required-label text-sm font-semibold text-slate-700">Port</span><input v-model="form.port" placeholder="587" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.port }" /><p v-if="form.errors.port" class="mt-1 text-sm text-rose-600">{{ form.errors.port }}</p></label><label class="md:col-span-2"><span class="required-label text-sm font-semibold text-slate-700">Username</span><input v-model="form.username" placeholder="notifications@example.com" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.username }" /><p v-if="form.errors.username" class="mt-1 text-sm text-rose-600">{{ form.errors.username }}</p></label><label><span class="required-label text-sm font-semibold text-slate-700">From address</span><input v-model="form.from_address" placeholder="notifications@example.com" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.from_address }" /><p v-if="form.errors.from_address" class="mt-1 text-sm text-rose-600">{{ form.errors.from_address }}</p><p class="mt-2 text-sm text-slate-500">Must be a sender verified with your SMTP provider, or emails may be silently dropped.</p></label><label><span class="required-label text-sm font-semibold text-slate-700">From name</span><input v-model="form.from_name" placeholder="SRIS" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.from_name }" /><p v-if="form.errors.from_name" class="mt-1 text-sm text-rose-600">{{ form.errors.from_name }}</p></label><label class="md:col-span-2"><span class="text-sm font-semibold text-slate-700">Password</span><input v-model="form.password" type="password" placeholder="Leave blank to keep the current password" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.password }" /><p v-if="form.errors.password" class="mt-1 text-sm text-rose-600">{{ form.errors.password }}</p><p class="mt-2 text-sm text-slate-500"><i class="fa-solid fa-lock mr-1 text-[#07559e]" aria-hidden="true"></i>Stored credentials remain encrypted.</p></label><div class="flex justify-end gap-3 border-t border-slate-200 pt-5 md:col-span-2"><button type="button" :disabled="testing" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60" @click="testConnection"><i class="fa-solid mr-2" :class="testing ? 'fa-spinner fa-spin' : 'fa-paper-plane'" aria-hidden="true"></i>{{ testing ? 'Testing…' : 'Test SMTP connection' }}</button><button type="submit" :disabled="form.processing" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#009bd8] disabled:cursor-not-allowed disabled:opacity-60"><i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>{{ form.processing ? 'Saving…' : 'Save SMTP settings' }}</button></div></form></section></AdminShell>`,
});
