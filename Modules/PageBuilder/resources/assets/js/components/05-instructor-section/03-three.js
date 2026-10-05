import { registerComponent } from '@utils/registerComponent';
import instructorPic from '@images/others/online-instructor.webp';
import blogManagerImg from '@images/sections/instructor-four.webp';


const generateHTML = (total = 4) => {
    let html = '';
    for (let i = 0; i < total; i++) {
        html += `
            <div class="col-xl-3 col-lg-4 col-sm-6">
                <div class="instructor__item-five">
                    <div class="instructor__thumb-five">
                        <a href="#"><img src="${instructorPic}" alt="Mark Davenport"></a>
                        <div class="instructor__social-two">
                            <ul class="list-wrap">
                                <li><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                                <li><a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a></li>
                                <li><a href="#" aria-label="Linkedin"><i class="fab fa-linkedin"></i></a></li>
                                <li><a href="#" aria-label="Github"><i class="fab fa-github"></i></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="instructor__content-five">
                        <h2 class="title"><a href="#">Mark Davenport</a>
                        </h2>
                        <span>Developer</span>
                    </div>
                </div>
            </div>
        `;
    }
    return html;
};

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "home_kindergarten instructor__area-six section-pb-110" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                ],

                // Default values
                sub_title: 'Our Teacher',
                title: 'Our Top Class & Expert Instructors in One Place',
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-5 col-lg-7">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title">${this.get('title')}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <!-- DYNAMIC_PART_START:instructor-four -->
                            ${generateHTML()}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="instructor__shape-two">
                    <img src="${baseUrl}/frontend/img/instructor/h5_instructor_img_shape01.svg" alt="shape"
                        class="rotateme">
                    <img src="${baseUrl}/frontend/img/instructor/h5_instructor_img_shape02.svg" alt="shape"
                        class="alltuchtopdown">
                </div>
                `;
                // Update the components
                this.components(html);
                this.addAttributes({ 'data-background': this.get('background_img') || '' });
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
