import { registerComponent } from '@utils/registerComponent';
import aboutImg from '@images/others/h4_choose_img.webp';
import aboutImgTwo from '@images/others/h4_choose_img02.webp';
import backgroundImg from '@images/others/h4_choose_bg.webp';
import blogManagerImg from '@images/sections/about-six.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${blogManagerImg}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "choose__area-two yoga_why_choose section-py-140 choose__bg" },
                traits: [
                    { name: 'subtitle', label: 'Subtitle', type: 'text', changeProp: true },
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'circle_text', label: 'Circle Text', type: 'text', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'about_img', label: 'Image', type: 'image-upload', changeProp: true },
                    { name: 'about_img_two', label: 'Image Two', type: 'image-upload', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                ],
                subtitle: 'Why Choose Us',
                title: 'Trained Your Body Mind Through Our Yoga',
                description: "<p>When an unknown printer took a galley of type and scrambled it to make cimen has survived not only five centurieswnknown.when an unknown printer took a galley of type.</p><ul><li>The Most World Class Instructors</li><li>Access Your Class anywhere</li><li>Flexible Course Price</li></ul>",
                circle_text: 'YoGa . Expert . Coach . Since 1996',
                btnLink: `${baseUrl}/about-us`,
                btnText: 'More Info',
                about_img: aboutImg,
                about_img_two: aboutImgTwo,
                background_img: backgroundImg,
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    
                    const text = document.querySelector('.circle');
                    text.innerHTML = text.textContent.replace(/\S/g, "<span>$&</span>");
                    const element = document.querySelectorAll('.circle span');
                    for (let i = 0; i < element.length; i++) {
                        element[i].style.transform = "rotate(" + i * 13 + "deg)"
                    }

                    SVGInject(document.querySelectorAll("img.injectable"));
                }
            },

            init() {
                const wrapper = this;
                // Use arrow function to preserve 'this' context
                wrapper.on('change:subtitle change:title change:description change:circle_text change:btnLink change:btnText change:about_img change:about_img_two change:background_img',
                    () => {
                        wrapper.updateContent();
                        wrapper.view.render();
                    }
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const html = `
                <div class="container">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6 col-md-10">
                            <div class="choose__img-two">
                                <img src="${this.get('about_img')}" alt="${this.get('subtitle')}">
                                <div class="circle__text-wrap">
                                    <div class="icon">
                                        <img src="${baseUrl}/frontend/img/icons/circle_icon.svg" alt="circle"
                                            class="injectable">
                                    </div>
                                    <div class="content">
                                        <h6 class="circle rotateme">${this.get('circle_text')}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="choose__content-two">
                                <div class="section__title mb-20">
                                    <span class="sub-title">${this.get('subtitle')}</span>
                                    <h2 class="title-two">${this.get('title')}</h2>
                                </div>
                                <p>${this.get('description')}</p>
                                <div class="choose__content-inner">
                                    <div class="row align-items-center gutter-20">
                                        <div class="col-sm-5 order-0 order-sm-2">
                                            <div class="choose__content-inner-img">
                                                <img src="${this.get('about_img_two')}" alt="${this.get('subtitle')}">
                                            </div>
                                        </div>
                                        <a href="${this.get('btnLink')}"
                                            class="btn arrow-btn btn-four">${this.get('btnText')} <img
                                                src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}"
                                                class="injectable"></a>
                                    </div>
                                </div>
                            </div>
                            <div class="shape">
                                <img src="${baseUrl}/frontend/img/others/h4_choose_shape.svg" alt="shape" class="rotateme">
                            </div>
                        </div>
                    </div>
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