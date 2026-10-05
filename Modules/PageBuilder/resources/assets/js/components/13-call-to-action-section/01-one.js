import { registerComponent } from '@utils/registerComponent';
import backgroundImg from '@images/others/cta_bg.webp';
import blogManagerImg from '@images/sections/call-two-action-two.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "cta__area home_3_cta" },
                traits: [
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'link', label: 'Link', type: 'text', changeProp: true },
                    { name: 'btn_text', label: 'Title', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                title: 'Together We Go Far',
                description: 'Through research and discovery, we are changing the world.',
                link: `${baseUrl}/register`,
                btn_text: 'Join With Us',
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:description change:link change:btn_text change:background_img', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="cta__bg" data-background="${this.get('background_img')}"></div>
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <div class="cta__content">
                                <h2 class="title">${this.get('title')}</h2>
                                <p>${this.get('description')}</p>
                                <a href="${this.get('link')}" class="btn arrow-btn">${this.get('btn_text')} <img
                                        src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btn_text')}"
                                        class="injectable"></a>
                            </div>
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
