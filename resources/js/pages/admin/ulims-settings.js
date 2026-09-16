import { Head, router, useForm } from '@inertiajs/vue3';
import { defineComponent, ref } from 'vue';
import AdminShell from '../../components/AdminShell';

export default defineComponent({
    name: 'AdminUlimsSettings',
    components: { AdminShell, Head },
    props: { setting: { type: Object, default: null } },
    setup(props) {
        const form = useForm({
            base_url: props.setting?.base_url ?? '',
            username: props.setting?.username ?? '',
        });
        const save = () => form.put('/admin/ulims-configuration');

        const testing = ref(false);
        const testConnection = () => {
            testing.value = true;
            router.post('/admin/ulims-configuration/test', {}, {
                preserveScroll: true,
                onFinish: () => {
                    testing.value = false;
                },
            });
        };

        return { form, save, testing, testConnection };
    },
    template: `<Head title="ULIMS Configuration" /><AdminShell active="ulims-configuration" title="ULIMS Configuration"><section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">System settings</p><h2 class="mt-1 text-2xl font-bold text-slate-900">ULIMS connection</h2><p class="mt-1 max-w-2xl text-slate-600">Configure the connection used to pull the test category, sample type, and test method catalogue from ULIMS for Lab Services requests.</p></div><button type="button" title="Test the ULIMS connection" :disabled="testing" class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-100 text-xl text-[#07559e] transition hover:bg-sky-200 disabled:cursor-not-allowed disabled:opacity-60" @click="testConnection"><i class="fa-solid" :class="testing ? 'fa-spinner fa-spin' : 'fa-flask'" aria-hidden="true"></i></button></div><form class="mt-7 grid gap-5 md:grid-cols-2" @submit.prevent="save"><label class="md:col-span-2"><span class="required-label text-sm font-semibold text-slate-700">Base URL</span><input v-model="form.base_url" placeholder="http://10.10.11.7:4000/api" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.base_url }" /><p v-if="form.errors.base_url" class="mt-1 text-sm text-rose-600">{{ form.errors.base_url }}</p></label><label class="md:col-span-2"><span class="required-label text-sm font-semibold text-slate-700">Username</span><input v-model="form.username" placeholder="srris" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.username }" /><p v-if="form.errors.username" class="mt-1 text-sm text-rose-600">{{ form.errors.username }}</p><p class="mt-2 text-sm text-slate-500">Sent to ULIMS when requesting an access token; no password is required by the ULIMS API.</p></label><div class="flex justify-end gap-3 border-t border-slate-200 pt-5 md:col-span-2"><button type="button" :disabled="testing" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60" @click="testConnection"><i class="fa-solid mr-2" :class="testing ? 'fa-spinner fa-spin' : 'fa-plug'" aria-hidden="true"></i>{{ testing ? 'Testing…' : 'Test ULIMS connection' }}</button><button type="submit" :disabled="form.processing" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#009bd8] disabled:cursor-not-allowed disabled:opacity-60"><i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>{{ form.processing ? 'Saving…' : 'Save ULIMS settings' }}</button></div></form></section></AdminShell>`,
});
