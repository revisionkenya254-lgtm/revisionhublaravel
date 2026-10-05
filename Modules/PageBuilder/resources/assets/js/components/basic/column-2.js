import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<svg viewBox="0 0 24 24">
  <path fill="currentColor" d="M2 20h9V4H2v16Zm11 0h9V4h-9v16ZM1 20V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1Zm11 0V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1h-9a1 1 0 0 1-1-1Z"/>
</svg>
`,
        model: {
            defaults: {
                name: 'Column 2',
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
                        classes: ['editor-preview-helper', 'col-lg-6'],
                        droppable: true,
                    },
                    {
                        tagName: 'div',
                        droppable: true,
                        draggable: true,
                        copyable: true,
                        stylable: true,
                        classes: ['editor-preview-helper', 'col-lg-6'],
                        droppable: true,
                    },
                ],
            },
        },
    });
};
