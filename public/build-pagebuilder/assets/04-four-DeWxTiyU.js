import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`h5_faq_img-D5uxPx9I.webp`,import.meta.url).href,n=``+new URL(`faq-four-KRGH_VUv.webp`,import.meta.url).href,r=(r,{folderName:i,fileName:a},o)=>{e({editor:r,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`faq__area-two tg-motion-effects section-py-140`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`faq_img`,label:`Image`,type:`image-upload`,changeProp:!0},{name:`faqs`,label:`FAQs`,type:`accordion`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,placeholder:`Question`,default:`New Question?`},{name:`answer`,type:`textarea`,placeholder:`Answer`,default:`Lorem Ipsum is simply dummy text of the printing and typesetting industry.`}]}],sub_title:`Faq’s`,title:`Why Our Schools are the Right Fit for Your Child?`,description:`Groove’s intuitive shared inbox makes it easy for team members to organize, prioritize and.In this episod.`,faq_img:t,faqs:[{title:`What kind of courses do you offer?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`},{title:`Who are your instructors?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`},{title:`How much do your courses cost?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`},{title:`How does online learning platform work?`,answer:`We offer a wide range of courses in various subjects, from business and technology to art and personal development. You can browse our extensive catalog to find a course that interests you.`}],script:function(){SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:sub_title change:title change:description change:faq_img change:faqs`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=(this.get(`faqs`)||[]).map((e,t)=>`
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
                        <div class="col-lg-6 col-md-8 order-0 order-lg-2">
                            <div class="faq__img-three">
                                <div class="faq__mask-img">
                                    <img src="${this.get(`faq_img`)}" alt="${this.get(`title`)}">
                                </div>
                                <div class="faq__img-shape">
                                    <img src="${o}/frontend/img/others/h5_faq_img_shape.svg" class="injectable">
                                </div>
                                <div class="shape shape-one" data-aos="fade-down-left" data-aos-delay="400">
                                    <img src="${o}/frontend/img/others/h5_faq_shape02.svg" alt="shape"
                                        class="tg-motion-effects3">
                                </div>
                                <div class="shape shape-two" data-aos="fade-up-left" data-aos-delay="400">
                                    <img src="${o}/frontend/img/others/h5_faq_shape03.svg" alt="shape"
                                        class="tg-motion-effects4">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="faq__content-two">
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
                <div class="faq__shape">
                    <img src="${o}/frontend/img/others/h5_faq_shape01.svg" alt="shape" class="tg-motion-effects3">
                </div> 
                `;this.components(t),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{r as default};