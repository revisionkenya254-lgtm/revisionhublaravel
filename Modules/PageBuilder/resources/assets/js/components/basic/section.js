import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path d="M3 4h18v2H3zm0 6h18v2H3zm0 6h18v2H3z"/>
        </svg>`,
        model: {
            defaults: {
                name: 'Section',
                tagName: 'section',
                classes: ['section-py-120'],
                droppable: false,
                draggable: true,
                copyable: true,
                stylable: true,
                // Setup initial inner container
                components: [
                    {
                        tagName: 'div',
                        droppable: true,
                        draggable: true,
                        copyable: true,
                        stylable: true,
                        classes: ['editor-preview-helper', 'container'],
                        droppable: true,
                        components: [],
                    },
                ],
            },
        },
    });
};
