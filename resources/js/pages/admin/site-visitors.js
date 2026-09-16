import { Head, router } from '@inertiajs/vue3';
import { computed, defineComponent, onBeforeUnmount, reactive, ref } from 'vue';
import AdminIndexControls from '../../components/AdminIndexControls';
import AdminPagination from '../../components/AdminPagination';
import AdminShell from '../../components/AdminShell';
import DashboardChart from '../../components/DashboardChart';

const palette = ['#07559e', '#00aeef', '#38bdf8', '#7dd3fc', '#1e40af', '#0f766e', '#f59e0b', '#64748b'];

export default defineComponent({
    name: 'AdminSiteVisitors',
    components: { AdminIndexControls, AdminPagination, AdminShell, DashboardChart, Head },
    props: {
        filters: { type: Object, required: true },
        visitors: { type: Object, required: true },
        stats: { type: Object, required: true },
        newVisitorsSeries: { type: Array, required: true },
        activeVisitorsSeries: { type: Array, required: true },
        providerBreakdown: { type: Array, required: true },
    },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const month = ref(props.filters.month);
        let searchTimer;

        const load = () => router.get('/admin/site-visitors', filters, { preserveState: true, replace: true, preserveScroll: true });
        const search = () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(load, 350);
        };
        const page = (url) => {
            if (url) router.get(url, {}, { preserveScroll: true });
        };

        const changeMonth = () => {
            filters.month = month.value;
            router.get('/admin/site-visitors', filters, { preserveScroll: true, preserveState: true, replace: true });
        };
        const moveMonth = (offset) => {
            const [year, selectedMonth] = month.value.split('-').map(Number);
            const date = new Date(year, selectedMonth - 1 + offset, 1);
            month.value = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            changeMonth();
        };
        const formattedMonth = computed(() => new Intl.DateTimeFormat('en-US', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        }).format(new Date(`${month.value}-01T00:00:00`)));

        const baseOptions = { plugins: { legend: { labels: { boxWidth: 12, color: '#334155', font: { family: 'Poppins' } } }, tooltip: { titleFont: { family: 'Poppins' }, bodyFont: { family: 'Poppins' } } } };
        const trendData = computed(() => ({
            labels: props.newVisitorsSeries.map((point) => point.date.slice(5)),
            datasets: [
                { label: 'New visitors', data: props.newVisitorsSeries.map((point) => point.total), borderColor: '#00aeef', backgroundColor: 'rgba(0, 174, 239, 0.15)', fill: true, tension: 0.35, pointBackgroundColor: '#07559e', pointRadius: 4 },
            ],
        }));
        const activityData = computed(() => ({
            labels: props.activeVisitorsSeries.map((point) => point.date.slice(5)),
            datasets: [
                { label: 'Active visitors', data: props.activeVisitorsSeries.map((point) => point.total), backgroundColor: '#07559e', borderRadius: 6, borderSkipped: false },
            ],
        }));
        const trendOptions = { ...baseOptions, scales: { x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Poppins' } } }, y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b', font: { family: 'Poppins' } }, grid: { color: '#e2e8f0' } } } };
        const activityOptions = { ...trendOptions, plugins: { ...baseOptions.plugins, legend: { display: false } } };
        const providerData = computed(() => ({
            labels: props.providerBreakdown.map((item) => item.provider),
            datasets: [{ data: props.providerBreakdown.map((item) => item.total), backgroundColor: palette, borderColor: '#ffffff', borderWidth: 3 }],
        }));
        const providerOptions = { ...baseOptions, plugins: { ...baseOptions.plugins, legend: { position: 'bottom', labels: { boxWidth: 12, color: '#334155', font: { family: 'Poppins' } } } } };

        onBeforeUnmount(() => clearTimeout(searchTimer));

        return {
            activityData,
            activityOptions,
            changeMonth,
            filters,
            formattedMonth,
            load,
            month,
            moveMonth,
            page,
            providerData,
            providerOptions,
            search,
            trendData,
            trendOptions,
        };
    },
    template: `
        <Head title="Site Visitors" />
        <AdminShell active="site-visitors" title="Site Visitors">
            <section class="flex flex-wrap items-end justify-between gap-4 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Site visitors module</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">Monthly visitor statistics</h2>
                    <p class="mt-1 text-slate-600">Review site traffic and visitor activity for the selected month.</p>
                </div>
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <p class="mb-2 text-sm font-semibold text-slate-700">Month</p>
                        <div class="inline-flex h-12 items-center rounded-lg border border-slate-300 bg-white shadow-sm">
                            <button type="button" class="flex h-full w-12 items-center justify-center border-r border-slate-200 text-[#07559e] transition hover:bg-sky-50" aria-label="Previous month" @click="moveMonth(-1)"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                            <span class="flex min-w-56 justify-center px-5 text-center text-sm font-bold text-slate-700">{{ formattedMonth }}</span>
                            <button type="button" class="flex h-full w-12 items-center justify-center border-l border-slate-200 text-[#07559e] transition hover:bg-sky-50" aria-label="Next month" @click="moveMonth(1)"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <label class="block">
                        <span class="mb-2 block text-sm font-semibold text-slate-700">Date picker</span>
                        <input v-model="month" type="month" class="h-12 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" aria-label="Date picker" @change="changeMonth" />
                    </label>
                </div>
            </section>

            <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article v-for="item in [
                    { label: 'Total visitors (all-time)', value: stats.totalVisitors, icon: 'fa-users', tone: 'text-[#07559e] bg-sky-100' },
                    { label: 'Total visits (all-time)', value: stats.totalVisits, icon: 'fa-eye', tone: 'text-cyan-700 bg-cyan-100' },
                    { label: 'New visitors this month', value: stats.newThisMonth, icon: 'fa-user-plus', tone: 'text-emerald-700 bg-emerald-100' },
                    { label: 'Active visitors this month', value: stats.activeThisMonth, icon: 'fa-signal', tone: 'text-amber-700 bg-amber-100' },
                ]" :key="item.label" class="border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="text-sm font-semibold text-slate-500">{{ item.label }}</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ item.value }}</p></div>
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl" :class="item.tone"><i class="fa-solid" :class="item.icon" aria-hidden="true"></i></span>
                    </div>
                </article>
            </section>

            <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Visitor activity</p>
                    <h2 class="mt-1 text-xl font-bold text-slate-900">New visitors per day</h2>
                    <p class="mt-1 text-sm text-slate-600">First-time visitors recorded during the selected month.</p>
                </div>
                <div class="mt-6"><DashboardChart chart-type="line" :chart-data="trendData" :options="trendOptions" /></div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-2">
                <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Visitor activity</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-900">Active visitors per day</h2>
                        <p class="mt-1 text-sm text-slate-600">Visitors last seen on each day of the selected month.</p>
                    </div>
                    <div class="mt-6"><DashboardChart chart-type="bar" :chart-data="activityData" :options="activityOptions" /></div>
                </article>
                <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Network providers</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-900">Visitors by provider</h2>
                        <p class="mt-1 text-sm text-slate-600">Top internet service providers detected this month.</p>
                    </div>
                    <div class="mt-6">
                        <DashboardChart v-if="providerData.labels.length" chart-type="pie" :chart-data="providerData" :options="providerOptions" />
                        <p v-else class="py-10 text-center text-sm text-slate-500">No visitor data for this month yet.</p>
                    </div>
                </article>
            </section>

            <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Site visitors module</p>
                    <h2 class="mt-1 text-2xl font-bold text-slate-900">All site visitors</h2>
                    <p class="mt-1 text-slate-600">Search and filter every visitor recorded on the public site.</p>
                </div>
                <div class="mt-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" search-placeholder="Search IP address, provider, or user agent" @search="search">
                        <template #filters>
                            <div class="relative">
                                <input v-model="filters.date" type="date" class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700" @change="load" />
                                <button v-if="filters.date" type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-2 py-1 text-xs font-bold text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Clear date filter" @click="filters.date = ''; load()">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                        </template>
                    </AdminIndexControls>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left">
                        <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-4">IP address</th>
                                <th class="px-4 py-4">Provider</th>
                                <th class="px-4 py-4">User agent</th>
                                <th class="px-4 py-4">Total visits</th>
                                <th class="px-4 py-4">First seen</th>
                                <th class="px-4 py-4">Last seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="visitor in visitors.data" :key="visitor.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ visitor.ip_address }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ visitor.provider || '—' }}</td>
                                <td class="max-w-xs truncate px-4 py-4 text-sm text-slate-500" :title="visitor.user_agent">{{ visitor.user_agent || '—' }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-700">{{ visitor.total_visits }}</td>
                                <td class="px-4 py-4 text-sm text-slate-500">{{ visitor.first_seen }}</td>
                                <td class="px-4 py-4 text-sm text-slate-500">{{ visitor.last_seen }}</td>
                            </tr>
                            <tr v-if="visitors.data.length === 0">
                                <td colspan="6" class="px-4 py-12 text-center text-slate-500">No site visitors match the selected filters.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <AdminPagination :pagination="visitors" @page="page" />
            </section>
        </AdminShell>
    `,
});
