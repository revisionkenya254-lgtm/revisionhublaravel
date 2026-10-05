import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<svg viewBox="0 0 24 24">
  <path fill="currentColor" d="M2 20h6V4H2v16Zm7 0h6V4h-6v16Zm7 0h6V4h-6v16ZM1 20V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1Zm7 0V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1Zm7 0V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-6a1 1 0 0 1-1-1Z"/>
</svg>
`,
        model: {
            defaults: {
                name: 'Column 3',
                tagName: 'div',
                classes: ['editor-preview-helper', 'row'],
                droppable: true,
                draggable: true,
                copyable: true,
                stylable: true,
                components: [
                    {
                        tagName: 'div',
                        droppable: true,
                        draggable: true,
                        copyable: true,
                        stylable: true,
                        classes: ['editor-preview-helper', 'col-lg-4'],
                        droppable: true,
                    },
                    {
                        tagName: 'div',
                        droppable: true,
                        draggable: true,
                        copyable: true,
                        stylable: true,
                        classes: ['editor-preview-helper', 'col-lg-4'],
                        droppable: true,
                    },
                    {
                        tagName: 'div',
                        droppable: true,
                        draggable: true,
                        copyable: true,
                        stylable: true,
                        classes: ['editor-preview-helper', 'col-lg-4'],
                        droppable: true,
                    },
                ],
            },
        },
    });
};
