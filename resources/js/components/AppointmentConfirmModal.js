import { computed, defineComponent, ref, watch } from 'vue';
import { formatDateTime } from '../utils/format-date';

export default defineComponent({
    name: 'AppointmentConfirmModal',
    props: {
        open: { type: Boolean, default: false },
        request: { type: Object, default: null },
    },
    emits: ['close', 'continue'],
    setup(props, { emit }) {
        const action = ref('approve');
        const appointmentDate = ref('');
        const appointmentTime = ref('');
        const reason = ref('');

        watch(() => props.request, (request) => {
            action.value = 'approve';
            appointmentDate.value = request?.appointment_date ?? '';
            // The server sends "HH:MM:SS"; the <input type="time"> below has no seconds
            // step, so trim to "HH:MM" or a submitted reschedule fails H:i validation.
            appointmentTime.value = (request?.appointment_time ?? '').slice(0, 5);
            reason.value = '';
        });

        const scheduledFor = computed(() => formatDateTime(props.request?.appointment_date, props.request?.appointment_time));
        const requiresReason = computed(() => action.value === 'cancel' || action.value === 'reschedule');

        const proceed = () => {
            emit('continue', action.value === 'cancel'
                ? { type: 'cancel', reason: reason.value }
                : {
                    type: 'approve',
                    reschedule: action.value === 'reschedule',
                    appointment_date: appointmentDate.value,
                    appointment_time: appointmentTime.value,
                    reason: action.value === 'reschedule' ? reason.value : '',
                });
        };

        return { action, appointmentDate, appointmentTime, proceed, reason, requiresReason, scheduledFor };
    },
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="open && request" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="appointment-action-title">
                <section class="w-full max-w-xl rounded-3xl bg-white p-7 shadow-2xl sm:p-9">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-sky-100 text-2xl text-[#07559e]">
                        <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                    </div>
                    <h2 id="appointment-action-title" class="mt-5 text-2xl font-bold text-slate-900">Take action on this appointment</h2>
                    <p class="mt-2 leading-7 text-slate-600">
                        Service request #{{ request.id }} is scheduled for <strong>{{ scheduledFor }}</strong>.
                    </p>

                    <fieldset class="mt-5 space-y-2">
                        <legend class="text-sm font-semibold text-slate-700">What would you like to do?</legend>
                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm" :class="action === 'approve' ? 'border-[#00aeef] bg-sky-50' : ''">
                            <input v-model="action" type="radio" value="approve" class="mt-1 h-4 w-4 accent-[#07559e]" />
                            <span><span class="font-semibold text-slate-900">Approve</span><br /><span class="text-slate-500">Confirm the appointment as scheduled.</span></span>
                        </label>
                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm" :class="action === 'reschedule' ? 'border-[#00aeef] bg-sky-50' : ''">
                            <input v-model="action" type="radio" value="reschedule" class="mt-1 h-4 w-4 accent-[#07559e]" />
                            <span><span class="font-semibold text-slate-900">Re-schedule</span><br /><span class="text-slate-500">Move the appointment to a new date and time, then confirm it.</span></span>
                        </label>
                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3 text-sm" :class="action === 'cancel' ? 'border-rose-500 bg-rose-50' : ''">
                            <input v-model="action" type="radio" value="cancel" class="mt-1 h-4 w-4 accent-rose-600" />
                            <span><span class="font-semibold text-slate-900">Cancel</span><br /><span class="text-slate-500">The request will be marked as cancelled.</span></span>
                        </label>
                    </fieldset>

                    <div v-if="action === 'reschedule'" class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label>
                            <span class="required-label text-sm font-semibold text-slate-700">New date</span>
                            <input v-model="appointmentDate" type="date" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" />
                        </label>
                        <label>
                            <span class="required-label text-sm font-semibold text-slate-700">New time</span>
                            <input v-model="appointmentTime" type="time" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" />
                        </label>
                    </div>

                    <label v-if="requiresReason" class="mt-5 block">
                        <span class="required-label text-sm font-semibold text-slate-700">Reason for {{ action === 'cancel' ? 'cancellation' : 'rescheduling' }}</span>
                        <textarea v-model="reason" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" placeholder="This will be included in the email sent to the client."></textarea>
                    </label>

                    <div class="mt-7 flex flex-wrap justify-end gap-3">
                        <button type="button" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50" @click="$emit('close')">
                            Close
                        </button>
                        <button type="button" class="rounded-lg px-5 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-60" :class="action === 'cancel' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-[#00aeef] hover:bg-[#008dcc]'" :disabled="(action === 'reschedule' && (!appointmentDate || !appointmentTime)) || (requiresReason && !reason.trim())" @click="proceed">
                            Continue
                        </button>
                    </div>
                </section>
            </div>
        </Transition>
    `,
});
