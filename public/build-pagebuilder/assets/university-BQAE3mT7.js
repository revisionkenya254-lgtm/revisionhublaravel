import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./student_grp-C3uvedIu.js";var n=``+new URL(`theme_university_hero_bg-BoUnC5-U.webp`,import.meta.url).href,r=``+new URL(`theme_university_banner_img-BtUBHiSl.webp`,import.meta.url).href,i=``+new URL(`theme_university_banner_bg-oY-B6ypS.svg`,import.meta.url).href,a=``+new URL(`hero-three-BVc9P4XQ.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c},l)=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`banner-area banner-bg-three tg-motion-effects`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`count`,label:`Count`,type:`text`,changeProp:!0},{name:`count_title`,label:`Count Title`,type:`text`,changeProp:!0},{name:`count_img`,label:`Count Image`,type:`image-upload`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0},{name:`banner_shape_img`,label:`Banner Background Image`,type:`image-upload`,changeProp:!0}],sub_title:`FOR A BETTER FUTURE`,title:`Empowering Minds Inspiring <span>Futures</span>`,description:`Every teaching and learning journey is unique Follwoing We'll help guide your way.`,btnLink:`/courses`,btnText:`Find Your Course`,count:`80K`,count_title:`Enrolled Students`,count_img:t,banner_img:r,banner_shape_img:i,background_img:n,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:sub_title change:title change:description change:btnLink change:btnText change:count change:count_title change:banner_img change:banner_shape_img change:background_img change:count_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="row justify-content-between align-items-start">
                            <div class="col-xl-5 col-lg-6">
                                <div class="banner__content-three">
                                    <span class="sub-title" data-aos="fade-right"
                                        data-aos-delay="200">${this.get(`sub_title`)}</span>
                                    <h2 class="title" data-aos="fade-right" data-aos-delay="400">${this.get(`title`)}</h2>
                                    <p data-aos="fade-right" data-aos-delay="600">${this.get(`description`)}</p>
                                        <div class="banner__btn-wrap" data-aos="fade-right" data-aos-delay="800">
                                            <a href="${this.get(`btnLink`)}"
                                                class="btn arrow-btn">${this.get(`btnText`)} <img
                                                    src="${l}/frontend/img/icons/right_arrow.svg" alt="img"
                                                    class="injectable"></a>
                                </div>
                                <div class="shape">
                                    <img src="${l}/frontend/img/banner/h3_hero_shape01.svg" alt="shape" data-aos="fade-right" data-aos-delay="1000">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="banner__images-three">
                                <img src="${this.get(`banner_img`)}" alt="img" class="main-img">
                                <div class="shape big-shape" data-aos="fade-up" data-aos-delay="800">
                                    <img src="${this.get(`banner_shape_img`)}" alt="shape" class="tg-motion-effects1">
                                </div>
                                <div class="shape__wrap">
                                    <img src="${l}/frontend/img/banner/h3_hero_shape02.svg" alt="shape"
                                        data-aos="fade-down-right" data-aos-delay="400">
                                    <img src="${l}/frontend/img/banner/h3_hero_shape03.svg" alt="shape"
                                        data-aos="fade-down-left" data-aos-delay="400">
                                </div>
                                <div class="about__enrolled" data-aos="fade-right" data-aos-delay="900">
                                    <p class="title"><span>${this.get(`count`)}</span>
                                        ${this.get(`count_title`)}</p>
                                    <img src="${this.get(`count_img`)}" alt="${this.get(`count_title`)}">
                                </div>
                            </div>
                        </div>
                    </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};