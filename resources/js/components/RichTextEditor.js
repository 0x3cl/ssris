import { defineComponent, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const QUILL_JS = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js';
const QUILL_CSS = 'https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css';

let quillLoader = null;

/** Loads the Quill rich text editor from a CDN once, sharing the promise across instances. */
function loadQuill() {
    if (window.Quill) {
        return Promise.resolve(window.Quill);
    }

    if (!quillLoader) {
        quillLoader = new Promise((resolve, reject) => {
            if (!document.querySelector(`link[href="${QUILL_CSS}"]`)) {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = QUILL_CSS;
                document.head.appendChild(link);
            }

            if (!document.getElementById('rich-text-editor-overrides')) {
                const style = document.createElement('style');
                style.id = 'rich-text-editor-overrides';
                style.textContent = '.ql-toolbar.ql-snow{border:none;border-bottom:1px solid #cbd5e1}.ql-container.ql-snow{border:none;font-size:1rem}';
                document.head.appendChild(style);
            }

            const script = document.createElement('script');
            script.src = QUILL_JS;
            script.async = true;
            script.onload = () => resolve(window.Quill);
            script.onerror = () => reject(new Error('Failed to load the rich text editor.'));
            document.head.appendChild(script);
        });
    }

    return quillLoader;
}

export default defineComponent({
    name: 'RichTextEditor',
    props: { modelValue: { type: String, default: '' } },
    emits: ['update:modelValue'],
    setup(props, { emit }) {
        const container = ref(null);
        const ready = ref(false);
        let quill = null;
        let applyingExternalValue = false;

        onMounted(async () => {
            const Quill = await loadQuill();

            if (!container.value) {
                return;
            }

            quill = new Quill(container.value, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        ['link'],
                        ['clean'],
                    ],
                },
            });
            applyingExternalValue = true;
            quill.clipboard.dangerouslyPasteHTML(props.modelValue ?? '');
            applyingExternalValue = false;
            quill.on('text-change', () => {
                if (applyingExternalValue) {
                    return;
                }
                const html = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
                emit('update:modelValue', html);
            });
            ready.value = true;
        });

        watch(() => props.modelValue, (value) => {
            if (!quill || value === quill.root.innerHTML) {
                return;
            }
            applyingExternalValue = true;
            quill.clipboard.dangerouslyPasteHTML(value ?? '');
            applyingExternalValue = false;
        });

        onBeforeUnmount(() => {
            quill = null;
        });

        return { container, ready };
    },
    template: `
        <div class="overflow-hidden rounded-lg border border-slate-300 focus-within:border-[#00aeef] focus-within:ring-4 focus-within:ring-sky-100">
            <p v-if="!ready" class="px-4 py-3 text-sm text-slate-400">Loading editor…</p>
            <div v-show="ready" ref="container" class="min-h-[220px] bg-white"></div>
        </div>
    `,
});
