import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`counter-two-BbLJ-Wyu.webp`,import.meta.url).href,n=(n,{folderName:r,fileName:i},a)=>{e({editor:n,name:i,category:r,media:`<img src="${t}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`fact__area-two`},traits:[{name:`title`,label:`Title`,type:`textarea`,changeProp:!0},{type:`accordion`,label:`Counters`,name:`counters`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,placeholder:`Title`,default:`New Counter`},{name:`count`,type:`number`,placeholder:`Count`,default:99},{name:`icon`,type:`text`,placeholder:`Icon (%, +, k)`,default:`%`}]}],title:`Thousands of <span class="highlight">courses</span> authored by industry experts`,counters:[{title:`Faculty Courses`,count:800,icon:`+`},{title:`Best Professors`,count:100,icon:`+`},{title:`Active Students`,count:3e3,icon:`+`}]},init(){let e=this;e.on(`change:title change:counters`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=(this.get(`counters`)||[]).map(e=>`
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
                                </div>
                            </div>
                        </div>
                    </div>
                `;this.components(t),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{n as default};