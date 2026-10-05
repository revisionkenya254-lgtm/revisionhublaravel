import{t as e}from"./registerComponent-vPl6l5lM.js";import{i as t,n,r,t as i}from"./features_icon04-BA3Grkuf.js";var a=``+new URL(`features-DlI6YruB.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c})=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`features__area`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{type:`accordion`,label:`Features`,name:`features`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Lorem Ipsum`},{name:`description`,type:`textarea`,label:`Description`,placeholder:`Description`,default:`Curate anding area share Pluralsight content to reach your`},{name:`icon`,type:`image-upload`,label:`Icon`,default:t}]}],sub_title:`How We Start Journey`,title:`Start your Learning Journey Today!`,description:`Groove’s intuitive shared inbox makesteam members together <br> organize, prioritize and.In this episode.`,features:[{title:`Learn with Experts`,description:`Curate anding area share Pluralsight content to reach your`,icon:t},{title:`Learn Anything`,description:`Curate anding area share Pluralsight content to reach your`,icon:r},{title:`Get Online Certificate`,description:`Curate anding area share Pluralsight content to reach your`,icon:n},{title:`E-mail Marketing`,description:`Curate anding area share Pluralsight content to reach your`,icon:i}]},init(){let e=this;e.on(`change:sub_title change:title change:description change:features`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=(this.get(`features`)||[]).map(e=>`
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="features__item">
                            <div class="features__icon">
                                <img src="${e.icon}" alt="${e.title}">
                            </div>
                            <div class="features__content">
                                <p class="title lh-base">${e.title}</p>
                                <p>${e.description}</p>
                            </div>
                        </div>
                    </div>
                `).join(``),t=`
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-xl-6">
                                <div class="section__title white-title text-center mb-50">
                                    <span class="sub-title">${this.get(`sub_title`)}</span>
                                    <h2 class="title">${this.get(`title`)}</h2>
                                    <p>${this.get(`description`)}</p>
                                </div>
                            </div>
                        </div>
                        <div class="row justify-content-center">
                            ${e}
                        </div>
                    </div>
                `;this.components(t),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};