import { Head } from '@inertiajs/vue3';
import { defineComponent } from 'vue';

export default defineComponent({
    name: 'Index',
    components: { Head },
    template: `
        <Head title="Choose a service" />

        <main class="flex min-h-screen items-center justify-center bg-slate-50 px-6 py-12">
            <section class="w-full max-w-5xl">
                <div class="mx-auto max-w-xl text-center">
                    <p class="text-sm font-semibold tracking-[0.2em] text-sky-700 uppercase">Service request</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">How would you like to continue?</h1>
                    <p class="mt-3 text-base text-slate-600">Choose the option that best fits your visit.</p>
                </div>

                <div class="mt-10 grid gap-6 md:grid-cols-2">
                    <a
                        href="/walk-in"
                        class="group flex min-h-96 flex-col items-center rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm transition hover:-translate-y-1 hover:border-sky-300 hover:shadow-xl focus-visible:ring-4 focus-visible:ring-sky-200 focus-visible:outline-none"
                    >
                        <img
                            src="https://unpkg.com/undraw-svg@1.0.0/svgs/walk-in-the-city.svg"
                            alt="Person walking into the city"
                            class="h-44 w-full max-w-xs object-contain"
                        >
                        <h2 class="mt-6 text-2xl font-semibold text-slate-900">Walk In</h2>
                        <p class="mt-2 text-slate-600">Visit us today without scheduling ahead.</p>
                        <span class="mt-6 font-semibold text-sky-700 group-hover:text-sky-800">Continue <span aria-hidden="true">→</span></span>
                    </a>

                    <a
                        href="/book-an-appointment"
                        class="group flex min-h-96 flex-col items-center rounded-3xl border border-slate-200 bg-white p-8 text-center shadow-sm transition hover:-translate-y-1 hover:border-violet-300 hover:shadow-xl focus-visible:ring-4 focus-visible:ring-violet-200 focus-visible:outline-none"
                    >
                        <img
                            src="https://unpkg.com/undraw-svg@1.0.0/svgs/schedule.svg"
                            alt="Calendar schedule"
                            class="h-44 w-full max-w-xs object-contain"
                        >
                        <h2 class="mt-6 text-2xl font-semibold text-slate-900">Book Appointment</h2>
                        <p class="mt-2 text-slate-600">Reserve a date and time that works for you.</p>
                        <span class="mt-6 font-semibold text-violet-700 group-hover:text-violet-800">Continue <span aria-hidden="true">→</span></span>
                    </a>
                </div>
            </section>
        </main>
    `,
});
