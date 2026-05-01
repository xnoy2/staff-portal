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
    'rgba(239,35,60,0.90)',
    'rgba(239,35,60,0.55)',
    'rgba(255,255,255,0.75)',
    'rgba(255,255,255,0.45)',
    'rgba(141,153,174,0.65)',
];
const LINK_DIST   = 155;
const MOUSE_DIST  = 180;
const REPEL_DIST  = 80;
const REPEL_FORCE = 0.12;
const COUNT       = 90;

function mkParticle(w, h) {
    const vx = (Math.random() - 0.5) * 0.6;
    const vy = (Math.random() - 0.5) * 0.6;
    const colorIdx = Math.floor(Math.random() * COLORS.length);
    return {
        x:          Math.random() * w,
        y:          Math.random() * h,
        vx,         vy,
        bvx:        vx,
        bvy:        vy,
        r:          1.0 + Math.random() * 2.2,
        color:      COLORS[colorIdx],
        isRed:      colorIdx < 2,
        phase:      Math.random() * Math.PI * 2,
        pulseSpeed: 0.015 + Math.random() * 0.02,
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

    // Move particles + cursor repulsion + pulse
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

        p.vx   += (p.bvx - p.vx) * 0.03;
        p.vy   += (p.bvy - p.vy) * 0.03;
        p.x    += p.vx;
        p.y    += p.vy;
        p.phase += p.pulseSpeed;

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
                const alpha = 0.28 * (1 - dist / LINK_DIST);
                ctx.beginPath();
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.strokeStyle = `rgba(255,255,255,${alpha})`;
                ctx.lineWidth   = 0.7;
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
                const alpha = 0.45 * (1 - dist / MOUSE_DIST);
                ctx.beginPath();
                ctx.moveTo(mouse.x, mouse.y);
                ctx.lineTo(p.x, p.y);
                ctx.strokeStyle = `rgba(239,35,60,${alpha})`;
                ctx.lineWidth   = 0.9;
                ctx.stroke();
            }
        }

        // Cursor dot
        ctx.beginPath();
        ctx.arc(mouse.x, mouse.y, 2.5, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(239,35,60,0.8)';
        ctx.fill();
    }

    // Particles
    for (const p of particles) {
        const drawR = p.r * (1 + 0.28 * Math.sin(p.phase));

        // Soft glow halo on red particles
        if (p.isRed) {
            ctx.beginPath();
            ctx.arc(p.x, p.y, drawR * 4.5, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(239,35,60,0.07)';
            ctx.fill();
        }

        // Main dot
        ctx.beginPath();
        ctx.arc(p.x, p.y, drawR, 0, Math.PI * 2);
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
