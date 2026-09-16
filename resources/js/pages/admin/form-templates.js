import { Head, Link, router } from '@inertiajs/vue3';
import { defineComponent, onBeforeUnmount, reactive } from 'vue';
import AdminShell from '../../components/AdminShell';

export default defineComponent({
    name: 'AdminFormTemplates',
    components: { AdminShell, Head, Link },
    props: { filters: { type: Object, required: true }, templates: { type: Array, required: true } },
    setup(props) {
        const filters = reactive({ ...props.filters });
        let searchTimer;
        const load = () => router.get('/admin/form-templates', filters, { preserveScroll: true, preserveState: true, replace: true });
        const search = () => { clearTimeout(searchTimer); searchTimer = setTimeout(load, 350); };
        const preview = (body) => body.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
        const placeholder = (variable) => `{{${variable}}}`;
        onBeforeUnmount(() => clearTimeout(searchTimer));
        return { filters, placeholder, preview, search };
    },
    template: `<Head title="Form Templates" /><AdminShell active="form-templates" title="Form Templates"><section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">System settings</p><h2 class="mt-1 text-2xl font-bold text-slate-900">Notice templates</h2><p class="mt-1 max-w-2xl text-slate-600">Edit the message sent to clients for each system notice. Placeholders wrapped in double braces are replaced with the request details before sending.</p></div><div class="flex h-12 w-12 items-center justify-center rounded-xl bg-sky-100 text-xl text-[#07559e]"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div></div><div class="mt-7 flex justify-end"><label class="relative w-full sm:w-80"><span class="sr-only">Search</span><i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><input v-model="filters.search" class="w-full rounded-lg border border-slate-300 py-2.5 pl-11 pr-4 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" placeholder="Search template name or subject" @input="search" /></label></div><div class="mt-6 grid gap-4 xl:grid-cols-2"><article v-for="template in templates" :key="template.id" class="flex flex-col border border-slate-200 p-5"><div class="flex items-start justify-between gap-4"><div class="min-w-0"><h3 class="text-lg font-bold text-slate-900">{{ template.name }}</h3><p class="mt-1 text-sm text-slate-600">{{ template.description }}</p></div><Link :href="'/admin/form-templates/' + template.id + '/edit'" class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit</Link></div><dl class="mt-4 space-y-2 text-sm"><div class="flex gap-2"><dt class="shrink-0 font-semibold text-slate-700">Subject:</dt><dd class="min-w-0 truncate text-slate-600">{{ template.subject }}</dd></div><div class="flex gap-2"><dt class="shrink-0 font-semibold text-slate-700">Message:</dt><dd class="line-clamp-2 min-w-0 text-slate-600">{{ preview(template.body) }}</dd></div></dl><div class="mt-4 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4"><span v-for="variable in template.variables" :key="variable" class="rounded-full bg-slate-100 px-3 py-1 font-mono text-xs text-slate-700">{{ placeholder(variable) }}</span></div><p v-if="template.updated_at" class="mt-3 text-xs text-slate-400">Last updated {{ template.updated_at }}</p></article><p v-if="templates.length === 0" class="px-4 py-12 text-center text-slate-500 xl:col-span-2">No form templates match your search.</p></div></section></AdminShell>`,
});
