import{t as e}from"./registerComponent-vPl6l5lM.js";import{t}from"./online-instructor-XAfdHgNU.js";var n=``+new URL(`instructor-three-Btgt_eND.webp`,import.meta.url).href,r=(e=4)=>{let n=``;for(let r=0;r<e;r++)n+=`
            <div class="col-lg-3 col-sm-6">
                <div class="instructor__item-four">
                    <div class="instructor__thumb-four">
                        <a href="£">
                            <img src="${t}" alt="img">
                        </a>
                    </div>
                    <div class="instructor__content-four">
                        <h2 class="title"><a href="£">Mark Davenport</a>
                        </h2>
                        <span>Developer</span>
                    </div>
                </div>
            </div>
        `;return n},i=(t,{folderName:i,fileName:a},o)=>{e({editor:t,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`yoga_theme instructor__area-five section-pt-140 section-pb-110`},traits:[{name:`sub_title`,label:`Sub Title`,type:`text`,changeProp:!0},{name:`title`,label:`Title`,type:`text`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0}],sub_title:`Our Instructors`,title:`Our Top Class & Expert Instructors in One Place`,description:`Combines the ideas of empowered learning and top-tier instruction for students. Emphasizes both instructor expertise`,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)})}},init(){let e=this;e.on(`change:sub_title change:title change:description`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=`
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-xl-6 col-lg-8">
                            <div class="section__title text-center mb-45">
                                <span class="sub-title">${this.get(`sub_title`)}</span>
                                <h2 class="title">${this.get(`title`)}</h2>
                                <p>${this.get(`description`)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <!-- DYNAMIC_PART_START:instructor-three -->
                            ${r()}
                        <!-- DYNAMIC_PART_END -->
                    </div>
                </div>
                `;this.components(e),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{i as default};