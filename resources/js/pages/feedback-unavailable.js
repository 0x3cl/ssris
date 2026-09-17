import { Head } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';

const copy = {
    submitted: {
        icon: 'fa-solid fa-circle-check',
        tone: 'bg-emerald-100 text-emerald-600',
        title: 'Thank you for your feedback!',
        message: 'Your Customer Satisfaction Feedback has already been submitted for this request.',
    },
    expired: {
        icon: 'fa-solid fa-clock',
        tone: 'bg-amber-100 text-amber-600',
        title: 'This link has expired',
        message: 'Feedback form links are valid for 24 hours. Please contact us to request a new link.',
    },
    'not-found': {
        icon: 'fa-solid fa-triangle-exclamation',
        tone: 'bg-rose-100 text-rose-600',
        title: 'Link not found',
        message: 'This feedback form link is invalid. Please check the link and try again.',
    },
};

export default defineComponent({
    name: 'PublicFeedbackUnavailable',
    props: {
        reason: { type: String, required: true },
        serviceRequestId: { type: Number, default: null },
        feedbackResponseId: { type: Number, default: null },
    },
    setup(props) {
        const details = computed(() => copy[props.reason] ?? copy['not-found']);

        return { details };
    },
    template: `
        <Head title="Customer Satisfaction Feedback" />
        <div class="flex flex-1 items-center justify-center bg-slate-50 px-4 py-10">
            <section class="w-full max-w-lg rounded-3xl bg-white p-8 text-center shadow-2xl sm:p-10">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full text-3xl" :class="details.tone">
                    <i :class="details.icon" aria-hidden="true"></i>
                </div>
                <h1 class="mt-6 text-2xl font-bold text-slate-900 sm:text-3xl">{{ details.title }}</h1>
                <p class="mt-3 leading-7 text-slate-600">{{ details.message }}</p>
                <p v-if="serviceRequestId" class="mt-4 inline-block rounded-full bg-slate-100 px-4 py-1.5 text-sm font-semibold text-slate-700">Service Request: #0000{{ serviceRequestId }}</p>
                <p v-if="feedbackResponseId" class="mt-2 text-sm font-semibold text-slate-700">Feedback ID: #0000{{ feedbackResponseId }}</p>
            </section>
        </div>
    `,
});
