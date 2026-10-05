import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./student_grp-C3uvedIu.js";var n=``+new URL(`h6_hero_bg-BikM_6yN.webp`,import.meta.url).href,r=``+new URL(`h6_hero_img-LlKTurV9.webp`,import.meta.url).href,i=``+new URL(`course_icon03-CsX2kDZH.svg`,import.meta.url).href,a=``+new URL(`hero-six-DocQ58Cc.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c},l)=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`banner-area banner-bg-six tg-motion-effects`},traits:[{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`videoLink`,label:`Video Link`,type:`text`,changeProp:!0},{name:`videoBtnText`,label:`Video Button Text`,type:`text`,changeProp:!0},{name:`feature_title`,label:`Feature Title`,type:`text`,changeProp:!0},{name:`feature_icon`,label:`Feature Image`,type:`image-upload`,changeProp:!0},{name:`feature_two`,label:`Feature Two`,type:`text`,changeProp:!0},{name:`feature_two_title`,label:`Feature Two Title`,type:`text`,changeProp:!0},{name:`feature_two_icon`,label:`Feature Two Image`,type:`image-upload`,changeProp:!0},{name:`feature_three`,label:`Feature Two`,type:`text`,changeProp:!0},{name:`feature_three_title`,label:`Feature Two Title`,type:`text`,changeProp:!0},{name:`feature_three_icon`,label:`Feature Two Image`,type:`image-upload`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0}],title:`Confidently speak a new language`,description:`<p>More than 240 thousand new users SignUp Here!</p><ul><li>Live classes online 24/7</li><li>Learn in small groups or 1-on-1</li><li>Free 7-day trial</li></ul>`,btnLink:`${l}/courses`,btnText:`Find Your Course`,videoLink:`https://www.youtube.com/watch?v=pMzGDBP6Bic`,videoBtnText:`Watch Our Class Demo`,feature_title:`80k Enrolled Students`,feature_icon:t,feature_two:`4.9/5`,feature_two_title:`Real Reviews`,feature_two_icon:`${l}/frontend/img/icons/star.svg`,feature_three:`45+`,feature_three_title:`Courses`,feature_three_icon:i,banner_img:r,background_img:n,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),$(`.tg-svg`).each(function(){var e=$(this),t=e.find(`.svg-icon`),n=t.attr(`id`),r=t.data(`svg-icon`);if(n){var i=new Vivus(n,{duration:80,file:r});e.on(`mouseenter`,function(){i.reset().play()})}}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:description change:btnLink change:btnText change:videoLink change:videoBtnText change:feature_title change:feature_two change:feature_two_title change:feature_three change:feature_three_title change:banner_img change:background_img change:feature_icon change:feature_two_icon change:feature_three_icon`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="home_language container">
                        <div class="row justify-content-center align-items-center">
                            <div class="col-lg-7 col-md-9 col-sm-10 order-0 order-lg-2">
                                <div class="banner__images-six">
                                    <div class="main-img tg-svg">
                                        <img src="${this.get(`banner_img`)}" alt="${this.get(`title`)}">
                                        <span class="svg-icon" id="banner-svg" data-svg-icon="${l}/frontend/img/banner/h6_banner_img_shape03.svg"></span>
                                    </div>
                                    <div class="about__enrolled about__enrolled-two" data-aos="fade-right" data-aos-delay="1000">
                                        <img src="${this.get(`feature_icon`)}" alt="${this.get(`feature_title`)}">
                                        <p class="title">${this.get(`feature_title`)}</p>
                                    </div>
                                    <div class="banner__review" data-aos="fade-left" data-aos-delay="1000">
                                        <div class="icon">
                                            <img src="${this.get(`feature_two_icon`)}" alt="${this.get(`feature_two_title`)}" class="injectable">
                                        </div>
                                        <h6 class="title">${this.get(`feature_two`)} <span> ${this.get(`feature_two_title`)}</span></h6>
                                    </div>
                                    <div class="banner__courses" data-aos="fade-up" data-aos-delay="1000">
                                        <div class="icon">
                                            <img src="${this.get(`feature_three_icon`)}" alt="${this.get(`feature_three_title`)}" class="injectable">
                                        </div>
                                        <h6 class="title">${this.get(`feature_three`)} <span>${this.get(`feature_three_title`)}</span></h6>
                                    </div>
                                    <div class="shape-wrap">
                                        <img src="${l}/frontend/img/banner/h6_banner_img_shape01.svg" alt="shape">
                                        <img src="${l}/frontend/img/banner/h6_banner_img_shape02.svg" alt="shape" class="alltuchtopdown">
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="banner__content-six">
                                    <h2 class="title" data-aos="fade-up" data-aos-delay="200">${this.get(`title`)}</h2>

                                    <div class="sub-title wsus_content-box" data-aos="fade-up" data-aos-delay="400">
                                        ${this.get(`description`)}
                                    </div>
                                    <div class="banner__btn-wrap-three" data-aos="fade-up" data-aos-delay="800">
                                        <a href="${this.get(`btnLink`)}" class="btn arrow-btn btn-four">${this.get(`btnText`)} <img src="${l}/frontend/img/icons/right_arrow.svg" alt="img" class="injectable"></a>
                                        <a href="${this.get(`videoLink`)}" class="play-btn popup-video" aria-label="${this.get(`videoBtnText`)}"><i class="fas fa-play"></i> ${this.get(`videoBtnText`)}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};