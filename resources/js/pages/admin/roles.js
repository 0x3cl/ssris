import { Head, router } from '@inertiajs/vue3';
import { defineComponent, onBeforeUnmount, reactive } from 'vue';
import AdminIndexControls from '../../components/AdminIndexControls';
import AdminPagination from '../../components/AdminPagination';
import AdminShell from '../../components/AdminShell';
import DeleteConfirmationModal from '../../components/DeleteConfirmationModal';

export default defineComponent({
    name: 'AdminRoles',
    components: { AdminIndexControls, AdminPagination, AdminShell, DeleteConfirmationModal, Head },
    props: { filters: { type: Object, required: true }, roles: { type: Object, required: true } },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const deleting = reactive({ processing: false, role: null });
        let searchTimer;
        const load = () => router.get('/admin/roles-and-permissions', filters, { preserveScroll: true, preserveState: true, replace: true });
        const search = () => { clearTimeout(searchTimer); searchTimer = setTimeout(load, 350); };
        const page = (url) => { if (url) router.get(url, {}, { preserveScroll: true }); };
        const remove = (role) => { if (role.name !== 'superadmin') deleting.role = role; };
        const confirmDelete = (deleteCode) => {
            if (!deleting.role) return;
            deleting.processing = true;
            router.delete(`/admin/roles-and-permissions/${deleting.role.id}`, {
                data: { delete_code: deleteCode },
                onSuccess: () => { deleting.role = null; },
                onFinish: () => { deleting.processing = false; },
            });
        };
        onBeforeUnmount(() => clearTimeout(searchTimer));
        return { confirmDelete, deleting, filters, page, remove, search };
    },
    template: `<Head title="Roles and permissions" /><AdminShell active="roles-and-permissions" title="Roles and Permissions"><section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Access management</p><h2 class="mt-1 text-2xl font-bold text-slate-900">Roles and permissions</h2><p class="mt-1 text-slate-600">Create roles and control the modules they can access.</p></div><div class="mt-7"><AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" add-href="/admin/roles-and-permissions/create" add-label="Add role" search-placeholder="Search role name" @search="search" /></div><div class="mt-6 overflow-x-auto"><table class="w-full min-w-[800px] text-left"><thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-4">Role</th><th class="px-4 py-4">Permissions</th><th class="px-4 py-4">Users</th><th class="px-4 py-4 text-right">Actions</th></tr></thead><tbody><tr v-for="role in roles.data" :key="role.id" class="border-b border-slate-100 hover:bg-sky-50/50"><td class="px-4 py-4 font-semibold capitalize text-slate-900">{{ role.name }}</td><td class="max-w-xl px-4 py-4 text-sm text-slate-600">{{ role.permissions.map(permission => permission.name).join(', ') || 'No permissions assigned' }}</td><td class="px-4 py-4 text-sm text-slate-700">{{ role.users_count }}</td><td class="px-4 py-4 text-right"><a :href="'/admin/roles-and-permissions/' + role.id + '/edit'" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit</a><button v-if="role.name !== 'superadmin'" type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50" @click="remove(role)"><i class="fa-solid fa-trash" aria-hidden="true"></i>Delete</button></td></tr><tr v-if="roles.data.length === 0"><td colspan="4" class="px-4 py-12 text-center text-slate-500">No roles match your search.</td></tr></tbody></table></div><AdminPagination :pagination="roles" @page="page" /></section><DeleteConfirmationModal :open="Boolean(deleting.role)" :item-name="deleting.role?.name" :processing="deleting.processing" @close="deleting.role = null" @confirm="confirmDelete" /></AdminShell>`,
});
