import { Head, Link } from '@inertiajs/vue3';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { defineComponent, onMounted } from 'vue';
import PrivacyNoticeCard from '../components/PrivacyNoticeCard';

export default defineComponent({
    name: 'Index',
    components: { Head, Link, PrivacyNoticeCard },
    setup() {
        onMounted(() => {
            AOS.init({ duration: 600, once: true });
        });
    },
    template: `
        <Head title="Choose a service" />

        <main class="bg-slate-50 px-5 py-10 sm:px-8 sm:py-14 lg:px-12">
            <section class="mx-auto grid w-full max-w-[1800px] gap-10 lg:grid-cols-[1.1fr_0.9fr] xl:gap-14 lg:items-stretch">
                <aside data-aos="fade-right" class="overflow-hidden rounded-3xl shadow-sm">
                    <img src="/assets/landing/left-panel-bg.png" alt="Registration Information System: PTRI services for laboratory testing and analysis, textile processing, technical training, and facility tours" class="h-full w-full object-cover" />
                </aside>

                <div class="flex flex-col justify-center">
                    <p class="text-sm font-semibold tracking-[0.2em] text-sky-700 uppercase">Service request</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">How would you like to continue?</h2>
                    <p class="mt-3 text-base text-slate-600">Choose the option that best fits your visit.</p>
                    <div class="mt-8 flex flex-col gap-5 md:grid md:grid-cols-2">
                    <Link
                        href="/walk-in"
                        data-aos="fade-up"
                        class="group flex min-h-80 flex-col items-center rounded-3xl border border-[#c8e0f4] bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-[#0d5ba6] hover:shadow-xl focus-visible:ring-4 focus-visible:ring-[#bfe5ff] focus-visible:outline-none"
                    >
                        <div class="flex h-44 w-full items-center justify-center p-4"><img src="/assets/undraw/walk-in-the-city.svg" alt="Person walking into the city" class="h-full w-full max-w-xs object-contain" /></div>
                        <h2 class="mt-6 text-2xl font-semibold text-slate-900 uppercase">Walk In</h2>
                        <p class="mt-2 text-slate-600">Visit us today without scheduling ahead.</p>
                        <span class="mt-6 font-semibold text-[#07559e] group-hover:text-[#043d78]">Continue <span aria-hidden="true">→</span></span>
                    </Link>

                    <Link
                        href="/book-an-appointment"
                        data-aos="fade-up"
                        data-aos-delay="100"
                        class="group flex min-h-80 flex-col items-center rounded-3xl border border-[#c8e0f4] bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-[#0d5ba6] hover:shadow-xl focus-visible:ring-4 focus-visible:ring-[#bfe5ff] focus-visible:outline-none"
                    >
                        <div class="flex h-44 w-full items-center justify-center p-4"><img src="/assets/undraw/schedule.svg" alt="Calendar schedule" class="h-full w-full max-w-xs object-contain" /></div>
                        <h2 class="mt-6 text-2xl font-semibold text-slate-900 uppercase">Book Appointment</h2>
                        <p class="mt-2 text-slate-600">Reserve a date and time that works for you.</p>
                        <span class="mt-6 font-semibold text-[#07559e] group-hover:text-[#043d78]">Continue <span aria-hidden="true">→</span></span>
                    </Link>

                    <a
                        href="https://www.lbp-eservices.com/egps/portal/index.jsp"
                        target="_blank"
                        rel="noopener noreferrer"
                        data-aos="fade-up"
                        data-aos-delay="200"
                        class="group flex flex-col items-center gap-4 rounded-3xl border border-[#c8e0f4] bg-white p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-[#0d5ba6] hover:shadow-xl focus-visible:ring-4 focus-visible:ring-[#bfe5ff] focus-visible:outline-none sm:min-h-60 sm:flex-row sm:gap-6 sm:px-8 sm:text-left md:col-span-2"
                    >
                        <div class="flex h-44 w-full items-center justify-center p-4 sm:h-32 sm:w-44 sm:shrink-0 sm:rounded-2xl sm:p-5">
                            <img
                                src="/assets/landbank.png"
                                alt="LANDBANK logo"
                                class="h-full w-full max-w-xs object-contain"
                            >
                        </div>
                        <div><h2 class="text-xl font-semibold text-slate-900 sm:text-2xl">LANDBANK E-Payment Facility</h2><p class="mt-2 text-slate-600">Continue to the official LANDBANK online payment portal.</p><span class="mt-5 block font-semibold text-[#07559e] group-hover:text-[#043d78]">Open payment portal <span aria-hidden="true">↗</span></span></div>
                    </a>
                    </div>
                </div>
            </section>
            <PrivacyNoticeCard position="left" offset-class="left-5 sm:left-8 lg:left-12" />
        </main>
    `,
});
