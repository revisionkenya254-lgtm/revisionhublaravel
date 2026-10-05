import { registerComponent } from '@utils/registerComponent';
import featureOneIcon from '@images/icons/features_icon01.svg';
import featureTwoIcon from '@images/icons/features_icon02.svg';
import featureThreeIcon from '@images/icons/features_icon03.svg';
import featureFourIcon from '@images/icons/features_icon04.svg';
import mediaImage from '@images/sections/features.webp';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "features__area" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },

                    {
                        type: 'accordion',
                        label: 'Features',
                        name: 'features',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Lorem Ipsum" },
                            { name: 'description', type: 'textarea', label: 'Description', placeholder: 'Description', default: "Curate anding area share Pluralsight content to reach your" },
                            { name: 'icon', type: 'image-upload', label: 'Icon', default: featureOneIcon }
                        ]
                    }
                ],
                sub_title: 'How We Start Journey',
                title: 'Start your Learning Journey Today!',
                description: 'Groove’s intuitive shared inbox makesteam members together <br> organize, prioritize and.In this episode.',

                features: [
                    { title: 'Learn with Experts', description: 'Curate anding area share Pluralsight content to reach your' , icon: featureOneIcon },
                    { title: 'Learn Anything', description: 'Curate anding area share Pluralsight content to reach your' , icon: featureTwoIcon },
                    { title: 'Get Online Certificate', description: 'Curate anding area share Pluralsight content to reach your' , icon: featureThreeIcon },
                    { title: 'E-mail Marketing', description: 'Curate anding area share Pluralsight content to reach your' , icon: featureFourIcon },
                ],
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title change:description change:features', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const generateHtml = (this.get('features') || []).map(feature => `
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="features__item">
                            <div class="features__icon">
                                <img src="${feature.icon}" alt="${feature.title}">
                            </div>
                            <div class="features__content">
                                <p class="title lh-base">${feature.title}</p>
                                <p>${feature.description}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                const html = `
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-xl-6">
                                <div class="section__title white-title text-center mb-50">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title">${this.get('title')}</h2>
                                    <p>${this.get('description')}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            ${generateHtml}
                        </div>
                    </div>
                `;
                // Update the components
                this.components(html);
                // Apply rules recursively
                this.applyRules(this, true);
            },
            // Recursive function to set editable/draggable/selectable etc.
            applyRules(comp, root = false) {
                comp.set({
                    editable: false,
                    draggable: root,
                    droppable: false,
                    copyable: root,
                    selectable: root,
                });
                comp.components().forEach(child => this.applyRules(child));
            },
        },
    });
};
