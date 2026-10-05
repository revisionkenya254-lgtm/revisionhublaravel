import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<svg viewBox="0 0 24 24">
  <path fill="currentColor" d="M2 20h20V4H2v16ZM4 6h16v12H4V6Z"/>
</svg>
`,
        model: {
            defaults: {
                name: 'Container',
                tagName: 'div',
                classes: ['editor-preview-helper', 'container'],
                droppable: true,
                draggable: true,
                copyable: true,
                stylable: true,
                components: [],
            },
        },
    });
};
