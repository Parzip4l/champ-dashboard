import{n as r,w as a}from"./wNumb-BIlb4Sml.js";import"./_commonjsHelpers-BosuxZz1.js";const e=document.getElementById("product-price-range");if(e){r.create(e,{start:[200,1299],step:1,margin:0,connect:!0,behaviour:"tap-drag",range:{min:0,max:1500},format:a({decimals:0,prefix:"$ "})});const n=document.getElementById("minCost"),i=document.getElementById("maxCost");e.noUiSlider.on("update",function(o,t){t?i.value=o[t]:n.value=o[t]}),n.addEventListener("change",function(){e.noUiSlider.set([null,this.value])}),i.addEventListener("change",function(){e.noUiSlider.set([null,this.value])})}