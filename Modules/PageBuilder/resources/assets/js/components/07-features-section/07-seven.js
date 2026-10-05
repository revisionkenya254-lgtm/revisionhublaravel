import { registerComponent } from '@utils/registerComponent';
import mediaImage from '@images/sections/feature-seven.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "features__area-six section-pt-140 section-pb-110" },
                traits: [
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
                            { name: 'description', type: 'textarea', label: 'Description', placeholder: 'Description', default: "Elevate your learning. Trusted guidance, real results" },
                            { name: 'btnLink', label: 'Link', type: 'text', changeProp: true, default: "#" },
                            { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true, default: "Read More" },
                        ]
                    }
                ],
                title: 'Grow and expand your skills through enjoyable & imaginative',
                description: 'Discover a World of Knowledge and Skills at Your Fingertips – Unlock Your Potential and Achieve Your Dreams with Our Comprehensive Learning Resources!',

                features: [
                    { title: 'Easy Class', description: 'Elevate your learning. Trusted guidance, real results', btnLink: '#', btnText: 'Read More' },
                    { title: 'Safety & Security', description: 'Elevate your learning. Trusted guidance, real results', btnLink: '#', btnText: 'Read More' },
                    { title: 'Skilled Teacher', description: 'Elevate your learning. Trusted guidance, real results', btnLink: '#', btnText: 'Read More' },
                    { title: 'Clean Curriculum', description: 'Elevate your learning. Trusted guidance, real results', btnLink: '#', btnText: 'Read More' },
                ],
                script: function () {
                    SVGInject(document.querySelectorAll("img.injectable"));
                    aosAnimation();
                    function aosAnimation() {
                        AOS.init({
                            duration: 1000,
                            mirror: true,
                            once: true,
                            disable: 'mobile',
                        });
                    }
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:description change:features', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const generateHtml = (this.get('features') || []).map(feature => `
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-8">
                        <div class="features__item-five">
                            <div class="features__item-five-shape">
                                <div class="shape-one">
                                    <img src="${baseUrl}/frontend/img/others/h5_features_item_shape.svg"
                                        class="injectable">
                                </div>
                                <div class="shape-two">
                                    <img src="${baseUrl}/frontend/img/others/h5_features_item_shape02.svg"
                                        class="injectable">
                                </div>
                            </div>
                            <div class="features__icon-five">
                                <i class="skillgro-video-tutorial"></i>
                                <img src="${baseUrl}/frontend/img/icons/h5_features_icon.svg" class="injectable">
                            </div>
                            <div class="features__content-five">
                                <h2 class="title">${feature.title}</h2>
                                <p>${feature.description}</p>
                                <a href="${feature.btnLink}"
                                    class="btn arrow-btn">${feature.btnText} <img
                                        src="${baseUrl}/frontend/img/icons/right_arrow.svg" class="injectable"></a>
                            </div>
                        </div>
                    </div>
                `).join('');
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="section__title text-center mb-40">
                                <h2 class="title bold">${this.get('title')}</h2>
                                <p>${this.get('description')}</p>
                            </div>
                        </div>
                    </div>
                    <div class="features__item-wrap-three">
                        <div class="row justify-content-center">
                            ${generateHtml}
                        </div>
                    </div>
                </div>
                <div class="features__shape-wrap-two">
                    <img src="${baseUrl}/frontend/img/others/h5_features_shape01.svg" alt="shape" class="rotateme">
                    <img src="${baseUrl}/frontend/img/others/h5_features_shape02.svg" alt="shape"
                        data-aos="fade-down-left" data-aos-delay="400">
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
