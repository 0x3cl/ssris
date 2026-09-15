import { Head, useForm } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';

export default defineComponent({
    name: 'AdminRoleForm',
    components: { AdminShell, Head },
    props: { modules: { type: Array, required: true }, role: { type: Object, default: null } },
    setup(props) {
        const isEditing = computed(() => Boolean(props.role));
        const permissions = (props.role?.permissions ?? []).reduce((items, permission) => {
            const [module, action] = permission.name.split('.');
            items[module] ??= [];
            items[module].push(action);
            return items;
        }, {});
        props.modules.forEach((module) => { permissions[module] ??= []; });
        const form = useForm({ name: props.role?.name ?? '', permissions });
        const save = () => { if (isEditing.value) { form.put(`/admin/roles-and-permissions/${props.role.id}`); } else { form.post('/admin/roles-and-permissions'); } };
        const label = (module) => module.replaceAll('-', ' ');
        const ensureRead = (module, isChecked) => {
            if (isChecked && !form.permissions[module].includes('read')) {
                form.permissions[module].push('read');
            }
        };
        return { ensureRead, form, isEditing, label, save };
    },
    template: `<Head :title="isEditing ? 'Edit role' : 'Add role'" /><AdminShell active="roles-and-permissions" :title="isEditing ? 'Edit role' : 'Add role'"><section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Access management</p><h2 class="mt-1 text-2xl font-bold text-slate-900">{{ isEditing ? 'Update role' : 'Create role' }}</h2><p class="mt-1 text-slate-600">Assign read and write access for each module.</p></div><a href="/admin/roles-and-permissions" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:border-[#07559e] hover:text-[#07559e]"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to roles</a></div><form class="mt-7" @submit.prevent="save"><label class="block max-w-xl"><span class="required-label text-sm font-semibold text-slate-700">Role name</span><input v-model="form.name" :readonly="role?.name === 'superadmin'" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100 read-only:bg-slate-100" :class="{ 'border-rose-500': form.errors.name }" /><p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p></label><fieldset class="mt-7"><legend class="text-sm font-semibold text-slate-700">Module permissions</legend><div class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-3"><article v-for="module in modules" :key="module" class="border border-slate-200 p-4"><h3 class="font-bold capitalize text-slate-900">{{ label(module) }}</h3><div class="mt-4 flex gap-5"><label class="inline-flex items-center gap-2 text-sm text-slate-700"><input v-model="form.permissions[module]" value="read" type="checkbox" class="h-4 w-4 accent-[#07559e]" />Read</label><label class="inline-flex items-center gap-2 text-sm text-slate-700"><input v-model="form.permissions[module]" value="write" type="checkbox" class="h-4 w-4 accent-[#07559e]" @change="ensureRead(module, $event.target.checked)" />Write</label></div></article></div></fieldset><div class="mt-7 flex justify-end gap-3 border-t border-slate-200 pt-5"><a href="/admin/roles-and-permissions" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a><button type="submit" :disabled="form.processing" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold text-white hover:bg-[#009bd8] disabled:cursor-not-allowed disabled:opacity-60"><i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>{{ isEditing ? 'Save changes' : 'Create role' }}</button></div></form></section></AdminShell>`,
});
