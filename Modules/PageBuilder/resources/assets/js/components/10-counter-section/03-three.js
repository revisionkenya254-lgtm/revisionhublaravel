import { registerComponent } from '@utils/registerComponent';
import mediaImage from '@images/sections/counter-three.webp';
import backgroundImg from '@images/others/fact_bg.webp';

export default (editor, { folderName, fileName }, baseUrl) => {
    registerComponent({
        editor,
        name: fileName,
        category: folderName,
        media: `<img src="${mediaImage}"/>`,
        model: {
            defaults: {
                tagName: "section",
                attributes: { class: "fact__area-three fact__bg" },
                traits: [
                    { name: 'title', label: 'Title', type: 'textarea', changeProp: true },
                    { name: 'description', label: 'Description', type: 'textarea', changeProp: true },
                    { name: 'btnLink', label: 'Button Link', type: 'text', changeProp: true },
                    { name: 'btnText', label: 'Button Text', type: 'text', changeProp: true },
                    { name: 'background_img', label: 'Background Image', type: 'image-upload', changeProp: true },
                    {
                        type: 'accordion',
                        label: 'Counters',
                        name: 'counters',
                        changeProp: true,
                        // Pass your dynamic inputs config as a property here:
                        inputsConfig: [
                            { name: 'title', type: 'text', placeholder: 'Title', default: "New Counter" },
                            { name: 'count', type: 'number', placeholder: 'Count', default: 99 },
                            { name: 'icon', type: 'text', placeholder: 'Icon (%, +, k)', default: "%" }
                        ]
                    }
                ],
                title: 'Explore Majors & <span class="highlight">Programs</span>',
                description: 'Choose from 16 undergraduate and graduate majors Board and the Mississippi Universities Board with goal of promoting collaboration.',
                counters: [
                    { title: 'Active Students', count: 3000, icon: "+" },
                    { title: 'Best Professors', count: 100, icon: "+" },
                    { title: 'Faculty Courses', count: 800, icon: "+" },
                ],
                background_img: backgroundImg,
                btnLink: `${baseUrl}/contact`,
                btnText: 'Take a Tour',
                script: function () {
                    $("[data-background]").each(function () {
                        $(this).css("background-image", "url(" + $(this).attr("data-background") + ")")
                    });
                    $('.odometer').appear(function (e) {
                        var odo = $(".odometer");
                        odo.each(function () {
                            var countNumber = $(this).attr("data-count");
                            $(this).html(countNumber);
                        });
                    });
                }
            },

            init() {
                const wrapper = this;

                // Use arrow function to preserve 'this' context
                wrapper.on('change:title change:description change:btnLink change:btnText change:counters change:background_img',
                    () =>{
                      wrapper.updateContent();  
                      wrapper.view.render();  
                    } 
                );
                // Initial render to sync content on load (including loaded JSON)
                wrapper.updateContent();
            },

            updateContent() {
                const countersHtml = (this.get('counters') || []).map(counter => `
                    <div class="col-md-4 col-sm-6">
                        <div class="fact__item fact__item-two">
                            <h2 class="count"><span class="odometer" data-count="${counter.count}"></span>${counter.icon}</h2>
                            <p>${counter.title}</p>
                        </div>
                    </div>
                `).join('');

                const html = `
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-5">
                            <div class="fact__content-wrap">
                                <h2 class="title">${this.get('title')}</h2>
                                <p>${this.get('description')}</p>
                                <a href="${this.get('btnLink')}" class="btn arrow-btn">${this.get('btnText')} <img src="${baseUrl}/frontend/img/icons/right_arrow.svg" alt="${this.get('btnText')}" class="injectable"></a>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="fact__item-wrap-two">
                                <div class="row justify-content-center">
                                    ${countersHtml}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fact__shape-wrap">
                    <img src="${baseUrl}/frontend/img/others/h3_fact_shape01.svg" alt="shape" class="alltuchtopdown">
                    <img src="${baseUrl}/frontend/img/others/h3_fact_shape02.svg" alt="shape" class="rotateme">
                </div>
                `;
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