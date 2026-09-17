import { computed, defineComponent, nextTick, ref, watch } from 'vue';

export default defineComponent({
    name: 'SignatorySelect',
    props: {
        id: { type: String, required: true },
        label: { type: String, required: true },
        modelValue: { type: String, default: '' },
        options: { type: Array, default: () => [] },
        optional: { type: Boolean, default: false },
        disabled: { type: Boolean, default: false },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const open = ref(false);
        const search = ref('');
        const searchInput = ref(null);
        const customInput = ref(null);
        const trigger = ref(null);
        const custom = ref(Boolean(props.modelValue && !props.options.includes(props.modelValue)));
        const filteredOptions = computed(() => props.options.filter((name) => name.toLocaleLowerCase().includes(search.value.toLocaleLowerCase())));
        watch(() => props.modelValue, (value) => {
            if (value) custom.value = !props.options.includes(value);
        });
        watch(() => props.disabled, () => { open.value = false; });
        const toggle = async () => {
            open.value = !open.value;
            search.value = '';
            if (open.value) {
                await nextTick();
                searchInput.value?.focus();
            }
        };
        const close = () => {
            open.value = false;
            trigger.value?.focus();
        };
        const choose = async (name, isCustom = false) => {
            custom.value = isCustom;
            emit('update:modelValue', name);
            open.value = false;
            await nextTick();
            (isCustom ? customInput.value : trigger.value)?.focus();
        };
        const onFocusOut = (event) => {
            if (!event.currentTarget.contains(event.relatedTarget)) open.value = false;
        };
        return { open, search, searchInput, customInput, trigger, custom, filteredOptions, toggle, close, choose, onFocusOut };
    },
    template: `
        <div class="relative" @focusout="onFocusOut" @keydown.esc.stop.prevent="close">
            <label :id="id + '-label'" :for="id" class="block text-center text-sm font-semibold text-slate-700">{{ label }}</label>
            <button :id="id" ref="trigger" type="button" :disabled="disabled" :aria-expanded="open" :aria-controls="id + '-options'" :aria-labelledby="id + '-label ' + id + '-value'" class="mt-2 flex w-full cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-left text-sm text-slate-700 disabled:cursor-not-allowed disabled:bg-slate-50" @click="toggle">
                <span :id="id + '-value'">{{ custom ? 'Other' : (modelValue || (optional ? 'None' : 'Select signatory')) }}</span>
                <i class="fa-solid fa-chevron-down text-xs text-slate-400" aria-hidden="true"></i>
            </button>
            <div v-if="open" :id="id + '-options'" class="absolute z-20 mt-1 w-full rounded-xl border border-slate-200 bg-white p-2 shadow-xl" role="group" :aria-labelledby="id + '-label'">
                <input ref="searchInput" v-model="search" type="search" :aria-label="'Search ' + label" placeholder="Search names…" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#00aeef]" @keydown.enter.prevent />
                <div class="mt-2 max-h-56 overflow-y-auto">
                    <button v-if="optional" type="button" class="w-full cursor-pointer rounded-lg px-3 py-2 text-left text-sm text-slate-600 hover:bg-sky-50 focus:bg-sky-50" @click="choose('')">None</button>
                    <button v-for="name in filteredOptions" :key="name" type="button" class="w-full cursor-pointer rounded-lg px-3 py-2 text-left text-sm hover:bg-sky-50 focus:bg-sky-50" :class="modelValue === name ? 'bg-sky-50 font-semibold text-[#07559e]' : 'text-slate-700'" @click="choose(name)">{{ name }}</button>
                    <p v-if="!filteredOptions.length" class="px-3 py-2 text-sm text-slate-500">No matching names.</p>
                    <button type="button" class="w-full cursor-pointer rounded-lg border-t border-slate-100 px-3 py-2 text-left text-sm font-semibold text-[#07559e] hover:bg-sky-50 focus:bg-sky-50" @click="choose('', true)">Other — enter a name</button>
                </div>
            </div>
            <div v-if="custom" class="mt-2">
                <label :for="id + '-custom'" class="text-sm font-medium text-slate-700">{{ label }} name</label>
                <input :id="id + '-custom'" ref="customInput" :value="modelValue" :disabled="disabled" :required="!optional" maxlength="255" type="text" placeholder="Enter full name" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm disabled:bg-slate-50" @input="$emit('update:modelValue', $event.target.value)" />
            </div>
        </div>
    `,
});
