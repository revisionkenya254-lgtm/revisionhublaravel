import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<svg viewBox="0 0 24 24">
        <path fill="currentColor" d="M2 20h20V4H2v16Zm-1 0V4a1 1 0 0 1 1-1h20a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1Z"></path>
      </svg>`,
        model: {
            defaults: {
                name: 'Column 1',
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
                        classes: ['editor-preview-helper', 'col-lg-12'],
                        droppable: true,
                    },
                ],
            },
        },
    });
};
