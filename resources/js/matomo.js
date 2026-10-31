// Thin wrappers around Matomo's `_paq` queue. All are no-ops when Matomo is not
// configured (resources/views/partials/matomo.blade.php only renders the
// tracker when the MatomoService returns a config).
//
// No optional chaining here: Docket still has to run on iOS 12 (see
// bin/check-ios12-compat.sh).

function push(payload) {
	if (window._paq) {
		window._paq.push(payload);
	}
}

export function trackPageView() {
	push(["trackPageView"]);
}

export function trackEvent(category, action, name) {
	const payload = ["trackEvent", category, action];

	if (name !== undefined) {
		payload.push(name);
	}

	push(payload);
}

// `goal` is a key from config('services.matomo.goals'), e.g. 'registration'.
export function trackGoal(goal) {
	const goalId = window.matomoGoals ? window.matomoGoals[goal] : undefined;

	if (goalId !== undefined) {
		push(["trackGoal", goalId]);
	}
}
