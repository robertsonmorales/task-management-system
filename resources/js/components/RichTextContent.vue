<script setup lang="ts">
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { watch } from 'vue';

const props = defineProps<{
    content: string;
}>();

/**
 * Render stored rich text read-only through the editor schema, so unknown or unsafe markup is dropped instead of injected.
 */
const editor = useEditor({
    content: props.content,
    editable: false,
    extensions: [StarterKit],
    editorProps: {
        attributes: {
            class: 'text-sm leading-relaxed focus:outline-none [&_ol]:my-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1 [&_strong]:font-semibold [&_ul]:my-1 [&_ul]:list-disc [&_ul]:pl-5',
        },
    },
});

watch(
    () => props.content,
    (value) => editor.value?.commands.setContent(value, { emitUpdate: false }),
);
</script>

<template>
    <EditorContent :editor="editor" />
</template>
