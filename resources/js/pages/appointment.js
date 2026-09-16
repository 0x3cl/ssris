import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, defineComponent, nextTick, onMounted, reactive, ref, watch } from 'vue';
import ClientTypeModal from '../components/ClientTypeModal';
import EmailLookupModal from '../components/EmailLookupModal';
import FeedbackModal from '../components/FeedbackModal';
import IllustratedChoiceModal from '../components/IllustratedChoiceModal';
import LoadingModal from '../components/LoadingModal';
import PrivacyNoticeCard from '../components/PrivacyNoticeCard';
import ServiceCard from '../components/ServiceCard';
import SourceModal from '../components/SourceModal';
import TermsConditionsModal from '../components/TermsConditionsModal';
import AppointmentStepper from '../components/AppointmentStepper';
import { markRequiredFields } from '../utils/required-fields';
import { formatDate, formatTime } from '../utils/format-date';

const minAppointmentDate = (() => {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    return tomorrow.toISOString().slice(0, 10);
})();

const blankClient = (service = '') => ({
    appointment_date: '',
    appointment_time: '',
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
    terms_accepted: false,
});

const serviceIllustrations = {
    'rnd-services': '/assets/undraw/researching.svg',
    'lab-services': '/assets/undraw/science.svg',
    'processing-services': '/assets/undraw/data-processing.svg',
    'training-services': '/assets/undraw/teaching.svg',
    'plant-tour-services': '/assets/undraw/watering-plants.svg',
    'library-registration': '/assets/undraw/bookshelves.svg',
};

const clientTypeIllustrations = {
    academe: '/assets/undraw/teacher.svg',
    government: '/assets/undraw/data-reports.svg',
    'private-companies': '/assets/undraw/creation-process.svg',
    'non-government-organizations': '/assets/undraw/a-better-world.svg',
    individual: '/assets/undraw/about-me.svg',
};

const sourceIllustrations = {
    'ptri-website': '/assets/undraw/app-data.svg',
    internet: '/assets/undraw/data-at-work.svg',
    'newspaper-magazine': '/assets/undraw/reading-a-book.svg',
    referral: '/assets/undraw/a-better-world.svg',
};

const choiceIllustrationPaths = [
    'researching',
    'science',
    'data-processing',
    'teaching',
    'watering-plants',
    'bookshelves',
    'all-the-data',
    'data-reports',
];

const withIllustrations = (choices, offset = 0) => choices.map((choice, index) => ({
    ...choice,
    illustration: `/assets/undraw/${choiceIllustrationPaths[(index + offset) % choiceIllustrationPaths.length]}.svg`,
}));

export default defineComponent({
    name: 'Appointment',
    components: { ClientTypeModal, EmailLookupModal, FeedbackModal, Head, IllustratedChoiceModal, LoadingModal, PrivacyNoticeCard, ServiceCard, SourceModal, TermsConditionsModal, AppointmentStepper },
    props: {
        selectedService: {
            type: String,
            default: null,
        },
        selectedEmail: {
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
        const page = usePage();
        const selectedService = ref(props.selectedService);
        const email = ref(props.selectedEmail ?? '');
        const form = reactive(blankClient(props.selectedService));
        const currentStep = ref(1);
        const isLookingUp = ref(false);
        const lookupError = ref('');
        const showWelcome = ref(false);
        const showEmailModal = ref(false);
        const showClientTypeModal = ref(false);
        const showSourceModal = ref(false);
        const showTermsModal = ref(false);
        const showSubmissionSuccessModal = ref(false);
        const showSubmissionErrorModal = ref(false);
        const submissionErrorMessage = ref('');
        const activeChoice = ref(null);
        const formErrors = ref({});
        const isSubmitting = ref(false);
        const isValidatingDetails = ref(false);
        const isValidatingBooking = ref(false);
        const bookingErrors = ref({});
        const returningClient = ref(false);
        const hasSelectedService = computed(() => Boolean(selectedService.value));
        const isAcademe = computed(() => form.type_client === 'academe');
        const isBusiness = computed(() => ['government', 'private-companies'].includes(form.type_client));
        const isPrivateCompany = computed(() => form.type_client === 'private-companies');
        const selectedClientTypeLabel = computed(() => props.clientTypes.find((type) => type.value === form.type_client)?.label ?? 'Choose client type');
        const selectedSourceLabel = computed(() => props.sources.find((source) => source.value === form.source)?.label ?? 'Choose source');
        const selectedServiceLabel = computed(() => props.services.find((service) => service.value === form.service)?.label ?? form.service);
        const selectedBusinessRoleLabel = computed(() => props.businessRoles.find((role) => role.value === form.business_role)?.label ?? form.business_role);
        const selectedEnterpriseSizeLabel = computed(() => props.enterpriseSizes.find((size) => size.value === form.enterprise_size)?.label ?? form.enterprise_size);
        const selectedMarketLabel = computed(() => props.markets.find((market) => market.value === form.market)?.label ?? form.market);
        const choiceConfig = computed(() => ({
            gender: { field: 'gender', title: 'Choose gender', choices: withIllustrations([{ value: 'male', label: 'Male' }, { value: 'female', label: 'Female' }, { value: 'prefer-not-to-say', label: 'Prefer not to say' }], 6) },
            business_role: { field: 'business_role', title: 'Choose business role', choices: withIllustrations(props.businessRoles, 0) },
            enterprise_size: { field: 'enterprise_size', title: 'Choose enterprise size', choices: withIllustrations(props.enterpriseSizes, 3) },
            market: { field: 'market', title: 'Choose market', choices: withIllustrations(props.markets, 6) },
        }));

        const findClient = async () => {
            lookupError.value = '';
            isLookingUp.value = true;

            try {
                const response = await fetch(`/book-an-appointment/client?email=${encodeURIComponent(email.value)}`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();

                if (!response.ok) {
                    lookupError.value = data.errors?.email?.[0] ?? 'Enter a valid email address.';

                    return;
                }

                Object.assign(form, {
                    ...blankClient(selectedService.value),
                    appointment_date: form.appointment_date,
                    appointment_time: form.appointment_time,
                    ...(data.client ?? { email: email.value }),
                });
                form.service = selectedService.value;
                returningClient.value = Boolean(data.client);
                showWelcome.value = Boolean(data.client);
                showEmailModal.value = false;
                currentStep.value = 3;
            } catch {
                lookupError.value = 'We could not check that email. Please try again.';
            } finally {
                isLookingUp.value = false;
            }
        };

        const openEmailModal = (service) => {
            selectedService.value = service;
            showEmailModal.value = true;
        };

        const selectClientType = (clientType) => {
            form.type_client = clientType;
            showClientTypeModal.value = false;
        };

        const selectSource = (source) => {
            form.source = source;
            showSourceModal.value = false;
        };

        const selectChoice = (value) => {
            form[choiceConfig.value[activeChoice.value].field] = value;
            activeChoice.value = null;
        };

        const errorLabels = {
            firstname: 'First name', middlename: 'Middle name', lastname: 'Last name', age: 'Age', gender: 'Gender', email: 'Email', mobile_no: 'Mobile number', tel_no: 'Telephone number', fax_no: 'Fax number', address: 'Address', region: 'Region', province: 'Province', municipality: 'Municipality', type_client: 'Client type', source: 'Source', company: 'Company', school_name: 'School name', business_role: 'Business role', enterprise_size: 'Enterprise size', market: 'Market', products: 'Products', description: 'Request description',
        };
        const markClientRequiredFields = () => nextTick(() => markRequiredFields('#client-details', ['First name', 'Last name', 'Age', 'Gender', 'Email', 'Mobile number', 'Telephone number', 'Address', 'Region', 'Province', 'Municipality', 'Client type', 'Source', 'Company', 'School name', 'Business role', 'Enterprise size', 'Market', 'Products', 'Request description']));

        const fieldForError = (fieldName) => {
            const label = errorLabels[fieldName === 'fullname' ? 'firstname' : fieldName];

            return [...document.querySelectorAll('#client-details label')]
                .find((element) => element.querySelector('span')?.textContent.trim() === label)
                ?.querySelector('input, textarea')
                ?? [...document.querySelectorAll('#client-details button')]
                    .find((element) => element.parentElement?.querySelector(':scope > span')?.textContent.trim() === label);
        };

        const displayFieldErrors = (errors) => {
            nextTick(() => {
                document.querySelectorAll('.field-error').forEach((element) => element.remove());
                document.querySelectorAll('#client-details [aria-invalid="true"]').forEach((element) => {
                    element.removeAttribute('aria-invalid');
                    element.classList.remove('border-rose-500', 'ring-2', 'ring-rose-100');
                });

                Object.entries(errors).forEach(([fieldName, messages]) => {
                    const field = fieldForError(fieldName);

                    if (!field) {
                        return;
                    }

                    const message = Array.isArray(messages) ? messages[0] : messages;
                    const messageElement = document.createElement('p');
                    messageElement.className = 'field-error mt-1 text-sm text-rose-600';
                    messageElement.textContent = message;
                    field.setAttribute('aria-invalid', 'true');
                    field.classList.add('border-rose-500', 'ring-2', 'ring-rose-100');
                    (field.closest('label') ?? field.parentElement)?.append(messageElement);
                });

                const field = fieldForError(Object.keys(errors)[0]);

                if (field) {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    field.focus({ preventScroll: true });
                }
            });
        };

        const validateDetails = async () => {
            formErrors.value = {};
            displayFieldErrors({});
            isValidatingDetails.value = true;

            try {
                const response = await fetch('/book-an-appointment/validate', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify(form),
                });

                if (response.ok) {
                    currentStep.value = 4;

                    return;
                }

                const data = await response.json();
                formErrors.value = data.errors ?? { form: ['We could not validate your details.'] };
                displayFieldErrors(formErrors.value);
            } catch {
                formErrors.value = { form: ['We could not validate your details. Please try again.'] };
            } finally {
                isValidatingDetails.value = false;
            }
        };

        const validateBooking = async () => {
            bookingErrors.value = {};
            isValidatingBooking.value = true;

            try {
                const response = await fetch('/book-an-appointment/validate-booking', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        appointment_date: form.appointment_date,
                        appointment_time: form.appointment_time,
                    }),
                });

                if (response.ok) {
                    currentStep.value = 2;

                    return;
                }

                const data = await response.json();
                bookingErrors.value = data.errors ?? {};
                await nextTick();
                document.querySelector('#booking-details [aria-invalid="true"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } finally {
                isValidatingBooking.value = false;
            }
        };

        const submitWalkIn = () => {
            formErrors.value = {};
            displayFieldErrors({});

            router.post('/book-an-appointment', form, {
                onStart: () => {
                    isSubmitting.value = true;
                },
                onError: (errors) => {
                    formErrors.value = errors;

                    if (errors.appointment_date || errors.appointment_time) {
                        bookingErrors.value = errors;
                        currentStep.value = 1;

                        return;
                    }

                currentStep.value = 3;
                markClientRequiredFields();
                    displayFieldErrors(errors);
                },
                onFinish: () => {
                    isSubmitting.value = false;
                },
            });
        };

        const openTermsModal = () => {
            showTermsModal.value = true;
        };

        const confirmTermsAndSubmit = () => {
            form.terms_accepted = true;
            showTermsModal.value = false;
            submitWalkIn();
        };

        onMounted(() => {
            if (props.selectedService && props.selectedEmail) {
                findClient();
            }
        });

        watch(
            () => page.props.flash?.success,
            (message) => {
                if (message) {
                    showSubmissionSuccessModal.value = true;
                }
            },
            { immediate: true },
        );

        watch(
            () => page.props.flash?.error,
            (message) => {
                if (message) {
                    submissionErrorMessage.value = message;
                    showSubmissionErrorModal.value = true;
                }
            },
            { immediate: true },
        );

        return {
            clientTypeIllustrations,
            choiceConfig,
            activeChoice,
            bookingErrors,
            currentStep,
            email,
            findClient,
            form,
            formErrors,
            confirmTermsAndSubmit,
            formatDate,
            formatTime,
            hasSelectedService,
            isAcademe,
            minAppointmentDate,
            isBusiness,
            isPrivateCompany,
            isLookingUp,
            isSubmitting,
            isValidatingDetails,
            isValidatingBooking,
            lookupError,
            openEmailModal,
            openTermsModal,
            selectClientType,
            selectedClientTypeLabel,
            selectedBusinessRoleLabel,
            selectedEnterpriseSizeLabel,
            selectedMarketLabel,
            selectedServiceLabel,
            selectedSourceLabel,
            selectSource,
            selectChoice,
            returningClient,
            selectedService,
            serviceIllustrations,
            sourceIllustrations,
            showClientTypeModal,
            showEmailModal,
            showWelcome,
            showSourceModal,
            showSubmissionErrorModal,
            showSubmissionSuccessModal,
            showTermsModal,
            submissionErrorMessage,
            submitWalkIn,
            validateBooking,
            validateDetails,
        };
    },
    template: `
        <Head title="Book an appointment" />

        <main class="min-h-screen bg-white px-4 py-8 sm:px-6 sm:py-12">
            <section class="mx-auto w-full max-w-6xl">
                <header class="flex items-center gap-4 border-b border-slate-200/80 pb-5">
                    <a href="/" class="flex h-9 w-9 items-center justify-center rounded-full text-xl text-slate-500 transition hover:bg-white hover:text-[#008dcc]" aria-label="Back to service options">
                        ←
                    </a>
                    <div><h1 class="font-semibold text-slate-900">Appointment request</h1><p class="text-xs text-[#3d68b1]">Complete the steps below</p></div>
                </header>
                <div class="mx-auto mt-10 w-full py-5 sm:py-8">
                    <AppointmentStepper :current-step="currentStep" />
                    <section v-if="currentStep === 1" class="mt-6 flex min-h-[320px] flex-col rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                        <div>
                            <h2 class="text-xl font-semibold text-slate-900">Booking details</h2>
                            <p class="mt-1 text-slate-600">
                                Choose the date and time you would prefer to visit PTRI. Fields marked with<span class="font-semibold text-rose-600">*</span>are required.
                            </p>
                        </div>
                        <form id="booking-details" class="mt-8 flex flex-1 flex-col" @submit.prevent="validateBooking">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <label>
                                    <span class="text-sm font-medium text-slate-700">Preferred date</span>
                                    <input v-model="form.appointment_date" type="date" :min="minAppointmentDate" :aria-invalid="Boolean(bookingErrors.appointment_date)" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" :class="bookingErrors.appointment_date ? 'border-rose-500 ring-2 ring-rose-100' : ''" />
                                    <p v-if="bookingErrors.appointment_date" class="mt-1 text-sm text-rose-600">{{ bookingErrors.appointment_date[0] }}</p>
                                </label>
                                <label>
                                    <span class="text-sm font-medium text-slate-700">Preferred time</span>
                                    <input v-model="form.appointment_time" type="time" :aria-invalid="Boolean(bookingErrors.appointment_time)" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" :class="bookingErrors.appointment_time ? 'border-rose-500 ring-2 ring-rose-100' : ''" />
                                    <p v-if="bookingErrors.appointment_time" class="mt-1 text-sm text-rose-600">{{ bookingErrors.appointment_time[0] }}</p>
                                </label>
                            </div>
                            <div class="mt-auto flex items-center justify-between gap-3 pt-8">
                                <a href="/" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                                </a>
                                <button type="submit" :disabled="isValidatingBooking" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-400">
                                    {{ isValidatingBooking ? 'Validating…' : 'Next' }}<i v-if="!isValidatingBooking" class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </form>
                    </section>
                    <section v-else-if="currentStep === 2" class="mt-8 border-t border-slate-100 pt-7">
                        <h2 class="text-xl font-semibold text-slate-900">Choose a service</h2>
                        <p class="mt-1 text-slate-600">Select the service you need before providing your client information.</p>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <ServiceCard
                                v-for="service in services"
                                :key="service.value"
                                :illustration="serviceIllustrations[service.value]"
                                :selected="selectedService === service.value"
                                :service="service"
                                @select="openEmailModal"
                            />
                        </div>
                    </section>
                    <section v-else-if="currentStep === 3" class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-xl font-semibold text-slate-900">Find your client record</h2>
                                <p class="mt-1 text-slate-600">Enter your email to save time if you have visited before.</p>
                            </div>
                            <button type="button" class="text-sm font-semibold text-[#008dcc] hover:text-[#006f9f]" @click="currentStep = 2">Change service</button>
                        </div>
                        <form v-if="form.email" id="client-details" class="mt-8 border-t border-slate-200 pt-8" @submit.prevent="validateDetails">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-xl font-semibold text-slate-900">Client information</h2>
                                    <p class="mt-1 text-slate-600">
                                        {{ returningClient ? 'Review and update your details if needed.' : 'Complete all fields to continue.' }} Fields marked with<span class="font-semibold text-rose-600">*</span>are required.
                                    </p>
                                </div>
                                <span class="rounded-full px-3 py-1 text-sm font-semibold" :class="returningClient ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'">
                                    {{ returningClient ? 'Returning client' : 'New client' }}
                                </span>
                            </div>
                            <div class="mt-8 space-y-10">
                                <section>
                                    <h3 class="text-base font-semibold text-slate-900">Personal information</h3>
                                    <p class="mt-1 text-sm text-slate-500">Your full name is generated when you submit the form.</p>
                                    <div class="mt-5 grid gap-5 md:grid-cols-6">
                                        <label class="md:col-span-2">
                                            <span class="text-sm font-medium text-slate-700">First name</span>
                                            <input v-model="form.firstname" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-2">
                                            <span class="text-sm font-medium text-slate-700">Middle name</span>
                                            <input v-model="form.middlename" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-2">
                                            <span class="text-sm font-medium text-slate-700">Last name</span>
                                            <input v-model="form.lastname" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-2">
                                            <span class="text-sm font-medium text-slate-700">Age</span>
                                            <input v-model="form.age" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <div class="md:col-span-4">
                                            <span class="text-sm font-medium text-slate-700">Gender</span>
                                            <button type="button" class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-300 px-3 py-2.5 text-left" @click="activeChoice = 'gender'">
                                                <span :class="form.gender ? 'text-slate-900' : 'text-slate-400'">{{ form.gender || 'Choose gender' }}</span>
                                                <span aria-hidden="true">⌄</span>
                                            </button>
                                        </div>
                                    </div>
                                </section>
                                <section class="border-t border-slate-100 pt-8">
                                    <h3 class="text-base font-semibold text-slate-900">Contact information</h3>
                                    <div class="mt-5 grid gap-5 md:grid-cols-6">
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Email</span>
                                            <input v-model="form.email" readonly aria-readonly="true" class="mt-1 w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-600" />
                                        </label>
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Mobile number</span>
                                            <input v-model="form.mobile_no" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Telephone number</span>
                                            <input v-model="form.tel_no" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Fax number</span>
                                            <input v-model="form.fax_no" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-6">
                                            <span class="text-sm font-medium text-slate-700">Address</span>
                                            <textarea v-model="form.address" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
                                        </label>
                                        <label class="md:col-span-2">
                                            <span class="text-sm font-medium text-slate-700">Region</span>
                                            <input v-model="form.region" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-2">
                                            <span class="text-sm font-medium text-slate-700">Province</span>
                                            <input v-model="form.province" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-2">
                                            <span class="text-sm font-medium text-slate-700">Municipality</span>
                                            <input v-model="form.municipality" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                    </div>
                                </section>
                                <section class="border-t border-slate-100 pt-8">
                                    <h3 class="text-base font-semibold text-slate-900">Background</h3>
                                    <div class="mt-5 grid gap-5 md:grid-cols-6">
                                        <div class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Client type</span>
                                            <button type="button" class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-300 px-3 py-2.5 text-left" @click="showClientTypeModal = true">
                                                <span :class="form.type_client ? 'text-slate-900' : 'text-slate-400'">{{ selectedClientTypeLabel }}</span>
                                                <span aria-hidden="true">⌄</span>
                                            </button>
                                        </div>
                                        <div class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Source</span>
                                            <button type="button" class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-300 px-3 py-2.5 text-left" @click="showSourceModal = true">
                                                <span :class="form.source ? 'text-slate-900' : 'text-slate-400'">{{ selectedSourceLabel }}</span>
                                                <span aria-hidden="true">⌄</span>
                                            </button>
                                        </div>
                                        <label v-if="isBusiness" class="md:col-span-6">
                                            <span class="text-sm font-medium text-slate-700">Company</span>
                                            <input v-model="form.company" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <label v-if="isAcademe" class="md:col-span-6">
                                            <span class="text-sm font-medium text-slate-700">School name</span>
                                            <input v-model="form.school_name" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                        </label>
                                        <template v-if="isPrivateCompany">
                                            <div class="md:col-span-3">
                                                <span class="text-sm font-medium text-slate-700">Business role</span>
                                                <button type="button" class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-300 px-3 py-2.5 text-left" @click="activeChoice = 'business_role'">
                                                    <span :class="form.business_role ? 'text-slate-900' : 'text-slate-400'">{{ form.business_role || 'Choose business role' }}</span>
                                                    <span>⌄</span>
                                                </button>
                                            </div>
                                            <div class="md:col-span-3">
                                                <span class="text-sm font-medium text-slate-700">Enterprise size</span>
                                                <button type="button" class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-300 px-3 py-2.5 text-left" @click="activeChoice = 'enterprise_size'">
                                                    <span :class="form.enterprise_size ? 'text-slate-900' : 'text-slate-400'">{{ form.enterprise_size || 'Choose enterprise size' }}</span>
                                                    <span>⌄</span>
                                                </button>
                                            </div>
                                            <div class="md:col-span-3">
                                                <span class="text-sm font-medium text-slate-700">Market</span>
                                                <button type="button" class="mt-1 flex w-full items-center justify-between rounded-xl border border-slate-300 px-3 py-2.5 text-left" @click="activeChoice = 'market'">
                                                    <span :class="form.market ? 'text-slate-900' : 'text-slate-400'">{{ form.market || 'Choose market' }}</span>
                                                    <span>⌄</span>
                                                </button>
                                            </div>
                                            <label class="md:col-span-3">
                                                <span class="text-sm font-medium text-slate-700">Products</span>
                                                <input v-model="form.products" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5" />
                                            </label>
                                        </template>
                                        <label class="md:col-span-6">
                                            <span class="text-sm font-medium text-slate-700">Request description</span>
                                            <textarea v-model="form.description" rows="4" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5"></textarea>
                                        </label>
                                    </div>
                                </section>
                            </div>
                            <div class="mt-8 flex items-center justify-between gap-3">
                                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="currentStep = 2">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                                </button>
                                <button type="submit" :disabled="isValidatingDetails" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-400">
                                    {{ isValidatingDetails ? 'Validating…' : 'Next' }}<i v-if="!isValidatingDetails" class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </form>
                    </section>
                    <section v-else class="mt-6 rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-8">
                        <div class="border-b border-slate-200 pb-6">
                            <h2 class="text-xl font-semibold text-slate-900">Review your request</h2>
                            <p class="mt-1 text-slate-600">Check the information below before submitting your appointment request.</p>
                        </div>
                        <form class="mt-8 space-y-8" @submit.prevent="openTermsModal">
                            <section>
                                <h3 class="text-base font-semibold text-slate-900">Booking details</h3>
                                <div class="mt-4 grid gap-5 sm:grid-cols-2">
                                    <label>
                                        <span class="text-sm font-medium text-slate-700">Preferred date</span>
                                        <input :value="formatDate(form.appointment_date)" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                                    </label>
                                    <label>
                                        <span class="text-sm font-medium text-slate-700">Preferred time</span>
                                        <input :value="formatTime(form.appointment_time)" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                                    </label>
                                </div>
                            </section>
                            <section class="border-t border-slate-100 pt-7">
                                <h3 class="text-base font-semibold text-slate-900">Service selected</h3>
                                <label class="mt-4 block">
                                    <span class="text-sm font-medium text-slate-700">Service</span>
                                    <input :value="selectedServiceLabel" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700" />
                                </label>
                            </section>
                            <section class="border-t border-slate-100 pt-7">
                                <h3 class="text-base font-semibold text-slate-900">Personal information</h3>
                                <div class="mt-4 grid gap-5 md:grid-cols-6">
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">First name</span>
                                        <input :value="form.firstname" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Middle name</span>
                                        <input :value="form.middlename || '—'" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Last name</span>
                                        <input :value="form.lastname" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Age</span>
                                        <input :value="form.age" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-6">
                                        <span class="text-sm font-medium text-slate-700">Gender</span>
                                        <input :value="form.gender" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                </div>
                            </section>
                            <section class="border-t border-slate-100 pt-7">
                                <h3 class="text-base font-semibold text-slate-900">Contact information</h3>
                                <div class="mt-4 grid gap-5 md:grid-cols-6">
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Email</span>
                                        <input :value="form.email" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Mobile number</span>
                                        <input :value="form.mobile_no" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Telephone number</span>
                                        <input :value="form.tel_no" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Fax number</span>
                                        <input :value="form.fax_no || '—'" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-6">
                                        <span class="text-sm font-medium text-slate-700">Address</span>
                                        <textarea :value="form.address" readonly rows="3" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5"></textarea>
                                    </label>
                                </div>
                            </section>
                            <section class="border-t border-slate-100 pt-7">
                                <h3 class="text-base font-semibold text-slate-900">Background</h3>
                                <div class="mt-4 grid gap-5 md:grid-cols-6">
                                    <label class="md:col-span-2">
                                        <span class="text-sm font-medium text-slate-700">Region</span>
                                        <input :value="form.region" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-2">
                                        <span class="text-sm font-medium text-slate-700">Province</span>
                                        <input :value="form.province" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-2">
                                        <span class="text-sm font-medium text-slate-700">Municipality</span>
                                        <input :value="form.municipality" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Client type</span>
                                        <input :value="selectedClientTypeLabel" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label class="md:col-span-3">
                                        <span class="text-sm font-medium text-slate-700">Source</span>
                                        <input :value="selectedSourceLabel" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label v-if="isBusiness" class="md:col-span-6">
                                        <span class="text-sm font-medium text-slate-700">Company</span>
                                        <input :value="form.company" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <label v-if="isAcademe" class="md:col-span-6">
                                        <span class="text-sm font-medium text-slate-700">School name</span>
                                        <input :value="form.school_name" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                    </label>
                                    <template v-if="isPrivateCompany">
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Business role</span>
                                            <input :value="selectedBusinessRoleLabel" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Enterprise size</span>
                                            <input :value="selectedEnterpriseSizeLabel" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Market</span>
                                            <input :value="selectedMarketLabel" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                        </label>
                                        <label class="md:col-span-3">
                                            <span class="text-sm font-medium text-slate-700">Products</span>
                                            <input :value="form.products" readonly class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5" />
                                        </label>
                                    </template>
                                    <label class="md:col-span-6">
                                        <span class="text-sm font-medium text-slate-700">Request description</span>
                                        <textarea :value="form.description" readonly rows="4" class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5"></textarea>
                                    </label>
                                </div>
                            </section>
                            <div class="flex justify-between gap-3 border-t border-slate-100 pt-7">
                                <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="currentStep = 3">
                                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                                </button>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]">
                                    Submit appointment request<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </section>
            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showWelcome" class="fixed inset-0 z-10 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="welcome-title">
                    <div class="w-full max-w-lg rounded-3xl bg-white p-7 shadow-2xl sm:p-8">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-2xl" aria-hidden="true">✓</div>
                        <h2 id="welcome-title" class="mt-5 text-2xl font-semibold text-slate-900">Welcome back, {{ form.firstname }}!</h2>
                        <p class="mt-2 text-slate-600">We found your client record and filled in your details. Please review them before continuing.</p>
                        <button type="button" class="mt-6 w-full rounded-xl bg-[#00aeef] px-5 py-3 font-semibold text-white hover:bg-[#008dcc]" @click="showWelcome = false">
                            Review my details
                        </button>
                    </div>
                </div>
            </Transition>
            <EmailLookupModal :email="email" :error="lookupError" :loading="isLookingUp" :open="showEmailModal" @close="showEmailModal = false" @submit="findClient" @update:email="email = $event" />
            <ClientTypeModal :client-types="clientTypes" :illustrations="clientTypeIllustrations" :open="showClientTypeModal" @close="showClientTypeModal = false" @select="selectClientType" />
            <SourceModal :illustrations="sourceIllustrations" :open="showSourceModal" :sources="sources" @close="showSourceModal = false" @select="selectSource" />
            <IllustratedChoiceModal :choices="activeChoice ? choiceConfig[activeChoice].choices : []" :open="Boolean(activeChoice)" :title="activeChoice ? choiceConfig[activeChoice].title : ''" @close="activeChoice = null" @select="selectChoice" />
            <TermsConditionsModal :open="showTermsModal" @close="showTermsModal = false" @confirm="confirmTermsAndSubmit" />
            <LoadingModal :open="isSubmitting" message="Submitting your appointment request. This will only take a moment." />
            <FeedbackModal
                :open="showSubmissionSuccessModal"
                title="Appointment request submitted"
                message="Your appointment request has been received. Our team will review it and email the next steps to you."
                action-label="Return to home"
                action-href="/"
                action-icon="fa-solid fa-arrow-left"
                @close="showSubmissionSuccessModal = false"
            />
            <FeedbackModal
                :open="showSubmissionErrorModal"
                tone="error"
                icon="fa-solid fa-triangle-exclamation"
                title="We could not submit your request"
                :message="submissionErrorMessage || 'Something went wrong while submitting your request. Please try again.'"
                close-label="Close"
                @close="showSubmissionErrorModal = false"
            />
            <PrivacyNoticeCard />
        </main>
`,
});
