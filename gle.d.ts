export type gleGame = {
    /**
     * version of the gleGame
     * @default 1.0
     */
    version: String
    /**
     * title to the glegame
     */
    title: String
}
type float = number;

interface IStateDevice {
    saveState(): void;
    restoreState(): void;
}

interface GraphicDevice extends IStateDevice {
    drawRect(
        /**
         * width of the rectangle 
         */
        width: float,
        /**
         * height of the rectangle
         */
        height: float,
        /**
         * x position of the rectangle
         */
        rx: float = 0,
        /**
         * y position of the rectangle
         */
        ry: float = 0
    ): void;
    /**
     * draw circle 
     * @param radius 
     * @param rx x center position 1
     * @param ry y center position 2     
     */
    drawCirc(
        radius: float,
        /**
         * x center position
         * @default 100.0
         */
        rx?: float = 100.0,
        /**
         * y center position
        * @default 100.0
        */
        ry?: float = 100.0
    ): void;
    drawEllipse(): void;
    drawPolygon(): void;
    drawPath(): void;
    drawSpline(): void;
    fillRect(): void;
    fillCirc(): void;
    fillEllipse(): void;
    fillPolygon(): void;
    fillPath(): void;
    fillSpline(): void;
}
type ColorBase = 'text-color' | 'background-color';

/**
 * represent a pogram protocol
 */
interface IProgramProtocol {
    /**
     * name of the program protocal
     */
    name?: String,
    /**
     * target url 
     */
    url: String,
    tag?: object,
    /**
     * base color type 
     * 
     */
    baseColor: ColorBase
}

type DoAction = () => void;
type RenderDelegate = (gl: GraphicDevice, obj?: ILitterDraw) => void;

declare function raiseEvent(x: int, y: int): void;

// ()=>{
//     console.log('raise event');
// }
type IRenderObj = {

};
interface ILitterDraw{
    (x: number):void;
    new (x: any): IRenderObj;
}


export { type IProgramProtocol, type ColorBase, type DoAction, RenderDelegate, type ILitterDraw } 