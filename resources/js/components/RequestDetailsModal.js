import { defineComponent } from 'vue';
import { formatDateTime } from '../utils/format-date';

export default defineComponent({
    name: 'RequestDetailsModal',
    props: { open: { type: Boolean, default: false }, request: { type: Object, default: null } },
    emits: ['close'],
    setup() {
        return { formatDateTime };
    },
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="open && request" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="request-details-title">
                <section class="flex max-h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl">
                    <header class="flex items-start justify-between gap-4 border-b border-slate-200 p-6 sm:px-8"><div><p class="text-xs font-bold uppercase tracking-[0.16em] text-[#07559e]">{{ request.type }}</p><h2 id="request-details-title" class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ request.id }}</h2></div><button type="button" class="flex h-10 w-10 items-center justify-center rounded-full text-3xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close" @click="$emit('close')">×</button></header>
                    <div class="overflow-y-auto p-6 sm:p-8"><dl class="grid gap-5 sm:grid-cols-2"><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Client</dt><dd class="mt-1 font-semibold text-slate-900">{{ request.client?.fullname }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-1 text-slate-700">{{ request.client?.email }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Service</dt><dd class="mt-1 text-slate-700">{{ request.service }}</dd></div><div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Status</dt><dd class="mt-1 capitalize text-slate-700">{{ request.status }}</dd></div><div v-if="request.type === 'appointment'" class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Preferred schedule</dt><dd class="mt-1 text-slate-700">{{ formatDateTime(request.appointment_date, request.appointment_time) }}</dd></div><div v-if="request.type === 'appointment'"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Appointment confirmation</dt><dd class="mt-1 text-slate-700">{{ request.is_appointment_approved ? 'Confirmed' : 'Awaiting confirmation' }}</dd></div><div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Request description</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ request.description }}</dd></div></dl></div>
                    <footer class="border-t border-slate-200 p-5 text-right sm:px-8"><button type="button" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white hover:bg-[#008dcc]" @click="$emit('close')">Close</button></footer>
                </section>
            </div>
        </Transition>
    `,
});
