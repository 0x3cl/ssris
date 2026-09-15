import { Head, router } from '@inertiajs/vue3';
import { computed, defineComponent, reactive, ref } from 'vue';

const blankClient = (service = '') => ({
    firstname: '',
    middlename: '',
    lastname: '',
    fullname: '',
    email: '',
    mobile_no: '',
    fax_no: '',
    age: '',
    gender: '',
    address: '',
    region: '',
    province: '',
    municipality: '',
    tel_no: '',
    type_client: '',
    company: '',
    school_name: '',
    business_role: '',
    enterprise_size: '',
    market: '',
    products: '',
    source: '',
    service,
    description: '',
});

export default defineComponent({
    name: 'WalkIn',
    components: { Head },
    props: {
        selectedService: {
            type: String,
            default: null,
        },
        services: {
            type: Array,
            required: true,
        },
        clientTypes: {
            type: Array,
            required: true,
        },
        businessRoles: {
            type: Array,
            required: true,
        },
        enterpriseSizes: {
            type: Array,
            required: true,
        },
        markets: {
            type: Array,
            required: true,
        },
        sources: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const selectedService = ref(props.selectedService);
        const email = ref('');
        const form = reactive(blankClient(props.selectedService));
        const currentStep = ref(1);
        const isLookingUp = ref(false);
        const lookupError = ref('');
        const showWelcome = ref(false);
        const returningClient = ref(false);
        const hasSelectedService = computed(() => Boolean(selectedService.value));

        const findClient = async () => {
            lookupError.value = '';
            isLookingUp.value = true;

            try {
                const response = await fetch(`/walk-in/client?email=${encodeURIComponent(email.value)}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();

                if (!response.ok) {
                    lookupError.value = data.errors?.email?.[0] ?? 'Enter a valid email address.';

                    return;
                }

                Object.assign(form, blankClient(selectedService.value), data.client ?? { email: email.value });
                form.service = selectedService.value;
                returningClient.value = Boolean(data.client);
                showWelcome.value = Boolean(data.client);
                currentStep.value = 2;
                window.history.pushState({}, '', `/walk-in?selected=${encodeURIComponent(selectedService.value)}`);
            } catch {
                lookupError.value = 'We could not check that email. Please try again.';
            } finally {
                isLookingUp.value = false;
            }
        };

        return {
            currentStep,
            email,
            findClient,
            form,
            hasSelectedService,
            isLookingUp,
            lookupError,
            returningClient,
            selectedService,
            showWelcome,
            submitWalkIn: () => router.post('/walk-in', form),
        };
    },
    template: `
        <Head title="Walk In" />

        <main class="min-h-screen bg-slate-50 px-4 py-10 sm:px-6">
            <section class="mx-auto w-full max-w-4xl">
                <a href="/" class="text-sm font-semibold text-sky-700 hover:text-sky-900">← Back to service options</a>

                <header class="mt-7">
                    <p class="text-sm font-semibold tracking-[0.2em] text-sky-700 uppercase">Walk In</p>
                    <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Start your service request</h1>
                    <p class="mt-2 text-slate-600">Choose a service, then confirm your client information.</p>
                </header>

                <ol class="mt-8 grid gap-3 sm:grid-cols-3" aria-label="Walk-in request steps">
                    <li class="rounded-xl border px-4 py-3" :class="currentStep >= 1 ? 'border-sky-200 bg-sky-50 text-sky-900' : 'border-slate-200 bg-white text-slate-600'">
                        <span class="font-semibold">1. Select service</span>
                    </li>
                    <li class="rounded-xl border px-4 py-3" :class="currentStep >= 2 ? 'border-sky-200 bg-sky-50 text-sky-900' : 'border-slate-200 bg-white text-slate-400'">
                        <span class="font-semibold">2. Client details</span>
                    </li>
                    <li class="rounded-xl border px-4 py-3" :class="currentStep === 3 ? 'border-sky-200 bg-sky-50 text-sky-900' : 'border-slate-200 bg-white text-slate-400'"><span class="font-semibold">3. Submit</span></li>
                </ol>

                <section v-if="currentStep === 1" class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <h2 class="text-xl font-semibold text-slate-900">Choose a service</h2>
                    <p class="mt-1 text-slate-600">Your choice will remain in the page URL when you continue.</p>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <button
                            v-for="service in services"
                            :key="service.value"
                            type="button"
                            class="rounded-2xl border p-4 text-left transition focus-visible:ring-4 focus-visible:ring-sky-200 focus-visible:outline-none"
                            :class="selectedService === service.value ? 'border-sky-600 bg-sky-50 ring-1 ring-sky-600' : 'border-slate-200 hover:border-sky-300 hover:bg-slate-50'"
                            @click="selectedService = service.value"
                        >
                            <img
                                src="https://unpkg.com/undraw-svg@1.0.0/svgs/booking.svg"
                                :alt="service.label + ' illustration'"
                                class="mb-3 h-20 w-full object-contain"
                            />
                            <span class="font-semibold text-slate-900">{{ service.label }}</span>
                        </button>
                    </div>

                    <form class="mt-8 flex flex-col gap-3 sm:flex-row" @submit.prevent="findClient"><label class="sr-only" for="client-email-lookup">Email address</label><input id="client-email-lookup" v-model="email" type="email" required autocomplete="email" placeholder="you@example.com" class="min-w-0 flex-1 rounded-xl border border-slate-300 px-4 py-3" /><button type="submit" :disabled="!selectedService || isLookingUp" class="rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white disabled:bg-slate-300">{{ isLookingUp ? 'Checking…' : 'Next' }}</button></form>
                    <p v-if="lookupError" class="mt-2 text-sm text-rose-700" role="alert">{{ lookupError }}</p>
                </section>

                <section v-else-if="currentStep === 2" class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Find your client record</h2>
                            <p class="mt-1 text-slate-600">Enter your email to save time if you have visited before.</p>
                        </div>
                        <button type="button" class="text-sm font-semibold text-sky-700 hover:text-sky-900" @click="currentStep = 1">Change service</button>
                    </div>

                    <form v-if="form.email" class="mt-8 border-t border-slate-200 pt-8" @submit.prevent="currentStep = 3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">Client information</h2>
                                <p class="mt-1 text-slate-600">{{ returningClient ? 'Review and update your details if needed.' : 'Complete all required fields to continue.' }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-sm font-semibold" :class="returningClient ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'">{{ returningClient ? 'Returning client' : 'New client' }}</span>
                        </div>

                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <label class="block"><span class="text-sm font-medium text-slate-700">First name</span><input v-model="form.firstname" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Middle name</span><input v-model="form.middlename" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Last name</span><input v-model="form.lastname" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Full name</span><input v-model="form.fullname" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Email</span><input v-model="form.email" type="email" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Mobile number</span><input v-model="form.mobile_no" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Fax number</span><input v-model="form.fax_no" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Age</span><input v-model="form.age" type="number" min="0" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Gender</span><input v-model="form.gender" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Telephone number</span><input v-model="form.tel_no" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block sm:col-span-2"><span class="text-sm font-medium text-slate-700">Address</span><input v-model="form.address" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Region</span><input v-model="form.region" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Province</span><input v-model="form.province" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Municipality</span><input v-model="form.municipality" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Client type</span><select v-model="form.type_client" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="">Select type</option><option v-for="type in clientTypes" :key="type.value" :value="type.value">{{ type.label }}</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Company</span><input v-model="form.company" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">School name</span><input v-model="form.school_name" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Business role</span><select v-model="form.business_role" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="">Not applicable</option><option v-for="role in businessRoles" :key="role.value" :value="role.value">{{ role.label }}</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Enterprise size</span><select v-model="form.enterprise_size" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="">Not applicable</option><option v-for="size in enterpriseSizes" :key="size.value" :value="size.value">{{ size.label }}</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Market</span><select v-model="form.market" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="">Not applicable</option><option v-for="market in markets" :key="market.value" :value="market.value">{{ market.label }}</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Products</span><input v-model="form.products" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Source</span><select v-model="form.source" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option value="">Select source</option><option v-for="source in sources" :key="source.value" :value="source.value">{{ source.label }}</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700">Selected service</span><select v-model="form.service" required class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"><option v-for="service in services" :key="service.value" :value="service.value">{{ service.label }}</option></select></label>
                            <label class="block sm:col-span-2"><span class="text-sm font-medium text-slate-700">Description</span><textarea v-model="form.description" required rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea></label>
                        </div>
                        <div class="mt-8 flex justify-end"><button type="submit" class="rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white">Review request</button></div>
                    </form>
                </section>

                <section v-else class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8"><h2 class="text-xl font-semibold text-slate-900">Ready to submit?</h2><p class="mt-2 text-slate-600">Your walk-in request for <strong>{{ form.service }}</strong> will be submitted with the client details shown in the previous step.</p><div class="mt-8 flex justify-between gap-3"><button type="button" class="rounded-xl px-5 py-3 font-semibold text-sky-700" @click="currentStep = 2">Back</button><button type="button" class="rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white" @click="submitWalkIn">Submit walk-in request</button></div></section>
            </section>

            <div v-if="showWelcome" class="fixed inset-0 z-10 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="welcome-title">
                <div class="w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl" aria-hidden="true">✓</div>
                    <h2 id="welcome-title" class="mt-5 text-2xl font-semibold text-slate-900">Welcome back, {{ form.firstname }}!</h2>
                    <p class="mt-2 text-slate-600">We found your client record and filled in your details. Please review them before continuing.</p>
                    <button type="button" class="mt-6 w-full rounded-xl bg-sky-700 px-5 py-3 font-semibold text-white hover:bg-sky-800" @click="showWelcome = false">Review my details</button>
                </div>
            </div>
        </main>
    `,
});
