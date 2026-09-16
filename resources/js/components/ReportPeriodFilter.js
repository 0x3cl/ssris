import { computed, defineComponent } from 'vue';

/**
 * Shared month/quarter/year period picker used across every Reports tab.
 * Fully controlled: the parent owns the period state and reloads on 'change'.
 */
export default defineComponent({
    name: 'ReportPeriodFilter',
    emits: ['change'],
    props: {
        period: { type: Object, required: true },
    },
    setup(props, { emit }) {
        const setType = (type) => emit('change', { ...props.period, type });

        const formattedMonth = computed(() => new Intl.DateTimeFormat('en-US', {
            month: 'long',
            year: 'numeric',
        }).format(new Date(`${props.period.month}-01T00:00:00`)));

        const moveMonth = (offset) => {
            const [year, month] = props.period.month.split('-').map(Number);
            const date = new Date(year, month - 1 + offset, 1);
            emit('change', { ...props.period, month: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}` });
        };

        const changeMonth = (value) => emit('change', { ...props.period, month: value });

        const moveQuarter = (offset) => {
            let quarterIndex = (props.period.year * 4) + (props.period.quarter - 1) + offset;
            const year = Math.floor(quarterIndex / 4);
            const quarter = (quarterIndex % 4) + 1;
            emit('change', { ...props.period, year, quarter });
        };

        const changeYear = (value) => emit('change', { ...props.period, year: Number(value) });
        const changeQuarter = (value) => emit('change', { ...props.period, quarter: Number(value) });
        const moveYear = (offset) => emit('change', { ...props.period, year: props.period.year + offset });

        return { changeMonth, changeQuarter, changeYear, formattedMonth, moveMonth, moveQuarter, moveYear, setType };
    },
    template: `
        <div class="flex flex-wrap items-end gap-3">
            <div class="inline-flex rounded-lg border border-slate-300 bg-white p-1 shadow-sm">
                <button v-for="option in ['month', 'quarter', 'year']" :key="option" type="button"
                    class="rounded-md px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition"
                    :class="period.type === option ? 'bg-[#07559e] text-white' : 'text-slate-500 hover:text-slate-900'"
                    @click="setType(option)">{{ option }}</button>
            </div>

            <div v-if="period.type === 'month'" class="flex items-end gap-3">
                <div class="inline-flex h-12 items-center rounded-lg border border-slate-300 bg-white shadow-sm">
                    <button type="button" class="flex h-full w-10 items-center justify-center border-r border-slate-200 text-[#07559e] hover:bg-sky-50" aria-label="Previous month" @click="moveMonth(-1)"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                    <span class="flex min-w-40 justify-center px-4 text-center text-sm font-bold text-slate-700">{{ formattedMonth }}</span>
                    <button type="button" class="flex h-full w-10 items-center justify-center border-l border-slate-200 text-[#07559e] hover:bg-sky-50" aria-label="Next month" @click="moveMonth(1)"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                </div>
                <input :value="period.month" type="month" class="h-12 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" @change="changeMonth($event.target.value)" />
            </div>

            <div v-else-if="period.type === 'quarter'" class="flex items-end gap-3">
                <div class="inline-flex h-12 items-center rounded-lg border border-slate-300 bg-white shadow-sm">
                    <button type="button" class="flex h-full w-10 items-center justify-center border-r border-slate-200 text-[#07559e] hover:bg-sky-50" aria-label="Previous quarter" @click="moveQuarter(-1)"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                    <span class="flex min-w-32 justify-center px-4 text-center text-sm font-bold text-slate-700">Q{{ period.quarter }} {{ period.year }}</span>
                    <button type="button" class="flex h-full w-10 items-center justify-center border-l border-slate-200 text-[#07559e] hover:bg-sky-50" aria-label="Next quarter" @click="moveQuarter(1)"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                </div>
                <select :value="period.quarter" class="h-12 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" @change="changeQuarter($event.target.value)">
                    <option v-for="q in [1, 2, 3, 4]" :key="q" :value="q">Q{{ q }}</option>
                </select>
                <input :value="period.year" type="number" class="h-12 w-24 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" @change="changeYear($event.target.value)" />
            </div>

            <div v-else class="flex items-end gap-3">
                <div class="inline-flex h-12 items-center rounded-lg border border-slate-300 bg-white shadow-sm">
                    <button type="button" class="flex h-full w-10 items-center justify-center border-r border-slate-200 text-[#07559e] hover:bg-sky-50" aria-label="Previous year" @click="moveYear(-1)"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
                    <span class="flex min-w-24 justify-center px-4 text-center text-sm font-bold text-slate-700">{{ period.year }}</span>
                    <button type="button" class="flex h-full w-10 items-center justify-center border-l border-slate-200 text-[#07559e] hover:bg-sky-50" aria-label="Next year" @click="moveYear(1)"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
                </div>
                <input :value="period.year" type="number" class="h-12 w-24 rounded-lg border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" @change="changeYear($event.target.value)" />
            </div>
        </div>
    `,
});
