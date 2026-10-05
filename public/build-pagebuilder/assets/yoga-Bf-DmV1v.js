import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`h4_hero_bg-CHab_o_F.webp`,import.meta.url).href,n=``+new URL(`h4_hero_img-kDBXjArj.webp`,import.meta.url).href,r=``+new URL(`h4_hero_img_shape01-BY-Wq_2h.svg`,import.meta.url).href,i=``+new URL(`h4_hero_img_shape02-CvqI-kEd.svg`,import.meta.url).href,a=``+new URL(`hero-four-GIDw-uvD.webp`,import.meta.url).href,o=(o,{folderName:s,fileName:c},l)=>{e({editor:o,name:c,category:s,media:`<img src="${a}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`banner-area banner-bg-four tg-motion-effects`},traits:[{name:`title_one`,label:`Title One`,type:`text`,changeProp:!0},{name:`title_two`,label:`Title Two`,type:`textarea`,changeProp:!0},{name:`title_three`,label:`Title Three`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`contact_title`,label:`Contact Title`,type:`text`,changeProp:!0},{name:`contact_number`,label:`Contact Number`,type:`text`,changeProp:!0},{name:`contact_icon`,label:`Contact Icon`,type:`image-upload`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0},{name:`banner_shape_img`,label:`Banner Shape Image`,type:`image-upload`,changeProp:!0},{name:`banner_bg_img`,label:`Banner Background Image`,type:`image-upload`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],title_one:`Hi, Im`,title_two:`<span class="highlight">Jecov Rossy</span>`,title_three:`Personal Yoga Coach`,description:`I’m here to help you find the confidence you need to feel amazing in your body!when an unknown printer took a galley of type and scrambled it ecimen has survived not onlyled it to make.`,btnLink:`/courses`,btnText:`Find Your Course`,contact_title:`Booking Open`,contact_number:`+1 (123) 909090`,contact_icon:`${l}/frontend/img/icons/phone.svg`,banner_img:n,banner_shape_img:r,banner_bg_img:i,background_img:t,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title_one change:title_two change:title_three change:description change:btnLink change:btnText change:contact_title change:contact_number change:contact_icon change:banner_img change:banner_shape_img change:banner_bg_img change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center align-items-start">
                        <div class="col-xl-5 col-lg-6">
                            <div class="banner__content-four">
                                <h6 class="sub-title" data-aos="fade-down" data-aos-delay="600">${this.get(`title_one`)}</h6>
                                <h2 class="title" data-aos="fade-down" data-aos-delay="400">${this.get(`title_two`)}</h2>
                                <span class="sub-title-two" data-aos="fade-down"
                                    data-aos-delay="200">${this.get(`title_three`)}</span>
                                <p data-aos="fade-up" data-aos-delay="400">${this.get(`description`)}</p>
                                <div class="banner__btn-wrap-two" data-aos="fade-up" data-aos-delay="600">
                                        <a href="${this.get(`btnLink`)}"
                                            class="btn arrow-btn">${this.get(`btnText`)} <img
                                                src="${l}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btnText`)}"
                                                class="injectable"></a>
                                        <div class="banner__contact">
                                            <div class="icon">
                                                <img src="${this.get(`contact_icon`)}" alt="${this.get(`contact_title`)}"
                                                    class="injectable">
                                            </div>
                                            <div class="content">
                                                <span>${this.get(`contact_title`)}</span>
                                                <a href="${this.get(`contact_number`)}">${this.get(`contact_number`)}</a>
                                            </div>
                                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-7 col-lg-6 col-md-9 col-sm-10">
                            <div class="banner__images-four">
                                <img src="${this.get(`banner_img`)}" alt="img" class="main-img">
                                <div class="shape big-shape" data-aos="fade-up" data-aos-delay="900">
                                    <img src="${this.get(`banner_shape_img`)}" alt="shape">
                                </div>
                                <div class="shape big-shape-two" data-aos="zoom-in" data-aos-delay="700">
                                    <img src="${this.get(`banner_bg_img`)}" alt="shape"
                                        class="tg-motion-effects5">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="banner__shape-wrap">
                    <img src="${l}/frontend/img/others/h4_hero_shape01.svg" alt="shape" data-aos="fade-down-right"
                        data-aos-delay="1000">
                    <img src="${l}/frontend/img/others/h4_hero_shape02.svg" alt="shape" data-aos="fade-up-left"
                        data-aos-delay="1000">
                    <img src="${l}/frontend/img/others/h4_hero_shape03.svg" alt="shape" class="tg-motion-effects5">
                </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};