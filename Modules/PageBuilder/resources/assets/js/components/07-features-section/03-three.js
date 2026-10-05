import { registerComponent } from '@utils/registerComponent';
import featureOneIcon from '@images/icons/theme_university_features_icon_1.svg';
import featureTwoIcon from '@images/icons/theme_university_features_icon_2.svg';
import featureThreeIcon from '@images/icons/theme_university_features_icon_3.svg';
import featureFourIcon from '@images/icons/theme_university_features_icon_4.svg';
import mediaImage from '@images/sections/feature-three.webp';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "features__area-four section-pb-90" },
                traits: [
                    {
                        type: 'accordion',
                        label: 'Features',
                        name: 'features',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Lorem Ipsum" },
                            { name: 'description', type: 'textarea', label: 'Description', placeholder: 'Description', default: "Eestuidar University we prepare you to launch your." },
                            { name: 'icon', type: 'image-upload', label: 'Icon', default: featureOneIcon }
                        ]
                    }
                ],
                features: [
                    { title: 'Scholarship Facility', description: 'Eestuidar University we prepare you to launch your.' , icon: featureOneIcon },
                    { title: 'Learn From Experts', description: 'Eestuidar University we prepare you to launch your.' , icon: featureTwoIcon },
                    { title: 'Graduation Courses', description: 'Eestuidar University we prepare you to launch your.' , icon: featureThreeIcon },
                    { title: 'Certificate Program', description: 'Eestuidar University we prepare you to launch your.' , icon: featureFourIcon },
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
                    <div class="col-lg-3 col-sm-6">
                        <div class="features__item-three orange">
                            <div class="features__icon-three">
                                <img src="${feature.icon}" class="injectable" alt="${feature.title}">
                            </div>
                            <div class="features__content-three">
                                <h2 class="title">${feature.title}</h2>
                                <p>${feature.description}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                const html = `
                    <div class="container">
                        <div class="features__item-wrap-two">
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
