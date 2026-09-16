import { defineComponent } from 'vue';
import ServiceCard from './ServiceCard';

export default defineComponent({
    name: 'ServiceSelectModal',
    components: { ServiceCard },
    props: {
        open: { type: Boolean, default: false },
        services: { type: Array, required: true },
        illustrations: { type: Object, required: true },
        selected: { type: Array, required: true },
    },
    emits: ['close', 'toggle', 'done'],
    template: `
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open" class="fixed inset-0 z-30 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-label="Choose services">
                <div class="w-full max-w-4xl rounded-3xl bg-white p-7 shadow-2xl sm:p-10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-2xl font-semibold text-slate-900">Choose services</h2>
                            <p class="mt-1 text-sm text-slate-600">Select every service this account should be assigned to.</p>
                        </div>
                        <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-3xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Close" @click="$emit('close')">×</button>
                    </div>
                    <div class="mt-6 grid max-h-[60vh] gap-4 overflow-y-auto p-1 sm:grid-cols-2 lg:grid-cols-3">
                        <ServiceCard
                            v-for="service in services"
                            :key="service.value"
                            :service="service"
                            :illustration="illustrations[service.value]"
                            :selected="selected.includes(service.value)"
                            @select="$emit('toggle', $event)"
                        />
                    </div>
                    <div class="mt-6 flex items-center justify-between gap-4 border-t border-slate-200 pt-5">
                        <p class="text-sm font-semibold text-slate-600">{{ selected.length }} service{{ selected.length === 1 ? '' : 's' }} selected</p>
                        <button type="button" class="rounded-lg bg-[#00aeef] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#009bd8]" @click="$emit('done')">Done</button>
                    </div>
                </div>
            </div>
        </Transition>
    `,
});
