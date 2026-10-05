import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
            <path d="M4 4v16h2v-6h8v6h2V4h-2v6H6V4H4z"/>
            </svg>`,
        model: {
        defaults: {
            tagName: 'h2',
            editable: true,
            droppable: false,
            copyable: true,
            traits: [
                {
                    type: 'select',
                    name: 'tagType',
                    label: 'Heading Tag',
                    changeProp: true,
                    options: [
                        { value: 'h1', name: 'H1' },
                        { value: 'h2', name: 'H2' },
                        { value: 'h3', name: 'H3' },
                        { value: 'h4', name: 'H4' },
                        { value: 'h5', name: 'H5' },
                        { value: 'h6', name: 'H6' },
                    ],
                },
                {
                    type: 'text',
                    name: 'content',
                    label: 'Heading Text',
                    changeProp: true,
                },
            ],
            // initial value
            tagType: 'h2',
            content: 'This is a heading',
        },

        init() {
            const tag = this.get('tagName') || 'h2';
            const content = this.get('content');
            this.set({
                tagType: tag,
                content: content,
            });
            this.listenTo(this, 'change:tagType', this.handleTagChange);
            this.on(this, 'change:content', () => this.view.render());
        },

        handleTagChange() {
            const newTag = this.get('tagType');
            if (newTag) this.set('tagName', newTag);
        },
    },
    });
};
