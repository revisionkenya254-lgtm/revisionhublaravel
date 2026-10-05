import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName }) => {
  registerComponent({
    editor,
    name: fileName,
    category: folderName,
    media: `<svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
        <path d="M7 4h14v2H7V4zm0 7h14v2H7v-2zm0 7h14v2H7v-2zM3 4h2v2H3V4zm0 7h2v2H3v-2zm0 7h2v2H3v-2z"/>
      </svg>`,
    model: {
      defaults: {
        name: 'List',
        tagName: 'ul',
        draggable: true,
        droppable: false,
        copyable: true,
        stylable: true,
        selectable: true,
        editable: false,
        traits: [
          {
            type: 'select',
            name: 'tagType',
            label: 'Tag',
            changeProp: true,
            options: [
              { value: 'ul', name: 'Unordered List (ul)' },
              { value: 'ol', name: 'Ordered List (ol)' },
            ],
          },
          {
            type: 'textarea',
            name: 'items',
            label: 'List Items (Enter one item per line)',
            placeholder: 'Enter one item per line',
            changeProp: true,
          },
        ],
        tagType: 'ul',
        items: 'Item 1\nItem 2',
      },

      init() {
        const tag = this.get('tagType') || 'ul';
        const items = this.get('items') || 'Item 1\nItem 2';

        this.set('tagName', tag);

        // Listen for tag or items change
        this.listenTo(this, 'change:tagType', this.handleTagChange);
        this.listenTo(this, 'change:items', this.updateItems);

        // Initial render
        this.updateItems();
      },

      handleTagChange() {
        const newTag = this.get('tagType');
        if (newTag) this.set('tagName', newTag);
      },

      updateItems() {
        const rawItems = this.get('items') || '';
        const lines = rawItems.split('\n').map(i => i.trim()).filter(Boolean);
        const liComps = lines.map(text => ({
          tagName: 'li',
          content: text,
          selectable: false,
          editable: false,
          draggable: false,
          copyable: false,
          stylable: false,
          droppable: false,
        }));
        this.components(liComps);
      },
    },
  });
};
