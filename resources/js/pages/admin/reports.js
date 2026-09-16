import { Head, router } from '@inertiajs/vue3';
import { computed, defineComponent, onBeforeUnmount, reactive, watch } from 'vue';
import AdminIndexControls from '../../components/AdminIndexControls';
import AdminPagination from '../../components/AdminPagination';
import AdminShell from '../../components/AdminShell';
import DashboardChart from '../../components/DashboardChart';
import ReportPeriodFilter from '../../components/ReportPeriodFilter';
import { useQueryTab } from '../../utils/query-tab';

const palette = ['#07559e', '#00aeef', '#38bdf8', '#7dd3fc', '#1e40af', '#0f766e', '#f59e0b', '#64748b'];
const TABS = ['demographics', 'clients', 'service-requests', 'feedback', 'site-visitors', 'audit-trails'];
const TAB_LABELS = {
    demographics: 'Demographic Profile',
    clients: 'Clients',
    'service-requests': 'Service Requests',
    feedback: 'Feedback Responses',
    'site-visitors': 'Site Visitors',
    'audit-trails': 'Audit Trails',
};

const baseOptions = { plugins: { legend: { labels: { boxWidth: 12, color: '#334155', font: { family: 'Poppins' } } }, tooltip: { titleFont: { family: 'Poppins' }, bodyFont: { family: 'Poppins' } } } };
const barOptions = { ...baseOptions, plugins: { ...baseOptions.plugins, legend: { display: false } }, scales: { x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'Poppins' } } }, y: { beginAtZero: true, ticks: { precision: 0, color: '#64748b', font: { family: 'Poppins' } }, grid: { color: '#e2e8f0' } } } };
const horizontalBarOptions = { ...barOptions, indexAxis: 'y' };
const pieOptions = { ...baseOptions, plugins: { ...baseOptions.plugins, legend: { position: 'bottom', labels: { boxWidth: 12, color: '#334155', font: { family: 'Poppins' } } } } };
const lineOptions = { ...baseOptions, scales: barOptions.scales };

const toBarOrPie = (rows, labelKey, valueKey, kind) => ({
    labels: (rows || []).map((row) => row[labelKey]),
    datasets: [{
        label: 'Total',
        data: (rows || []).map((row) => row[valueKey]),
        backgroundColor: kind === 'pie' ? palette : '#07559e',
        borderRadius: kind === 'pie' ? undefined : 6,
        borderSkipped: kind === 'pie' ? undefined : false,
        borderColor: kind === 'pie' ? '#ffffff' : undefined,
        borderWidth: kind === 'pie' ? 3 : undefined,
    }],
});

const toSeries = (rows, dateKey, valueKey, label) => ({
    labels: (rows || []).map((row) => row[dateKey].slice(5)),
    datasets: [{
        label,
        data: (rows || []).map((row) => row[valueKey]),
        borderColor: '#00aeef',
        backgroundColor: 'rgba(0, 174, 239, 0.15)',
        fill: true,
        tension: 0.35,
        pointBackgroundColor: '#07559e',
        pointRadius: 4,
    }],
});

export default defineComponent({
    name: 'AdminReports',
    components: { AdminIndexControls, AdminPagination, AdminShell, DashboardChart, Head, ReportPeriodFilter },
    props: {
        tab: { type: String, required: true },
        period: { type: Object, required: true },
        reportData: { type: Object, required: true },
        options: { type: Object, required: true },
    },
    setup(props) {
        const activeTab = useQueryTab(TABS, props.tab);
        const period = reactive({ ...props.period });
        const filters = reactive({ ...(props.reportData.filters || {}) });
        let searchTimer;

        const load = (overrides = {}) => {
            const query = {
                tab: activeTab.value,
                period: period.type,
                month: period.month,
                year: period.year,
                quarter: period.quarter,
                ...filters,
                ...overrides,
            };
            router.get('/admin/reports', query, { preserveState: true, preserveScroll: true, replace: true });
        };

        const switchTab = (tab) => {
            if (tab === activeTab.value) return;
            Object.keys(filters).forEach((key) => delete filters[key]);
            activeTab.value = tab;
            load({ tab });
        };

        const changePeriod = (next) => {
            Object.assign(period, next);
            load();
        };

        const search = () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(load, 350);
        };

        const page = (url) => {
            if (url) router.get(url, {}, { preserveScroll: true });
        };

        watch(() => props.reportData, (data) => {
            Object.assign(filters, data.filters || {});
        });
        watch(() => props.period, (next) => {
            Object.assign(period, next);
        });

        onBeforeUnmount(() => clearTimeout(searchTimer));

        // Demographic Profile
        const genderChart = computed(() => toBarOrPie(props.reportData.genderBreakdown, 'label', 'total', 'pie'));
        const ageBracketChart = computed(() => toBarOrPie(props.reportData.ageBracketBreakdown, 'label', 'total', 'bar'));
        const regionChart = computed(() => toBarOrPie(props.reportData.regionBreakdown, 'label', 'total', 'bar'));
        const clientTypeChart = computed(() => toBarOrPie(props.reportData.clientTypeBreakdown, 'label', 'total', 'pie'));
        const governmentCategoryChart = computed(() => toBarOrPie(props.reportData.governmentCategoryBreakdown, 'label', 'total', 'pie'));

        // Clients
        const clientServiceChart = computed(() => toBarOrPie(props.reportData.serviceBreakdown, 'label', 'total', 'bar'));
        const clientSourceChart = computed(() => toBarOrPie(props.reportData.sourceBreakdown, 'label', 'total', 'pie'));
        const clientMarketChart = computed(() => toBarOrPie(props.reportData.marketBreakdown, 'label', 'total', 'pie'));

        // Service Requests
        const statusChart = computed(() => toBarOrPie(props.reportData.statusBreakdown, 'label', 'total', 'pie'));
        const requestServiceChart = computed(() => toBarOrPie(props.reportData.serviceBreakdown, 'label', 'total', 'bar'));
        const requestTrendChart = computed(() => toSeries(props.reportData.trend, 'date', 'total', 'Requests'));

        // Feedback Responses
        const dimensionChart = computed(() => toBarOrPie(props.reportData.perDimension, 'name', 'average', 'bar'));
        const criterionChart = computed(() => toBarOrPie(props.reportData.perCriterion, 'description', 'average', 'bar'));
        const ratingDistributionChart = computed(() => toBarOrPie(props.reportData.ratingDistribution, 'label', 'count', 'pie'));

        // Site Visitors
        const newVisitorsChart = computed(() => toSeries(props.reportData.newVisitorsSeries, 'date', 'total', 'New visitors'));
        const activeVisitorsChart = computed(() => toBarOrPie(
            (props.reportData.activeVisitorsSeries || []).map((point) => ({ label: point.date.slice(5), total: point.total })),
            'label',
            'total',
            'bar',
        ));
        const providerChart = computed(() => toBarOrPie(props.reportData.providerBreakdown, 'provider', 'total', 'pie'));

        // Audit Trails
        const eventChart = computed(() => toBarOrPie(props.reportData.eventBreakdown, 'label', 'total', 'pie'));
        const auditTrendChart = computed(() => toBarOrPie(
            (props.reportData.trend || []).map((point) => ({ label: point.date.slice(5), total: point.total })),
            'label',
            'total',
            'bar',
        ));

        return {
            activeVisitorsChart,
            activeTab,
            ageBracketChart,
            auditTrendChart,
            barOptions,
            changePeriod,
            clientMarketChart,
            clientServiceChart,
            clientSourceChart,
            clientTypeChart,
            criterionChart,
            dimensionChart,
            eventChart,
            filters,
            genderChart,
            governmentCategoryChart,
            horizontalBarOptions,
            lineOptions,
            load,
            newVisitorsChart,
            page,
            period,
            pieOptions,
            providerChart,
            ratingDistributionChart,
            regionChart,
            requestServiceChart,
            requestTrendChart,
            search,
            statusChart,
            switchTab,
            TAB_LABELS,
            TABS,
        };
    },
    template: `
        <Head title="Reports" />
        <AdminShell active="reports" title="Reports">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Reports module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ TAB_LABELS[activeTab] }}</h2>
                        <p class="mt-1 text-slate-600">Reporting for {{ period.label }}.</p>
                    </div>
                    <ReportPeriodFilter :period="period" @change="changePeriod" />
                </div>
                <div class="mt-7 flex flex-wrap gap-2 border-b border-slate-200">
                    <button v-for="key in TABS" :key="key" type="button"
                        class="border-b-2 px-4 py-3 text-sm font-bold transition"
                        :class="activeTab === key ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'"
                        @click="switchTab(key)">{{ TAB_LABELS[key] }}</button>
                </div>
            </section>

            <template v-if="activeTab === 'demographics'">
                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Filters</p>
                    <div class="mt-4 flex flex-wrap items-end gap-4">
                        <label class="text-sm font-semibold text-slate-700">Gender
                            <select v-model="filters.gender" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="load">
                                <option value="">All</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Age bracket
                            <select v-model="filters.age_bracket" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="load">
                                <option value="">All</option>
                                <option value="less than 20 yrs old">Less than 20 yrs old</option>
                                <option value="21-30 yrs old">21-30 yrs old</option>
                                <option value="31-50 yrs old">31-50 yrs old</option>
                                <option value="51-59 yrs old">51-59 yrs old</option>
                                <option value="60 yrs old and above">60 yrs old and above</option>
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Client type
                            <select v-model="filters.type_client" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="load">
                                <option value="">All</option>
                                <option v-for="option in options.clientTypes" :key="option.value" :value="option.value">{{ option.label }}</option>
                            </select>
                        </label>
                        <label class="text-sm font-semibold text-slate-700">Region
                            <input v-model="filters.region" placeholder="e.g. Region IV-A" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="load" />
                        </label>
                    </div>
                </section>

                <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-slate-500">Total clients</p>
                        <p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.total }}</p>
                    </article>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-2">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Gender distribution</h3>
                        <div class="mt-6"><DashboardChart v-if="genderChart.labels.length" chart-type="pie" :chart-data="genderChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data for this period.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Age bracket distribution</h3>
                        <div class="mt-6"><DashboardChart v-if="ageBracketChart.labels.length" chart-type="bar" :chart-data="ageBracketChart" :options="barOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data for this period.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Top regions</h3>
                        <div class="mt-6"><DashboardChart v-if="regionChart.labels.length" chart-type="bar" :chart-data="regionChart" :options="horizontalBarOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data for this period.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Client type</h3>
                        <div class="mt-6"><DashboardChart v-if="clientTypeChart.labels.length" chart-type="pie" :chart-data="clientTypeChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data for this period.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7 xl:col-span-2">
                        <h3 class="text-lg font-bold text-slate-900">Government-to-X category</h3>
                        <p class="mt-1 text-sm text-slate-600">Derived from client type: Government &rarr; G2G, Individual &rarr; G2C, all others &rarr; G2B.</p>
                        <div class="mt-6"><DashboardChart v-if="governmentCategoryChart.labels.length" chart-type="pie" :chart-data="governmentCategoryChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data for this period.</p></div>
                    </article>
                </section>
            </template>

            <template v-else-if="activeTab === 'clients'">
                <section class="mt-6 grid gap-4 sm:grid-cols-3">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Total in period</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.total }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Active</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.active }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Archived</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.archived }}</p></article>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-3">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">By service</h3>
                        <div class="mt-6"><DashboardChart v-if="clientServiceChart.labels.length" chart-type="bar" :chart-data="clientServiceChart" :options="horizontalBarOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">By source</h3>
                        <div class="mt-6"><DashboardChart v-if="clientSourceChart.labels.length" chart-type="pie" :chart-data="clientSourceChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">By market</h3>
                        <div class="mt-6"><DashboardChart v-if="clientMarketChart.labels.length" chart-type="pie" :chart-data="clientMarketChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data.</p></div>
                    </article>
                </section>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" search-placeholder="Search name, email, mobile, organization" @search="search">
                        <template #filters>
                            <select v-model="filters.type_client" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">All client types</option><option v-for="o in options.clientTypes" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                            <select v-model="filters.service" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">All services</option><option v-for="o in options.services" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                            <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="active">Active</option><option value="archived">Archived</option></select>
                        </template>
                    </AdminIndexControls>
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left">
                            <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-4">Name</th><th class="px-4 py-4">Type</th><th class="px-4 py-4">Government category</th><th class="px-4 py-4">Service</th><th class="px-4 py-4">Region</th><th class="px-4 py-4">Registered</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="client in reportData.clients.data" :key="client.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ client.fullname }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ client.type }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ client.government_category }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ client.service }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ client.region }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ client.created_at }}</td>
                                </tr>
                                <tr v-if="reportData.clients.data.length === 0"><td colspan="6" class="px-4 py-12 text-center text-slate-500">No clients match the selected filters.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination :pagination="reportData.clients" @page="page" />
                </section>
            </template>

            <template v-else-if="activeTab === 'service-requests'">
                <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Total</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.total }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Walk-ins</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.walkIns }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Appointments</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.appointments }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Completed</p><p class="mt-3 text-3xl font-bold text-emerald-700">{{ reportData.stats.completed }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Cancelled</p><p class="mt-3 text-3xl font-bold text-rose-700">{{ reportData.stats.cancelled }}</p></article>
                </section>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <h3 class="text-lg font-bold text-slate-900">Requests over time</h3>
                    <div class="mt-6"><DashboardChart chart-type="line" :chart-data="requestTrendChart" :options="lineOptions" /></div>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-2">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">By status</h3>
                        <div class="mt-6"><DashboardChart chart-type="pie" :chart-data="statusChart" :options="pieOptions" /></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">By service</h3>
                        <div class="mt-6"><DashboardChart v-if="requestServiceChart.labels.length" chart-type="bar" :chart-data="requestServiceChart" :options="horizontalBarOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No data.</p></div>
                    </article>
                </section>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" search-placeholder="Search description, client name, or email" @search="search">
                        <template #filters>
                            <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">All statuses</option><option v-for="o in options.statuses" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                            <select v-model="filters.service" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">All services</option><option v-for="o in options.services" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                            <select v-model="filters.type" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">Walk-in &amp; appointment</option><option value="walk-in">Walk-in</option><option value="appointment">Appointment</option></select>
                            <select v-model="filters.government_category" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">G2C / G2B / G2G</option><option v-for="o in options.governmentCategories" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                        </template>
                    </AdminIndexControls>
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[800px] text-left">
                            <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-4">Client</th><th class="px-4 py-4">Service</th><th class="px-4 py-4">Type</th><th class="px-4 py-4">Status</th><th class="px-4 py-4">Government category</th><th class="px-4 py-4">Date</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in reportData.requests.data" :key="item.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ item.client }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ item.service }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ item.type }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ item.status }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ item.government_category }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ item.created_at }}</td>
                                </tr>
                                <tr v-if="reportData.requests.data.length === 0"><td colspan="6" class="px-4 py-12 text-center text-slate-500">No requests match the selected filters.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination :pagination="reportData.requests" @page="page" />
                </section>
            </template>

            <template v-else-if="activeTab === 'feedback'">
                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div class="flex flex-wrap items-end gap-4">
                        <select v-model="filters.service" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">All services</option><option v-for="o in options.services" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                        <select v-model="filters.government_category" class="rounded-lg border border-slate-300 px-3 py-2" @change="load"><option value="">G2C / G2B / G2G</option><option v-for="o in options.governmentCategories" :key="o.value" :value="o.value">{{ o.label }}</option></select>
                    </div>
                </section>

                <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Responses</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.totalResponses }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Response rate</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.responseRate !== null ? reportData.stats.responseRate + '%' : '—' }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Total weighted score</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.overallScore ?? '—' }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Interpretation</p><p class="mt-3 text-3xl font-bold text-[#07559e]">{{ reportData.stats.interpretation ?? '—' }}</p></article>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-2">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Average score per dimension</h3>
                        <div class="mt-6"><DashboardChart v-if="dimensionChart.labels.length" chart-type="bar" :chart-data="dimensionChart" :options="horizontalBarOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No responses for this period.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Rating distribution</h3>
                        <div class="mt-6"><DashboardChart v-if="ratingDistributionChart.labels.length" chart-type="pie" :chart-data="ratingDistributionChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No responses for this period.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7 xl:col-span-2">
                        <h3 class="text-lg font-bold text-slate-900">Average score per criterion</h3>
                        <div class="mt-6"><DashboardChart v-if="criterionChart.labels.length" chart-type="bar" :chart-data="criterionChart" :options="horizontalBarOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No responses for this period.</p></div>
                    </article>
                </section>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <h3 class="text-lg font-bold text-slate-900">Responses</h3>
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[700px] text-left">
                            <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-4">Client</th><th class="px-4 py-4">Service</th><th class="px-4 py-4">Government category</th><th class="px-4 py-4">Score</th><th class="px-4 py-4">Submitted</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="item in reportData.responses.data" :key="item.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ item.client }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ item.service }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ item.government_category }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ item.score ?? '—' }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ item.submitted_at }}</td>
                                </tr>
                                <tr v-if="reportData.responses.data.length === 0"><td colspan="5" class="px-4 py-12 text-center text-slate-500">No feedback responses for the selected filters.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination :pagination="reportData.responses" @page="page" />
                </section>
            </template>

            <template v-else-if="activeTab === 'site-visitors'">
                <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Total visitors (all-time)</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.totalVisitors }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Total visits (all-time)</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.totalVisits }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">New this period</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.newInPeriod }}</p></article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Active this period</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.activeInPeriod }}</p></article>
                </section>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <h3 class="text-lg font-bold text-slate-900">New visitors per day</h3>
                    <div class="mt-6"><DashboardChart chart-type="line" :chart-data="newVisitorsChart" :options="lineOptions" /></div>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-2">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Active visitors per day</h3>
                        <div class="mt-6"><DashboardChart chart-type="bar" :chart-data="activeVisitorsChart" :options="barOptions" /></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">By network provider</h3>
                        <div class="mt-6"><DashboardChart v-if="providerChart.labels.length" chart-type="pie" :chart-data="providerChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No visitor data for this period.</p></div>
                    </article>
                </section>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" search-placeholder="Search IP address, provider, or user agent" @search="search">
                        <template #filters>
                            <input v-model="filters.date" type="date" class="rounded-lg border border-slate-300 px-3 py-2" @change="load" />
                        </template>
                    </AdminIndexControls>
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left">
                            <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-4">IP address</th><th class="px-4 py-4">Provider</th><th class="px-4 py-4">User agent</th><th class="px-4 py-4">Total visits</th><th class="px-4 py-4">First seen</th><th class="px-4 py-4">Last seen</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="visitor in reportData.visitors.data" :key="visitor.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ visitor.ip_address }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ visitor.provider || '—' }}</td>
                                    <td class="max-w-xs truncate px-4 py-4 text-sm text-slate-500" :title="visitor.user_agent">{{ visitor.user_agent || '—' }}</td>
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-700">{{ visitor.total_visits }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ visitor.first_seen }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ visitor.last_seen }}</td>
                                </tr>
                                <tr v-if="reportData.visitors.data.length === 0"><td colspan="6" class="px-4 py-12 text-center text-slate-500">No site visitors match the selected filters.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination :pagination="reportData.visitors" @page="page" />
                </section>
            </template>

            <template v-else-if="activeTab === 'audit-trails'">
                <section class="mt-6 grid gap-4 sm:grid-cols-1">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">Total events this period</p><p class="mt-3 text-3xl font-bold text-slate-900">{{ reportData.stats.total }}</p></article>
                </section>

                <section class="mt-6 grid gap-6 xl:grid-cols-2">
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Events by type</h3>
                        <div class="mt-6"><DashboardChart v-if="eventChart.labels.length" chart-type="pie" :chart-data="eventChart" :options="pieOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No events for this period.</p></div>
                    </article>
                    <article class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                        <h3 class="text-lg font-bold text-slate-900">Events over time</h3>
                        <div class="mt-6"><DashboardChart v-if="auditTrendChart.labels.length" chart-type="bar" :chart-data="auditTrendChart" :options="barOptions" /><p v-else class="py-10 text-center text-sm text-slate-500">No events for this period.</p></div>
                    </article>
                </section>

                <section class="mt-6 border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <AdminIndexControls v-model:entries="filters.entries" v-model:search="filters.search" search-placeholder="Search subject, user, or record id" @search="search">
                        <template #filters>
                            <select v-model="filters.event" class="rounded-lg border border-slate-300 px-3 py-2" @change="load">
                                <option value="">All events</option>
                                <option v-for="event in reportData.events" :key="event" :value="event">{{ event }}</option>
                            </select>
                            <input v-model="filters.date" type="date" class="rounded-lg border border-slate-300 px-3 py-2" @change="load" />
                        </template>
                    </AdminIndexControls>
                    <div class="mt-6 overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left">
                            <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-4">Event</th><th class="px-4 py-4">User</th><th class="px-4 py-4">Subject</th><th class="px-4 py-4">IP address</th><th class="px-4 py-4">Date</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="audit in reportData.audits.data" :key="audit.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                    <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ audit.event }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ audit.user }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-700">{{ audit.model }} #{{ audit.model_id }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ audit.ip_address }}</td>
                                    <td class="px-4 py-4 text-sm text-slate-500">{{ audit.created_at }}</td>
                                </tr>
                                <tr v-if="reportData.audits.data.length === 0"><td colspan="5" class="px-4 py-12 text-center text-slate-500">No audit events match the selected filters.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <AdminPagination :pagination="reportData.audits" @page="page" />
                </section>
            </template>
        </AdminShell>
    `,
});
