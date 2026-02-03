/**
 * Polyfills for iOS 12 compatibility
 * Must be loaded before other scripts
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
		var len = parseInt(o.length) || 0;
		if (len === 0) {
			return false;
		}
		var n = parseInt(fromIndex) || 0;
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
		var length = parseInt(list.length) || 0;
		var thisArg = arguments[1];
		var value;

		for (var i = 0; i < length; i++) {
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

		for (var index = 1; index < arguments.length; index++) {
			var nextSource = arguments[index];

			if (nextSource != null) {
				for (var nextKey in nextSource) {
					if (Object.hasOwn(nextSource, nextKey)) {
						to[nextKey] = nextSource[nextKey];
					}
				}
			}
		}
		return to;
	};
}

// Simple fetch polyfill for basic GET requests
if (!window.fetch) {
	window.fetch = (url, options) =>
		new Promise((resolve, reject) => {
			var xhr = new XMLHttpRequest();
			xhr.open((options && options.method) || "GET", url);

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
					json: () => Promise.resolve(JSON.parse(xhr.responseText)),
				});
			};

			xhr.onerror = () => {
				reject(new Error("Network request failed"));
			};

			xhr.send((options && options.body) || null);
		});
}

// Promise polyfill (basic version)
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
