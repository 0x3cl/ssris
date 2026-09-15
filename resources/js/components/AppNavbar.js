import { defineComponent } from 'vue';

export default defineComponent({
    name: 'AppNavbar',
    template: `
        <header class="relative z-20 border-t-7 border-slate-800 bg-linear-to-r from-[#0d5ba6] via-[#07559e] to-[#043d78] py-4 text-white shadow-lg">
            <nav class="mx-auto flex min-h-20 max-w-[1800px] flex-col items-center justify-between gap-5 px-5 py-3 sm:px-8 md:flex-row lg:px-12" aria-label="PTRI header">
                <a href="/" class="flex min-w-0 items-center gap-3" aria-label="PTRI home">
                    <img src="https://hrms.dost-ptri.com/images/ptrionlywhite.png" alt="DOST-PTRI" class="h-14 w-14 shrink-0 object-contain" />
                    <div class="min-w-0 leading-tight"><p class="text-[11px] font-medium text-sky-100 sm:text-xs">Republic of the Philippines</p><p class="text-[11px] font-medium text-sky-100 sm:text-xs">Department of Science and Technology</p><p class="mt-1 text-sm font-bold tracking-tight sm:text-base">PHILIPPINE TEXTILE RESEARCH INSTITUTE</p></div>
                </a>
                <div class="border-t border-white/20 pt-4 text-center md:border-t-0 md:border-l md:pl-8 md:pt-0 md:text-right">
                    <p class="text-xs font-medium tracking-[0.18em] text-sky-200 uppercase">DOST-PTRI</p>
                    <p class="mt-1 text-lg font-semibold tracking-tight sm:text-xl">Service Requests Information System</p>
                </div>
            </nav>
        </header>
    `,
});
