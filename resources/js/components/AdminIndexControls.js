import { Link } from '@inertiajs/vue3';
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'AdminIndexControls',
    components: { Link },
    emits: ['update:search', 'update:entries', 'search'],
    props: {
        addHref: { type: String, default: '' },
        addLabel: { type: String, default: '' },
        entries: { type: Number, required: true },
        search: { type: String, required: true },
        searchPlaceholder: { type: String, required: true },
    },
    template: `
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-wrap items-end gap-4">
                <label class="text-sm font-semibold text-slate-700">Entries
                    <select :value="entries" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="$emit('update:entries', Number($event.target.value)); $emit('search')">
                        <option :value="10">10</option><option :value="25">25</option><option :value="50">50</option>
                    </select>
                </label>
                <slot name="filters" />
            </div>
            <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                <label class="relative min-w-0 sm:w-80"><span class="sr-only">Search</span><i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i><input :value="search" class="w-full rounded-lg border border-slate-300 py-2.5 pl-11 pr-4 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :placeholder="searchPlaceholder" @input="$emit('update:search', $event.target.value); $emit('search')" /></label>
                <Link v-if="addHref" :href="addHref" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00aeef] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#009bd8]"><i class="fa-solid fa-plus" aria-hidden="true"></i>{{ addLabel }}</Link>
            </div>
        </div>
    `,
});
