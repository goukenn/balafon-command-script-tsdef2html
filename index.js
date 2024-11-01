// 
'use strict';
 

/**
 *  @type {import("./gle.d").gleGame}
 */
let g = {
    version: '1.0',
    title: "present"
};
/**
 *  @type {import("./gle.d").IProgramProtocol}
 */
let c = {
    url: 'undefined',
    baseColor: 'text-color'
}

 
/**
 * @type {import("./gle.d").RenderDelegate}
 */
const render = (gl, draw)=>{
    
    gl.saveState(); 

    gl.drawRect(50,100);
    gl.drawCirc(60,0,6);
    gl.restoreState();
};
/**
 * @type {import("./gle.d").DoAction}
 */
let j = ()=>{
    console.log('ok')
}
c.baseColor = 'background-color'

console.log(g);
console.log(c);
console.log(j);
 

 