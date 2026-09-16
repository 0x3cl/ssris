import { Link, usePage, router } from '@inertiajs/vue3';
import { defineComponent, ref, watch } from 'vue';
import FeedbackModal from './FeedbackModal';

export default defineComponent({
    name: 'AdminShell',
    props: { active: { type: String, required: true }, title: { type: String, required: true } },
    components: { FeedbackModal, Link },
    setup() {
        const logout = () => router.post('/admin/logout');
        const page = usePage();
        const sidebarOpen = ref(false);
        const feedback = ref({ message: '', open: false, title: '', tone: 'success' });
        const showFeedback = (title, message, tone) => {
            if (!message) return;
            feedback.value = { message, open: true, title, tone };
        };
        watch(() => page.props.flash?.success, (message) => showFeedback('Saved successfully', message, 'success'), { immediate: true });
        watch(() => page.props.flash?.error, (message) => showFeedback('Something went wrong', message, 'error'), { immediate: true });
        watch(() => page.props.errors, (errors) => {
            const message = Object.values(errors ?? {}).flat()[0];
            if (typeof message === 'string') showFeedback('Please review the form', message, 'error');
        });
        const modules = [
            { key: 'dashboard', label: 'Dashboard', href: '/dashboard', icon: 'fa-solid fa-chart-line' },
            { key: 'clients', label: 'Clients', href: '/admin/clients', icon: 'fa-solid fa-address-book' },
            { key: 'requests', label: 'Requests', href: '/admin/requests', icon: 'fa-solid fa-clipboard-list' },
            { key: 'site-visitors', label: 'Site Visitors', href: '/admin/site-visitors', icon: 'fa-solid fa-globe' },
            { key: 'reports', label: 'Reports', href: '/admin/reports', icon: 'fa-solid fa-chart-column' },
            { key: 'users', label: 'Users', href: '/admin/users', icon: 'fa-solid fa-users' },
            { key: 'roles-and-permissions', label: 'Roles and permissions', href: '/admin/roles-and-permissions', icon: 'fa-solid fa-user-shield' },
            { key: 'form-templates', label: 'Form templates', href: '/admin/form-templates', icon: 'fa-solid fa-file-lines' },
            { key: 'feedback-builder', label: 'Feedback Builder', href: '/admin/feedback-builder', icon: 'fa-solid fa-star-half-stroke' },
            { key: 'smtp-configuration', label: 'SMTP configuration', href: '/admin/smtp-configuration', icon: 'fa-solid fa-envelope' },
            { key: 'ulims-configuration', label: 'ULIMS configuration', href: '/admin/ulims-configuration', icon: 'fa-solid fa-flask' },
            { key: 'audit-trails', label: 'Audit Trails', href: '/admin/audit-trails', icon: 'fa-solid fa-clock-rotate-left' },
            { key: 'my-account', label: 'My account', href: '/admin/my-account', icon: 'fa-solid fa-circle-user' },
        ];

        return { feedback, logout, modules, sidebarOpen };
    },
    template: `
        <main class="bg-slate-50 px-4 py-8 sm:px-6 lg:px-10">
            <section class="mx-auto w-full max-w-[1800px]">
                <header class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-6">
                    <div class="flex items-center gap-5">
                        <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-300 text-slate-600 transition hover:border-[#07559e] hover:text-[#07559e] lg:hidden" aria-label="Open modules menu" @click="sidebarOpen = true">
                            <i class="fa-solid fa-bars" aria-hidden="true"></i>
                        </button>
                        <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-[#07559e]">Administration</p><h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ title }}</h1></div>
                    </div>
                    <div class="flex items-center gap-4"><Link href="/" class="text-sm font-semibold text-[#07559e] transition hover:text-[#043d78]"><i class="fa-solid fa-house mr-2" aria-hidden="true"></i>Public site</Link><button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[#07559e] hover:text-[#07559e]" @click="logout"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>Sign out</button></div>
                </header>
                <div class="mt-8 grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
                    <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden" @click="sidebarOpen = false"></div>
                    <aside
                        class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full overflow-y-auto border border-slate-200 bg-white p-3 transition-transform duration-300 ease-out lg:static lg:z-auto lg:h-fit lg:w-auto lg:translate-x-0 lg:self-start lg:sticky lg:top-6"
                        :class="sidebarOpen ? 'translate-x-0' : ''"
                    >
                        <div class="mb-2 flex items-center justify-between px-1 lg:hidden">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-400">Modules</p>
                            <button type="button" class="flex h-9 w-9 items-center justify-center rounded-full text-2xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close modules menu" @click="sidebarOpen = false">×</button>
                        </div>
                        <p class="hidden px-3 pb-3 pt-2 text-xs font-semibold uppercase tracking-[0.14em] text-slate-400 lg:block">Modules</p>
                        <nav class="space-y-1" aria-label="Admin modules"><Link v-for="module in modules" :key="module.key" :href="module.href" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition" :class="active === module.key ? 'bg-sky-100 text-[#07559e]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'" @click="sidebarOpen = false"><i :class="module.icon" class="w-4 text-center" aria-hidden="true"></i>{{ module.label }}</Link></nav>
                    </aside>
                    <div class="min-w-0"><slot /></div>
                </div>
            </section>
            <FeedbackModal :open="feedback.open" :title="feedback.title" :message="feedback.message" :tone="feedback.tone" :icon="feedback.tone === 'error' ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check'" @close="feedback.open = false" />
        </main>
    `,
});
