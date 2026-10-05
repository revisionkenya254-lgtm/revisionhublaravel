import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./h4_features_icon03-DEAEfvA3.js";var n=``+new URL(`category-three-CuBzjv1M.webp`,import.meta.url).href,r=[{name:`Graphic Design`,totalCourse:22},{name:`Finance`,totalCourse:41},{name:`Development`,totalCourse:29},{name:`App Development`,totalCourse:19},{name:`Marketing`,totalCourse:31},{name:`Life Style`,totalCourse:23},{name:`Management`,totalCourse:19},{name:`App Design`,totalCourse:18}].map(e=>`
<div class="col-lg-3 col-sm-6">
    <div class="categories__item-four shine__animate-item">
        <a href="#" class="shine__animate-link">
            <img src="${t}" alt="${e.name}">
            <span class="name">${e.name} <strong>(${e.totalCourse})</strong></span>
        </a>
    </div>
</div>
`).join(``),i=(t,{folderName:i,fileName:a},o)=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`categories-area-four fix section-pt-140 section-pb-110`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0}],sub_title:`Our Top Categories`,title:`Our Food categories`,script:function(){e();function e(){AOS.init({duration:1e3,mirror:!0,once:!0,disable:`mobile`})}}},init(){let e=this;e.on(`change:sub_title change:title`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
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
                        <!-- DYNAMIC_PART_START:category-three -->
                                ${r}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="categories__shape-wrap-two">
                    <img src="${o}/frontend/img/others/cat_shape01.svg" alt="shape" data-aos="fade-down-right"
                        data-aos-delay="400">
                    <img src="${o}/frontend/img/others/cat_shape02.svg" alt="shape" data-aos="fade-up-left"
                        data-aos-delay="400">
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};