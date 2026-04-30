<template>
    <canvas ref="canvas" class="absolute inset-0 w-full h-full" />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const canvas = ref(null);
let animId = null;
let particles = [];

const COLORS = [
    'rgba(239,35,60,0.8)',
    'rgba(239,35,60,0.4)',
    'rgba(255,255,255,0.6)',
    'rgba(255,255,255,0.3)',
    'rgba(141,153,174,0.5)',
];
const LINK_DIST  = 130;
const COUNT      = 65;

function mkParticle(w, h) {
    return {
        x:     Math.random() * w,
        y:     Math.random() * h,
        vx:    (Math.random() - 0.5) * 0.55,
        vy:    (Math.random() - 0.5) * 0.55,
        r:     Math.random() * 1.8 + 0.8,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
    };
}

function init(w, h) {
    particles = Array.from({ length: COUNT }, () => mkParticle(w, h));
}

function draw() {
    const cvs = canvas.value;
    if (!cvs) return;
    const ctx = cvs.getContext('2d');
    const { width: w, height: h } = cvs;

    ctx.clearRect(0, 0, w, h);

    for (const p of particles) {
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0 || p.x > w) p.vx *= -1;
        if (p.y < 0 || p.y > h) p.vy *= -1;
    }

    for (let i = 0; i < particles.length; i++) {
        for (let j = i + 1; j < particles.length; j++) {
            const dx   = particles[i].x - particles[j].x;
            const dy   = particles[i].y - particles[j].y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < LINK_DIST) {
                const alpha = 0.12 * (1 - dist / LINK_DIST);
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.strokeStyle = `rgba(255,255,255,${alpha})`;
                ctx.lineWidth   = 0.6;
                ctx.stroke();
            }
        }
    }

    for (const p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
    }

    animId = requestAnimationFrame(draw);
}

function resize() {
    const cvs = canvas.value;
    if (!cvs) return;
    cvs.width  = cvs.offsetWidth;
    cvs.height = cvs.offsetHeight;
    init(cvs.width, cvs.height);
}

onMounted(() => {
    resize();
    draw();
    window.addEventListener('resize', resize);
});

onUnmounted(() => {
    if (animId) cancelAnimationFrame(animId);
    window.removeEventListener('resize', resize);
});
</script>
