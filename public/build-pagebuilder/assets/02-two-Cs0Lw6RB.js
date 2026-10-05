import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`categories_bg-BbO8MKOr.webp`,import.meta.url).href,n=``+new URL(`category-two-C80HNDP6.webp`,import.meta.url).href,r=[{icon:`flaticon-graphic-design`,name:`Graphic Design`,totalCourse:22},{icon:`flaticon-investment`,name:`Finance`,totalCourse:41},{icon:`flaticon-coding`,name:`Development`,totalCourse:29},{icon:`flaticon-web-design`,name:`App Design`,totalCourse:18}].map(e=>`
<div class="col-lg-3 col-sm-6">
    <div class="categories__item-three">
        <a href="#">
            <div class="icon">
                <i class="${e.icon}"></i>
            </div>
            <span class="name">${e.name}</span>
            <span class="courses">${e.totalCourse} Courses</span>
        </a>
    </div>
</div>
`).join(``),i=(i,{folderName:a,fileName:o},s)=>{e({editor:i,name:o,category:a,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`categories-area-three fix section-pt-140 section-pb-110 categories__bg`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],sub_title:`Our Top Categories`,title:`Your Creative and Passionate Business Coach`,background_img:t,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`)),e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:sub_title change:title change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get(`sub_title`)}</span>
                                <h2 class="title bold">${this.get(`title`)}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                         <!-- DYNAMIC_PART_START:category-two -->
                                ${r}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="categories__shape-wrap">
                    <img src="${s}/frontend/img/others/h7_categories_shape01.svg" alt="shape" class="rotateme">
                    <img src="${s}/frontend/img/others/h7_categories_shape02.svg" alt="shape" data-aos="fade-down-left"
                        data-aos-delay="400">
                    <img src="${s}/frontend/img/others/h7_categories_shape03.svg" alt="shape" class="alltuchtopdown">
                    <img src="${s}/frontend/img/others/h7_categories_shape04.svg" alt="shape" data-aos="fade-up-right"
                        data-aos-delay="400">
                </div>
                `;this.components(e),this.applyRules(this,!0),this.addAttributes({"data-background":this.get(`background_img`)||``})},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};