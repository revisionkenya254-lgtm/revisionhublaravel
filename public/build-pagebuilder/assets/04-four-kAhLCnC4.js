import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./h4_features_icon03-DEAEfvA3.js";var n=``+new URL(`category-four-B1papPse.webp`,import.meta.url).href,r=[{name:`Graphic Design`,totalCourse:22},{name:`Finance`,totalCourse:41},{name:`Development`,totalCourse:29},{name:`App Development`,totalCourse:19},{name:`Marketing`,totalCourse:31},{name:`Life Style`,totalCourse:23},{name:`Management`,totalCourse:19},{name:`App Design`,totalCourse:18}].map(e=>`
<div class="col-xl-3 col-lg-4 col-sm-6">
    <div class="categories__item-two">
        <a href="#">
            <div class="content">
                <img src="${t}" alt="${e.name}">
                <span
                    class="name"><strong>${e.name}</strong>${e.totalCourse} Courses</span>
            </div>
            <div class="icon">
                <i class="revisionhubkenya-next-2"></i>
            </div>
        </a>
    </div>
</div>
`).join(``),i=(t,{folderName:i,fileName:a})=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`categories-area-two section-pt-140 section-pb-110`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0}],sub_title:`Trending Categories`,title:`Top Category We Have`},init(){let e=this;e.on(`change:sub_title change:title`,()=>{e.updateContent()}),e.updateContent()},updateContent(){let e=`
                <div class="container home_language">
                    <div class="row align-items-center">
                        <div class="col-lg-6 col-md-8">
                            <div class="section__title mb-50 mb-xs-20">
                                <span class="sub-title">${this.get(`sub_title`)}</span>
                                <h2 class="title bold">${this.get(`title`)}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- DYNAMIC_PART_START:category-four -->
                                ${r}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};