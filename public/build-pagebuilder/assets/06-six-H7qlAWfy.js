import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./theme_university_features_icon_4-D1niCy1p.js";import{n,r,t as i}from"./theme_university_features_icon_3-DMK4h1Ru.js";var a=``+new URL(`feature-six-BkQNe3c9.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c})=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`features__area-eight`},traits:[{type:`accordion`,label:`Features`,name:`features`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Lorem Ipsum`},{name:`description`,type:`textarea`,label:`Description`,placeholder:`Description`,default:`Elevate your learning.`},{name:`icon`,type:`image-upload`,label:`Icon`,default:r}]}],features:[{title:`Learn From Experts`,description:`Elevate your learning.`,icon:n},{title:`Learn Anything`,description:`Master Any Skill.`,icon:i},{title:`Graduation Courses`,description:`Grow skills, shape future.`,icon:t},{title:`Get Online Certificate`,description:`Master in Demand Skills.`,icon:r}],script:function(){SVGInject(document.querySelectorAll(`img.injectable`))}},init(){let e=this;e.on(`change:features`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row">
                        ${(this.get(`features`)||[]).map(e=>`
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="features__item-six features__item-seven">
                            <div class="features__icon-six features__icon-seven">
                                <img src="${e.icon}" alt="${e.title}">
                            </div>
                            <div class="features__content-six features__content-seven">
                                <h4 class="title">${e.title}</h4>
                                <span>${e.description}</span>
                            </div>
                        </div>
                    </div>
                `).join(``)}
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};