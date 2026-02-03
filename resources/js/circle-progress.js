/* jshint esversion: 9 */
/* jshint browser: true */
/* jshint devel: true */

/**
 * Circle progress animation functionality for countdown display
 */
// biome-ignore-start lint/correctness/noUnusedVariables: CircleProgress is used globally
var CircleProgress = {
	// biome-ignore-end lint/correctness/noUnusedVariables: CircleProgress is used globally
	trackPercent: 0,
	x: 0,
	y: 0,
	radius: false,
	curPerc: 0,
	counterClockwise: false,
	circ: Math.PI * 2,
	quart: Math.PI / 2,

	/**
	 * Initialize and draw the circle
	 * @param {string} id - Canvas element ID
	 */
	drawCircle: function (id) {
		var canvas, context;

		canvas = document.getElementById(id);
		if (!canvas) {
			NotificationUtils.warning("Canvas element not found: " + id);
			return;
		}
		context = canvas.getContext("2d");
		if (!context) {
			NotificationUtils.warning("Could not get 2d context for canvas: " + id);
			return;
		}
		this.x = canvas.width / 2;
		this.y = canvas.height / 2;
		this.radius = 10;
		context.lineWidth = 3;
		this.endPercent = 85;
		this.curPerc = 0;
		context.strokeStyle = "#ad2323";
		this.animate(0, id);
	},

	/**
	 * Animate the circle progress
	 * @param {number} current - Current progress (0-1)
	 * @param {string} id - Canvas element ID
	 */
	animate: function (current, id) {
		var canvas, context;

		canvas = document.getElementById(id);
		context = canvas.getContext("2d");

		context.clearRect(0, 0, canvas.width, canvas.height);
		context.beginPath();
		context.arc(
			this.x,
			this.y,
			this.radius,
			-this.quart,
			this.circ * current - this.quart,
			false,
		);
		context.stroke();
	},
};

// Make CircleProgress available globally
window.CircleProgress = CircleProgress;
