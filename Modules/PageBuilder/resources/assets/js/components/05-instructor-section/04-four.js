import { registerComponent } from '@utils/registerComponent';
import instructorPic from '@images/others/online-instructor.webp';
import blogManagerImg from '@images/sections/instructor-three.webp';


const generateHTML = (total = 4) => {
    let html = '';
    for (let i = 0; i < total; i++) {
        html += `
            <div class="col-lg-3 col-sm-6">
                <div class="instructor__item-four">
                    <div class="instructor__thumb-four">
                        <a href="£">
                            <img src="${instructorPic}" alt="img">
                        </a>
                    </div>
                    <div class="instructor__content-four">
                        <h2 class="title"><a href="£">Mark Davenport</a>
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
                attributes: { class: "yoga_theme instructor__area-five section-pt-140 section-pb-110" },
                traits: [
                    { name: 'sub_title', label: 'Sub Title', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'text', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                ],

                // Default values
                sub_title: 'Our Instructors',
                title: 'Our Top Class & Expert Instructors in One Place',
                description: 'Combines the ideas of empowered learning and top-tier instruction for students. Emphasizes both instructor expertise',
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:sub_title change:title change:description', () => wrapper.updateContent());
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="section__title text-center mb-45">
                                <span class="sub-title">${this.get('sub_title')}</span>
                                <h2 class="title">${this.get('title')}</h2>
                                <p>${this.get('description')}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- DYNAMIC_PART_START:instructor-three -->
                            ${generateHTML()}
                        <!-- DYNAMIC_PART_END -->
                    </div>
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
