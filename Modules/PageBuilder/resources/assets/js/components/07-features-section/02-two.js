import { registerComponent } from '@utils/registerComponent';
import featureOneIcon from '@images/icons/features_icon01.svg';
import featureTwoIcon from '@images/icons/features_icon02.svg';
import featureThreeIcon from '@images/icons/features_icon03.svg';
import featureFourIcon from '@images/icons/features_icon04.svg';
import mediaImage from '@images/sections/feature-two.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "features__area-two section-pt-120 section-pb-90" },
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
                            { name: 'description', type: 'textarea', label: 'Description', placeholder: 'Description', default: "when an unknown printer took a galley offer type and scrambled makes." },
                            { name: 'icon', type: 'image-upload', label: 'Icon', default: featureOneIcon }
                        ]
                    }
                ],
                sub_title: 'Our Top Features',
                title: 'Achieve Your Goal With SkillGrow',
                description: 'when an unknown printer took a galley of type and scrambled make <br> specimen book has survived not only five centuries',

                features: [
                    { title: 'Learn with Experts', description: 'when an unknown printer took a galley offer type and scrambled makes.', icon: featureOneIcon },
                    { title: 'Effective Courses', description: 'when an unknown printer took a galley offer type and scrambled makes.', icon: featureTwoIcon },
                    { title: 'Earn Certificate', description: 'when an unknown printer took a galley offer type and scrambled makes.', icon: featureThreeIcon },
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
                    <div class="col-lg-4 col-md-6">
                            <div class="features__item-two">
                                <div class="features__content-two">
                                    <div class="content-top">
                                        <div class="features__icon-two">
                                            <img src="${feature.icon}" alt="${feature.title}" class="injectable">
                                        </div>
                                        <h2 class="title">${feature.title}</h2>
                                    </div>
                                    <p>${feature.description}</p>
                                </div>
                                <div class="features__item-shape">
                                    <img src="${baseUrl}/frontend/img/others/features_item_shape.svg" alt="img" class="injectable">
                                </div>
                            </div>
                        </div>
                `).join('');
                const html = `
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-xl-6 col-lg-8">
                                <div class="section__title text-center mb-40">
                                    <span class="sub-title">${this.get('sub_title')}</span>
                                    <h2 class="title">${this.get('title')}</h2>
                                    <p>${this.get('description')}</p>
                                </div>
                            </div>
                        </div>
                        <div class="features__item-wrap">
                            <div class="row justify-content-center">
                        ${generateHtml}
                            </div>
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
