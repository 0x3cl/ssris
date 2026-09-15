import { Head, router } from '@inertiajs/vue3';
import { defineComponent, ref } from 'vue';
import AdminShell from '../../components/AdminShell';

const currencyFormatter = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const peso = (amount) => currencyFormatter.format(Number(amount) || 0);

export default defineComponent({
    name: 'AdminRddFeedbackForm',
    components: { AdminShell, Head },
    props: { serviceRequest: { type: Object, required: true }, rddRequest: { type: Object, required: true } },
    setup(props) {
        const activeTab = ref('service-request');
        const sending = ref(false);

        const rowTotal = (item) => (Number(item.quantity) || 0) * (Number(item.unit_fee) || 0);

        const sendReminder = () => {
            sending.value = true;
            router.post(`/admin/requests/${props.serviceRequest.id}/rdd-request/feedback/remind`, {}, {
                preserveScroll: true,
                onFinish: () => {
                    sending.value = false;
                },
            });
        };

        return {
            activeTab,
            peso,
            rowTotal,
            sendReminder,
            sending,
        };
    },
    template: `
        <Head title="Review feedback" />
        <AdminShell active="requests" title="Review Feedback">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Requests module</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Service request #{{ serviceRequest.id }} &middot; {{ rddRequest.reference_no }}</h2>
                        <p class="mt-1 text-slate-600">Review the request, payment, and client feedback.</p>
                    </div>
                    <a href="/admin/requests" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to requests
                    </a>
                </div>

                <div class="mt-7 flex flex-wrap gap-2 border-b border-slate-200">
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'service-request' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'service-request'">
                        Service request
                    </button>
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'verify-payment' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'verify-payment'">
                        Verify payment
                    </button>
                    <button type="button" class="border-b-2 px-4 py-3 text-sm font-bold transition" :class="activeTab === 'feedback' ? 'border-[#00aeef] text-[#07559e]' : 'border-transparent text-slate-500 hover:text-slate-900'" @click="activeTab = 'feedback'">
                        Feedback
                    </button>
                </div>

                <template v-if="activeTab === 'service-request'">
                    <div class="mt-7 space-y-8">
                        <div class="flex justify-end">
                            <a :href="'/admin/requests/' + serviceRequest.id + '/rdd-request/pdf'" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>Download PDF
                            </a>
                        </div>
                        <section>
                            <h3 class="text-base font-bold text-slate-900">Section 1 &middot; Customer information</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Full name</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.fullname }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Client type</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.type_client }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Email</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.email }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Mobile number</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.mobile_no }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Company / School</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.company_or_school || '—' }}</dd></div>
                                <div><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Address</dt><dd class="mt-1 text-slate-700">{{ serviceRequest.client.address }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Request description</dt><dd class="mt-1 whitespace-pre-wrap leading-7 text-slate-700">{{ serviceRequest.description }}</dd></div>
                            </dl>
                        </section>

                        <section class="border-t border-slate-100 pt-7">
                            <h3 class="text-base font-bold text-slate-900">Section 2 &middot; Receiving officer details</h3>
                            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Reference number</dt>
                                    <dd class="mt-1 font-semibold text-slate-900">{{ rddRequest.reference_no }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">Due date</dt>
                                    <dd class="mt-1 text-slate-700">{{ rddRequest.due_date }}</dd>
                                </div>
                            </dl>
                            <div class="mt-6 overflow-x-auto">
                                <table class="w-full min-w-[500px] text-left text-sm">
                                    <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th class="px-3 py-2">Item</th>
                                            <th class="px-3 py-2">Specifications</th>
                                            <th class="px-3 py-2">Qty</th>
                                            <th class="px-3 py-2">Unit fee</th>
                                            <th class="px-3 py-2">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(item, index) in rddRequest.items" :key="index" class="border-b border-slate-100">
                                            <td class="px-3 py-2">{{ item.item }}</td>
                                            <td class="px-3 py-2">{{ item.specification }}</td>
                                            <td class="px-3 py-2">{{ item.quantity }}</td>
                                            <td class="px-3 py-2">{{ peso(item.unit_fee) }}</td>
                                            <td class="px-3 py-2">{{ peso(rowTotal(item)) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <dl class="mt-6 ml-auto max-w-sm space-y-2 text-sm">
                                <div class="flex justify-between"><dt class="text-slate-500">Sub total</dt><dd class="font-semibold text-slate-900">{{ peso(rddRequest.sub_total) }}</dd></div>
                                <div class="flex justify-between"><dt class="text-slate-500">Discount</dt><dd class="font-semibold text-slate-900">-{{ peso(rddRequest.discount) }}</dd></div>
                                <div class="flex justify-between border-t border-slate-200 pt-2 text-base"><dt class="font-bold text-slate-900">Total fee</dt><dd class="font-bold text-slate-900">{{ peso(rddRequest.total_fee) }}</dd></div>
                            </dl>
                        </section>
                    </div>

                    <div class="mt-8 flex justify-end border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="activeTab = 'verify-payment'">
                            Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>

                <template v-else-if="activeTab === 'verify-payment'">
                    <div class="mt-7 max-w-xl">
                        <div class="flex items-center gap-3">
                            <h3 class="text-base font-bold text-slate-900">Verified payment details</h3>
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold uppercase tracking-wide text-emerald-700">
                                <i class="fa-solid fa-check" aria-hidden="true"></i>Verified
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">Payment for {{ rddRequest.reference_no }} has already been verified.</p>

                        <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">OP number</dt>
                                <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700">{{ rddRequest.op_no }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wide text-slate-400">OR number</dt>
                                <dd class="mt-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-slate-700">{{ rddRequest.or_no }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-8 flex justify-between border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'service-request'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                        </button>
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc]" @click="activeTab = 'feedback'">
                            Next<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>

                <template v-else>
                    <div class="mt-7 max-w-xl">
                        <h3 class="text-base font-bold text-slate-900">Client feedback</h3>

                        <div class="mt-5 space-y-4">
                            <div class="rounded-xl bg-amber-50 px-5 py-4 text-sm font-semibold text-amber-700">
                                Send the client a reminder to fill out the Customer Satisfaction Feedback and claim their request.
                            </div>
                            <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-[#00aeef] px-5 py-3 text-sm font-bold uppercase tracking-wide text-white transition hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-400" :disabled="sending" @click="sendReminder">
                                <i class="fa-solid fa-envelope" aria-hidden="true"></i>{{ sending ? 'Sending…' : 'Send reminder' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-start border-t border-slate-100 pt-7">
                        <button type="button" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-5 py-3 text-sm font-bold uppercase tracking-wide text-[#07559e] transition hover:border-[#07559e] hover:bg-sky-50" @click="activeTab = 'verify-payment'">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Go back
                        </button>
                    </div>
                </template>
            </section>

        </AdminShell>
    `,
});
