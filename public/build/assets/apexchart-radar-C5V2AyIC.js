import{A as s}from"./apexcharts.common-rVUl4e9q.js";import"./_commonjsHelpers-BosuxZz1.js";var r=["#7f56da"],t={chart:{toolbar:{show:!1},height:350,type:"radar"},series:[{name:"Series 1",data:[80,50,30,40,100,20]}],colors:r,labels:["January","February","March","April","May","June"]},a=new s(document.querySelector("#basic-radar"),t);a.render();var r=["#ff6c2f"],t={chart:{height:350,type:"radar",toolbar:{show:!1}},series:[{name:"Series 1",data:[20,100,40,30,50,80,33]}],labels:["Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],plotOptions:{radar:{size:140}},colors:r,markers:{size:4,colors:["#fff"],strokeColor:r,strokeWidth:2},tooltip:{y:{formatter:function(e){return e}}},yaxis:{tickAmount:7,labels:{formatter:function(e,o){return o%2===0?e:""}}}},a=new s(document.querySelector("#radar-polygon"),t);a.render();var r=["#1c84ee","#ef5f5f","#4ecac2"],t={chart:{height:350,type:"radar",toolbar:{show:!1}},series:[{name:"Series 1",data:[80,50,30,40,100,20]},{name:"Series 2",data:[20,30,40,80,20,80]},{name:"Series 3",data:[44,76,78,13,43,10]}],stroke:{width:0},fill:{opacity:.4},markers:{size:0},legend:{offsetY:-10},colors:r,labels:["2011","2012","2013","2014","2015","2016"]},a=new s(document.querySelector("#radar-multiple-series"),t);a.render();function i(){function e(){for(var o=[],n=0;n<6;n++)o.push(Math.floor(Math.random()*100));return o}a.updateSeries([{name:"Series 1",data:e()},{name:"Series 2",data:e()},{name:"Series 3",data:e()}])}document.getElementById("update-button").addEventListener("click",i);
