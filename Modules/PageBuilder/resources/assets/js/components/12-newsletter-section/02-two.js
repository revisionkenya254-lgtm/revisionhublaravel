import { registerComponent } from '@utils/registerComponent';
import blogManagerImg from '@images/sections/newsletter-two.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "newsletter__area-three" },
                traits: [
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'placeholderText', label: 'Input Placeholder Text', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                ],
                title: 'Start today and get certified in Fundamentals of Business Core',
                placeholderText: 'Type your email',
                btnText: 'Subscribe Now',
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
                wrapper.on('change:title change:placeholderText change:btnText', () => {
                    wrapper.updateContent();
                    wrapper.view.render();
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="newsletter__inner-wrap newsletter__inner-wrap-two">
                        <h2 class="title">${this.get('title')}</h2>
                        <!-- DYNAMIC_PART_START:newsletter-form-two placeholder_text="${this.get('placeholderText')}" button_text="${this.get('btnText')}" -->
                            <form action="#" class="newsletter__form-two newsletter">
                                <input type="email" placeholder="${this.get('placeholderText')}">
                                <button type="submit" class="btn  arrow-btn">${this.get('btnText')}</button>
                            </form>
                        <!-- DYNAMIC_PART_END -->
                        <img src="${baseUrl}/frontend/img/others/h7_newsletter_shape01.svg" alt="shape" data-aos="fade-down-right"
                            data-aos-delay="400" class="shape shape-one">
                        <img src="${baseUrl}/frontend/img/others/h7_newsletter_shape02.svg" alt="shape" class="shape shape-two rotateme">
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
