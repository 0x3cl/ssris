import { computed, defineComponent, ref } from 'vue';

export default defineComponent({
    name: 'ProfileImageUpload',
    emits: ['select'],
    props: {
        centered: { type: Boolean, default: false },
        imageUrl: { type: String, default: '' },
        initials: { type: String, default: '?' },
    },
    setup(props, { emit }) {
        const preview = ref('');
        const source = computed(() => preview.value || props.imageUrl);
        const select = (event) => {
            const file = event.target.files[0] ?? null;
            preview.value = file ? URL.createObjectURL(file) : '';
            emit('select', file);
        };
        return { select, source };
    },
    template: `<div :class="centered ? 'flex flex-col items-center text-center' : ''"><p class="text-sm font-semibold text-slate-700">Profile image</p><label class="group relative mt-3 block h-28 w-28 cursor-pointer"><span class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-sky-300 bg-sky-50 transition group-hover:border-[#00aeef] group-hover:bg-sky-100"><img v-if="source" :src="source" alt="Profile preview" class="h-full w-full object-cover" /><span v-else class="text-4xl font-bold text-[#07559e]">{{ initials }}</span></span><span class="absolute -bottom-1 -right-1 z-10 flex h-10 w-10 items-center justify-center rounded-full border-2 border-white bg-[#07559e] text-white shadow-md transition group-hover:bg-[#00aeef]"><i class="fa-solid fa-camera" aria-hidden="true"></i></span><input type="file" accept="image/*" class="sr-only" @change="select" /></label><p class="mt-4 text-xs leading-5 text-slate-500" :class="centered ? 'max-w-48' : ''">Select an image or use the camera button.</p></div>`,
});
