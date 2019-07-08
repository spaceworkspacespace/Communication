

export async function err_retry(fn: () => any, count: number = 3) {
    while (count-- > 0) {
        try {
            let server = await Promise.resolve(fn());
            return server;
        } catch (e) {
            console.error(e);
            await new Promise((resolve, reject) => setTimeout(resolve, 1000));
        }
    }
    return fn();
}

export namespace fp {
    namespace t {
        export type BiFunction<T, U, R> = (t: T, r: U) => R;
        export type Function<T, R> = (t: T) => R;
        export type Consumer<T> = (t: T) => void;
        export type Supplier<R> = () => R;
        export type Predicate<T> = (t: T) => boolean;
        export type BiConsumer<T, U> = (t: T, u: U) => void;
    }

    /** Combinator **/
    export function seq<T>(...funs: t.Consumer<T>[]): t.Consumer<T> {
        return (value: T) => funs.forEach(fn => fn(value));
    }

    export function fork<T, M1, M2, R>(
        join: t.BiFunction<M1, M2, R>,
        fork1: t.Function<T, M1>,
        fork2: t.Function<T, M2>): t.Function<T, R> {
        return (value: T) => join(fork1(value), fork2(value));
    }
}