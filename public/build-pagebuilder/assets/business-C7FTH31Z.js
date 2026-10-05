import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`theme_business_slider_1-B7ZDBPuM.webp`,import.meta.url).href,n=``+new URL(`theme_business_slider_2-Ct1m5fgX.webp`,import.meta.url).href,r=``+new URL(`slider-BB2Xh8VK.webp`,import.meta.url).href,i=(i,{folderName:a,fileName:o},s)=>{e({editor:i,name:o,category:a,media:`<img src="${r}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`slider__area`},traits:[{type:`accordion`,label:`Sliders`,name:`sliders`,changeProp:!0,inputsConfig:[{name:`title`,label:`Title`,type:`text`,changeProp:!0,default:`Professional Courses`},{name:`sub_title`,label:`Sub Title`,type:`textarea`,changeProp:!0,default:`Find Business <span class="highlight">Courses</span> & Develop Your Skills`},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0,default:`Free & Premium online courses from the world’s Join 17 million learners today.`},{name:`placeholderText`,label:`Input placeholder text`,type:`text`,changeProp:!0,default:`Search Here . . .`},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0,default:`Find Courses`},{name:`Image`,label:`Slider Image`,type:`image-upload`,changeProp:!0,default:t}]}],sliders:[{title:`Professional Courses`,sub_title:`Find Business <span class="highlight">Courses</span> & Develop Your Skills`,description:`Free & Premium online courses from the world’s Join 17 million learners today.`,placeholderText:`Search Here . . .`,btnText:`Find Courses`,Image:t},{title:`Professional Courses`,sub_title:`Find Business <span class="highlight">Courses</span> & Develop Your Skills`,description:`Free & Premium online courses from the world’s Join 17 million learners today.`,placeholderText:`Search Here . . .`,btnText:`Find Courses`,Image:n}],script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),new Swiper(`.slider__active`,{spaceBetween:0,effect:`fade`,loop:!0,autoplay:{delay:6e3}})}},init(){let e=this;e.on(`change:sliders`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=`
                    <div class="swiper-container slider__active">
                        <div class="swiper-wrapper">
                            ${(this.get(`sliders`)||[]).map(e=>`
                    <div class="swiper-slide slider__single">
                        <div class="slider__bg" data-background="${e.Image}">
                            <div class="container">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-7">
                                        <div class="slider__content">
                                            <span class="sub-title">${e.title}</span>
                                            <h2 class="title">${e.sub_title}</h2>
                                            <p>${e.description}</p>
                                            <div class="slider__search">
                                                <!-- DYNAMIC_PART_START:search-form placeholder='${e.placeholderText}' buttonText='${e.btnText}' -->
                                                    <form action="#" class="slider__search-form">
                                                        <input type="text" name="search"
                                                            placeholder="${e.placeholderText}">
                                                        <button type="button">${e.btnText}</button>
                                                    </form>
                                                <!-- DYNAMIC_PART_END -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join(``)}
                        </div>
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};