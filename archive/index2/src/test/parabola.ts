

class Parabola {
    // 水平和垂直初速度
    private v0: number;
    // Gravitational acceleration
    private g: number;
    // 角度
    private radian: number;

    // private animationId: number;

    private static stop: boolean = true;

    constructor(options: { v0: number, g?: number, r: number }) {
        this.v0 = options.v0;
        if (!options.g)
            this.g = 9.8;
        else
            this.g = options.g;
        this.radian = options.r;

        if (Parabola.stop) {
            this.animate();
            Parabola.stop = false;
        }
    }
    animate() {
        if (Parabola.stop) return;
        requestAnimationFrame(this.animate);
        TWEEN.update();
    }

    obtainCoords(t: number) {
        return {
            x: this.v0 * t * Math.cos(this.radian),
            y: this.v0 * t * Math.sin(this.radian) - this.g * Math.pow(t, 2) / 2
        };
    }

    run(el: HTMLElement, zero: { x: number, y: number }, t: number) {
        if (Parabola.stop) {
            Parabola.stop = false;
            this.animate();
        }
        let tween = new TWEEN.Tween({ t: 0 })
            .to({ t }, t)
            .easing(TWEEN.Easing.Quartic.Out)
            .onUpdate(o => {
                let coords = this.obtainCoords(o.t);
                el.setAttribute("style", "top: " + coords.y + "px; left: ")
            }).
            start();

    }

}
export {
    Parabola
};