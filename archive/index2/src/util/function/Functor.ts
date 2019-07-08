

class Functor<T> {
    private _value: T;

    constructor(value: T) {
        this._value = value;
    }

    get value(): T {
        return this._value;
    }

    map<R>(f: (a: T) => R) {
        return f(this.value)
    }

    fmap<R>(f: (a: T) => R) {
        return new Functor(f(this.value));
    }
}