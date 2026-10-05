import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`h5_hero_bg-B6pTfTRn.webp`,import.meta.url).href,n=``+new URL(`h5_hero_img-BA9FTUK3.webp`,import.meta.url).href,r=``+new URL(`hero-seven-C2lvqoM0.webp`,import.meta.url).href,i=(i,{folderName:a,fileName:o},s)=>{e({editor:i,name:o,category:a,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`banner-area banner-bg-five tg-motion-effects`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0}],sub_title:`Kindergarten Care School`,title:`Together We’ll <br> Explore New Things`,description:`Every teaching and learning journey is unique Following We'll help guide your way.`,btnLink:`${s}/courses`,btnText:`Find Your Course`,banner_img:n,background_img:t,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:sub_title change:title change:description change:btnLink change:btnText change:banner_img change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="home_kindergarten banner-bg-five-shape" data-background="${s}/frontend/img/banner/h5_hero_bg_shape.svg"></div>
                        <div class="container">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-xl-5 col-lg-5">
                                    <div class="banner__content-five">
                                        <span class="sub-title" data-aos="fade-right" data-aos-delay="200">${this.get(`sub_title`)}</span>
                                        <h2 class="title" data-aos="fade-right" data-aos-delay="400">${this.get(`title`)}</h2>
                                        <p data-aos="fade-right" data-aos-delay="600">${this.get(`description`)}</p>
                                        <div class="banner__btn" data-aos="fade-right" data-aos-delay="800">
                                            <a href="${this.get(`btnLink`)}" class="btn arrow-btn">${this.get(`btnText`)} <img src="${s}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btnText`)}" class="injectable"></a>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-7 col-lg-7 col-md-9 col-sm-10">
                                    <div class="banner__images-five">
                                        <img src="${this.get(`banner_img`)}" alt="${this.get(`title`)}">
                                        <div class="shape-wrap">
                                            <div class="shape-one" data-aos="fade-up-right" data-aos-delay="800">
                                                <img src="${s}/frontend/img/banner/h5_hero_shape04.svg" alt="shape" class="tg-motion-effects1">
                                            </div>
                                            <div class="shape-two" data-aos="fade-down-left" data-aos-delay="800">
                                                <img src="${s}/frontend/img/banner/h5_hero_shape05.svg" alt="shape" class="tg-motion-effects3">
                                            </div>
                                            <div class="shape-three" data-aos="fade-up-left" data-aos-delay="800">
                                                <img src="${s}/frontend/img/banner/h5_hero_shape06.svg" alt="shape">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="banner__shape-wrap-two">
                            <img src="${s}/frontend/img/banner/h5_hero_shape01.svg" alt="shape" data-aos="fade-right" data-aos-delay="1000">
                            <img src="${s}/frontend/img/banner/h5_hero_shape02.svg" alt="shape" class="tg-motion-effects7">
                            <img src="${s}/frontend/img/banner/h5_hero_shape03.svg" alt="shape" class="tg-motion-effects3">
                        </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};