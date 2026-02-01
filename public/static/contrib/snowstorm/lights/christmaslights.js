// Christmas Light Smashfest - Optimized
// Original: Adapted from XLSF 2007 as originally used on http://schillmania.com/?theme=2007&christmas=1

// Constants - pulled out for better caching and minification
const LIGHT_CLASSES = { pico: 32, tiny: 50, small: 64, medium: 72, large: 96 };
const FRAGMENT_SIZE = 50;
const ANIMATION_DURATION = 1000;
const BURST_PHASES = 4;
const VELOCITY_MULTIPLIER = 1.5;
const EASING_POWER = 3;

class ExplosionFragment {
  constructor(type, lightClass, velocityX, velocityY) {
    this.type = type;
    this.burstPhase = 1;
    this.vX = velocityX * (VELOCITY_MULTIPLIER + Math.random());
    this.vY = velocityY * (VELOCITY_MULTIPLIER + Math.random());

    // Adjust velocity based on light class
    if (lightClass === "left") {
      this.vX = Math.abs(this.vX);
    } else if (lightClass === "right") {
      this.vX = -Math.abs(this.vX);
    }

    this.element = document.createElement("div");
    this.element.className = "xlsf-fragment";
    this.element.style.willChange = "transform";
    this.updateBurstPhase();
  }

  updateBurstPhase() {
    this.element.style.backgroundPosition = `${FRAGMENT_SIZE * -this.burstPhase}px ${FRAGMENT_SIZE * -this.type}px`;
  }

  animate() {
    const startTime = performance.now();
    const targetX = this.vX * 8;
    const targetY = this.vY * 8;

    const animationStep = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / ANIMATION_DURATION, 1);

      // Easing: 1 - (1-t)^3 (easeOutStrong)
      const eased = 1 - Math.pow(1 - progress, EASING_POWER);

      // Use transform for GPU acceleration - much faster than margin/left/top
      this.element.style.transform = `translate(${targetX * eased}px, ${targetY * eased}px)`;

      // Update burst phase
      const newPhase = 1 + Math.floor(progress * BURST_PHASES);
      if (newPhase !== this.burstPhase) {
        this.burstPhase = newPhase;
        this.updateBurstPhase();
      }

      if (progress < 1) {
        requestAnimationFrame(animationStep);
      }
    };

    requestAnimationFrame(animationStep);
  }

  reset() {
    this.element.style.transform = "translate(0, 0)";
    this.burstPhase = 1;
    this.updateBurstPhase();
  }
}

class Explosion {
  static VELOCITY_VECTORS = [
    [-5, -5],
    [0, -5],
    [5, -5],
    [-5, 0],
    [0, 0],
    [5, 0],
    [5, -5],
    [5, 0],
    [5, 5],
  ];

  constructor(type, lightClass, x, y, explosionTemplate) {
    this.type = type;
    this.lightClass = lightClass;
    this.x = x;
    this.y = y;
    this.vX = 0;
    this.vY = 0;
    this.fragments = [];

    this.element = explosionTemplate.cloneNode(true);
    this.element.style.left = `${x}px`;
    this.element.style.top = `${y}px`;
    this.element.style.willChange = "transform";

    // Create fragments from static velocity vectors
    Explosion.VELOCITY_VECTORS.forEach(([vx, vy]) => {
      const fragment = new ExplosionFragment(type, lightClass, vx, vy);
      this.fragments.push(fragment);
      this.element.appendChild(fragment.element);
    });
  }

  trigger(boxVX, boxVY) {
    this.element.style.display = "block";
    this.vX = boxVX;
    this.vY = boxVY;

    // Adjust velocity for light class
    if (this.lightClass === "right") {
      this.vX = -Math.abs(this.vX);
    } else if (this.lightClass === "left") {
      this.vX = Math.abs(this.vX);
    }

    // Animate fragments
    this.fragments.forEach((f) => f.animate());

    // Animate container
    const startTime = performance.now();
    const targetX = 100 * this.vX;
    const targetY = 150 * this.vY;

    const containerStep = (currentTime) => {
      const elapsed = currentTime - startTime;
      const progress = Math.min(elapsed / ANIMATION_DURATION, 1);

      // Easing: t^3 (easeInStrong)
      const eased = Math.pow(progress, EASING_POWER);

      this.element.style.transform = `translate(${targetX * eased}px, ${targetY * eased}px)`;

      if (progress < 1) {
        requestAnimationFrame(containerStep);
      } else {
        this.reset();
      }
    };

    requestAnimationFrame(containerStep);
  }

  reset() {
    this.element.style.display = "none";
    this.element.style.transform = "translate(0, 0)";
    this.element.style.left = `${this.x}px`;
    this.element.style.top = `${this.y}px`;
    this.fragments.forEach((f) => f.reset());
  }
}

class Light {
  constructor(sizeClass, lightClass, type, x, y, urlBase, explosionTemplate) {
    this.lightClass = lightClass;
    this.type = type;
    this.useY = lightClass === "left" || lightClass === "right";
    this.state = null;
    this.broken = false;
    this.glassType = Math.floor(Math.random() * 6);
    this.soundId = `smash${this.glassType}`;

    const size = LIGHT_CLASSES[sizeClass];
    this.w = size;
    this.h = size;

    // Calculate background positions
    this.bgBaseX = this.useY ? -this.w * type : 0;
    this.bgBaseY = !this.useY ? -this.h * type : 0;

    // Create element using cssText for better performance
    this.element = document.createElement("div");
    this.element.className = `xlsf-light ${sizeClass} ${lightClass}`;
    this.element.style.cssText =
      `left:${x}px;top:${y}px;width:${this.w}px;height:${this.h}px;` +
      `background:url(${urlBase}image/bulbs-${this.w}x${this.h}-${lightClass}.png) no-repeat 0 0;` +
      `will-change:background-position`;

    // Calculate audio pan
    const panValue = 75;
    const mid = 481;
    const right = 962;
    this.pan =
      x <= mid
        ? -panValue + (x / mid) * panValue
        : ((x - mid) / (right - mid)) * panValue;

    this.explosion = new Explosion(type, lightClass, x, y, explosionTemplate);

    // Store bound handler for removal
    this.smashHandler = () => this.smash();
    this.element.addEventListener("click", this.smashHandler);

    this.flicker();
  }

  setBackgroundPosition(offsetX, offsetY) {
    this.element.style.backgroundPosition = `${this.bgBaseX + offsetX}px ${this.bgBaseY + offsetY}px`;
  }

  setLight(isOn) {
    if (this.broken || this.state === isOn) return false;

    this.state = isOn;
    if (this.useY) {
      this.setBackgroundPosition(0, -this.h * (isOn ? 0 : 1));
    } else {
      this.setBackgroundPosition(-this.w * (isOn ? 0 : 1), 0);
    }
    return true;
  }

  on() {
    this.setLight(true);
  }
  off() {
    this.setLight(false);
  }
  toggle() {
    this.setLight(!this.state);
  }
  flicker() {
    this.setLight(Math.random() >= 0.5);
  }

  smash() {
    if (this.broken) return;

    this.broken = true;

    // Play sound if available
    if (window.soundManager?.ok?.()) {
      soundManager.play(this.soundId, { pan: this.pan });
    }

    this.explosion.trigger(0, 1);

    const brokenFrame = 2;
    if (this.useY) {
      this.setBackgroundPosition(0, this.h * -brokenFrame);
    } else {
      this.setBackgroundPosition(this.w * -brokenFrame, 0);
    }
  }

  reset() {
    if (!this.broken) return;
    this.broken = false;
    this.state = null;
    this.flicker();
  }

  destroy() {
    this.element.removeEventListener("click", this.smashHandler);
  }
}

class ChristmasLights {
  constructor(targetElement, urlBase = "lights/") {
    this.targetElement = targetElement;
    this.urlBase = urlBase;
    this.lights = [];
    this.lightGroups = { left: [], top: [], right: [], bottom: [] };
    this.sequenceTimer = null;
    this.lightIndex = 0;

    // Get screen dimensions once
    const { screenX, screenY } = this.getScreenDimensions();

    // Determine light size
    const lightClass = screenX > 1800 ? "small" : "pico";
    const lightSize = LIGHT_CLASSES[lightClass];

    // Create reusable template
    const explosionTemplate = document.createElement("div");
    explosionTemplate.className = "xlsf-fragment-box";

    // Create cover
    const cover = document.createElement("div");
    cover.className = "xlsf-cover";
    document.documentElement.appendChild(cover);

    // Create lights using DocumentFragment for batch insertion - huge performance boost
    const fragment = document.createDocumentFragment();
    const offset = 0;
    const jMax = Math.floor((screenX - offset - 16) / lightSize);

    for (let j = 0; j < jMax; j++) {
      const light = new Light(
        lightClass,
        "top",
        Math.floor(j / 3) % 4,
        offset + j * lightSize,
        0,
        urlBase,
        explosionTemplate,
      );

      this.lights.push(light);
      this.lightGroups.top.push(light);
      fragment.appendChild(light.element);
      fragment.appendChild(light.explosion.element);
    }

    // Single DOM insertion instead of many
    targetElement.appendChild(fragment);

    // Initialize sounds
    this.initSounds();

    // Start animation
    this.startSequence(() => this.randomLights(), 500);
  }

  getScreenDimensions() {
    return {
      screenX:
        window.innerWidth ||
        document.documentElement.clientWidth ||
        document.body.clientWidth,
      screenY:
        window.innerHeight ||
        document.documentElement.clientHeight ||
        document.body.clientHeight,
    };
  }

  initSounds() {
    if (!window.soundManager) return;

    for (let i = 0; i < 6; i++) {
      soundManager.createSound({
        id: `smash${i}`,
        url: `${this.urlBase}sound/glass${i}.mp3`,
        autoLoad: true,
        multiShot: true,
        volume: 50,
      });
    }
  }

  randomLights() {
    if (this.lights.length === 0) return;
    this.lights[Math.floor(Math.random() * this.lights.length)].toggle();
  }

  rotateLights() {
    if (this.lights.length === 0) return;
    this.lights[this.lightIndex].off();
    this.lightIndex = (this.lightIndex + 1) % this.lights.length;
    this.lights[this.lightIndex].on();
  }

  destroyLights() {
    let index = 0;
    const groupSize = 2;
    const step = () => {
      const limit = Math.min(index + groupSize, this.lights.length);
      for (let i = index; i < limit; i++) {
        this.lights[i].smash();
      }
      index = limit;
      if (index < this.lights.length) {
        this.sequenceTimer = setTimeout(step, 20);
      }
    };
    step();
  }

  startSequence(callback, interval) {
    this.stopSequence();
    this.sequenceTimer = setInterval(callback, interval);
  }

  stopSequence() {
    if (this.sequenceTimer) {
      clearInterval(this.sequenceTimer);
      this.sequenceTimer = null;
    }
  }

  destroy() {
    this.stopSequence();
    this.lights.forEach((light) => light.destroy());
    this.lights = [];
  }
}

// Initialize
function initChristmasLights() {
  const lightsElement = document.getElementById("lights");
  if (!lightsElement) {
    console.warn("Christmas lights container not found");
    return;
  }

  const urlBase = window.LIGHTS_URL_BASE || "lights/";
  window.christmasLights = new ChristmasLights(lightsElement, urlBase);

  const loadingElement = document.getElementById("loading");
  if (loadingElement) {
    loadingElement.style.display = "none";
  }
}

// Auto-initialize when ready
if (window.soundManager) {
  soundManager.setup({
    flashVersion: 9,
    preferFlash: false,
    url: "lights/",
    onready: initChristmasLights,
    ontimeout: initChristmasLights,
  });
} else if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initChristmasLights);
} else {
  initChristmasLights();
}
