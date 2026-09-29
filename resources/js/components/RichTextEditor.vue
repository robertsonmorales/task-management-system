<script setup lang="ts">
import { cn } from '@/lib/utils';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { Bold, Italic, List, ListOrdered } from 'lucide-vue-next';
import { watch, type HTMLAttributes } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [StarterKit, Placeholder.configure({ placeholder: props.placeholder })],
    editorProps: {
        attributes: {
            class: 'min-h-[120px] px-3 py-2 text-sm focus:outline-none [&_.is-editor-empty:first-child::before]:pointer-events-none [&_.is-editor-empty:first-child::before]:float-left [&_.is-editor-empty:first-child::before]:h-0 [&_.is-editor-empty:first-child::before]:text-muted-foreground [&_.is-editor-empty:first-child::before]:content-[attr(data-placeholder)] [&_ol]:my-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:my-1 [&_strong]:font-semibold [&_ul]:my-1 [&_ul]:list-disc [&_ul]:pl-5',
        },
    },
    onUpdate: ({ editor: instance }) => emit('update:modelValue', instance.getHTML()),
});

watch(
    () => props.modelValue,
    (value) => {
        if (editor.value && value !== editor.value.getHTML()) {
            editor.value.commands.setContent(value, false);
        }
    },
);
</script>

<template>
    <div
        :class="
            cn(
                'rounded-md border border-input bg-background ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                props.class,
            )
        "
    >
        <div v-if="editor" class="flex items-center gap-1 border-b border-input p-1">
            <button
                type="button"
                :class="
                    cn(
                        'inline-flex h-7 w-7 items-center justify-center rounded-sm hover:bg-accent hover:text-accent-foreground',
                        editor.isActive('bold') && 'bg-accent text-accent-foreground',
                    )
                "
                @click="editor.chain().focus().toggleBold().run()"
            >
                <Bold class="h-4 w-4" />
            </button>
            <button
                type="button"
                :class="
                    cn(
                        'inline-flex h-7 w-7 items-center justify-center rounded-sm hover:bg-accent hover:text-accent-foreground',
                        editor.isActive('italic') && 'bg-accent text-accent-foreground',
                    )
                "
                @click="editor.chain().focus().toggleItalic().run()"
            >
                <Italic class="h-4 w-4" />
            </button>
            <button
                type="button"
                :class="
                    cn(
                        'inline-flex h-7 w-7 items-center justify-center rounded-sm hover:bg-accent hover:text-accent-foreground',
                        editor.isActive('bulletList') && 'bg-accent text-accent-foreground',
                    )
                "
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                <List class="h-4 w-4" />
            </button>
            <button
                type="button"
                :class="
                    cn(
                        'inline-flex h-7 w-7 items-center justify-center rounded-sm hover:bg-accent hover:text-accent-foreground',
                        editor.isActive('orderedList') && 'bg-accent text-accent-foreground',
                    )
                "
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                <ListOrdered class="h-4 w-4" />
            </button>
        </div>
        <EditorContent :editor="editor" />
    </div>
</template>
