import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';
import EmojiPickerInput from '../../components/EmojiPickerInput';

export default defineComponent({
    name: 'AdminFeedbackRatingForm',
    components: { AdminShell, EmojiPickerInput, Head, Link },
    props: { rating: { type: Object, default: null } },
    setup(props) {
        const isEditing = computed(() => Boolean(props.rating));
        const form = useForm({ name: props.rating?.name ?? '', value: props.rating?.value ?? '', weight: props.rating?.weight ?? '', emoji: props.rating?.emoji ?? '' });
        const weightRequired = computed(() => form.value.trim().toUpperCase() !== 'N/A');
        const save = () => {
            if (isEditing.value) {
                form.put(`/admin/feedback-builder/ratings/${props.rating.id}`);
            } else {
                form.post('/admin/feedback-builder/ratings');
            }
        };

        return { form, isEditing, save, weightRequired };
    },
    template: `
        <Head :title="isEditing ? 'Edit rating' : 'Add rating'" />
        <AdminShell active="feedback-builder" :title="isEditing ? 'Edit rating' : 'Add rating'">
            <section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Feedback Builder</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ isEditing ? 'Update rating' : 'Create rating' }}</h2>
                        <p class="mt-1 text-slate-600">A rating option on the scale, e.g. "5" &middot; "Excellent" or "N/A" &middot; "Not Applicable".</p>
                    </div>
                    <Link href="/admin/feedback-builder/ratings" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:border-[#07559e] hover:text-[#07559e]">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to ratings
                    </Link>
                </div>

                <div class="mt-7 flex items-center gap-4 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-white text-3xl ring-1 ring-slate-200"><span v-if="form.emoji" class="leading-normal">{{ form.emoji }}</span><i v-else class="fa-regular fa-face-smile text-slate-300" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <p class="truncate font-bold text-slate-900">{{ form.name || 'Rating name' }}</p>
                        <p class="text-sm text-slate-500">Value: {{ form.value || '—' }} &middot; Weight: {{ form.weight === '' ? '—' : form.weight }}</p>
                    </div>
                </div>

                <form class="mt-7 grid gap-5 sm:grid-cols-2" @submit.prevent="save">
                    <label class="block sm:col-span-2">
                        <span class="required-label text-sm font-semibold text-slate-700">Emoji</span>
                        <div class="mt-2"><EmojiPickerInput v-model="form.emoji" :invalid="Boolean(form.errors.emoji)" /></div>
                        <p class="mt-1 text-sm text-slate-500">Shown next to this rating wherever the scale is displayed.</p>
                        <p v-if="form.errors.emoji" class="mt-1 text-sm text-rose-600">{{ form.errors.emoji }}</p>
                    </label>
                    <label class="block">
                        <span class="required-label text-sm font-semibold text-slate-700">Value</span>
                        <input v-model="form.value" placeholder="e.g. 5 or N/A" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.value }" />
                        <p v-if="form.errors.value" class="mt-1 text-sm text-rose-600">{{ form.errors.value }}</p>
                    </label>
                    <label class="block">
                        <span class="required-label text-sm font-semibold text-slate-700">Name</span>
                        <input v-model="form.name" placeholder="e.g. Excellent or Not Applicable" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.name }" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="block text-sm font-semibold text-slate-700" :class="{ 'required-label': weightRequired }">Weight</span>
                        <input v-model="form.weight" type="number" step="0.01" placeholder="e.g. 5" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100 sm:w-1/2" :class="{ 'border-rose-500': form.errors.weight }" />
                        <p class="mt-1 text-sm text-slate-500">The numeric score this option contributes to feedback report averages. Required, unless the Value is exactly "N/A" (a non-scoring option).</p>
                        <p v-if="form.errors.weight" class="mt-1 text-sm text-rose-600">{{ form.errors.weight }}</p>
                    </label>
                    <div class="mt-2 flex justify-end gap-3 border-t border-slate-200 pt-5 sm:col-span-2">
                        <Link href="/admin/feedback-builder/ratings" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold text-white hover:bg-[#009bd8] disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>{{ isEditing ? 'Save changes' : 'Create rating' }}
                        </button>
                    </div>
                </form>
            </section>
        </AdminShell>
    `,
});
