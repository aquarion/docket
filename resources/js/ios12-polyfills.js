/**
 * Polyfills for iOS 12 compatibility
 * Must be the first import in app.js so polyfills are available before any application code runs.
 */

// Polyfill for String.prototype.includes
if (!String.prototype.includes) {
	String.prototype.includes = function (search, start) {
		if (typeof start !== "number") {
			start = 0;
		}

		if (start + search.length > this.length) {
			return false;
		} else {
			return this.indexOf(search, start) !== -1;
		}
	};
}

// Polyfill for Array.prototype.includes
if (!Array.prototype.includes) {
	Array.prototype.includes = function (searchElement, fromIndex) {
		var o = Object(this);
		var len = parseInt(o.length, 10) || 0;
		if (len === 0) {
			return false;
		}
		var n = parseInt(fromIndex, 10) || 0;
		var k;
		if (n >= 0) {
			k = n;
		} else {
			k = len + n;
			if (k < 0) {
				k = 0;
			}
		}
		var currentElement;
		while (k < len) {
			currentElement = o[k];
			// NaN is the only value not equal to itself; use self-inequality (x !== x) to detect it.
			if (
				searchElement === currentElement ||
				(searchElement !== searchElement && currentElement !== currentElement)
			) {
				return true;
			}
			k++;
		}
		return false;
	};
}

// Polyfill for Array.prototype.find
if (!Array.prototype.find) {
	Array.prototype.find = function (predicate) {
		if (this == null) {
			throw new TypeError("Array.prototype.find called on null or undefined");
		}
		if (typeof predicate !== "function") {
			throw new TypeError("predicate must be a function");
		}
		var list = Object(this);
		var length = parseInt(list.length, 10) || 0;
		var thisArg = arguments[1];
		var value;
		var i;

		for (i = 0; i < length; i++) {
			value = list[i];
			if (predicate.call(thisArg, value, i, list)) {
				return value;
			}
		}
		return undefined;
	};
}

// Polyfill for Element.closest
if (!Element.prototype.closest) {
	Element.prototype.closest = function (s) {
		var el = this;
		if (!document.documentElement.contains(el)) return null;
		do {
			if (Element.prototype.matches.call(el, s)) return el;
			el = el.parentElement || el.parentNode;
		} while (el !== null && el.nodeType === 1);
		return null;
	};
}

// Polyfill for Element.matches
if (!Element.prototype.matches) {
	Element.prototype.matches =
		Element.prototype.msMatchesSelector ||
		Element.prototype.webkitMatchesSelector;
}

// Polyfill for Object.assign
if (typeof Object.assign !== "function") {
	Object.assign = function (target) {
		if (target == null) {
			throw new TypeError("Cannot convert undefined or null to object");
		}

		var to = Object(target);
		var index, nextSource, nextKey;

		for (index = 1; index < arguments.length; index++) {
			nextSource = arguments[index];

			if (nextSource != null) {
				for (nextKey in nextSource) {
					// biome-ignore lint: Object.hasOwn is ES2022 and unavailable on iOS 12
					if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
						to[nextKey] = nextSource[nextKey];
					}
				}
			}
		}
		return to;
	};
}

// Minimal fetch polyfill — supports method, headers, and body but omits streaming, abort, credentials, and redirect handling.
if (!window.fetch) {
	window.fetch = (url, options) =>
		new Promise((resolve, reject) => {
			var xhr = new XMLHttpRequest();
			// biome-ignore lint: optional chaining is unsafe in polyfill files before transpilation
			xhr.open((options && options.method) || "GET", url);

			// biome-ignore lint: optional chaining is unsafe in polyfill files before transpilation
			if (options && options.headers) {
				Object.keys(options.headers).forEach((key) => {
					xhr.setRequestHeader(key, options.headers[key]);
				});
			}

			xhr.onload = () => {
				resolve({
					ok: xhr.status >= 200 && xhr.status < 300,
					status: xhr.status,
					statusText: xhr.statusText,
					text: () => Promise.resolve(xhr.responseText),
					json: () => {
						try {
							return Promise.resolve(JSON.parse(xhr.responseText));
						} catch (e) {
							return Promise.reject(e);
						}
					},
				});
			};

			xhr.onerror = () => {
				reject(new Error("Network request failed"));
			};

			// biome-ignore lint: optional chaining is unsafe in polyfill files before transpilation
			xhr.send((options && options.body) || null);
		});
}

// Promise polyfill for pre-iOS 12 browsers — iOS 12 (Safari 12) ships Promise natively,
// so this only runs on iOS 9–11 devices that receive the legacy bundle.
if (typeof Promise === "undefined") {
	window.Promise = function (executor) {
		var self = this;
		this.state = "pending";
		this.value = undefined;
		this.handlers = [];

		function resolve(result) {
			if (self.state === "pending") {
				self.state = "fulfilled";
				self.value = result;
				self.handlers.forEach(handle);
				self.handlers = null;
			}
		}

		function reject(error) {
			if (self.state === "pending") {
				self.state = "rejected";
				self.value = error;
				self.handlers.forEach(handle);
				self.handlers = null;
			}
		}

		function handle(handler) {
			if (self.state === "pending") {
				self.handlers.push(handler);
			} else {
				if (
					self.state === "fulfilled" &&
					typeof handler.onFulfilled === "function"
				) {
					handler.onFulfilled(self.value);
				}
				if (
					self.state === "rejected" &&
					typeof handler.onRejected === "function"
				) {
					handler.onRejected(self.value);
				}
			}
		}

		// biome-ignore lint/suspicious/noThenProperty: Promise polyfill requires then method
		this.then = (onFulfilled, onRejected) =>
			new Promise((resolve, reject) => {
				handle({
					onFulfilled: (result) => {
						try {
							resolve(onFulfilled ? onFulfilled(result) : result);
						} catch (ex) {
							reject(ex);
						}
					},
					onRejected: (error) => {
						try {
							resolve(onRejected ? onRejected(error) : error);
						} catch (ex) {
							reject(ex);
						}
					},
				});
			});

		executor(resolve, reject);
	};
}

// Polyfill for queueMicrotask (Safari 12.1+ / iOS 12.2+)
if (typeof window.queueMicrotask !== "function") {
	window.queueMicrotask = (callback) => {
		new Promise((resolve) => {
			resolve();
		})
			.then(callback)
			.catch((e) => {
				setTimeout(() => {
					throw e;
				}, 0);
			});
	};
}

// Polyfill for structuredClone (Safari 15.4+)
// Note: JSON-based approximation only — does not support Date, undefined, Map, Set, functions, or circular references.
if (typeof window.structuredClone !== "function") {
	window.structuredClone = (obj) => JSON.parse(JSON.stringify(obj));
}

// Polyfill for ResizeObserver (Safari 13.1+)
if (typeof window.ResizeObserver === "undefined") {
	window.ResizeObserver = function (callback) {
		var listeners = [];
		this.observe = (el) => {
			callback([{ target: el, contentRect: el.getBoundingClientRect() }]);
			var handler = () => {
				callback([{ target: el, contentRect: el.getBoundingClientRect() }]);
			};
			listeners.push({ el: el, handler: handler });
			window.addEventListener("resize", handler);
		};
		this.unobserve = (el) => {
			listeners = listeners.filter((entry) => {
				if (entry.el === el) {
					window.removeEventListener("resize", entry.handler);
					return false;
				}
				return true;
			});
		};
		this.disconnect = () => {
			listeners.forEach((entry) => {
				window.removeEventListener("resize", entry.handler);
			});
			listeners = [];
		};
	};
}
