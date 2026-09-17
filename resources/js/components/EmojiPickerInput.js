import { defineComponent, onBeforeUnmount, ref } from 'vue';

/** A curated set of emoji relevant to satisfaction/rating scales. */
const EMOJI_OPTIONS = [
    '😍', '🤩', '😄', '😃', '🙂', '😊', '😐', '😕', '🙁', '☹️', '😞', '😢', '😡',
    '👍', '👎', '🙌', '👏', '⭐', '🌟', '✅', '❌', '🚫', '➖', '💯', '🔥',
];

export default defineComponent({
    name: 'EmojiPickerInput',
    props: {
        modelValue: { type: String, default: '' },
        invalid: { type: Boolean, default: false },
    },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const open = ref(false);
        const root = ref(null);

        const select = (emoji) => {
            emit('update:modelValue', emoji);
            open.value = false;
        };
        const onInput = (event) => emit('update:modelValue', event.target.value);

        const onDocumentClick = (event) => {
            if (open.value && root.value && !root.value.contains(event.target)) {
                open.value = false;
            }
        };
        document.addEventListener('click', onDocumentClick);
        onBeforeUnmount(() => document.removeEventListener('click', onDocumentClick));

        return { EMOJI_OPTIONS, onInput, open, root, select };
    },
    template: `
        <div ref="root" class="relative inline-flex flex-wrap items-center gap-3">
            <button
                type="button"
                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl border-2 bg-white text-3xl transition hover:border-[#0d5ba6]"
                :class="invalid ? 'border-rose-400' : 'border-slate-300'"
                :aria-label="modelValue ? 'Change emoji, currently ' + modelValue : 'Choose an emoji'"
                @click="open = !open"
            >
                <span v-if="modelValue" class="leading-normal">{{ modelValue }}</span>
                <i v-else class="fa-regular fa-face-smile text-slate-300" aria-hidden="true"></i>
            </button>

            <div class="flex flex-col gap-1.5">
                <button type="button" class="inline-flex items-center gap-2 self-start rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:border-[#07559e] hover:text-[#07559e]" @click="open = !open">
                    <i class="fa-regular fa-face-smile" aria-hidden="true"></i>{{ modelValue ? 'Change emoji' : 'Choose emoji' }}
                </button>
                <input :value="modelValue" @input="onInput" placeholder="Or paste one here" maxlength="8" class="w-40 rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" />
            </div>

            <div v-if="open" class="absolute top-full z-20 mt-2 grid w-72 grid-cols-8 gap-1 rounded-xl border border-slate-200 bg-white p-3 shadow-lg">
                <button v-for="emoji in EMOJI_OPTIONS" :key="emoji" type="button" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg leading-normal hover:bg-sky-100" :aria-label="'Use ' + emoji" @click="select(emoji)">{{ emoji }}</button>
            </div>
        </div>
    `,
});
