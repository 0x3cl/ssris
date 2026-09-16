import { Head, router } from '@inertiajs/vue3';
import { defineComponent, reactive } from 'vue';
import AdminPagination from '../../components/AdminPagination';
import AdminShell from '../../components/AdminShell';
import DeleteConfirmationModal from '../../components/DeleteConfirmationModal';

export default defineComponent({
    name: 'AdminFeedbackQuestions',
    components: { AdminPagination, AdminShell, DeleteConfirmationModal, Head },
    props: { filters: { type: Object, required: true }, questions: { type: Object, required: true } },
    setup(props) {
        const filters = reactive({ ...props.filters });
        const deleting = reactive({ processing: false, question: null });
        const load = () => router.get('/admin/feedback-builder/questions', filters, { preserveScroll: true, preserveState: true, replace: true });
        const page = (url) => { if (url) router.get(url, {}, { preserveScroll: true }); };
        const remove = (question) => { deleting.question = question; };
        const confirmDelete = (deleteCode) => {
            if (!deleting.question) return;
            deleting.processing = true;
            router.delete(`/admin/feedback-builder/questions/${deleting.question.id}`, {
                data: { delete_code: deleteCode },
                onSuccess: () => { deleting.question = null; },
                onFinish: () => { deleting.processing = false; },
            });
        };

        return { confirmDelete, deleting, filters, load, page, remove };
    },
    template: `
        <Head title="Questions" />
        <AdminShell active="feedback-builder" title="Questions">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Feedback Builder</p>
                        <h2 class="mt-1 text-2xl font-bold text-slate-900">Questions</h2>
                        <p class="mt-1 text-slate-600">Open-ended questions asked outside the rated dimensions, e.g. "Areas for improvement".</p>
                    </div>
                    <a href="/admin/feedback-builder" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 hover:border-[#07559e] hover:text-[#07559e]">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Back to dimensions
                    </a>
                </div>
                <div class="mt-7 flex flex-wrap items-end justify-between gap-4">
                    <label class="text-sm font-semibold text-slate-700">
                        Entries
                        <select v-model="filters.entries" class="ml-2 rounded-lg border border-slate-300 px-3 py-2" @change="load">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                    </label>
                    <a href="/admin/feedback-builder/questions/create" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00aeef] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#009bd8]">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>Add question
                    </a>
                </div>
                <div class="mt-6 overflow-x-auto">
                    <table class="w-full min-w-[500px] text-left">
                        <thead class="border-y border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-4">Name</th>
                                <th class="px-4 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="question in questions.data" :key="question.id" class="border-b border-slate-100 hover:bg-sky-50/50">
                                <td class="px-4 py-4 text-sm text-slate-700">{{ question.name }}</td>
                                <td class="px-4 py-4 text-right">
                                    <a :href="'/admin/feedback-builder/questions/' + question.id + '/edit'" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit</a>
                                    <button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50" @click="remove(question)"><i class="fa-solid fa-trash" aria-hidden="true"></i>Delete</button>
                                </td>
                            </tr>
                            <tr v-if="questions.data.length === 0">
                                <td colspan="2" class="px-4 py-12 text-center text-slate-500">No questions yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <AdminPagination :pagination="questions" @page="page" />
            </section>
            <DeleteConfirmationModal :open="Boolean(deleting.question)" :item-name="deleting.question?.name" :processing="deleting.processing" @close="deleting.question = null" @confirm="confirmDelete" />
        </AdminShell>
    `,
});
