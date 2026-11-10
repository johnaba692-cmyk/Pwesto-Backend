<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0c0b0b">
    <meta name="description" content="LavaLust is a lightweight PHP MVC framework powering the Pwesto backend.">
    <title>LavaLust Framework · Backend</title>
    <style>
        :root{color-scheme:dark;font-family:Inter,"Segoe UI",sans-serif;background:#0c0b0b;color:#f5f3f2}
        *{box-sizing:border-box}
        body{min-height:100vh;margin:0;background-color:#0c0b0b;background-image:linear-gradient(#ffffff08 1px,transparent 1px),linear-gradient(90deg,#ffffff08 1px,transparent 1px);background-size:60px 60px}
        a{color:inherit;text-decoration:none}
        .topbar{height:72px;padding:0 28px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #ffffff12;background:#0c0b0bf2}
        .brand{display:flex;align-items:center;gap:10px;font-size:18px;font-weight:800;letter-spacing:-.7px}
        .brand-mark{display:grid;place-items:center;width:29px;height:29px;border-radius:8px;background:#e84c12;color:#fff;font-size:16px}
        .nav{display:flex;align-items:center;gap:26px;color:#969298;font-size:13px;font-weight:650}
        .nav a:hover,.secondary:hover{color:#fff}
        .nav .get-started,.primary{padding:10px 15px;border:1px solid #f15a24;border-radius:8px;background:#e94c13;color:#fff;font-weight:750}
        .hero{min-height:585px;display:flex;justify-content:center;align-items:center;padding:64px 20px 82px;text-align:center}
        .hero-content{max-width:760px}
        .version{display:inline-flex;align-items:center;gap:8px;padding:7px 13px;border:1px solid #773018;border-radius:999px;background:#e94c1316;color:#ff6b31;font:700 11px/1 ui-monospace,SFMono-Regular,Consolas,monospace;letter-spacing:.08em;text-transform:uppercase}
        .version:before{content:"";width:7px;height:7px;border-radius:50%;background:#e94c13}
        h1{margin:28px 0 17px;font-size:clamp(56px,11vw,112px);line-height:.91;letter-spacing:-.085em;font-weight:850}
        h1 .lava{color:#ed4c13}
        h1 .lust{color:transparent;-webkit-text-stroke:1.5px #69676a}
        h1 .framework{display:block;color:#f3f1f0;font-size:.81em;letter-spacing:-.078em}
        .intro{max-width:515px;margin:26px auto 0;color:#969298;font-size:clamp(15px,2.1vw,19px);line-height:1.65}
        .actions{display:flex;justify-content:center;gap:12px;flex-wrap:wrap;margin-top:34px}
        .primary,.secondary{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:0 18px;border-radius:8px;font-size:13px;font-weight:750}
        .primary{border:1px solid #f15a24}
        .secondary{border:1px solid #ffffff18;color:#a6a2a7}
        .features{max-width:1050px;margin:0 auto;padding:35px 24px 70px;border-top:1px solid #ffffff12}
        .features-heading{text-align:center}
        .features-heading span{color:#ed5825;font-size:11px;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
        .features-heading h2{margin:10px 0 23px;font-size:clamp(22px,4vw,32px);letter-spacing:-.045em}
        .feature-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:13px}
        .feature{min-height:120px;padding:19px;border:1px solid #ffffff14;border-radius:12px;background:#ffffff05}
        .feature strong{display:block;font-size:13px}
        .feature p{margin:8px 0 0;color:#969298;font-size:12px;line-height:1.65}
        footer{padding:18px 24px 28px;color:#747176;font-size:11px;text-align:center}
        @media(max-width:620px){.topbar{height:64px;padding:0 16px}.nav{gap:12px;font-size:11px}.nav .get-started{padding:8px 10px}.hero{min-height:510px;padding-top:48px}.feature-grid{grid-template-columns:1fr}.feature{min-height:0}}
        @media(prefers-reduced-motion:no-preference){.hero-content{animation:arrive .65s ease-out both}@keyframes arrive{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}}
    </style>
</head>
<body>
    <header class="topbar">
        <a class="brand" href="/" aria-label="LavaLust Framework home"><span class="brand-mark" aria-hidden="true">♥</span><span>LavaLust</span></a>
        <nav class="nav" aria-label="Framework links">
            <a href="https://lavalust.netlify.app/" target="_blank" rel="noopener noreferrer">Docs</a>
            <a href="https://github.com/ronmarasigan/LavaLust" target="_blank" rel="noopener noreferrer">GitHub</a>
            <a class="get-started" href="https://github.com/ronmarasigan/LavaLust" target="_blank" rel="noopener noreferrer">Get Started</a>
        </nav>
    </header>
    <main>
        <section class="hero">
            <div class="hero-content">
                <span class="version">PHP MVC · Pwesto backend</span>
                <h1><span class="lava">Lava</span><span class="lust">Lust</span><span class="framework">Framework</span></h1>
                <p class="intro">A lightweight PHP MVC framework powering the Pwesto backend, APIs, and marketplace services.</p>
                <div class="actions">
                    <a class="primary" href="https://lavalust.netlify.app/" target="_blank" rel="noopener noreferrer">Read the Docs</a>
                    <a class="secondary" href="https://github.com/ronmarasigan/LavaLust" target="_blank" rel="noopener noreferrer">View on GitHub</a>
                </div>
            </div>
        </section>
        <section class="features" aria-labelledby="features-heading">
            <div class="features-heading"><span>Backend foundation</span><h2>Structure for the Pwesto application</h2></div>
            <div class="feature-grid">
                <article class="feature"><strong>MVC architecture</strong><p>Controllers, views, and data services organize the PHP application.</p></article>
                <article class="feature"><strong>Application API</strong><p>Framework routes connect the Pwesto frontend to marketplace services.</p></article>
                <article class="feature"><strong>Framework core</strong><p>LavaLust provides routing and request handling for the backend.</p></article>
            </div>
        </section>
    </main>
    <footer>Pwesto Backend · LavaLust Framework · PHP <?php echo htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8'); ?></footer>
</body>
</html>
