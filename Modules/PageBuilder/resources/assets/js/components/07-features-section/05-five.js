import { registerComponent } from '@utils/registerComponent';
import featureOneIcon from '@images/icons/h4_features_icon01.svg';
import featureTwoIcon from '@images/icons/h4_features_icon02.svg';
import featureThreeIcon from '@images/icons/h4_features_icon03.svg';
import backgroundImg from '@images/others/features_bg.webp';
import mediaImage from '@images/sections/feature-five.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "features__area-five features__bg section-pt-120 section-pb-90" },
                traits: [
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                    {
                        type: 'accordion',
                        label: 'Features',
                        name: 'features',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Lorem Ipsum" },
                            { name: 'description', type: 'textarea', label: 'Description', placeholder: 'Description', default: "We are able to offer every yoga training experienced & best yoga trainer." },
                            { name: 'icon', type: 'image-upload', label: 'Icon', default: featureOneIcon }
                        ]
                    },
                ],
                title: 'Connecting Your Mind, Body and Spirit through',
                description: "We believe yoga can power transformation on and off the mat We are more than just yogis - we're mom's, home makers, yoga studio owners, epic yoga retreat hosts",

                features: [
                    { title: 'Support & Motivation', description: 'We are able to offer every yoga training experienced & best yoga trainer.', icon: featureOneIcon },
                    { title: 'Strong Body Life', description: 'We are able to offer every yoga training experienced & best yoga trainer.', icon: featureTwoIcon },
                    { title: 'Increased Flexibility', description: 'We are able to offer every yoga training experienced & best yoga trainer.', icon: featureThreeIcon },
                ],
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
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
                wrapper.on('change:title change:description change:features change:background_img', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const generateHtml = (this.get('features') || []).map(feature => `
                    <div class="col-lg-4 col-md-6">
                        <div class="features__item-four">
                            <div class="features__icon-four">
                                <img src="${feature.icon}" alt="${feature.title}"
                                    class="injectable">
                            </div>
                            <div class="features__content-four">
                                <h3 class="title">${feature.title}</h3>
                                <p>${feature.description}</p>
                            </div>
                        </div>
                    </div>
                `).join('');
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="section__title text-center white-title mb-60">
                                <h2 class="title">${this.get('title')}</h2>
                                <p>${this.get('description')}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <div class="col-xl-10">
                            <div class="row justify-content-center">
                                ${generateHtml}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="features__shape-wrap">
                    <img src="${baseUrl}/frontend/img/others/h4_features_shape01.svg" alt="shape" data-aos="fade-right"
                        data-aos-delay="200">
                    <img src="${baseUrl}/frontend/img/others/h4_features_shape02.svg" alt="shape" data-aos="fade-left"
                        data-aos-delay="200">
                </div>
                `;
                // Update the components
                this.components(html);
                this.addAttributes({ 'data-background': this.get('background_img') || '' });
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
