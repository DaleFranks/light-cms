{{-- Puts what the Insert image popup chose at the cursor in the content editor it was opened from. --}}
<script>
    window.addEventListener('insert-into-editor', (event) => {
        const codeMirror = document.getElementById(event.detail.editorId)?._editor?.codemirror;

        if (! codeMirror) {
            return;
        }

        codeMirror.replaceSelection(event.detail.text);

        // Once the popup has closed and handed focus back to the button that opened it.
        setTimeout(() => codeMirror.focus(), 300);
    });
</script>
