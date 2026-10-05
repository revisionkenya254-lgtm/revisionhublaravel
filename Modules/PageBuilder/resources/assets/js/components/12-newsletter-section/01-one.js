import { registerComponent } from '@utils/registerComponent';
import newsletterImage from '@images/others/newsletter.webp';
import shapeOneImg from '@images/others/newsletter_shape01.png';
import shapeTwoImg from '@images/others/newsletter_shape02.png';
import shapeThreeImg from '@images/others/newsletter_shape03.png';
import blogManagerImg from '@images/sections/newsletter.webp';

export default (editor, { folderName, fileName }) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "newsletter__area" },
                traits: [
                    { name: 'title', label: 'Title Part One', type: 'textarea', changeProp: true },
                    { name: 'placeholderText', label: 'Input Placeholder Text', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'image', label: 'Image', type: 'image-upload', changeProp: true },
                    { name: 'shape_one', label: 'Shape One', type: 'image-upload', changeProp: true },
                    { name: 'shape_two', label: 'Shape Two', type: 'image-upload', changeProp: true },
                    { name: 'shape_three', label: 'Shape Three', type: 'image-upload', changeProp: true },
                ],
                title: 'Want to stay informed about <br> new courses and study',
                placeholderText: 'Type your email',
                btnText: 'Subscribe Now',
                image: newsletterImage,
                shape_one: shapeOneImg,
                shape_two: shapeTwoImg,
                shape_three: shapeThreeImg,
                script: function () {
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
                wrapper.on('change:title change:placeholderText change:btnText change:image change:shape_one change:shape_two change:shape_three', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-lg-4">
                                <div class="newsletter__img-wrap">
                                    <img src="${this.get('image')}" alt="${this.get('title')}">
                                    <img src="${this.get('shape_one')}" alt="shape" data-aos="fade-up"
                                        data-aos-delay="400">
                                    <img src="${this.get('shape_two')}" alt="shape" class="alltuchtopdown">
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="newsletter__content">
                                    <h2 class="title"><b>${this.get('title')}</b></h2>
                                    <div class="newsletter__form">
                                        <!-- DYNAMIC_PART_START:newsletter-form placeholder_text="${this.get('placeholderText')}" button_text="${this.get('btnText')}" -->
                                        <form action="#">
                                            <input type="email" placeholder="${this.get('placeholderText')}">
                                            <button type="submit" class="btn">${this.get('btnText')}</button>
                                        </form>
                                        <!-- DYNAMIC_PART_END -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="newsletter__shape">
                        <img src="${this.get('shape_three')}" alt="shape" data-aos="fade-left" data-aos-delay="400">
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
