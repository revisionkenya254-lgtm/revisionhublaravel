import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`fact_img-D_B0HfSd.webp`,import.meta.url).href,n=``+new URL(`counter-four-BZJlruFR.webp`,import.meta.url).href,r=(r,{folderName:i,fileName:a},o)=>{e({editor:r,name:a,category:i,media:`<img src="${n}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`fact__area-two section-pb-140`},traits:[{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{name:`image`,label:`Image`,type:`image-upload`,changeProp:!0},{type:`accordion`,label:`Counters`,name:`counters`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,placeholder:`Title`,default:`New Counter`},{name:`count`,type:`number`,placeholder:`Count`,default:99},{name:`icon`,type:`text`,placeholder:`Icon (%, +, k)`,default:`%`}]}],title:`Thousands of <span class="highlight">courses</span> authored by industry experts`,image:t,counters:[{title:`Active Students`,count:3e3,icon:`+`},{title:`Best Professors`,count:100,icon:`+`}],script:function(){$(`.tg-svg`).each(function(){var e=$(this),t=e.find(`.svg-icon`),n=t.attr(`id`),r=t.data(`svg-icon`);if(n){var i=new Vivus(n,{duration:80,file:r});e.on(`mouseenter`,function(){i.reset().play()})}}),SVGInject(document.querySelectorAll(`img.injectable`))}},init(){let e=this;e.on(`change:title change:image change:counters`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=(this.get(`counters`)||[]).map(e=>`
                    <div class="fact__item">
                        <h2 class="count">${e.count}<span class="odometer" data-count="${e.count}"></span>${e.icon}</h2>
                        <p>${e.title}</p>
                    </div>
                `).join(``),t=`
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="fact__inner-wrap-two">
                                <div class="section__title white-title mb-30">
                                    <h2 class="title">${this.get(`title`)}</h2>
                                </div>
                                <div class="fact__item-wrap">
                                    ${e}
                                </div>
                                <div class="fact__img-wrap tg-svg">
                                    <img src="${this.get(`image`)}" alt="counter image">
                                    <div class="shape-one">
                                        <img src="${o}/frontend/img/others/fact_shape01.svg" alt="img" class="injectable">
                                    </div>
                                    <div class="shape-two">
                                        <span class="svg-icon" id="fact-btn" data-svg-icon="${o}/frontend/img/others/fact_shape02.svg"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                `;this.components(t),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{r as default};