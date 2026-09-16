import { defineComponent } from 'vue';

export default defineComponent({
    props: { request: { type: Object, required: true } },
    template: `
        <a :href="request.url" class="group block min-w-0 rounded-xl border border-slate-200 bg-white p-5 transition hover:border-[#07559e] hover:shadow-md focus-visible:ring-2 focus-visible:ring-[#07559e]">
            <div class="flex items-center justify-between gap-3">
                <i class="fa-solid fa-folder text-4xl text-[#07559e]" aria-hidden="true"></i>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ request.status }}</span>
            </div>
            <h3 class="mt-5 font-bold text-slate-900">{{ request.service }}</h3>
            <p class="mt-1 text-sm text-slate-600">Request #{{ request.id }} · {{ request.type }}</p>
            <p class="mt-3 line-clamp-2 text-sm text-slate-500">{{ request.description }}</p>
            <div class="mt-5 flex justify-between border-t border-slate-100 pt-3 text-xs text-slate-500">
                <span>{{ request.date }}</span><span class="font-semibold text-[#07559e]">Open request →</span>
            </div>
        </a>
    `,
});
