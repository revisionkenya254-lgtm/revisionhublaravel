import { registerComponent } from '@utils/registerComponent';
import becomeInstructor from '@images/others/become_instructor.webp';
import becomeStudent from '@images/others/become_student.webp';
import blogManagerImg from '@images/sections/become.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "instructor__area-two" },
                traits: [
                    { name: 'item_one_title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'item_one_description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'item_one_link', label: 'Link', type: 'text', changeProp: true },
                    { name: 'item_one_btn_text', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'item_one_image', label: 'Feature One Icon', type: 'image-upload', changeProp: true },

                    { name: 'item_two_title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'item_two_description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'item_two_link', label: 'Link', type: 'text', changeProp: true },
                    { name: 'item_two_btn_text', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'item_two_image', label: 'Feature One Icon', type: 'image-upload', changeProp: true },
                ],
                item_one_title: 'Become a Instructor',
                item_one_description: 'Join our team to inspire students, share your knowledge, and shape the future',
                item_one_link: `${baseUrl}/register`,
                item_one_btn_text: 'Join Now',
                item_one_image: becomeInstructor,

                item_two_title: 'Become a Student',
                item_two_description: 'Unlock your potential by joining our vibrant learning community',
                item_two_link: `${baseUrl}/register`,
                item_two_btn_text: 'Join Now',
                item_two_image: becomeStudent,
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:item_one_title change:item_one_description change:item_one_link change:item_one_btn_text change:item_one_image change:item_two_title change:item_two_description change:item_two_link change:item_two_btn_text change:item_two_image', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                    <div class="container">
                        <div class="instructor__item-wrap-two">
                            <div class="row">
                                ${this.getItemHtml('one')}
                                ${this.getItemHtml('two')}
                            </div>
                        </div>
                    </div>
                `;
                // Update the components
                this.components(html);
                // Apply rules recursively
                this.applyRules(this, true);
            },
            getItemHtml(i) {
                return `
                    <div class="col-xl-6">
                        <div class="instructor__item-two tg-svg">
                            <div class="instructor__thumb-two">
                                <img src="${this.get(`item_${i}_image`)}" alt="${this.get(`item_${i}_title`)}">
                                <div class="shape-one">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="224" height="158"
                                        viewBox="0 0 224 158" fill="none"
                                        data-inject-url="https://skillgro.websolutionus.com/frontend/img/instructor/instructor_shape01.svg"
                                        class="injectable">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M-44.3643 224.756C-12.2393 262.104 53.4544 248.637 109.818 221.047C165.437 193.82 219.176 148.021 222.855 93.9472C226.613 38.7315 180.039 6.40385 124.786 1.08905C73.1398 -3.87887 16.1752 23.3672 -19.5665 70.6284C-58.3852 121.958 -77.2268 186.551 -44.3643 224.756Z"
                                            fill="currentcolor"></path>
                                    </svg>
                                </div>
                                <div class="shape-two">
                                    <span class="svg-icon" id="instructor-svg"
                                        data-svg-icon="https://skillgro.websolutionus.com/frontend/img/instructor/instructor_shape02.svg"><svg
                                            width="100%" height="100%" viewBox="0 0 48 55" fill="none"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path d="M42.3863 32.303C40.459 26.5102 36.4329 12.9132 35.7471 4.86755"
                                                stroke="currentcolor" stroke-width="2" stroke-linejoin="round"
                                                style="stroke-dasharray: 29, 31; stroke-dashoffset: 0;"></path>
                                            <path d="M28.5958 42.9917C22.8296 40.9865 9.59837 35.8853 2.80371 31.5222"
                                                stroke="currentcolor" stroke-width="2" stroke-linejoin="round"
                                                style="stroke-dasharray: 29, 31; stroke-dashoffset: 0;"></path>
                                            <path d="M34.3513 34.837C31.2834 32.212 24.4868 25.9248 21.8428 21.7755"
                                                stroke="currentcolor" stroke-width="2" stroke-linejoin="round"
                                                style="stroke-dasharray: 19, 21; stroke-dashoffset: 0;"></path>
                                        </svg></span>
                                </div>
                            </div>
                            <div class="instructor__content-two">
                                <h3 class="title"><a href="${this.get(`item_${i}_link`)}">${this.get(`item_${i}_title`)}</a></h3>
                                <p>${this.get(`item_${i}_description`)}</p>
                                <div class="tg-button-wrap">
                                    <a href="${this.get(`item_${i}_link`)}" class="btn arrow-btn">${this.get(`item_${i}_btn_text`)} <img
                                            src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="right arrow"
                                            class="injectable"></a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
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
