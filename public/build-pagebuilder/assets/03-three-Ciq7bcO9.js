import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`counter-three-DKAUxAqP.webp`,import.meta.url).href,n=``+new URL(`fact_bg-BPGCdXeq.webp`,import.meta.url).href,r=(r,{folderName:i,fileName:a},o)=>{e({editor:r,name:a,category:i,media:`<img src="${t}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`fact__area-three fact__bg`},traits:[{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`description`,label:`Description`,type:`textarea`,changeProp:!0},{name:`btnLink`,label:`Button Link`,type:`text`,changeProp:!0},{name:`btnText`,label:`Button Text`,type:`text`,changeProp:!0},{name:`background_img`,label:`Background Image`,type:`image-upload`,changeProp:!0},{type:`accordion`,label:`Counters`,name:`counters`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,placeholder:`Title`,default:`New Counter`},{name:`count`,type:`number`,placeholder:`Count`,default:99},{name:`icon`,type:`text`,placeholder:`Icon (%, +, k)`,default:`%`}]}],title:`Explore Majors & <span class="highlight">Programs</span>`,description:`Choose from 16 undergraduate and graduate majors Board and the Mississippi Universities Board with goal of promoting collaboration.`,counters:[{title:`Active Students`,count:3e3,icon:`+`},{title:`Best Professors`,count:100,icon:`+`},{title:`Faculty Courses`,count:800,icon:`+`}],background_img:n,btnLink:`${o}/contact`,btnText:`Take a Tour`,script:function(){$(`[data-background]`).each(function(){$(this).css(`background-image`,`url(`+$(this).attr(`data-background`)+`)`)}),$(`.odometer`).appear(function(e){$(`.odometer`).each(function(){var e=$(this).attr(`data-count`);$(this).html(e)})})}},init(){let e=this;e.on(`change:title change:description change:btnLink change:btnText change:counters change:background_img`,()=>{e.updateContent(),e.view.render()}),e.updateContent()},updateContent(){let e=(this.get(`counters`)||[]).map(e=>`
                    <div class="col-md-4 col-sm-6">
                        <div class="fact__item fact__item-two">
                            <h2 class="count"><span class="odometer" data-count="${e.count}"></span>${e.icon}</h2>
                            <p>${e.title}</p>
                        </div>
                    </div>
                `).join(``),t=`
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-5">
                            <div class="fact__content-wrap">
                                <h2 class="title">${this.get(`title`)}</h2>
                                <p>${this.get(`description`)}</p>
                                <a href="${this.get(`btnLink`)}" class="btn arrow-btn">${this.get(`btnText`)} <img src="${o}/frontend/img/icons/right_arrow.svg" alt="${this.get(`btnText`)}" class="injectable"></a>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="fact__item-wrap-two">
                                <div class="row justify-content-center">
                                    ${e}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="fact__shape-wrap">
                    <img src="${o}/frontend/img/others/h3_fact_shape01.svg" alt="shape" class="alltuchtopdown">
                    <img src="${o}/frontend/img/others/h3_fact_shape02.svg" alt="shape" class="rotateme">
                </div>
                `;this.components(t),this.addAttributes({"data-background":this.get(`background_img`)||``}),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{r as default};