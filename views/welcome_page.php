<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');
$appBasePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
if ($appBasePath === '.' || $appBasePath === '/') {
    $appBasePath = '';
}
$reactCssPath = ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'pwesto-react.css';
$reactJsPath = ROOT_DIR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'pwesto-react.js';
$reactCssVersion = is_file($reactCssPath) ? filemtime($reactCssPath) : 1;
$reactJsVersion = is_file($reactJsPath) ? filemtime($reactJsPath) : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f6f7f2">
    <meta name="description" content="Ang Pwesto ay online na gabay para matuklasan ang mga lokal na puwesto sa palengke, makakonekta sa mga vendor, at mapalago ang komunidad.">
    <title>Pwesto — Your neighborhood, in the market</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($appBasePath . '/assets/pwesto-react.css?v=' . $reactCssVersion, ENT_QUOTES, 'UTF-8'); ?>">
    <style nonce="<?php echo defined('CSP_NONCE') ? CSP_NONCE : ''; ?>">
        :root{--ink:#202b25;--muted:#768078;--green:#315b43;--green-dark:#234733;--lime:#dcebbd;--paper:#f7f8f5;--white:#fff;--line:#e7ebe4;--orange:#ef8f55;--shadow:0 20px 55px rgba(31,48,36,.08);--sans:"DM Sans",sans-serif;--display:"Manrope",sans-serif}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;background:var(--paper);color:var(--ink);font-family:var(--sans);font-size:15px;-webkit-font-smoothing:antialiased}
        button,input,select{font:inherit}
        button,a{-webkit-tap-highlight-color:transparent}
        button{cursor:pointer}
        a{color:inherit;text-decoration:none}
        .shell{width:min(1180px,calc(100% - 56px));margin:auto}
        .topbar{height:82px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(32,43,37,.08)}
        .brand{display:inline-flex;align-items:center;gap:10px;font:800 23px var(--display);letter-spacing:-1.3px}
        .brand-mark{width:37px;height:37px;border-radius:13px;background:var(--green);display:grid;place-items:center;color:#fff}
        .brand-mark svg{width:23px}
        .nav-links{display:flex;align-items:center;gap:34px;color:#68736a;font-weight:600;font-size:13px}
        .nav-links a:hover{color:var(--green)}
        .nav-actions{display:flex;align-items:center;gap:12px}
        .button{border:0;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:13px 19px;font-weight:700;font-size:13px;transition:transform .2s,background .2s,box-shadow .2s}
        .button:hover{transform:translateY(-2px)}
        .button-primary{background:var(--green);color:#fff;box-shadow:0 8px 18px rgba(49,91,67,.16)}
        .button-primary:hover{background:var(--green-dark)}
        .button-light{background:#fff;color:var(--green);border:1px solid var(--line)}
        .button-small{padding:10px 14px;font-size:12px}
        .text-button{border:0;background:none;padding:8px;color:var(--muted);font-weight:700;font-size:13px}
        .hero{min-height:565px;display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:65px;padding:62px 0 76px}
        .eyebrow{display:inline-flex;align-items:center;gap:8px;color:var(--green);font-size:11px;font-weight:800;letter-spacing:1.2px;text-transform:uppercase}
        .eyebrow-dot{width:7px;height:7px;border-radius:50%;background:#8caf5d;box-shadow:0 0 0 4px #eaf1df}
        h1,h2,h3,p{margin-top:0}
        .hero h1{font:800 clamp(43px,5.2vw,66px)/1.08 var(--display);letter-spacing:-3.6px;margin:18px 0 18px;max-width:570px}
        .hero h1 em{font-style:normal;color:var(--green)}
        .hero-copy{max-width:480px;font-size:16px;line-height:1.8;color:#758078;margin-bottom:27px}
        .hero-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
        .hero-note{display:flex;align-items:center;gap:10px;margin-top:27px;color:#7d867e;font-size:12px}
        .avatar-stack{display:flex;padding-left:5px}
        .avatar-stack img,.avatar-initial{width:29px;height:29px;object-fit:cover;border:2px solid var(--paper);border-radius:50%;margin-left:-5px;background:#e9eddf}
        .avatar-stack img:nth-child(2){background:#f2dfd2}.avatar-stack img:nth-child(3){background:#dce7ef}
        .hero-visual{height:414px;position:relative;border-radius:26px;background:#e5eddd;overflow:hidden}
        .hero-photo{position:absolute;inset:0;background:linear-gradient(180deg,rgba(27,46,31,.02),rgba(27,46,31,.25)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1100&q=85") center/cover}
        .float-card{position:absolute;background:#fff;border-radius:14px;box-shadow:var(--shadow);padding:14px 16px}
        .float-market{top:24px;left:22px;display:flex;align-items:center;gap:10px}
        .float-icon{width:35px;height:35px;border-radius:11px;background:#edf3e7;color:var(--green);display:grid;place-items:center;font-size:17px}
        .float-market strong,.float-market small{display:block}
        .float-market strong{font-size:12px}.float-market small{color:var(--muted);font-size:10px;margin-top:3px}
        .float-open{right:18px;top:25px;border-radius:30px;color:var(--green);font-weight:800;font-size:10px}
        .float-bottom{left:20px;bottom:19px;right:20px;display:flex;align-items:center;justify-content:space-between}
        .float-bottom strong{display:block;font:700 15px var(--display)}
        .float-bottom small{display:block;color:var(--muted);font-size:11px;margin-top:4px}
        .float-rating{color:#344c36;font-size:12px;font-weight:800}
        .section{padding:74px 0}
        .section-heading{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:30px}
        .section-heading h2{font:750 31px/1.2 var(--display);letter-spacing:-1.3px;margin:9px 0 0}
        .section-heading p{color:var(--muted);margin:8px 0 0;font-size:13px}
        .how-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:17px}
        .how-card{background:#fff;border:1px solid var(--line);border-radius:17px;padding:24px;min-height:174px}
        .how-icon{width:43px;height:43px;display:grid;place-items:center;border-radius:13px;background:#eef3e8;font-size:20px;margin-bottom:18px}
        .how-card h3{font:700 15px var(--display);margin-bottom:7px}
        .how-card p{color:var(--muted);font-size:12px;line-height:1.65;margin:0}
        .market-band{background:#eef2e9}
        .trust-row{display:flex;justify-content:space-between;gap:30px;align-items:center;padding:27px 0;color:#637064;font-size:12px}
        .trust-row strong{color:var(--green);font:800 14px var(--display)}
        .footer{border-top:1px solid var(--line);padding:25px 0;color:#818a82;font-size:11px}
        .footer-inner{display:flex;justify-content:space-between;gap:15px}

        .topbar{position:sticky;top:0;z-index:900;background:rgba(247,248,245,.9);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);transition:box-shadow .2s}
        .topbar.is-scrolled{box-shadow:0 8px 28px rgba(32,43,37,.07)}
        .brand-mark{box-shadow:0 5px 13px rgba(49,91,67,.2)}
        .nav-links a{position:relative;padding:10px 0}
        .nav-links a:after{content:"";position:absolute;left:0;right:100%;bottom:4px;height:2px;border-radius:2px;background:#8caf5d;transition:right .2s}
        .nav-links a:hover:after{right:0}
        .hero{position:relative;isolation:isolate;min-height:640px;grid-template-columns:minmax(0,1.02fr) minmax(390px,.98fr);gap:58px;padding:68px 0 58px}
        .hero:before{content:"";position:absolute;z-index:-1;inset:-1px 0 0;background:radial-gradient(ellipse at 18% 24%,rgba(221,235,202,.48),transparent 40%),radial-gradient(ellipse at 78% 12%,rgba(237,225,205,.36),transparent 34%);pointer-events:none}
        .hero-copy-column{max-width:570px;animation:hero-rise .7s both}
        .hero .eyebrow{gap:10px}
        .eyebrow-dot{animation:dot-pulse 2.6s ease-in-out infinite}
        .hero h1{font-size:clamp(48px,5.5vw,72px);line-height:1.02;letter-spacing:-4.6px;margin:20px 0 19px}
        .hero h1 em{background:linear-gradient(110deg,#315b43 15%,#75924e 92%);background-clip:text;-webkit-background-clip:text;color:transparent}
        .hero-copy{max-width:470px;font-size:15px;line-height:1.8;color:#66736a;margin-bottom:23px}
        .hero-search{display:flex;align-items:center;gap:10px;max-width:490px;min-height:60px;padding:6px 7px 6px 16px;border:1px solid rgba(49,91,67,.12);border-radius:15px;background:#fff;box-shadow:0 10px 30px rgba(32,43,37,.07),0 1px 2px rgba(32,43,37,.03);transition:border-color .2s,box-shadow .2s,transform .2s}
        .hero-search:focus-within{transform:translateY(-2px);border-color:#9eb595;box-shadow:0 13px 34px rgba(49,91,67,.13)}
        .hero-search-icon{width:22px;height:22px;flex:0 0 22px;color:#78877c}
        .hero-search input{flex:1;min-width:0;height:42px;border:0;outline:0;background:transparent;color:var(--ink);font:500 13px var(--sans)}
        .hero-search input::placeholder{color:#9aa39b}
        .hero-search .button{min-height:46px;border-radius:10px;padding:0 16px}
        .hero-search-chips{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:14px;color:#89928a;font-size:10px}
        .hero-search-chips strong{margin-right:2px;font-size:9px;letter-spacing:.8px}
        .hero-chip{border:1px solid #e8ece5;border-radius:99px;padding:7px 11px;background:rgba(255,255,255,.72);color:#56655a;font-size:10px;font-weight:700;transition:background .18s,color .18s,border-color .18s,transform .18s}
        .hero-chip:hover{transform:translateY(-2px);border-color:#cad8c0;background:#eff4e9;color:var(--green)}
        .hero-actions{margin-top:20px}
        .hero-actions .button{min-height:43px}
        .hero-note{gap:11px;margin-top:22px;font-size:11px}
        .hero-note-copy strong{display:block;color:#46564a;font-size:11px}
        .hero-note-copy small{display:block;margin-top:3px;color:#89928a;font-size:10px}
        .hero-visual{height:460px;border-radius:25px;background:#dbe5d5;box-shadow:0 30px 70px rgba(36,55,42,.16);animation:hero-rise .8s .1s both;transform:translateZ(0)}
        button.hero-visual{display:block;width:100%;padding:0;border:0;color:inherit;text-align:left}
        .hero-visual:focus-visible{outline:3px solid #94ad8b;outline-offset:4px}
        .hero-photo{background-image:linear-gradient(180deg,rgba(20,36,25,.02) 35%,rgba(20,36,25,.43) 100%),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1300&q=88");transition:transform .8s cubic-bezier(.2,.7,.2,1);will-change:transform}
        .hero-visual:hover .hero-photo{transform:scale(1.035)}
        .hero-visual:after{content:"";position:absolute;inset:0;z-index:1;pointer-events:none;background:radial-gradient(300px circle at var(--pointer-x,50%) var(--pointer-y,45%),rgba(255,255,255,.18),transparent 72%)}
        .hero-visual-top{position:absolute;z-index:2;top:21px;left:21px;right:21px;display:flex;align-items:center;justify-content:space-between;gap:10px}
        .hero-live-pill,.hero-area-pill{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(255,255,255,.48);border-radius:99px;background:rgba(255,255,255,.91);padding:9px 12px;color:#344438;box-shadow:0 4px 16px rgba(0,0,0,.08);font-size:10px;font-weight:800;backdrop-filter:blur(12px)}
        .hero-live-pill i{width:7px;height:7px;border-radius:50%;background:#7cad59;box-shadow:0 0 0 4px #e8f0df}
        .hero-area-pill{color:#59685d}
        .hero-market-card{position:absolute;z-index:2;right:20px;bottom:20px;left:20px;display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 19px;border:1px solid rgba(255,255,255,.65);border-radius:17px;background:rgba(255,255,255,.94);box-shadow:0 12px 36px rgba(23,39,27,.19);backdrop-filter:blur(16px);transition:transform .25s,box-shadow .25s}
        .hero-market-card:hover{transform:translateY(-3px);box-shadow:0 17px 40px rgba(23,39,27,.23)}
        .hero-market-copy{min-width:0}
        .hero-market-kicker{display:block;margin-bottom:5px;color:#718076;font-size:9px;font-weight:800;letter-spacing:1px;text-transform:uppercase}
        .hero-market-card strong{display:block;font:800 16px var(--display);letter-spacing:-.45px}
        .hero-market-card small{display:block;margin-top:5px;color:#7a857c;font-size:10px;line-height:1.5}
        .hero-market-cta{display:grid;place-items:center;width:42px;height:42px;flex:0 0 42px;border-radius:13px;background:var(--green);color:#fff;font-size:19px;transition:transform .2s,background .2s}
        .hero-market-card:hover .hero-market-cta{transform:translateX(3px);background:var(--green-dark)}
        .hero-photo-caption{position:absolute;z-index:2;left:23px;bottom:117px;color:#fff;text-shadow:0 2px 12px rgba(0,0,0,.25);font-size:10px;font-weight:700;letter-spacing:.3px}
        .hero-bottom-row{display:grid;grid-template-columns:repeat(3,1fr);gap:0;margin-top:34px;padding-top:20px;border-top:1px solid rgba(49,91,67,.12)}
        .hero-stat{padding:0 15px;border-right:1px solid rgba(49,91,67,.12)}
        .hero-stat:first-child{padding-left:0}
        .hero-stat:last-child{border-right:0}
        .hero-stat strong{display:block;color:#304c38;font:800 19px var(--display);letter-spacing:-.6px}
        .hero-stat span{display:block;margin-top:4px;color:#879188;font-size:9px}
        .section{padding:86px 0}
        .section-heading{margin-bottom:34px}
        .section-heading h2{font-size:34px;letter-spacing:-1.55px}
        .how-card{position:relative;overflow:hidden;min-height:205px;padding:25px;border-radius:18px;transition:transform .24s,border-color .24s,box-shadow .24s}
        .how-card:hover{transform:translateY(-5px);border-color:#d5e0cd;box-shadow:0 17px 36px rgba(32,43,37,.07)}
        .how-card:after{content:attr(data-step);position:absolute;top:17px;right:20px;color:#edf1e9;font:800 37px var(--display);letter-spacing:-2px}
        .how-icon{position:relative;z-index:1;width:45px;height:45px;border-radius:14px;color:var(--green);background:#eef3e8}
        .how-card h3{font-size:16px}
        .how-card p{font-size:12px}
        .market-band{position:relative;overflow:hidden;background:linear-gradient(112deg,#294b36,#1e3d2c);color:#fff}
        .market-band:before{content:"";position:absolute;width:480px;height:480px;right:-120px;top:-265px;border:1px solid #ffffff1f;border-radius:50%;box-shadow:0 0 0 34px #ffffff08,0 0 0 70px #ffffff06;pointer-events:none}
        .trust-row{position:relative;z-index:1;display:grid;grid-template-columns:minmax(220px,1.4fr) repeat(3,minmax(100px,.65fr)) auto;gap:20px;padding:34px 0;color:#d3dfd5}
        .trust-row strong{color:#fff;font-size:13px}
        .trust-stat{display:flex;align-items:center;gap:9px}
        .trust-stat strong{font:800 21px var(--display);letter-spacing:-.5px;color:#e1edc6}
        .trust-stat span{font-size:9px;line-height:1.4;color:#cad8cd}
        .trust-row .button{align-self:center;white-space:nowrap;background:#fff;color:var(--green)}
        [data-reveal]{transition:opacity .65s ease,transform .65s cubic-bezier(.2,.7,.2,1)}
        .js [data-reveal]:not(.is-visible){opacity:0;transform:translateY(18px)}
        @keyframes hero-rise{from{opacity:0;transform:translateY(15px)}to{opacity:1;transform:translateY(0)}}
        @keyframes dot-pulse{50%{box-shadow:0 0 0 6px rgba(140,175,93,.12)}}

        /* Discovery dashboard */
        #dashboard{display:none;padding:35px 0 72px}
        .dashboard-profile-bar{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);align-items:center;gap:24px;margin-bottom:24px;padding:22px 24px;border:1px solid var(--line);border-radius:16px;background:#fff}
        .dashboard-profile-identity{display:flex;align-items:center;gap:16px;min-width:0}
        .profile-wrap{position:relative}
        .profile-button{display:flex;align-items:center;gap:10px;border:0;background:transparent;padding:0;text-align:left}
        .profile-button img,.profile-avatar{width:88px;height:88px;border-radius:20px;object-fit:cover;background:#e7eddf;display:grid;place-items:center;font-size:30px;font-weight:800;color:var(--green)}
        .profile-button .profile-details{display:grid;gap:3px;min-width:0}
        .profile-button .profile-name{overflow-wrap:anywhere;color:var(--ink);font:750 20px/1.3 var(--display)}
        .profile-button .profile-email{overflow-wrap:anywhere;color:var(--muted);font-size:12px;font-weight:400}
        .profile-button .profile-chevron{color:var(--muted);font-size:12px}
        .profile-menu{display:none;position:absolute;z-index:800;left:0;top:104px;width:190px;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:var(--shadow);padding:8px}
        .profile-menu.open{display:block}
        .profile-menu button{width:100%;text-align:left;border:0;background:transparent;padding:10px;border-radius:8px;font-size:12px;color:var(--ink)}
        .profile-menu button:hover{background:var(--paper)}
        .dashboard-profile-copy{display:grid;gap:8px;min-width:0}
        .dashboard-profile-copy .eyebrow{margin:0}
        .dashboard-profile-form{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.15fr) auto;align-items:end;gap:10px;min-width:0}
        .dashboard-profile-form label{display:block;margin-bottom:5px;color:var(--ink);font-size:10px;font-weight:750}
        .dashboard-profile-form input[type="text"],.dashboard-profile-form input[type="file"]{width:100%;min-width:0;min-height:39px;border:1px solid var(--line);border-radius:8px;background:#fff;padding:8px;color:#20271f;color-scheme:light;font:11px var(--sans)}
        .dashboard-profile-form input[type="text"]:focus,.dashboard-profile-form input[type="file"]:focus{outline:2px solid #94ad8b;outline-offset:1px}
        .dashboard-profile-form .button{min-height:39px;white-space:nowrap}
        .dashboard-profile-feedback{grid-column:1/-1;min-height:14px;margin:0;color:var(--muted);font-size:10px}
        .dashboard-profile-actions{grid-column:1/-1;display:flex;align-items:center;justify-content:flex-end;gap:9px;flex-wrap:wrap}
        .notification-wrap{position:relative}
        .notification-button{position:relative;display:inline-flex;align-items:center;gap:7px;min-height:36px;border:1px solid var(--line);border-radius:9px;background:#fff;padding:0 11px;color:var(--ink);font:700 11px var(--sans)}
        .notification-button:hover{background:var(--paper)}
        .notification-button svg{width:15px;height:15px}
        .notification-badge{display:none;min-width:17px;height:17px;align-items:center;justify-content:center;border-radius:999px;background:#a44735;color:#fff;padding:0 4px;font-size:9px}
        .notification-badge.visible{display:inline-flex}
        .notification-panel{position:absolute;z-index:1200;top:calc(100% + 10px);right:0;width:min(360px,calc(100vw - 44px));max-height:min(440px,calc(100vh - 120px));overflow:hidden;border:1px solid var(--line);border-radius:12px;background:#fff;box-shadow:0 18px 50px rgba(20,24,32,.2)}
        .notification-panel[hidden]{display:none}
        .notification-heading{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 15px;border-bottom:1px solid var(--line)}
        .notification-heading strong{font:750 13px var(--display)}
        .notification-heading button{border:0;background:transparent;color:var(--green);font:700 10px var(--sans)}
        .notification-list{max-height:370px;overflow:auto}
        .notification-item{display:grid;width:100%;gap:5px;padding:12px 15px;border:0;border-bottom:1px solid #eff1ec;background:#fff;text-align:left;color:var(--ink)}
        .notification-item:hover{background:#f7f8f4}
        .notification-item.unread{background:#f3f6ef}
        .notification-item strong{font-size:11px}
        .notification-item span{color:#59635b;font-size:10px;line-height:1.5}
        .notification-item time{color:#8b938b;font-size:9px}
        .notification-empty{padding:22px 15px;color:var(--muted);font-size:11px;text-align:center}
        .dashboard-head{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:25px}
        .dashboard-head h1{font:800 31px var(--display);letter-spacing:-1.4px;margin:7px 0}
        .dashboard-head p{color:var(--muted);font-size:13px;margin:0}
        .dashboard-tabs{display:flex;gap:5px;border-bottom:1px solid var(--line);margin-bottom:22px}
        .tab{padding:11px 16px;background:none;border:0;color:#879087;font-size:12px;font-weight:700;border-bottom:2px solid transparent}
        .tab.active{color:var(--green);border-color:var(--green)}
        .discover-toolbar{display:flex;gap:10px;align-items:center;margin-bottom:18px;flex-wrap:wrap}
        .search-wrap{flex:1;min-width:230px;position:relative}
        .search-wrap input{width:100%;height:43px;border:1px solid var(--line);background:#fff;border-radius:11px;padding:0 15px 0 40px;outline:none;font-size:12px}
        .search-wrap input:focus,.select-filter:focus{border-color:#94ad8b}
        .search-symbol{position:absolute;left:14px;top:12px;color:#8a958b}
        .select-filter{height:43px;border:1px solid var(--line);background:#fff;border-radius:10px;padding:0 13px;color:#536055;font-size:12px;outline:none}
        .nearby-button{height:43px}
        .content-grid{display:grid;grid-template-columns:minmax(320px,.9fr) minmax(440px,1.1fr);gap:18px;align-items:stretch}
        .vendor-panel,.map-panel{background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}
        .panel-header{display:flex;justify-content:space-between;align-items:center;padding:16px 18px;border-bottom:1px solid #eff1ec}
        .panel-header strong{font:750 13px var(--display)}
        .panel-header span{font-size:11px;color:var(--muted)}
        .vendor-list{padding:4px 12px;max-height:480px;overflow:auto}
        .vendor-card{display:grid;grid-template-columns:72px minmax(0,1fr) auto;gap:12px;align-items:center;padding:13px 6px;border-bottom:1px solid #eff1ec;cursor:pointer}
        .vendor-card:last-child{border-bottom:0}
        .vendor-card:hover,.vendor-card.selected{background:#f8faf6}
        .vendor-image{width:72px;height:72px;border-radius:12px;object-fit:cover;background:#edf1e8}
        .vendor-main{min-width:0}
        .vendor-main h3{font:700 13px var(--display);margin:0 0 4px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}
        .vendor-category,.vendor-meta{font-size:10px;color:var(--muted)}
        .vendor-meta{display:flex;gap:10px;margin-top:7px}
        .vendor-main .stars{color:#b47732;font-weight:800}
        .vendor-action{border:1px solid var(--line);background:white;color:var(--green);border-radius:9px;font-weight:700;font-size:10px;padding:8px}
        .vendor-action:hover{background:#edf3e8}
        .vendor-card-actions{display:flex;flex-direction:column;gap:6px}
        .vendor-message-action{border:0;background:#edf3e8;color:var(--green);border-radius:9px;font-weight:800;font-size:10px;padding:8px}
        .vendor-message-action:hover{background:#e2ead9}
        #vendorMap{height:480px;width:100%;z-index:1}
        .map-panel-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;width:100%}
        .map-panel-title{display:grid;gap:4px}
        .map-note{display:flex;gap:6px;align-items:center;font-size:10px;color:#818a82}
        .map-actions{display:flex;gap:7px;flex-shrink:0}
        .map-action{display:inline-flex;align-items:center;justify-content:center;min-height:32px;padding:0 10px;border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--green);font-size:10px;font-weight:800;text-decoration:none}
        .map-action-primary{border-color:var(--green);background:var(--green);color:#fff}
        .pin{width:33px;height:33px;background:var(--green);border:3px solid #fff;border-radius:50% 50% 50% 5px;transform:rotate(-45deg);box-shadow:0 3px 9px #263a2b55;display:grid;place-items:center}
        .pin span{transform:rotate(45deg);font-size:14px}
        .market-pin{width:36px;height:36px;border:3px solid #fff;border-radius:50% 50% 50% 5px;background:#d9342b;box-shadow:0 3px 9px #263a2b55;transform:rotate(-45deg);display:grid;place-items:center}
        .market-pin span{color:#fff;font-size:16px;font-weight:800;transform:rotate(45deg)}
        .market-popup-actions{display:flex;gap:7px;margin-top:8px}
        .market-popup-actions a{display:inline-block;padding:7px 9px;border-radius:7px;background:var(--green);color:#fff!important;font-size:10px;font-weight:800;text-decoration:none}
        .market-popup-actions a+ a{background:#edf3e8;color:var(--green)!important}
        .booking-section{margin-top:20px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:19px}
        .booking-section h2{font:750 15px var(--display);margin:0}
        .booking-empty{padding:17px 0 3px;color:var(--muted);font-size:12px}
        .booking-row{display:flex;justify-content:space-between;align-items:center;border-top:1px solid #eff1ec;padding:12px 0;font-size:12px}
        .booking-row strong{display:block;margin-bottom:4px}
        .status-pill{border-radius:20px;padding:5px 9px;background:#edf3e8;color:var(--green);font-size:10px;font-weight:800}
        .message-view{display:none}
        .message-inbox{max-width:860px;background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}
        .message-inbox-heading{padding:20px 21px;border-bottom:1px solid var(--line)}
        .message-inbox-heading h2{font:750 16px var(--display);margin:0 0 5px}
        .message-inbox-heading p{font-size:11px;line-height:1.6;color:var(--muted);margin:0}
        .message-contacts{margin-top:16px}
        .message-contact-action{border:1px solid var(--line);border-radius:20px;background:#fff;color:var(--green);padding:7px 11px;font-size:10px;font-weight:750;white-space:nowrap}
        .message-contact-action:not(:disabled):hover{background:#edf3e8}
        .message-contact-action:disabled{color:#929a92;background:#f7f8f5;cursor:not-allowed}
        .conversation-row{display:flex;width:100%;align-items:center;gap:12px;padding:15px 19px;text-align:left;border:0;border-bottom:1px solid #eff1ec;background:#fff}
        .conversation-row:last-child{border-bottom:0}
        .conversation-row:hover{background:#f8faf6}
        .conversation-avatar{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:13px;background:#edf3e8;color:var(--green);font:800 14px var(--display)}
        .conversation-copy{flex:1;min-width:0}
        .conversation-copy strong,.conversation-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .conversation-copy strong{font:750 12px var(--display);margin-bottom:4px}
        .conversation-copy small{font-size:10px;color:var(--muted)}
        .conversation-meta{display:flex;flex-direction:column;align-items:flex-end;gap:7px;flex-shrink:0}
        .conversation-meta time{font-size:9px;color:#929a92}
        .unread-badge{display:grid;place-items:center;min-width:19px;height:19px;padding:0 5px;border-radius:20px;background:var(--green);color:#fff;font-size:9px;font-weight:800}
        .message-empty{padding:32px 20px;text-align:center;color:var(--muted);font-size:11px;line-height:1.7}
        .message-empty strong{display:block;margin-bottom:5px;color:var(--ink);font:750 13px var(--display)}
        .vendor-message-card{margin-top:16px}
        .chat-modal{width:min(520px,100%);padding:0;overflow:hidden;display:flex;flex-direction:column;max-height:min(720px,calc(100dvh - 32px))}
        .chat-header{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:17px 20px;border-bottom:1px solid var(--line)}
        .chat-heading strong,.chat-heading small{display:block}
        .chat-heading strong{font:750 13px var(--display);margin-bottom:4px}
        .chat-heading small{font-size:10px;color:var(--muted)}
        .chat-close{border:0;background:transparent;color:var(--muted);font-size:22px}
        .chat-messages{display:flex;flex:1;min-height:220px;max-height:450px;flex-direction:column;gap:10px;overflow:auto;padding:18px;background:#f7f8f5}
        .chat-message{max-width:82%;align-self:flex-start;padding:10px 12px;background:#fff;border:1px solid var(--line);border-radius:4px 14px 14px 14px;box-shadow:0 3px 10px rgba(31,48,36,.03)}
        .chat-message.is-own{align-self:flex-end;background:var(--green);border-color:var(--green);color:#fff;border-radius:14px 4px 14px 14px}
        .chat-message p{font-size:12px;line-height:1.6;margin:0;white-space:pre-wrap;overflow-wrap:anywhere}
        .chat-message time{display:block;margin-top:5px;font-size:9px;opacity:.7}
        .chat-start-note{padding:16px 20px;background:#f7f8f5;color:var(--muted);font-size:10px;line-height:1.6}
        .chat-suggestions{display:flex;flex-wrap:wrap;gap:7px;padding:10px 15px 0;background:#f7f8f5}
        .chat-suggestions button{border:1px solid var(--line);border-radius:20px;background:#fff;color:var(--green);padding:7px 10px;font-size:10px;font-weight:700}
        .chat-suggestions button:hover{background:#edf3e8}
        .chat-form{display:flex;align-items:flex-end;gap:9px;padding:13px 15px;background:#fff;border-top:1px solid var(--line)}
        .chat-form textarea{width:auto;min-width:0;flex:1 1 auto;min-height:42px;max-height:110px;resize:vertical;border:1px solid var(--line);border-radius:11px;padding:10px 12px;font:12px/1.5 var(--sans);outline:none}
        .chat-form textarea:focus{border-color:#94ad8b}
        .chat-modal .chat-form .button{width:auto;flex:0 0 auto;min-height:42px;margin:0;white-space:nowrap}
        .customer-overview{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(270px,.8fr);gap:16px;margin:0 0 22px}
        .customer-welcome{position:relative;overflow:hidden;display:flex;align-items:center;justify-content:space-between;gap:20px;min-height:148px;padding:25px 28px;background:linear-gradient(118deg,#315b43,#234733);border-radius:18px;color:#fff}
        .customer-welcome:after{content:"";position:absolute;width:230px;height:230px;right:13%;top:-125px;border:1px solid #ffffff20;border-radius:50%;box-shadow:0 0 0 24px #ffffff09,0 0 0 48px #ffffff07;pointer-events:none}
        .customer-welcome-copy{position:relative;z-index:1;max-width:470px}
        .customer-welcome .eyebrow{color:#dcebbd}
        .customer-welcome h2{font:800 23px/1.2 var(--display);letter-spacing:-.8px;margin:11px 0 6px}
        .customer-welcome p{margin:0;color:#e0e9e1;font-size:12px;line-height:1.7}
        .customer-welcome .button{position:relative;z-index:1;flex-shrink:0;background:#fff;color:var(--green)}
        .customer-highlights{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .customer-highlight{display:flex;flex-direction:column;justify-content:center;padding:17px;background:#fff;border:1px solid var(--line);border-radius:15px}
        .customer-highlight span{font-size:11px;color:var(--muted)}
        .customer-highlight strong{font:800 23px var(--display);margin-top:7px;letter-spacing:-.6px}
        .customer-highlight small{font-size:10px;color:#909990;margin-top:4px}
        .recommendation-panel{display:none;margin:0 0 20px;padding:18px;background:#fff;border:1px solid var(--line);border-radius:16px}
        .recommendation-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:15px;margin-bottom:14px}
        .recommendation-heading h2{margin:0 0 5px;font:750 15px var(--display)}
        .recommendation-heading p{margin:0;color:var(--muted);font-size:10px;line-height:1.5}
        .recommendation-badge{flex:0 0 auto;padding:6px 9px;border-radius:999px;background:#eef3e9;color:#315b43;font-size:9px;font-weight:750}
        .recommendation-list{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
        .recommendation-card{display:grid;grid-template-columns:56px minmax(0,1fr);gap:10px;align-items:center;width:100%;min-width:0;padding:9px;border:1px solid #edf1ec;border-radius:12px;background:#fff;color:inherit;text-align:left;cursor:pointer}
        .recommendation-card:hover{border-color:#b9c8b4;background:#f8faf6}
        .recommendation-card img{width:56px;height:56px;border-radius:9px;object-fit:cover;background:#edf1e8}
        .recommendation-copy{min-width:0}
        .recommendation-copy strong,.recommendation-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .recommendation-copy strong{font:700 11px var(--display)}
        .recommendation-copy small{margin-top:5px;color:var(--muted);font-size:9px}
        .recommendation-copy .recommendation-reason{color:#527052}
        body.dashboard-mode #dashboard .recommendation-panel{border-color:#343a31;background:#171c17}
        body.dashboard-mode #dashboard .recommendation-heading h2{color:#f2f0e7}
        body.dashboard-mode #dashboard .recommendation-heading p,body.dashboard-mode #dashboard .recommendation-copy small{color:#a2a496}
        body.dashboard-mode #dashboard .recommendation-badge{background:#252c23;color:#e2e0c5}
        body.dashboard-mode #dashboard .recommendation-card{border-color:#343a31;background:#20251f;color:#f2f0e7}
        body.dashboard-mode #dashboard .recommendation-card:hover{border-color:#5b6752;background:#252c23}
        body.dashboard-mode #dashboard .recommendation-copy .recommendation-reason{color:#c3c3b9}
        .market-assistant{position:fixed;z-index:1400;right:24px;bottom:24px;display:grid;justify-items:end;gap:12px}
        .assistant-launcher{display:inline-flex;align-items:center;gap:10px;min-height:52px;padding:0 18px;border:0;border-radius:999px;background:#315b43;color:#fff;box-shadow:0 12px 30px rgba(27,54,36,.25);font:700 12px var(--sans);transition:transform .18s,background .18s,box-shadow .18s}
        .assistant-launcher:hover{transform:translateY(-2px);background:#234733;box-shadow:0 16px 34px rgba(27,54,36,.3)}
        .assistant-launcher svg{width:19px;height:19px}
        .assistant-panel{display:flex;width:min(390px,calc(100vw - 32px));height:min(540px,calc(100dvh - 112px));min-height:340px;flex-direction:column;overflow:hidden;border:1px solid #dfe6de;border-radius:18px;background:#fff;box-shadow:0 22px 65px rgba(20,35,25,.22);color:#202b25}
        .assistant-panel[hidden]{display:none}
        .assistant-header{display:flex;align-items:center;gap:11px;padding:15px 16px;border-bottom:1px solid #e8ede7;background:#fff}
        .assistant-avatar{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border-radius:12px;background:#e9f0e5;color:#315b43}
        .assistant-avatar svg{width:20px;height:20px}
        .assistant-heading{flex:1;min-width:0}
        .assistant-heading strong,.assistant-heading small{display:block}
        .assistant-heading strong{font:750 13px var(--display)}
        .assistant-heading small{margin-top:3px;color:#758078;font-size:10px}
        .assistant-online{display:inline-block;width:7px;height:7px;margin-right:5px;border-radius:50%;background:#76a76a}
        .assistant-close{display:grid;place-items:center;width:34px;height:34px;border:0;border-radius:9px;background:transparent;color:#758078;font-size:22px}
        .assistant-close:hover{background:#f2f5f0;color:#202b25}
        .assistant-disclaimer{margin:0;padding:9px 16px;border-bottom:1px solid #edf0ec;background:#f8faf7;color:#69756b;font-size:9px;line-height:1.5}
        .assistant-messages{display:flex;flex:1;flex-direction:column;gap:12px;overflow:auto;padding:16px;background:#f7f9f6;scrollbar-width:thin}
        .assistant-message{max-width:90%;padding:11px 13px;border:1px solid #e2e8e1;border-radius:4px 14px 14px 14px;background:#fff;color:#344138;font-size:11px;line-height:1.6;overflow-wrap:anywhere;white-space:pre-line}
        .assistant-message.is-user{align-self:flex-end;border-color:#315b43;border-radius:14px 4px 14px 14px;background:#315b43;color:#fff}
        .assistant-message.is-assistant{align-self:flex-start}
        .assistant-recommendations{display:grid;gap:7px;width:100%;max-width:90%;align-self:flex-start}
        .assistant-recommendation{display:grid;grid-template-columns:42px minmax(0,1fr) auto;align-items:center;gap:9px;width:100%;padding:8px;border:1px solid #e2e8e1;border-radius:11px;background:#fff;color:#202b25;text-align:left;cursor:pointer;transition:border-color .18s,transform .18s}
        .assistant-recommendation:hover{transform:translateY(-1px);border-color:#a9bea4}
        .assistant-recommendation img{width:42px;height:42px;border-radius:8px;object-fit:cover;background:#edf1e8}
        .assistant-recommendation-copy{min-width:0}
        .assistant-recommendation-copy strong,.assistant-recommendation-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .assistant-recommendation-copy strong{font:700 10px var(--display)}
        .assistant-recommendation-copy small{margin-top:4px;color:#758078;font-size:9px}
        .assistant-recommendation-arrow{color:#315b43;font-size:15px}
        .assistant-actions{display:flex;flex-wrap:wrap;gap:7px;width:100%;max-width:90%;align-self:flex-start}
        .assistant-action{padding:7px 10px;border:1px solid #dce5d9;border-radius:999px;background:#fff;color:#315b43;font:700 9px var(--sans);cursor:pointer}
        .assistant-action:hover{background:#eef4eb}
        .assistant-suggestions{display:flex;gap:7px;overflow-x:auto;padding:10px 13px;border-top:1px solid #e8ede7;background:#fff;scrollbar-width:none}
        .assistant-suggestions::-webkit-scrollbar{display:none}
        .assistant-suggestion{flex:0 0 auto;padding:7px 10px;border:1px solid #dfe6de;border-radius:999px;background:#fff;color:#526752;font:600 9px var(--sans);cursor:pointer}
        .assistant-suggestion:hover{border-color:#b9cbb4;background:#f7faf5}
        .assistant-form{display:flex;align-items:center;gap:8px;padding:11px 12px;border-top:1px solid #e8ede7;background:#fff}
        .assistant-input{flex:1;min-width:0;height:40px;padding:0 12px;border:1px solid #dfe6de;border-radius:10px;background:#fff;color:#202b25;font:11px var(--sans);outline:none}
        .assistant-input:focus{border-color:#8caf5d;box-shadow:0 0 0 3px rgba(140,175,93,.13)}
        .assistant-send{display:grid;place-items:center;flex:0 0 40px;width:40px;height:40px;border:0;border-radius:10px;background:#315b43;color:#fff}
        .assistant-send:hover{background:#234733}
        .assistant-send svg{width:17px;height:17px}
        body.dashboard-mode .assistant-panel{border-color:#343a31;background:#171c17;color:#f2f0e7}
        body.dashboard-mode .assistant-header,body.dashboard-mode .assistant-form,body.dashboard-mode .assistant-suggestions{border-color:#343a31;background:#171c17}
        body.dashboard-mode .assistant-heading small,body.dashboard-mode .assistant-recommendation-copy small{color:#a2a496}
        body.dashboard-mode .assistant-avatar{background:#252c23;color:#d8d6b8}
        body.dashboard-mode .assistant-close{color:#a2a496}
        body.dashboard-mode .assistant-close:hover{background:#252c23;color:#f2f0e7}
        body.dashboard-mode .assistant-disclaimer{border-color:#343a31;background:#20251f;color:#b8bdad}
        body.dashboard-mode .assistant-messages{background:#111611}
        body.dashboard-mode .assistant-message:not(.is-user){border-color:#343a31;background:#20251f;color:#f2f0e7}
        body.dashboard-mode .assistant-recommendation{border-color:#343a31;background:#20251f;color:#f2f0e7}
        body.dashboard-mode .assistant-action,body.dashboard-mode .assistant-suggestion{border-color:#444a39;background:#20251f;color:#e2e0c5}
        body.dashboard-mode .assistant-input{border-color:#444a39;background:#111611;color:#f2f0e7}
        body.dashboard-mode .assistant-input:focus{border-color:#b3b58a;box-shadow:0 0 0 3px rgba(212,213,173,.12)}
        @media(max-width:560px){
            .market-assistant{right:14px;bottom:14px;gap:9px}
            .assistant-launcher{min-height:48px;padding:0 15px}
            .assistant-panel{width:calc(100vw - 28px);height:min(520px,calc(100dvh - 88px));min-height:310px}
        }
        .merchant-panel{display:none;background:transparent}
        .vendor-welcome,.admin-welcome{position:relative;overflow:hidden;display:flex;justify-content:space-between;align-items:center;gap:24px;padding:26px 29px;border-radius:18px;background:linear-gradient(118deg,#315b43,#234733);color:#fff}
        .vendor-welcome:after,.admin-welcome:after{content:"";position:absolute;width:230px;height:230px;right:17%;top:-142px;border:1px solid #ffffff20;border-radius:50%;box-shadow:0 0 0 25px #ffffff09,0 0 0 52px #ffffff07;pointer-events:none}
        .vendor-welcome-copy,.admin-welcome-copy{position:relative;z-index:1}
        .vendor-welcome .eyebrow,.admin-welcome .eyebrow{color:#dcebbd}
        .vendor-welcome h2,.admin-welcome h2{font:800 23px/1.2 var(--display);letter-spacing:-.8px;margin:11px 0 7px}
        .vendor-welcome p,.admin-welcome p{max-width:530px;margin:0;color:#e0e9e1;font-size:12px;line-height:1.7}
        .vendor-welcome .button,.admin-welcome .button{position:relative;z-index:1;flex-shrink:0;background:#fff;color:var(--green)}
        .vendor-profile-card{display:grid;grid-template-columns:minmax(235px,.8fr) minmax(0,1.2fr);gap:0;margin-top:16px;background:#fff;border:1px solid var(--line);border-radius:16px;overflow:hidden}
        .vendor-profile-account,.vendor-profile-store{padding:20px 22px}
        .vendor-profile-account{border-right:1px solid var(--line)}
        .vendor-profile-title{font:750 13px var(--display);margin:0 0 14px}
        .vendor-account-identity{display:flex;align-items:center;gap:12px}
        .vendor-account-avatar{width:48px;height:48px;flex:0 0 48px;display:grid;place-items:center;border-radius:15px;background:#edf3e8;color:var(--green);font:800 18px var(--display);object-fit:cover}
        .vendor-account-copy{min-width:0}
        .vendor-account-copy strong,.vendor-account-copy small{display:block;overflow-wrap:anywhere}
        .vendor-account-copy strong{font:750 13px var(--display);margin-bottom:5px}
        .vendor-account-copy small{font-size:10px;color:var(--muted)}
        .vendor-account-form{display:grid;gap:9px;margin-top:16px}
        .vendor-account-form label{font-size:10px;font-weight:700}
        .vendor-account-form input[type="text"],.vendor-account-form input[type="file"]{width:100%;min-width:0;border:1px solid var(--line);border-radius:9px;background:#fff;padding:9px 10px;font:11px var(--sans)}
        .vendor-account-form input[type="file"]{padding:7px}
        .vendor-account-form small{color:var(--muted);font-size:9px}
        .vendor-account-form .button{justify-self:start;margin:2px 0 0}
        .vendor-profile-feedback{min-height:15px;margin:0;color:var(--muted);font-size:10px}
        .vendor-profile-role{display:inline-flex;margin-top:12px;padding:5px 9px;border-radius:20px;background:#edf3e8;color:var(--green);font-size:10px;font-weight:800}
        .vendor-store-profile{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:14px;background:#f7f8f5;border:1px solid #eff1ec;border-radius:13px}
        .vendor-store-copy{min-width:0}
        .vendor-store-copy strong{display:block;font:750 13px var(--display);margin:3px 0 5px}
        .vendor-store-copy small{display:block;color:var(--muted);font-size:10px;line-height:1.6}
        .vendor-store-status{display:inline-flex;padding:5px 8px;border-radius:20px;background:#edf3e8;color:var(--green);font-size:9px;font-weight:800;text-transform:capitalize;white-space:nowrap}
        .vendor-store-status.status-pending{background:#fff2d9;color:#886023}
        .vendor-store-status.status-rejected{background:#f9e9e6;color:#8c382f}
        .vendor-profile-empty{padding:15px;background:#f7f8f5;border-radius:13px;color:var(--muted);font-size:11px;line-height:1.65}
        .vendor-profile-empty strong{display:block;color:var(--ink);font:750 12px var(--display);margin-bottom:4px}
        .merchant-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:13px;margin:16px 0}
        .vendor-page{margin-top:16px}
        .vendor-page[data-vendor-page="overview"]{margin-top:0}
        .vendor-page .merchant-content-grid{grid-template-columns:minmax(0,1fr)}
        .product-stall-select{display:flex;align-items:center;gap:12px;max-width:520px;margin:0 0 16px}
        .product-stall-select label{flex:0 0 48px;font-size:11px;font-weight:700}
        .product-stall-select select{width:100%;min-width:0;height:42px;padding:0 11px;border:1px solid #343a31;border-radius:9px;background:#171c17;color:#f2f0e7}
        .merchant-stat{min-height:108px;background:#fff;border:1px solid var(--line);border-radius:15px;padding:17px}
        .merchant-stat span{font-size:11px;color:var(--muted)}
        .merchant-stat strong{display:block;font:800 25px var(--display);margin-top:11px;letter-spacing:-.8px}
        .merchant-stat small{display:block;color:#909990;font-size:10px;margin-top:4px}
        .merchant-callout{display:flex;align-items:flex-start;gap:12px;background:#eef3e8;border:1px solid #e2ead9;border-radius:13px;padding:15px 17px;color:#536957;font-size:12px;line-height:1.7}
        .merchant-callout-icon{display:grid;place-items:center;flex:0 0 27px;height:27px;border-radius:9px;background:#fff;color:var(--green);font-weight:800}
        .merchant-content-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(300px,.95fr);gap:16px;margin-top:16px;align-items:start}
        .merchant-card{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}
        .merchant-card-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:14px}
        .merchant-card-heading h3{font:750 14px var(--display);margin:0 0 5px}
        .merchant-card-heading p{font-size:11px;color:var(--muted);margin:0;line-height:1.6}
        .merchant-form{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin-top:15px}
        .merchant-form label{display:block;font-size:11px;font-weight:700;margin-bottom:6px}
        .merchant-form input,.merchant-form select{width:100%;height:42px;border:1px solid var(--line);border-radius:9px;padding:0 11px;background:#fff;outline:none}
        .merchant-form input:focus,.merchant-form select:focus{border-color:#94ad8b}
        .merchant-form .form-full{grid-column:1/-1}
        .application-requirements{grid-column:1/-1;min-width:0;padding:15px;border:1px solid var(--line);border-radius:12px;background:#f8faf6;color:#20271f}
        .application-requirements h4{margin:0 0 4px;font:750 12px var(--display)}
        .application-requirements p{margin:0 0 12px;color:#626b60;font-size:10px;line-height:1.6}
        .application-requirements .requirement-fields{display:grid;grid-template-columns:1fr 1fr;gap:11px}
        .application-requirements label{display:block;margin-bottom:5px;color:#30382f;font-size:10px;font-weight:700}
        .application-requirements input[type="file"]{width:100%;min-width:0;min-height:42px;padding:6px;border:1px solid #aeb6a9;border-radius:8px;background:#fff;color:#20271f;color-scheme:light;font:10px var(--sans)}
        .application-requirements .requirement-field:last-child{grid-column:1/-1}
        .merchant-payment-note{grid-column:1/-1;padding:11px 12px;border-radius:10px;background:#f6efe4;color:#654b2d;font-size:10px;line-height:1.6}
        .merchant-payment-state{max-width:260px;text-align:left;color:var(--muted);font-size:9px;line-height:1.5}
        .vendor-requirements-update{flex:1 1 100%;min-width:0;max-width:none;text-align:left}
        .vendor-requirements-update summary{cursor:pointer;color:var(--green);font-size:9px;font-weight:800}
        .vendor-requirements-update p{margin:6px 0;color:var(--muted);font-size:9px;line-height:1.5}
        .vendor-requirements-update form{display:grid;gap:7px}
        .vendor-requirements-update label{display:block;min-width:0;margin:3px 0;color:var(--ink);font-size:9px;font-weight:700}
        .vendor-requirements-update input[type="file"]{display:block;width:100%;max-width:100%;min-width:0;margin-top:4px;border:1px solid var(--line);border-radius:8px;background:#fff;color:#20271f;color-scheme:light;padding:6px;font-size:9px}
        .vendor-requirements-update button{justify-self:start}
        .merchant-vendors{margin-top:0}
        .merchant-vendor-row{display:grid;grid-template-columns:minmax(0,1fr);gap:11px;padding:13px 0;border-top:1px solid var(--line);font-size:12px}
        .merchant-vendor-name{display:flex;align-items:center;gap:11px;min-width:0}
        .merchant-vendor-icon{display:grid;place-items:center;flex:0 0 38px;height:38px;border-radius:11px;background:#eef3e8;color:var(--green);font-size:16px}
        .merchant-vendor-name>span:last-child{min-width:0}
        .merchant-vendor-name strong{display:block;margin-bottom:4px;overflow-wrap:anywhere}
        .merchant-vendor-name small{display:block;color:var(--muted);font-size:10px;line-height:1.5;overflow-wrap:anywhere}
        .merchant-row-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;min-width:0}
        .merchant-edit-button{border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--green);padding:7px 9px;font-size:10px;font-weight:800}
        .merchant-edit-button:hover{background:#f7f8f5}
        .merchant-payment-actions{display:flex;gap:6px;flex-wrap:wrap}
        .merchant-payment-button{border:0;border-radius:8px;background:#315b43;color:#fff;padding:7px 9px;font-size:10px;font-weight:800}
        .merchant-payment-check{border:1px solid var(--line);border-radius:8px;background:#fff;color:var(--green);padding:7px 9px;font-size:10px;font-weight:800}
        .admin-document-links{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
        .admin-document-links a{display:inline-flex;align-items:center;border:1px solid var(--line);border-radius:7px;padding:5px 7px;background:#fff;color:var(--green);font-size:9px;font-weight:800}
        .admin-payment-state{display:inline-flex;margin-top:7px;border-radius:20px;padding:4px 7px;background:#fff2d9;color:#886023;font-size:9px;font-weight:800;text-transform:capitalize}
        .admin-payment-state.is-paid{background:#edf3e8;color:var(--green)}
        .status-pending{background:#fff2d9;color:#886023}
        .status-rejected{background:#f9e9e6;color:#8c382f}
        .role-chip{display:inline-flex;align-items:center;padding:5px 9px;border-radius:20px;background:#edf3e8;color:var(--green);font-size:10px;font-weight:800;text-transform:capitalize}
        .admin-panel{display:none}
        .admin-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:16px 0 14px}
        .admin-stat{min-height:96px;padding:15px 16px;background:#fff;border:1px solid var(--line);border-radius:12px}
        .admin-stat span{font-size:10px;color:var(--muted)}
        .admin-stat strong{display:block;font:800 24px var(--display);margin-top:8px;letter-spacing:-.6px}
        .admin-stat small{display:block;color:var(--muted);font-size:10px;margin-top:4px}
        .admin-overview-heading{display:flex;align-items:center;justify-content:space-between;gap:14px}
        .admin-overview-heading h2{margin:0 0 4px;font:750 18px var(--display);letter-spacing:-.3px}
        .admin-overview-heading p{margin:0;color:var(--muted);font-size:11px;line-height:1.5}
        .admin-overview-updated{flex:none;padding:6px 9px;border:1px solid var(--line);border-radius:20px;color:var(--muted);font-size:9px}
        .admin-analytics{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:0 0 14px}
        .admin-analysis-card{min-width:0;padding:16px;background:#fff;border:1px solid var(--line);border-radius:12px}
        .admin-analysis-card h3{margin:0 0 3px;font:700 12px var(--display)}
        .admin-analysis-card>p{margin:0 0 12px;color:var(--muted);font-size:10px;line-height:1.5}
        .admin-analysis-list{display:grid;gap:10px}
        .admin-analysis-row>span{display:flex;justify-content:space-between;gap:10px;margin-bottom:5px;color:var(--muted);font-size:10px}
        .admin-analysis-row>span strong{color:var(--ink);font-weight:700}
        .admin-analysis-bar{height:5px;overflow:hidden;border-radius:8px;background:var(--paper)}
        .admin-analysis-bar span{display:block;height:100%;border-radius:inherit;background:var(--green)}
        .admin-analysis-empty{padding:9px 0;color:var(--muted);font-size:10px}
        .admin-workspace{display:block}
        .admin-queue,.admin-sidecard{background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}
        .admin-queue-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:8px}
        .admin-queue-heading h3,.admin-sidecard h3{font:750 14px var(--display);margin:0 0 5px}
        .admin-queue-heading p,.admin-sidecard p{font-size:11px;color:var(--muted);line-height:1.6;margin:0}
        .admin-queue-count{padding:5px 9px;border-radius:20px;background:var(--lime);color:var(--green-dark);font-size:10px;font-weight:800;white-space:nowrap}
        .admin-empty{display:grid;justify-items:center;text-align:center;padding:38px 16px 24px}
        .admin-empty-icon{display:grid;place-items:center;width:40px;height:40px;border-radius:13px;background:#eef3e8;color:var(--green);font-size:18px;font-weight:800;margin-bottom:12px}
        .admin-empty strong{font:750 13px var(--display)}
        .admin-empty p{font-size:11px;color:var(--muted);margin:5px 0 0}
        .admin-application{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:15px 0;border-top:1px solid var(--line)}
        .admin-application-info{display:flex;align-items:flex-start;gap:11px;min-width:0}
        .admin-application-icon{display:grid;place-items:center;flex:0 0 38px;height:38px;border-radius:11px;background:#eef3e8;color:var(--green);font-size:16px}
        .admin-application strong{display:block;font:700 12px var(--display);margin-bottom:5px}
        .admin-application small{display:block;color:var(--muted);font-size:10px;line-height:1.6}
        .admin-actions{display:flex;gap:7px;flex-shrink:0}
        .admin-account-list{display:grid;gap:12px;margin-top:12px}
        .admin-account-row{display:grid;grid-template-columns:minmax(0,1fr) minmax(280px,1.1fr);gap:16px;align-items:center;padding:14px;border:1px solid var(--line);border-radius:12px}
        .admin-account-form{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end}
        .admin-account-form label{display:grid;gap:5px;color:var(--muted);font-size:10px;font-weight:700}
        .admin-account-form input{width:100%;min-width:0;height:38px;border:1px solid var(--line);border-radius:8px;padding:0 10px;font:inherit;font-size:11px}
        .admin-account-form button{height:38px;white-space:nowrap}
        .admin-account-note{margin-top:5px;color:var(--muted);font-size:10px;line-height:1.5}
        .button-approve{background:var(--green);color:#fff;border:0;border-radius:9px;padding:9px 12px;font-weight:700;font-size:11px}
        .button-reject{background:#fff;border:1px solid #ecd5d1;color:#8c382f;border-radius:9px;padding:9px 12px;font-weight:700;font-size:11px}
        .admin-sidecard{position:sticky;top:18px}
        .admin-sidecard h3{margin-bottom:12px}
        .admin-checklist{list-style:none;padding:0;margin:0}
        .admin-checklist li{display:flex;gap:9px;padding:10px 0;border-top:1px solid #eff1ec;color:#526056;font-size:10px;line-height:1.6}
        .admin-check{display:grid;place-items:center;flex:0 0 20px;height:20px;border-radius:7px;background:#eef3e8;color:var(--green);font-weight:800}
        .admin-storage-section{margin-top:16px}
        .admin-storage-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
        .admin-storage-group{min-width:0;background:#fff;border:1px solid var(--line);border-radius:16px;padding:20px}
        .admin-storage-group h2{margin:0 0 5px;font:750 15px var(--display)}
        .admin-storage-group>p{margin:0 0 15px;color:var(--muted);font-size:11px;line-height:1.6}
        .admin-storage-count{display:inline-flex;margin-bottom:12px;padding:5px 9px;border-radius:20px;background:#eef3e8;color:var(--green);font-size:10px;font-weight:800}
        .admin-storage-photos{display:grid;grid-template-columns:repeat(auto-fill,minmax(135px,1fr));gap:10px}
        .admin-storage-photo{display:block;min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:11px;background:var(--paper);color:var(--ink);text-decoration:none}
        .admin-storage-photo img{display:block;width:100%;height:112px;object-fit:cover;background:#e8ebe4}
        .admin-storage-photo span{display:block;padding:9px;font-size:10px;line-height:1.45}
        .admin-storage-photo strong,.admin-storage-photo small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .admin-storage-photo small{margin-top:3px;color:var(--muted);font-size:9px}
        .admin-storage-documents{display:grid;gap:8px}
        .admin-storage-document{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px;border:1px solid var(--line);border-radius:10px}
        .admin-storage-document strong,.admin-storage-document small{display:block}
        .admin-storage-document strong{font-size:11px}
        .admin-storage-document small{margin-top:4px;color:var(--muted);font-size:9px}
        .admin-storage-document a{flex:none;padding:7px 10px;border-radius:7px;background:var(--lime);color:var(--green-dark);font-size:10px;font-weight:800;text-decoration:none}
        .admin-storage-empty{grid-column:1/-1;padding:20px;border:1px dashed var(--line);border-radius:10px;color:var(--muted);font-size:11px;text-align:center}
        #dashboard.vendor-dashboard{--ink:#302b25;--muted:#7b756c;--green:#8a5b2e;--green-dark:#694321;--lime:#f2e5ce;--line:#ebe3d8;--paper:#faf7f2}
        #dashboard.admin-dashboard{--ink:#273447;--muted:#748093;--green:#3f5f83;--green-dark:#2f4967;--lime:#dce7f2;--line:#e1e7ef;--paper:#f5f7fa}
        #dashboard.customer-dashboard .customer-welcome{box-shadow:0 16px 38px rgba(35,71,51,.12)}
        #dashboard.vendor-dashboard .vendor-welcome{background:linear-gradient(118deg,#523b26,#8a5b2e)}
        #dashboard.vendor-dashboard .vendor-welcome .eyebrow{color:#f1d8b2}
        #dashboard.vendor-dashboard .vendor-profile-card{border-top:3px solid #c58b4e}
        #dashboard.vendor-dashboard .merchant-callout{background:#f7efe3;border-color:#ecddc7;color:#685439}
        #dashboard.vendor-dashboard .merchant-callout-icon{color:#8a5b2e}
        #dashboard.admin-dashboard .admin-welcome{background:linear-gradient(118deg,#273b55,#3f5f83)}
        #dashboard.admin-dashboard .admin-welcome .eyebrow{color:#dce7f2}
        #dashboard.admin-dashboard .admin-stat{box-shadow:0 8px 22px rgba(39,52,71,.04)}
        #dashboard.admin-dashboard .admin-queue-count{background:#e8eef6;color:#3f5f83}
        body.dashboard-mode{background:#10130f;color:#f5f2e9}
        body.dashboard-mode .topbar{width:100%;max-width:none;height:76px;margin:0;padding:0 max(28px,calc((100vw - 1180px)/2));border-bottom:1px solid rgba(255,255,255,.1);background:rgba(16,19,15,.94);color:#f7f5ee}
        body.dashboard-mode .topbar.is-scrolled{background:rgba(16,19,15,.97);box-shadow:0 8px 30px rgba(0,0,0,.2)}
        body.dashboard-mode .brand-mark{background:#d6d4b2;color:#172019;box-shadow:none}
        body.dashboard-mode .nav-links{color:#c3c3b9}
        body.dashboard-mode .nav-links a:hover,body.dashboard-mode .text-button{color:#f3f0e7}
        body.dashboard-mode .nav-links a:after{background:#c6ca9b}
        body.dashboard-mode .button-primary{background:#d8d6b8;color:#171c16;box-shadow:none}
        body.dashboard-mode .button-primary:hover{background:#eee9d1}
        body.dashboard-mode .button-light{border-color:rgba(255,255,255,.23);background:rgba(255,255,255,.06);color:#f5f2e9}
        body.dashboard-mode #dashboard{width:min(1180px,calc(100% - 56px));max-width:none;padding:35px 0 72px;scroll-margin-top:90px;--ink:#f2f0e7;--muted:#a2a496;--green:#d4d4b4;--green-dark:#e4e1c7;--lime:#252c23;--line:#343a31;--paper:#171c17}
        body.dashboard-mode #dashboard.vendor-dashboard,body.dashboard-mode #dashboard.admin-dashboard{--ink:#f2f0e7;--muted:#a2a496;--green:#d4d4b4;--green-dark:#e4e1c7;--lime:#252c23;--line:#343a31;--paper:#171c17}
        body.dashboard-mode #dashboard .dashboard-head{border-color:#343a31}
        body.dashboard-mode #dashboard .dashboard-profile-bar{border-color:#343a31;background:#171c17}
        body.dashboard-mode #dashboard .profile-button .profile-name{color:#f2f0e7}
        body.dashboard-mode #dashboard .profile-button .profile-email{color:#a2a496}
        body.dashboard-mode #dashboard .dashboard-profile-form label{color:#f2f0e7}
        body.dashboard-mode #dashboard .dashboard-profile-feedback{color:#a2a496}
        body.dashboard-mode #dashboard .dashboard-head h1{color:#f2f0e7}
        body.dashboard-mode #dashboard .dashboard-head p{color:#a2a496}
        body.dashboard-mode #dashboard .eyebrow{color:#c9c9aa}
        body.dashboard-mode #dashboard .eyebrow-dot{background:#d4d5ad;box-shadow:0 0 0 4px rgba(212,213,173,.12)}
        body.dashboard-mode #dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.admin-dashboard .dashboard-sidebar{background:#171c17;border:1px solid #30372d;color:#d5d6c9;box-shadow:0 18px 40px rgba(0,0,0,.2)}
        body.dashboard-mode #dashboard .dashboard-brand{color:#f5f2e9}
        body.dashboard-mode #dashboard .dashboard-brand-mark{border-radius:2px;background:#d8d6b8;color:#171c16}
        body.dashboard-mode #dashboard .dashboard-sidebar-label{color:#a5a795}
        body.dashboard-mode #dashboard .dashboard-nav-button{color:#c3c3b9}
        body.dashboard-mode #dashboard .dashboard-nav-button:hover{background:rgba(255,255,255,.08);color:#fff}
        body.dashboard-mode #dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.admin-dashboard .dashboard-nav-button.active{background:#d8d6b8;color:#171c16;box-shadow:none}
        body.dashboard-mode #dashboard .dashboard-sidebar-foot{border-color:#ffffff1a;color:#a5a795}
        body.dashboard-mode #dashboard .notification-button{border-color:#343a31;background:#171c17;color:#f2f0e7}
        body.dashboard-mode #dashboard .notification-button:hover{background:#252c24}
        body.dashboard-mode #dashboard .notification-panel{border-color:#343a31;background:#171c17;color:#f2f0e7}
        body.dashboard-mode #dashboard .notification-heading{border-color:#343a31}
        body.dashboard-mode #dashboard .notification-heading button{color:#d8d6b8}
        body.dashboard-mode #dashboard .notification-item{border-color:#343a31;background:#171c17;color:#f2f0e7}
        body.dashboard-mode #dashboard .notification-item:hover,body.dashboard-mode #dashboard .notification-item.unread{background:#242b23}
        body.dashboard-mode #dashboard .notification-item span,body.dashboard-mode #dashboard .notification-item time{color:#aeb1a3}
        body.dashboard-mode #dashboard .customer-welcome,body.dashboard-mode #dashboard .vendor-welcome,body.dashboard-mode #dashboard .admin-welcome{border:1px solid rgba(245,243,229,.18);border-radius:2px;background:linear-gradient(90deg,rgba(9,13,10,.94),rgba(12,17,12,.68)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=85") center 48%/cover;color:#f5f2e9}
        body.dashboard-mode #dashboard .customer-welcome .eyebrow,body.dashboard-mode #dashboard .vendor-welcome .eyebrow,body.dashboard-mode #dashboard .admin-welcome .eyebrow{color:#d4d4b4}
        body.dashboard-mode #dashboard .customer-welcome h2,body.dashboard-mode #dashboard .vendor-welcome h2,body.dashboard-mode #dashboard .admin-welcome h2{font-weight:500;letter-spacing:-.6px}
        body.dashboard-mode #dashboard .customer-welcome p,body.dashboard-mode #dashboard .vendor-welcome p,body.dashboard-mode #dashboard .admin-welcome p{color:rgba(238,239,228,.76)}
        body.dashboard-mode #dashboard .customer-welcome .button,body.dashboard-mode #dashboard .vendor-welcome .button,body.dashboard-mode #dashboard .admin-welcome .button{border-radius:2px;background:#d8d6b8;color:#171c16}
        body.dashboard-mode #dashboard .customer-highlight,body.dashboard-mode #dashboard .vendor-panel,body.dashboard-mode #dashboard .map-panel,body.dashboard-mode #dashboard .booking-section,body.dashboard-mode #dashboard .message-inbox,body.dashboard-mode #dashboard .vendor-profile-card,body.dashboard-mode #dashboard .merchant-card,body.dashboard-mode #dashboard .merchant-stat,body.dashboard-mode #dashboard .admin-stat,body.dashboard-mode #dashboard .admin-queue,body.dashboard-mode #dashboard .admin-sidecard{border-color:#343a31;border-radius:2px;background:#171c17;color:#f2f0e7;box-shadow:none}
        body.dashboard-mode #dashboard .dashboard-tabs{border-color:#343a31}
        body.dashboard-mode #dashboard .tab{color:#a2a496}
        body.dashboard-mode #dashboard .tab.active{border-color:#d4d4b4;color:#e5e2c8}
        body.dashboard-mode #dashboard .search-wrap input,body.dashboard-mode #dashboard .select-filter{border-color:rgba(255,255,255,.2);border-radius:2px;background:#f4f2e6;color:#20271f}
        body.dashboard-mode #dashboard .search-wrap input:focus,body.dashboard-mode #dashboard .select-filter:focus{border-color:#d4d4b4}
        body.dashboard-mode #dashboard .search-symbol{color:#616c5e}
        body.dashboard-mode #dashboard .vendor-card,body.dashboard-mode #dashboard .booking-row,body.dashboard-mode #dashboard .conversation-row{border-color:#343a31;background:transparent;color:#f2f0e7}
        body.dashboard-mode #dashboard .vendor-card:hover,body.dashboard-mode #dashboard .vendor-card.selected,body.dashboard-mode #dashboard .conversation-row:hover{background:#222820}
        body.dashboard-mode #dashboard .vendor-action,body.dashboard-mode #dashboard .vendor-message-action,body.dashboard-mode #dashboard .status-pill,body.dashboard-mode #dashboard .role-chip,body.dashboard-mode #dashboard .merchant-callout,body.dashboard-mode #dashboard .merchant-callout-icon{border-color:#444a39;background:#252c23;color:#e2e0c5}
        body.dashboard-mode #dashboard .panel-header{border-color:#343a31}
        body.dashboard-mode #dashboard .panel-header strong,body.dashboard-mode #dashboard .booking-section h2,body.dashboard-mode #dashboard .message-inbox-heading h2,body.dashboard-mode #dashboard .message-empty strong,body.dashboard-mode #dashboard .admin-queue-heading h3,body.dashboard-mode #dashboard .admin-sidecard h3{color:#f2f0e7}
        body.dashboard-mode #dashboard .panel-header span,body.dashboard-mode #dashboard .booking-empty,body.dashboard-mode #dashboard .message-inbox-heading p,body.dashboard-mode #dashboard .message-empty,body.dashboard-mode #dashboard .admin-queue-heading p,body.dashboard-mode #dashboard .admin-sidecard p{color:#a2a496}
        body.dashboard-mode #dashboard .vendor-main h3,body.dashboard-mode #dashboard .customer-highlight strong,body.dashboard-mode #dashboard .admin-stat strong,body.dashboard-mode #dashboard .merchant-stat strong{color:#e3e0c7}
        body.dashboard-mode #dashboard .vendor-main .stars{color:#d8c58a}
        body.dashboard-mode #dashboard .vendor-profile-account{border-color:#343a31}
        body.dashboard-mode #dashboard .vendor-store-profile{border-color:#343a31;background:#20251f}
        body.dashboard-mode #dashboard .profile-button,body.dashboard-mode #dashboard .profile-menu{border-color:#343a31;background:#171c17;color:#f2f0e7}
        body.dashboard-mode #dashboard .profile-menu button{color:#f2f0e7}
        body.dashboard-mode #dashboard .profile-menu button:hover{background:#252c23}
        body.dashboard-mode #dashboard .modal{border:1px solid #343a31;border-radius:2px;background:#171c17;color:#f2f0e7}
        body.dashboard-mode #dashboard .modal input,body.dashboard-mode #dashboard .modal select,body.dashboard-mode #dashboard .role-form-field select,body.dashboard-mode #dashboard .vendor-account-form input[type="text"],body.dashboard-mode #dashboard .vendor-account-form input[type="file"]{border-color:#444a39;background:#f4f2e6;color:#20271f}
        body.dashboard-mode #dashboard .modal h2,body.dashboard-mode #dashboard .modal label,body.dashboard-mode #dashboard .role-form-field label{color:#f2f0e7}
        body.dashboard-mode #dashboard .modal p,body.dashboard-mode #dashboard .modal-close{color:#a2a496}
        body.dashboard-mode #dashboard .admin-queue-count{background:#252c23;color:#e2e0c5}
        body.dashboard-mode #dashboard .admin-checklist li{border-color:#343a31;color:#c3c3b9}
        body.dashboard-mode #dashboard .admin-check,body.dashboard-mode #dashboard .admin-empty-icon{background:#252c23;color:#e2e0c5}
        body.dashboard-mode #dashboard .admin-document-links a,body.dashboard-mode #dashboard .merchant-payment-check{border-color:#444a39;background:#20251f;color:#e2e0c5}
        body.dashboard-mode #dashboard .merchant-payment-button,body.dashboard-mode #dashboard .button-approve{background:#d8d6b8;color:#171c16}
        body.dashboard-mode #dashboard .button-reject{border-color:#5c3933;background:#211817;color:#e2b8ac}
        body.dashboard-mode #dashboard .chat-messages,body.dashboard-mode #dashboard .chat-start-note,body.dashboard-mode #dashboard .chat-suggestions{background:#20251f}
        body.dashboard-mode #dashboard .chat-form{border-color:#343a31;background:#171c17}
        body.dashboard-mode #dashboard .chat-message{border-color:#343a31;background:#252c23;color:#f2f0e7}
        body.dashboard-mode #dashboard .chat-message.is-own{border-color:#d8d6b8;background:#d8d6b8;color:#171c16}
        body.dashboard-mode #dashboard .chat-form textarea{border-color:#444a39;background:#f4f2e6;color:#20271f}
        body.dashboard-mode #dashboard .chat-suggestions button{border-color:#444a39;background:#171c17;color:#e2e0c5}
        body.dashboard-mode #dashboard .chat-suggestions button:hover{background:#252c23}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome{min-height:168px;padding:30px 32px;border:1px solid #ffffff16;border-radius:8px;background:linear-gradient(100deg,rgba(12,17,13,.91),rgba(15,20,15,.58)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1600&q=85") center 46%/cover;box-shadow:0 18px 42px rgba(0,0,0,.18)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome:after{right:30%;top:-145px;width:270px;height:270px;border-color:#e6e3c01c;box-shadow:0 0 0 30px #e6e3c00a,0 0 0 60px #e6e3c006}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome h2{font-size:clamp(23px,2.5vw,31px);letter-spacing:-1px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome p{max-width:600px;color:#d0d1c5}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome .button{min-height:46px;padding:0 18px;border-radius:5px;font-size:12px;box-shadow:0 6px 18px #0003}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stats{gap:14px;margin:18px 0}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat{position:relative;overflow:hidden;min-height:116px;padding:19px 20px;border-radius:7px;background:linear-gradient(145deg,#1a201a,#151a15);box-shadow:0 8px 22px #0002}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat:before{position:absolute;inset:0 auto 0 0;width:3px;background:linear-gradient(180deg,#d4d4b4,#777d5f);content:""}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat span{font-size:10px;letter-spacing:.2px;color:#b8b9aa}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat strong{margin-top:10px;color:#ece8cf;font-size:29px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat small{margin-top:5px;color:#91968a}
        body.dashboard-mode #dashboard.admin-dashboard .admin-workspace{grid-template-columns:minmax(0,1fr) 288px;gap:18px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{border-radius:8px;background:linear-gradient(145deg,#191e19,#151a15);box-shadow:0 12px 28px #0002}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue{padding:22px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{top:20px;padding:22px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading{align-items:flex-start;margin-bottom:17px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading h3,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard h3{font-size:15px;letter-spacing:-.2px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading p,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard p{max-width:440px;color:#aeb1a3;font-size:11px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-count{padding:7px 10px;border:1px solid #d4d4b422;border-radius:999px;background:#d4d4b40d;color:#e3e0c5}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application{display:grid;grid-template-columns:minmax(0,1fr);gap:14px;margin-top:12px;padding:16px;border:1px solid #343a31;border-radius:7px;background:linear-gradient(120deg,#1c221c,#171c17);transition:border-color .18s,background .18s}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application:hover{border-color:#59624e;background:linear-gradient(120deg,#20271f,#191e19)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application-info{gap:13px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application-icon{flex-basis:44px;height:44px;border:1px solid #d4d4b41f;border-radius:11px;background:#d4d4b40c;color:#d8d6b8}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application strong{margin:1px 0 7px;font-size:13px;color:#f2f0e7}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application small{color:#aeb1a3;line-height:1.75}
        body.dashboard-mode #dashboard.admin-dashboard .admin-document-links{gap:7px;margin-top:11px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-document-links a{border-radius:6px;background:#202720}
        body.dashboard-mode #dashboard.admin-dashboard .admin-payment-state{padding:6px 9px;border:1px solid #d7a85a25;background:#352d1c;color:#e5c27f}
        body.dashboard-mode #dashboard.admin-dashboard .admin-payment-state.is-paid{border-color:#a8be8528;background:#222b1e;color:#c8d7a9}
        body.dashboard-mode #dashboard.admin-dashboard .admin-actions{justify-content:flex-end;gap:9px;padding-top:13px;border-top:1px solid #343a31}
        body.dashboard-mode #dashboard.admin-dashboard .button-approve,body.dashboard-mode #dashboard.admin-dashboard .button-reject{min-height:39px;padding:0 14px;border-radius:6px;font-size:10px;transition:transform .18s,background .18s}
        body.dashboard-mode #dashboard.admin-dashboard .button-approve:not(:disabled):hover,body.dashboard-mode #dashboard.admin-dashboard .button-reject:hover{transform:translateY(-1px)}
        body.dashboard-mode #dashboard.admin-dashboard .button-approve:disabled{border:1px solid #484b3a;background:#292c23;color:#8c8d7c;cursor:not-allowed}
        body.dashboard-mode #dashboard.admin-dashboard .button-reject{border-color:#75483d;background:#261b18;color:#e2b8ac}
        body.dashboard-mode #dashboard.admin-dashboard .button-reject:hover{background:#35211d}
        body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard h3{margin-bottom:8px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-checklist{margin-top:15px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-checklist li{gap:11px;padding:13px 0;border-color:#343a31;color:#c6c7ba}
        body.dashboard-mode #dashboard.admin-dashboard .admin-check{flex-basis:22px;height:22px;border:1px solid #d4d4b41a;border-radius:7px;background:#d4d4b40c;color:#dedbbd}
        body.dashboard-mode #dashboard.admin-dashboard .admin-empty{padding:48px 18px 34px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-empty-icon{border:1px solid #d4d4b41a;background:#d4d4b40c}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{border-color:#30372d;border-radius:8px;background:linear-gradient(145deg,#191e19,#151a15);box-shadow:0 12px 28px #0002}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group h2{color:#f2f0e7}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group>p{color:#aeb1a3}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-count{border:1px solid #d4d4b422;background:#d4d4b40d;color:#e3e0c5}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo{border-color:#343a31;background:#1a201a;color:#f2f0e7}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo img{background:#202720}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo small,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document small{color:#aeb1a3}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document{border-color:#343a31;background:#1a201a;color:#f2f0e7}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document a{background:#d4d4b4;color:#171c16}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-empty{border-color:#343a31;color:#aeb1a3}
        @media(max-width:1050px){
            body.dashboard-mode #dashboard.admin-dashboard .admin-workspace{grid-template-columns:minmax(0,1fr) 250px;gap:13px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-queue{padding:18px}
        }
        @media(max-width:820px){
            body.dashboard-mode #dashboard.admin-dashboard .admin-workspace{grid-template-columns:minmax(0,1fr)}
            body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{position:static}
            body.dashboard-mode #dashboard.admin-dashboard .admin-welcome{min-height:0;padding:24px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-storage-grid{grid-template-columns:minmax(0,1fr)}
        }
        @media(max-width:560px){
            body.dashboard-mode #dashboard.admin-dashboard .admin-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-stat{min-height:100px;padding:14px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-stat strong{font-size:25px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{padding:15px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading{flex-direction:column}
            body.dashboard-mode #dashboard.admin-dashboard .admin-application{padding:13px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-actions{justify-content:stretch;flex-direction:column}
            body.dashboard-mode #dashboard.admin-dashboard .admin-actions button{width:100%}
        }
        .dashboard-layout{display:grid;grid-template-columns:205px minmax(0,1fr);gap:23px;align-items:start}
        .dashboard-sidebar{position:sticky;top:16px;min-height:calc(100dvh - 32px);padding:20px 13px;border-radius:18px;background:#222731;color:#d5d8df;box-shadow:0 18px 40px rgba(20,24,32,.14)}
        .dashboard-brand{display:flex;align-items:center;gap:9px;padding:2px 8px 24px;color:#fff;font:800 17px var(--display);letter-spacing:-.6px}
        .dashboard-brand-mark{display:grid;place-items:center;width:29px;height:29px;border-radius:9px;background:#8b62bc;color:#fff}
        .dashboard-brand-mark svg{width:19px;height:19px}
        .dashboard-sidebar-label{padding:0 9px;margin:0 0 9px;color:#858b99;font-size:8px;font-weight:800;letter-spacing:1px;text-transform:uppercase}
        .dashboard-sidebar-nav{display:grid;gap:4px}
        .dashboard-nav-button{display:flex;align-items:center;gap:10px;width:100%;padding:10px 11px;border:0;border-radius:9px;background:transparent;color:#b8bdc8;text-align:left;font:600 10px var(--sans)}
        .dashboard-nav-button span:first-child{width:17px;text-align:center;font-size:14px}
        .dashboard-nav-button:hover{background:#ffffff0d;color:#fff}
        .dashboard-nav-button.active{background:#694b8d;color:#fff;box-shadow:0 6px 14px #1a1d2480}
        .dashboard-sidebar-foot{position:absolute;left:13px;right:13px;bottom:15px;border-top:1px solid #ffffff14;padding:13px 8px 0;color:#949aa7;font-size:9px;line-height:1.6}
        .dashboard-main{min-width:0}
        #dashboard .dashboard-head{padding:5px 0 17px;border-bottom:1px solid var(--line)}
        #dashboard .dashboard-head h1{font-size:27px}
        #dashboard.vendor-dashboard .dashboard-sidebar{background:#302b27}
        #dashboard.vendor-dashboard .dashboard-nav-button.active{background:#8a5b2e}
        #dashboard.admin-dashboard .dashboard-sidebar{background:#263244}
        #dashboard.admin-dashboard .dashboard-nav-button.active{background:#3f5f83}
        body.dashboard-mode #dashboard.vendor-dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.admin-dashboard .dashboard-sidebar{background:#171c17;border-color:#30372d;color:#d5d6c9}
        body.dashboard-mode #dashboard.vendor-dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.admin-dashboard .dashboard-nav-button.active{background:#d8d6b8;color:#171c16;box-shadow:none}
        .role-form-field{margin-top:14px}
        .role-form-field label{display:block;font-size:11px;font-weight:700;margin-bottom:6px}
        .role-form-field select{width:100%;height:42px;border:1px solid var(--line);border-radius:9px;padding:0 11px;background:#fff}
        .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(22,34,26,.42);backdrop-filter:blur(4px);z-index:2000;align-items:center;justify-content:center;padding:18px}
        .modal-backdrop.open{display:flex}
        .modal{width:min(420px,100%);background:#fff;border-radius:18px;padding:25px;box-shadow:0 28px 90px #12201a45;position:relative}
        .modal-close{position:absolute;right:14px;top:12px;border:0;background:none;font-size:22px;color:#879087}
        .modal h2{font:800 21px var(--display);letter-spacing:-.6px;margin:8px 0}
        .modal p{font-size:12px;color:var(--muted);line-height:1.65}
        .modal label{display:block;font-size:11px;font-weight:700;margin:15px 0 6px}
        .modal input,.modal select{width:100%;height:42px;border:1px solid var(--line);border-radius:9px;padding:0 11px;outline:none;background:#fff}
        .modal input:focus,.modal select:focus{border-color:#94ad8b}
        .modal .button{width:100%;margin-top:18px}
        #authModal .modal{color:#202b25}
        #authModal .eyebrow{color:#315b43}
        #authModal .modal h2,#authModal .modal label{color:#202b25}
        #authModal #forgotPasswordButton{position:relative;z-index:1;display:inline-flex;align-items:center;min-height:44px;margin:4px 0 0;padding:8px 10px;color:#315b43;font-size:14px;cursor:pointer;touch-action:manipulation}
        #authModal #forgotPasswordButton:disabled{cursor:wait;opacity:.7}
        #authModal #forgotPasswordButton:hover,#authModal #forgotPasswordButton:focus-visible{color:#234733;text-decoration:underline}
        #authModal #forgotPasswordButton:focus-visible{outline:2px solid #315b43;outline-offset:2px}
        #authModal .text-button{color:#315b43}
        #authModal .text-button:hover,#authModal .text-button:focus-visible{color:#234733;text-decoration:underline}
        .google-area{min-height:42px;margin:18px auto 10px;display:flex;justify-content:center}
        .auth-help{text-align:center;font-size:10px!important;margin-bottom:0}
        .auth-switch{display:flex;gap:5px;padding:4px;border-radius:11px;background:#f3f5f1;margin:16px 0}
        .auth-mode{flex:1;border:0;background:transparent;color:var(--muted);border-radius:8px;padding:9px;font-size:12px;font-weight:700}
        .auth-mode.active{background:#fff;color:var(--green);box-shadow:0 1px 4px rgba(31,48,36,.08)}
        .auth-divider{display:flex;align-items:center;gap:12px;color:#98a098;font-size:10px;margin:11px 0}
        .auth-divider:before,.auth-divider:after{content:"";height:1px;background:var(--line);flex:1}
        .auth-form{margin-top:12px}
        .auth-form label{margin-top:12px}
        .auth-form .button{margin-top:16px}
        .auth-form-message{min-height:16px;margin:8px 0 0!important;text-align:center;color:#8c382f!important;font-size:11px!important}
        #authModal [hidden]{display:none!important}
        .toast{position:fixed;z-index:3000;bottom:22px;left:50%;transform:translate(-50%,15px);opacity:0;pointer-events:none;background:#263d2e;color:#fff;padding:12px 17px;border-radius:11px;font-size:12px;box-shadow:var(--shadow);transition:.25s}
        .toast.show{transform:translate(-50%,0);opacity:1}
        .toast.error{background:#8c382f}
        .leaflet-popup-content-wrapper{border-radius:12px!important}
        .leaflet-popup-content{font:12px var(--sans);margin:12px!important}
        .leaflet-popup-content strong{font:700 13px var(--display)}
        .leaflet-popup-content button{margin-top:8px;border:0;border-radius:7px;background:var(--green);color:white;padding:7px 10px;font:700 10px var(--sans)}
        @media(max-width:1100px){
            body.dashboard-mode #dashboard .content-grid{grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr)}
        }
        @media(max-width:820px){
            body.dashboard-mode #dashboard .dashboard-layout{grid-template-columns:minmax(0,1fr)}
            body.dashboard-mode #dashboard .dashboard-main,body.dashboard-mode #dashboard .content-grid,body.dashboard-mode #dashboard .vendor-panel,body.dashboard-mode #dashboard .map-panel{min-width:0}
            body.dashboard-mode #dashboard .content-grid{grid-template-columns:minmax(0,1fr)}
            body.dashboard-mode #dashboard .dashboard-profile-bar{grid-template-columns:minmax(0,1fr)}
        }
        @media(max-width:820px){
            .shell{width:min(100% - 36px,620px)}.nav-links{display:none}.hero{grid-template-columns:1fr;gap:29px;padding:51px 0 46px;min-height:auto}.hero-copy-column{max-width:620px}.hero h1{font-size:56px}.hero-visual{height:390px}.hero-bottom-row{margin-top:25px}.content-grid{grid-template-columns:1fr}.vendor-list{max-height:380px}#vendorMap{height:350px}.how-grid{grid-template-columns:1fr}.how-card{min-height:auto}.trust-row{grid-template-columns:repeat(3,1fr);gap:18px}.trust-row>strong{grid-column:1/-1}.trust-row .button{grid-column:1/-1;justify-self:start}.dashboard-head{align-items:center}.customer-overview{grid-template-columns:1fr}.customer-highlights{grid-template-columns:repeat(2,1fr)}.vendor-profile-card{grid-template-columns:1fr}.vendor-profile-account{border-right:0;border-bottom:1px solid var(--line)}.merchant-grid{grid-template-columns:repeat(3,1fr)}.merchant-content-grid{grid-template-columns:1fr}.admin-workspace{grid-template-columns:1fr}.admin-sidecard{position:static}
            body.dashboard-mode #dashboard .content-grid{grid-template-columns:1fr}
            .map-panel-heading{align-items:flex-start;flex-direction:column}.map-actions{width:100%}.map-action{flex:1}
        }
        @media(max-width:480px){
            .shell{width:calc(100% - 28px)}.topbar{height:68px}.brand{font-size:20px}.nav-actions .button{padding:10px 12px;font-size:11px}
            .hero{padding:37px 0 35px;gap:23px}.hero h1{font-size:44px;letter-spacing:-2.9px}.hero-copy{font-size:13px;line-height:1.75}.hero-search{min-height:55px;padding-left:12px;gap:6px}.hero-search input{font-size:11px}.hero-search .button{min-height:42px;padding:0 11px;font-size:11px}.hero-search-chips{gap:6px}.hero-chip{padding:7px 9px}.hero-visual{height:290px;border-radius:20px}.hero-visual-top{top:13px;left:13px;right:13px}.hero-live-pill,.hero-area-pill{padding:7px 9px;font-size:8px}.hero-market-card{right:12px;bottom:12px;left:12px;padding:13px;border-radius:14px}.hero-market-card strong{font-size:14px}.hero-market-card small{font-size:9px}.hero-market-cta{width:37px;height:37px;flex-basis:37px}.hero-photo-caption{left:15px;bottom:96px;font-size:9px}.hero-bottom-row{margin-top:20px;padding-top:16px}.hero-stat{padding:0 9px}.hero-stat strong{font-size:16px}.hero-stat span{font-size:8px}.section{padding:54px 0}.section-heading{align-items:start;flex-direction:column;margin-bottom:23px}.section-heading h2{font-size:25px}.how-card{padding:20px}.trust-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:27px 0}.trust-row>strong{grid-column:1/-1}.trust-stat{align-items:flex-start;gap:6px}.trust-stat strong{font-size:18px}.trust-stat span{font-size:8px}.trust-row .button{width:100%;grid-column:1/-1}
            #dashboard{padding-top:25px}.dashboard-layout{grid-template-columns:1fr;gap:14px}.dashboard-sidebar{position:static;min-height:0;padding:10px 11px;border-radius:13px}.dashboard-brand{display:none}.dashboard-sidebar-label,.dashboard-sidebar-foot{display:none}.dashboard-sidebar-nav{display:flex;gap:5px;overflow-x:auto;scrollbar-width:thin}.dashboard-nav-button{width:auto;min-width:max-content;padding:9px 10px}.dashboard-head{align-items:flex-start}.dashboard-head h1{font-size:25px}.dashboard-head p{max-width:240px;line-height:1.5}.dashboard-head>.button{display:none}.tab{padding:10px 9px;font-size:10px}.customer-welcome,.vendor-welcome,.admin-welcome{align-items:flex-start;flex-direction:column;padding:21px}.customer-welcome h2,.vendor-welcome h2,.admin-welcome h2{font-size:20px}.customer-welcome .button,.vendor-welcome .button,.admin-welcome .button{width:100%}.customer-highlights{gap:8px}.customer-highlight{padding:13px}.customer-highlight strong{font-size:20px}.recommendation-panel{padding:14px}.recommendation-list{grid-template-columns:1fr}.recommendation-heading{align-items:flex-start}.vendor-profile-account,.vendor-profile-store{padding:16px}.vendor-store-profile{align-items:flex-start;flex-direction:column}.merchant-grid{grid-template-columns:1fr}.merchant-stat{min-height:88px}.admin-stats{grid-template-columns:repeat(2,1fr)}.admin-application{align-items:flex-start;flex-direction:column}.admin-queue,.admin-sidecard,.merchant-card{padding:16px}.merchant-vendor-row{align-items:stretch;flex-direction:column}.merchant-row-actions{width:100%;flex-direction:column;align-items:stretch}.merchant-payment-state{text-align:left}.vendor-requirements-update{width:100%;max-width:100%}.application-requirements .requirement-fields{grid-template-columns:1fr}.application-requirements .requirement-field:last-child{grid-column:auto}
            .discover-toolbar{gap:7px}.select-filter{flex:1}.nearby-button{width:100%}.vendor-card{grid-template-columns:60px minmax(0,1fr) auto;gap:9px}.vendor-image-wrap{width:60px;height:60px}.vendor-image{width:60px;height:60px}.vendor-action{padding:7px}
            .footer-inner{flex-direction:column}
            body.dashboard-mode .topbar{height:66px;padding:0 15px}
            body.dashboard-mode #dashboard{width:calc(100% - 28px);padding-top:25px}
            body.dashboard-mode #dashboard .dashboard-head{align-items:flex-start;flex-wrap:wrap}
            body.dashboard-mode #dashboard .dashboard-head>div:first-child{flex:1 1 100%;min-width:0}
            body.dashboard-mode #dashboard .dashboard-profile-bar{gap:16px;padding:16px}
            body.dashboard-mode #dashboard .dashboard-profile-identity{width:100%}
            body.dashboard-mode #dashboard .profile-wrap{min-width:0;flex:1}
            body.dashboard-mode #dashboard .profile-button{max-width:100%}
            body.dashboard-mode #dashboard .profile-button .profile-details{min-width:0}
            body.dashboard-mode #dashboard .profile-button img,body.dashboard-mode #dashboard .profile-avatar{width:68px;height:68px;border-radius:16px;font-size:24px}
            body.dashboard-mode #dashboard .profile-button .profile-name{font-size:17px}
            body.dashboard-mode #dashboard .profile-menu{top:82px}
            body.dashboard-mode #dashboard .dashboard-profile-form{grid-template-columns:minmax(0,1fr)}
            body.dashboard-mode #dashboard .dashboard-profile-form .button{justify-self:start}
            body.dashboard-mode #dashboard .dashboard-profile-actions{display:none}
            body.dashboard-mode #dashboard #dashboardReturnButton,body.dashboard-mode #dashboard #merchantCta{display:none!important}
            body.dashboard-mode #dashboard .dashboard-sidebar-nav{max-width:100%;scrollbar-width:none}
            body.dashboard-mode #dashboard .dashboard-sidebar-nav::-webkit-scrollbar{display:none}
        }
        body.landing-mode{background:#10130f;color:#f5f2e9}
        body.landing-mode .topbar{width:100%;height:76px;margin:0;padding:0 max(28px,calc((100vw - 1180px)/2));border-bottom:1px solid rgba(255,255,255,.1);background:rgba(16,19,15,.94);color:#f7f5ee}
        body.landing-mode .topbar.is-scrolled{background:rgba(16,19,15,.97);box-shadow:0 8px 30px rgba(0,0,0,.2)}
        body.landing-mode .brand-mark{background:#d6d4b2;color:#172019;box-shadow:none}
        body.landing-mode .nav-links{color:#c3c3b9}
        body.landing-mode .nav-links a:hover,body.landing-mode .text-button{color:#f3f0e7}
        body.landing-mode .nav-links a:after{background:#c6ca9b}
        body.landing-mode .button-primary{background:#d8d6b8;color:#171c16;box-shadow:none}
        body.landing-mode .button-primary:hover{background:#eee9d1}
        body.landing-mode .button-light{border-color:rgba(255,255,255,.28);background:rgba(255,255,255,.07);color:#f5f2e9}
        body.landing-mode .hero{width:100%;max-width:none;min-height:clamp(760px,calc(100svh - 76px),960px);margin:0;display:flex;align-items:center;gap:0;padding:0;background:#161a14}
        body.landing-mode .hero:before{z-index:1;inset:0;background:linear-gradient(90deg,rgba(9,13,10,.91) 0%,rgba(12,17,12,.83) 34%,rgba(13,17,12,.38) 68%,rgba(13,17,12,.2) 100%),linear-gradient(0deg,rgba(10,14,11,.74),transparent 39%),linear-gradient(180deg,rgba(10,14,11,.16),transparent 22%)}
        body.landing-mode .hero-copy-column{position:relative;z-index:3;width:min(1180px,calc(100% - 56px));max-width:none;margin:0 auto;padding:76px 0 44px;animation:hero-rise .75s both}
        body.landing-mode .hero .eyebrow{color:#d2d3b8;font-size:10px;letter-spacing:2px}
        body.landing-mode .eyebrow-dot{background:#d4d5ad;box-shadow:0 0 0 4px rgba(212,213,173,.12)}
        body.landing-mode .hero h1{max-width:950px;margin:23px 0 22px;color:#f1f0e8;font:400 clamp(50px,7.8vw,104px)/.98 var(--display);letter-spacing:-.075em;text-transform:uppercase}
        body.landing-mode .hero h1 em{background:none;color:#d4d4b4;font-style:normal;font-weight:300}
        body.landing-mode .hero-copy{max-width:480px;margin-bottom:24px;color:rgba(238,239,228,.76);font-size:14px;line-height:1.8}
        body.landing-mode .hero-search{max-width:490px;min-height:57px;border-color:rgba(255,255,255,.22);border-radius:4px;background:rgba(244,242,230,.94);box-shadow:0 12px 40px rgba(0,0,0,.2)}
        body.landing-mode .hero-search:focus-within{border-color:#d4d4b4;box-shadow:0 14px 42px rgba(0,0,0,.28)}
        body.landing-mode .hero-search-icon{color:#616c5e}
        body.landing-mode .hero-search input{color:#20271f}
        body.landing-mode .hero-search .button{border-radius:2px;background:#29372b;color:#f5f2e9}
        body.landing-mode .hero-search .button:hover{background:#405442}
        body.landing-mode .hero-search-chips{color:rgba(238,239,228,.68)}
        body.landing-mode .hero-search-chips strong{color:#e2e0cb;letter-spacing:1.4px}
        body.landing-mode .hero-chip{border-color:rgba(242,241,226,.25);border-radius:2px;background:rgba(238,238,220,.08);color:#f0efe4}
        body.landing-mode .hero-chip:hover{border-color:#d2d3b8;background:rgba(210,211,184,.2);color:#fff}
        body.landing-mode .hero-actions{gap:10px;margin-top:21px}
        body.landing-mode .hero-actions .button{min-height:40px;border-radius:2px;font-size:11px;letter-spacing:.2px}
        body.landing-mode .hero-note{margin-top:23px;color:rgba(238,239,228,.65)}
        body.landing-mode .avatar-stack img{border-color:#182019}
        body.landing-mode .hero-note-copy strong{color:#f0efe5}
        body.landing-mode .hero-note-copy small{color:rgba(238,239,228,.59)}
        body.landing-mode .hero-bottom-row{max-width:490px;margin-top:28px;padding-top:17px;border-top-color:rgba(245,243,229,.22)}
        body.landing-mode .hero-stat{border-right-color:rgba(245,243,229,.18)}
        body.landing-mode .hero-stat strong{color:#e1dfc2;font-size:18px}
        body.landing-mode .hero-stat span{color:rgba(238,239,228,.59)}
        body.landing-mode .hero-visual{position:absolute;z-index:0;inset:0;width:100%;height:100%;border-radius:0;background:#1b241b;box-shadow:none;animation:none}
        body.landing-mode .hero-photo{background-image:linear-gradient(180deg,rgba(12,18,12,.04),rgba(12,18,12,.2)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=2000&q=90");background-position:center 52%;transition:transform 1.2s cubic-bezier(.2,.7,.2,1);will-change:transform}
        body.landing-mode .hero-visual:hover .hero-photo{transform:scale(1.025)}
        body.landing-mode .hero-visual:after{z-index:1;background:radial-gradient(360px circle at var(--pointer-x,68%) var(--pointer-y,42%),rgba(232,226,193,.11),transparent 72%)}
        body.landing-mode .hero-visual-top{top:28px;left:auto;right:max(28px,calc((100vw - 1180px)/2));gap:8px}
        body.landing-mode .hero-live-pill,body.landing-mode .hero-area-pill{border-color:rgba(255,255,255,.23);border-radius:2px;background:rgba(19,25,19,.38);color:#f2f0e6;box-shadow:none;font-size:9px;letter-spacing:.8px}
        body.landing-mode .hero-live-pill i{background:#d4d5ad;box-shadow:0 0 0 4px rgba(212,213,173,.13)}
        body.landing-mode .hero-area-pill{color:rgba(242,240,230,.8)}
        body.landing-mode .hero-photo-caption{left:auto;right:max(28px,calc((100vw - 1180px)/2));bottom:124px;color:#f2f0e6;font-size:9px;letter-spacing:1.2px;text-transform:uppercase}
        body.landing-mode .hero-market-card{right:max(28px,calc((100vw - 1180px)/2));bottom:32px;left:auto;width:min(390px,calc(100% - 56px));gap:14px;padding:15px 16px;border-color:rgba(255,255,255,.2);border-radius:2px;background:rgba(18,24,18,.8);color:#f5f2e9;box-shadow:0 12px 45px rgba(0,0,0,.24);backdrop-filter:blur(16px)}
        body.landing-mode .hero-market-card:hover{box-shadow:0 16px 48px rgba(0,0,0,.3)}
        body.landing-mode .hero-market-kicker{color:#d4d4b4;font-size:8px;letter-spacing:1.3px}
        body.landing-mode .hero-market-card strong{font-size:14px;letter-spacing:-.2px}
        body.landing-mode .hero-market-card small{color:rgba(242,240,230,.62);font-size:9px}
        body.landing-mode .hero-market-cta{border-radius:2px;background:#d8d6b8;color:#1a211a}
        body.landing-mode .hero-market-card:hover .hero-market-cta{background:#eee9d1}
        body.landing-mode #how{width:100%;max-width:none;padding:92px max(28px,calc((100vw - 1180px)/2)) 96px;background:#111510;color:#f5f2e9}
        body.landing-mode #how .section-heading{max-width:1180px;margin:0 auto 31px}
        body.landing-mode #how .eyebrow{color:#c9c9aa;font-size:9px;letter-spacing:1.9px}
        body.landing-mode #how .section-heading h2{max-width:700px;margin-top:12px;color:#f2f0e7;font-size:clamp(28px,4vw,44px);font-weight:500;letter-spacing:-1.7px}
        body.landing-mode #how .section-heading p{color:rgba(238,239,228,.6);font-size:12px}
        body.landing-mode .how-grid{max-width:1180px;margin:auto;grid-template-columns:1.12fr .88fr .88fr;gap:12px}
        body.landing-mode .how-card{min-height:330px;display:flex;flex-direction:column;align-items:flex-start;justify-content:flex-end;padding:22px;border:1px solid rgba(240,240,225,.18);border-radius:2px;background:#20271f;isolation:isolate}
        body.landing-mode .how-card:before{content:"";position:absolute;z-index:-2;inset:0;background-position:center;background-size:cover;opacity:.68;transition:transform .7s cubic-bezier(.2,.7,.2,1),opacity .35s}
        body.landing-mode .how-card:after{top:17px;right:18px;color:rgba(243,241,228,.72);font-size:26px;font-weight:500;letter-spacing:0}
        body.landing-mode .how-card:nth-child(1):before{background-image:linear-gradient(180deg,rgba(12,17,12,.04) 5%,rgba(12,17,12,.9) 100%),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=950&q=85")}
        body.landing-mode .how-card:nth-child(2):before{background-image:linear-gradient(180deg,rgba(12,17,12,.04) 5%,rgba(12,17,12,.9) 100%),url("https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=950&q=85")}
        body.landing-mode .how-card:nth-child(3):before{background-image:linear-gradient(180deg,rgba(12,17,12,.04) 5%,rgba(12,17,12,.9) 100%),url("https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?auto=format&fit=crop&w=950&q=85")}
        body.landing-mode .how-card:hover:before{transform:scale(1.045);opacity:.85}
        body.landing-mode .how-icon{width:35px;height:35px;margin-bottom:auto;border:1px solid rgba(255,255,255,.27);border-radius:2px;background:rgba(20,26,20,.32);color:#f1efdc;font-size:15px;backdrop-filter:blur(8px)}
        body.landing-mode .how-card h3{margin:0 0 7px;color:#f5f2e9;font-size:17px;font-weight:500;letter-spacing:-.3px}
        body.landing-mode .how-card p{max-width:270px;margin:0;color:rgba(245,243,232,.7);font-size:10px;line-height:1.7}
        body.landing-mode .market-band{background:linear-gradient(90deg,rgba(13,18,14,.93),rgba(13,18,14,.72)),url("https://images.unsplash.com/photo-1488459716781-31db52582fe9?auto=format&fit=crop&w=1800&q=85") center 52%/cover}
        body.landing-mode .market-band:before{right:12%;top:-310px;border-color:#ffffff20;box-shadow:0 0 0 34px #ffffff07,0 0 0 70px #ffffff05}
        body.landing-mode .trust-row{width:min(1180px,calc(100% - 56px));grid-template-columns:minmax(190px,1.4fr) repeat(3,minmax(90px,.55fr)) auto;padding:35px 0}
        body.landing-mode .trust-row>strong{max-width:240px;color:#f3f1e8;font-size:14px;line-height:1.5}
        body.landing-mode .trust-stat strong{color:#dedbb7}
        body.landing-mode .trust-stat span{color:rgba(242,240,230,.68)}
        body.landing-mode .trust-row .button{border:1px solid rgba(255,255,255,.3);border-radius:2px;background:rgba(245,243,231,.08);color:#f5f2e9}
        body.landing-mode .trust-row .button:hover{background:rgba(245,243,231,.17)}
        body.landing-mode #marketInfo{width:100%;max-width:none;margin:0;padding:62px max(28px,calc((100vw - 1180px)/2));background:#111510;color:#f5f2e9}
        body.landing-mode #marketInfo .section-heading{max-width:1180px;margin:0 auto}
        body.landing-mode #marketInfo .eyebrow{color:#c9c9aa;font-size:9px;letter-spacing:1.7px}
        body.landing-mode #marketInfo h2{color:#f2f0e7;font-size:clamp(27px,4vw,40px);font-weight:500;letter-spacing:-1.5px}
        body.landing-mode #marketInfo p{color:rgba(238,239,228,.6)}
        body.landing-mode #marketInfo p+p{margin-top:-12px}
        body.landing-mode #marketInfo .button{border:1px solid rgba(255,255,255,.25);border-radius:2px;background:#d8d6b8;color:#171c16}
        body.landing-mode .footer{border-top-color:rgba(255,255,255,.12);background:#111510;color:rgba(238,239,228,.55)}
        @media(max-width:820px){
            body.landing-mode .hero{min-height:780px}
            body.landing-mode .hero:before{background:linear-gradient(90deg,rgba(9,13,10,.87),rgba(12,17,12,.46)),linear-gradient(0deg,rgba(10,14,11,.78),transparent 56%)}
            body.landing-mode .hero-copy-column{width:min(620px,calc(100% - 36px));padding:66px 0 39px}
            body.landing-mode .hero h1{font-size:clamp(58px,9vw,78px)}
            body.landing-mode .hero-visual-top{right:18px}
            body.landing-mode .hero-area-pill{display:none}
            body.landing-mode .hero-photo-caption{right:18px}
            body.landing-mode .hero-market-card{right:18px}
            body.landing-mode #how{padding:66px 18px 70px}
            body.landing-mode .how-grid{grid-template-columns:1fr 1fr}
            body.landing-mode .how-card:first-child{grid-column:1/-1;min-height:290px}
            body.landing-mode .how-card{min-height:250px}
            body.landing-mode .trust-row{width:min(620px,calc(100% - 36px))}
            body.landing-mode #marketInfo{padding:48px 18px}
        }
        @media(max-width:480px){
            body.landing-mode .topbar{height:66px;padding:0 15px}
            body.landing-mode .brand{gap:7px;font-size:19px}
            body.landing-mode .brand-mark{width:32px;height:32px;border-radius:9px}
            body.landing-mode .nav-actions{gap:6px}
            body.landing-mode .text-button{padding:7px;font-size:11px}
            body.landing-mode .nav-actions .button{padding:9px 10px;font-size:10px}
            body.landing-mode .hero{min-height:790px}
            body.landing-mode .hero:before{background:linear-gradient(90deg,rgba(9,13,10,.82),rgba(12,17,12,.48)),linear-gradient(0deg,rgba(10,14,11,.86),rgba(10,14,11,.08) 70%)}
            body.landing-mode .hero-copy-column{width:calc(100% - 32px);padding:51px 0 36px}
            body.landing-mode .hero .eyebrow{font-size:8px;letter-spacing:1.45px}
            body.landing-mode .hero h1{font-size:clamp(42px,10.5vw,54px);letter-spacing:-.065em;line-height:.98}
            body.landing-mode .hero-copy{max-width:360px;font-size:12px}
            body.landing-mode .hero-search{min-height:53px;padding:5px 5px 5px 10px;gap:4px}
            body.landing-mode .hero-search input{font-size:10px}
            body.landing-mode .hero-search .button{min-height:41px;padding:0 9px;font-size:10px}
            body.landing-mode .hero-search-chips{gap:5px}
            body.landing-mode .hero-search-chips strong{width:100%;font-size:8px}
            body.landing-mode .hero-chip{padding:6px 8px;font-size:9px}
            body.landing-mode .hero-actions{gap:7px}
            body.landing-mode .hero-actions .button{padding:10px;font-size:9px}
            body.landing-mode .hero-note{margin-top:16px}
            body.landing-mode .hero-bottom-row{margin-top:20px;padding-top:13px}
            body.landing-mode .hero-stat{padding:0 8px}
            body.landing-mode .hero-stat strong{font-size:16px}
            body.landing-mode .hero-stat span{font-size:8px}
            body.landing-mode .hero-visual-top{top:15px;left:16px;right:16px}
            body.landing-mode .hero-live-pill{padding:7px 9px;font-size:8px}
            body.landing-mode .hero-photo-caption{display:none}
            body.landing-mode .hero-market-card{right:14px;bottom:14px;left:14px;width:auto;padding:12px}
            body.landing-mode .hero-market-card strong{font-size:12px}
            body.landing-mode .hero-market-card small{font-size:8px}
            body.landing-mode #how{padding:53px 14px 58px}
            body.landing-mode #how .section-heading{margin-bottom:22px}
            body.landing-mode #how .section-heading h2{font-size:29px}
            body.landing-mode .how-grid{grid-template-columns:1fr;gap:9px}
            body.landing-mode .how-card:first-child,body.landing-mode .how-card{grid-column:auto;min-height:215px;padding:18px}
            body.landing-mode .how-card h3{font-size:15px}
            body.landing-mode .trust-row{width:calc(100% - 32px);grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:26px 0}
            body.landing-mode .trust-row>strong{grid-column:1/-1}
            body.landing-mode .trust-row .button{grid-column:1/-1;width:100%}
            body.landing-mode #marketInfo{padding:43px 16px}
            body.landing-mode #marketInfo .section-heading{align-items:flex-start}
        }
        body.dashboard-mode #dashboard .dashboard-nav-button,
        body.dashboard-mode #dashboard .tab,
        body.dashboard-mode #dashboard .profile-button,
        body.dashboard-mode #dashboard .profile-menu button,
        body.dashboard-mode #dashboard .vendor-action,
        body.dashboard-mode #dashboard .vendor-message-action,
        body.dashboard-mode #dashboard .message-contact-action:not(:disabled),
        body.dashboard-mode #dashboard .merchant-edit-button,
        body.dashboard-mode #dashboard .merchant-payment-button,
        body.dashboard-mode #dashboard .merchant-payment-check,
        body.dashboard-mode #dashboard .admin-document-links a,
        body.dashboard-mode #dashboard .vendor-requirements-update summary,
        body.dashboard-mode #dashboard .admin-checklist li,
        body.dashboard-mode #dashboard .customer-highlight,
        body.dashboard-mode #dashboard .merchant-stat,
        body.dashboard-mode #dashboard .admin-stat{
            transition:color .18s ease,background-color .18s ease,border-color .18s ease,box-shadow .18s ease,transform .18s ease;
        }
        body.dashboard-mode #dashboard .profile-button{border-radius:12px;padding:5px;margin:-5px}
        body.dashboard-mode #dashboard .profile-button:hover{background:#ffffff08}
        body.dashboard-mode #dashboard .tab:not(.active):hover{background:#ffffff08;color:#e8e5d4}
        body.dashboard-mode #dashboard .dashboard-nav-button:not(.active):hover{transform:translateX(3px)}
        body.dashboard-mode #dashboard .customer-highlight:hover,
        body.dashboard-mode #dashboard .merchant-stat:hover,
        body.dashboard-mode #dashboard .admin-stat:hover{transform:translateY(-3px);border-color:#777d5f;box-shadow:0 13px 30px #0003}
        body.dashboard-mode #dashboard .admin-checklist li{border-radius:6px}
        body.dashboard-mode #dashboard .admin-checklist li:hover{transform:translateX(3px);background:#ffffff06}
        body.dashboard-mode #dashboard .admin-document-links a:hover{transform:translateY(-2px);border-color:#777d5f;background:#2a3229}
        body.dashboard-mode #dashboard .vendor-requirements-update summary:hover{color:#f0edcf}
        body.dashboard-mode #dashboard .vendor-card,
        body.dashboard-mode #dashboard .conversation-row,
        body.dashboard-mode #dashboard .recommendation-card{transition:background-color .18s ease,border-color .18s ease,box-shadow .18s ease,transform .18s ease}
        body.dashboard-mode #dashboard .vendor-card:hover,
        body.dashboard-mode #dashboard .conversation-row:hover{box-shadow:inset 3px 0 #d4d4b4}
        body.dashboard-mode #dashboard .recommendation-card:hover{transform:translateY(-2px);box-shadow:0 8px 20px #0003}
        body.dashboard-mode #dashboard .button:not(:disabled):active,
        body.dashboard-mode #dashboard button:not(:disabled):not(.profile-button):active{transform:scale(.98)}
        body.dashboard-mode #dashboard button:focus-visible,
        body.dashboard-mode #dashboard a:focus-visible,
        body.dashboard-mode #dashboard summary:focus-visible,
        body.dashboard-mode .topbar button:focus-visible,
        body.dashboard-mode .topbar a:focus-visible{
            outline:2px solid #d8d6b8;outline-offset:3px
        }
        body.dashboard-mode #dashboard input:focus-visible,
        body.dashboard-mode #dashboard select:focus-visible,
        body.dashboard-mode #dashboard textarea:focus-visible{outline:2px solid #d8d6b8;outline-offset:2px}
        body.landing-mode{background:#f6f7f2;color:#253127}
        body.landing-mode .topbar,body.landing-mode .topbar.is-scrolled{border-color:#e4e9e0;background:rgba(255,255,255,.94);color:#253127;box-shadow:0 6px 22px rgba(32,43,37,.06)}
        body.landing-mode .brand-mark{background:#315b43;color:#fff}
        body.landing-mode .nav-links,body.landing-mode .nav-links a:hover,body.landing-mode .text-button{color:#536257}
        body.landing-mode .nav-links a:after{background:#8caf5d}
        body.landing-mode .button-primary,body.landing-mode .button-primary:hover{background:#315b43;color:#fff}
        body.landing-mode .button-light{border-color:#dfe6dc;background:#fff;color:#315b43}
        body.landing-mode .hero{background:#e8eee4}
        body.landing-mode .hero:before{background:linear-gradient(90deg,rgba(248,250,246,.97) 0%,rgba(248,250,246,.91) 38%,rgba(248,250,246,.65) 67%,rgba(248,250,246,.35) 100%),linear-gradient(0deg,rgba(248,250,246,.45),rgba(248,250,246,.04) 65%)}
        body.landing-mode .hero .eyebrow{color:#526b52}
        body.landing-mode .hero .eyebrow-dot{background:#789657;box-shadow:0 0 0 4px rgba(120,150,87,.16)}
        body.landing-mode .hero h1{color:#24362a}
        body.landing-mode .hero h1 em{color:#5d7c42}
        body.landing-mode .hero-copy{color:#59675c}
        body.landing-mode .hero-search{border-color:#dfe6dc;background:#fff;box-shadow:0 12px 30px rgba(32,43,37,.1)}
        body.landing-mode .hero-search:focus-within{border-color:#9eb595;box-shadow:0 14px 36px rgba(49,91,67,.16)}
        body.landing-mode .hero-search-icon{color:#647565}
        body.landing-mode .hero-search input{color:#202b25}
        body.landing-mode .hero-search .button{background:#315b43;color:#fff}
        body.landing-mode .hero-search-chips,body.landing-mode .hero-search-chips strong{color:#657366}
        body.landing-mode .hero-chip,body.landing-mode .hero-chip:hover{border-color:#dfe6dc;background:#fff;color:#405741}
        body.landing-mode .hero-note,body.landing-mode .hero-note-copy small{color:#69766a}
        body.landing-mode .hero-note-copy strong{color:#304c38}
        body.landing-mode .avatar-stack img{border-color:#f6f7f2}
        body.landing-mode .hero-bottom-row{border-top-color:#d7e0d4}
        body.landing-mode .hero-stat{border-right-color:#d7e0d4}
        body.landing-mode .hero-stat strong{color:#315b43}
        body.landing-mode .hero-stat span{color:#718071}
        body.landing-mode .hero-visual{background:#e5eddd}
        body.landing-mode .hero-photo{background-image:linear-gradient(180deg,rgba(248,250,246,.05),rgba(248,250,246,.12)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=2000&q=90")}
        body.landing-mode .hero-live-pill,body.landing-mode .hero-area-pill{border-color:#e1e7dd;background:rgba(255,255,255,.82);color:#415344;box-shadow:0 4px 14px rgba(32,43,37,.08)}
        body.landing-mode .hero-live-pill i{background:#71934b;box-shadow:0 0 0 4px rgba(113,147,75,.16)}
        body.landing-mode .hero-area-pill{color:#637064}
        body.landing-mode .hero-photo-caption{color:#fff;text-shadow:0 2px 12px rgba(0,0,0,.5)}
        body.landing-mode .hero-market-card{border-color:#e4e9e0;background:rgba(255,255,255,.95);color:#253127;box-shadow:0 14px 35px rgba(32,43,37,.14)}
        body.landing-mode .hero-market-card:hover{box-shadow:0 18px 42px rgba(32,43,37,.18)}
        body.landing-mode .hero-market-kicker{color:#71864f}
        body.landing-mode .hero-market-card small{color:#6e796f}
        body.landing-mode .hero-market-cta,body.landing-mode .hero-market-card:hover .hero-market-cta{background:#315b43;color:#fff}
        body.landing-mode #how,body.landing-mode #marketInfo{background:#f6f7f2;color:#253127}
        body.landing-mode #how .eyebrow{color:#657c4d}
        body.landing-mode #how .section-heading h2{color:#253127}
        body.landing-mode #how .section-heading p{color:#6f7b70}
        body.landing-mode .how-card{border-color:#e1e7dd;border-radius:14px;background:#fff;box-shadow:0 8px 22px rgba(32,43,37,.04)}
        body.landing-mode .how-card>*{position:relative;z-index:1}
        body.landing-mode .how-card:before{z-index:0;opacity:.7}
        body.landing-mode .how-card:nth-child(1):before{background-image:linear-gradient(180deg,rgba(248,250,246,.04) 5%,rgba(248,250,246,.88) 100%),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=950&q=85")}
        body.landing-mode .how-card:nth-child(2):before{background-image:linear-gradient(180deg,rgba(248,250,246,.04) 5%,rgba(248,250,246,.88) 100%),url("https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?auto=format&fit=crop&w=950&q=85")}
        body.landing-mode .how-card:nth-child(3):before{background-image:linear-gradient(180deg,rgba(248,250,246,.04) 5%,rgba(248,250,246,.88) 100%),url("https://images.unsplash.com/photo-1555529669-e69e7aa0ba9a?auto=format&fit=crop&w=950&q=85")}
        body.landing-mode .how-card:after{color:#e8eee4}
        body.landing-mode .how-icon{border-color:#e1e9dc;background:#edf3e8;color:#315b43}
        body.landing-mode .how-card h3{color:#253127}
        body.landing-mode .how-card p{color:#6a766b}
        body.landing-mode .market-band{background:#e9efe5;color:#314334}
        body.landing-mode .market-band:before{border-color:#cdd9c8;box-shadow:0 0 0 34px rgba(205,217,200,.25),0 0 0 70px rgba(205,217,200,.16)}
        body.landing-mode .trust-row,body.landing-mode .trust-row>strong,body.landing-mode .trust-stat strong{color:#315b43}
        body.landing-mode .trust-stat span{color:#69766a}
        body.landing-mode .trust-row .button,body.landing-mode .trust-row .button:hover{border-color:#315b43;background:#315b43;color:#fff}
        body.landing-mode .footer{border-color:#e1e7dd;color:#748075}
        body.dashboard-mode{background:#f4f6f1;color:#253127}
        body.dashboard-mode .topbar,body.dashboard-mode .topbar.is-scrolled{border-color:#e1e7dd;background:rgba(255,255,255,.96);color:#253127;box-shadow:0 6px 22px rgba(32,43,37,.06)}
        body.dashboard-mode .brand-mark{background:#315b43;color:#fff}
        body.dashboard-mode .nav-links,body.dashboard-mode .nav-links a:hover,body.dashboard-mode .text-button{color:#536257}
        body.dashboard-mode .nav-links a:after{background:#8caf5d}
        body.dashboard-mode .button-primary,body.dashboard-mode .button-primary:hover{background:#315b43;color:#fff}
        body.dashboard-mode .button-light{border-color:#dfe6dc;background:#fff;color:#315b43}
        body.dashboard-mode #dashboard,body.dashboard-mode #dashboard.vendor-dashboard,body.dashboard-mode #dashboard.admin-dashboard{--ink:#253127;--muted:#6d786e;--green:#52764d;--green-dark:#3f6240;--lime:#e8efe2;--line:#e1e7dd;--paper:#f6f7f2;color:#253127}
        body.dashboard-mode #dashboard .dashboard-head{border-color:#e1e7dd}
        body.dashboard-mode #dashboard .dashboard-head h1,body.dashboard-mode #dashboard .profile-button .profile-name,body.dashboard-mode #dashboard .dashboard-profile-form label{color:#253127}
        body.dashboard-mode #dashboard .dashboard-head p,body.dashboard-mode #dashboard .profile-button .profile-email,body.dashboard-mode #dashboard .dashboard-profile-feedback{color:#6d786e}
        body.dashboard-mode #dashboard .eyebrow{color:#59764d}
        body.dashboard-mode #dashboard .eyebrow-dot{background:#789657;box-shadow:0 0 0 4px rgba(120,150,87,.16)}
        body.dashboard-mode #dashboard .dashboard-profile-bar{border-color:#e1e7dd;background:#fff}
        body.dashboard-mode #dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.admin-dashboard .dashboard-sidebar{border-color:#e1e7dd;border-radius:15px;background:#edf2e9;color:#344536;box-shadow:0 10px 28px rgba(32,43,37,.07)}
        body.dashboard-mode #dashboard .dashboard-brand{color:#253127}
        body.dashboard-mode #dashboard .dashboard-brand-mark{border-radius:8px;background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .dashboard-sidebar-label{color:#758371}
        body.dashboard-mode #dashboard .dashboard-nav-button{color:#516052}
        body.dashboard-mode #dashboard .dashboard-nav-button:hover{background:#e1e9dc;color:#2f4d34}
        body.dashboard-mode #dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.admin-dashboard .dashboard-nav-button.active{background:#dce8d4;color:#315b43;box-shadow:none}
        body.dashboard-mode #dashboard .dashboard-sidebar-foot{border-color:#dce4d8;color:#748071}
        body.dashboard-mode #dashboard .notification-button,body.dashboard-mode #dashboard .notification-panel,body.dashboard-mode #dashboard .notification-item,body.dashboard-mode #dashboard .profile-menu{border-color:#e1e7dd;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .notification-heading{border-color:#e1e7dd}
        body.dashboard-mode #dashboard .notification-heading button{color:#315b43}
        body.dashboard-mode #dashboard .notification-item:hover,body.dashboard-mode #dashboard .notification-item.unread,body.dashboard-mode #dashboard .profile-menu button:hover{background:#f1f5ee}
        body.dashboard-mode #dashboard .notification-item span,body.dashboard-mode #dashboard .notification-item time{color:#6d786e}
        body.dashboard-mode #dashboard .customer-welcome,body.dashboard-mode #dashboard .vendor-welcome,body.dashboard-mode #dashboard .admin-welcome{border:1px solid #e1e7dd;border-radius:14px;background:linear-gradient(115deg,#eaf1e4,#f6f7f2);color:#253127}
        body.dashboard-mode #dashboard .customer-welcome .eyebrow,body.dashboard-mode #dashboard .vendor-welcome .eyebrow,body.dashboard-mode #dashboard .admin-welcome .eyebrow{color:#58764b}
        body.dashboard-mode #dashboard .customer-welcome h2,body.dashboard-mode #dashboard .vendor-welcome h2,body.dashboard-mode #dashboard .admin-welcome h2{color:#253127;font-weight:700}
        body.dashboard-mode #dashboard .customer-welcome p,body.dashboard-mode #dashboard .vendor-welcome p,body.dashboard-mode #dashboard .admin-welcome p{color:#657264}
        body.dashboard-mode #dashboard .customer-welcome .button,body.dashboard-mode #dashboard .vendor-welcome .button,body.dashboard-mode #dashboard .admin-welcome .button{border-radius:9px;background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .customer-highlight,body.dashboard-mode #dashboard .vendor-panel,body.dashboard-mode #dashboard .map-panel,body.dashboard-mode #dashboard .booking-section,body.dashboard-mode #dashboard .message-inbox,body.dashboard-mode #dashboard .vendor-profile-card,body.dashboard-mode #dashboard .merchant-card,body.dashboard-mode #dashboard .merchant-stat,body.dashboard-mode #dashboard .admin-stat,body.dashboard-mode #dashboard .admin-queue,body.dashboard-mode #dashboard .admin-sidecard{border-color:#e1e7dd;border-radius:13px;background:#fff;color:#253127;box-shadow:0 8px 24px rgba(32,43,37,.045)}
        body.dashboard-mode #dashboard .dashboard-tabs{border-color:#e1e7dd}
        body.dashboard-mode #dashboard .tab{color:#6d786e}
        body.dashboard-mode #dashboard .tab.active{border-color:#52764d;color:#315b43}
        body.dashboard-mode #dashboard .search-wrap input,body.dashboard-mode #dashboard .select-filter{border-color:#dfe6dc;border-radius:9px;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .search-wrap input:focus,body.dashboard-mode #dashboard .select-filter:focus{border-color:#9eb595}
        body.dashboard-mode #dashboard .search-symbol{color:#718071}
        body.dashboard-mode #dashboard .vendor-card,body.dashboard-mode #dashboard .booking-row,body.dashboard-mode #dashboard .conversation-row{border-color:#e1e7dd;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .vendor-card:hover,body.dashboard-mode #dashboard .vendor-card.selected,body.dashboard-mode #dashboard .conversation-row:hover{background:#f4f7f1}
        body.dashboard-mode #dashboard .vendor-action,body.dashboard-mode #dashboard .vendor-message-action,body.dashboard-mode #dashboard .status-pill,body.dashboard-mode #dashboard .role-chip,body.dashboard-mode #dashboard .merchant-callout,body.dashboard-mode #dashboard .merchant-callout-icon{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard .panel-header,body.dashboard-mode #dashboard .vendor-profile-account{border-color:#e1e7dd}
        body.dashboard-mode #dashboard .panel-header strong,body.dashboard-mode #dashboard .booking-section h2,body.dashboard-mode #dashboard .message-inbox-heading h2,body.dashboard-mode #dashboard .message-empty strong,body.dashboard-mode #dashboard .admin-queue-heading h3,body.dashboard-mode #dashboard .admin-sidecard h3{color:#253127}
        body.dashboard-mode #dashboard .panel-header span,body.dashboard-mode #dashboard .booking-empty,body.dashboard-mode #dashboard .message-inbox-heading p,body.dashboard-mode #dashboard .message-empty,body.dashboard-mode #dashboard .admin-queue-heading p,body.dashboard-mode #dashboard .admin-sidecard p{color:#6d786e}
        body.dashboard-mode #dashboard .vendor-main h3,body.dashboard-mode #dashboard .customer-highlight strong,body.dashboard-mode #dashboard .admin-stat strong,body.dashboard-mode #dashboard .merchant-stat strong{color:#315b43}
        body.dashboard-mode #dashboard .vendor-profile-account{border-color:#e1e7dd}
        body.dashboard-mode #dashboard .vendor-store-profile{border-color:#e1e7dd;background:#f6f7f2}
        body.dashboard-mode #dashboard .profile-button,body.dashboard-mode #dashboard .profile-menu{border-color:#e1e7dd;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .profile-menu button{color:#253127}
        body.dashboard-mode #dashboard .modal{border:1px solid #e1e7dd;border-radius:14px;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .modal input,body.dashboard-mode #dashboard .modal select,body.dashboard-mode #dashboard .role-form-field select,body.dashboard-mode #dashboard .vendor-account-form input[type="text"],body.dashboard-mode #dashboard .vendor-account-form input[type="file"]{border-color:#dfe6dc;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .modal h2,body.dashboard-mode #dashboard .modal label,body.dashboard-mode #dashboard .role-form-field label{color:#253127}
        body.dashboard-mode #dashboard .modal p,body.dashboard-mode #dashboard .modal-close{color:#6d786e}
        body.dashboard-mode #dashboard .admin-queue-count{background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard .admin-checklist li{border-color:#e7ece4;color:#556457}
        body.dashboard-mode #dashboard .admin-check,body.dashboard-mode #dashboard .admin-empty-icon{background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard .admin-document-links a,body.dashboard-mode #dashboard .merchant-payment-check{border-color:#dfe6dc;background:#f1f5ee;color:#3f6240}
        body.dashboard-mode #dashboard .merchant-payment-button,body.dashboard-mode #dashboard .button-approve{background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .button-reject{border-color:#ead3ce;background:#fff7f5;color:#8c382f}
        body.dashboard-mode #dashboard .chat-messages,body.dashboard-mode #dashboard .chat-start-note,body.dashboard-mode #dashboard .chat-suggestions{background:#f5f7f3}
        body.dashboard-mode #dashboard .chat-form{border-color:#e1e7dd;background:#fff}
        body.dashboard-mode #dashboard .chat-message{border-color:#e1e7dd;background:#f0f3ed;color:#253127}
        body.dashboard-mode #dashboard .chat-message.is-own{border-color:#315b43;background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .chat-form textarea{border-color:#dfe6dc;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .chat-suggestions button{border-color:#dfe6dc;background:#fff;color:#3f6240}
        body.dashboard-mode #dashboard .chat-suggestions button:hover{background:#f1f5ee}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome{min-height:168px;padding:30px 32px;border-color:#dfe6dc;border-radius:14px;background:linear-gradient(110deg,#e6eee0,#f6f7f2);box-shadow:0 10px 28px rgba(32,43,37,.05)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome:after{border-color:#8ba17b30;box-shadow:0 0 0 30px #8ba17b12,0 0 0 60px #8ba17b08}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome p{color:#657264}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome .button{border-radius:9px;background:#315b43;color:#fff;box-shadow:none}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat{border-radius:13px;background:#fff;box-shadow:0 8px 24px rgba(32,43,37,.045)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat:before{background:linear-gradient(180deg,#8ca979,#d7e3cf)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat span{color:#68776a}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat strong{color:#315b43}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat small{color:#798579}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{border-color:#e1e7dd;border-radius:13px;background:#fff;box-shadow:0 8px 24px rgba(32,43,37,.045)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading p,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard p{color:#6d786e}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-count{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application{border-color:#e1e7dd;border-radius:11px;background:#fff}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application:hover{border-color:#cbd9c4;background:#f9fbf8}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application-icon{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application strong{color:#253127}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application small{color:#68776a}
        body.dashboard-mode #dashboard.admin-dashboard .admin-document-links a{background:#f1f5ee;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-payment-state{border-color:#eadfbf;background:#fff8e9;color:#866326}
        body.dashboard-mode #dashboard.admin-dashboard .admin-payment-state.is-paid{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-actions{border-color:#e7ece4}
        body.dashboard-mode #dashboard.admin-dashboard .button-approve:disabled{border-color:#e0e4dc;background:#eef0eb;color:#858d82}
        body.dashboard-mode #dashboard.admin-dashboard .button-reject{border-color:#ead3ce;background:#fff7f5;color:#8c382f}
        body.dashboard-mode #dashboard.admin-dashboard .button-reject:hover{background:#fbece8}
        body.dashboard-mode #dashboard.admin-dashboard .admin-checklist li{border-color:#e7ece4;color:#556457}
        body.dashboard-mode #dashboard.admin-dashboard .admin-check{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-empty-icon{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{border-color:#e1e7dd;border-radius:13px;background:#fff;box-shadow:0 8px 24px rgba(32,43,37,.045)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group h2{color:#253127}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group>p,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo small,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document small{color:#6d786e}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-count{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo{border-color:#e1e7dd;background:#f8faf7;color:#253127}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo img{background:#edf1e9}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document{border-color:#e1e7dd;background:#fff;color:#253127}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document a{background:#edf3e8;color:#315b43}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-empty{border-color:#dfe6dc;color:#6d786e}
        body.dashboard-mode #dashboard .dashboard-nav-button:focus-visible,body.dashboard-mode #dashboard a:focus-visible,body.dashboard-mode #dashboard button:focus-visible{outline-color:#52764d}
        body.dashboard-mode #dashboard input:focus-visible,body.dashboard-mode #dashboard select:focus-visible,body.dashboard-mode #dashboard textarea:focus-visible{outline-color:#52764d}
        body.landing-mode{background:#f5f7f3;color:#243128}
        body.landing-mode .topbar,body.landing-mode .topbar.is-scrolled{height:74px;padding:0 max(32px,calc((100vw - 1240px)/2));border-bottom:1px solid #e6ebe3;background:rgba(255,255,255,.94);box-shadow:0 3px 14px rgba(32,43,37,.035)}
        body.landing-mode .brand{gap:11px;color:#243128;font-size:21px;letter-spacing:-.8px}
        body.landing-mode .brand-mark{width:39px;height:39px;border-radius:12px;background:#315b43;color:#fff;box-shadow:none}
        body.landing-mode .nav-links{gap:30px;color:#5d6a60;font-size:12px}
        body.landing-mode .nav-links a:hover,body.landing-mode .text-button{color:#315b43}
        body.landing-mode .nav-links a:after{height:2px;background:#6c8a58}
        body.landing-mode .nav-actions{gap:14px}
        body.landing-mode .nav-actions .button{min-height:42px;border-radius:9px;padding:0 16px;font-size:11px}
        body.landing-mode .button-primary,body.landing-mode .button-primary:hover{background:#315b43;color:#fff;box-shadow:0 5px 12px rgba(49,91,67,.12)}
        body.landing-mode .button-light{border-color:#dfe6dc;background:#fff;color:#315b43}
        body.landing-mode .hero{position:relative;isolation:isolate;width:min(1240px,calc(100% - 64px));max-width:1240px;min-height:0;margin:27px auto 68px;padding:38px;display:grid;grid-template-columns:minmax(0,.92fr) minmax(0,1.08fr);align-items:center;gap:40px;overflow:hidden;border:1px solid #e1e8de;border-radius:22px;background:linear-gradient(135deg,#fff 0%,#f8faf6 55%,#eef3e9 100%);box-shadow:0 18px 48px rgba(33,50,37,.07)}
        body.landing-mode .hero:before{z-index:-1;inset:auto -85px -195px auto;width:470px;height:470px;border:1px solid rgba(98,130,79,.12);border-radius:50%;background:none;box-shadow:0 0 0 30px rgba(98,130,79,.035),0 0 0 65px rgba(98,130,79,.025)}
        body.landing-mode .hero-copy-column{z-index:2;width:auto;max-width:570px;margin:0;padding:7px 0;animation:none}
        body.landing-mode .hero .eyebrow{gap:10px;color:#617d4d;font-size:9px;letter-spacing:1.55px}
        body.landing-mode .hero h1{max-width:570px;margin:20px 0 17px;color:#243128;font-size:clamp(43px,4.7vw,65px);font-weight:750;line-height:1.04;letter-spacing:-3.4px;text-transform:none}
        body.landing-mode .hero h1 em{background:none;color:#557747;font-weight:650}
        body.landing-mode .hero-copy{max-width:470px;margin-bottom:20px;color:#637067;font-size:13px;line-height:1.8}
        body.landing-mode .hero-search{max-width:470px;min-height:55px;gap:9px;padding:5px 6px 5px 14px;border:1px solid #e1e7de;border-radius:11px;background:#fff;box-shadow:0 7px 20px rgba(33,50,37,.055)}
        body.landing-mode .hero-search:focus-within{transform:none;border-color:#9bb28e;box-shadow:0 0 0 3px rgba(111,145,91,.12)}
        body.landing-mode .hero-search input{font-size:12px}
        body.landing-mode .hero-search .button{min-height:43px;border-radius:8px;padding:0 14px;font-size:11px}
        body.landing-mode .hero-search-chips{gap:7px;margin-top:12px;color:#758176;font-size:9px}
        body.landing-mode .hero-search-chips strong{color:#697769;font-size:8px;letter-spacing:1px}
        body.landing-mode .hero-chip,body.landing-mode .hero-chip:hover{padding:6px 10px;border-color:#e4e9e1;border-radius:99px;background:#fff;color:#536553;font-size:9px}
        body.landing-mode .hero-actions{gap:9px;margin-top:17px}
        body.landing-mode .hero-actions .button{min-height:40px;border-radius:8px;padding:0 14px;font-size:10px}
        body.landing-mode .hero-note{gap:10px;margin-top:18px;color:#68766b;font-size:10px}
        body.landing-mode .avatar-stack img{border-color:#f8faf6}
        body.landing-mode .hero-note-copy strong{color:#3f5744;font-size:10px}
        body.landing-mode .hero-note-copy small{color:#7d897f;font-size:9px}
        body.landing-mode .hero-bottom-row{max-width:470px;margin-top:22px;padding-top:15px;border-top-color:#e2e8df}
        body.landing-mode .hero-stat{border-right-color:#e2e8df}
        body.landing-mode .hero-stat strong{color:#315b43;font-size:17px}
        body.landing-mode .hero-stat span{color:#778378;font-size:8px}
        body.landing-mode .hero-visual{position:relative;z-index:1;inset:auto;grid-column:2;grid-row:1;width:100%;height:clamp(350px,40vw,490px);border:1px solid rgba(255,255,255,.7);border-radius:16px;background:#dce7d7;box-shadow:0 15px 34px rgba(35,52,39,.14);animation:none}
        body.landing-mode .hero-photo{background-image:linear-gradient(180deg,rgba(24,44,28,.02),rgba(24,44,28,.18)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1400&q=85");background-position:center 50%}
        body.landing-mode .hero-visual:hover .hero-photo{transform:scale(1.025)}
        body.landing-mode .hero-live-pill,body.landing-mode .hero-area-pill{min-height:29px;padding:7px 10px;border-color:rgba(255,255,255,.45);border-radius:99px;background:rgba(255,255,255,.91);color:#435848;box-shadow:0 5px 14px rgba(28,43,31,.08);font-size:8px;letter-spacing:.55px}
        body.landing-mode .hero-live-pill i{background:#6f9652;box-shadow:0 0 0 4px rgba(111,150,82,.15)}
        body.landing-mode .hero-area-pill{color:#647366}
        body.landing-mode .hero-photo-caption{color:#fff;font-size:9px;text-shadow:0 2px 12px rgba(0,0,0,.42)}
        body.landing-mode .hero-market-card{right:17px;bottom:17px;left:17px;width:auto;min-height:83px;gap:12px;padding:14px 15px;border-color:rgba(255,255,255,.7);border-radius:11px;background:rgba(255,255,255,.96);color:#26352a;box-shadow:0 8px 24px rgba(26,38,28,.16);backdrop-filter:blur(12px)}
        body.landing-mode .hero-market-card:hover{box-shadow:0 11px 29px rgba(26,38,28,.2)}
        body.landing-mode .hero-market-kicker{color:#678052;font-size:8px;letter-spacing:1px}
        body.landing-mode .hero-market-card strong{font-size:13px}
        body.landing-mode .hero-market-card small{color:#778178;font-size:9px}
        body.landing-mode .hero-market-cta,body.landing-mode .hero-market-card:hover .hero-market-cta{width:34px;height:34px;flex:0 0 34px;border-radius:9px;background:#315b43;color:#fff}
        body.landing-mode #how{width:100%;max-width:none;padding:0 max(32px,calc((100vw - 1240px)/2)) 70px;background:#f5f7f3;color:#243128}
        body.landing-mode #how .section-heading{max-width:1240px;margin:0 auto 24px}
        body.landing-mode #how .eyebrow{color:#617d4d;font-size:9px;letter-spacing:1.45px}
        body.landing-mode #how .section-heading h2{max-width:700px;margin-top:9px;color:#243128;font-size:clamp(27px,3vw,36px);font-weight:700;letter-spacing:-1.3px}
        body.landing-mode #how .section-heading p{color:#6e7a70;font-size:12px}
        body.landing-mode .how-grid{max-width:1240px;margin:auto;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
        body.landing-mode .how-card{min-height:224px;padding:20px;border:1px solid #e2e8df;border-radius:14px;background:#fff;box-shadow:0 7px 20px rgba(33,50,37,.035);transition:transform .2s,border-color .2s,box-shadow .2s}
        body.landing-mode .how-card:hover{transform:translateY(-3px);border-color:#cddbc6;box-shadow:0 13px 28px rgba(33,50,37,.08)}
        body.landing-mode .how-card:before{z-index:0;opacity:.22}
        body.landing-mode .how-card>*{position:relative;z-index:1}
        body.landing-mode .how-card:after{top:15px;right:17px;color:#e3eae0;font-size:25px;font-weight:700}
        body.landing-mode .how-icon{width:39px;height:39px;margin-bottom:28px;border:1px solid #e1e9dd;border-radius:11px;background:#eff4ec;color:#4e7045;font-size:17px}
        body.landing-mode .how-card h3{margin-bottom:7px;color:#2d3b30;font-size:14px;font-weight:700}
        body.landing-mode .how-card p{max-width:290px;color:#6a766c;font-size:10px;line-height:1.7}
        body.landing-mode .market-band{overflow:hidden;border-top:1px solid #e1e8dc;border-bottom:1px solid #e1e8dc;background:#edf2e9;color:#334536}
        body.landing-mode .market-band:before{right:13%;top:-315px;border-color:#cbd9c5;box-shadow:0 0 0 34px rgba(203,217,197,.22),0 0 0 70px rgba(203,217,197,.12)}
        body.landing-mode .trust-row{width:min(1240px,calc(100% - 64px));grid-template-columns:minmax(210px,1.4fr) repeat(3,minmax(90px,.55fr)) auto;gap:22px;padding:27px 0}
        body.landing-mode .trust-row>strong{max-width:250px;color:#315b43;font-size:13px;line-height:1.5}
        body.landing-mode .trust-stat{gap:9px}
        body.landing-mode .trust-stat strong{color:#315b43;font-size:20px}
        body.landing-mode .trust-stat span{color:#677569;font-size:9px}
        body.landing-mode .trust-row .button,body.landing-mode .trust-row .button:hover{min-height:43px;border:0;border-radius:9px;background:#315b43;color:#fff;font-size:10px}
        body.landing-mode #marketInfo{padding:60px max(32px,calc((100vw - 1240px)/2));background:#f5f7f3;color:#243128}
        body.landing-mode #marketInfo .section-heading{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:32px}
        body.landing-mode #marketInfo .section-heading>div{max-width:860px;min-width:0}
        body.landing-mode #marketInfo .eyebrow{color:#607b4e}
        body.landing-mode #marketInfo .section-heading h2{max-width:760px;margin:10px 0 18px;color:#243128;line-height:1.2}
        body.landing-mode #marketInfo .section-heading p{max-width:820px;margin:0;color:#58665b;font-size:14px;line-height:1.75}
        body.landing-mode #marketInfo .section-heading p+p{margin-top:13px}
        body.landing-mode #marketInfo .section-heading .button{align-self:center;min-width:188px;min-height:50px;margin-top:24px;padding:0 18px;justify-content:center;white-space:nowrap;border:0;border-radius:9px;background:#315b43;color:#fff;font-size:11px}
        body.landing-mode #marketInfo .section-heading .button:hover{background:#264b36}
        body.landing-mode .footer{border-color:#e2e8df;color:#768278}
        body.dashboard-mode{background:#f4f6f2;color:#253127}
        body.dashboard-mode .topbar,body.dashboard-mode .topbar.is-scrolled{height:70px;padding:0 max(32px,calc((100vw - 1360px)/2));border-color:#e4e9e2;background:rgba(255,255,255,.96);color:#243128;box-shadow:0 3px 14px rgba(32,43,37,.04)}
        body.dashboard-mode .brand{color:#243128;font-size:20px}
        body.dashboard-mode .brand-mark{background:#315b43;color:#fff}
        body.dashboard-mode .nav-links{color:#58675b}
        body.dashboard-mode .nav-links a:hover,body.dashboard-mode .text-button{color:#315b43}
        body.dashboard-mode .button-primary,body.dashboard-mode .button-primary:hover{background:#315b43;color:#fff}
        body.dashboard-mode .button-light{border-color:#dfe6dc;background:#fff;color:#315b43}
        body.dashboard-mode #dashboard,body.dashboard-mode #dashboard.vendor-dashboard,body.dashboard-mode #dashboard.admin-dashboard{--ink:#253127;--muted:#6d786e;--green:#52764d;--green-dark:#3f6240;--lime:#e8efe2;--line:#e1e7dd;--paper:#f6f7f3;color:#253127}
        body.dashboard-mode #dashboard{width:min(1360px,calc(100% - 64px));padding:28px 0 72px}
        body.dashboard-mode #dashboard .dashboard-layout{grid-template-columns:224px minmax(0,1fr);gap:24px}
        body.dashboard-mode #dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.admin-dashboard .dashboard-sidebar{top:84px;min-height:calc(100dvh - 106px);padding:19px 13px;border:1px solid #e2e8df;border-radius:15px;background:#fff;color:#354638;box-shadow:0 8px 24px rgba(32,43,37,.045)}
        body.dashboard-mode #dashboard .dashboard-brand{padding-bottom:22px;color:#243128}
        body.dashboard-mode #dashboard .dashboard-brand-mark{border-radius:9px;background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .dashboard-sidebar-label{color:#7a877b;font-size:8px;letter-spacing:1.1px}
        body.dashboard-mode #dashboard .dashboard-sidebar-nav{gap:5px}
        body.dashboard-mode #dashboard .dashboard-nav-button{min-height:39px;padding:9px 10px;border-radius:8px;color:#536255;font-size:10px}
        body.dashboard-mode #dashboard .dashboard-nav-button:hover{background:#f3f6f1;color:#34513a}
        body.dashboard-mode #dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.admin-dashboard .dashboard-nav-button.active{background:#eaf1e6;color:#315b43;box-shadow:inset 3px 0 #668857}
        body.dashboard-mode #dashboard .dashboard-sidebar-foot{border-color:#e7ece5;color:#788479}
        body.dashboard-mode #dashboard .dashboard-main{min-width:0}
        body.dashboard-mode #dashboard .dashboard-profile-bar{gap:20px;margin-bottom:20px;padding:17px 19px;border:1px solid #e2e8df;border-radius:13px;background:#fff;box-shadow:0 6px 20px rgba(32,43,37,.035)}
        body.dashboard-mode #dashboard .profile-button .profile-name,body.dashboard-mode #dashboard .dashboard-profile-form label{color:#253127}
        body.dashboard-mode #dashboard .profile-button .profile-email,body.dashboard-mode #dashboard .dashboard-profile-feedback{color:#758176}
        body.dashboard-mode #dashboard .profile-button img,body.dashboard-mode #dashboard .profile-avatar{width:68px;height:68px;border:1px solid #e4e9e2;border-radius:15px;background:#edf3e8}
        body.dashboard-mode #dashboard .dashboard-profile-form input[type="text"],body.dashboard-mode #dashboard .dashboard-profile-form input[type="file"]{border-color:#e0e6de;border-radius:8px;background:#fbfcfa;color:#29372d}
        body.dashboard-mode #dashboard .dashboard-profile-actions .button,body.dashboard-mode #dashboard .notification-button{min-height:36px;border-radius:8px}
        body.dashboard-mode #dashboard .dashboard-head{padding:4px 0 16px;border-color:#e2e8df}
        body.dashboard-mode #dashboard .dashboard-head h1{margin-bottom:5px;color:#243128;font-size:28px;letter-spacing:-.9px}
        body.dashboard-mode #dashboard .dashboard-head p{color:#6d786e;font-size:12px}
        body.dashboard-mode #dashboard .eyebrow{color:#617d4d;font-size:9px;letter-spacing:1.2px}
        body.dashboard-mode #dashboard .eyebrow-dot{background:#789657;box-shadow:0 0 0 4px rgba(120,150,87,.13)}
        body.dashboard-mode #dashboard .customer-welcome,body.dashboard-mode #dashboard .vendor-welcome,body.dashboard-mode #dashboard .admin-welcome{border:1px solid #e1e8de;border-radius:14px;background:linear-gradient(120deg,#edf3e9,#f8faf7);color:#253127}
        body.dashboard-mode #dashboard .customer-welcome h2,body.dashboard-mode #dashboard .vendor-welcome h2,body.dashboard-mode #dashboard .admin-welcome h2{color:#26362b;font-weight:700}
        body.dashboard-mode #dashboard .customer-welcome p,body.dashboard-mode #dashboard .vendor-welcome p,body.dashboard-mode #dashboard .admin-welcome p{color:#667468}
        body.dashboard-mode #dashboard .customer-welcome .button,body.dashboard-mode #dashboard .vendor-welcome .button,body.dashboard-mode #dashboard .admin-welcome .button{border-radius:8px;background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .customer-highlight,body.dashboard-mode #dashboard .vendor-panel,body.dashboard-mode #dashboard .map-panel,body.dashboard-mode #dashboard .booking-section,body.dashboard-mode #dashboard .message-inbox,body.dashboard-mode #dashboard .vendor-profile-card,body.dashboard-mode #dashboard .merchant-card,body.dashboard-mode #dashboard .merchant-stat,body.dashboard-mode #dashboard .admin-stat,body.dashboard-mode #dashboard .admin-queue,body.dashboard-mode #dashboard .admin-sidecard{border-color:#e2e8df;border-radius:12px;background:#fff;color:#253127;box-shadow:0 6px 20px rgba(32,43,37,.035)}
        body.dashboard-mode #dashboard .dashboard-tabs{border-color:#e2e8df}
        body.dashboard-mode #dashboard .tab{color:#69776c}
        body.dashboard-mode #dashboard .tab.active{border-color:#668857;color:#315b43}
        body.dashboard-mode #dashboard .search-wrap input,body.dashboard-mode #dashboard .select-filter{border-color:#dfe6dc;border-radius:8px;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .vendor-card,body.dashboard-mode #dashboard .booking-row,body.dashboard-mode #dashboard .conversation-row{border-color:#e2e8df;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .vendor-card:hover,body.dashboard-mode #dashboard .vendor-card.selected,body.dashboard-mode #dashboard .conversation-row:hover{background:#f5f8f3}
        body.dashboard-mode #dashboard .vendor-main h3,body.dashboard-mode #dashboard .customer-highlight strong,body.dashboard-mode #dashboard .admin-stat strong,body.dashboard-mode #dashboard .merchant-stat strong{color:#315b43}
        body.dashboard-mode #dashboard .panel-header,body.dashboard-mode #dashboard .vendor-profile-account{border-color:#e2e8df}
        body.dashboard-mode #dashboard .panel-header strong,body.dashboard-mode #dashboard .booking-section h2,body.dashboard-mode #dashboard .message-inbox-heading h2,body.dashboard-mode #dashboard .admin-queue-heading h3,body.dashboard-mode #dashboard .admin-sidecard h3{color:#29382d}
        body.dashboard-mode #dashboard .panel-header span,body.dashboard-mode #dashboard .booking-empty,body.dashboard-mode #dashboard .message-inbox-heading p,body.dashboard-mode #dashboard .admin-queue-heading p,body.dashboard-mode #dashboard .admin-sidecard p{color:#6d786e}
        body.dashboard-mode #dashboard .profile-menu,body.dashboard-mode #dashboard .notification-panel,body.dashboard-mode #dashboard .notification-item,body.dashboard-mode #dashboard .modal{border-color:#e2e8df;border-radius:12px;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .profile-menu button{color:#253127}
        body.dashboard-mode #dashboard .profile-menu button:hover,body.dashboard-mode #dashboard .notification-item:hover,body.dashboard-mode #dashboard .notification-item.unread{background:#f3f6f1}
        body.dashboard-mode #dashboard .notification-item span,body.dashboard-mode #dashboard .notification-item time{color:#6d786e}
        body.dashboard-mode #dashboard .notification-heading{border-color:#e2e8df}
        body.dashboard-mode #dashboard .notification-heading button{color:#315b43}
        body.dashboard-mode #dashboard .modal h2,body.dashboard-mode #dashboard .modal label,body.dashboard-mode #dashboard .role-form-field label{color:#253127}
        body.dashboard-mode #dashboard .modal p,body.dashboard-mode #dashboard .modal-close{color:#6d786e}
        body.dashboard-mode #dashboard .modal input,body.dashboard-mode #dashboard .modal select,body.dashboard-mode #dashboard .role-form-field select,body.dashboard-mode #dashboard .vendor-account-form input[type="text"],body.dashboard-mode #dashboard .vendor-account-form input[type="file"]{border-color:#dfe6dc;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .status-pill,body.dashboard-mode #dashboard .role-chip,body.dashboard-mode #dashboard .merchant-callout,body.dashboard-mode #dashboard .merchant-callout-icon{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard .merchant-edit-button,body.dashboard-mode #dashboard .merchant-payment-check,body.dashboard-mode #dashboard .admin-document-links a{border-color:#dfe6dc;border-radius:8px;background:#f4f7f2;color:#3f6240}
        body.dashboard-mode #dashboard .merchant-payment-button,body.dashboard-mode #dashboard .button-approve{border-radius:8px;background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .button-reject{border:1px solid #ead3ce;border-radius:8px;background:#fff7f5;color:#8c382f}
        body.dashboard-mode #dashboard .admin-welcome{min-height:155px;padding:27px 30px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome{border-color:#dfe6dc;border-radius:14px;background:linear-gradient(120deg,#edf3e9,#f8faf7);box-shadow:0 8px 24px rgba(32,43,37,.04)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome:after{border-color:#8ba17b2b;box-shadow:0 0 0 30px #8ba17b10,0 0 0 60px #8ba17b07}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome p{color:#667468}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stats{gap:12px;margin:15px 0}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat{min-height:105px;padding:16px;border-radius:12px;background:#fff;box-shadow:0 6px 20px rgba(32,43,37,.035)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat:before{width:3px;background:linear-gradient(180deg,#789657,#d5e2ce)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat span{color:#657366;font-size:9px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat strong{margin-top:7px;color:#315b43;font-size:25px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat small{color:#7b877c;font-size:9px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-workspace{grid-template-columns:minmax(0,1fr) 270px;gap:15px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{border-color:#e2e8df;border-radius:12px;background:#fff;box-shadow:0 6px 20px rgba(32,43,37,.035)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue{padding:20px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{top:85px;padding:20px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading{align-items:flex-start;margin-bottom:14px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading h3,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard h3{color:#29382d;font-size:14px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading p,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard p{color:#6d786e;font-size:10px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-count,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-count{border-color:#dce6d6;border-radius:99px;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application{border-color:#e2e8df;border-radius:10px;background:#fff}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application:hover{border-color:#ccd9c6;background:#fbfcfa}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application-icon{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application strong{color:#29382d}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application small{color:#68776a}
        body.dashboard-mode #dashboard.admin-dashboard .admin-payment-state{border-color:#eadfbf;background:#fff8e9;color:#866326}
        body.dashboard-mode #dashboard.admin-dashboard .admin-payment-state.is-paid{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-actions{border-color:#e7ece4}
        body.dashboard-mode #dashboard.admin-dashboard .button-approve:disabled{border-color:#e0e4dc;background:#eef0eb;color:#858d82}
        body.dashboard-mode #dashboard.admin-dashboard .button-reject{border-color:#ead3ce;background:#fff7f5;color:#8c382f}
        body.dashboard-mode #dashboard.admin-dashboard .admin-checklist li{border-color:#e7ece4;color:#556457}
        body.dashboard-mode #dashboard.admin-dashboard .admin-check,body.dashboard-mode #dashboard.admin-dashboard .admin-empty-icon{border-color:#dce6d6;background:#edf3e8;color:#3f6240}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-section{margin-top:17px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-grid{gap:15px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{border-color:#e2e8df;border-radius:12px;background:#fff;box-shadow:0 6px 20px rgba(32,43,37,.035)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group h2{color:#29382d;font-size:14px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group>p,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo small,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document small{color:#6d786e}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo{border-color:#e2e8df;border-radius:10px;background:#fafbf9;color:#29382d}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo img{background:#edf1e9}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document{border-color:#e2e8df;border-radius:9px;background:#fff;color:#29382d}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document a{border-radius:7px;background:#edf3e8;color:#315b43}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-empty{border-color:#dfe6dc;color:#6d786e}
        body.dashboard-mode #dashboard .product-image-preview{border-color:#dfe6dc}
        body.dashboard-mode #dashboard .vendor-product,body.dashboard-mode #dashboard .vendor-order,body.dashboard-mode #dashboard .merchant-vendor-row{border-color:#e2e8df;background:#fff}
        body.dashboard-mode #dashboard .admin-queue-count,body.dashboard-mode #dashboard .admin-document-links a{font-size:9px}
        body.dashboard-mode #dashboard .chat-messages,body.dashboard-mode #dashboard .chat-start-note,body.dashboard-mode #dashboard .chat-suggestions{background:#f5f7f3}
        body.dashboard-mode #dashboard .chat-form{border-color:#e2e8df;background:#fff}
        body.dashboard-mode #dashboard .chat-message{border-color:#e2e8df;background:#f0f3ed;color:#253127}
        body.dashboard-mode #dashboard .chat-message.is-own{border-color:#315b43;background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .chat-form textarea{border-color:#dfe6dc;background:#fff;color:#253127}
        body.dashboard-mode #dashboard .chat-suggestions button{border-color:#dfe6dc;background:#fff;color:#3f6240}
        body.dashboard-mode #dashboard .dashboard-nav-button:focus-visible,body.dashboard-mode #dashboard a:focus-visible,body.dashboard-mode #dashboard button:focus-visible{outline:2px solid #52764d;outline-offset:3px}
        body.dashboard-mode #dashboard input:focus-visible,body.dashboard-mode #dashboard select:focus-visible,body.dashboard-mode #dashboard textarea:focus-visible{outline:2px solid #52764d;outline-offset:2px}
        @media(max-width:980px){
            body.landing-mode .hero{width:min(820px,calc(100% - 42px));grid-template-columns:minmax(0,1fr) minmax(0,.95fr);gap:24px;padding:28px}
            body.dashboard-mode #dashboard .dashboard-layout{grid-template-columns:190px minmax(0,1fr);gap:16px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-workspace{grid-template-columns:minmax(0,1fr)}
            body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{position:static}
        }
        @media(max-width:760px){
            .admin-account-row{grid-template-columns:1fr}
            .admin-account-form{grid-template-columns:1fr}
        }
        @media(max-width:760px){
            body.landing-mode .topbar,body.landing-mode .topbar.is-scrolled,body.dashboard-mode .topbar,body.dashboard-mode .topbar.is-scrolled{height:66px;padding:0 18px}
            body.landing-mode .nav-links,body.dashboard-mode .nav-links{display:none}
            body.landing-mode .hero{width:calc(100% - 32px);margin:16px auto 45px;padding:23px;grid-template-columns:minmax(0,1fr);gap:22px;border-radius:17px}
            body.landing-mode .hero-copy-column{max-width:none;padding:3px 0}
            body.landing-mode .hero h1{max-width:580px;font-size:clamp(38px,9vw,54px);letter-spacing:-2.5px}
            body.landing-mode .hero-visual{grid-column:1;grid-row:2;height:clamp(290px,66vw,390px)}
            body.landing-mode #how{padding:0 16px 48px}
            body.landing-mode #how .section-heading{margin-bottom:18px}
            body.landing-mode .how-grid{grid-template-columns:1fr;gap:10px}
            body.landing-mode .how-card{min-height:170px;padding:17px}
            body.landing-mode .how-icon{margin-bottom:18px}
            body.landing-mode .trust-row{width:calc(100% - 32px);grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;padding:22px 0}
            body.landing-mode .trust-row>strong{grid-column:1/-1}
            body.landing-mode .trust-row .button{grid-column:1/-1;width:100%}
            body.landing-mode #marketInfo{padding:42px 18px}
            body.landing-mode #marketInfo .section-heading{grid-template-columns:minmax(0,1fr);gap:10px}
            body.landing-mode #marketInfo .section-heading h2{margin-bottom:14px;font-size:clamp(26px,7vw,34px)}
            body.landing-mode #marketInfo .section-heading p{font-size:13px;line-height:1.75}
            body.landing-mode #marketInfo .section-heading .button{min-width:0;min-height:46px;margin-top:8px;justify-self:start}
            body.dashboard-mode #dashboard{width:calc(100% - 32px);padding-top:18px}
            body.dashboard-mode #dashboard .dashboard-layout{grid-template-columns:minmax(0,1fr);gap:12px}
            body.dashboard-mode #dashboard .dashboard-sidebar{position:static;min-height:0;padding:10px;border-radius:11px}
            body.dashboard-mode #dashboard .dashboard-brand{display:none}
            body.dashboard-mode #dashboard .dashboard-sidebar-label,body.dashboard-mode #dashboard .dashboard-sidebar-foot{display:none}
            body.dashboard-mode #dashboard .dashboard-sidebar-nav{display:flex;gap:5px;max-width:100%;overflow-x:auto;scrollbar-width:thin}
            body.dashboard-mode #dashboard .dashboard-nav-button{width:auto;min-width:max-content;min-height:37px;padding:8px 10px}
            body.dashboard-mode #dashboard .dashboard-profile-bar{grid-template-columns:minmax(0,1fr);gap:13px;padding:14px}
            body.dashboard-mode #dashboard .dashboard-head h1{font-size:25px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-stats{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-stat{min-height:92px;padding:13px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-stat strong{font-size:23px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-storage-grid{grid-template-columns:minmax(0,1fr)}
        }
        @media(max-width:480px){
            body.landing-mode .topbar,body.landing-mode .topbar.is-scrolled,body.dashboard-mode .topbar,body.dashboard-mode .topbar.is-scrolled{padding:0 13px}
            body.landing-mode .brand{font-size:18px}
            body.landing-mode .brand-mark{width:34px;height:34px;border-radius:10px}
            body.landing-mode .nav-actions{gap:5px}
            body.landing-mode .nav-actions .button{min-height:37px;padding:0 10px;font-size:9px}
            body.landing-mode .text-button{padding:7px;font-size:10px}
            body.landing-mode .hero{width:calc(100% - 24px);padding:17px}
            body.landing-mode .hero h1{font-size:clamp(35px,10vw,46px);letter-spacing:-2px}
            body.landing-mode .hero-copy{font-size:12px}
            body.landing-mode .hero-search{gap:5px;padding-left:9px}
            body.landing-mode .hero-search .button{padding:0 9px;font-size:9px}
            body.landing-mode .hero-visual{height:280px}
            body.landing-mode .hero-market-card{right:10px;bottom:10px;left:10px;padding:11px}
            body.landing-mode .hero-market-card strong{font-size:11px}
            body.landing-mode .hero-market-card small{font-size:8px}
            body.landing-mode .trust-row{grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
            body.dashboard-mode #dashboard{width:calc(100% - 24px)}
            body.dashboard-mode #dashboard .dashboard-profile-actions{justify-content:flex-start}
            body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard{padding:14px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading{flex-direction:column}
            body.dashboard-mode #dashboard.admin-dashboard .admin-application{align-items:flex-start;flex-direction:column;padding:13px}
            body.dashboard-mode #dashboard.admin-dashboard .admin-actions{width:100%;justify-content:stretch;flex-direction:column}
            body.dashboard-mode #dashboard.admin-dashboard .admin-actions button{width:100%}
            body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{padding:15px}
        }
        body.landing-mode{background:#e9ebdf}
        body.landing-mode .topbar,body.landing-mode .topbar.is-scrolled{background:rgba(246,245,235,.97);border-color:#d9ddcf}
        body.landing-mode .hero{border-color:#d5dccd;background:linear-gradient(135deg,#f7f5e9 0%,#f3f2e7 56%,#e5eadc 100%)}
        body.landing-mode .hero-copy{color:#4f5c50;font-size:14px}
        body.landing-mode .hero-search,body.landing-mode .hero-chip,body.landing-mode .hero-actions .button{background:#f8f6ec}
        body.landing-mode .hero-search input{font-size:13px}
        body.landing-mode .hero-chip{font-size:10px}
        body.landing-mode #how,body.landing-mode #marketInfo{background:#e9ebdf}
        body.landing-mode #how .section-heading p,body.landing-mode #marketInfo .section-heading p{color:#4e5b50}
        body.landing-mode .how-card{border-color:#d6dccf;background:#f7f5e9}
        body.landing-mode .how-card h3{font-size:16px}
        body.landing-mode .how-card p{color:#4f5c50;font-size:12px}
        body.landing-mode .market-band{border-color:#d0d7c8;background:#dfe6d6}
        body.landing-mode .trust-row>strong,body.landing-mode .trust-stat strong{color:#31533b}
        body.landing-mode .trust-stat span{color:#4f6151;font-size:11px}
        body.landing-mode #marketInfo .section-heading h2{color:#223127}
        body.landing-mode #marketInfo .eyebrow{font-size:10px}
        body.landing-mode #marketInfo .section-heading p{font-size:15px}
        body.landing-mode #marketInfo .section-heading .button{font-size:12px}
        body.landing-mode .footer{background:#e2e5d9;color:#526052}
        body.dashboard-mode{background:#e7e9dd;color:#253127}
        body.dashboard-mode .topbar,body.dashboard-mode .topbar.is-scrolled{background:rgba(242,241,229,.97);border-color:#d4d9cc}
        body.dashboard-mode #dashboard,body.dashboard-mode #dashboard.vendor-dashboard,body.dashboard-mode #dashboard.admin-dashboard{--paper:#ececdf;--line:#d5dbce}
        body.dashboard-mode #dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.admin-dashboard .dashboard-sidebar{background:#e2e5d8;border-color:#cfd6c7}
        body.dashboard-mode #dashboard .dashboard-nav-button{font-size:12px}
        body.dashboard-mode #dashboard .dashboard-sidebar-label{color:#556651;font-size:10px;letter-spacing:.8px}
        body.dashboard-mode #dashboard .dashboard-nav-button:hover{background:#d7dece}
        body.dashboard-mode #dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.admin-dashboard .dashboard-nav-button.active{background:#cedbc5;color:#294a33}
        body.dashboard-mode #dashboard .dashboard-profile-bar,body.dashboard-mode #dashboard .customer-highlight,body.dashboard-mode #dashboard .vendor-panel,body.dashboard-mode #dashboard .map-panel,body.dashboard-mode #dashboard .booking-section,body.dashboard-mode #dashboard .message-inbox,body.dashboard-mode #dashboard .vendor-profile-card,body.dashboard-mode #dashboard .merchant-card,body.dashboard-mode #dashboard .merchant-stat,body.dashboard-mode #dashboard .admin-stat,body.dashboard-mode #dashboard .admin-queue,body.dashboard-mode #dashboard .admin-sidecard{background:#f5f3e8;border-color:#d6dccf}
        body.dashboard-mode #dashboard .dashboard-profile-form input[type="text"],body.dashboard-mode #dashboard .dashboard-profile-form input[type="file"],body.dashboard-mode #dashboard .modal input,body.dashboard-mode #dashboard .modal select,body.dashboard-mode #dashboard .role-form-field select,body.dashboard-mode #dashboard .vendor-account-form input[type="text"],body.dashboard-mode #dashboard .vendor-account-form input[type="file"],body.dashboard-mode #dashboard .search-wrap input,body.dashboard-mode #dashboard .select-filter,body.dashboard-mode #dashboard .chat-form textarea{background:#fbf9f0;border-color:#d3d9cc}
        body.dashboard-mode #dashboard .dashboard-head h1{font-size:30px}
        body.dashboard-mode #dashboard .dashboard-head p{color:#4f5c50;font-size:15px}
        body.dashboard-mode #dashboard .dashboard-profile-form label{font-size:12px}
        body.dashboard-mode #dashboard .dashboard-profile-form input[type="text"],body.dashboard-mode #dashboard .dashboard-profile-form input[type="file"]{font-size:13px}
        body.dashboard-mode #dashboard .dashboard-profile-feedback{font-size:12px}
        body.dashboard-mode #dashboard .profile-button .profile-email{font-size:13px}
        body.dashboard-mode #dashboard .notification-button{font-size:12px}
        body.dashboard-mode #dashboard .notification-item strong{font-size:13px}
        body.dashboard-mode #dashboard .notification-item span{font-size:12px}
        body.dashboard-mode #dashboard .notification-item time{font-size:11px}
        body.dashboard-mode #dashboard .tab{font-size:13px}
        body.dashboard-mode #dashboard .search-wrap input,body.dashboard-mode #dashboard .select-filter{font-size:14px}
        body.dashboard-mode #dashboard .panel-header strong{font-size:15px}
        body.dashboard-mode #dashboard .panel-header span{font-size:12px}
        body.dashboard-mode #dashboard .vendor-card,body.dashboard-mode #dashboard .booking-row,body.dashboard-mode #dashboard .conversation-row{background:#f5f3e8;border-color:#dce1d5}
        body.dashboard-mode #dashboard .vendor-card:hover,body.dashboard-mode #dashboard .vendor-card.selected,body.dashboard-mode #dashboard .conversation-row:hover{background:#e9eddf}
        body.dashboard-mode #dashboard .vendor-main p,body.dashboard-mode #dashboard .vendor-description,body.dashboard-mode #dashboard .booking-empty{color:#4f5c50}
        body.dashboard-mode #dashboard .customer-welcome,body.dashboard-mode #dashboard .vendor-welcome,body.dashboard-mode #dashboard .admin-welcome{background:linear-gradient(120deg,#e1e8d8,#f2f1e5);border-color:#d1d9ca}
        body.dashboard-mode #dashboard .customer-welcome h2,body.dashboard-mode #dashboard .vendor-welcome h2,body.dashboard-mode #dashboard .admin-welcome h2{font-size:24px}
        body.dashboard-mode #dashboard .customer-welcome p,body.dashboard-mode #dashboard .vendor-welcome p,body.dashboard-mode #dashboard .admin-welcome p{color:#4f5c50;font-size:14px;line-height:1.65}
        body.dashboard-mode #dashboard .customer-highlight strong,body.dashboard-mode #dashboard .merchant-stat strong{font-size:25px}
        body.dashboard-mode #dashboard .customer-highlight span,body.dashboard-mode #dashboard .merchant-stat span{font-size:13px}
        body.dashboard-mode #dashboard .customer-highlight small,body.dashboard-mode #dashboard .merchant-stat small{color:#566255;font-size:11px}
        body.dashboard-mode #dashboard .booking-section h2,body.dashboard-mode #dashboard .message-inbox-heading h2{font-size:18px}
        body.dashboard-mode #dashboard .message-inbox-heading p{color:#4f5c50;font-size:13px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat{background:#f5f3e8}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat span{color:#4f5c50;font-size:12px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat strong{font-size:28px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat small{color:#566255;font-size:11px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading h3,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard h3{font-size:16px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading p,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard p{color:#4f5c50;font-size:12px;line-height:1.6}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application{background:#f8f6ec;border-color:#dce1d5}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application strong{font-size:14px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application small{color:#4f5c50;font-size:12px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{background:#f5f3e8;border-color:#d6dccf}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group h2{font-size:16px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group>p,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo small,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document small{color:#4f5c50;font-size:12px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document{background:#f8f6ec;border-color:#dce1d5}
        body.dashboard-mode #dashboard .eyebrow{font-size:10px}
        body.dashboard-mode #dashboard .button,body.dashboard-mode .nav-actions .button{font-size:12px}
        @media(max-width:760px){
            body.dashboard-mode #dashboard .dashboard-sidebar-nav{display:flex;flex-wrap:wrap;gap:6px;overflow:visible}
            body.dashboard-mode #dashboard .dashboard-nav-button{width:calc(50% - 3px);min-width:0;min-height:40px;padding:8px 9px;font-size:11px}
            body.dashboard-mode #dashboard .dashboard-profile-bar{background:#f5f3e8}
        }
        @media(max-width:480px){
            body.landing-mode .hero{background:linear-gradient(135deg,#f7f5e9,#e7ebdd)}
            body.dashboard-mode #dashboard .dashboard-nav-button{font-size:11px}
            body.dashboard-mode #dashboard .dashboard-head h1{font-size:25px}
            body.dashboard-mode #dashboard .dashboard-head p{font-size:13px}
            body.dashboard-mode #dashboard .customer-welcome h2,body.dashboard-mode #dashboard .vendor-welcome h2,body.dashboard-mode #dashboard .admin-welcome h2{font-size:21px}
        }
        body.landing-mode{background:#cbd3c3}
        body.landing-mode .topbar,body.landing-mode .topbar.is-scrolled{background:rgba(216,222,208,.98);border-color:#bdc8b7}
        body.landing-mode .hero{border-color:#b9c5b2;background:linear-gradient(135deg,#d9dece 0%,#d1d9c8 55%,#c2cfbb 100%)}
        body.landing-mode .hero-search,body.landing-mode .hero-chip,body.landing-mode .hero-actions .button{border-color:#becab7;background:#d7decd}
        body.landing-mode .hero-market-card{border-color:#bdc9b5;background:rgba(211,220,201,.97);color:#26352a}
        body.landing-mode #how,body.landing-mode #marketInfo{background:#cbd3c3}
        body.landing-mode .how-card{border-color:#bdc8b7;background:#d5dccb}
        body.landing-mode .how-icon{border-color:#bdcbb7;background:#cbd7c3}
        body.landing-mode .market-band{border-color:#b8c5b1;background:#c1cfb9}
        body.landing-mode #marketInfo .section-heading .button{background:#315b43}
        body.landing-mode .footer{border-color:#bdc8b7;background:#c2cbb9}
        body.dashboard-mode{background:#cbd4c4}
        body.dashboard-mode .topbar,body.dashboard-mode .topbar.is-scrolled{background:rgba(216,222,208,.98);border-color:#bdc8b7}
        body.dashboard-mode #dashboard,body.dashboard-mode #dashboard.vendor-dashboard,body.dashboard-mode #dashboard.admin-dashboard{--paper:#d4dccb;--line:#bdc8b7}
        body.dashboard-mode #dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.admin-dashboard .dashboard-sidebar{background:#c1cdb9;border-color:#b0bea9}
        body.dashboard-mode #dashboard .dashboard-nav-button:hover{background:#b4c3ac}
        body.dashboard-mode #dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.admin-dashboard .dashboard-nav-button.active{background:#afc3a6}
        body.dashboard-mode #dashboard .dashboard-profile-bar,body.dashboard-mode #dashboard .customer-highlight,body.dashboard-mode #dashboard .vendor-panel,body.dashboard-mode #dashboard .map-panel,body.dashboard-mode #dashboard .booking-section,body.dashboard-mode #dashboard .message-inbox,body.dashboard-mode #dashboard .vendor-profile-card,body.dashboard-mode #dashboard .merchant-card,body.dashboard-mode #dashboard .merchant-stat,body.dashboard-mode #dashboard .admin-stat,body.dashboard-mode #dashboard .admin-queue,body.dashboard-mode #dashboard .admin-sidecard{background:#d6ddcd;border-color:#bdc8b7}
        body.dashboard-mode #dashboard .dashboard-profile-form input[type="text"],body.dashboard-mode #dashboard .dashboard-profile-form input[type="file"],body.dashboard-mode #dashboard .modal input,body.dashboard-mode #dashboard .modal select,body.dashboard-mode #dashboard .role-form-field select,body.dashboard-mode #dashboard .vendor-account-form input[type="text"],body.dashboard-mode #dashboard .vendor-account-form input[type="file"],body.dashboard-mode #dashboard .search-wrap input,body.dashboard-mode #dashboard .select-filter,body.dashboard-mode #dashboard .chat-form textarea{background:#e0e5d7;border-color:#b9c6b3}
        body.dashboard-mode #dashboard .vendor-card,body.dashboard-mode #dashboard .booking-row,body.dashboard-mode #dashboard .conversation-row,body.dashboard-mode #dashboard .profile-menu,body.dashboard-mode #dashboard .notification-panel,body.dashboard-mode #dashboard .notification-item,body.dashboard-mode #dashboard .modal{background:#d6ddcd;border-color:#bdc8b7}
        body.dashboard-mode #dashboard .vendor-card:hover,body.dashboard-mode #dashboard .vendor-card.selected,body.dashboard-mode #dashboard .conversation-row:hover,body.dashboard-mode #dashboard .profile-menu button:hover,body.dashboard-mode #dashboard .notification-item:hover,body.dashboard-mode #dashboard .notification-item.unread{background:#cbd5c3}
        body.dashboard-mode #dashboard .chat-messages,body.dashboard-mode #dashboard .chat-start-note,body.dashboard-mode #dashboard .chat-suggestions{background:#cbd5c3}
        body.dashboard-mode #dashboard .chat-message{background:#dce2d3;border-color:#bdc8b7;color:#253127}
        body.dashboard-mode #dashboard .chat-message.is-own{background:#315b43;color:#fff}
        body.dashboard-mode #dashboard .chat-form{background:#d6ddcd;border-color:#bdc8b7}
        body.dashboard-mode #dashboard .chat-suggestions button,body.dashboard-mode #dashboard .merchant-edit-button,body.dashboard-mode #dashboard .merchant-payment-check,body.dashboard-mode #dashboard .admin-document-links a{background:#cbd5c3;border-color:#b7c4b1}
        body.dashboard-mode #dashboard .customer-welcome,body.dashboard-mode #dashboard .vendor-welcome,body.dashboard-mode #dashboard .admin-welcome{background:linear-gradient(120deg,#bfceb7,#d1dac8);border-color:#b5c3ad}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat,body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{background:#d6ddcd;border-color:#bdc8b7}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document{background:#dfe4d5;border-color:#bdc8b7}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application:hover{background:#cbd5c3}
        body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo img{background:#c5d0bd}
        body.dashboard-mode #dashboard .vendor-product,body.dashboard-mode #dashboard .vendor-order,body.dashboard-mode #dashboard .merchant-vendor-row{background:#d6ddcd;border-color:#bdc8b7}
        body.landing-mode .topbar,body.landing-mode .topbar.is-scrolled,body.dashboard-mode .topbar,body.dashboard-mode .topbar.is-scrolled{background:#20382b;border-color:#20382b;color:#f5f4e9}
        body.landing-mode .brand,body.dashboard-mode .brand{color:#f5f4e9}
        body.landing-mode .brand-mark,body.dashboard-mode .brand-mark{background:#d2dfc5;color:#20382b}
        body.landing-mode .nav-links,body.dashboard-mode .nav-links{color:#d9e1d3}
        body.landing-mode .nav-links a:hover,body.dashboard-mode .nav-links a:hover,body.landing-mode .text-button,body.dashboard-mode .text-button{color:#d2dfc5}
        body.landing-mode .nav-links a:after,body.dashboard-mode .nav-links a:after{background:#c5d7b7}
        body.landing-mode .nav-actions .button-primary,body.dashboard-mode .nav-actions .button-primary{border:1px solid #d2dfc5;background:#d2dfc5;color:#20382b}
        body.landing-mode .nav-actions .button-primary:hover,body.dashboard-mode .nav-actions .button-primary:hover{background:#e0e9d7}
        body.landing-mode .hero{border-color:#b9c5b2;background:linear-gradient(135deg,#e3e7d9 0%,#d8e0d0 62%,#cbd8c3 100%)}
        body.landing-mode #how{background:#20382b;color:#f3f2e8}
        body.landing-mode #how .section-heading h2{color:#f3f2e8}
        body.landing-mode #how .section-heading p{color:#c3d0c0}
        body.landing-mode #how .eyebrow{color:#c8d8b8}
        body.landing-mode .how-card{border-color:#526651;background:#2c4635;box-shadow:0 10px 24px rgba(9,20,12,.16)}
        body.landing-mode .how-card:hover{border-color:#829477;background:#324e3b}
        body.landing-mode .how-card h3{color:#f5f3e8}
        body.landing-mode .how-card p{color:#d0d9ca}
        body.landing-mode .how-card:after{color:#617563}
        body.landing-mode .how-icon{border-color:#566b50;background:#40583f;color:#e4ebd8}
        body.landing-mode .market-band{border-color:#294331;background:#294331}
        body.landing-mode .market-band:before{border-color:#ffffff24;box-shadow:0 0 0 34px #ffffff08,0 0 0 70px #ffffff05}
        body.landing-mode .trust-row>strong,body.landing-mode .trust-stat strong{color:#f3f2e8}
        body.landing-mode .trust-stat span{color:#d1dbcd}
        body.landing-mode .trust-row .button,body.landing-mode .trust-row .button:hover{background:#d2dfc5;color:#20382b}
        body.landing-mode #marketInfo{background:#dfe4d7}
        body.landing-mode #marketInfo .section-heading h2{color:#213328}
        body.landing-mode #marketInfo .section-heading p{color:#435347}
        body.landing-mode .footer{border-color:#20382b;background:#20382b;color:#d4ded0}
        body.dashboard-mode{background:#d8dfd3}
        body.dashboard-mode #dashboard,body.dashboard-mode #dashboard.vendor-dashboard,body.dashboard-mode #dashboard.admin-dashboard{--paper:#dce3d6;--line:#c2cdbd}
        body.dashboard-mode #dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-sidebar,body.dashboard-mode #dashboard.admin-dashboard .dashboard-sidebar{border-color:#20382b;background:#20382b;color:#e7ecdf;box-shadow:0 12px 28px rgba(21,40,27,.18)}
        body.dashboard-mode #dashboard .dashboard-brand{color:#f3f2e8}
        body.dashboard-mode #dashboard .dashboard-brand-mark{background:#d2dfc5;color:#20382b}
        body.dashboard-mode #dashboard .dashboard-sidebar-label{color:#bdcbb7}
        body.dashboard-mode #dashboard .dashboard-nav-button{color:#dce5d8}
        body.dashboard-mode #dashboard .dashboard-nav-button:hover{background:#344f3a;color:#fff}
        body.dashboard-mode #dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.vendor-dashboard .dashboard-nav-button.active,body.dashboard-mode #dashboard.admin-dashboard .dashboard-nav-button.active{background:#d2dfc5;color:#20382b;box-shadow:none}
        body.dashboard-mode #dashboard .dashboard-sidebar-foot{border-color:#ffffff24;color:#bdcbb7}
        body.dashboard-mode #dashboard .dashboard-profile-bar,body.dashboard-mode #dashboard .customer-highlight,body.dashboard-mode #dashboard .vendor-panel,body.dashboard-mode #dashboard .map-panel,body.dashboard-mode #dashboard .booking-section,body.dashboard-mode #dashboard .message-inbox,body.dashboard-mode #dashboard .vendor-profile-card,body.dashboard-mode #dashboard .merchant-card,body.dashboard-mode #dashboard .merchant-stat,body.dashboard-mode #dashboard .admin-stat,body.dashboard-mode #dashboard .admin-queue,body.dashboard-mode #dashboard .admin-sidecard{background:#e7eadf;border-color:#c5d0c0}
        body.dashboard-mode #dashboard .dashboard-profile-bar{box-shadow:0 7px 20px rgba(28,46,32,.08)}
        body.dashboard-mode #dashboard .customer-welcome,body.dashboard-mode #dashboard .vendor-welcome,body.dashboard-mode #dashboard .admin-welcome{border-color:#35543c;background:linear-gradient(112deg,#20382b,#35543c);color:#f5f3e9;box-shadow:0 12px 28px rgba(21,40,27,.14)}
        body.dashboard-mode #dashboard .customer-welcome h2,body.dashboard-mode #dashboard .vendor-welcome h2,body.dashboard-mode #dashboard .admin-welcome h2{color:#f5f3e9}
        body.dashboard-mode #dashboard .customer-welcome p,body.dashboard-mode #dashboard .vendor-welcome p,body.dashboard-mode #dashboard .admin-welcome p{color:#d6e0d2}
        body.dashboard-mode #dashboard .customer-welcome .eyebrow,body.dashboard-mode #dashboard .vendor-welcome .eyebrow,body.dashboard-mode #dashboard .admin-welcome .eyebrow{color:#cfdfbf}
        body.dashboard-mode #dashboard .customer-welcome .button,body.dashboard-mode #dashboard .vendor-welcome .button,body.dashboard-mode #dashboard .admin-welcome .button{background:#d2dfc5;color:#20382b}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome{background:linear-gradient(100deg,rgba(20,39,27,.93),rgba(40,66,47,.82)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1600&q=85") center 46%/cover}
        body.dashboard-mode #dashboard.admin-dashboard .admin-welcome p{color:#e0e7dc}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat{background:linear-gradient(145deg,#294332,#20382b);border-color:#365640}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat span{color:#d5dfd1}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat strong{color:#f3f1e5}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat small{color:#c2d0bd}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stat:before{background:linear-gradient(180deg,#d2dfc5,#8ba17c)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{background:#e7eadf;border-color:#c5d0c0}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-photo,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-document{background:#edf0e6;border-color:#cbd4c6}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application:hover{background:#e0e7d9}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application strong,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group h2{color:#293a2d}
        body.dashboard-mode #dashboard .notification-button{border-color:#365640;background:#294332;color:#f2f1e7}
        body.dashboard-mode #dashboard .notification-button:hover{background:#365640}
        body.landing-mode .hero{min-height:600px;grid-template-columns:minmax(0,1fr);align-items:center;padding:38px;border-color:#263e2e;background:linear-gradient(90deg,rgba(20,42,29,.94) 0%,rgba(22,44,31,.85) 47%,rgba(25,46,32,.34) 100%),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=85") center 48%/cover}
        body.landing-mode .hero:before{display:none}
        body.landing-mode .hero-copy-column{z-index:2;width:min(620px,100%);max-width:620px;margin:0;padding:16px 0;animation:none}
        body.landing-mode .hero .eyebrow{color:#d8e6ca}
        body.landing-mode .hero h1{color:#f7f6ed}
        body.landing-mode .hero h1 em{color:#c8ddb6}
        body.landing-mode .hero-copy{color:#edf0e7}
        body.landing-mode .hero-search{border-color:rgba(255,255,255,.55);background:rgba(244,244,232,.96);box-shadow:0 12px 30px rgba(0,0,0,.2)}
        body.landing-mode .hero-search input{color:#26352a}
        body.landing-mode .hero-search-icon{color:#53664f}
        body.landing-mode .hero-chip,body.landing-mode .hero-chip:hover{border-color:rgba(255,255,255,.48);background:rgba(236,240,226,.94);color:#334a38}
        body.landing-mode .hero-search-chips,body.landing-mode .hero-search-chips strong{color:#edf0e7}
        body.landing-mode .hero-note{color:#dbe4d7}
        body.landing-mode .hero-note-copy strong{color:#fff}
        body.landing-mode .hero-note-copy small{color:#d6dfd2}
        body.landing-mode .hero-bottom-row{border-top-color:rgba(245,243,229,.36)}
        body.landing-mode .hero-stat{border-right-color:rgba(245,243,229,.28)}
        body.landing-mode .hero-stat strong{color:#e2edcf}
        body.landing-mode .hero-stat span{color:#e0e6da}
        body.landing-mode .hero-visual{position:absolute;z-index:1;inset:0;width:100%;height:100%;grid-column:auto;grid-row:auto;border:0;border-radius:0;background:transparent;box-shadow:none;animation:none;pointer-events:none}
        body.landing-mode .hero-photo{display:none}
        body.landing-mode .hero-visual-top,body.landing-mode .hero-photo-caption{pointer-events:none}
        body.landing-mode .hero-market-card{pointer-events:auto}
        body.landing-mode .hero-live-pill,body.landing-mode .hero-area-pill{border-color:rgba(255,255,255,.5);background:rgba(241,243,231,.94);color:#334a38}
        body.landing-mode .hero-market-card{right:22px;left:auto;width:min(390px,calc(100% - 44px));border-color:rgba(223,232,215,.8);background:rgba(225,233,216,.96)}
        @media(max-width:760px){
            body.landing-mode .hero{min-height:0;padding:0;grid-template-columns:minmax(0,1fr);background-position:62% center}
            body.landing-mode .hero-copy-column{width:calc(100% - 34px);max-width:none;margin:0 auto;padding:43px 0 160px}
            body.landing-mode .hero h1{font-size:clamp(38px,9vw,54px)}
            body.landing-mode .hero-visual{grid-column:auto;grid-row:auto;height:100%}
            body.landing-mode .hero-visual-top{display:none}
            body.landing-mode .hero-market-card{right:12px;bottom:12px;left:12px;width:auto}
            body.landing-mode .hero-live-pill,body.landing-mode .hero-area-pill{font-size:8px}
        }
        @media(max-width:480px){
            body.landing-mode .hero{background-image:linear-gradient(90deg,rgba(20,42,29,.94),rgba(22,44,31,.78)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1000&q=85")}
            body.landing-mode .hero-copy-column{width:calc(100% - 26px);padding:36px 0 148px}
            body.landing-mode .hero-market-card{right:10px;bottom:10px;left:10px;width:auto;padding:10px}
        }
        body.landing-mode .hero{width:100%;max-width:none;min-height:calc(100vh - 74px);min-height:calc(100svh - 74px);margin:0;padding:0;overflow:hidden;border-right:0;border-left:0;border-radius:0;box-shadow:none;background-position:center;background-size:cover}
        body.landing-mode .hero-copy-column{width:min(1180px,calc(100% - 64px));max-width:none;margin:0 auto}
        body.landing-mode .hero-visual{border-radius:0}
        @media(max-width:760px){
            body.landing-mode .hero{min-height:calc(100vh - 66px);min-height:calc(100svh - 66px)}
            body.landing-mode .hero-copy-column{width:calc(100% - 34px)}
        }
        body.landing-mode .hero-copy-column{width:min(920px,calc(100% - 64px));text-align:center}
        body.landing-mode .hero .eyebrow{justify-content:center;font-size:12px;letter-spacing:1.6px}
        body.landing-mode .hero h1{max-width:none;margin:22px auto;font-size:clamp(54px,7vw,88px);line-height:1.04;letter-spacing:-4px}
        body.landing-mode .hero-copy{max-width:760px;margin:0 auto 27px;font-size:19px;line-height:1.75;color:#f2f4ec}
        body.landing-mode .hero-search{width:min(680px,100%);max-width:680px;margin:0 auto;min-height:66px}
        body.landing-mode .hero-search input{font-size:16px}
        body.landing-mode .hero-search .button{min-height:52px;font-size:14px}
        body.landing-mode .hero-search-chips{justify-content:center;gap:10px;margin-top:18px;font-size:13px}
        body.landing-mode .hero-search-chips strong{font-size:11px;letter-spacing:1px}
        body.landing-mode .hero-chip,body.landing-mode .hero-chip:hover{padding:9px 14px;font-size:12px}
        body.landing-mode .hero-actions{justify-content:center;gap:12px;margin-top:23px}
        body.landing-mode .hero-actions .button{min-height:49px;padding:0 18px;font-size:13px}
        body.landing-mode .hero-note{justify-content:center;gap:13px;margin-top:23px;font-size:13px;text-align:left}
        body.landing-mode .avatar-stack img{width:34px;height:34px}
        body.landing-mode .hero-note-copy strong{font-size:13px}
        body.landing-mode .hero-note-copy small{font-size:12px}
        body.landing-mode .hero-bottom-row{width:min(680px,100%);max-width:680px;margin:27px auto 0;padding-top:18px}
        body.landing-mode .hero-stat{padding:0 17px;text-align:center}
        body.landing-mode .hero-stat strong{font-size:23px}
        body.landing-mode .hero-stat span{font-size:12px}
        body.landing-mode .hero-photo-caption{display:none}
        @media(max-width:760px){
            body.landing-mode .hero-copy-column{width:calc(100% - 34px);padding:38px 0 170px}
            body.landing-mode .hero .eyebrow{font-size:10px}
            body.landing-mode .hero h1{font-size:clamp(40px,10vw,58px);letter-spacing:-2.6px}
            body.landing-mode .hero-copy{max-width:580px;font-size:16px;line-height:1.65}
            body.landing-mode .hero-search{min-height:60px}
            body.landing-mode .hero-search input{font-size:14px}
            body.landing-mode .hero-search .button{min-height:48px;font-size:12px}
            body.landing-mode .hero-search-chips{gap:7px;font-size:12px}
            body.landing-mode .hero-chip,body.landing-mode .hero-chip:hover{padding:8px 11px;font-size:11px}
            body.landing-mode .hero-actions .button{min-height:44px;font-size:12px}
            body.landing-mode .hero-note-copy strong{font-size:12px}
            body.landing-mode .hero-note-copy small{font-size:11px}
            body.landing-mode .hero-bottom-row{margin-top:22px}
            body.landing-mode .hero-stat{padding:0 9px}
            body.landing-mode .hero-stat strong{font-size:20px}
            body.landing-mode .hero-stat span{font-size:10px;line-height:1.4}
            body.landing-mode .hero-photo-caption{bottom:111px;font-size:10px}
        }
        @media(max-width:480px){
            body.landing-mode .hero-copy-column{width:calc(100% - 26px);padding:31px 0 155px}
            body.landing-mode .hero h1{font-size:clamp(38px,10.5vw,46px);letter-spacing:-2px}
            body.landing-mode .hero-copy{font-size:15px}
            body.landing-mode .hero-search{min-height:55px;padding-left:10px}
            body.landing-mode .hero-search .button{min-height:45px;padding:0 10px}
            body.landing-mode .hero-actions{gap:8px}
            body.landing-mode .hero-actions .button{padding:0 12px;font-size:11px}
            body.landing-mode .hero-note{margin-top:18px}
            body.landing-mode .hero-stat{padding:0 6px}
            body.landing-mode .hero-stat span{font-size:9px}
            body.landing-mode .hero-photo-caption{display:none}
        }
        body.landing-mode .hero{min-height:calc(100vh - 74px);min-height:calc(100svh - 74px);background-image:linear-gradient(90deg,rgba(17,38,26,.88),rgba(17,38,26,.67)),url("https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=1800&q=85")}
        body.landing-mode .hero-copy-column{width:min(920px,calc(100% - 64px));padding:38px 0;text-align:center}
        body.landing-mode .hero .eyebrow{justify-content:center;color:#e0e9d8;font-size:11px;letter-spacing:1.5px}
        body.landing-mode .hero h1{margin:18px auto 16px;font-size:clamp(48px,5.8vw,76px);line-height:1.06;letter-spacing:-3.4px}
        body.landing-mode .hero-copy{max-width:620px;margin:0 auto 23px;color:#eff2e9;font-size:17px;line-height:1.65}
        body.landing-mode .hero-search{width:min(650px,100%);min-height:62px;margin:0 auto;border-radius:12px}
        body.landing-mode .hero-search input{font-size:15px}
        body.landing-mode .hero-search .button{min-height:48px;font-size:13px}
        body.landing-mode .hero-search-chips{justify-content:center;gap:8px;margin-top:15px;font-size:12px}
        body.landing-mode .hero-search-chips strong{margin-right:4px;color:#f0f3e8;font-size:10px;letter-spacing:.8px}
        body.landing-mode .hero-chip,body.landing-mode .hero-chip:hover{padding:8px 12px;font-size:11px}
        body.landing-mode .hero-actions{justify-content:center;gap:10px;margin-top:18px}
        body.landing-mode .hero-actions .button{min-height:44px;padding:0 15px;font-size:12px}
        body.landing-mode .hero-bottom-row{width:min(650px,100%);margin:22px auto 0;padding-top:15px}
        body.landing-mode .hero-stat{padding:0 14px;text-align:center}
        body.landing-mode .hero-stat strong{font-size:21px}
        body.landing-mode .hero-stat span{color:#e8ede2;font-size:11px}
        body.landing-mode #how{padding-top:74px;padding-bottom:74px}
        body.landing-mode #how .section-heading{margin-bottom:26px}
        body.landing-mode #how .section-heading h2{font-size:clamp(28px,3vw,36px);line-height:1.2}
        body.landing-mode .how-grid{gap:16px}
        body.landing-mode .how-card{min-height:194px;padding:23px}
        body.landing-mode .how-icon{margin-bottom:21px}
        body.landing-mode .how-card h3{margin-bottom:8px;font-size:16px}
        body.landing-mode .how-card p{font-size:13px;line-height:1.65}
        body.landing-mode .market-band{background:#20382b}
        body.landing-mode .trust-row{grid-template-columns:minmax(0,1fr) auto;align-items:center;padding:27px 0}
        body.landing-mode .vendor-cta-copy{display:grid;gap:5px}
        body.landing-mode .vendor-cta-copy strong{color:#f3f2e8;font:700 17px var(--display)}
        body.landing-mode .vendor-cta-copy span{color:#d1dbcd;font-size:13px}
        body.landing-mode .trust-row .button{min-height:46px;padding:0 17px;border-radius:9px;font-size:12px}
        body.landing-mode #marketInfo{padding-top:58px;padding-bottom:58px}
        body.landing-mode #marketInfo .section-heading{max-width:1050px;margin:0 auto;align-items:center}
        body.landing-mode #marketInfo .section-heading>div{max-width:760px}
        body.landing-mode #marketInfo .section-heading h2{margin-bottom:14px;font-size:clamp(28px,3vw,38px);line-height:1.2}
        body.landing-mode #marketInfo .section-heading p{font-size:15px;line-height:1.7}
        body.landing-mode #marketInfo .section-heading .button{margin-top:0}
        body.landing-mode .footer{padding:19px 0}
        body.landing-mode .footer-inner{justify-content:center;font-size:12px}
        body.dashboard-mode #dashboard{padding-top:24px}
        body.dashboard-mode #dashboard .dashboard-layout{gap:20px}
        body.dashboard-mode #dashboard .dashboard-sidebar{padding:17px 12px}
        body.dashboard-mode #dashboard .dashboard-nav-button{min-height:42px;font-size:12px}
        body.dashboard-mode #dashboard .dashboard-profile-bar{grid-template-columns:minmax(0,1fr) minmax(0,1.3fr) auto;gap:18px;padding:16px 18px}
        body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest{grid-template-columns:minmax(0,1fr) auto;align-items:center;padding:13px 17px}
        body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest .dashboard-profile-copy{display:none}
        body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest .dashboard-profile-actions{grid-column:auto;justify-content:flex-end}
        body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest .profile-button img,body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest .profile-avatar{width:48px;height:48px;border-radius:13px;font-size:20px}
        body.dashboard-mode #dashboard .dashboard-profile-copy .eyebrow{font-size:10px;text-transform:none;letter-spacing:0}
        body.dashboard-mode #dashboard .dashboard-profile-form{gap:9px}
        body.dashboard-mode #dashboard .dashboard-head{margin-bottom:19px;padding-bottom:13px}
        body.dashboard-mode #dashboard .dashboard-head h1{font-size:28px;letter-spacing:-.7px}
        body.dashboard-mode #dashboard .dashboard-head p{font-size:14px}
        body.dashboard-mode #dashboard .customer-overview{gap:14px;margin-bottom:19px}
        body.dashboard-mode #dashboard .customer-welcome{min-height:128px;padding:23px 25px;border-radius:14px}
        body.dashboard-mode #dashboard .customer-welcome h2,body.dashboard-mode #dashboard .vendor-welcome h2{margin-bottom:7px;font-size:23px}
        body.dashboard-mode #dashboard .customer-welcome p,body.dashboard-mode #dashboard .vendor-welcome p{margin:0;font-size:14px;line-height:1.55}
        body.dashboard-mode #dashboard .customer-welcome .button,body.dashboard-mode #dashboard .vendor-welcome .button{min-height:44px;font-size:12px}
        body.dashboard-mode #dashboard .customer-highlights{gap:10px}
        body.dashboard-mode #dashboard .customer-highlight{min-height:58px;padding:12px 15px}
        body.dashboard-mode #dashboard .customer-highlight span{font-size:12px}
        body.dashboard-mode #dashboard .customer-highlight strong{margin-top:3px;font-size:22px}
        body.dashboard-mode #dashboard .dashboard-tabs{margin-bottom:18px}
        body.dashboard-mode #dashboard .tab{padding:11px 15px;font-size:13px}
        body.dashboard-mode #dashboard .search-wrap input,body.dashboard-mode #dashboard .select-filter{height:46px;font-size:14px}
        body.dashboard-mode #dashboard .panel-header{padding:14px 16px}
        body.dashboard-mode #dashboard .vendor-list{max-height:510px}
        body.dashboard-mode #dashboard .vendor-card{padding:14px 7px}
        body.dashboard-mode #dashboard .recommendation-panel{margin-bottom:18px;padding:17px}
        body.dashboard-mode #dashboard .recommendation-heading{margin-bottom:12px}
        body.dashboard-mode #dashboard .recommendation-heading h2{font-size:16px}
        body.dashboard-mode #dashboard .recommendation-heading p{font-size:12px;line-height:1.5}
        body.dashboard-mode #dashboard .merchant-grid{gap:12px}
        body.dashboard-mode #dashboard .merchant-stat{min-height:78px;padding:15px}
        body.dashboard-mode #dashboard .merchant-stat span{font-size:12px}
        body.dashboard-mode #dashboard .merchant-stat strong{margin-top:5px;font-size:23px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-stats{gap:12px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-queue,body.dashboard-mode #dashboard.admin-dashboard .admin-sidecard,body.dashboard-mode #dashboard.admin-dashboard .admin-storage-group{padding:18px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-application{gap:11px;padding:14px}
        body.dashboard-mode #dashboard.admin-dashboard .admin-overview-heading h2{color:#29382d}
        body.dashboard-mode #dashboard.admin-dashboard .admin-overview-heading p,body.dashboard-mode #dashboard.admin-dashboard .admin-overview-updated{color:#6d786e}
        body.dashboard-mode #dashboard.admin-dashboard .admin-overview-updated{border-color:#e2e8df;background:#fff}
        body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-card{border-color:#e2e8df;border-radius:12px;background:#fff;box-shadow:0 6px 20px rgba(32,43,37,.035)}
        body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-card h3{color:#29382d}
        body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-card>p,body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-row>span{color:#6d786e}
        body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-row>span strong{color:#29382d}
        body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-bar{background:#edf1f5}
        body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-bar span{background:#3f5f83}
        body.dashboard-mode #dashboard.admin-dashboard .admin-analysis-empty{color:#6d786e}
        body.dashboard-mode #dashboard.admin-dashboard .button-approve{background:#3f5f83}
        @media(max-width:980px){
            body.dashboard-mode #dashboard.admin-dashboard .admin-stats{grid-template-columns:repeat(2,minmax(0,1fr))}
        }
        @media(max-width:760px){
            body.dashboard-mode #dashboard.admin-dashboard .admin-analytics{grid-template-columns:minmax(0,1fr)}
            body.dashboard-mode #dashboard.admin-dashboard .admin-overview-heading{align-items:flex-start;flex-direction:column}
            body.dashboard-mode #dashboard.admin-dashboard .admin-queue-heading{align-items:flex-start}
        }
        @media(max-width:980px){
            body.dashboard-mode #dashboard .dashboard-profile-bar{grid-template-columns:minmax(0,1fr) minmax(0,1.2fr)}
            body.dashboard-mode #dashboard .dashboard-profile-actions{grid-column:1/-1}
        }
        @media(max-width:760px){
            body.landing-mode .hero{min-height:calc(100vh - 66px);min-height:calc(100svh - 66px);padding:24px 0;background-position:62% center}
            body.landing-mode .hero-copy-column{width:calc(100% - 34px);padding:25px 0}
            body.landing-mode .hero h1{font-size:clamp(40px,8vw,58px);letter-spacing:-2.3px}
            body.landing-mode .hero-copy{font-size:16px}
            body.landing-mode .hero-search{min-height:58px}
            body.landing-mode .hero-search .button{min-height:45px;padding:0 12px}
            body.landing-mode .hero-actions .button{min-height:42px}
            body.landing-mode .hero-bottom-row{margin-top:19px}
            body.landing-mode .trust-row{grid-template-columns:minmax(0,1fr);gap:15px}
            body.landing-mode .trust-row .button{justify-self:start}
            body.landing-mode #marketInfo .section-heading{grid-template-columns:minmax(0,1fr);gap:14px}
            body.landing-mode #marketInfo .section-heading .button{justify-self:start}
            body.dashboard-mode #dashboard .dashboard-profile-bar{grid-template-columns:minmax(0,1fr)}
            body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest{grid-template-columns:minmax(0,1fr) auto}
            body.dashboard-mode #dashboard .dashboard-profile-bar:not(.is-guest) .dashboard-profile-actions{grid-column:1/-1}
            body.dashboard-mode #dashboard .customer-welcome{min-height:0}
        }
        @media(max-width:480px){
            body.landing-mode .hero-copy-column{width:calc(100% - 26px)}
            body.landing-mode .hero h1{font-size:clamp(37px,10vw,46px);letter-spacing:-1.9px}
            body.landing-mode .hero-copy{font-size:15px}
            body.landing-mode .hero-search{gap:5px;padding-left:9px}
            body.landing-mode .hero-search input{font-size:13px}
            body.landing-mode .hero-search .button{padding:0 9px;font-size:11px}
            body.landing-mode .hero-search-chips{gap:6px}
            body.landing-mode .hero-search-chips strong{flex-basis:100%}
            body.landing-mode .hero-chip,body.landing-mode .hero-chip:hover{padding:7px 10px;font-size:10px}
            body.landing-mode .hero-actions{gap:7px}
            body.landing-mode .hero-actions .button{padding:0 10px;font-size:10px}
            body.landing-mode .hero-stat{padding:0 6px}
            body.landing-mode .hero-stat strong{font-size:18px}
            body.landing-mode .hero-stat span{font-size:9px}
            body.landing-mode #how{padding-top:55px;padding-bottom:55px}
            body.landing-mode .how-card{min-height:0;padding:19px}
            body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest{gap:9px;padding:11px}
            body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest .profile-button{gap:8px}
            body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest .profile-email{font-size:11px}
            body.dashboard-mode #dashboard .dashboard-profile-bar.is-guest #merchantCta{min-height:36px;padding:0 10px;font-size:10px}
            body.dashboard-mode #dashboard .customer-welcome{padding:18px}
        }
        @media(hover:none){
            body.dashboard-mode #dashboard .dashboard-nav-button:not(.active):hover{background:transparent;color:#dce5d8}
        }
        @media(hover:none){
            body.dashboard-mode #dashboard .dashboard-nav-button:not(.active):hover,
            body.dashboard-mode #dashboard .customer-highlight:hover,
            body.dashboard-mode #dashboard .merchant-stat:hover,
            body.dashboard-mode #dashboard .admin-stat:hover,
            body.dashboard-mode #dashboard .admin-checklist li:hover{transform:none}
        }
        @media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*:before,*:after{animation-duration:.01ms!important;animation-iteration-count:1!important;scroll-behavior:auto!important;transition-duration:.01ms!important}.js [data-reveal]:not(.is-visible){opacity:1;transform:none}}
    </style>
</head>
<body class="landing-mode">
    <header class="shell topbar">
        <a class="brand" href="#" id="homeLink" aria-label="Pwesto home">
            <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5.5 5h13L20 10H4l1.5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M4 10v1a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0v-1M6 13v6h12v-6M10 19v-4h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>pwesto
        </a>
        <nav class="nav-links" aria-label="Main navigation">
            <a href="#how">How it works</a><a href="#market">For vendors</a><a href="#dashboard" id="navDiscover">Explore stalls</a>
        </nav>
        <div class="nav-actions">
            <button class="text-button" id="signInTop">Sign in</button>
            <button class="button button-primary button-small" id="openDashboard">Browse markets <span aria-hidden="true">↗</span></button>
        </div>
    </header>

    <main>
        <div id="landing">
            <section class="shell hero" aria-labelledby="heroTitle">
                <div class="hero-copy-column">
                    <span class="eyebrow"><i class="eyebrow-dot"></i> Local market directory</span>
                    <h1 id="heroTitle">Find local stalls.<br><em>Shop your market.</em></h1>
                    <p class="hero-copy">Browse products, discover nearby vendors, and plan your market visit.</p>
                    <form class="hero-search" id="heroSearchForm" role="search">
                        <svg class="hero-search-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4.2 4.2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        <input id="heroSearchInput" type="search" placeholder="Search stalls, products, or markets" aria-label="Search stalls, products, or markets">
                        <button class="button button-primary" type="submit">Search <span aria-hidden="true">→</span></button>
                    </form>
                    <div class="hero-search-chips" aria-label="Explore a stall category">
                        <strong>Browse by category</strong>
                        <button class="hero-chip" type="button" data-hero-category="Fruits">Fruits</button>
                        <button class="hero-chip" type="button" data-hero-category="Vegetables">Vegetables</button>
                        <button class="hero-chip" type="button" data-hero-category="Meat">Meat</button>
                        <button class="hero-chip" type="button" data-hero-category="Seafood">Seafood</button>
                    </div>
                    <div class="hero-actions">
                        <button class="button button-light" id="heroSignIn">Sign in or create account</button>
                        <button class="button button-light" id="heroVendorSignup">Vendor application</button>
                    </div>
                    <div class="hero-bottom-row" aria-label="Pwesto market directory">
                        <div class="hero-stat"><strong id="heroStallCount">—</strong><span>Stalls</span></div>
                        <div class="hero-stat"><strong id="heroMarketCount">—</strong><span>Markets</span></div>
                        <div class="hero-stat"><strong id="heroCategoryCount">—</strong><span>Categories</span></div>
                    </div>
                </div>
            </section>

            <section class="shell section" id="how">
                <div class="section-heading" data-reveal><div><span class="eyebrow">How it works</span><h2>Everything for your market visit</h2></div></div>
                <div class="how-grid">
                    <article class="how-card" data-step="01" data-reveal><div class="how-icon" aria-hidden="true">⌖</div><h3>Find nearby stalls</h3><p>Search local vendors by product, category, or market.</p></article>
                    <article class="how-card" data-step="02" data-reveal><div class="how-icon" aria-hidden="true">✳</div><h3>Contact vendors</h3><p>Ask about products, availability, or reservations.</p></article>
                    <article class="how-card" data-step="03" data-reveal><div class="how-icon" aria-hidden="true">♡</div><h3>Save and plan</h3><p>Keep favorite stalls and upcoming visits in one place.</p></article>
                </div>
            </section>

            <section class="market-band" id="market">
                <div class="shell trust-row">
                    <div class="vendor-cta-copy"><strong>List your stall on Pwesto</strong><span>Start a vendor application.</span></div>
                    <button class="button" type="button" id="marketVendorSignup">Apply as a vendor <span aria-hidden="true">→</span></button>
                </div>
            </section>
        </div>

        <section id="dashboard" class="shell" aria-label="Market discovery dashboard">
            <div class="dashboard-layout">
                <aside class="dashboard-sidebar" aria-label="Dashboard navigation">
                    <a class="dashboard-brand" href="#" id="dashboardBrand"><span class="dashboard-brand-mark"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5.5 5h13L20 10H4l1.5-5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M4 10v1a2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0 2 2 0 0 0 4 0v-1M6 13v6h12v-6M10 19v-4h4v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span> pwesto</a>
                    <p class="dashboard-sidebar-label" id="dashboardSidebarLabel">Workspace</p>
                    <nav class="dashboard-sidebar-nav">
                        <button class="dashboard-nav-button active" type="button" data-dashboard-nav="overview"><span aria-hidden="true">▦</span><span>Dashboard</span></button>
                        <button class="dashboard-nav-button" type="button" data-dashboard-nav="discover"><span aria-hidden="true">⌕</span><span>Discover markets</span></button>
                        <button class="dashboard-nav-button customer-nav" type="button" data-dashboard-nav="saved"><span aria-hidden="true">♡</span><span>Saved stalls</span></button>
                        <button class="dashboard-nav-button customer-nav" type="button" data-dashboard-nav="visits"><span aria-hidden="true">◷</span><span>My visits</span></button>
                        <button class="dashboard-nav-button customer-nav" type="button" data-dashboard-nav="customer-orders"><span aria-hidden="true">▣</span><span>My orders</span></button>
                        <button class="dashboard-nav-button customer-nav vendor-nav" type="button" data-dashboard-nav="messages"><span aria-hidden="true">✉</span><span>Messages</span></button>
                        <button class="dashboard-nav-button vendor-nav" type="button" data-dashboard-nav="listings"><span aria-hidden="true">⌂</span><span>My stall</span></button>
                        <button class="dashboard-nav-button vendor-nav" type="button" data-dashboard-nav="products"><span aria-hidden="true">▤</span><span>Products</span></button>
                        <button class="dashboard-nav-button vendor-nav" type="button" data-dashboard-nav="add-product"><span aria-hidden="true">＋</span><span>Add product</span></button>
                        <button class="dashboard-nav-button vendor-nav" type="button" data-dashboard-nav="vendor-application"><span aria-hidden="true">▧</span><span>Rental application</span></button>
                        <button class="dashboard-nav-button vendor-nav" type="button" data-dashboard-nav="vendor-orders"><span aria-hidden="true">▣</span><span>Customer orders</span></button>
                        <button class="dashboard-nav-button admin-nav" type="button" data-dashboard-nav="review"><span aria-hidden="true">✓</span><span>Review queue</span></button>
                        <button class="dashboard-nav-button admin-nav" type="button" data-dashboard-nav="storage"><span aria-hidden="true">▤</span><span>Storage</span></button>
                    </nav>
                    <div class="dashboard-sidebar-foot"><span id="dashboardSidebarRole">Pwesto community</span></div>
                </aside>
                <div class="dashboard-main">
            <div class="dashboard-profile-bar" id="dashboardProfileBar">
                <div class="dashboard-profile-identity">
                    <div class="profile-wrap">
                        <button class="profile-button" id="profileButton" aria-label="Account menu" aria-expanded="false"><span class="profile-avatar" id="profileAvatar">G</span><span class="profile-details"><span class="profile-name" id="profileName">Guest</span><small class="profile-email" id="profileEmail">Sign in to view your account</small></span><span class="profile-chevron" aria-hidden="true">⌄</span></button>
                        <div class="profile-menu" id="profileMenu"><button id="profileSignIn">Sign in or create account</button><button id="profileSignOut" style="display:none">Sign out</button></div>
                    </div>
                    <span class="role-chip" id="accountRole" style="display:none"></span>
                </div>
                <div class="dashboard-profile-copy">
                    <span class="eyebrow" id="dashboardProfileCopyEyebrow">YOUR ACCOUNT PROFILE</span>
                    <form class="dashboard-profile-form" id="accountProfileForm" enctype="multipart/form-data">
                        <div><label for="accountProfileName">Display name</label><input type="text" id="accountProfileName" name="name" minlength="2" maxlength="80" autocomplete="name" required></div>
                        <div><label for="accountProfilePicture">Profile photo · JPEG, PNG, or WebP · up to 2 MB</label><input type="file" id="accountProfilePicture" name="picture" accept="image/jpeg,image/png,image/webp"></div>
                        <button class="button button-primary button-small" id="accountProfileSubmit" type="submit">Save profile</button>
                        <p class="dashboard-profile-feedback" id="accountProfileFeedback" aria-live="polite"></p>
                    </form>
                </div>
                <div class="dashboard-profile-actions">
                    <button class="button button-light button-small" id="dashboardReturnButton" style="display:none">Back to my dashboard</button>
                    <button class="button button-light button-small" id="merchantCta">Become a vendor</button>
                    <div class="notification-wrap" id="notificationWrap" style="display:none">
                        <button class="notification-button" id="notificationButton" type="button" aria-expanded="false" aria-controls="notificationsPanel" aria-label="Notifications">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M10 21h4"></path></svg>
                            Notifications <span class="notification-badge" id="notificationBadge" aria-live="polite"></span>
                        </button>
                        <section class="notification-panel" id="notificationsPanel" aria-label="Notifications" hidden>
                            <div class="notification-heading"><strong>Your notifications</strong><button id="markAllNotificationsRead" type="button">Mark all read</button></div>
                            <div class="notification-list" id="notificationList" aria-live="polite"><div class="notification-empty">Your notifications will appear here.</div></div>
                        </section>
                    </div>
                </div>
            </div>
            <div class="dashboard-head">
                <div><span class="eyebrow"><i class="eyebrow-dot"></i> <span id="dashboardEyebrow">MARKET DISCOVERY</span></span><h1 id="dashboardHeading">Your neighborhood market</h1><p id="dashboardSubtitle">Find fresh picks and the people behind them.</p></div>
            </div>
            <section class="customer-overview" id="customerOverview">
                <div class="customer-welcome">
                    <div class="customer-welcome-copy"><h2 id="customerGreeting">Discover your local market</h2><p>Browse nearby stalls, save favorites, and plan visits.</p></div>
                    <button class="button" id="customerExploreButton">Browse stalls <span aria-hidden="true">→</span></button>
                </div>
                <div class="customer-highlights">
                    <div class="customer-highlight"><span>Saved stalls</span><strong id="customerSavedTotal">0</strong></div>
                    <div class="customer-highlight"><span>Planned visits</span><strong id="customerVisitTotal">0</strong></div>
                </div>
            </section>
            <section class="recommendation-panel" id="customerRecommendations" aria-label="Personalized stall recommendations">
                <div class="recommendation-heading">
                    <div><h2>Recommended stalls</h2><p id="recommendationExplanation">Based on your saved stalls and interests.</p></div>
                </div>
                <div class="recommendation-list" id="recommendationList"></div>
            </section>
            <div class="dashboard-tabs" id="dashboardTabs" role="tablist" aria-label="Dashboard views">
                <button class="tab active" id="discoverTab" role="tab" aria-selected="true">Discover</button>
                <button class="tab" id="savedTab" role="tab" aria-selected="false">Saved stalls <span id="savedCount"></span></button>
                <button class="tab" id="myVisitsTab" role="tab" aria-selected="false">My visits</button>
                <button class="tab" id="messagesTab" role="tab" aria-selected="false">Messages <span id="messagesCount"></span></button>
            </div>
            <div id="discoverView">
                <div class="discover-toolbar">
                    <label class="search-wrap"><span class="search-symbol" aria-hidden="true">⌕</span><input type="search" id="searchInput" placeholder="Search stalls, products, or markets..." aria-label="Search stalls, products, or markets"></label>
                    <select class="select-filter" id="categoryFilter" aria-label="Filter by category"><option value="">All categories</option><option value="Meat">Meat</option><option value="Vegetables">Vegetables</option><option value="Fruits">Fruits</option><option value="Seafood">Seafood</option><option value="Pantry">Pantry</option><option value="Flowers">Flowers</option><option value="Produce">Produce (legacy)</option></select>
                    <button class="button button-light button-small nearby-button" id="nearbyButton">⌖ Use my location</button>
                </div>
                <div class="content-grid">
                    <section class="vendor-panel" aria-label="Nearby vendor stalls">
                        <div class="panel-header"><strong>Stalls near you</strong><span id="resultCount">Loading stalls…</span></div>
                        <div class="vendor-list" id="vendorList" data-api-base="<?php echo htmlspecialchars($appBasePath . '/api', ENT_QUOTES, 'UTF-8'); ?>" aria-live="polite"></div>
                    </section>
                    <section class="map-panel" aria-label="Calapan City Public Market map">
                        <div class="panel-header">
                            <div class="map-panel-heading">
                                <div class="map-panel-title"><strong>Calapan City Public Market</strong><span class="map-note">⌖ Tap the red pin for directions and Street View</span></div>
                                <div class="map-actions">
                                    <a class="map-action map-action-primary" href="https://www.google.com/maps/dir/?api=1&amp;destination=Calapan%20City%20Public%20Market%2C%20Calapan%20City%2C%20Oriental%20Mindoro%2C%20Philippines" target="_blank" rel="noopener noreferrer">Get directions ↗</a>
                                    <a class="map-action" href="https://www.google.com/maps/@?api=1&amp;map_action=pano&amp;viewpoint=13.4118%2C121.1809" target="_blank" rel="noopener noreferrer">Street View ↗</a>
                                </div>
                            </div>
                        </div>
                        <div id="vendorMap" aria-label="Interactive stall map"></div>
                    </section>
                </div>
            </div>
            <section class="booking-section" id="customerOrdersView" aria-label="Your product orders" style="display:none">
                <div class="panel-header order-history-heading"><strong>Your product orders</strong><span>Cash at pickup or GCash test checkout. <button class="text-button" type="button" id="refreshCustomerOrders">Refresh</button></span></div>
                <div id="customerOrderList" class="booking-empty">Sign in to see orders placed with local vendors.</div>
            </section>
            <section class="booking-section" id="visitsView" aria-label="Your visits" style="display:none">
                <h2>My visits</h2>
                <div id="bookingList" class="booking-empty">Sign in to save your market visits and stall bookings.</div>
            </section>
            <section class="message-view" id="messagesView" aria-label="Your market messages">
                <div class="message-inbox">
                    <div class="message-inbox-heading"><h2>Your conversations</h2><p>Ask a seller about today’s fresh finds, availability, or a reservation.</p></div>
                    <div id="customerConversationList"><div class="message-empty">Loading your conversations…</div></div>
                </div>
                <div class="message-inbox message-contacts" id="customerVendorContactsSection">
                    <div class="message-inbox-heading"><h2>Contact a vendor</h2><p>Browse approved market stalls and start a private conversation with vendors accepting messages.</p></div>
                    <div id="customerVendorContacts"><div class="message-empty">Loading vendor contacts…</div></div>
                </div>
            </section>
            <section class="merchant-panel" id="merchantView">
                <div class="vendor-page" data-vendor-page="overview">
                <div class="vendor-welcome">
                    <div class="vendor-welcome-copy"><h2>Manage your stall</h2><p>Update your listing, products, application, and orders.</p></div>
                    <button class="button" id="vendorMarketButton">Browse market <span aria-hidden="true">→</span></button>
                </div>
                <div class="merchant-grid">
                    <div class="merchant-stat"><span>Stall listings</span><strong id="vendorListingTotal">0</strong></div>
                    <div class="merchant-stat"><span>Live on Pwesto</span><strong id="vendorLiveTotal">0</strong></div>
                    <div class="merchant-stat"><span>Under review</span><strong id="vendorPendingTotal">0</strong></div>
                </div>
                </div>
                <section class="merchant-card product-management-card vendor-page" data-vendor-page="products">
                    <div class="merchant-card-heading"><div><h3>Your product catalog</h3><p>Add products while your application is pending. Customers can see and order them only after the stall is approved.</p></div><button class="button button-primary button-small" type="button" id="addProductFromCatalog">Add product</button></div>
                    <div class="product-stall-select">
                        <label for="productCatalogVendorSelect">Stall</label>
                        <select id="productCatalogVendorSelect" required><option value="">Choose one of your stalls</option></select>
                    </div>
                    <span class="product-count" id="managedProductCount">0 products</span>
                    <div id="vendorProductList" class="vendor-product-list"><p class="product-list-empty">Choose a stall to view its catalog.</p></div>
                </section>
                <section class="merchant-card product-management-card vendor-page" data-vendor-page="add-product">
                    <div class="merchant-card-heading"><div><h3 id="productFormHeading">Add a product</h3><p>Enter the actual name, price, and available stock. Products stay hidden until the stall is approved.</p></div></div>
                    <form id="productForm" class="merchant-form product-form">
                        <input type="hidden" id="productId" name="product_id" value="">
                        <div class="form-full"><label for="productVendorSelect">Stall</label><select id="productVendorSelect" required><option value="">Choose one of your stalls</option></select></div>
                        <div class="form-full"><label for="productName">Product name</label><input id="productName" name="name" type="text" maxlength="100" placeholder="e.g. Fresh mangoes" required></div>
                        <div class="form-full"><label for="productDescription">Description <span class="product-optional-label">Optional</span></label><textarea id="productDescription" name="description" maxlength="500" rows="3" placeholder="Add details such as size, variety, or how it is sold."></textarea></div>
                        <div class="form-full product-image-field"><label for="productImage">Product photo <span class="product-optional-label">Optional · JPEG, PNG, or WebP · up to 5 MB</span></label><input id="productImage" name="image" type="file" accept="image/jpeg,image/png,image/webp"><small class="product-image-help">Use a clear photo of the actual product. A new photo replaces the current one.</small><img id="productImagePreview" class="product-image-preview" alt="Product photo preview" hidden></div>
                        <div><label for="productPrice">Price <span class="product-optional-label">(₱)</span></label><input id="productPrice" name="price" type="number" min="0.01" max="99999999.99" step="0.01" placeholder="0.00" required></div>
                        <div><label for="productStock">Available quantity</label><input id="productStock" name="stock" type="number" min="0" max="1000000" step="1" placeholder="0" required></div>
                        <div class="form-full product-form-actions"><button class="button button-primary button-small" id="saveProductButton" type="submit">Add product</button><button class="button button-light button-small" id="cancelProductEdit" type="button" hidden>Cancel edit</button><span class="product-form-feedback" id="productFormFeedback" aria-live="polite"></span></div>
                    </form>
                </section>
                <div class="vendor-page" data-vendor-page="listings">
                <section class="vendor-profile-card" id="vendorProfileCard" aria-label="Vendor profile">
                    <div class="vendor-profile-account" id="vendorAccountProfile"></div>
                    <div class="vendor-profile-store" id="vendorStoreProfile"></div>
                </section>
                <div class="merchant-callout"><span class="merchant-callout-icon" aria-hidden="true">i</span><span id="merchantCallout">Submit your stall details for review. Approved listings appear on the public market map.</span></div>
                <div class="merchant-content-grid">
                    <section class="merchant-card">
                        <div class="merchant-card-heading"><div><h3>Your stall listings</h3><p>Track review progress and what shoppers can see.</p></div></div>
                        <div class="merchant-vendors" id="merchantVendorList"></div>
                    </section>
                </div>
                </div>
                <section class="merchant-card vendor-message-card vendor-page" data-vendor-page="messages">
                    <div class="merchant-card-heading"><div><h3>Customer messages <span id="vendorMessagesCount"></span></h3><p>Reply to neighbors asking about your stall and market finds.</p></div></div>
                    <div id="vendorConversationList"><div class="message-empty">Your customer conversations will appear here.</div></div>
                </section>
                <div class="vendor-page" data-vendor-page="vendor-application">
                    <div class="merchant-content-grid">
                    <section class="merchant-card">
                        <div class="merchant-card-heading"><div><h3 id="merchantFormHeading">Bring your stall online</h3><p id="merchantFormDescription">Tell nearby shoppers what you sell and where to find you.</p></div></div>
                        <form id="merchantForm" class="merchant-form">
                            <input type="hidden" id="stallVendorId" name="vendor_id" value="">
                            <div><label for="stallName">Stall name</label><input id="stallName" name="name" minlength="2" maxlength="100" placeholder="e.g. Aling Rosa’s Fresh Produce" required></div>
                            <div><label for="stallCategory">Category</label><select id="stallCategory" name="category" required><option value="">Choose a category</option><option>Meat</option><option>Vegetables</option><option>Fruits</option><option>Seafood</option><option>Pantry</option><option>Flowers</option></select></div>
                            <div><label for="stallMarket">Public market</label><select id="stallMarket" name="market" required><option value="">Choose your market</option><option>Calapan City Public Market</option></select></div>
                            <div><label for="stallSpecialty">What do you sell?</label><input id="stallSpecialty" name="specialty" minlength="5" maxlength="240" placeholder="Your fresh finds, specialties, or story" required></div>
                            <section class="application-requirements" id="applicationRequirements" aria-label="Required vendor documents">
                                <h4>Application requirements</h4>
                                <p>Upload clear scans. Files are private to you and authorized Pwesto administrators. PDF, JPEG, or PNG · up to 2 MB each.</p>
                                <div class="requirement-fields">
                                    <div class="requirement-field"><label for="applicationDocument">Completed vendor application form</label><input type="file" id="applicationDocument" name="application_document" accept="application/pdf,image/jpeg,image/png" required></div>
                                    <div class="requirement-field"><label for="cedulaDocument">Barangay cedula</label><input type="file" id="cedulaDocument" name="cedula_document" accept="application/pdf,image/jpeg,image/png" required></div>
                                    <div class="requirement-field"><label for="vendorTwoByTwo">2×2 ID photo (square image)</label><input type="file" id="vendorTwoByTwo" name="photo_document" accept="image/jpeg,image/png" required></div>
                                </div>
                            </section>
                            <div class="merchant-payment-note">Stall rent: <strong id="monthlyRentLabel">₱7,000 per month</strong>. After your documents are submitted, pay securely through Xendit. Admin approval requires verified payment.</div>
                            <div class="form-full" style="display:flex;gap:8px;flex-wrap:wrap"><button class="button button-primary button-small" id="merchantSubmit" type="submit">Submit rental request <span aria-hidden="true">→</span></button><button class="button button-light button-small" id="cancelVendorEdit" type="button" style="display:none">Cancel edit</button></div>
                        </form>
                    </section>
                </div>
                </div>
                <section class="merchant-card vendor-orders-card vendor-page" data-vendor-page="vendor-orders">
                    <div class="merchant-card-heading"><div><h3>Customer orders</h3><p>Review orders here. Confirmed GCash orders also send a payment email to your vendor account address.</p></div><button class="merchant-edit-button" type="button" id="refreshVendorOrders">Refresh</button></div>
                    <div id="vendorOrderList" class="vendor-order-list"><p class="booking-empty">Customer orders will appear here.</p></div>
                </section>
            </section>
            <section class="admin-panel" id="adminView">
                <div id="adminOverview">
                <div class="admin-overview-heading">
                    <div><h2>Market overview</h2><p>Current listings, marketplace activity, and applications that need review.</p></div>
                    <span class="admin-overview-updated">Live dashboard data</span>
                </div>
                <div class="admin-stats" id="adminStats"></div>
                <div class="admin-analytics">
                    <section class="admin-analysis-card">
                        <h3>Approved stalls by category</h3>
                        <p>Distribution of currently approved listings.</p>
                        <div id="adminCategoryBreakdown" class="admin-analysis-list"><div class="admin-analysis-empty">Loading category data…</div></div>
                    </section>
                    <section class="admin-analysis-card">
                        <h3>Application readiness</h3>
                        <p>Document and first-month rent checks for requests in the queue.</p>
                        <div id="adminApplicationReadiness" class="admin-analysis-list"><div class="admin-analysis-empty">Loading application data…</div></div>
                    </section>
                </div>
                <div class="admin-workspace">
                    <section class="admin-queue">
                        <div class="admin-queue-heading"><div><h3>Rental applications</h3><p>Approval requires all documents and verified first-month rent.</p></div><span id="adminQueueCount" class="admin-queue-count">0 to review</span></div>
                        <div id="adminApplications"></div>
                    </section>
                </div>
                <section class="admin-queue" style="margin-top:14px">
                    <div class="admin-queue-heading">
                        <div><h3>Unassigned vendor accounts</h3><p>Link an existing stall to its real vendor. The vendor sets their password using “Forgot password.”</p></div>
                        <span id="unassignedStallCount" class="admin-queue-count">0 stalls</span>
                    </div>
                    <div id="adminUnassignedStalls"><div class="admin-empty"><strong>Loading stalls…</strong></div></div>
                </section>
                </div>
                <section class="admin-storage-section" id="adminStorageView" style="display:none" aria-label="Admin files and market data storage">
                    <div class="admin-queue-heading">
                        <div><h3>Admin storage</h3><p>Browse stall photos, the complete product catalog, uploaded product photos, and vendor application files. Sample photos may remain hosted by their source; vendor documents are visible only to administrators.</p></div>
                        <button class="merchant-edit-button" type="button" id="refreshAdminStorage">Refresh storage</button>
                    </div>
                    <div class="admin-storage-grid">
                        <section class="admin-storage-group" aria-labelledby="adminRentalRecordsTitle">
                            <h2 id="adminRentalRecordsTitle">Vendor applications · rental payments</h2>
                            <p>Application documents and recorded monthly stall-rent payment status for every stall. Missing documents or payments are shown as missing/unpaid; they are not generated here.</p>
                            <span class="admin-storage-count" id="adminRentalRecordCount">0 stalls</span>
                            <div class="admin-storage-documents" id="adminRentalRecords"><div class="admin-storage-empty">Loading vendor records…</div></div>
                        </section>
                        <section class="admin-storage-group" aria-labelledby="adminStallStorageTitle">
                            <h2 id="adminStallStorageTitle">Market stall photos</h2>
                            <p>Stall images and their category, code, and review status.</p>
                            <span class="admin-storage-count" id="adminStallStorageCount">0 stalls</span>
                            <div class="admin-storage-photos" id="adminStallStorage"><div class="admin-storage-empty">Loading stall photos…</div></div>
                        </section>
                        <section class="admin-storage-group" aria-labelledby="adminCatalogStorageTitle">
                            <h2 id="adminCatalogStorageTitle">Market datasets · products</h2>
                            <p>Product names, descriptions, prices, stock, and sample-data status for every stall.</p>
                            <span class="admin-storage-count" id="adminCatalogStorageCount">0 products</span>
                            <div class="admin-storage-documents" id="adminCatalogStorage"><div class="admin-storage-empty">Loading catalog data…</div></div>
                        </section>
                        <section class="admin-storage-group" aria-labelledby="adminProductStorageTitle">
                            <h2 id="adminProductStorageTitle">Product photos</h2>
                            <p>Uploaded product images are served through Pwesto after listing access is checked.</p>
                            <span class="admin-storage-count" id="adminProductStorageCount">0 photos</span>
                            <div class="admin-storage-photos" id="adminProductStorage"><div class="admin-storage-empty">Loading product photos…</div></div>
                        </section>
                        <section class="admin-storage-group" aria-labelledby="adminDocumentStorageTitle">
                            <h2 id="adminDocumentStorageTitle">Vendor application files</h2>
                            <p>Application forms, cedulas, and ID photos. Only admins can open these files.</p>
                            <span class="admin-storage-count" id="adminDocumentStorageCount">0 files</span>
                            <div class="admin-storage-documents" id="adminDocumentStorage"><div class="admin-storage-empty">Loading vendor files…</div></div>
                        </section>
                    </div>
                </section>
            </section>
                </div>
            </div>
            <div class="market-assistant" id="marketAssistant">
                <section class="assistant-panel" id="assistantPanel" role="dialog" aria-modal="false" aria-labelledby="assistantTitle" hidden>
                    <header class="assistant-header">
                        <span class="assistant-avatar" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5a8.5 8.5 0 0 0-7.3 12.9L4 20l3.8-.7A8.5 8.5 0 1 0 12 3.5Z"></path><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"></path></svg></span>
                        <span class="assistant-heading"><strong id="assistantTitle">Pwesto assistant</strong><small><span class="assistant-online" aria-hidden="true"></span>Handa sumagot tungkol sa palengke</small></span>
                        <button class="assistant-close" id="assistantClose" type="button" aria-label="Isara ang assistant">×</button>
                    </header>
                    <p class="assistant-disclaimer">Magtanong tungkol sa produkto, presyo, order, stall application, o paggamit ng Pwesto.</p>
                    <div class="assistant-messages" id="assistantMessages" aria-live="polite" aria-relevant="additions"></div>
                    <div class="assistant-suggestions" id="assistantSuggestions" aria-label="Mga mungkahing tanong"></div>
                    <form class="assistant-form" id="assistantForm">
                        <input class="assistant-input" id="assistantInput" type="text" maxlength="3000" autocomplete="off" placeholder="Magtanong tungkol sa Pwesto, produkto, o presyo…" aria-label="Magtanong sa Pwesto assistant" required>
                        <button class="assistant-send" type="submit" aria-label="Ipadala ang tanong"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z"></path><path d="M22 2 11 13"></path></svg></button>
                    </form>
                </section>
                <button class="assistant-launcher" id="assistantLauncher" type="button" aria-expanded="false" aria-controls="assistantPanel">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3.5a8.5 8.5 0 0 0-7.3 12.9L4 20l3.8-.7A8.5 8.5 0 1 0 12 3.5Z"></path><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"></path></svg>
                    <span>Magtanong sa Pwesto</span>
                </button>
            </div>
        </section>

        <section class="shell section" id="marketInfo" aria-labelledby="marketInfoTitle">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">About Pwesto</span>
                    <h2 id="marketInfoTitle">One place to find your local market</h2>
                    <p>Discover market stalls and products, contact vendors, and plan visits. Vendors can apply to list their stall; admins review applications before they go live.</p>
                </div>
                <button class="button button-primary" id="bottomExplore">Explore stalls <span aria-hidden="true">→</span></button>
            </div>
        </section>
    </main>

    <footer class="footer"><div class="shell footer-inner"><span>© <span id="year"></span> Pwesto</span></div></footer>

    <div class="modal-backdrop" id="authModal" role="dialog" aria-modal="true" aria-labelledby="authTitle">
        <div class="modal">
            <button class="modal-close" data-close-modal aria-label="Close">×</button>
            <span class="eyebrow">WELCOME TO PWESTO</span>
            <h2 id="authTitle">Your local market, closer.</h2>
            <p id="authDescription">Sign in to save your favorite stalls and plan your next market visit.</p>
            <div class="auth-switch" role="tablist" aria-label="Account access">
                <button class="auth-mode active" type="button" id="signInMode" role="tab" aria-selected="true">Sign in</button>
                <button class="auth-mode" type="button" id="signUpMode" role="tab" aria-selected="false">Create account</button>
            </div>
            <form id="emailAuthForm" class="auth-form" novalidate>
                <div id="authNameField" style="display:none"><label for="authName">Your name</label><input id="authName" name="name" type="text" autocomplete="name" maxlength="80" placeholder="How should we call you?"></div>
                <div id="authRoleField" class="role-form-field" style="display:none"><label for="authRole">I’m joining Pwesto as</label><select id="authRole" name="role"><option value="customer">Customer — discover and visit market stalls</option><option value="vendor">Vendor — apply to list my market stall</option></select></div>
                <label for="authEmail">Email address</label><input id="authEmail" name="email" type="email" autocomplete="email" maxlength="254" placeholder="you@example.com" required>
                <label for="authPassword">Password</label><input id="authPassword" name="password" type="password" autocomplete="current-password" minlength="8" maxlength="72" placeholder="At least 8 characters" required>
                <button class="text-button" id="forgotPasswordButton" type="button">Forgot password?</button>
                <div id="authConfirmField" style="display:none"><label for="authConfirmPassword">Confirm password</label><input id="authConfirmPassword" name="confirm_password" type="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Enter your password again"></div>
                <p id="authFormMessage" class="auth-form-message" aria-live="polite"></p>
                <button id="emailAuthSubmit" class="button button-primary" type="submit">Sign in with email</button>
            </form>
            <div class="auth-divider"><span>or continue with Google</span></div>
            <div id="googleButton" class="google-area"></div>
            <p id="googleHelp" class="auth-help">Google sign-in is optional. You can use an email address and password instead.</p>
            <form id="passwordResetForm" class="auth-form" hidden>
                <label for="resetPassword">New password</label><input id="resetPassword" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                <label for="resetPasswordConfirm">Confirm new password</label><input id="resetPasswordConfirm" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
                <p id="passwordResetMessage" class="auth-form-message" aria-live="polite"></p>
                <button class="button button-primary" type="submit" id="passwordResetSubmit">Reset password</button>
                <button class="text-button" type="button" id="cancelPasswordReset">Back to sign in</button>
            </form>
        </div>
    </div>
    <div class="modal-backdrop" id="chatModal" role="dialog" aria-modal="true" aria-labelledby="chatTitle">
        <section class="modal chat-modal">
            <header class="chat-header"><div class="chat-heading"><strong id="chatTitle">Market conversation</strong><small id="chatSubtitle">Direct messages on Pwesto</small></div><button class="chat-close" type="button" data-close-modal aria-label="Close conversation">×</button></header>
            <div class="chat-start-note" id="chatStartNote">Ask about availability, pricing, or reserve something for your next market visit.</div>
            <div class="chat-suggestions" id="chatSuggestions"><button type="button" data-chat-suggestion="May stock pa po kayo nito?">May stock pa po?</button><button type="button" data-chat-suggestion="May discount po ba kung maramihan?">Ask about a discount</button><button type="button" data-chat-suggestion="Pa-reserve po sana ng isang order.">Request a reservation</button></div>
            <div class="chat-messages" id="chatMessages" aria-live="polite"><div class="message-empty">Send the first message to start the conversation.</div></div>
            <form class="chat-form" id="chatForm"><textarea id="chatInput" maxlength="2000" rows="2" placeholder="Write a message…" aria-label="Write a message" required></textarea><button class="button button-primary button-small" type="submit">Send</button></form>
        </section>
    </div>
    <div class="modal-backdrop" id="bookingModal" role="dialog" aria-modal="true" aria-labelledby="bookingTitle">
        <form class="modal" id="bookingForm"><button type="button" class="modal-close" data-close-modal aria-label="Close">×</button><span class="eyebrow">PLAN A MARKET VISIT</span><h2 id="bookingTitle">Save a spot at the stall</h2><p id="bookingVendorName">Choose a time that works for you.</p><label for="visitDate">Visit date</label><input type="date" id="visitDate" required><label for="visitTime">Preferred time</label><select id="visitTime" required><option value="">Choose a time</option><option>8:00 AM</option><option>9:00 AM</option><option>10:00 AM</option><option>11:00 AM</option><option>1:00 PM</option><option>2:00 PM</option><option>3:00 PM</option></select><button class="button button-primary" type="submit">Save my visit →</button></form>
    </div>
    <div class="toast" id="toast" role="status" aria-live="polite"></div>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script type="module" src="<?php echo htmlspecialchars($appBasePath . '/assets/pwesto-react.js?v=' . $reactJsVersion, ENT_QUOTES, 'UTF-8'); ?>"></script>
    <script type="module" nonce="<?php echo defined('CSP_NONCE') ? CSP_NONCE : ''; ?>">
        (function () {
            'use strict';
            document.documentElement.classList.add('js');
            const apiBase = <?php echo json_encode($appBasePath . '/api', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const googleClientId = <?php echo json_encode($google_client_id ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            const featuredMarket = { name: 'Calapan City Public Market', address: 'Calapan City, Oriental Mindoro, Philippines', lat: 13.4118, lng: 121.1809 };
            const defaultCenter = [featuredMarket.lat, featuredMarket.lng];
            const state = { user: null, csrf: '', vendors: [], appointments: [], favorites: [], myVendors: [], customerOrders: [], vendorOrders: [], productVendorId: '', managedProducts: [], vendorStatusLoaded: false, vendorStatusLoading: false, rentalFee: 7000, paymentConfigured: false, conversations: [], notifications: [], notificationPolling: null, notificationsLoading: false, notificationsUpdating: false, notificationRevision: 0, notificationUnreadCount: 0, notificationErrorShown: false, activeConversationId: null, chatVendor: null, pendingMessageIntent: null, chatPolling: null, inboxPolling: null, inboxLoading: false, threadLoading: false, adminSummary: null, adminApplications: [], adminUnassignedStalls: [], adminDashboardLoaded: false, adminDashboardLoading: false, adminStorage: null, adminStorageLoading: false, assistantHistory: [], assistantBusy: false, map: null, markers: new Map(), googleInitialized: false, activeVendor: null, activeTab: 'discover', saved: new Set(loadSaved('guest')) };
            const $ = (id) => document.getElementById(id);
            let toastTimer;
            let authMode = 'login';
            let passwordResetToken = '';

            function savedKey() {
                return 'pwesto-saved-' + (state.user && state.user.id ? state.user.id : 'guest');
            }
            function interestHistoryKey() {
                return 'pwesto-interests-' + (state.user && state.user.id ? state.user.id : 'guest');
            }
            function loadSaved(accountId) {
                try {
                    const stored = JSON.parse(localStorage.getItem('pwesto-saved-' + accountId) || '[]');
                    return Array.isArray(stored) ? stored.map(String) : [];
                } catch (error) {
                    return [];
                }
            }
            function loadInterestHistory() {
                try {
                    const stored = JSON.parse(localStorage.getItem(interestHistoryKey()) || '[]');
                    return Array.isArray(stored) ? stored.filter(category => typeof category === 'string') : [];
                } catch (error) {
                    return [];
                }
            }
            function recordVendorInterest(vendor) {
                if (!state.user || state.user.role !== 'customer' || !vendor || !vendor.category) return;
                recordCategoryInterest(vendor.category);
            }
            function recordCategoryInterest(category) {
                if (!state.user || state.user.role !== 'customer' || !category) return;
                const history = loadInterestHistory().filter(item => item !== category);
                history.push(category);
                localStorage.setItem(interestHistoryKey(), JSON.stringify(history.slice(-8)));
                renderRecommendations();
            }
            function showToast(message, isError) {
                const toast = $('toast');
                toast.textContent = message;
                toast.classList.toggle('error', !!isError);
                toast.classList.add('show');
                clearTimeout(toastTimer);
                toastTimer = setTimeout(() => toast.classList.remove('show'), 3200);
            }
            window.pwestoPlaceOrder = async (vendorId, items, deliveryMethod, deliveryAddress, pickupAt, paymentMethod) => {
                if (!state.user || state.user.role !== 'customer') {
                    showToast(state.user ? 'Sign in with a customer account to place an order.' : 'Sign in or create a customer account to place your order.');
                    if (!state.user) {
                        setAuthMode('login');
                        openModal('authModal');
                    }
                    return false;
                }
                const result = await api('/orders', {
                    method: 'POST',
                    body: {
                        vendor_id: vendorId,
                        items: items,
                        delivery_method: deliveryMethod,
                        delivery_address: deliveryAddress,
                        pickup_at: pickupAt,
                        payment_method: paymentMethod
                    }
                });
                if (result.checkout_url) {
                    window.location.assign(result.checkout_url);
                    return true;
                }
                await loadCustomerOrders(true);
                const message = deliveryMethod === 'delivery'
                    ? result.email_sent
                        ? 'Delivery order sent. We emailed you status updates.'
                        : 'Delivery order sent, but its email update could not be sent. Check Gmail settings.'
                    : 'Your pickup order was sent to the vendor. Pay in cash at pickup.';
                showToast(message, deliveryMethod === 'delivery' && !result.email_sent);
                return true;
            };
            function setupLandingInteractions() {
                const header = document.querySelector('.topbar');
                window.addEventListener('scroll', () => {
                    header.classList.toggle('is-scrolled', window.scrollY > 8);
                }, { passive: true });
                if ('IntersectionObserver' in window) {
                    const revealObserver = new IntersectionObserver(entries => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('is-visible');
                                revealObserver.unobserve(entry.target);
                            }
                        });
                    }, { threshold: 0.12 });
                    document.querySelectorAll('[data-reveal]').forEach(element => revealObserver.observe(element));
                } else {
                    document.querySelectorAll('[data-reveal]').forEach(element => element.classList.add('is-visible'));
                }
            }
            function renderLandingStats(available) {
                const stalls = available ? state.vendors.length : null;
                const markets = available ? new Set(state.vendors.map(vendor => vendor.market)).size : null;
                const categories = available ? new Set(state.vendors.map(vendor => vendor.category)).size : null;
                $('heroStallCount').textContent = stalls === null ? '—' : stalls.toLocaleString('en-PH');
                $('heroMarketCount').textContent = markets === null ? '—' : markets.toLocaleString('en-PH');
                $('heroCategoryCount').textContent = categories === null ? '—' : categories.toLocaleString('en-PH');
            }
            function exploreFromLanding(search, category) {
                $('searchInput').value = search || '';
                $('categoryFilter').value = category || '';
                switchView('dashboard');
                showMarketDiscovery();
                if (search) $('searchInput').focus({ preventScroll: true });
            }
            function validateRequirementFiles(fileInputs) {
                const files = Array.from(fileInputs).map(input => input.files && input.files[0]).filter(Boolean);
                if (files.some(file => file.size > 2 * 1024 * 1024)) {
                    return 'Each requirement file must be no larger than 2 MB.';
                }
                if (files.reduce((total, file) => total + file.size, 0) > 10 * 1024 * 1024) {
                    return 'The selected requirement files are too large to upload together. Choose smaller files and try again.';
                }
                return '';
            }
            async function api(path, options) {
                const config = Object.assign({ credentials: 'same-origin', headers: {} }, options || {});
                config.headers = Object.assign({ 'Accept': 'application/json' }, config.headers);
                if (config.body && typeof config.body !== 'string' && !(config.body instanceof FormData)) {
                    config.headers['Content-Type'] = 'application/json';
                    config.body = JSON.stringify(config.body);
                }
                if (config.method && config.method !== 'GET' && state.csrf) config.headers['X-CSRF-Token'] = state.csrf;
                let response;
                try {
                    response = await fetch(apiBase + path, config);
                } catch (error) {
                    if (error instanceof TypeError) {
                        throw new Error('We couldn’t reach Pwesto just now. Check your connection or try again shortly.');
                    }
                    throw error;
                }
                let result;
                try {
                    result = await response.json();
                } catch (error) {
                    const contentType = response.headers.get('Content-Type') || 'unknown content type';
                    if (response.status === 413) {
                        throw new Error('The server rejected this upload because it is too large. Keep each requirement file under 2 MB and try again.');
                    }
                    if (response.status >= 500) {
                        throw new Error(`Pwesto could not process this request (HTTP ${response.status}). Please try again later; if it continues, share this status with the site administrator.`);
                    }
                    throw new Error(`Pwesto returned an unexpected response (HTTP ${response.status}, ${contentType}). Refresh the page and try again.`);
                }
                if (response.status === 403
                    && result.error === 'Your session has expired. Refresh the page and try again.'
                    && config.method
                    && config.method !== 'GET'
                    && path !== '/session') {
                    let refreshedSession;
                    try {
                        const sessionResponse = await fetch(apiBase + '/session', {
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json' }
                        });
                        refreshedSession = await sessionResponse.json();
                        if (!sessionResponse.ok || typeof refreshedSession.csrf_token !== 'string') {
                            throw new Error('The session could not be refreshed.');
                        }
                    } catch (error) {
                        throw new Error('Your session expired. Refresh the page and try again.');
                    }
                    state.csrf = refreshedSession.csrf_token;
                    state.user = refreshedSession.user || null;
                    state.favorites = refreshedSession.favorites || [];
                    state.appointments = refreshedSession.appointments || [];
                    updateProfile();
                    config.headers['X-CSRF-Token'] = state.csrf;
                    try {
                        response = await fetch(apiBase + path, config);
                        result = await response.json();
                    } catch (error) {
                        if (error instanceof TypeError) {
                            throw new Error('We couldn’t reach Pwesto just now. Check your connection or try again shortly.');
                        }
                        const contentType = response.headers.get('Content-Type') || 'unknown content type';
                        throw new Error(`Pwesto returned an unexpected response (HTTP ${response.status}, ${contentType}). Refresh the page and try again.`);
                    }
                }
                if (!response.ok) throw new Error(result.error || 'Something went wrong. Please try again.');
                return result;
            }
            function escapeHtml(value) {
                return String(value).replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[character]);
            }
            function switchView(view) {
                const dashboard = view === 'dashboard';
                document.body.classList.toggle('landing-mode', !dashboard);
                document.body.classList.toggle('dashboard-mode', dashboard);
                $('landing').style.display = dashboard ? 'none' : '';
                $('marketInfo').style.display = dashboard ? 'none' : '';
                $('dashboard').style.display = dashboard ? 'block' : 'none';
                if (dashboard) {
                    $('dashboard').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    if (state.map) setTimeout(() => state.map.invalidateSize(), 150);
                } else window.scrollTo({ top: 0, behavior: 'smooth' });
            }
            function updateProfile() {
                state.saved = new Set(state.user ? state.favorites.map(String) : loadSaved('guest'));
                renderVendors();
                const avatar = $('profileAvatar');
                const name = $('profileName');
                name.textContent = state.user && state.user.name ? state.user.name : 'Guest';
                $('profileEmail').textContent = state.user && state.user.email
                    ? state.user.email
                    : 'Sign in to view your account';
                $('accountProfileName').value = state.user && state.user.name ? state.user.name : '';
                $('accountProfileForm').style.display = state.user ? 'grid' : 'none';
                $('dashboardProfileBar').classList.toggle('is-guest', !state.user);
                $('dashboardProfileCopyEyebrow').textContent = 'Your account';
                if (state.user && state.user.picture) {
                    avatar.outerHTML = '<img class="profile-avatar" id="profileAvatar" src="' + escapeHtml(state.user.picture) + '" alt="">';
                    const profileImage = $('profileAvatar');
                    profileImage.addEventListener('error', () => {
                        profileImage.outerHTML = '<span class="profile-avatar" id="profileAvatar">'
                            + escapeHtml(state.user && state.user.name ? state.user.name.charAt(0).toUpperCase() : 'G')
                            + '</span>';
                    }, { once: true });
                } else {
                    $('profileAvatar').textContent = state.user && state.user.name ? state.user.name.charAt(0).toUpperCase() : 'G';
                    $('profileAvatar').outerHTML = '<span class="profile-avatar" id="profileAvatar">' + escapeHtml(state.user && state.user.name ? state.user.name.charAt(0).toUpperCase() : 'G') + '</span>';
                }
                $('profileSignIn').style.display = state.user ? 'none' : '';
                $('profileSignOut').style.display = state.user ? '' : 'none';
                $('notificationWrap').style.display = state.user ? '' : 'none';
                updateRoleDashboard();
                renderAppointments();
                startInboxPolling();
                startNotificationPolling();
            }
            function showDashboardPanel(panel) {
                $('discoverView').style.display = panel === 'discover' ? '' : 'none';
                $('customerOrdersView').style.display = panel === 'customer-orders' ? '' : 'none';
                $('visitsView').style.display = panel === 'visits' ? '' : 'none';
                $('messagesView').style.display = panel === 'messages' ? 'block' : 'none';
                $('merchantView').style.display = panel === 'vendor' ? 'block' : 'none';
                $('adminView').style.display = panel === 'admin' ? 'block' : 'none';
                $('dashboardTabs').style.display = panel === 'discover' || panel === 'messages' || panel === 'visits' ? '' : 'none';
                $('customerOverview').style.display = (panel === 'discover' || panel === 'messages') && (!state.user || state.user.role === 'customer') ? 'grid' : 'none';
                $('customerRecommendations').style.display = panel === 'discover' && state.user && state.user.role === 'customer' ? '' : 'none';
                if (panel === 'discover' && state.map) setTimeout(() => state.map.invalidateSize(), 100);
            }
            function showVendorSection(section) {
                const pages = {
                    overview: ['Vendor dashboard', 'A quick overview of your stall activity.'],
                    listings: ['My stall', 'Manage your stall profile and listing status.'],
                    products: ['Product catalog', 'Browse and manage the products customers can see.'],
                    'add-product': ['Add product', 'Enter a new product for one of your stalls.'],
                    'vendor-application': ['Rental application', 'Submit or update your stall details and required documents.'],
                    messages: ['Customer messages', 'Reply to neighbors asking about your stall and products.'],
                    'vendor-orders': ['Customer orders', 'Review and update orders placed with your stall.']
                };
                const page = pages[section] ? section : 'overview';
                document.querySelectorAll('#merchantView [data-vendor-page]').forEach(view => {
                    view.style.display = view.dataset.vendorPage === page ? 'block' : 'none';
                });
                document.querySelectorAll('.dashboard-nav-button').forEach(button => {
                    button.classList.toggle('active', button.dataset.dashboardNav === page);
                });
                $('dashboardEyebrow').textContent = 'VENDOR PORTAL';
                $('dashboardHeading').textContent = pages[page][0];
                $('dashboardSubtitle').textContent = pages[page][1];
            }
            function showAdminSection(section) {
                const storage = section === 'storage';
                $('adminOverview').style.display = storage ? 'none' : '';
                $('adminStorageView').style.display = storage ? '' : 'none';
                $('dashboardEyebrow').textContent = storage ? 'FILE STORAGE' : 'ADMIN CONSOLE';
                $('dashboardHeading').textContent = storage ? 'Pwesto file storage' : 'Pwesto administration';
                $('dashboardSubtitle').textContent = storage
                    ? 'Product photos and vendor application files.'
                    : 'Review vendor applications and manage market listings.';
            }
            function updateRoleDashboard() {
                const role = state.user && state.user.role ? state.user.role : 'customer';
                $('dashboard').classList.remove('customer-dashboard', 'vendor-dashboard', 'admin-dashboard');
                $('dashboard').classList.add(role + '-dashboard');
                $('dashboardSidebarLabel').textContent = role === 'admin' ? 'Market operations' : role === 'vendor' ? 'Seller workspace' : 'My market';
                $('dashboardSidebarRole').textContent = role === 'admin' ? 'Administrator' : role === 'vendor' ? 'Vendor account' : 'Customer account';
                $('accountRole').textContent = role === 'admin' ? 'Administrator' : role === 'vendor' ? 'Vendor account' : 'Customer account';
                document.querySelectorAll('.dashboard-nav-button').forEach(button => {
                    const vendorNav = button.classList.contains('vendor-nav');
                    const customerNav = button.classList.contains('customer-nav');
                    const adminNav = button.classList.contains('admin-nav');
                    button.style.display = adminNav ? role === 'admin' ? '' : 'none'
                        : vendorNav ? role === 'vendor' || (button.dataset.dashboardNav === 'messages' && role === 'customer') ? '' : 'none'
                            : customerNav ? role === 'customer' ? '' : 'none' : '';
                    button.classList.toggle('active', button.dataset.dashboardNav === 'overview');
                });
                $('accountRole').textContent = role;
                $('accountRole').style.display = state.user ? '' : 'none';
                $('merchantCta').style.display = role === 'customer' ? '' : 'none';
                $('customerOverview').style.display = role === 'customer' ? 'grid' : 'none';
                $('refreshCustomerOrders').hidden = !state.user || role !== 'customer';
                if (role === 'admin') {
                    $('dashboardReturnButton').style.display = 'none';
                    showAdminSection('overview');
                    showDashboardPanel('admin');
                    loadAdminDashboard();
                } else if (role === 'vendor') {
                    $('dashboardReturnButton').style.display = 'none';
                    showDashboardPanel('vendor');
                    showVendorSection('overview');
                    loadMyVendors();
                    loadVendorOrders(true);
                } else {
                    $('dashboardReturnButton').style.display = 'none';
                    $('dashboardEyebrow').textContent = 'MARKET DISCOVERY';
                    const firstName = state.user && state.user.name ? state.user.name.split(' ')[0] : '';
                    $('dashboardHeading').textContent = firstName ? 'Welcome back, ' + firstName : 'Your neighborhood market';
                    $('dashboardSubtitle').textContent = 'Find stalls and products near you.';
                    $('customerGreeting').textContent = firstName ? 'Welcome back, ' + firstName : 'Discover your local market';
                    showDashboardPanel('discover');
                    loadCustomerOrders(true);
                }
                $('customerSavedTotal').textContent = state.saved.size;
                $('customerVisitTotal').textContent = state.appointments.filter(visit => visit.status !== 'Cancelled').length;
                assistantSuggestions();
            }
            function filteredVendors() {
                const query = $('searchInput').value.trim().toLowerCase();
                const category = $('categoryFilter').value;
                return state.vendors.filter(vendor => {
                    const matchesCategory = !category || vendor.category === category;
                    const searchable = [vendor.name, vendor.category, vendor.market, vendor.specialty, vendor.product_names].join(' ').toLowerCase();
                    const matchesQuery = !query || searchable.includes(query);
                    return matchesCategory && matchesQuery && (state.activeTab !== 'saved' || state.saved.has(vendor.id));
                });
            }
            function renderRecommendations() {
                const target = $('recommendationList');
                if (!state.user || state.user.role !== 'customer') {
                    $('customerRecommendations').style.display = 'none';
                    return;
                }
                $('customerRecommendations').style.display = $('discoverView').style.display === 'none' ? 'none' : '';
                const preferred = new Map();
                const addPreference = (category, weight, reason) => {
                    if (!category) return;
                    const current = preferred.get(category) || { score: 0, reason: reason };
                    current.score += weight;
                    if (weight > current.score - weight) current.reason = reason;
                    preferred.set(category, current);
                };
                state.vendors.forEach(vendor => {
                    if (state.saved.has(String(vendor.id))) addPreference(vendor.category, 5, 'Similar to a stall you saved');
                });
                state.appointments.filter(visit => visit.status !== 'Cancelled').forEach(visit => {
                    const vendor = state.vendors.find(item => String(item.id) === String(visit.vendor_id));
                    if (vendor) addPreference(vendor.category, 4, 'Fits your planned market visits');
                });
                loadInterestHistory().forEach(category => addPreference(category, 2, 'Based on stalls you explored'));

                const recommendations = state.vendors
                    .filter(vendor => !state.saved.has(String(vendor.id)))
                    .map(vendor => {
                        const rating = Number(vendor.rating) || 0;
                        const distanceMatch = String(vendor.distance || '').match(/([\\d.]+)\\s*(km|m)/i);
                        const distance = distanceMatch ? Number(distanceMatch[1]) * (distanceMatch[2].toLowerCase() === 'm' ? 0.001 : 1) : 10;
                        const preference = preferred.get(vendor.category);
                        return {
                            vendor: vendor,
                            score: (preference ? preference.score * 3 : 0) + rating * 1.5 + Math.max(0, 1.5 - distance * 0.5),
                            reason: preference ? preference.reason : 'A top-rated local pick'
                        };
                    })
                    .sort((first, second) => second.score - first.score || first.vendor.name.localeCompare(second.vendor.name))
                    .slice(0, 3);
                target.innerHTML = recommendations.length
                    ? recommendations.map(item => `<button class="recommendation-card" type="button" data-recommendation-vendor="${escapeHtml(item.vendor.id)}"><img src="${escapeHtml(item.vendor.image)}" alt="" loading="lazy"><span class="recommendation-copy"><strong>${escapeHtml(item.vendor.name)}</strong><small>${escapeHtml(item.vendor.category)} · ★ ${escapeHtml(item.vendor.rating)} · ${escapeHtml(item.vendor.distance)}</small><small class="recommendation-reason">${escapeHtml(item.reason)}</small></span></button>`).join('')
                    : '<p class="booking-empty">Save or explore a stall to get more personalized recommendations.</p>';
            }
            function assistantRole() {
                return state.user && ['customer', 'vendor', 'admin'].includes(state.user.role) ? state.user.role : 'customer';
            }
            function assistantSuggestions() {
                const suggestions = {
                    customer: ['May seafood ba na pasok sa ₱450?', 'Magkano ang bangus?', 'Paano mag-order sa Pwesto?'],
                    vendor: ['Anong requirements sa rental application?', 'Paano magdagdag ng produkto?', 'Paano ko makikita ang orders?'],
                    admin: ['Saan makikita ang rental documents?', 'Ano ang requirements bago mag-approve?', 'Paano i-link ang vendor sa stall?']
                };
                const target = $('assistantSuggestions');
                target.replaceChildren();
                suggestions[assistantRole()].forEach(label => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'assistant-suggestion';
                    button.dataset.assistantSuggestion = label;
                    button.textContent = label;
                    target.appendChild(button);
                });
            }
            function appendAssistantMessage(message, type, vendors, actions) {
                const messages = $('assistantMessages');
                const bubble = document.createElement('div');
                bubble.className = 'assistant-message ' + (type === 'user' ? 'is-user' : type === 'error' ? 'is-error' : 'is-assistant');
                bubble.textContent = message;
                messages.appendChild(bubble);
                if (vendors && vendors.length) {
                    const list = document.createElement('div');
                    list.className = 'assistant-recommendations';
                    vendors.forEach(vendor => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'assistant-recommendation';
                        button.dataset.assistantVendor = String(vendor.id);
                        const image = document.createElement('img');
                        image.src = vendor.image || '';
                        image.alt = '';
                        image.loading = 'lazy';
                        const copy = document.createElement('span');
                        copy.className = 'assistant-recommendation-copy';
                        const name = document.createElement('strong');
                        name.textContent = vendor.name || 'Local stall';
                        const detail = document.createElement('small');
                        detail.textContent = [vendor.category, vendor.market, vendor.distance ? vendor.distance + ' away' : ''].filter(Boolean).join(' · ');
                        const arrow = document.createElement('span');
                        arrow.className = 'assistant-recommendation-arrow';
                        arrow.setAttribute('aria-hidden', 'true');
                        arrow.textContent = '→';
                        copy.append(name, detail);
                        button.append(image, copy, arrow);
                        list.appendChild(button);
                    });
                    messages.appendChild(list);
                }
                if (actions && actions.length) {
                    const actionList = document.createElement('div');
                    actionList.className = 'assistant-actions';
                    actions.forEach(action => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'assistant-action';
                        button.dataset.assistantAction = action.action;
                        button.textContent = action.label;
                        actionList.appendChild(button);
                    });
                    messages.appendChild(actionList);
                }
                messages.scrollTop = messages.scrollHeight;
            }
            function getAssistantVendorRecommendations(query) {
                const vendors = state.vendors;
                if (!vendors.length) return { matches: [], requestedCategory: '', noMatch: false };
                const normalized = query.toLowerCase();
                const categories = [
                    { terms: ['produce', 'vegetable', 'vegetables', 'fruit', 'fruits', 'gulay'], matches: ['produce', 'vegetable', 'fruit'] },
                    { terms: ['seafood', 'fish', 'isda', 'shrimp', 'hipon'], matches: ['seafood', 'fish'] },
                    { terms: ['meat', 'poultry', 'chicken', 'beef', 'baboy', 'manok'], matches: ['meat', 'poultry', 'chicken'] },
                    { terms: ['pantry', 'groceries', 'grocery', 'sari-sari'], matches: ['pantry', 'grocery'] },
                    { terms: ['flower', 'flowers', 'bulaklak'], matches: ['flower'] }
                ];
                const requested = categories.find(category => category.terms.some(term => normalized.includes(term)));
                const dynamicCategory = vendors.find(vendor => vendor.category && normalized.includes(String(vendor.category).toLowerCase()));
                const requestedCategory = requested ? requested.matches : dynamicCategory ? [String(dynamicCategory.category).toLowerCase()] : [];
                const stopWords = new Set(['recommend', 'recommendation', 'recommendations', 'recommended', 'suggest', 'suggestions', 'show', 'find', 'me', 'my', 'for', 'the', 'a', 'an', 'please', 'what', 'is', 'near', 'nearby', 'nearer', 'around', 'here', 'best', 'top', 'local', 'stall', 'stalls', 'market', 'markets', 'good', 'some', 'want', 'looking', 'something', 'po', 'ako', 'ng', 'mga', 'sa', 'saan', 'malapit', 'hanap', 'magandang']);
                const tokens = normalized.replace(/[^a-z0-9\s-]/g, ' ').split(/\s+/).filter(token => token.length > 2 && !stopWords.has(token));
                const interests = new Set(loadInterestHistory().map(category => String(category).toLowerCase()));
                const savedCategories = new Set(vendors.filter(vendor => state.saved.has(String(vendor.id))).map(vendor => String(vendor.category || '').toLowerCase()));
                const scored = vendors.map(vendor => {
                    const category = String(vendor.category || '').toLowerCase();
                    const searchable = [vendor.name, vendor.category, vendor.market, vendor.specialty, vendor.product_names].join(' ').toLowerCase();
                    const categoryMatch = requestedCategory.length > 0 && requestedCategory.some(term => category.includes(term));
                    const tokenMatches = tokens.filter(token => searchable.includes(token)).length;
                    const preferenceMatch = savedCategories.has(category) || interests.has(category);
                    const distanceMatch = String(vendor.distance || '').match(/([\d.]+)\s*(km|m)/i);
                    const distance = distanceMatch ? Number(distanceMatch[1]) * (distanceMatch[2].toLowerCase() === 'm' ? 0.001 : 1) : 10;
                    return {
                        vendor: vendor,
                        categoryMatch: categoryMatch,
                        tokenMatches: tokenMatches,
                        preferenceMatch: preferenceMatch,
                        score: (categoryMatch ? 100 : 0) + tokenMatches * 18 + (preferenceMatch ? 12 : 0) + (Number(vendor.rating) || 0) * 1.5 + Math.max(0, 1.5 - distance * 0.5)
                    };
                });
                const hasSpecificRequest = requestedCategory.length > 0 || tokens.length > 0;
                const relevant = hasSpecificRequest
                    ? scored.filter(item => item.categoryMatch || item.tokenMatches > 0)
                    : scored;
                const noMatch = hasSpecificRequest && relevant.length === 0;
                const pool = relevant.length ? relevant : noMatch ? [] : scored;
                const unsaved = pool.filter(item => !state.saved.has(String(item.vendor.id)));
                const selected = (unsaved.length ? unsaved : pool)
                    .sort((first, second) => second.score - first.score || String(first.vendor.name).localeCompare(String(second.vendor.name)))
                    .slice(0, 3)
                    .map(item => item.vendor);
                return { matches: selected, requestedCategory: requestedCategory.join(', '), noMatch: noMatch };
            }
            function getVendorAssistantResponse() {
                const listings = state.myVendors;
                const actions = [];
                if (state.vendorStatusLoading) {
                    return { message: 'I’m loading your current stall status. Please try that question again in a moment.', actions: [] };
                }
                if (!state.vendorStatusLoaded) {
                    return { message: 'I couldn’t load your stall status. Refresh the dashboard and try again so I can give you accurate next steps.', actions: [] };
                }
                if (!listings.length) {
                    actions.push({ label: 'Start a stall application', action: 'vendor-application' });
                    return {
                        message: 'You haven’t submitted a stall listing yet. Add your stall details and required documents in the application form. The market team reviews complete submissions before publishing them.',
                        actions: actions
                    };
                }
                const live = listings.filter(vendor => vendor.status === 'approved').length;
                const pending = listings.filter(vendor => vendor.status === 'pending').length;
                const rejected = listings.filter(vendor => vendor.status === 'rejected');
                const incomplete = listings.filter(vendor => !Number(vendor.has_application) || !Number(vendor.has_cedula) || !Number(vendor.has_photo));
                const unpaid = listings.filter(vendor => vendor.rental_payment_status !== 'paid');
                const parts = ['You have ' + listings.length + ' stall listing' + (listings.length === 1 ? '' : 's') + ', ' + live + ' live and ' + pending + ' awaiting review.'];
                if (incomplete.length) parts.push(incomplete.length + ' listing' + (incomplete.length === 1 ? ' is' : 's are') + ' missing required documents. Open the listing and upload the application, cedula, and 2×2 photo.');
                if (unpaid.length) parts.push('Check the rental payment status for ' + unpaid.length + ' listing' + (unpaid.length === 1 ? '' : 's') + '; verified first-month rent is required before approval.');
                if (rejected.length) parts.push('You have a declined application. Review its details, make the needed updates, and resubmit it.');
                if (live) parts.push('For live listings, keep your stall details current and reply promptly to customer messages.');
                if (incomplete.length || unpaid.length || rejected.length) actions.push({ label: 'Review my stall listings', action: 'vendor-listings' });
                if (live) actions.push({ label: 'Open messages', action: 'vendor-messages' });
                return { message: parts.join(' '), actions: actions };
            }
            function getAdminAssistantResponse(query) {
                if (state.adminDashboardLoading) {
                    return { message: 'I’m loading the current review queue. Please try again in a moment.', actions: [] };
                }
                if (!state.adminDashboardLoaded) {
                    return { message: 'I couldn’t load the review queue. Refresh the dashboard and try again so I can provide an accurate summary.', actions: [] };
                }
                const applications = state.adminApplications;
                const ready = applications.filter(application => Number(application.has_application) && Number(application.has_cedula) && Number(application.has_photo) && application.rental_payment_status === 'paid');
                const documentsMissing = applications.filter(application => !Number(application.has_application) || !Number(application.has_cedula) || !Number(application.has_photo));
                const paymentPending = applications.filter(application => application.rental_payment_status !== 'paid');
                const summary = applications.length
                    ? 'There ' + (applications.length === 1 ? 'is ' : 'are ') + applications.length + ' rental request' + (applications.length === 1 ? '' : 's') + ' in the review queue. ' + ready.length + ' meet the document and verified-rent requirements, ' + documentsMissing.length + ' need documents, and ' + paymentPending.length + ' have rent pending.'
                    : 'The review queue is currently empty. You’re all caught up with stall rental requests.';
                const guidance = ready.length
                    ? 'Start with eligible requests, then verify each application and market details before making an approval decision.'
                    : 'For each request, check the application form, barangay cedula, 2×2 photo, and verified first-month rent before approving.';
                const isTipsRequest = /tip|checklist|how|paano|review|next|priority|summar|pending|request/.test(query.toLowerCase());
                return {
                    message: isTipsRequest ? summary + ' ' + guidance : summary + ' I can help you prioritize the queue; approval decisions remain yours.',
                    actions: applications.length ? [{ label: 'Open review queue', action: 'admin-review' }] : []
                };
            }
            function getAssistantResponse(query) {
                const role = assistantRole();
                if (role === 'vendor') return getVendorAssistantResponse();
                if (role === 'admin') return getAdminAssistantResponse(query);
                const result = getAssistantVendorRecommendations(query);
                if (!state.vendors.length) {
                    return { message: 'I can recommend approved local stalls once the market listings have loaded. Please try again in a moment.', vendors: [] };
                }
                if (result.noMatch) {
                    return { message: 'I couldn’t find a stall matching that request in the current Pwesto listings. Try a category such as produce, seafood, meat, pantry, or flowers.', vendors: [] };
                }
                if (!result.matches.length) {
                    return { message: 'I don’t have a matching stall to recommend just yet. Explore the market or try a different category.', vendors: [] };
                }
                const lead = result.requestedCategory
                    ? 'Here are local stalls matching your request. I ranked these using listing details, ratings, distance, and your saved or explored categories.'
                    : 'Here are a few local picks. I ranked these using ratings, distance, and categories you’ve saved or explored.';
                return { message: lead, vendors: result.matches };
            }
            async function sendAssistantMessage(message) {
                const query = String(message || '').trim();
                if (!query || state.assistantBusy) return;
                appendAssistantMessage(query, 'user');
                state.assistantBusy = true;
                $('assistantInput').disabled = true;
                const sendButton = $('assistantForm').querySelector('.assistant-send');
                sendButton.disabled = true;
                appendAssistantMessage('Sandali, hinahanap ko ang sagot…', 'assistant');
                const pendingMessage = $('assistantMessages').lastElementChild;
                pendingMessage.classList.add('is-pending');
                try {
                    const response = await api('/assistant', {
                        method: 'POST',
                        body: { message: query, history: state.assistantHistory.slice(-4) }
                    });
                    pendingMessage.remove();
                    appendAssistantMessage(response.answer, 'assistant');
                    state.assistantHistory = [
                        ...state.assistantHistory,
                        { role: 'user', text: query },
                        { role: 'model', text: response.answer }
                    ].slice(-4);
                } catch (error) {
                    pendingMessage.remove();
                    appendAssistantMessage(error.message || 'Hindi ako makakonekta ngayon. Pakisubukan ulit mamaya.', 'error');
                } finally {
                    state.assistantBusy = false;
                    $('assistantInput').disabled = false;
                    sendButton.disabled = false;
                    $('assistantInput').focus();
                }
            }
            function toggleAssistant(open) {
                const panel = $('assistantPanel');
                const isOpen = typeof open === 'boolean' ? open : panel.hidden;
                panel.hidden = !isOpen;
                $('assistantLauncher').setAttribute('aria-expanded', String(isOpen));
                if (isOpen) {
                    assistantSuggestions();
                    if (!$('assistantMessages').children.length) {
                        const greeting = assistantRole() === 'customer'
                            ? 'Kumusta! Magtanong tungkol sa produkto, presyo, order, o mga stall sa palengke.'
                            : assistantRole() === 'vendor'
                                ? 'Kumusta! Matutulungan kitang alamin ang status ng stall, requirements, produkto, at orders.'
                                : 'Kumusta! Maaari akong tumulong sa pag-review ng mga rental application at requirements.';
                        appendAssistantMessage(greeting, 'assistant');
                    }
                    $('assistantInput').focus();
                }
            }
            function renderVendors() {
                const vendors = filteredVendors();
                $('resultCount').textContent = vendors.length + (vendors.length === 1 ? ' stall' : ' stalls');
                $('savedCount').textContent = state.saved.size ? '(' + state.saved.size + ')' : '';
                $('customerSavedTotal').textContent = state.saved.size;
                window.renderPwestoVendors({
                    vendors: vendors,
                    savedIds: Array.from(state.saved),
                    canMessage: !state.user || state.user.role === 'customer'
                });
                state.markers.forEach((marker, id) => {
                    if (vendors.some(vendor => String(vendor.id) === String(id))) marker.addTo(state.map);
                    else marker.remove();
                });
                renderRecommendations();
            }
            function renderAppointments() {
                const target = $('bookingList');
                $('customerVisitTotal').textContent = state.appointments.filter(visit => visit.status !== 'Cancelled').length;
                renderRecommendations();
                if (!state.user) {
                    target.className = 'booking-empty';
                    target.textContent = 'Sign in to save your market visits and stall bookings.';
                    return;
                }
                if (!state.appointments.length) {
                    target.className = 'booking-empty';
                    target.textContent = 'No visits planned yet. Find a stall you love and save a visit.';
                    return;
                }
                target.className = '';
                target.innerHTML = state.appointments.map(visit => `<div class="booking-row"><span><strong>${escapeHtml(visit.vendor_name)}</strong>${escapeHtml(visit.visit_date)} · ${escapeHtml(visit.visit_time)}</span><span style="display:flex;align-items:center;gap:8px"><span class="status-pill ${visit.status === 'Cancelled' ? 'status-pending' : ''}">${escapeHtml(visit.status || 'Planned')}</span>${visit.status === 'Planned' ? `<button class="vendor-action" data-cancel-visit="${escapeHtml(visit.id)}">Cancel</button>` : ''}</span></div>`).join('');
            }
            function formatProductMoney(cents) {
                return '₱' + (Number(cents || 0) / 100).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            function formatPickupDateTime(value) {
                if (!value) return 'Not scheduled';
                const date = new Date(String(value).replace(' ', 'T') + '+08:00');
                if (Number.isNaN(date.getTime())) return 'Not scheduled';
                return new Intl.DateTimeFormat('en-PH', {
                    dateStyle: 'medium',
                    timeStyle: 'short',
                    timeZone: 'Asia/Manila'
                }).format(date);
            }
            function renderCustomerOrders() {
                const target = $('customerOrderList');
                if (!state.user || state.user.role !== 'customer') {
                    target.className = 'booking-empty';
                    target.textContent = 'Sign in with a customer account to see your product orders.';
                    return;
                }
                if (!state.customerOrders.length) {
                    target.className = 'booking-empty';
                    target.textContent = 'No product orders yet. Choose products from a market stall to get started.';
                    return;
                }
                target.className = 'vendor-order-list';
                target.innerHTML = state.customerOrders.map(order => {
                    const items = (order.items || []).map(item =>
                        `<span>${escapeHtml(item.quantity)} × ${escapeHtml(item.product_name)} · ${formatProductMoney(item.unit_price_cents)}</span>`
                    ).join('');
                    const fulfillment = order.delivery_method === 'delivery'
                        ? `<p>Delivery · ${escapeHtml(order.delivery_address || 'Address not provided')}</p>`
                        : `<p>Pickup at the stall · ${escapeHtml(formatPickupDateTime(order.pickup_at))} (Philippine time)</p>`;
                    const payment = order.payment_method === 'gcash'
                        ? `<p>Xendit GCash · ${escapeHtml(order.payment_status)}${order.payment_status === 'pending' && order.payment_checkout_url ? ` · <a href="${escapeHtml(order.payment_checkout_url)}" target="_blank" rel="noopener noreferrer">Continue checkout</a> <button type="button" class="merchant-edit-button" data-check-order-payment="${escapeHtml(order.id)}">Check payment</button>` : ''}</p>`
                        : order.payment_method === 'cash_on_pickup' ? '<p>Cash payment at pickup</p>' : '<p>Pay vendor directly</p>';
                    return `<article class="vendor-order"><div class="vendor-order-heading"><strong>${escapeHtml(order.vendor_name)} · Order #${escapeHtml(order.id.slice(0, 8))}</strong><span class="status-pill ${['cancelled', 'completed', 'delivered'].includes(order.status) ? '' : 'status-pending'}">${escapeHtml(order.status.replaceAll('_', ' '))}</span></div>${fulfillment}${payment}<div class="vendor-order-items">${items}</div><div class="vendor-order-meta"><span>${escapeHtml(order.created_at)}</span><strong>Total ${formatProductMoney(order.total_cents)}</strong></div></article>`;
                }).join('');
            }
            async function loadCustomerOrders(silent) {
                if (!state.user || state.user.role !== 'customer') {
                    state.customerOrders = [];
                    renderCustomerOrders();
                    return;
                }
                try {
                    const result = await api('/orders');
                    state.customerOrders = result.orders || [];
                    renderCustomerOrders();
                } catch (error) {
                    $('customerOrderList').textContent = 'Your product orders could not be loaded.';
                    if (!silent) showToast(error.message, true);
                }
            }
            function renderManagedProducts() {
                const target = $('vendorProductList');
                const count = state.managedProducts.length;
                $('managedProductCount').textContent = count + (count === 1 ? ' product' : ' products');
                if (!state.productVendorId) {
                    target.innerHTML = '<div class="product-list-empty"><strong>No stall selected</strong>Submit a stall application, then select it to manage its product catalog.</div>';
                    return;
                }
                if (!state.managedProducts.length) {
                    target.innerHTML = '<div class="product-list-empty"><strong>Your catalog is ready</strong>Open Add product from the sidebar to create your first listing.</div>';
                    return;
                }
                target.innerHTML = state.managedProducts.map(product =>
                    `<article class="managed-product">${product.image_url ? `<img class="managed-product-image" src="${escapeHtml(product.image_url)}" alt="${escapeHtml(product.name)}" loading="lazy">` : ''}<div class="managed-product-heading"><strong>${escapeHtml(product.name)}</strong><span class="status-pill">${formatProductMoney(product.price_cents)} · ${escapeHtml(product.stock)} in stock</span></div>${product.description ? `<p>${escapeHtml(product.description)}</p>` : ''}<div class="managed-product-actions"><button type="button" class="merchant-edit-button" data-edit-product="${escapeHtml(product.id)}">Edit</button><button type="button" class="merchant-edit-button" data-delete-product="${escapeHtml(product.id)}">Remove</button></div></article>`
                ).join('');
            }
            async function loadManagedProducts() {
                if (!state.productVendorId || !state.user) {
                    state.managedProducts = [];
                    renderManagedProducts();
                    return;
                }
                $('vendorProductList').innerHTML = '<p class="product-list-empty">Loading product catalog…</p>';
                try {
                    const result = await api('/vendors/products?vendor_id=' + encodeURIComponent(state.productVendorId));
                    state.managedProducts = result.products || [];
                    renderManagedProducts();
                } catch (error) {
                    $('vendorProductList').innerHTML = `<p class="product-list-empty">${escapeHtml(error.message)}</p>`;
                }
            }
            function refreshProductVendorOptions() {
                const previousId = state.productVendorId;
                const options = '<option value="">Choose one of your stalls</option>' + state.myVendors.map(vendor =>
                    `<option value="${escapeHtml(vendor.id)}">${escapeHtml(vendor.name)} · ${escapeHtml(vendor.status)}</option>`
                ).join('');
                const nextId = state.myVendors.some(vendor => String(vendor.id) === previousId)
                    ? previousId
                    : (state.myVendors.find(vendor => vendor.status === 'approved') || state.myVendors[0] || {}).id || '';
                state.productVendorId = String(nextId);
                ['productVendorSelect', 'productCatalogVendorSelect'].forEach(id => {
                    $(id).innerHTML = options;
                    $(id).value = state.productVendorId;
                });
                if (state.productVendorId !== previousId) {
                    state.managedProducts = [];
                    loadManagedProducts();
                } else {
                    renderManagedProducts();
                }
            }
            function resetProductEditor() {
                const preview = $('productImagePreview');
                if (preview.dataset.objectUrl) {
                    URL.revokeObjectURL(preview.dataset.objectUrl);
                    delete preview.dataset.objectUrl;
                }
                $('productForm').reset();
                $('productId').value = '';
                $('productVendorSelect').value = state.productVendorId;
                $('productCatalogVendorSelect').value = state.productVendorId;
                preview.removeAttribute('src');
                preview.hidden = true;
                $('productFormFeedback').textContent = '';
                $('productFormHeading').textContent = 'Add a product';
                $('saveProductButton').textContent = 'Add product';
                $('cancelProductEdit').hidden = true;
            }
            function renderVendorOrders() {
                const target = $('vendorOrderList');
                if (!state.user || state.user.role !== 'vendor') {
                    target.innerHTML = '<p class="booking-empty">Sign in with a vendor account to manage customer orders.</p>';
                    return;
                }
                if (!state.vendorOrders.length) {
                    target.innerHTML = '<p class="booking-empty">No customer orders yet. New orders will appear here.</p>';
                    return;
                }
                const pickupNextStatus = {
                    pending: [['accepted', 'Accept'], ['cancelled', 'Decline']],
                    accepted: [['preparing', 'Start preparing'], ['cancelled', 'Cancel order']],
                    preparing: [['ready', 'Mark ready'], ['cancelled', 'Cancel order']],
                    ready: [['completed', 'Complete order']],
                    completed: [],
                    cancelled: []
                };
                target.innerHTML = state.vendorOrders.map(order => {
                    const nextStatus = order.delivery_method === 'delivery'
                        ? {
                            pending: [['accepted', 'Accept'], ['cancelled', 'Decline']],
                            accepted: [['preparing', 'Start preparing'], ['cancelled', 'Cancel order']],
                            preparing: [['out_for_delivery', 'Out for delivery'], ['cancelled', 'Cancel order']],
                            out_for_delivery: [['delivered', 'Mark delivered']],
                            delivered: [],
                            cancelled: []
                        }
                        : pickupNextStatus;
                    const items = (order.items || []).map(item =>
                        `<span>${escapeHtml(item.quantity)} × ${escapeHtml(item.product_name)} · ${formatProductMoney(item.unit_price_cents)}</span>`
                    ).join('');
                    const actions = (nextStatus[order.status] || []).map(([status, label]) =>
                        `<button type="button" class="merchant-edit-button" data-order-status="${status}" data-order-id="${escapeHtml(order.id)}">${label}</button>`
                    ).join('');
                    const fulfillment = order.delivery_method === 'delivery'
                        ? `<p>Delivery to: ${escapeHtml(order.delivery_address || '')}</p>`
                        : `<p>Pickup · ${escapeHtml(formatPickupDateTime(order.pickup_at))} (Philippine time)</p>`;
                    const payment = order.payment_method === 'gcash'
                        ? '<p>Xendit GCash · payment confirmed</p>'
                        : order.payment_method === 'cash_on_pickup' ? '<p>Cash due at pickup</p>' : '<p>Customer pays vendor directly</p>';
                    return `<article class="vendor-order"><div class="vendor-order-heading"><strong>${escapeHtml(order.vendor_name)} · ${escapeHtml(order.customer_name)} · #${escapeHtml(order.id.slice(0, 8))}</strong><span class="status-pill ${['completed', 'delivered', 'cancelled'].includes(order.status) ? '' : 'status-pending'}">${escapeHtml(order.status.replaceAll('_', ' '))}</span></div>${fulfillment}${payment}<div class="vendor-order-items">${items}</div><div class="vendor-order-meta"><span>${escapeHtml(order.created_at)}</span><strong>Total ${formatProductMoney(order.total_cents)}</strong></div><div class="vendor-order-actions">${actions}</div></article>`;
                }).join('');
            }
            async function loadVendorOrders(silent) {
                if (!state.user || state.user.role !== 'vendor') {
                    state.vendorOrders = [];
                    renderVendorOrders();
                    return;
                }
                try {
                    const result = await api('/vendor/orders');
                    state.vendorOrders = result.orders || [];
                    renderVendorOrders();
                } catch (error) {
                    $('vendorOrderList').innerHTML = `<p class="booking-empty">${escapeHtml(error.message)}</p>`;
                    if (!silent) showToast(error.message, true);
                }
            }
            function renderMyVendors() {
                const target = $('merchantVendorList');
                const live = state.myVendors.filter(vendor => vendor.status === 'approved').length;
                const pending = state.myVendors.filter(vendor => vendor.status === 'pending').length;
                renderVendorProfile();
                $('vendorListingTotal').textContent = state.myVendors.length;
                $('vendorLiveTotal').textContent = live;
                $('vendorPendingTotal').textContent = pending;
                $('monthlyRentLabel').textContent = '₱' + Number(state.rentalFee || 7000).toLocaleString('en-PH') + ' per month';
                $('merchantCallout').textContent = !state.myVendors.length
                    ? 'Start by adding your stall details. The Pwesto market team reviews each listing before it appears on the public map.'
                    : pending
                        ? 'Your stall rental request is awaiting admin approval. Once the documents and first-month rent are verified, the admin can approve and publish your listing.'
                        : live
                            ? 'Your approved stall' + (live === 1 ? ' is' : 's are') + ' live on Pwesto. Share your listing with neighbors and keep your stall details up to date.'
                            : 'Your latest application was not approved. Review the stall details and submit an updated listing for the market team.';
                if (!state.user) {
                    target.innerHTML = '<p class="booking-empty">Sign in to submit and manage your market stalls.</p>';
                    refreshProductVendorOptions();
                    return;
                }
                target.innerHTML = state.myVendors.length
                    ? state.myVendors.map(vendor => {
                        const documentsReady = Number(vendor.has_application) && Number(vendor.has_cedula) && Number(vendor.has_photo);
                        const paymentStatus = vendor.rental_payment_status || 'unpaid';
                        const paymentAction = paymentStatus === 'paid'
                            ? `<span class="admin-payment-state is-paid">Paid until ${escapeHtml(vendor.rental_paid_until || 'the end of this month')}</span>`
                            : vendor.rental_checkout_url
                                ? `<a class="merchant-payment-button" href="${escapeHtml(vendor.rental_checkout_url)}" target="_blank" rel="noopener noreferrer">Continue Xendit checkout</a><button type="button" class="merchant-payment-check" data-check-payment="${escapeHtml(vendor.id)}">Check payment</button>`
                                : `<button type="button" class="merchant-payment-button" data-rental-payment="${escapeHtml(vendor.id)}">Pay ₱${Number(state.rentalFee || 7000).toLocaleString('en-PH')} monthly</button>`;
                        const requirementsText = documentsReady ? 'Application, cedula, and 2×2 photo submitted' : 'Required documents are incomplete';
                        const paymentConfigText = state.paymentConfigured || paymentStatus === 'paid' ? '' : 'Xendit checkout awaits server configuration.';
                        const requirementsForm = `<details class="vendor-requirements-update"><summary>${documentsReady ? 'Replace submitted documents' : 'Upload missing requirements'}</summary><p>Choose one or more files to add or replace. Only Pwesto administrators can view these private documents.</p><form data-update-vendor-docs="${escapeHtml(vendor.id)}"><input type="hidden" name="vendor_id" value="${escapeHtml(vendor.id)}"><label>Application form · PDF/JPEG/PNG${Number(vendor.has_application) ? '' : ' · missing'}<input type="file" name="application_document" accept="application/pdf,image/jpeg,image/png"></label><label>Barangay cedula · PDF/JPEG/PNG${Number(vendor.has_cedula) ? '' : ' · missing'}<input type="file" name="cedula_document" accept="application/pdf,image/jpeg,image/png"></label><label>2×2 photo · square JPEG/PNG${Number(vendor.has_photo) ? '' : ' · missing'}<input type="file" name="photo_document" accept="image/jpeg,image/png"></label><button type="submit" class="merchant-edit-button">Save requirements</button></form></details>`;
                        return `<div class="merchant-vendor-row"><div class="merchant-vendor-name"><span class="merchant-vendor-icon" aria-hidden="true">⌂</span><span><strong>${escapeHtml(vendor.stall_code || 'Code pending')} · ${escapeHtml(vendor.name)}</strong><small>${escapeHtml(vendor.category)} · ${escapeHtml(vendor.market)}<br>${escapeHtml(vendor.specialty)}<br>${requirementsText}</small></span></div><div class="merchant-row-actions"><span class="status-pill ${vendor.status === 'pending' ? 'status-pending' : vendor.status === 'rejected' ? 'status-rejected' : ''}">${escapeHtml(vendor.status)}</span><button type="button" class="merchant-edit-button" data-manage-products="${escapeHtml(vendor.id)}">Manage products</button><button type="button" class="merchant-edit-button" data-edit-vendor="${escapeHtml(vendor.id)}">Edit</button><span class="merchant-payment-actions">${paymentAction}</span><span class="merchant-payment-state">${escapeHtml(paymentConfigText || ('Monthly fee · ' + paymentStatus))}</span>${requirementsForm}</div></div>`;
                    }).join('')
                    : '<p class="booking-empty">You haven’t submitted a stall yet.</p>';
                refreshProductVendorOptions();
                renderConversations();
            }
            function renderVendorProfile() {
                const user = state.user;
                if (!user) {
                    $('vendorAccountProfile').innerHTML = '<h3 class="vendor-profile-title">Vendor account</h3><div class="vendor-profile-empty">Sign in to view your Pwesto seller profile.</div>';
                    $('vendorStoreProfile').innerHTML = '<div class="vendor-profile-empty">Your stall profile will appear here.</div>';
                    return;
                }
                const name = user.name || 'Pwesto vendor';
                const avatar = user.picture
                    ? `<img class="vendor-account-avatar" src="${escapeHtml(user.picture)}" alt="">`
                    : `<span class="vendor-account-avatar" aria-hidden="true">${escapeHtml(name.charAt(0).toUpperCase())}</span>`;
                $('vendorAccountProfile').innerHTML = `<h3 class="vendor-profile-title">Your vendor profile</h3><div class="vendor-account-identity">${avatar}<span class="vendor-account-copy"><strong>${escapeHtml(name)}</strong><small>${escapeHtml(user.email || '')}</small></span></div><span class="vendor-profile-role">Pwesto vendor</span>`;

                const profileVendor = state.myVendors.find(vendor => vendor.status === 'approved')
                    || state.myVendors.find(vendor => vendor.status === 'pending')
                    || state.myVendors[0];
                if (!profileVendor) {
                    $('vendorStoreProfile').innerHTML = '<div class="vendor-profile-empty"><strong>Your public stall profile</strong>Add your stall details to create a profile shoppers can discover on Pwesto.</div>';
                    return;
                }
                const statusClass = profileVendor.status === 'pending' ? 'status-pending'
                    : profileVendor.status === 'rejected' ? 'status-rejected' : '';
                const viewButton = profileVendor.status === 'approved'
                    ? `<button class="vendor-action" type="button" data-view-vendor-profile="${escapeHtml(profileVendor.id)}">View on market map</button>`
                    : '';
                $('vendorStoreProfile').innerHTML = `<div class="vendor-store-copy"><span class="vendor-profile-title">Your public stall profile</span><strong>${escapeHtml(profileVendor.stall_code || 'Code pending')} · ${escapeHtml(profileVendor.name)}</strong><small>${escapeHtml(profileVendor.category)} · ${escapeHtml(profileVendor.market)}<br>${escapeHtml(profileVendor.specialty)}</small></div><span class="vendor-store-status ${statusClass}">${escapeHtml(profileVendor.status)}</span>${viewButton}`;
            }
            function editVendorProfile(vendorId) {
                const vendor = state.myVendors.find(item => String(item.id) === String(vendorId));
                if (!vendor) return;
                $('stallVendorId').value = vendor.id;
                $('stallName').value = vendor.name;
                if (![...$('stallCategory').options].some(option => option.value === vendor.category)) {
                    $('stallCategory').add(new Option(vendor.category + ' (legacy)', vendor.category));
                }
                $('stallCategory').value = vendor.category;
                $('stallMarket').value = vendor.market;
                $('stallSpecialty').value = vendor.specialty;
                $('merchantFormHeading').textContent = 'Update your stall details';
                $('merchantFormDescription').textContent = 'Changes to your approved listing appear on the market map. Rejected applications return to review after an update.';
                $('merchantSubmit').textContent = 'Save stall changes';
                $('cancelVendorEdit').style.display = '';
                $('applicationRequirements').style.display = 'none';
                $('applicationRequirements').querySelectorAll('input[type="file"]').forEach(input => { input.required = false; input.disabled = true; });
                document.querySelector('.merchant-payment-note').style.display = 'none';
                $('merchantForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
                $('stallName').focus({ preventScroll: true });
            }
            function resetVendorEditor() {
                $('merchantForm').reset();
                $('stallVendorId').value = '';
                $('merchantFormHeading').textContent = 'Bring your stall online';
                $('merchantFormDescription').textContent = 'Tell nearby shoppers what you sell and where to find you.';
                $('merchantSubmit').textContent = 'Submit rental request →';
                $('cancelVendorEdit').style.display = 'none';
                $('applicationRequirements').style.display = '';
                $('applicationRequirements').querySelectorAll('input[type="file"]').forEach(input => { input.required = true; input.disabled = false; });
                document.querySelector('.merchant-payment-note').style.display = '';
            }
            function renderAdminDashboard() {
                const summary = state.adminSummary || {};
                $('adminStats').innerHTML = [
                    ['Approved stalls', summary.approved_stalls, 'Live on the market map'],
                    ['Active products', summary.active_products, 'Listed by approved vendors'],
                    ['Open orders', summary.open_orders, 'Not yet completed or cancelled'],
                    ['Pending applications', summary.pending_applications, 'Awaiting admin review']
                ].map(item => `<div class="admin-stat"><span>${escapeHtml(item[0])}</span><strong>${escapeHtml(item[1] === undefined ? '—' : item[1])}</strong><small>${escapeHtml(item[2])}</small></div>`).join('');
                const categoryBreakdown = Array.isArray(summary.category_breakdown) ? summary.category_breakdown : [];
                const categoryTotal = categoryBreakdown.reduce((total, item) => total + (Number(item.stalls) || 0), 0);
                $('adminCategoryBreakdown').innerHTML = categoryBreakdown.length
                    ? categoryBreakdown.map(item => {
                        const count = Number(item.stalls) || 0;
                        const width = categoryTotal ? Math.round(count / categoryTotal * 100) : 0;
                        return `<div class="admin-analysis-row"><span><span>${escapeHtml(item.category || 'Other')}</span><strong>${count}</strong></span><div class="admin-analysis-bar"><span style="width:${width}%"></span></div></div>`;
                    }).join('')
                    : '<div class="admin-analysis-empty">No approved stalls yet.</div>';
                const applicationTotal = state.adminApplications.length;
                const readiness = state.adminApplications.reduce((counts, application) => {
                    const documentsReady = Number(application.has_application) && Number(application.has_cedula) && Number(application.has_photo);
                    if (documentsReady && application.rental_payment_status === 'paid') counts.ready++;
                    if (!documentsReady) counts.missingDocuments++;
                    if (application.rental_payment_status !== 'paid') counts.unpaid++;
                    return counts;
                }, { ready: 0, missingDocuments: 0, unpaid: 0 });
                const readinessRows = [
                    ['Ready to approve', readiness.ready],
                    ['Missing documents', readiness.missingDocuments],
                    ['First-month rent unpaid', readiness.unpaid]
                ];
                $('adminApplicationReadiness').innerHTML = applicationTotal
                    ? readinessRows.map(([label, count]) => {
                        const width = Math.round(count / applicationTotal * 100);
                        return `<div class="admin-analysis-row"><span><span>${label}</span><strong>${count} / ${applicationTotal}</strong></span><div class="admin-analysis-bar"><span style="width:${width}%"></span></div></div>`;
                    }).join('')
                    : '<div class="admin-analysis-empty">No pending applications to review.</div>';
                $('adminQueueCount').textContent = state.adminApplications.length + ' rental request' + (state.adminApplications.length === 1 ? '' : 's') + ' to review';
                $('adminApplications').innerHTML = (
                    state.adminApplications.length
                        ? state.adminApplications.map(application => {
                            const documentsReady = Number(application.has_application) && Number(application.has_cedula) && Number(application.has_photo);
                            const canApprove = documentsReady && application.rental_payment_status === 'paid';
                            const documentLink = (kind, label, available) => available
                                ? `<a href="${apiBase}/admin/vendor-document?vendor_id=${encodeURIComponent(application.id)}&kind=${kind}" target="_blank" rel="noopener noreferrer">${label}</a>`
                                : `<span>${label} missing</span>`;
                            return `<div class="admin-application"><span class="admin-application-info"><span class="admin-application-icon" aria-hidden="true">⌂</span><span><strong>${escapeHtml(application.stall_code || 'Code pending')} · ${escapeHtml(application.name)}</strong><small>${escapeHtml(application.category)} · ${escapeHtml(application.market)}<br>${escapeHtml(application.specialty)}<br>Submitted by ${escapeHtml(application.owner_name || 'Pwesto vendor')} · ${escapeHtml(application.owner_email || '')}</small><span class="admin-document-links">${documentLink('application', 'Application form', application.has_application)}${documentLink('cedula', 'Barangay cedula', application.has_cedula)}${documentLink('photo', '2×2 photo', application.has_photo)}</span><span class="admin-payment-state ${canApprove ? 'is-paid' : ''}">First-month rent · ${escapeHtml(application.rental_payment_status || 'unpaid')}</span></span></span><span class="admin-actions"><button class="button-approve" data-review-vendor="${escapeHtml(application.id)}" data-decision="approved" ${canApprove ? '' : 'disabled title="All documents and verified first-month rent are required"'}>Approve rental & publish</button><button class="button-reject" data-review-vendor="${escapeHtml(application.id)}" data-decision="rejected">Decline request</button></span></div>`;
                        }).join('')
                        : '<div class="admin-empty"><span class="admin-empty-icon" aria-hidden="true">✓</span><strong>You’re all caught up</strong><p>There are no stall rental requests waiting for admin approval.</p></div>'
                );
                const unassigned = state.adminUnassignedStalls;
                $('unassignedStallCount').textContent = unassigned.length + ' stall' + (unassigned.length === 1 ? '' : 's');
                $('adminUnassignedStalls').innerHTML = unassigned.length
                    ? '<div class="admin-account-list">' + unassigned.map(stall => `
                        <div class="admin-account-row">
                            <div>
                                <strong>${escapeHtml(stall.stall_code || 'No code')} · ${escapeHtml(stall.name)}</strong>
                                <small class="admin-account-note">${escapeHtml(stall.category)} · ${escapeHtml(stall.market)} · Current status: ${escapeHtml(stall.status)}</small>
                            </div>
                            <form class="admin-account-form" data-assign-stall="${escapeHtml(stall.id)}">
                                <label>Vendor’s real name<input name="name" maxlength="120" minlength="2" autocomplete="name" required></label>
                                <label>Vendor’s email<input name="email" type="email" maxlength="254" autocomplete="email" required></label>
                                <button class="button button-primary button-small" type="submit">Create & link</button>
                            </form>
                        </div>`).join('') + '</div>'
                    : '<div class="admin-empty"><span class="admin-empty-icon" aria-hidden="true">✓</span><strong>All stalls have vendor accounts</strong><p>No unassigned stalls remain.</p></div>';
            }
            async function loadAdminDashboard() {
                if (!state.user || state.user.role !== 'admin') return;
                state.adminDashboardLoading = true;
                try {
                    const result = await api('/admin/vendors');
                    state.adminSummary = result.summary || {};
                    state.adminApplications = result.applications || [];
                    state.adminUnassignedStalls = result.unassigned_stalls || [];
                    state.adminDashboardLoaded = true;
                    renderAdminDashboard();
                } catch (error) {
                    state.adminDashboardLoaded = false;
                    $('adminApplications').innerHTML = '<p class="booking-empty">Admin data could not be loaded.</p>';
                    showToast(error.message, true);
                } finally {
                    state.adminDashboardLoading = false;
                }
            }
            function renderAdminStorage() {
                const storage = state.adminStorage || { stalls: [], catalog: [], products: [], documents: [], applications: [] };
                const stalls = storage.stalls || [];
                const catalog = storage.catalog || [];
                const products = storage.products || [];
                const documents = storage.documents || [];
                const applications = storage.applications || [];
                const documentLabels = [
                    ['application', 'Application form'],
                    ['cedula', 'Barangay cedula'],
                    ['photo', '2×2 photo']
                ];
                $('adminStallStorageCount').textContent = stalls.length + ' stall' + (stalls.length === 1 ? '' : 's');
                $('adminCatalogStorageCount').textContent = catalog.length + ' product' + (catalog.length === 1 ? '' : 's');
                $('adminProductStorageCount').textContent = products.length + ' photo' + (products.length === 1 ? '' : 's');
                $('adminDocumentStorageCount').textContent = documents.length + ' file' + (documents.length === 1 ? '' : 's');
                $('adminRentalRecordCount').textContent = applications.length + ' stall' + (applications.length === 1 ? '' : 's');
                $('adminRentalRecords').innerHTML = applications.length
                    ? applications.map(record => {
                        const documentLinks = documentLabels.map(([kind, label]) => record.documents && record.documents[kind]
                            ? `<a href="${escapeHtml(record.documents[kind])}" target="_blank" rel="noopener noreferrer">${label}</a>`
                            : `<span>${label} missing</span>`).join('');
                        const paidDate = record.rental_paid_at ? ` · Paid ${escapeHtml(record.rental_paid_at)}` : '';
                        const paidUntil = record.rental_paid_until ? ` · Through ${escapeHtml(record.rental_paid_until)}` : '';
                        return `<div class="admin-storage-document"><span><strong>${escapeHtml(record.stall_code || 'No code')} · ${escapeHtml(record.name)}</strong><small>${escapeHtml(record.category)} · ${escapeHtml(record.status)}<br>Vendor: ${escapeHtml(record.owner_name || 'No account linked')}${record.owner_email ? ' · ' + escapeHtml(record.owner_email) : ''}<br>Monthly rent ₱${(Number(record.rental_amount_cents || 0) / 100).toLocaleString('en-PH', { minimumFractionDigits: 2 })} · ${escapeHtml(record.rental_payment_status)}${paidDate}${paidUntil}</small><span class="admin-document-links">${documentLinks}</span></span></div>`;
                    }).join('')
                    : '<div class="admin-storage-empty">No vendor records are stored yet.</div>';
                $('adminStallStorage').innerHTML = stalls.length
                    ? stalls.map(stall => {
                        const photo = stall.image_url
                            ? `<img src="${escapeHtml(stall.image_url)}" alt="${escapeHtml(stall.stall_code || stall.name)}" loading="lazy">`
                            : '<div class="admin-storage-empty">No stall photo</div>';
                        const photoSource = stall.is_sample_image ? 'Sample photo · external source' : 'Stall photo';
                        return `<div class="admin-storage-photo">${photo}<span><strong>${escapeHtml(stall.stall_code || stall.name)} · ${escapeHtml(stall.name)}</strong><small>${escapeHtml(stall.category)} · ${escapeHtml(stall.status)} · ${photoSource}</small></span></div>`;
                    }).join('')
                    : '<div class="admin-storage-empty">No market stalls are stored yet.</div>';
                $('adminCatalogStorage').innerHTML = catalog.length
                    ? catalog.map(product => `<div class="admin-storage-document"><span><strong>${escapeHtml(product.product_name)}${product.is_sample ? ' · Sample dataset' : ''}</strong><small>${escapeHtml(product.stall_code || '')} · ${escapeHtml(product.vendor_name)} (${escapeHtml(product.vendor_category)})<br>${escapeHtml(product.description || 'No description')}<br>₱${(Number(product.price_cents || 0) / 100).toFixed(2)} · ${escapeHtml(product.stock)} in stock · ${product.active ? 'Active' : 'Inactive'} · ${escapeHtml(product.vendor_status)}</small></span></div>`).join('')
                    : '<div class="admin-storage-empty">No catalog products are stored yet.</div>';
                $('adminProductStorage').innerHTML = products.length
                    ? products.map(file => `<a class="admin-storage-photo" href="${escapeHtml(file.image_url)}" target="_blank" rel="noopener noreferrer"><img src="${escapeHtml(file.image_url)}" alt="${escapeHtml(file.product_name)}" loading="lazy"><span><strong>${escapeHtml(file.product_name)}</strong><small>${escapeHtml(file.vendor_name)} · ${escapeHtml(file.vendor_status)}${file.is_sample ? ' · Sample dataset' : ''}</small></span></a>`).join('')
                    : '<div class="admin-storage-empty">No product photos are stored yet.</div>';
                $('adminDocumentStorage').innerHTML = documents.length
                    ? documents.map(file => `<div class="admin-storage-document"><span><strong>${escapeHtml(file.label)}</strong><small>${escapeHtml(file.vendor_name)} · ${escapeHtml(file.vendor_status)}</small></span><a href="${escapeHtml(file.url)}" target="_blank" rel="noopener noreferrer">Open file</a></div>`).join('')
                    : '<div class="admin-storage-empty">No vendor application files are stored yet.</div>';
            }
            async function loadAdminStorage() {
                if (!state.user || state.user.role !== 'admin' || state.adminStorageLoading) return;
                state.adminStorageLoading = true;
                $('adminStallStorage').innerHTML = '<div class="admin-storage-empty">Loading stall photos…</div>';
                $('adminCatalogStorage').innerHTML = '<div class="admin-storage-empty">Loading catalog data…</div>';
                $('adminProductStorage').innerHTML = '<div class="admin-storage-empty">Loading product photos…</div>';
                $('adminDocumentStorage').innerHTML = '<div class="admin-storage-empty">Loading vendor files…</div>';
                try {
                    state.adminStorage = await api('/admin/storage');
                    renderAdminStorage();
                } catch (error) {
                    $('adminRentalRecords').innerHTML = '<div class="admin-storage-empty">Storage files could not be loaded.</div>';
                    $('adminStallStorage').innerHTML = '<div class="admin-storage-empty">Storage files could not be loaded.</div>';
                    $('adminCatalogStorage').innerHTML = '<div class="admin-storage-empty">Storage files could not be loaded.</div>';
                    $('adminProductStorage').innerHTML = '<div class="admin-storage-empty">Storage files could not be loaded.</div>';
                    $('adminDocumentStorage').innerHTML = '<div class="admin-storage-empty">Storage files could not be loaded.</div>';
                    showToast(error.message, true);
                } finally {
                    state.adminStorageLoading = false;
                }
            }
            async function loadMyVendors() {
                if (!state.user) { state.myVendors = []; state.vendorStatusLoaded = true; renderMyVendors(); return; }
                state.vendorStatusLoading = true;
                try {
                    const result = await api('/vendors/mine');
                    state.myVendors = result.vendors || [];
                    state.rentalFee = Number(result.rental_fee || 7000);
                    state.paymentConfigured = result.payment_configured === true;
                    state.vendorStatusLoaded = true;
                    renderMyVendors();
                } catch (error) {
                    state.vendorStatusLoaded = false;
                    showToast(error.message, true);
                } finally {
                    state.vendorStatusLoading = false;
                }
            }
            function startInboxPolling() {
                if (state.inboxPolling) clearInterval(state.inboxPolling);
                state.inboxPolling = null;
                if (!state.user || !['customer', 'vendor'].includes(state.user.role)) {
                    state.conversations = [];
                    renderConversations();
                    return;
                }
                loadConversations(true);
                state.inboxPolling = setInterval(() => {
                    if (!document.hidden) loadConversations(true);
                }, 10000);
            }
            function renderNotifications(unreadCount) {
                const unread = Number(unreadCount || 0);
                state.notificationUnreadCount = unread;
                const badge = $('notificationBadge');
                badge.textContent = unread > 99 ? '99+' : String(unread);
                badge.classList.toggle('visible', unread > 0);
                $('markAllNotificationsRead').disabled = unread === 0 || state.notificationsUpdating;
                $('notificationList').innerHTML = state.notifications.length
                    ? state.notifications.map(notification => `<button class="notification-item ${notification.read_at ? '' : 'unread'}" type="button" data-notification-id="${escapeHtml(notification.id)}" data-notification-type="${escapeHtml(notification.type)}" ${state.notificationsUpdating ? 'disabled' : ''} ${notification.read_at ? '' : 'aria-label="Unread notification: ' + escapeHtml(notification.title) + '"'}><strong>${escapeHtml(notification.title)}</strong><span>${escapeHtml(notification.message)}</span><time>${escapeHtml(notification.created_at)}</time></button>`).join('')
                    : '<div class="notification-empty">You’re all caught up. New messages and account updates will appear here.</div>';
            }
            function startNotificationPolling() {
                if (state.notificationPolling) clearInterval(state.notificationPolling);
                state.notificationPolling = null;
                if (!state.user) {
                    state.notifications = [];
                    renderNotifications(0);
                    $('notificationsPanel').hidden = true;
                    $('notificationButton').setAttribute('aria-expanded', 'false');
                    return;
                }
                loadNotifications(true);
                state.notificationPolling = setInterval(() => {
                    if (!document.hidden) loadNotifications(true);
                }, 20000);
            }
            async function loadNotifications(silent) {
                if (state.notificationsLoading || state.notificationsUpdating || !state.user) return;
                state.notificationsLoading = true;
                const revision = state.notificationRevision;
                try {
                    const result = await api('/notifications');
                    if (revision !== state.notificationRevision) return;
                    state.notifications = result.notifications || [];
                    renderNotifications(result.unread_count);
                    state.notificationErrorShown = false;
                } catch (error) {
                    if (!silent || !state.notificationErrorShown) showToast(error.message, true);
                    state.notificationErrorShown = true;
                } finally {
                    state.notificationsLoading = false;
                }
            }
            async function updateNotifications(body) {
                state.notificationsUpdating = true;
                state.notificationRevision += 1;
                renderNotifications(state.notificationUnreadCount);
                try {
                    const result = await api('/notifications', { method: 'POST', body: body });
                    state.notifications = result.notifications || [];
                    renderNotifications(result.unread_count);
                } finally {
                    state.notificationsUpdating = false;
                    renderNotifications(state.notificationUnreadCount);
                }
            }
            async function openNotification(notification) {
                if (!notification.read_at) {
                    try {
                        await updateNotifications({ action: 'mark_read', notification_id: notification.id });
                    } catch (error) {
                        showToast(error.message, true);
                        return;
                    }
                }
                $('notificationsPanel').hidden = true;
                $('notificationButton').setAttribute('aria-expanded', 'false');
                switchView('dashboard');
                if (notification.type === 'message') {
                    if (state.user.role === 'customer') {
                        setTab('messages');
                    } else {
                        showDashboardPanel('vendor');
                        showVendorSection('messages');
                    }
                } else if (notification.type === 'rental_request' || notification.type === 'rental_payment') {
                    showAdminSection('overview');
                    showDashboardPanel('admin');
                    $('adminApplications').scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (notification.type === 'rental_review') {
                    showDashboardPanel('vendor');
                    showVendorSection('listings');
                }
            }
            async function loadConversations(silent) {
                if (state.inboxLoading || !state.user || !['customer', 'vendor'].includes(state.user.role)) return;
                state.inboxLoading = true;
                try {
                    const result = await api('/messages');
                    state.conversations = result.conversations || [];
                    state.inboxErrorShown = false;
                    renderConversations();
                } catch (error) {
                    if (!silent || !state.inboxErrorShown) showToast(error.message, true);
                    state.inboxErrorShown = true;
                } finally {
                    state.inboxLoading = false;
                }
            }
            function renderConversations() {
                const conversations = state.conversations || [];
                const unread = conversations.reduce((total, conversation) => total + Number(conversation.unread_count || 0), 0);
                $('messagesCount').textContent = unread ? '(' + unread + ')' : '';
                $('vendorMessagesCount').textContent = unread ? '(' + unread + ')' : '';
                const emptyText = !state.user
                    ? '<div class="message-empty"><strong>Sign in to see your messages</strong>Start a conversation with a local seller from an approved stall listing.</div>'
                    : state.user.role === 'customer'
                        ? '<div class="message-empty"><strong>No conversations yet</strong>Choose a vendor below to start a conversation.<br><button type="button" class="message-contact-action" data-browse-vendors>Browse vendors</button></div>'
                        : '<div class="message-empty"><strong>No conversations yet</strong>Messages from customers will appear here when they contact your stall.</div>';
                const rows = conversations.length ? conversations.map(conversation => {
                    const otherName = conversation.other_name || 'Pwesto neighbor';
                    const preview = conversation.last_message || 'Conversation started — send a message to say hello.';
                    const unreadCount = Number(conversation.unread_count || 0);
                    const when = conversation.last_message_at || '';
                    return `<button class="conversation-row" type="button" data-open-conversation="${escapeHtml(conversation.id)}"><span class="conversation-avatar" aria-hidden="true">${escapeHtml(otherName.charAt(0).toUpperCase())}</span><span class="conversation-copy"><strong>${escapeHtml(otherName)} · ${escapeHtml(conversation.stall_name)}</strong><small>${escapeHtml(preview)}</small></span><span class="conversation-meta">${when ? `<time>${escapeHtml(when)}</time>` : ''}${unreadCount ? `<span class="unread-badge">${unreadCount}</span>` : ''}</span></button>`;
                }).join('') : emptyText;
                $('customerConversationList').innerHTML = rows;
                $('vendorConversationList').innerHTML = rows;
                renderVendorContacts();
            }
            function renderVendorContacts() {
                const contacts = state.vendors || [];
                const rows = contacts.length ? contacts.map(vendor => {
                    const canMessage = Boolean(vendor.can_message);
                    return `<div class="conversation-row"><span class="conversation-avatar" aria-hidden="true">${escapeHtml((vendor.name || 'V').charAt(0).toUpperCase())}</span><span class="conversation-copy"><strong>${escapeHtml(vendor.name)}</strong><small>${escapeHtml(vendor.category)} · ${escapeHtml(vendor.market)}</small></span><span class="conversation-meta"><button class="message-contact-action" type="button" data-contact-vendor="${escapeHtml(vendor.id)}" ${canMessage ? '' : 'disabled'}>${canMessage ? 'Message vendor' : 'Unavailable'}</button></span></div>`;
                }).join('') : '<div class="message-empty"><strong>No vendor contacts yet</strong>Approved market stalls will appear here.</div>';
                $('customerVendorContacts').innerHTML = rows;
                $('customerVendorContactsSection').style.display = state.user && state.user.role === 'customer' ? '' : 'none';
            }
            function syncChatComposerState() {
                const hasVendor = Boolean(state.chatVendor && state.chatVendor.id);
                const hasConversation = Boolean(state.activeConversationId);
                const input = $('chatInput');
                const submit = $('chatForm').querySelector('[type="submit"]');
                const allowed = hasVendor || hasConversation;
                const showSuggestions = !hasConversation && hasVendor;
                input.disabled = !allowed;
                submit.disabled = !allowed;
                input.setAttribute('placeholder', allowed ? 'Write a message…' : 'Choose an available vendor to start a conversation.');
                $('chatStartNote').style.display = showSuggestions ? '' : 'none';
                $('chatSuggestions').style.display = showSuggestions ? '' : 'none';
                if (!allowed) {
                    $('chatMessages').innerHTML = '<div class="message-empty">Choose an available vendor to start a conversation.</div>';
                }
            }
            function showChatMessages(messages) {
                const hasVendor = Boolean(state.chatVendor && state.chatVendor.id);
                const hasConversation = Boolean(state.activeConversationId);
                if (!hasVendor && !hasConversation) {
                    syncChatComposerState();
                    return;
                }
                $('chatStartNote').style.display = hasConversation ? 'none' : '';
                $('chatSuggestions').style.display = hasConversation ? 'none' : '';
                if (!messages.length) {
                    $('chatMessages').innerHTML = '<div class="message-empty">Send the first message to start the conversation.</div>';
                    syncChatComposerState();
                    return;
                }
                $('chatMessages').innerHTML = messages.map(message => {
                    const own = state.user && String(message.sender_id) === String(state.user.id);
                    return `<article class="chat-message ${own ? 'is-own' : ''}"><p>${escapeHtml(message.body)}</p><time>${own ? 'You' : escapeHtml(message.sender_name)} · ${escapeHtml(message.created_at)}</time></article>`;
                }).join('');
                $('chatMessages').scrollTop = $('chatMessages').scrollHeight;
                syncChatComposerState();
            }
            async function loadChatThread(silent) {
                if (!state.activeConversationId || state.threadLoading) return;
                state.threadLoading = true;
                try {
                    const result = await api('/messages/thread?conversation_id=' + encodeURIComponent(state.activeConversationId));
                    showChatMessages(result.messages || []);
                    state.threadErrorShown = false;
                    loadConversations(true);
                } catch (error) {
                    if (!silent || !state.threadErrorShown) showToast(error.message, true);
                    state.threadErrorShown = true;
                } finally {
                    state.threadLoading = false;
                }
            }
            function beginChatPolling() {
                if (state.chatPolling) clearInterval(state.chatPolling);
                state.chatPolling = setInterval(() => {
                    if (!document.hidden) {
                        loadChatThread(true);
                        loadConversations(true);
                    }
                }, 4000);
            }
            function openChatWithVendor(vendorId) {
                if (!state.user) {
                    state.pendingMessageIntent = { type: 'vendor', vendorId: String(vendorId) };
                    setAuthMode('login');
                    $('authDescription').textContent = 'Sign in to start a conversation with this market vendor.';
                    openModal('authModal');
                    return;
                }
                if (state.user.role !== 'customer') {
                    showToast('Direct seller messages are available from customer accounts.');
                    return;
                }
                const vendor = state.vendors.find(item => String(item.id) === String(vendorId));
                if (!vendor || !vendor.can_message) {
                    showToast('This stall is not available for direct messages.', true);
                    return;
                }
                state.activeConversationId = null;
                state.chatVendor = vendor;
                $('chatTitle').textContent = vendor.name;
                $('chatSubtitle').textContent = vendor.market + ' · Direct message';
                $('chatInput').value = '';
                showChatMessages([]);
                openModal('chatModal');
                $('chatInput').focus();
            }
            function openConversation(conversationId) {
                const conversation = state.conversations.find(item => String(item.id) === String(conversationId));
                if (!conversation) return;
                state.activeConversationId = conversation.id;
                state.chatVendor = null;
                $('chatTitle').textContent = conversation.other_name || 'Pwesto neighbor';
                $('chatSubtitle').textContent = conversation.stall_name + ' · ' + conversation.market;
                $('chatInput').value = '';
                $('chatMessages').innerHTML = '<div class="message-empty">Loading conversation…</div>';
                openModal('chatModal');
                loadChatThread(false);
                beginChatPolling();
            }
            async function sendChatMessage(event) {
                event.preventDefault();
                const input = $('chatInput');
                const message = input.value.trim();
                if (!message) return;
                if (!state.activeConversationId && !state.chatVendor) {
                    showToast('Choose an available vendor to start a conversation.', true);
                    syncChatComposerState();
                    return;
                }
                const submit = $('chatForm').querySelector('[type="submit"]');
                submit.disabled = true;
                try {
                    const payload = state.activeConversationId
                        ? { conversation_id: state.activeConversationId, message: message }
                        : { vendor_id: state.chatVendor.id, message: message };
                    const result = await api('/messages', { method: 'POST', body: payload });
                    state.activeConversationId = result.conversation_id;
                    state.chatVendor = null;
                    input.value = '';
                    showChatMessages(result.messages || []);
                    await loadConversations(true);
                    beginChatPolling();
                } catch (error) {
                    showToast(error.message, true);
                } finally {
                    submit.disabled = false;
                    syncChatComposerState();
                }
            }
            function resumePendingMessageIntent() {
                const intent = state.pendingMessageIntent;
                state.pendingMessageIntent = null;
                if (!intent || !state.user) return;
                if (intent.type === 'messages' && state.user.role === 'customer') {
                    setTab('messages');
                } else if (intent.type === 'vendor' && state.user.role === 'customer') {
                    openChatWithVendor(intent.vendorId);
                }
            }
            function openModal(id) {
                if (id === 'authModal' && googleClientId && window.google && google.accounts) initializeGoogle();
                if (id === 'chatModal') {
                    syncChatComposerState();
                }
                $(id).classList.add('open');
            }
            function closeModals() {
                document.querySelectorAll('.modal-backdrop').forEach(modal => modal.classList.remove('open'));
                if (state.chatPolling) clearInterval(state.chatPolling);
                state.chatPolling = null;
                state.activeConversationId = null;
                state.chatVendor = null;
                syncChatComposerState();
            }
            function initializeGoogle() {
                if (state.googleInitialized || !googleClientId || !window.google || !google.accounts || !google.accounts.id) return;
                google.accounts.id.initialize({ client_id: googleClientId, callback: handleGoogleCredential, auto_select: false, cancel_on_tap_outside: true });
                $('googleButton').innerHTML = '';
                google.accounts.id.renderButton($('googleButton'), { theme: 'outline', size: 'large', shape: 'pill', text: 'signin_with', width: 260 });
                state.googleInitialized = true;
                $('googleHelp').textContent = 'Google sign-in is optional. You can also use your email and password.';
            }
            function setAuthMode(mode) {
                authMode = mode;
                const signingUp = mode === 'register';
                $('passwordResetForm').hidden = true;
                $('emailAuthForm').hidden = false;
                $('signInMode').parentElement.hidden = false;
                document.querySelector('.auth-divider').hidden = false;
                $('googleButton').hidden = false;
                $('googleHelp').hidden = false;
                $('signInMode').classList.toggle('active', !signingUp);
                $('signUpMode').classList.toggle('active', signingUp);
                $('signInMode').setAttribute('aria-selected', String(!signingUp));
                $('signUpMode').setAttribute('aria-selected', String(signingUp));
                $('authNameField').style.display = signingUp ? '' : 'none';
                $('authRoleField').style.display = signingUp ? '' : 'none';
                $('authConfirmField').style.display = signingUp ? '' : 'none';
                $('forgotPasswordButton').hidden = signingUp;
                $('authName').required = signingUp;
                $('authConfirmPassword').required = signingUp;
                $('authPassword').autocomplete = signingUp ? 'new-password' : 'current-password';
                $('emailAuthSubmit').textContent = signingUp ? 'Create my Pwesto account' : 'Sign in with email';
                $('authDescription').textContent = signingUp
                    ? 'Choose a customer or vendor account. Admin accounts are assigned by the Pwesto administrator.'
                    : 'Sign in to save your favorite stalls and plan your next market visit.';
                $('authTitle').textContent = 'Your local market, closer.';
                $('authFormMessage').textContent = '';
            }
            function showPasswordResetForm(token) {
                passwordResetToken = token;
                $('emailAuthForm').hidden = true;
                $('signInMode').parentElement.hidden = true;
                document.querySelector('.auth-divider').hidden = true;
                $('googleButton').hidden = true;
                $('googleHelp').hidden = true;
                $('passwordResetForm').hidden = false;
                $('passwordResetMessage').textContent = '';
                $('authTitle').textContent = 'Choose a new password';
                $('authDescription').textContent = 'Your reset link is secure and expires after one hour.';
                openModal('authModal');
            }
            async function requestPasswordReset() {
                const email = $('authEmail').value.trim();
                if (!email) {
                    $('authFormMessage').textContent = 'Enter your email address first, then choose Forgot password?';
                    $('authEmail').focus();
                    return;
                }
                const button = $('forgotPasswordButton');
                const originalLabel = button.textContent;
                button.disabled = true;
                button.textContent = 'Sending reset link…';
                $('authFormMessage').textContent = 'Sending reset link…';
                try {
                    const result = await api('/auth/password/forgot', { method: 'POST', body: { email: email } });
                    $('authFormMessage').textContent = result.message;
                } catch (error) {
                    $('authFormMessage').textContent = error.message;
                } finally {
                    button.textContent = originalLabel;
                    button.disabled = false;
                }
            }
            async function handlePasswordReset(event) {
                event.preventDefault();
                const password = $('resetPassword').value;
                const confirmation = $('resetPasswordConfirm').value;
                const message = $('passwordResetMessage');
                message.textContent = '';
                if (password.length < 8 || password.length > 72) {
                    message.textContent = 'Choose a password between 8 and 72 characters.';
                    return;
                }
                if (password !== confirmation) {
                    message.textContent = 'Your passwords do not match.';
                    return;
                }
                const button = $('passwordResetSubmit');
                button.disabled = true;
                try {
                    const result = await api('/auth/password/reset', {
                        method: 'POST',
                        body: {
                            token: passwordResetToken,
                            password: password,
                            confirm_password: confirmation
                        }
                    });
                    const cleanUrl = new URL(window.location.href);
                    cleanUrl.searchParams.delete('password_reset');
                    window.history.replaceState({}, '', cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);
                    $('passwordResetForm').reset();
                    $('signInMode').parentElement.hidden = false;
                    setAuthMode('login');
                    $('authFormMessage').textContent = result.message;
                    showToast(result.message);
                } catch (error) {
                    message.textContent = error.message;
                } finally {
                    button.disabled = false;
                }
            }
            async function handleEmailAuth(event) {
                event.preventDefault();
                const button = $('emailAuthSubmit');
                const message = $('authFormMessage');
                message.textContent = '';
                const email = $('authEmail').value.trim();
                const password = $('authPassword').value;
                if (!email || !password) {
                    message.textContent = 'Enter your email address and password.';
                    return;
                }
                if (authMode === 'register') {
                    if ($('authName').value.trim().length < 2) {
                        message.textContent = 'Enter your name (at least 2 characters).';
                        return;
                    }
                    if (password.length < 8) {
                        message.textContent = 'Choose a password with at least 8 characters.';
                        return;
                    }
                    if (password !== $('authConfirmPassword').value) {
                        message.textContent = 'Your passwords do not match.';
                        return;
                    }
                }
                button.disabled = true;
                button.textContent = authMode === 'register' ? 'Creating your account…' : 'Signing in…';
                const wasRegistering = authMode === 'register';
                try {
                    const endpoint = wasRegistering ? '/auth/register' : '/auth/login';
                    const payload = { email, password };
                    if (wasRegistering) {
                        payload.name = $('authName').value.trim();
                        payload.confirm_password = $('authConfirmPassword').value;
                        payload.role = $('authRole').value;
                    }
                    const result = await api(endpoint, { method: 'POST', body: payload });
                    state.user = result.user;
                    state.csrf = result.csrf_token;
                    state.favorites = result.favorites || [];
                    state.appointments = result.appointments || [];
                    updateProfile();
                    closeModals();
                    $('emailAuthForm').reset();
                    setAuthMode('login');
                    showToast((wasRegistering ? 'Welcome to Pwesto, ' : 'Welcome back, ') + (state.user.name || 'neighbor').split(' ')[0] + '!');
                    switchView('dashboard');
                    updateRoleDashboard();
                    resumePendingMessageIntent();
                } catch (error) {
                    message.textContent = !wasRegistering && error.message === 'The email or password is incorrect.'
                        ? 'The email or password is incorrect. If an admin created this vendor account or you signed up with Google, use “Forgot password?” to set an email password, or sign in with Google.'
                        : error.message;
                } finally {
                    button.disabled = false;
                    button.textContent = authMode === 'register' ? 'Create my Pwesto account' : 'Sign in with email';
                }
            }
            async function handleGoogleCredential(response) {
                try {
                    const result = await api('/auth/google', { method: 'POST', body: { credential: response.credential } });
                    state.user = result.user;
                    state.csrf = result.csrf_token;
                    state.favorites = result.favorites || [];
                    state.appointments = result.appointments || [];
                    updateProfile();
                    closeModals();
                    showToast('Welcome to Pwesto, ' + (state.user.name || 'neighbor').split(' ')[0] + '!');
                    switchView('dashboard');
                    updateRoleDashboard();
                    resumePendingMessageIntent();
                } catch (error) {
                    showToast(error.message, true);
                }
            }
            async function signOut() {
                try {
                    await api('/auth/logout', { method: 'POST', body: {} });
                    state.user = null;
                    state.appointments = [];
                    state.favorites = [];
                    state.myVendors = [];
                    state.customerOrders = [];
                    state.vendorOrders = [];
                    state.managedProducts = [];
                    state.productVendorId = '';
                    const result = await api('/session');
                    state.csrf = result.csrf_token;
                    updateProfile();
                    renderMyVendors();
                    renderCustomerOrders();
                    renderVendorOrders();
                    updateRoleDashboard();
                    $('profileMenu').classList.remove('open');
                    showToast('You’ve signed out.');
                } catch (error) { showToast(error.message, true); }
            }
            function initializeMap() {
                if (!window.L || state.map) return;
                state.map = L.map('vendorMap', { scrollWheelZoom: false }).setView(defaultCenter, 17);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                    maxZoom: 19
                }).addTo(state.map);
                const directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination='
                    + encodeURIComponent(featuredMarket.name + ', ' + featuredMarket.address);
                const streetViewUrl = 'https://www.google.com/maps/@?api=1&map_action=pano&viewpoint='
                    + featuredMarket.lat + ',' + featuredMarket.lng;
                const marketIcon = L.divIcon({
                    className: '',
                    html: '<div class="market-pin"><span>⌂</span></div>',
                    iconSize: [36, 43],
                    iconAnchor: [18, 41],
                    popupAnchor: [0, -39]
                });
                L.marker(defaultCenter, { icon: marketIcon, zIndexOffset: 1000 })
                    .bindPopup(
                        '<strong>' + escapeHtml(featuredMarket.name) + '</strong><br>'
                        + escapeHtml(featuredMarket.address)
                        + '<div class="market-popup-actions"><a href="' + directionsUrl + '" target="_blank" rel="noopener noreferrer">Get directions ↗</a>'
                        + '<a href="' + streetViewUrl + '" target="_blank" rel="noopener noreferrer">Street View ↗</a></div>'
                    )
                    .addTo(state.map)
                    .openPopup();
                syncMapMarkers();
            }
            function syncMapMarkers() {
                if (!state.map) return;
                const vendorIds = new Set(state.vendors.map(vendor => String(vendor.id)));
                state.markers.forEach((marker, id) => {
                    if (!vendorIds.has(id)) {
                        marker.remove();
                        state.markers.delete(id);
                    }
                });
                state.vendors.forEach(vendor => {
                    if (state.markers.has(String(vendor.id))) return;
                    const icon = L.divIcon({ className: '', html: '<div class="pin"><span>' + escapeHtml(vendor.icon || '⌂') + '</span></div>', iconSize: [33, 39], iconAnchor: [16, 37], popupAnchor: [0, -36] });
                    const marker = L.marker([vendor.lat, vendor.lng], { icon: icon }).bindPopup('<strong>' + escapeHtml(vendor.stall_code || '') + ' · ' + escapeHtml(vendor.name) + '</strong><br>' + escapeHtml(vendor.category) + ' · ' + escapeHtml(vendor.market) + '<br><button type="button" data-book="' + escapeHtml(vendor.id) + '">Plan a visit</button>');
                    marker.addTo(state.map);
                    marker.on('click', () => focusVendor(vendor.id));
                    state.markers.set(String(vendor.id), marker);
                });
            }
            function focusVendor(id) {
                const vendor = state.vendors.find(item => String(item.id) === String(id));
                if (!vendor) return;
                recordVendorInterest(vendor);
                if (state.map) {
                    state.map.flyTo([vendor.lat, vendor.lng], 16, { duration: .5 });
                    state.markers.get(String(id)).openPopup();
                }
                document.querySelectorAll('.vendor-card').forEach(card => card.classList.toggle('selected', card.dataset.vendor === String(id)));
            }
            function startBooking(id) {
                const vendor = state.vendors.find(item => String(item.id) === String(id));
                if (!vendor) return;
                if (!state.user) {
                    showToast('Sign in or create an account to save a market visit.');
                    openModal('authModal');
                    return;
                }
                state.activeVendor = vendor;
                $('bookingVendorName').textContent = vendor.name + ' · ' + vendor.market;
                const today = new Date();
                $('visitDate').min = today.toISOString().slice(0, 10);
                $('visitDate').value = today.toISOString().slice(0, 10);
                openModal('bookingModal');
            }
            async function loadVendors() {
                try {
                    const result = await api('/vendors');
                    state.vendors = result.vendors || [];
                    renderLandingStats(true);
                    initializeMap();
                    syncMapMarkers();
                    renderVendors();
                    renderVendorContacts();
                } catch (error) {
                    renderLandingStats(false);
                    $('vendorList').innerHTML = '<p class="booking-empty" style="padding:22px">We couldn’t load market stalls. Please refresh the page to try again.</p>';
                    showToast(error.message, true);
                }
            }
            async function initializeApp() {
                $('year').textContent = new Date().getFullYear();
                setupLandingInteractions();
                try {
                    const result = await api('/session');
                    state.csrf = result.csrf_token;
                    state.user = result.user || null;
                    state.favorites = result.favorites || [];
                    state.appointments = result.appointments || [];
                    updateProfile();
                } catch (error) { showToast(error.message, true); }
                await loadVendors();
                const resetToken = new URLSearchParams(window.location.search).get('password_reset');
                if (resetToken !== null) {
                    if (/^[a-f0-9]{64}$/.test(resetToken)) {
                        showPasswordResetForm(resetToken);
                    } else {
                        setAuthMode('login');
                        openModal('authModal');
                        $('authFormMessage').textContent = 'This password reset link is invalid. Request a new one.';
                    }
                }
                if (state.user) {
                    switchView('dashboard');
                    updateRoleDashboard();
                } else {
                    renderCustomerOrders();
                    renderVendorOrders();
                }
                if (googleClientId) {
                    const waitForGoogle = setInterval(() => {
                        if (window.google && google.accounts && google.accounts.id) { clearInterval(waitForGoogle); initializeGoogle(); }
                    }, 200);
                    setTimeout(() => clearInterval(waitForGoogle), 10000);
                } else {
                    $('googleHelp').textContent = 'Google sign-in is optional and not configured yet. You can create an account or sign in with your email instead.';
                }
            }

            ['openDashboard', 'bottomExplore', 'navDiscover'].forEach(id => $(id).addEventListener('click', event => { event.preventDefault(); switchView('dashboard'); }));
            $('homeLink').addEventListener('click', event => { event.preventDefault(); switchView('landing'); });
            ['signInTop', 'profileSignIn'].forEach(id => $(id).addEventListener('click', () => { $('profileMenu').classList.remove('open'); setAuthMode('login'); openModal('authModal'); }));
            $('heroSignIn').addEventListener('click', () => {
                setAuthMode('register');
                $('authRole').value = 'customer';
                openModal('authModal');
            });
            ['heroVendorSignup', 'marketVendorSignup'].forEach(id => $(id).addEventListener('click', () => {
                setAuthMode('register');
                $('authRole').value = 'vendor';
                openModal('authModal');
            }));
            $('heroSearchForm').addEventListener('submit', event => {
                event.preventDefault();
                exploreFromLanding($('heroSearchInput').value.trim(), '');
            });
            document.querySelectorAll('[data-hero-category]').forEach(button => {
                button.addEventListener('click', () => exploreFromLanding('', button.dataset.heroCategory));
            });
            $('signInMode').addEventListener('click', () => setAuthMode('login'));
            $('signUpMode').addEventListener('click', () => setAuthMode('register'));
            $('emailAuthForm').addEventListener('submit', handleEmailAuth);
            $('forgotPasswordButton').addEventListener('click', requestPasswordReset);
            $('passwordResetForm').addEventListener('submit', handlePasswordReset);
            $('cancelPasswordReset').addEventListener('click', () => {
                const cleanUrl = new URL(window.location.href);
                cleanUrl.searchParams.delete('password_reset');
                window.history.replaceState({}, '', cleanUrl.pathname + cleanUrl.search + cleanUrl.hash);
                $('signInMode').parentElement.hidden = false;
                passwordResetToken = '';
                setAuthMode('login');
            });
            $('merchantCta').addEventListener('click', () => {
                showDashboardPanel('vendor');
                showVendorSection('vendor-application');
                renderMyVendors();
            });
            $('customerExploreButton').addEventListener('click', () => {
                $('discoverTab').click();
                $('vendorList').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            ['vendorMarketButton'].forEach(id => $(id).addEventListener('click', () => {
                showMarketDiscovery();
            }));
            $('dashboardReturnButton').addEventListener('click', updateRoleDashboard);
            $('dashboardBrand').addEventListener('click', event => {
                event.preventDefault();
                updateRoleDashboard();
            });
            $('refreshAdminStorage').addEventListener('click', loadAdminStorage);
            document.querySelector('.dashboard-sidebar-nav').addEventListener('click', event => {
                const button = event.target.closest('[data-dashboard-nav]');
                if (!button) return;
                document.querySelectorAll('.dashboard-nav-button').forEach(item => item.classList.toggle('active', item === button));
                const destination = button.dataset.dashboardNav;
                if (destination === 'overview') {
                    updateRoleDashboard();
                } else if (destination === 'discover') {
                    showMarketDiscovery();
                } else if (destination === 'saved' || destination === 'visits') {
                    setTab(destination);
                } else if (destination === 'customer-orders') {
                    $('dashboardEyebrow').textContent = 'ORDER HISTORY';
                    $('dashboardHeading').textContent = 'Your product orders';
                    $('dashboardSubtitle').textContent = 'Track purchases from local market vendors.';
                    showDashboardPanel('customer-orders');
                    loadCustomerOrders(true);
                } else if (destination === 'messages' && (!state.user || state.user.role === 'customer')) {
                    setTab('messages');
                } else if (destination === 'messages') {
                    showDashboardPanel('vendor');
                    showVendorSection('messages');
                } else if (destination === 'listings') {
                    showDashboardPanel('vendor');
                    showVendorSection('listings');
                } else if (destination === 'products') {
                    showDashboardPanel('vendor');
                    showVendorSection('products');
                } else if (destination === 'add-product') {
                    showDashboardPanel('vendor');
                    showVendorSection('add-product');
                } else if (destination === 'vendor-application') {
                    showDashboardPanel('vendor');
                    showVendorSection('vendor-application');
                } else if (destination === 'vendor-orders') {
                    showDashboardPanel('vendor');
                    showVendorSection('vendor-orders');
                } else if (destination === 'review') {
                    showDashboardPanel('admin');
                    showAdminSection('overview');
                    $('adminApplications').scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (destination === 'storage') {
                    showDashboardPanel('admin');
                    showAdminSection('storage');
                    loadAdminStorage();
                }
            });
            $('vendorStoreProfile').addEventListener('click', event => {
                const button = event.target.closest('[data-view-vendor-profile]');
                if (button) showMarketDiscovery(button.dataset.viewVendorProfile);
            });
            $('merchantVendorList').addEventListener('click', event => {
                const button = event.target.closest('[data-edit-vendor]');
                if (button) editVendorProfile(button.dataset.editVendor);
            });
            $('merchantVendorList').addEventListener('click', event => {
                const button = event.target.closest('[data-manage-products]');
                if (!button) return;
                state.productVendorId = button.dataset.manageProducts;
                refreshProductVendorOptions();
                showDashboardPanel('vendor');
                showVendorSection('products');
            });
            $('merchantVendorList').addEventListener('submit', async event => {
                const form = event.target.closest('form[data-update-vendor-docs]');
                if (!form) return;
                event.preventDefault();
                const uploadError = validateRequirementFiles(form.querySelectorAll('input[type="file"]'));
                if (uploadError) {
                    showToast(uploadError, true);
                    return;
                }
                const submit = form.querySelector('[type="submit"]');
                submit.disabled = true;
                submit.textContent = 'Uploading…';
                try {
                    const result = await api('/vendors/requirements', {
                        method: 'POST',
                        body: new FormData(form)
                    });
                    state.myVendors = result.vendors || [];
                    renderMyVendors();
                    showToast('Your requirements have been saved.');
                } catch (error) {
                    showToast(error.message, true);
                } finally {
                    if (submit.isConnected) {
                        submit.disabled = false;
                        submit.textContent = 'Save requirements';
                    }
                }
            });
            $('merchantVendorList').addEventListener('click', async event => {
                const payButton = event.target.closest('[data-rental-payment]');
                const checkButton = event.target.closest('[data-check-payment]');
                const button = payButton || checkButton;
                if (!button) return;
                button.disabled = true;
                try {
                    if (payButton) {
                        const result = await api('/vendors/rental-payment', {
                            method: 'POST',
                            body: { vendor_id: payButton.dataset.rentalPayment }
                        });
                        const vendor = state.myVendors.find(item => String(item.id) === String(payButton.dataset.rentalPayment));
                        if (vendor && result.checkout_url) {
                            vendor.rental_checkout_url = result.checkout_url;
                            vendor.rental_payment_status = result.payment_status || 'pending';
                        }
                        renderMyVendors();
                        showToast('Checkout is ready. Continue using the secure Xendit link.');
                    } else {
                        const result = await api('/vendors/rental-payment/check', {
                            method: 'POST',
                            body: { vendor_id: checkButton.dataset.checkPayment }
                        });
                        await loadMyVendors();
                        showToast(result.payment_status === 'paid'
                            ? 'Xendit confirmed your monthly rent payment.'
                            : 'Payment is not confirmed yet. Check again after checkout.');
                    }
                } catch (error) {
                    button.disabled = false;
                    showToast(error.message, true);
                }
            });
            $('cancelVendorEdit').addEventListener('click', resetVendorEditor);
            ['productVendorSelect', 'productCatalogVendorSelect'].forEach(id => $(id).addEventListener('change', event => {
                state.productVendorId = event.target.value;
                $('productVendorSelect').value = state.productVendorId;
                $('productCatalogVendorSelect').value = state.productVendorId;
                state.managedProducts = [];
                resetProductEditor();
                loadManagedProducts();
            }));
            $('addProductFromCatalog').addEventListener('click', () => {
                document.querySelector('.dashboard-nav-button[data-dashboard-nav="add-product"]').click();
                $('productName').focus({ preventScroll: true });
            });
            $('productForm').addEventListener('submit', async event => {
                event.preventDefault();
                const productId = $('productId').value;
                const payload = new FormData($('productForm'));
                payload.set('vendor_id', state.productVendorId);
                const submit = $('saveProductButton');
                const feedback = $('productFormFeedback');
                submit.disabled = true;
                submit.textContent = productId ? 'Saving changes…' : 'Adding product…';
                feedback.textContent = '';
                try {
                    await api('/vendors/products', {
                        method: 'POST',
                        body: payload
                    });
                    resetProductEditor();
                    await loadManagedProducts();
                    window.dispatchEvent(new Event('pwesto:products-updated'));
                    showToast(productId ? 'Your product was updated.' : 'Your product was added to the stall.');
                    feedback.textContent = productId ? 'Product details updated.' : 'Product added to your catalog.';
                    showVendorSection('products');
                } catch (error) {
                    feedback.textContent = error.message;
                    showToast(error.message, true);
                } finally {
                    submit.disabled = false;
                    submit.textContent = $('productId').value ? 'Save changes' : 'Add product';
                }
            });
            $('cancelProductEdit').addEventListener('click', resetProductEditor);
            $('vendorProductList').addEventListener('click', async event => {
                const editButton = event.target.closest('[data-edit-product]');
                if (editButton) {
                    const product = state.managedProducts.find(item => item.id === editButton.dataset.editProduct);
                    if (!product) return;
                    $('productId').value = product.id;
                    $('productName').value = product.name;
                    $('productDescription').value = product.description || '';
                    $('productPrice').value = (Number(product.price_cents) / 100).toFixed(2);
                    $('productStock').value = product.stock;
                    const preview = $('productImagePreview');
                    if (product.image_url) {
                        preview.src = product.image_url;
                        preview.hidden = false;
                    } else {
                        preview.removeAttribute('src');
                        preview.hidden = true;
                    }
                    $('saveProductButton').textContent = 'Save changes';
                    $('cancelProductEdit').hidden = false;
                    $('productFormHeading').textContent = 'Edit product';
                    showVendorSection('add-product');
                    $('productName').focus();
                    return;
                }
                const deleteButton = event.target.closest('[data-delete-product]');
                if (!deleteButton || !window.confirm('Remove this product from your stall?')) return;
                deleteButton.disabled = true;
                try {
                    await api('/vendors/products', { method: 'DELETE', body: { product_id: deleteButton.dataset.deleteProduct } });
                    await loadManagedProducts();
                    window.dispatchEvent(new Event('pwesto:products-updated'));
                    showToast('The product was removed.');
                } catch (error) {
                    deleteButton.disabled = false;
                    showToast(error.message, true);
                }
            });
            $('productImage').addEventListener('change', () => {
                const preview = $('productImagePreview');
                const feedback = $('productFormFeedback');
                if (preview.dataset.objectUrl) URL.revokeObjectURL(preview.dataset.objectUrl);
                delete preview.dataset.objectUrl;
                let file = $('productImage').files[0];
                feedback.textContent = '';
                if (file && file.size > 5242880) {
                    $('productImage').value = '';
                    file = null;
                    feedback.textContent = 'Choose an image smaller than 5 MB.';
                    showToast('Product images must be no larger than 5 MB.', true);
                }
                if (!file) {
                    const product = state.managedProducts.find(item => item.id === $('productId').value);
                    if (product && product.image_url) {
                        preview.src = product.image_url;
                        preview.hidden = false;
                    } else {
                        preview.removeAttribute('src');
                        preview.hidden = true;
                    }
                    return;
                }
                const objectUrl = URL.createObjectURL(file);
                preview.dataset.objectUrl = objectUrl;
                preview.src = objectUrl;
                preview.hidden = false;
            });
            $('productImagePreview').addEventListener('error', () => {
                $('productImagePreview').hidden = true;
                $('productFormFeedback').textContent = 'This photo preview could not be loaded. Choose another image.';
            });
            $('refreshVendorOrders').addEventListener('click', () => loadVendorOrders(false));
            $('refreshCustomerOrders').addEventListener('click', () => loadCustomerOrders(false));
            $('customerOrderList').addEventListener('click', async event => {
                const button = event.target.closest('[data-check-order-payment]');
                if (!button) return;
                button.disabled = true;
                try {
                    const result = await api('/orders/check-payment', {
                        method: 'POST',
                        body: { order_id: button.dataset.checkOrderPayment }
                    });
                    showToast(result.payment_status === 'paid'
                        ? 'GCash payment confirmed. The vendor can now see your order.'
                        : 'Payment is still pending. Complete the Xendit checkout, then check again.');
                    await loadCustomerOrders(true);
                } catch (error) {
                    button.disabled = false;
                    showToast(error.message || 'The payment status could not be checked.', true);
                }
            });
            $('vendorOrderList').addEventListener('click', async event => {
                const button = event.target.closest('[data-order-status]');
                if (!button) return;
                button.disabled = true;
                try {
                    const result = await api('/vendor/orders', {
                        method: 'PATCH',
                        body: { order_id: button.dataset.orderId, status: button.dataset.orderStatus }
                    });
                    await loadVendorOrders(true);
                    showToast(result.email_sent === false
                        ? 'Order updated, but the Gmail delivery email could not be sent. Check mail settings.'
                        : 'Order status updated and customer notified.');
                } catch (error) {
                    button.disabled = false;
                    showToast(error.message, true);
                }
            });
            $('accountProfileForm').addEventListener('submit', async event => {
                event.preventDefault();
                if (!state.user) return;
                const form = $('accountProfileForm');
                const submit = $('accountProfileSubmit');
                const feedback = $('accountProfileFeedback');
                submit.disabled = true;
                submit.textContent = 'Saving…';
                feedback.textContent = '';
                try {
                    const result = await api('/profile', {
                        method: 'POST',
                        body: new FormData(form)
                    });
                    state.user = result.user;
                    $('accountProfilePicture').value = '';
                    updateProfile();
                    feedback.textContent = 'Your profile has been updated.';
                    showToast('Your profile has been updated.');
                } catch (error) {
                    feedback.textContent = error.message;
                    showToast(error.message, true);
                } finally {
                    submit.disabled = false;
                    submit.textContent = 'Save profile';
                }
            });
            ['customerConversationList', 'vendorConversationList'].forEach(id => $(id).addEventListener('click', event => {
                const browseButton = event.target.closest('[data-browse-vendors]');
                if (browseButton) {
                    $('customerVendorContactsSection').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                const row = event.target.closest('[data-open-conversation]');
                if (row) openConversation(row.dataset.openConversation);
            }));
            $('customerVendorContacts').addEventListener('click', event => {
                const button = event.target.closest('[data-contact-vendor]');
                if (button) openChatWithVendor(button.dataset.contactVendor);
            });
            $('chatForm').addEventListener('submit', sendChatMessage);
            $('chatSuggestions').addEventListener('click', event => {
                const suggestion = event.target.closest('[data-chat-suggestion]');
                if (!suggestion) return;
                $('chatInput').value = suggestion.dataset.chatSuggestion;
                $('chatInput').focus();
            });
            function showMarketDiscovery(vendorId) {
                document.querySelectorAll('.dashboard-nav-button').forEach(button => button.classList.toggle('active', button.dataset.dashboardNav === 'discover'));
                $('dashboardEyebrow').textContent = 'MARKET DISCOVERY';
                $('dashboardHeading').textContent = 'Find your next local favorite';
                $('dashboardSubtitle').textContent = 'Find stalls and products near you.';
                $('dashboardReturnButton').style.display = '';
                showDashboardPanel('discover');
                document.querySelectorAll('.tab').forEach(tab => {
                    const active = tab.id === 'discoverTab';
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-selected', String(active));
                });
                state.activeTab = 'discover';
                renderVendors();
                if (vendorId) {
                    focusVendor(vendorId);
                    $('vendorMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    $('vendorList').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
            function setTab(name) {
                if (name === 'messages' && (!state.user || state.user.role !== 'customer')) {
                    state.pendingMessageIntent = { type: 'messages' };
                    setAuthMode('login');
                    $('authDescription').textContent = 'Sign in to view your messages and contact market vendors.';
                    openModal('authModal');
                    return;
                }
                state.activeTab = name;
                document.querySelectorAll('.dashboard-nav-button').forEach(button => button.classList.toggle('active', button.dataset.dashboardNav === (name === 'saved' || name === 'visits' ? name : name === 'messages' ? 'messages' : 'discover')));
                showDashboardPanel(name === 'visits' ? 'visits' : name === 'messages' ? 'messages' : 'discover');
                document.querySelectorAll('.tab').forEach(tab => {
                    const active = tab.id === ({ discover: 'discoverTab', saved: 'savedTab', visits: 'myVisitsTab', messages: 'messagesTab' })[name];
                    tab.classList.toggle('active', active);
                    tab.setAttribute('aria-selected', String(active));
                });
                if (name === 'visits') {
                    if (!state.user) { openModal('authModal'); return; }
                }
                if (name === 'messages') {
                    loadConversations(false);
                    $('messagesView').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
                renderVendors();
            }
            $('discoverTab').addEventListener('click', () => setTab('discover'));
            $('savedTab').addEventListener('click', () => setTab('saved'));
            $('myVisitsTab').addEventListener('click', () => setTab('visits'));
            $('messagesTab').addEventListener('click', () => setTab('messages'));
            $('profileButton').addEventListener('click', () => {
                const isOpen = $('profileMenu').classList.toggle('open');
                $('profileButton').setAttribute('aria-expanded', String(isOpen));
            });
            $('notificationButton').addEventListener('click', () => {
                const isOpen = $('notificationsPanel').hidden;
                $('notificationsPanel').hidden = !isOpen;
                $('notificationButton').setAttribute('aria-expanded', String(isOpen));
                if (isOpen) loadNotifications(false);
            });
            $('markAllNotificationsRead').addEventListener('click', async () => {
                try {
                    await updateNotifications({ action: 'mark_all_read' });
                } catch (error) {
                    showToast(error.message, true);
                }
            });
            $('notificationList').addEventListener('click', event => {
                const item = event.target.closest('[data-notification-id]');
                if (!item) return;
                const notification = state.notifications.find(entry => entry.id === item.dataset.notificationId);
                if (notification) openNotification(notification);
            });
            $('profileSignOut').addEventListener('click', signOut);
            $('searchInput').addEventListener('input', renderVendors);
            $('categoryFilter').addEventListener('change', () => {
                if ($('categoryFilter').value) recordCategoryInterest($('categoryFilter').value);
                renderVendors();
            });
            $('recommendationList').addEventListener('click', event => {
                const recommendation = event.target.closest('[data-recommendation-vendor]');
                if (!recommendation) return;
                $('searchInput').value = '';
                $('categoryFilter').value = '';
                setTab('discover');
                focusVendor(recommendation.dataset.recommendationVendor);
                $('vendorList').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
            $('assistantLauncher').addEventListener('click', () => toggleAssistant());
            $('assistantClose').addEventListener('click', () => toggleAssistant(false));
            $('assistantForm').addEventListener('submit', event => {
                event.preventDefault();
                const input = $('assistantInput');
                const message = input.value;
                input.value = '';
                sendAssistantMessage(message);
                input.focus();
            });
            $('assistantSuggestions').addEventListener('click', event => {
                const suggestion = event.target.closest('[data-assistant-suggestion]');
                if (!suggestion) return;
                sendAssistantMessage(suggestion.dataset.assistantSuggestion);
            });
            $('assistantMessages').addEventListener('click', event => {
                const vendorRecommendation = event.target.closest('[data-assistant-vendor]');
                if (vendorRecommendation) {
                    toggleAssistant(false);
                    showMarketDiscovery(vendorRecommendation.dataset.assistantVendor);
                    return;
                }
                const action = event.target.closest('[data-assistant-action]');
                if (!action) return;
                const destination = action.dataset.assistantAction;
                if (destination === 'vendor-application') {
                    document.querySelector('.dashboard-nav-button[data-dashboard-nav="vendor-application"]').click();
                    $('merchantForm').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    $('stallName').focus({ preventScroll: true });
                } else if (destination === 'add-product') {
                    document.querySelector('.dashboard-nav-button[data-dashboard-nav="add-product"]').click();
                    $('productName').focus({ preventScroll: true });
                } else if (destination === 'vendor-listings') {
                    document.querySelector('.dashboard-nav-button[data-dashboard-nav="listings"]').click();
                } else if (destination === 'vendor-messages') {
                    document.querySelector('.dashboard-nav-button[data-dashboard-nav="messages"]').click();
                } else if (destination === 'admin-review') {
                    document.querySelector('.dashboard-nav-button[data-dashboard-nav="review"]').click();
                }
                toggleAssistant(false);
            });
            document.addEventListener('keydown', event => {
                if (event.key === 'Escape' && !$('assistantPanel').hidden) toggleAssistant(false);
            });
            $('vendorList').addEventListener('click', event => {
                if (event.target.closest('[data-product-control]')) return;
                const messageButton = event.target.closest('[data-message-vendor]');
                if (messageButton) {
                    openChatWithVendor(messageButton.dataset.messageVendor);
                    return;
                }
                const action = event.target.closest('[data-action]');
                if (action) {
                    const id = String(action.dataset.id);
                    toggleFavorite(id).catch(error => showToast(error.message, true));
                    return;
                }
                const card = event.target.closest('[data-vendor]');
                if (card) focusVendor(card.dataset.vendor);
            });
            async function toggleFavorite(id) {
                const saved = !state.saved.has(id);
                if (state.user) {
                    const result = await api('/favorites', { method: 'POST', body: { vendor_id: id, saved: saved } });
                    state.favorites = result.favorites || [];
                    state.saved = new Set(state.favorites.map(String));
                } else {
                    if (saved) state.saved.add(id); else state.saved.delete(id);
                    localStorage.setItem(savedKey(), JSON.stringify(Array.from(state.saved)));
                }
                renderVendors();
            }
            $('bookingList').addEventListener('click', async event => {
                const cancelButton = event.target.closest('[data-cancel-visit]');
                if (!cancelButton) return;
                cancelButton.disabled = true;
                try {
                    const result = await api('/appointments', { method: 'DELETE', body: { id: cancelButton.dataset.cancelVisit } });
                    state.appointments = result.appointments || [];
                    renderAppointments();
                    showToast('Your visit has been cancelled.');
                } catch (error) {
                    cancelButton.disabled = false;
                    showToast(error.message, true);
                }
            });
            $('merchantForm').addEventListener('submit', async event => {
                event.preventDefault();
                if (!state.user) {
                    showToast('Sign in or create an account to submit your stall.');
                    openModal('authModal');
                    return;
                }
                const submit = $('merchantForm').querySelector('[type="submit"]');
                const editingVendorId = $('stallVendorId').value;
                if (!editingVendorId) {
                    const uploadError = validateRequirementFiles([
                        $('applicationDocument'),
                        $('cedulaDocument'),
                        $('vendorTwoByTwo')
                    ]);
                    if (uploadError) {
                        showToast(uploadError, true);
                        return;
                    }
                }
                submit.disabled = true;
                submit.textContent = editingVendorId ? 'Saving…' : 'Submitting…';
                try {
                    const request = editingVendorId
                        ? {
                            method: 'POST',
                            body: {
                            vendor_id: editingVendorId,
                            name: $('stallName').value,
                            category: $('stallCategory').value,
                            market: $('stallMarket').value,
                            specialty: $('stallSpecialty').value
                            }
                        }
                        : { method: 'POST', body: new FormData($('merchantForm')) };
                    const result = await api(editingVendorId ? '/vendors/update' : '/vendors', request);
                    state.myVendors = result.vendors || [];
                    state.rentalFee = Number(result.rental_fee || state.rentalFee);
                    state.paymentConfigured = result.payment_configured === true;
                    if (result.user) {
                        state.user = result.user;
                        updateProfile();
                    }
                    renderMyVendors();
                    if (editingVendorId) {
                        resetVendorEditor();
                        await loadVendors();
                        showToast('Your stall details have been updated.');
                    } else {
                        resetVendorEditor();
                        showToast('Your stall rental request was submitted to the admin. Pay the first-month rent to complete the review requirements.');
                    }
                } catch (error) { showToast(error.message, true); }
                finally {
                    submit.disabled = false;
                    if (!editingVendorId) submit.textContent = 'Submit rental request →';
                    else if ($('stallVendorId').value) submit.textContent = 'Save stall changes';
                }
            });
            $('adminApplications').addEventListener('click', async event => {
                const action = event.target.closest('[data-review-vendor]');
                if (!action) return;
                action.disabled = true;
                try {
                    const result = await api('/admin/vendors', {
                        method: 'POST',
                        body: { vendor_id: action.dataset.reviewVendor, decision: action.dataset.decision }
                    });
                    state.adminSummary = result.summary || {};
                    state.adminApplications = result.applications || [];
                    renderAdminDashboard();
                    if (action.dataset.decision === 'approved') await loadVendors();
                    showToast(action.dataset.decision === 'approved' ? 'Stall rental approved and listing published.' : 'Stall rental request declined.');
                } catch (error) {
                    action.disabled = false;
                    showToast(error.message, true);
                }
            });
            $('adminUnassignedStalls').addEventListener('submit', async event => {
                const form = event.target.closest('[data-assign-stall]');
                if (!form) return;
                event.preventDefault();
                const submit = form.querySelector('button[type="submit"]');
                submit.disabled = true;
                try {
                    const formData = new FormData(form);
                    const result = await api('/admin/vendors', {
                        method: 'POST',
                        body: {
                            action: 'assign_account',
                            vendor_id: form.dataset.assignStall,
                            name: formData.get('name'),
                            email: formData.get('email')
                        }
                    });
                    state.adminSummary = result.summary || {};
                    state.adminApplications = result.applications || [];
                    state.adminUnassignedStalls = result.unassigned_stalls || [];
                    renderAdminDashboard();
                    if (result.account_created) {
                        showToast('Vendor account created and linked. Ask the vendor to use “Forgot password” with this email to set a password.');
                    } else {
                        showToast('Existing vendor account linked to the stall.');
                    }
                } catch (error) {
                    submit.disabled = false;
                    showToast(error.message, true);
                }
            });
            document.addEventListener('click', event => {
                const bookingButton = event.target.closest('[data-book]');
                if (bookingButton) startBooking(bookingButton.dataset.book);
                if (!event.target.closest('.profile-wrap')) {
                    $('profileMenu').classList.remove('open');
                    $('profileButton').setAttribute('aria-expanded', 'false');
                }
                if (!event.target.closest('.notification-wrap')) {
                    $('notificationsPanel').hidden = true;
                    $('notificationButton').setAttribute('aria-expanded', 'false');
                }
                if (event.target.matches('[data-close-modal]') || event.target.classList.contains('modal-backdrop')) closeModals();
            });
            $('nearbyButton').addEventListener('click', () => {
                if (!navigator.geolocation) { showToast('Location is not available in this browser.', true); return; }
                $('nearbyButton').textContent = 'Finding you…';
                navigator.geolocation.getCurrentPosition(position => {
                    const location = [position.coords.latitude, position.coords.longitude];
                    if (state.map) state.map.flyTo(location, 14);
                    state.vendors.forEach(vendor => {
                        const distance = L.latLng(location).distanceTo([vendor.lat, vendor.lng]) / 1000;
                        vendor.distance = distance < 1 ? Math.round(distance * 1000) + ' m' : distance.toFixed(1) + ' km';
                    });
                    renderVendors();
                    $('nearbyButton').textContent = '⌖ Location updated';
                    showToast('Showing stalls near your location.');
                }, () => { $('nearbyButton').textContent = '⌖ Use my location'; showToast('Could not access your location. Check your browser permissions.', true); }, { timeout: 10000, maximumAge: 60000 });
            });
            $('bookingForm').addEventListener('submit', async event => {
                event.preventDefault();
                if (!state.activeVendor) return;
                const submit = $('bookingForm').querySelector('[type="submit"]');
                submit.disabled = true;
                submit.textContent = 'Saving your visit…';
                try {
                    const result = await api('/appointments', { method: 'POST', body: { vendor_id: state.activeVendor.id, visit_date: $('visitDate').value, visit_time: $('visitTime').value } });
                    state.appointments = result.appointments || state.appointments;
                    renderAppointments();
                    closeModals();
                    showToast('Your visit is saved. See you at the market!');
                } catch (error) { showToast(error.message, true); }
                finally { submit.disabled = false; submit.textContent = 'Save my visit →'; }
            });
            document.addEventListener('keydown', event => { if (event.key === 'Escape') closeModals(); });
            initializeApp();
        })();
    </script>
</body>
</html>
