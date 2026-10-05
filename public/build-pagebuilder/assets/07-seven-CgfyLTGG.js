import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`feature-seven-D1cGOA_x.webp`,import.meta.url).href,n=(n,{folderName:r,fileName:i},a)=>{e({editor:n,name:i,category:r,media:`<img src="${t}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`features__area-six section-pt-140 section-pb-110`},traits:[{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{type:`accordion`,label:`Features`,name:`features`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Lorem Ipsum`},{name:`description`,type:`textarea`,label:`Description`,placeholder:`Description`,default:`Elevate your learning. Trusted guidance, real results`},{name:`btnLink`,label:`Link`,type:`text`,changeProp:!0,default:`#`},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0,default:`Read More`}]}],title:`Grow and expand your skills through enjoyable & imaginative`,description:`Discover a World of Knowledge and Skills at Your Fingertips – Unlock Your Potential and Achieve Your Dreams with Our Comprehensive Learning Resources!`,features:[{title:`Easy Class`,description:`Elevate your learning. Trusted guidance, real results`,btnLink:`#`,btnText:`Read More`},{title:`Safety & Security`,description:`Elevate your learning. Trusted guidance, real results`,btnLink:`#`,btnText:`Read More`},{title:`Skilled Teacher`,description:`Elevate your learning. Trusted guidance, real results`,btnLink:`#`,btnText:`Read More`},{title:`Clean Curriculum`,description:`Elevate your learning. Trusted guidance, real results`,btnLink:`#`,btnText:`Read More`}],script:function(){SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:description change:features`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=(this.get(`features`)||[]).map(e=>`
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-8">
                        <div class="features__item-five">
                            <div class="features__item-five-shape">
                                <div class="shape-one">
                                    <img src="${a}/frontend/img/others/h5_features_item_shape.svg"
                                        class="injectable">
                                </div>
                                <div class="shape-two">
                                    <img src="${a}/frontend/img/others/h5_features_item_shape02.svg"
                                        class="injectable">
                                </div>
                            </div>
                            <div class="features__icon-five">
                                <i class="skillgro-video-tutorial"></i>
                                <img src="${a}/frontend/img/icons/h5_features_icon.svg" class="injectable">
                            </div>
                            <div class="features__content-five">
                                <h2 class="title">${e.title}</h2>
                                <p>${e.description}</p>
                                <a href="${e.btnLink}"
                                    class="btn arrow-btn">${e.btnText} <img
                                        src="${a}/frontend/img/icons/right_arrow.svg" class="injectable"></a>
                            </div>
                        </div>
                    </div>
                `).join(``),t=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="section__title text-center mb-40">
                                <h2 class="title bold">${this.get(`title`)}</h2>
                                <p>${this.get(`description`)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="features__item-wrap-three">
                        <div class="row justify-content-center">
                            ${e}
                        </div>
                    </div>
                </div>
                <div class="features__shape-wrap-two">
                    <img src="${a}/frontend/img/others/h5_features_shape01.svg" alt="shape" class="rotateme">
                    <img src="${a}/frontend/img/others/h5_features_shape02.svg" alt="shape"
                        data-aos="fade-down-left" data-aos-delay="400">
                </div>
                `;this.components(t),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{n as default};