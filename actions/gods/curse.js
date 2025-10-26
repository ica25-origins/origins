// ROW 1334-1336 MODIFIED
var dst = 1 + Math.hypot(c.position.x - danger.box.position.x,
    c.position.y - danger.box.position.y);

// New Calculation - 80HP MAX in the middle
var distanceFactor = Math.min(dst / 4, 1);
var damage = 0.34 * danger.strength * (1 - distanceFactor * distanceFactor);

c.health -= damage;