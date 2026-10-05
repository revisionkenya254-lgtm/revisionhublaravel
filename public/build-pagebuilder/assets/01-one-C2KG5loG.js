import{t as e}from"./registerComponent-vPl6l5lM.js";var t=``+new URL(`counter-CdJfIc5b.webp`,import.meta.url).href,n=(n,{folderName:r,fileName:i},a)=>{e({editor:n,name:i,category:r,media:`<img src="${t}"/>`,model:{defaults:{tagName:`section`,attributes:{class:`fact__area`},traits:[{type:`accordion`,label:`Counters`,name:`counters`,changeProp:!0,inputsConfig:[{name:`title`,type:`text`,placeholder:`Title`,default:`New Counter`},{name:`count`,type:`number`,placeholder:`Count`,default:99},{name:`icon`,type:`text`,placeholder:`Icon (%, +, k)`,default:`%`}]}],counters:[{title:`Active Students`,count:45,icon:`k+`},{title:`Faculty Courses`,count:89,icon:`+`},{title:`Best Professors`,count:156,icon:`k`},{title:`Award Achieved`,count:42,icon:`k`}]},init(){let e=this;e.on(`change:counters`,()=>e.updateContent()),e.updateContent()},updateContent(){let e=`
                    <div class="container">
                        <div class="fact__inner-wrap">
                            <div class="row">
                                ${(this.get(`counters`)||[]).map(e=>`
                    <div class="col-lg-3 col-sm-6">
                        <div class="fact__item">
                            <h2 class="count">
                                <span class="odometer" data-count="${e.count}">${e.count}</span>${e.icon}
                            </h2>
                            <p>${e.title}</p>
                        </div>
                    </div>
                `).join(``)}
                            </div>
                        </div>
                    </div>
                `;this.components(e),this.applyRules(this,!0)},applyRules(e,t=!1){e.set({editable:!1,draggable:t,droppable:!1,copyable:t,selectable:t}),e.components().forEach(e=>this.applyRules(e))}}})};export{n as default};