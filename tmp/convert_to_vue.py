import os
import re

html_path = "/home/vircom/.gemini/antigravity/scratch/climasdeldesierto/public/propuesta-sgs.html"
vue_path = "/home/vircom/.gemini/antigravity/scratch/climasdeldesierto/resources/js/Pages/Public/PropuestaSGS.vue"

with open(html_path, "r", encoding="utf-8") as f:
    content = f.read()

style_match = re.search(r"<style>(.*?)</style>", content, re.DOTALL)
body_match = re.search(r"<body>(.*?)</body>", content, re.DOTALL)

if style_match and body_match:
    style_content = style_match.group(1)
    body_content = body_match.group(1)
    
    # We must strip the <script> block out of body_content since we are putting it in Vue script setup
    body_content = re.sub(r"<script>.*?</script>", "", body_content, flags=re.DOTALL)
    
    # Replace references to /images with /images
    # (actually they are already /images)

    # Insert Mascot Sticker HTML in the hero section 
    mascot_html = """
        <div class="mascot-sticker">
            <img src="/images/tecnico_bgless.png" alt="Técnico CDD" class="tecnico" />
            <img src="/images/logo.webp" alt="Logo CDD" class="cdd-logo" />
        </div>
        <div class="print-toolbar">
            <button @click="printPage" class="btn-print">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                Imprimir Propuesta
            </button>
        </div>
"""
    
    # inject the mascot into the hero section
    body_content = body_content.replace('<div class="watermark">CDD</div>', f'<div class="watermark">CDD</div>\n{mascot_html}')
    
    vue_content = f"""<template>
    <div class="propuesta-sgs">
        <Head title="Propuesta Anual de Mantenimiento - SGS México" />
{body_content}
    </div>
</template>

<script setup>
import {{ Head }} from '@inertiajs/vue3';
import {{ onMounted }} from 'vue';

onMounted(() => {{
    /* Smooth reveal animations on scroll */
    const observer = new IntersectionObserver((entries) => {{
        entries.forEach(entry => {{
            if (entry.isIntersecting) {{
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }}
        }});
    }}, {{ threshold: 0.1, rootMargin: '0px 0px -50px 0px' }});

    document.querySelectorAll('.section, .info-card, .table-wrapper, .totals-card, .term-item, .cta-card, .detail-card, .emergency-banner, .cert-badge').forEach(el => {{
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    }});

    /* Add staggered delay to grid items */
    document.querySelectorAll('.info-card, .term-item, .cert-badge').forEach((el, i) => {{
        el.style.transitionDelay = `${{i * 0.1}}s`;
    }});

    document.querySelectorAll('.table-row').forEach((el, i) => {{
        el.style.opacity = '0';
        el.style.transform = 'translateX(-10px)';
        el.style.transition = `opacity 0.3s ease ${{i * 0.04}}s, transform 0.3s ease ${{i * 0.04}}s, background 0.15s ease`;
        observer.observe(el);
    }});
}});

const printPage = () => {{
    window.print();
}};
</script>

<style scoped>
{style_content}

/* Adjustments for the scoped container */
.propuesta-sgs {{
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--text-primary);
    background: var(--brand-surface);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    min-height: 100vh;
}}

.print-toolbar {{
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 100;
}}

.btn-print {{
    background: var(--brand-primary);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 50px;
    font-weight: bold;
    font-size: 14px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(2, 132, 199, 0.4);
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}}

.btn-print:hover {{
    background: #0ea5e9;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(2, 132, 199, 0.6);
}}

@media print {{
    .print-toolbar {{ display: none !important; }}
    .propuesta-sgs {{ background: white; }}
    .mascot-sticker {{ 
        position: absolute;
        bottom: 2%;
        right: 0;
        width: 200px;
    }}
}}

/* Mascot and Sticker Styling */
.mascot-sticker {{
    position: absolute;
    bottom: -10%;
    right: 5%;
    width: 320px;
    z-index: 10;
    pointer-events: none;
    animation: fadeInUp 1.2s ease-out 0.6s both;
}}

.mascot-sticker img.tecnico {{
    width: 100%;
    height: auto;
    filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.3));
}}

.mascot-sticker img.cdd-logo {{
    position: absolute;
    width: 70px;
    top: 36%;
    left: 20%;
    transform: rotate(-12deg);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
}}

@media (max-width: 768px) {{
    .mascot-sticker {{ width: 200px; right: -5%; bottom: -5%; opacity: 0.8; }}
}}
</style>
"""
    # Write to target
    os.makedirs(os.path.dirname(vue_path), exist_ok=True)
    with open(vue_path, "w", encoding="utf-8") as out:
        out.write(vue_content)
    print("Converted HTML to Vue successfully.")
else:
    print("Regex match failed.")
