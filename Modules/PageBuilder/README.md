## Project Asset Loading

The project's CSS and JavaScript are loaded from:
````
Modules/PageBuilder/resources/assets/js/builderAssets.js
````

## Component Creation Guide

### 1. Navigate to the Components Folder

Go to the following path:
````
Modules/PageBuilder/resources/assets/js/components
````

Create your component file here. Example:
````
blog.js
````

### 2. Component Structure

Each component should follow this structure:
Each GrapesJS component should follow the structure below to ensure it is:

✅ Dynamically registered

✅ Categorized based on folder name

✅ Named based on file name

✅ Easily maintainable and reusable

```
import { registerComponent } from '@utils/registerComponent';

export default (editor, { folderName, fileName },baseUrl) => {
  registerComponent({
    editor,
    name: fileName,
    category: folderName,
    media: 'font awesome / svg or image',
    model: {
        //your component data load here
    },
  });
};
```

### 3. Dynamic Component Rendering

Wrap your dynamic component in a Blade file stored at:
````
Modules/PageBuilder/resources/views/components
````
Example file name: 
````
blog.blade.php
````

Use the following wrapper syntax for dynamic content injection:

```
<!-- DYNAMIC_PART_START:blog -->

<!-- DYNAMIC_PART_END -->
```

Place your component HTML between the DYNAMIC_PART_START and DYNAMIC_PART_END comments. These markers are used for dynamic rendering and content replacement.

## Component Setup with traits

```
model: {
    defaults: {
        tagName: "section",
        attributes: { class: "about-area tg-motion-effects section-py-120" },
        traits: [
            { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
            { name: 'title', label: 'Title', type: 'text', changeProp: true },
            { name: 'about_img', label: 'About Image', type: 'image-upload', changeProp: true },
        ],
        //traits default value
        subtitle: 'Get More About Us',
        title: 'Thousand Of Top',
    },

    init() {
        const wrapper = this;
        // Use arrow function to preserve 'this' context
        wrapper.on('change:subtitle change:title change:count_img', () => wrapper.updateContent());
        // Initial render to sync content on load (including loaded JSON)
        wrapper.updateContent();
    },
    //custom update content method
    updateContent() {
        const html = `
            <div class="container">
                <div class="row align-items-center justify-content-center">
                    <div class="col-lg-6 col-md-9">
                        <img src="${this.get('about_img')}" alt="${this.get('title')}" class="main-img">
                    </div>
                    <div class="col-lg-6">
                        <span class="sub-title">${this.get('subtitle')}</span>
                        <h2 class="title">${this.get('title')}</h2>
                    </div>
                </div>
            </div>
        `;
        // Update the components
        this.components(html);
        // Apply rules recursively
        this.applyRules(this, true);
    },
    // custom edit/select rules method
    applyRules(comp, root = false) {
        comp.set({
            editable: false,
            draggable: root,
            droppable: false,
            copyable: false,
            selectable: root,
        });
        comp.components().forEach(child => this.applyRules(child));
    },
}
```

## Custom Trait Creation
To define custom trait types for your GrapesJS components, follow the guide below.

### 1. Open the Trait Definition File
Go to:
````
Modules/PageBuilder/resources/assets/js/customTraits.js
````

### 2. Example: Create a textarea Trait
Here's how to define a custom textarea trait type:
```
traitManager.addType('textarea', {
    createInput({ trait }) {
        const el = document.createElement('textarea');
        el.placeholder = trait.get('placeholder') || '';
        el.rows = 4;
        el.value = trait.get('value') || '';
        el.addEventListener('change', (e) => trait.set('value', e.target.value));
        return el;
    },
    onUpdate({ elInput, trait }) {
        elInput.value = trait.get('value') || '';
    }
});
```
#### Explanation

- ````createInput()````: Called when the trait input is rendered in the trait panel.
- Creates a ````<textarea>```` element.
- Applies placeholder, rows, and value.
- Adds a change listener to sync the value with the trait model.
- ````onUpdate()````: Called when the trait value is updated programmatically.
- Ensures the ````<textarea>```` input reflects the current trait value.



## ⚙️ Development & Build Commands
### ▶️ Run in Development Mode
To start the project in development mode (with hot-reloading):

````
npm run dev
````

Use this while actively developing components to see real-time updates.

### 📦 Build for Production
After completing or updating any component, compile the production build by running:

````
npm run build
````
This generates the optimized files required for the PageBuilder to work in production.

🔁 Always run this after making changes to components.

✅ Ensure you're in the ```Modules/PageBuilder``` directory before running the commands.
