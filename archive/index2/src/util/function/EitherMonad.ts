



export abstract class EitherMonad<T> {
    private _value: T;

    constructor(value: T) {
        this._value = value;
    }

    get value(): T {
        return this._value;
    }

    chain<R>(f: (a: T) => R): R | EitherMonad<T> {
        return f(this.value);
    }

    filter(f: (a: T) => boolean) {
        return EitherMonad.fromNullable(f(this.value) ? this.value : null);
    }

    abstract getOrElse<R>(other: R): R | T;

    abstract getOrElseThrow(a: string): T;

    abstract map<R>(f: (a: T) => R): EitherMonad<R | T>;

    static left<T>(a: T): EitherMonad<T> {
        return new Left<T>(a);
    }

    abstract orElse<R>(f: (a: T) => R): R | EitherMonad<T>;

    static right<T>(a: T): EitherMonad<T> {
        return new Right<T>(a);
    }

    static fromNullable<T>(val: T): EitherMonad<T> {
        return val !== null ? this.right(val) : this.left(val);
    }

    static of<T>(a: T): EitherMonad<T> {
        return this.right(a);
    }

    abstract toString(): string;
}

class Left<T> extends EitherMonad<T> {
    map<R>(f: (a: T) => R): EitherMonad<R | T> {
        return this;
    }

    get value(): T {
        throw new TypeError('Can\'t extract the value of a Left(a).');
    }

    getOrElse<R>(other: R): R | T {
        return other;
    }

    orElse<R>(f: (a: T) => R): R | EitherMonad<T> {
        return f(this.value);
    }

    chain<R>(f: (a: T) => R): R | EitherMonad<T> {
        return this;
    }

    getOrElseThrow(a: string): T {
        throw new Error(a);
    }

    filter(f: any) {
        return this;
    }

    toString() {
        return `Either.Left(${this.value})`;
    }
}

class Right<T> extends EitherMonad<T> {
    map<R>(f: (a: T) => R): EitherMonad<R> {
        return EitherMonad.of(f(this.value));
    }

    getOrElse<R>(other: R): T {
        return this.value;
    }

    orElse<R>(f: (a: T) => R): R | EitherMonad<T> {
        return this;
    }

    chain<R>(f: (a: T) => R): R | EitherMonad<T> {
        return f(this.value);
    }

    getOrElseThrow(a: string): T {
        return this.value;
    }

    toString(): string {
        return `Either.Right(${this.value})`;
    }
}