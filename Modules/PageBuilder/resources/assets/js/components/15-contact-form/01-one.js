import { registerComponent } from '@utils/registerComponent';
import blogManagerImg from '@images/sections/contact-form.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "contact-area section-py-120" },
                traits: [
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'comment_placeholder', label: 'Comment Input Placeholder Text', type: 'text', changeProp: true },
                    { name: 'subject_placeholder', label: 'Subject Input Placeholder Text', type: 'text', changeProp: true },
                    { name: 'name_placeholder', label: 'Name Input Placeholder Text', type: 'text', changeProp: true },
                    { name: 'email_placeholder', label: 'Email Input Placeholder Text', type: 'text', changeProp: true },
                    { name: 'btn_text', label: 'Button Text', type: 'text', changeProp: true },
                    {
                        type: 'accordion',
                        label: 'Contact Information',
                        name: 'infos',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', label: 'Title', placeholder: 'Title', default: "Lorem Ipsum" },
                            { name: 'icon', type: 'image-upload', label: 'Icon', default: `${baseUrl}/frontend/img/icons/map.svg` },
                            { name: 'items', type: 'textarea', placeholder: 'Items (Enter one item per line)', rows:10, default: "+1 (126) 471-4441\n+1 (553) 149-8938" },
                        ]
                    }
                ],
                title: 'Send Us Message',
                description: 'Your email address will not be published. Required fields are marked *',
                comment_placeholder: 'Comment',
                subject_placeholder: 'Subject',
                name_placeholder: 'Name',
                email_placeholder: 'E-mail',
                btn_text: 'Submit Now',
                infos: [
                    { title: 'Address', items: "3166 Chestnut Street Winter Haven,\n FL 33830",   icon: `${baseUrl}/frontend/img/icons/map.svg` },
                    { title: 'Phone', items: "+1 (126) 471-4441\n+1 (553) 149-8938", icon: `${baseUrl}/frontend/img/icons/contact_phone.svg` },
                    { title: 'E-mail Address', items: "veqev@mailinator.com\nsosodyca@mailinator.com", icon: `${baseUrl}/frontend/img/icons/emial.svg` },
                ],
                script: function () {
                    SVGInject(document.querySelectorAll("img.injectable"));
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:description change:comment_placeholder change:subject_placeholder change:name_placeholder change:email_placeholder change:btn_text change:infos', () => {
                    wrapper.updateContent();
                    wrapper.view.render(); 
                });
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                 const generateHtml = (this.get('infos') || []).map(info => `
                    <li>
                        <div class="icon">
                            <img src="${info.icon}" alt="${info.title}" class="injectable">
                        </div>
                        <div class="content">
                            <h4 class="title">${info.title}</h4>
                            ${info.items.split('\n').map(l => l.trim()).filter(Boolean).map((l, i, arr) => {
                                const isLast = i === arr.length - 1;
                                return `<p class="${isLast ? '' : ''}">${l}</p>`;
                            }).join('')}
                        </div>
                    </li>
                `).join('');

                const html = `
                <div class="container">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="contact-info-wrap">
                                <ul class="list-wrap">
                                    ${generateHtml}
                                </ul>
                            </div>
                        </div>
                        <div class="col-lg-8">
                            <div class="contact-form-wrap">
                                <h4 class="title">${this.get('title')}</h4>
                                <p>${this.get('description')}</p>
                                <!-- DYNAMIC_PART_START:contact-form commentPlaceholderText="${this.get('comment_placeholder')}" subjectPlaceholderText="${this.get('subject_placeholder')}" namePlaceholderText="${this.get('name_placeholder')}" emailPlaceholderText="${this.get('email_placeholder')}" button_text="${this.get('btn_text')}" -->
                                    <form id="contact-form" action="">
                                        <div class="form-grp">
                                            <textarea name="message" placeholder="${this.get('comment_placeholder')} *" required></textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-grp">
                                                    <input name="subject" type="text" placeholder="${this.get('subject_placeholder')} *" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-grp">
                                                    <input name="name" type="text" placeholder="${this.get('name_placeholder')} *" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-grp">
                                                    <input name="email" type="email" placeholder="${this.get('email_placeholder')} *" required>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-two arrow-btn">${this.get('btn_text')}<img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="img" class="injectable"></button>
                                    </form>
                                <!-- DYNAMIC_PART_END -->
                            </div>
                        </div>
                    </div>
                </div>
                `;
                // Update the components
                this.components(html);
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
