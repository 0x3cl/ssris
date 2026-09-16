import { Head, Link, useForm } from '@inertiajs/vue3';
import { defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';
import RichTextEditor from '../../components/RichTextEditor';

export default defineComponent({
    name: 'AdminFormTemplateForm',
    components: { AdminShell, Head, Link, RichTextEditor },
    props: { template: { type: Object, required: true } },
    setup(props) {
        const form = useForm({ subject: props.template.subject, body: props.template.body });
        const save = () => form.put(`/admin/form-templates/${props.template.id}`);
        const placeholder = (variable) => `{{${variable}}}`;
        return { form, placeholder, save };
    },
    template: `<Head :title="template.name" /><AdminShell active="form-templates" :title="template.name"><section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">System settings</p><h2 class="mt-1 text-2xl font-bold text-slate-900">{{ template.name }}</h2><p class="mt-1 max-w-2xl text-slate-600">{{ template.description }}</p></div><Link href="/admin/form-templates" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:border-[#07559e] hover:text-[#07559e]"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to templates</Link></div><form class="mt-7 grid gap-5" @submit.prevent="save"><label><span class="required-label text-sm font-semibold text-slate-700">Subject</span><input v-model="form.subject" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.subject }" /><p v-if="form.errors.subject" class="mt-1 text-sm text-rose-600">{{ form.errors.subject }}</p></label><label><span class="required-label text-sm font-semibold text-slate-700">Message</span><RichTextEditor v-model="form.body" class="mt-2" :class="{ 'border-rose-500': form.errors.body }" /><p v-if="form.errors.body" class="mt-1 text-sm text-rose-600">{{ form.errors.body }}</p></label><div class="border border-slate-200 bg-slate-50 p-4"><p class="text-sm font-semibold text-slate-700">Available placeholders</p><p class="mt-1 text-sm text-slate-500">Copy a placeholder into the subject or message and it will be replaced with the request details before the notice is sent.</p><div class="mt-3 flex flex-wrap gap-2"><span v-for="variable in template.variables" :key="variable" class="rounded-full bg-white px-3 py-1 font-mono text-xs text-slate-700 ring-1 ring-slate-200">{{ placeholder(variable) }}</span></div></div><div class="flex justify-end gap-3 border-t border-slate-200 pt-5"><Link href="/admin/form-templates" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</Link><button type="submit" :disabled="form.processing" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#009bd8] disabled:cursor-not-allowed disabled:opacity-60"><i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>{{ form.processing ? 'Saving…' : 'Save template' }}</button></div></form></section></AdminShell>`,
});
