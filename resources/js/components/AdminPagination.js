import { defineComponent } from 'vue';

export default defineComponent({
    name: 'AdminPagination',
    emits: ['page'],
    props: { pagination: { type: Object, required: true } },
    template: `
        <footer class="mt-6 flex flex-wrap items-center justify-between gap-4 text-sm text-slate-600">
            <p>Showing {{ pagination.from ?? 0 }}–{{ pagination.to ?? 0 }} of {{ pagination.total }} entries</p>
            <nav class="flex gap-2" aria-label="Pagination"><button v-for="link in pagination.links" :key="link.label" type="button" :disabled="!link.url || link.active" class="rounded-lg border px-3 py-2" :class="link.active ? 'border-[#00aeef] bg-sky-50 font-bold text-[#07559e]' : 'border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40'" @click="$emit('page', link.url)"><span v-html="link.label"></span></button></nav>
        </footer>
    `,
});
