import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./placeholder-DfUPplv7.js";var n=``+new URL(`h4_courses_bg-CmU_drru.webp`,import.meta.url).href,r=``+new URL(`course-four-CrsqdYXJ.webp`,import.meta.url).href,i=()=>{let e=``;for(let n=0;n<4;n++)e+=`
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="courses__item-five shine__animate-item">
                    <div class="courses__item-thumb-four shine__animate-link">
                        <a href="#"><img src="${t}" alt="img"></a>

                        <span class="courses__price-two">$99.00</span>
                        <a  href="javascript:;" class="wsus-wishlist-btn courses__wishlist-two" >
                            <i class="far fa-heart"></i>
                        </a>
                    </div>
                    <div class="courses__item-content-four">
                        <ul class="courses__item-meta list-wrap">
                            <li class="courses__item-tag courses__item-tag-two">
                                <a
                                    href="#">Category</a>
                            </li>
                            <li class="avg-rating"><i class="fas fa-star"></i>
                                (5 Reviews)
                            </li>
                        </ul>
                        <h2 class="title"><a href="#">Course Title</a>
                        </h2>
                        <div class="courses__item-bottom-three">
                            <ul class="list-wrap">
                                <li><i class="flaticon-book"></i>Lessons 21</li>
                                <li><i class="flaticon-mortarboard"></i>Students 999</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;return e},a=(e,t,n=!1)=>`
    <div class="tab-pane fade ${n?`show active`:``}" id="${e}-tab-pane" role="tabpanel" aria-labelledby="${e}-tab" tabindex="0">
        <div class="row gutter-24 justify-content-center">
            ${i()}
        </div>
    </div>
`,o=(t,{folderName:i,fileName:o},s)=>{e({editor:t,name:o,category:i,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`courses-area-three youga_course_area section-pt-140 section-pb-110 courses__bg-two`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0}],sub_title:`Top Class Courses`,title:`Explore Our World's Best Courses`,btnText:`Discover All Class`,background_img:n,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),SVGInject(document.querySelectorAll(`img.injectable`))}},init(){let e=this;this.swiperInstance=null,e.on(`change:sub_title change:title change:btnText change:background_img`,()=>{e.updateContent()}),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="row justify-content-center">
                            <div class="col-xxl-8 col-lg-10 col-md-12">
                                <div class="section__title text-center mb-50">
                                    <span class="sub-title">${this.get(`sub_title`)}</span>
                                    <h2 class="title">${this.get(`title`)}</h2>
                                </div>
                            </div>
                        </div>
                        <!-- DYNAMIC_PART_START:course-four -->
                            <div class="row justify-content-center mb-50">
                                <div class="courses__nav-two">
                                    <ul class="nav nav-tabs" id="courseTab" role="tablist">
                                        ${[{id:`all`,label:`All Course`},{id:`design`,label:`Web Design`},{id:`business`,label:`Business`},{id:`development`,label:`Development`}].map((e,t)=>`
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link ${t===0?`active`:``}" id="${e.id}-tab" data-bs-toggle="tab" data-bs-target="#${e.id}-tab-pane"
                                                    type="button" role="tab" aria-controls="${e.id}-tab-pane" aria-selected="${t===0}">
                                                    ${e.label}
                                                </button>
                                            </li>
                                        `).join(``)}
                                    </ul>
                                </div>
                            </div>
                            <div class="tab-content" id="courseTabContent">
                                ${a(`all`,`All Courses`,!0)}
                                ${a(`design`,`Design`)}
                                ${a(`business`,`Business`)}
                                ${a(`development`,`Development`)}
                            </div>
                        <!-- DYNAMIC_PART_END -->
                        <div class="discover-courses-btn-two text-center mt-30">
                            <a href="{{ route('courses') }}" class="btn arrow-btn btn-four">${this.get(`btnText`)} <img src="${s}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btnText`)}" class="injectable"></a>
                        </div>
                    </div>
                    <div class="courses__shape-wrap-two">
                        <img src="${s}/frontend/img/others/h4_course_shape.svg" alt="shape" class="rotateme">
                        <img src="${s}/frontend/img/others/h4_course_shape.svg" alt="shape" class="rotateme">
                    </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{o as default};