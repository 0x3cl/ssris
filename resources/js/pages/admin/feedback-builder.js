import { Head, Link, router } from '@inertiajs/vue3';
import { defineComponent, reactive, ref, watch } from 'vue';
import AdminShell from '../../components/AdminShell';
import DeleteConfirmationModal from '../../components/DeleteConfirmationModal';

const moveBefore = (ids, sourceId, targetId) => {
    const sourceIndex = ids.indexOf(sourceId);
    const targetIndex = ids.indexOf(targetId);
    if (sourceIndex < 0 || targetIndex < 0 || sourceIndex === targetIndex) return ids;

    const reordered = [...ids];
    reordered.splice(sourceIndex, 1);
    reordered.splice(reordered.indexOf(targetId), 0, sourceId);

    return reordered;
};

const OPEN_DIMENSIONS_STORAGE_KEY = 'srris.feedback-builder.open-dimensions';

export default defineComponent({
    name: 'AdminFeedbackBuilder',
    components: { AdminShell, DeleteConfirmationModal, Head, Link },
    props: { dimensions: { type: Array, required: true } },
    setup(props) {
        const newItemText = reactive({});
        const addingItem = reactive({});
        let storedOpenDimensions = {};

        try {
            const storedValue = JSON.parse(window.localStorage.getItem(OPEN_DIMENSIONS_STORAGE_KEY) ?? '{}');
            storedOpenDimensions = storedValue !== null && typeof storedValue === 'object' && !Array.isArray(storedValue)
                ? storedValue
                : {};
        } catch {
            storedOpenDimensions = {};
        }

        const openDimensions = reactive(Object.fromEntries(props.dimensions.map((dimension, index) => [
            dimension.id,
            typeof storedOpenDimensions[dimension.id] === 'boolean' ? storedOpenDimensions[dimension.id] : index === 0,
        ])));
        const editingItem = ref(null);
        const savingItem = ref(false);
        const dragging = reactive({ dimensionId: null, itemDimensionId: null, itemId: null });
        const deleting = reactive({ processing: false, type: null, dimension: null, item: null });

        const dragEnd = () => {
            dragging.dimensionId = null;
            dragging.itemDimensionId = null;
            dragging.itemId = null;
        };
        const persistOpenDimensions = () => {
            try {
                window.localStorage.setItem(OPEN_DIMENSIONS_STORAGE_KEY, JSON.stringify(openDimensions));
            } catch {
                return;
            }
        };
        const toggleDimension = (dimensionId) => {
            openDimensions[dimensionId] = !openDimensions[dimensionId];
            persistOpenDimensions();
        };

        watch(() => props.dimensions, (dimensions) => {
            dimensions.forEach((dimension) => {
                if (!Object.prototype.hasOwnProperty.call(openDimensions, dimension.id)) {
                    openDimensions[dimension.id] = false;
                }
            });
            persistOpenDimensions();
        });
        const addItem = (dimension) => {
            const description = (newItemText[dimension.id] ?? '').trim();
            if (!description) return;

            addingItem[dimension.id] = true;
            router.post(`/admin/feedback-builder/${dimension.id}/items`, { description }, {
                preserveScroll: true,
                onSuccess: () => { newItemText[dimension.id] = ''; },
                onFinish: () => { addingItem[dimension.id] = false; },
            });
        };
        const startEditItem = (dimensionId, item) => { editingItem.value = { dimensionId, itemId: item.id, description: item.description }; };
        const cancelEditItem = () => { editingItem.value = null; };
        const saveEditItem = () => {
            if (!editingItem.value) return;

            savingItem.value = true;
            router.put(`/admin/feedback-builder/${editingItem.value.dimensionId}/items/${editingItem.value.itemId}`, { description: editingItem.value.description }, {
                preserveScroll: true,
                onSuccess: () => { editingItem.value = null; },
                onFinish: () => { savingItem.value = false; },
            });
        };
        const startDimensionDrag = (dimensionId, event) => {
            dragging.dimensionId = dimensionId;
            event.dataTransfer.effectAllowed = 'move';
        };
        const dropDimension = (targetDimensionId) => {
            if (dragging.dimensionId === null) return;

            const dimensionIds = moveBefore(props.dimensions.map((dimension) => dimension.id), dragging.dimensionId, targetDimensionId);
            dragging.dimensionId = null;
            router.put('/admin/feedback-builder/order', { dimension_ids: dimensionIds }, { preserveScroll: true });
        };
        const startItemDrag = (dimensionId, itemId, event) => {
            dragging.itemDimensionId = dimensionId;
            dragging.itemId = itemId;
            event.dataTransfer.effectAllowed = 'move';
        };
        const dropItem = (dimension, targetItemId) => {
            if (dragging.itemDimensionId !== dimension.id || dragging.itemId === null) return;

            const itemIds = moveBefore(dimension.items.map((item) => item.id), dragging.itemId, targetItemId);
            dragEnd();
            router.put(`/admin/feedback-builder/${dimension.id}/items/order`, { item_ids: itemIds }, { preserveScroll: true });
        };
        const removeDimension = (dimension) => {
            deleting.type = 'dimension';
            deleting.dimension = dimension;
        };
        const removeItem = (dimensionId, item) => {
            deleting.type = 'item';
            deleting.dimension = { id: dimensionId };
            deleting.item = item;
        };
        const deletingName = () => (deleting.type === 'dimension' ? deleting.dimension?.name : deleting.item?.description);
        const confirmDelete = (deleteCode) => {
            deleting.processing = true;
            const url = deleting.type === 'dimension'
                ? `/admin/feedback-builder/${deleting.dimension.id}`
                : `/admin/feedback-builder/${deleting.dimension.id}/items/${deleting.item.id}`;
            router.delete(url, {
                data: { delete_code: deleteCode },
                onSuccess: () => {
                    deleting.type = null;
                    deleting.dimension = null;
                    deleting.item = null;
                },
                onFinish: () => { deleting.processing = false; },
            });
        };

        return { addingItem, addItem, cancelEditItem, confirmDelete, deleting, deletingName, dragEnd, dragging, dropDimension, dropItem, editingItem, newItemText, openDimensions, removeDimension, removeItem, saveEditItem, savingItem, startDimensionDrag, startEditItem, startItemDrag, toggleDimension };
    },
    template: `
        <Head title="Feedback Builder" />
        <AdminShell active="feedback-builder" title="Feedback Builder">
            <section class="border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-[#07559e]">Feedback Builder</p><h2 class="mt-1 text-2xl font-bold text-slate-900">Customer satisfaction survey</h2><p class="mt-1 max-w-2xl text-slate-600">Drag dimensions or descriptions by their handles to set the survey order. Open a dimension to manage its descriptions.</p></div>
                    <div class="flex flex-wrap gap-3"><Link href="/admin/feedback-builder/ratings" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-[#07559e] hover:text-[#07559e]"><i class="fa-solid fa-star-half-stroke" aria-hidden="true"></i>Rating Scales</Link><Link href="/admin/feedback-builder/questions" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-[#07559e] hover:text-[#07559e]"><i class="fa-solid fa-circle-question" aria-hidden="true"></i>Questions</Link><a href="/admin/feedback-builder/visualize" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#07559e] px-4 py-2.5 text-sm font-bold text-[#07559e] transition hover:bg-sky-50"><i class="fa-solid fa-eye" aria-hidden="true"></i>Visualize</a><Link href="/admin/feedback-builder/create" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#00aeef] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#009bd8]"><i class="fa-solid fa-plus" aria-hidden="true"></i>Add dimension</Link></div>
                </div>
                <div class="mt-8 space-y-4">
                    <article v-for="dimension in dimensions" :key="dimension.id" class="border border-slate-200 transition" :class="dragging.dimensionId === dimension.id ? 'opacity-50' : ''" @dragover.prevent @drop.prevent="dropDimension(dimension.id)">
                        <header class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 px-5 py-4"><div class="flex min-w-0 items-center gap-3"><button type="button" class="cursor-grab rounded-lg p-2 text-slate-400 transition hover:bg-slate-200 hover:text-[#07559e] active:cursor-grabbing" draggable="true" aria-label="Drag to reorder dimension" @dragstart="startDimensionDrag(dimension.id, $event)" @dragend="dragEnd"><i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></button><button type="button" class="flex min-w-0 items-center gap-3 text-left" :aria-expanded="Boolean(openDimensions[dimension.id])" @click="toggleDimension(dimension.id)"><i class="fa-solid text-sm text-[#07559e]" :class="openDimensions[dimension.id] ? 'fa-chevron-down' : 'fa-chevron-right'" aria-hidden="true"></i><span class="truncate text-base font-bold uppercase tracking-wide text-slate-900">{{ dimension.name }}</span><span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-[#07559e]">{{ dimension.items.length }}</span></button></div><div class="flex items-center gap-1"><Link :href="'/admin/feedback-builder/' + dimension.id + '/edit'" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit</Link><button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50" @click="removeDimension(dimension)"><i class="fa-solid fa-trash" aria-hidden="true"></i>Delete</button></div></header>
                        <div v-show="openDimensions[dimension.id]" class="border-t border-slate-200 p-5">
                            <ul v-if="dimension.items.length" class="divide-y divide-slate-100"><li v-for="item in dimension.items" :key="item.id" class="py-4 transition" :class="dragging.itemId === item.id ? 'opacity-50' : ''" draggable="true" @dragstart.stop="startItemDrag(dimension.id, item.id, $event)" @dragend="dragEnd" @dragover.prevent @drop.stop.prevent="dropItem(dimension, item.id)"><div v-if="editingItem && editingItem.itemId === item.id" class="flex flex-wrap items-start gap-3"><textarea v-model="editingItem.description" rows="2" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100"></textarea><div class="flex gap-2"><button type="button" class="rounded-lg bg-[#00aeef] px-3 py-2 text-sm font-bold text-white hover:bg-[#009bd8] disabled:cursor-not-allowed disabled:opacity-60" :disabled="savingItem" @click="saveEditItem">Save</button><button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50" :disabled="savingItem" @click="cancelEditItem">Cancel</button></div></div><div v-else class="flex flex-wrap items-start justify-between gap-3"><div class="flex min-w-0 max-w-2xl items-center gap-3"><span class="flex h-7 w-7 shrink-0 cursor-grab items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-[#07559e] active:cursor-grabbing"><i class="fa-solid fa-grip-vertical" aria-hidden="true"></i></span><p class="text-sm leading-6 text-slate-700">{{ item.description }}</p></div><div class="flex shrink-0 items-center gap-2"><button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-[#07559e] hover:bg-sky-100" @click="startEditItem(dimension.id, item)"><i class="fa-solid fa-pen" aria-hidden="true"></i>Edit</button><button type="button" class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-bold text-rose-600 hover:bg-rose-50" @click="removeItem(dimension.id, item)"><i class="fa-solid fa-trash" aria-hidden="true"></i>Delete</button></div></div></li></ul>
                            <p v-else class="text-sm text-slate-500">No descriptions yet for this dimension.</p><form class="mt-4 flex flex-wrap items-start gap-3 border-t border-slate-100 pt-4" @submit.prevent="addItem(dimension)"><input v-model="newItemText[dimension.id]" placeholder="Add a new description" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#00aeef] focus:ring-4 focus:ring-sky-100" /><button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:border-[#07559e] hover:text-[#07559e] disabled:cursor-not-allowed disabled:opacity-60" :disabled="addingItem[dimension.id]"><i class="fa-solid fa-plus" aria-hidden="true"></i>{{ addingItem[dimension.id] ? 'Adding…' : 'Add description' }}</button></form>
                        </div>
                    </article>
                    <p v-if="dimensions.length === 0" class="border border-dashed border-slate-300 p-8 text-center text-slate-500">No dimensions yet. Add your first dimension to start building the survey.</p>
                </div>
            </section>
            <DeleteConfirmationModal :open="Boolean(deleting.type)" :item-name="deletingName()" :processing="deleting.processing" @close="deleting.type = null" @confirm="confirmDelete" />
        </AdminShell>
    `,
});
