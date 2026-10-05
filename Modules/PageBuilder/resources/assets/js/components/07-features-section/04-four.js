import { registerComponent } from '@utils/registerComponent';
import featureOneIcon from '@images/icons/theme_university_features_icon_1.svg';
import featureTwoIcon from '@images/icons/theme_university_features_icon_2.svg';
import featureThreeIcon from '@images/icons/theme_university_features_icon_3.svg';
import featureFourIcon from '@images/icons/theme_university_features_icon_4.svg';
import mediaImage from '@images/sections/feature-four.webp';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "features__area-seven grey-bg-two" },
                traits: [
                    {
                        type: 'accordion',
                        label: 'Features',
                        name: 'features',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Lorem Ipsum" },
                            { name: 'description', type: 'textarea', label: 'Description', placeholder: 'Description', default: "Elevate your learning." },
                            { name: 'icon', type: 'image-upload', label: 'Icon', default: featureOneIcon }
                        ]
                    }
                ],
                features: [
                    { title: 'Learn From Experts', description: 'Elevate your learning.' , icon: featureTwoIcon },
                    { title: 'Learn Anything', description: 'Master Any Skill.' , icon: featureThreeIcon },
                    { title: 'Graduation Courses', description: 'Grow skills, shape future.' , icon: featureFourIcon },
                    { title: 'Get Online Certificate', description: 'Master in Demand Skills.' , icon: featureOneIcon },
                ],
                script:function(){
                    SVGInject(document.querySelectorAll("img.injectable"));
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:features', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const generateHtml = (this.get('features') || []).map(feature => `
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="features__item-six">
                            <div class="features__icon-six">
                                <img src="${feature.icon}" alt="${feature.title}">
                            </div>
                            <div class="features__content-six">
                                <h4 class="title">${feature.title}</h4>
                                <span>${feature.description}</span>
                            </div>
                        </div>
                    </div>
                `).join('');
                const html = `
                <div class="container">
                    <div class="features__item-wrap-four">
                        <div class="row">
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
