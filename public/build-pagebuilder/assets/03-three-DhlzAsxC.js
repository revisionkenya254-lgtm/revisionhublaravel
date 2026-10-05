import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`h6_faq_img01-I8fIAR1i.webp`,import.meta.url).href,n=``+new URL(`h6_faq_img02-BpdBjCGG.webp`,import.meta.url).href,r=``+new URL(`faq-three-zdQfqIfX.webp`,import.meta.url).href,i=(i,{folderName:a,fileName:o},s)=>{e({editor:i,name:o,category:a,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`faq__area-three tg-motion-effects section-py-140`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`counter`,label:`Counter`,type:`text`,changeProp:!0},{name:`counter_title`,label:`Counter Title`,type:`text`,changeProp:!0},{name:`faq_img`,label:`Image`,type:`image-upload`,changeProp:!0},{name:`faq_img_two`,label:`Image Two`,type:`image-upload`,changeProp:!0},{name:`faqs`,label:`FAQs`,type:`accordion`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,placeholder:`Question`,default:`New Question?`},{name:`answer`,type:`textarea`,placeholder:`Answer`,default:`Lorem Ipsum is simply dummy text of the printing and typesetting industry.`}]}],sub_title:`Faq’s`,title:`Why Our Schools are the Right Fit for Your Child?`,description:`Groove’s intuitive shared inbox makes it easy for team members to organize, prioritize and.In this episod.`,counter:`45+`,counter_title:`Course Language`,faq_img:t,faq_img_two:n,faqs:[{title:`What kind of courses do you offer?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`},{title:`Who are your instructors?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`},{title:`How much do your courses cost?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`},{title:`How does online learning platform work?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`}],script:function(){e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:sub_title change:title change:description  change:counter  change:counter_title change:faq_img change:faq_img_two change:faqs`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=(this.get(`faqs`)||[]).map((e,t)=>`
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button ${t==0?``:`collapsed`}"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse_${t}" aria-expanded="true"
                                aria-controls="collapse_${t}">
                                ${e.title}
                            </button>
                        </h2>
                        <div id="collapse_${t}"
                            class="accordion-collapse collapse ${t==0?`show`:``}"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <p>${e.answer}</p>
                            </div>
                        </div>
                    </div>
                `).join(``),t=`
                <div class="container">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-7 col-md-10 order-0 order-lg-2">
                            <div class="faq__img-four">
                                <div class="main-img">
                                    <img src="${this.get(`faq_img`)}" alt="${this.get(`title`)}" data-aos="fade-down"
                                        data-aos-delay="400">
                                    <img src="${this.get(`faq_img_two`)}" alt="${this.get(`title`)}" data-aos="fade-up"
                                        data-aos-delay="400">
                                </div>
                                <div class="faq__language-wrap" data-aos="fade-right" data-aos-delay="600">
                                    <h2 class="title">${this.get(`counter`)}</h2>
                                    <span>${this.get(`counter_title`)}</span>
                                </div>
                                <div class="shape">
                                    <img src="${s}/frontend/img/others/h6_faq_shape01.svg" alt="shape"
                                        class="alltuchtopdown">
                                    <img src="${s}/frontend/img/others/h6_faq_shape02.svg" alt="shape"
                                        class="tg-motion-effects4">
                                    <img src="${s}/frontend/img/others/h6_faq_shape03.svg" alt="shape"
                                        class="tg-motion-effects3">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="faq__content-two faq__content-three">
                                <div class="section__title mb-15">
                                    <span class="sub-title">${this.get(`sub_title`)}</span>
                                    <h2 class="title bold">${this.get(`title`)}</h2>
                                </div>
                                <p>${this.get(`description`)}}</p>
                                <div class="faq__wrap faq__wrap-two">
                                    <div class="accordion" id="accordionExample">
                                        ${e}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>   
                `;this.components(t),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};