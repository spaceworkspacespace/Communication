import * as fn from 'lodash/fp'

abstract class MaybeMonad<T> {

    abstract get value(): T;

    abstract filter(f: (a: T) => boolean): MaybeMonad<T | void>;

    abstract getOrElse<R>(other: R): T | R;

    static just<R>(a: R): MaybeMonad<R> {
        return new Just(a);
    }

    abstract map<R>(f: (a: T) => R): MaybeMonad<T | R>;

    static nothing<R>(): MaybeMonad<void> {
        return new Nothing();
    }

    static fromNullable<R>(a: R) {
        return a !== null ? this.just(a) : this.nothing();
    }

    static of<R>(a: R): MaybeMonad<R> {
        return this.just(a);
    }

    get isNothing(): boolean {
        return false;
    }

    get isJust(): boolean {
        return false;
    }

    abstract toString(): string;
}

class Just<T> extends MaybeMonad<T> {
    private _value: T;
    constructor(value: T) {
        super();
        this._value = value;
    }

    get isJust(): boolean {
        return true;
    }

    get value(): T {
        return this._value;
    }

    map<R>(f: (a: T) => R): MaybeMonad<T | R> {
        return MaybeMonad.of(f(this.value));
    }

    getOrElse<R>(other: R): T | R {
        return this.value;
    }

    filter(f: (a: T) => boolean): MaybeMonad<T | void> {
        return MaybeMonad.fromNullable(f(this.value) ? this.value : null)
    }

    toString(): string {
        return `Maybe.Just(${this.value})`;
    }

}

class Nothing<T> extends MaybeMonad<T>{
    get isNothing(): boolean {
        return true;
    }
    get value(): T {
        throw new TypeError('Can\'t extract the value of a Nothing.');
    }

    filter(f: (a: T) => boolean): MaybeMonad<T | void> {
        return this;
    }

    getOrElse<R>(other: R): T | R {
        return other;
    }

    map<R>(f: (a: T) => R): MaybeMonad<T | R> {
        return this;
    }

    toString(): string {
        return "Maybe.Nothing";
    }
}