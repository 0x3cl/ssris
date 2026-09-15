import { defineComponent } from 'vue';

export default defineComponent({
    name: 'ProfileImagePreviewModal',
    emits: ['close'],
    props: { imageUrl: { type: String, default: '' }, name: { type: String, default: '' }, open: { type: Boolean, default: false } },
    template: `<Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95"><div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/45 p-4" role="dialog" aria-modal="true" aria-labelledby="profile-preview-title"><section class="w-full max-w-md rounded-3xl bg-white p-7 text-center shadow-2xl"><button type="button" class="float-right text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close" @click="$emit('close')">&times;</button><h2 id="profile-preview-title" class="text-xl font-bold text-slate-900">Profile photo</h2><img :src="imageUrl" :alt="name" class="mx-auto mt-6 h-72 w-72 max-w-full rounded-2xl object-cover" /><p class="mt-4 font-semibold text-slate-700">{{ name }}</p></section></div></Transition>`,
});
