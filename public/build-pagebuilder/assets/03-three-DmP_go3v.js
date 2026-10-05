import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./online-instructor-XAfdHgNU.js";var n=``+new URL(`instructor-four-Bw2Ljuo8.webp`,import.meta.url).href,r=(e=4)=>{let n=``;for(let r=0;r<e;r++)n+=`
            <div class="col-xl-3 col-lg-4 col-sm-6">
                <div class="instructor__item-five">
                    <div class="instructor__thumb-five">
                        <a href="#"><img src="${t}" alt="Mark Davenport"></a>
                        <div class="instructor__social-two">
                            <ul class="list-wrap">
                                <li><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                                <li><a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a></li>
                                <li><a href="#" aria-label="Linkedin"><i class="fab fa-linkedin"></i></a></li>
                                <li><a href="#" aria-label="Github"><i class="fab fa-github"></i></a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="instructor__content-five">
                        <h2 class="title"><a href="#">Mark Davenport</a>
                        </h2>
                        <span>Developer</span>
                    </div>
                </div>
            </div>
        `;return n},i=(t,{folderName:i,fileName:a},o)=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`home_kindergarten instructor__area-six section-pb-110`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0}],sub_title:`Our Teacher`,title:`Our Top Class & Expert Instructors in One Place`,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)})}},init(){let e=this;e.on(`change:sub_title change:title`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-5 col-lg-7">
                            <div class="section__title text-center mb-50">
                                <span class="sub-title">${this.get(`sub_title`)}</span>
                                <h2 class="title">${this.get(`title`)}</h2>
                            </div>
                        </div>
                    </div>
                    <div class="row justify-content-center">
                        <!-- DYNAMIC_PART_START:instructor-four -->
                            ${r()}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                <div class="instructor__shape-two">
                    <img src="${o}/frontend/img/instructor/h5_instructor_img_shape01.svg" alt="shape"
                        class="rotateme">
                    <img src="${o}/frontend/img/instructor/h5_instructor_img_shape02.svg" alt="shape"
                        class="alltuchtopdown">
                </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};