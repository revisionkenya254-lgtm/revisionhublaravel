import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./theme_university_features_icon_4-D1niCy1p.js";import{n,r,t as i}from"./theme_university_features_icon_3-DMK4h1Ru.js";var a=``+new URL(`feature-three-BgbO-994.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c})=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`features__area-four section-pb-90`},traits:[{type:`accordion`,label:`Features`,name:`features`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Lorem Ipsum`},{name:`description`,type:`textarea`,label:`Description`,placeholder:`Description`,default:`Eestuidar University we prepare you to launch your.`},{name:`icon`,type:`image-upload`,label:`Icon`,default:r}]}],features:[{title:`Scholarship Facility`,description:`Eestuidar University we prepare you to launch your.`,icon:r},{title:`Learn From Experts`,description:`Eestuidar University we prepare you to launch your.`,icon:n},{title:`Graduation Courses`,description:`Eestuidar University we prepare you to launch your.`,icon:i},{title:`Certificate Program`,description:`Eestuidar University we prepare you to launch your.`,icon:t}],script:function(){SVGInject(document.querySelectorAll(`img.injectable`))}},init(){let e=this;e.on(`change:features`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="features__item-wrap-two">
                            <div class="row justify-content-center">
                                ${(this.get(`features`)||[]).map(e=>`
                    <div class="col-lg-3 col-sm-6">
                        <div class="features__item-three orange">
                            <div class="features__icon-three">
                                <img src="${e.icon}" class="injectable" alt="${e.title}">
                            </div>
                            <div class="features__content-three">
                                <h2 class="title">${e.title}</h2>
                                <p>${e.description}</p>
                            </div>
                        </div>
                    </div>
                `).join(``)}
                            </div>
                        </div>
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};