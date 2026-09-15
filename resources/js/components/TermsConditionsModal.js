import { computed, defineComponent, ref, watch } from 'vue';

export default defineComponent({
    name: 'TermsConditionsModal',
    props: {
        open: { type: Boolean, default: false },
    },
    emits: ['close', 'confirm'],
    setup(props, { emit }) {
        const hasReachedEnd = ref(false);
        const hasAgreed = ref(false);
        const canConfirm = computed(() => hasReachedEnd.value && hasAgreed.value);

        const checkScrollPosition = (event) => {
            const { clientHeight, scrollHeight, scrollTop } = event.target;
            hasReachedEnd.value = scrollTop + clientHeight >= scrollHeight - 4;
        };

        const close = () => {
            emit('close');
        };

        const confirm = () => {
            if (canConfirm.value) {
                emit('confirm');
            }
        };

        watch(() => props.open, (isOpen) => {
            if (isOpen) {
                hasReachedEnd.value = false;
                hasAgreed.value = false;
            }
        });

        return { canConfirm, checkScrollPosition, close, confirm, hasAgreed, hasReachedEnd };
    },
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="terms-title">
                <section class="flex max-h-[calc(100vh-2rem)] w-full max-w-3xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <header class="flex items-start justify-between gap-4 border-b border-slate-200 p-6 sm:px-8">
                        <div><h2 id="terms-title" class="text-2xl font-semibold text-slate-900">Terms and Conditions</h2><p class="mt-1 text-sm text-slate-500">TSD Form No. 001 · Rev. 4/04-10-21</p></div>
                        <button type="button" class="text-xl text-slate-400 hover:text-slate-700" aria-label="Close" @click="close">×</button>
                    </header>
                    <div class="overflow-y-auto px-6 py-6 text-sm leading-6 text-slate-700 sm:px-8" @scroll="checkScrollPosition">
                        <ol class="list-decimal space-y-4 pl-5 marker:font-semibold marker:text-slate-900">
                            <li><strong>PTRI agrees to provide processing services in accordance with:</strong><ol class="mt-2 list-[lower-alpha] space-y-1 pl-5"><li>the Processing Services Request (PSR) form and the terms and conditions herein stated, unless otherwise specifically stipulated and agreed upon in writing by the parties;</li><li>the customer's specific instructions only, or of any party authorized by the customer;</li><li>processing procedures considered by PTRI to be appropriate based on technical, operational, and/or financial grounds.</li></ol></li>
                            <li><strong>PTRI agrees to exercise reasonable diligence</strong> in the manner of performing the processing services, however, no warranty, either expressed or implied, is herein stipulated relative to PTRI's processing services. In no event shall PTRI be liable for collateral, special, or consequential damage, any fault, or negligence of its officers or employees.</li>
                            <li><strong>Customer agrees to have the processing done according to the stated schedule.</strong> Cancellation of processing request shall not be allowed.</li>
                            <li><strong>Customer understands that any information</strong> (written, verbal, or other form) obtained during the services request shall remain confidential or may also be legally privileged.</li>
                            <li><strong>All personal information necessary for the purpose of this transaction are obtained with consent of the customer.</strong> PTRI agrees to keep all information confidential unless authorized by the customer or unless required by law.</li>
                            <li><strong>The customer shall:</strong><ol class="mt-2 list-[lower-alpha] space-y-1 pl-5"><li>ensure that the instructions to PTRI and other relevant information are provided in due time to enable effective performance of processing services;</li><li>provide the required quantity and description of the materials for processing;</li><li>pay in full as quoted, i.e., no deduction or withholding tax, prior to processing;</li><li>allow PTRI to use his/her personal information and/or the Company's information, including photos, for documentation and preparation of PTRI reports, subject to the Implementing Rules and Regulations (IRR) of RA 10173 Data Privacy Act of 2012.</li></ol></li>
                            <li><strong>This contract is only for such items/materials and work as specified herein.</strong> Any other additions or amendments after acceptance will be separately charged.</li>
                            <li><strong>Excess materials shall be stored for three (3) months.</strong> Beyond this period materials shall be disposed of.</li>
                            <li><strong>Processed materials not picked up by customers one (1) week after the due date are considered PTRI property</strong> and shall be handled accordingly.</li>
                            <li><strong>The customer agrees that complaint, question, or dispute shall be given due course only if made in writing.</strong> For verbal communications, the customer shall fill-out the required form for the purpose.</li>
                            <li><strong>The terms and conditions herein stated shall in all respects operate as a contract between the parties in conformity with Philippine laws.</strong></li>
                        </ol>
                        <p class="mt-6 text-center text-xs text-slate-400">End of terms and conditions</p>
                    </div>
                    <footer class="border-t border-slate-200 p-6 sm:px-8">
                        <p v-if="!hasReachedEnd" class="mb-3 text-sm text-slate-500">Scroll to the end to confirm your agreement.</p>
                        <label class="flex items-start gap-3 text-sm font-medium text-slate-800" :class="hasReachedEnd ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'"><input v-model="hasAgreed" :disabled="!hasReachedEnd" type="checkbox" class="mt-0.5 h-5 w-5 rounded border-slate-300 text-[#00aeef] focus:ring-[#00aeef]" /><span>I have read and agreed to the Terms and Conditions</span></label>
                        <div class="mt-5 flex justify-end gap-3"><button type="button" class="rounded-xl px-5 py-3 font-semibold text-slate-600 hover:bg-slate-100" @click="close">Cancel</button><button type="button" :disabled="!canConfirm" class="rounded-xl bg-[#00aeef] px-5 py-3 font-semibold text-white hover:bg-[#008dcc] disabled:cursor-not-allowed disabled:bg-slate-300" @click="confirm">Agree and submit</button></div>
                    </footer>
                </section>
            </div>
        </Transition>
    `,
});
