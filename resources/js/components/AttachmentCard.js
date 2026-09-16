import { defineComponent } from 'vue';

export default defineComponent({
    name: 'AttachmentCard',
    props: {
        href: { type: String, default: null },
        name: { type: String, required: true },
    },
    template: `
        <a v-if="href" :href="href" :aria-label="'Download ' + name" class="group flex w-full items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-[#07559e] hover:bg-sky-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#07559e] focus-visible:ring-offset-2">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-xl text-[#07559e]">
                <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block break-words text-sm font-semibold text-slate-900">{{ name }}</span>
                <span class="mt-1 block text-xs text-slate-500">Payment supporting document</span>
            </span>
            <span class="flex shrink-0 items-center gap-2 text-sm font-semibold text-[#07559e]">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
                <span class="hidden sm:inline">Download</span>
            </span>
        </a>
        <div v-else class="flex w-full items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xl text-slate-400">
                <i class="fa-solid fa-file-circle-xmark" aria-hidden="true"></i>
            </span>
            <span class="min-w-0 flex-1">
                <span class="block break-words text-sm font-semibold text-slate-500">{{ name }}</span>
                <span class="mt-1 block text-xs text-slate-400">No attachment uploaded</span>
            </span>
        </div>
    `,
});
