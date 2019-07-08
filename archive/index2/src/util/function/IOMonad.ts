
import * as fn from 'lodash/fp';

type EffectType = () => any;
type UnaryFun = (a: any) => any;
export class IOMonad {
    private _effect: EffectType;

    constructor(effect: EffectType) {
        if (!fn.isFunction(effect)) {
            throw "IO Usage: function required";
        }
        this._effect = effect;
    }

    static of(fn: EffectType) {
        return new IOMonad(fn);
    }

    static from(a: any) {
        return new IOMonad(() => a);
    }

    map(fn: UnaryFun) {
        return new IOMonad(() => fn(this._effect()));
    }

    chain(fn: UnaryFun) {
        return fn(this._effect());
    }

    run() {
        return this._effect();
    }
}