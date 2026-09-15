import { Head, router } from '@inertiajs/vue3';
import { defineComponent, onBeforeUnmount, reactive, ref } from 'vue';
import AdminIndexControls from '../../components/AdminIndexControls';
import AdminPagination from '../../components/AdminPagination';
import AdminShell from '../../components/AdminShell';
import DeleteConfirmationModal from '../../components/DeleteConfirmationModal';
import ProfileImagePreviewModal from '../../components/ProfileImagePreviewModal';

export default defineComponent({
    name: 'AdminUsers',
    components: { AdminIndexControls, AdminPagination, AdminShell, DeleteConfirmationModal, Head, ProfileImagePreviewModal },
    props: { filters: { type: Object, required: true }, users: { type: Object, required: true } },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const deleting = reactive({ processing: false, user: null });
        const previewUser = ref(null);
        let searchTimer;
        const load = () => router.get('/admin/users', filters, { preserveScroll: true, preserveState: true, replace: true });
        const search = () => { clearTimeout(searchTimer); searchTimer = setTimeout(load, 350); };
        const page = (url) => { if (url) router.get(url, {}, { preserveScroll: true }); };
        const remove = (user) => { deleting.user = user; };
        const confirmDelete = (deleteCode) => {
            if (!deleting.user) return;
            deleting.processing = true;
            router.delete(`/admin/users/${deleting.user.id}`, {
                data: { delete_code: deleteCode },
                onSuccess: () => { deleting.user = null; },
                onFinish: () => { deleting.processing = false; },
            });
        };
        onBeforeUnmount(() => clearTimeout(searchTimer));
        return { confirmDelete, deleting, filters, page, previewUser, remove, search };
    },
    template: `<Head title="Users" /><AdminShell active="users" title="Users"><section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Users module</p><h2 class="mt-1 text-2xl font-bold text-slate-900">All users</h2><p class="mt-1 text-slate-600">Manage administrator accounts and assigned roles.</p></div><div class="mt-7"><AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" add-href="/admin/users/create" add-label="Add user" search-placeholder="Search name, username, or email" @search="search" /></div><div class="mt-6 overflow-x-auto"><table class="w-full min-w-[850px] text-left"><thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-4">User</th><th class="px-4 py-4">Username</th><th class="px-4 py-4">Role</th><th class="px-4 py-4">Status</th><th class="px-4 py-4 text-right">Actions</th></tr></thead><tbody><tr v-for="user in users.data" :key="user.id" class="border-b border-slate-100 hover:bg-sky-50/50"><td class="px-4 py-4"><div class="flex items-center gap-3"><button v-if="user.profile_image" type="button" class="group relative shrink-0 rounded-full" :aria-label="'Preview ' + user.name + ' profile image'" @click="previewUser = user"><img :src="'/storage/' + user.profile_image" :alt="user.name" class="h-11 w-11 rounded-full object-cover ring-2 ring-transparent transition group-hover:ring-[#00aeef]" /><span class="absolute inset-0 flex items-center justify-center rounded-full bg-slate-950/40 text-xs text-white opacity-0 transition group-hover:opacity-100"><i class="fa-solid fa-expand" aria-hidden="true"></i></span></button><span v-else class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-sky-100 font-bold text-[#07559e]">{{ user.name.slice(0, 1).toUpperCase() }}</span><div><p class="font-semibold text-slate-900">{{ user.name }}</p><p class="mt-1 text-sm text-slate-500">{{ user.email }}</p></div></div></td><td class="px-4 py-4 text-sm text-slate-700">{{ user.username }}</td><td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold capitalize text-slate-700">{{ user.role_type || 'Unassigned' }}</span></td><td class="px-4 py-4"><span class="rounded-full px-3 py-1 text-xs font-bold uppercase" :class="user.account_status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ user.account_status }}</span></td><td class="px-4 py-4 text-right"><a :href="'/admin/users/' + user.id + '/edit'" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit</a><button v-if="user.id !== $page.props.auth?.user?.id" type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50" @click="remove(user)"><i class="fa-solid fa-trash" aria-hidden="true"></i>Delete</button></td></tr><tr v-if="users.data.length === 0"><td colspan="5" class="px-4 py-12 text-center text-slate-500">No users match your search.</td></tr></tbody></table></div><AdminPagination :pagination="users" @page="page" /></section><DeleteConfirmationModal :open="Boolean(deleting.user)" :item-name="deleting.user?.name" :processing="deleting.processing" @close="deleting.user = null" @confirm="confirmDelete" /><ProfileImagePreviewModal :open="Boolean(previewUser)" :name="previewUser?.name" :image-url="previewUser?.profile_image ? '/storage/' + previewUser.profile_image : ''" @close="previewUser = null" /></AdminShell>`,
});
