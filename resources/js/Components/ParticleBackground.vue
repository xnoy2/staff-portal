<template>
    <canvas ref="canvas" class="absolute inset-0 w-full h-full" />
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const canvas = ref(null);
let animId = null;
let particles = [];

const mouse = { x: null, y: null };

const COLORS = [
    'rgba(239,35,60,0.8)',
    'rgba(239,35,60,0.4)',
    'rgba(255,255,255,0.6)',
    'rgba(255,255,255,0.3)',
    'rgba(141,153,174,0.5)',
];
const LINK_DIST    = 130;
const MOUSE_DIST   = 160;   // cursor connection radius
const REPEL_DIST   = 80;    // cursor push radius
const REPEL_FORCE  = 0.12;
const COUNT        = 65;

function mkParticle(w, h) {
    return {
        x:   Math.random() * w,
        y:   Math.random() * h,
        vx:  (Math.random() - 0.5) * 0.55,
        vy:  (Math.random() - 0.5) * 0.55,
        r:   Math.random() * 1.8 + 0.8,
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

    // Move particles + cursor repulsion
    for (const p of particles) {
        if (mouse.x !== null) {
            const dx   = p.x - mouse.x;
            const dy   = p.y - mouse.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < REPEL_DIST && dist > 0) {
                const force = (1 - dist / REPEL_DIST) * REPEL_FORCE;
                p.vx += (dx / dist) * force;
                p.vy += (dy / dist) * force;
            }
        }

        // Dampen so repulsion doesn't accelerate forever
        p.vx *= 0.995;
        p.vy *= 0.995;

        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0 || p.x > w) p.vx *= -1;
        if (p.y < 0 || p.y > h) p.vy *= -1;
    }

    // Particle–particle connections
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

    // Cursor → particle connections
    if (mouse.x !== null) {
        for (const p of particles) {
            const dx   = p.x - mouse.x;
            const dy   = p.y - mouse.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist < MOUSE_DIST) {
                const alpha = 0.35 * (1 - dist / MOUSE_DIST);
                ctx.beginPath();
                ctx.moveTo(mouse.x, mouse.y);
                ctx.lineTo(p.x, p.y);
                ctx.strokeStyle = `rgba(239,35,60,${alpha})`;
                ctx.lineWidth   = 0.8;
                ctx.stroke();
            }
        }

        // Cursor dot
        ctx.beginPath();
        ctx.arc(mouse.x, mouse.y, 2.5, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(239,35,60,0.7)';
        ctx.fill();
    }

    // Particles
    for (const p of particles) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.fill();
    }

    animId = requestAnimationFrame(draw);
}

function onMouseMove(e) {
    const cvs = canvas.value;
    if (!cvs) return;
    const rect = cvs.getBoundingClientRect();
    mouse.x = e.clientX - rect.left;
    mouse.y = e.clientY - rect.top;
}

function onMouseLeave() {
    mouse.x = null;
    mouse.y = null;
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
    window.addEventListener('mousemove', onMouseMove);
    window.addEventListener('mouseleave', onMouseLeave);
});

onUnmounted(() => {
    if (animId) cancelAnimationFrame(animId);
    window.removeEventListener('resize', resize);
    window.removeEventListener('mousemove', onMouseMove);
    window.removeEventListener('mouseleave', onMouseLeave);
});
</script>
