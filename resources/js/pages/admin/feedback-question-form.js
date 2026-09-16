import { Head, useForm } from '@inertiajs/vue3';
import { computed, defineComponent } from 'vue';
import AdminShell from '../../components/AdminShell';

export default defineComponent({
    name: 'AdminFeedbackQuestionForm',
    components: { AdminShell, Head },
    props: { question: { type: Object, default: null } },
    setup(props) {
        const isEditing = computed(() => Boolean(props.question));
        const form = useForm({ name: props.question?.name ?? '' });
        const save = () => {
            if (isEditing.value) {
                form.put(`/admin/feedback-builder/questions/${props.question.id}`);
            } else {
                form.post('/admin/feedback-builder/questions');
            }
        };

        return { form, isEditing, save };
    },
    template: `
        <Head :title="isEditing ? 'Edit question' : 'Add question'" />
        <AdminShell active="feedback-builder" :title="isEditing ? 'Edit question' : 'Add question'">
            <section class="w-full border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-200 pb-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Feedback Builder</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">{{ isEditing ? 'Update question' : 'Create question' }}</h2>
                        <p class="mt-1 text-slate-600">An open-ended question asked outside the rated dimensions, e.g. "Areas for improvement".</p>
                    </div>
                    <a href="/admin/feedback-builder/questions" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:border-[#07559e] hover:text-[#07559e]">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to questions
                    </a>
                </div>
                <form class="mt-7" @submit.prevent="save">
                    <label class="block max-w-xl">
                        <span class="required-label text-sm font-semibold text-slate-700">Question name</span>
                        <input v-model="form.name" placeholder="e.g. Areas for improvement" class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" :class="{ 'border-rose-500': form.errors.name }" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-rose-600">{{ form.errors.name }}</p>
                    </label>
                    <div class="mt-7 flex justify-end gap-3 border-t border-slate-200 pt-5">
                        <a href="/admin/feedback-builder/questions" class="rounded-lg border border-slate-300 px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Cancel</a>
                        <button type="submit" :disabled="form.processing" class="rounded-lg bg-[#00aeef] px-5 py-3 text-sm font-bold text-white hover:bg-[#009bd8] disabled:cursor-not-allowed disabled:opacity-60">
                            <i class="fa-solid fa-floppy-disk mr-2" aria-hidden="true"></i>{{ isEditing ? 'Save changes' : 'Create question' }}
                        </button>
                    </div>
                </form>
            </section>
        </AdminShell>
    `,
});
