import Quill from 'quill';
import 'quill/dist/quill.snow.css';

/**
 * Rich text editor (RTL) bound to a hidden input/textarea.
 * Markup: <div class="editor-wrap" data-editor data-target="#body" data-placeholder="..."></div>
 */
const boot = () => {
    document.querySelectorAll('[data-editor]:not([data-editor-ready])').forEach((wrapper) => {
        const target = document.querySelector(wrapper.dataset.target);
        if (!target) return;

        const holder = document.createElement('div');
        wrapper.appendChild(holder);

        const quill = new Quill(holder, {
            theme: 'snow',
            placeholder: wrapper.dataset.placeholder || 'כתבו כאן…',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['blockquote', 'link'],
                    ['clean'],
                ],
            },
        });

        quill.format('direction', 'rtl');
        quill.format('align', 'right');

        if (target.value) {
            quill.clipboard.dangerouslyPasteHTML(target.value);
        }

        const sync = () => {
            const html = quill.getText().trim() === '' ? '' : quill.root.innerHTML;
            target.value = html;
            target.dispatchEvent(new Event('input', { bubbles: true }));
        };

        quill.on('text-change', sync);
        target.form?.addEventListener('submit', sync);
        wrapper.setAttribute('data-editor-ready', '1');
    });
};

document.addEventListener('DOMContentLoaded', boot);
boot();
