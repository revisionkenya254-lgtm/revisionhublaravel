import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./student_grp-C3uvedIu.js";import{a as n,i as r,n as i,o as a,r as o,s,t as c}from"./brand07-DcXUHnHa.js";var l=``+new URL(`h8_hero_bg-B_YetW4Y.webp`,import.meta.url).href,u=``+new URL(`h8_hero_img-zt5ypXI5.webp`,import.meta.url).href,d=``+new URL(`h8_hero_img_shape02-CxY4e26I.svg`,import.meta.url).href,f=``+new URL(`h8_hero_img_shape-CW2odeiM.svg`,import.meta.url).href,p=``+new URL(`hero-five-teUidzaz.webp`,import.meta.url).href,m=(m,{folderName:h,fileName:g},_)=>{e({editor:m,name:g,category:h,media:`<img src="${p}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`banner-area fix banner-bg-seven tg-motion-effects`},traits:[{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`placeholderText`,label:`Input placeholder text`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`count`,label:`Count`,type:`text`,changeProp:!0},{name:`count_title`,label:`Count Title`,type:`text`,changeProp:!0},{name:`count_img`,label:`Count Image`,type:`image-upload`,changeProp:!0},{name:`count_two`,label:`Count Two`,type:`text`,changeProp:!0},{name:`count_two_title`,label:`Count Two Title`,type:`text`,changeProp:!0},{name:`banner_img`,label:`Banner Image`,type:`image-upload`,changeProp:!0},{name:`banner_shape_img`,label:`Banner Shape Image`,type:`image-upload`,changeProp:!0},{name:`banner_bg_img`,label:`Banner Background Image`,type:`image-upload`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{type:`accordion`,label:`Brands`,name:`brands`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,label:`Title`,placeholder:`Title`,default:`Brand`},{name:`logo`,type:`image-upload`,label:`Logo`,default:s},{name:`link`,type:`text`,label:`Link`,default:`#`}]}],title:`Take Your <span class="highlight">Cooking</span> Skill <br> To The Next Level`,description:`Free & Premium online courses from the world’s Join 17 million learners today.`,placeholderText:`Search Here . . .`,btnText:`Find Courses`,count:`80K`,count_title:`Enrolled Students`,count_img:t,count_two:`12k+`,count_two_title:`Total Recipes`,banner_img:u,banner_shape_img:d,banner_bg_img:f,background_img:l,brands:[{title:`Brand 1`,logo:s,link:`#`},{title:`Brand 2`,logo:a,link:`#`},{title:`Brand 3`,logo:n,link:`#`},{title:`Brand 4`,logo:r,link:`#`},{title:`Brand 5`,logo:o,link:`#`},{title:`Brand 6`,logo:i,link:`#`},{title:`Brand 7`,logo:c,link:`#`}],script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`)),new Swiper(`.brand-swiper-active-two`,{slidesPerView:5,spaceBetween:30,observer:!0,observeParents:!0,loop:!0,breakpoints:{1500:{slidesPerView:5},1200:{slidesPerView:4},992:{slidesPerView:4,spaceBetween:24},768:{slidesPerView:4,spaceBetween:24},576:{slidesPerView:3},0:{slidesPerView:2}}}),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:title change:description change:placeholderText change:btnText change:count change:count_title change:count_two change:count_two_title change:count_img change:banner_img change:banner_shape_img change:banner_bg_img change:background_img change:brands`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=(this.get(`brands`)||[]).map(e=>`
                    <div class="swiper-slide">
                        <div class="brand__item-two">
                            <a href="${e.link}"><img src="${e.logo}" alt="${e.title}"></a>
                        </div>
                    </div>
                `).join(``),t=`
                <div class="container">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6">
                            <div class="banner__content-seven">
                                <h2 class="title">${this.get(`title`)}</h2>
                                <p>${this.get(`description`)}</p>
                                <div class="slider__search banner__search">
                                    <!-- DYNAMIC_PART_START:search-form placeholder='${this.get(`placeholderText`)}' buttonText='${this.get(`btnText`)}' -->
                                        <form action="#" class="slider__search-form">
                                            <input type="text" name="search"
                                                placeholder="${this.get(`placeholderText`)}">
                                            <button type="button">${this.get(`btnText`)}</button>
                                        </form>
                                    <!-- DYNAMIC_PART_END -->
                                </div>
                                <div class="banner__brand-wrap">
                                    <div class="swiper-container brand-swiper-active-two">
                                        <div class="swiper-wrapper">
                                            ${e}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-10">
                            <div class="banner__images-seven">
                                <img src="${this.get(`banner_img`)}" alt="background image" class="main-img">
                                <div class="big_shape">
                                    <img src="${this.get(`banner_shape_img`)}" alt="background image"
                                        class="injectable tg-motion-effects1">
                                </div>
                                <div class="shape">
                                    <img src="${this.get(`banner_bg_img`)}" alt="background image"
                                        class="rotateme">
                                </div>
                                <div class="about__enrolled" data-aos="fade-right" data-aos-delay="200">
                                    <p class="title"><span>${this.get(`count`)}</span>
                                        ${this.get(`count_title`)}}</p>
                                    <img src="${this.get(`count_img`)}" alt="${this.get(`count_title`)}}">
                                </div>
                                <div class="banner__all-recipe" data-aos="fade-left" data-aos-delay="200">
                                    <h2 class="count">${this.get(`count_two`)}</h2>
                                    <span>${this.get(`count_two_title`)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>   
                `;this.components(t),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{m as default};