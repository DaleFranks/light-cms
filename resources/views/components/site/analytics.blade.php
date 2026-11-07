@props([
    'settings',
    'tracks',
    'place',
])

{{-- The analytics and verification code for one place in the page: "head"
     (early in <head>), "head-end" (just before </head>), "body-start" (just
     after <body>) or "body-end" (just before </body>).
     The IDs are checked when saved, so they're safe inside the scripts. --}}
@if ($place === 'head')
    @if ($settings->google_verification)
        <meta name="google-site-verification" content="{{ $settings->google_verification }}">
    @endif
    @if ($settings->bing_verification)
        <meta name="msvalidate.01" content="{{ $settings->bing_verification }}">
    @endif
    @if ($tracks && $settings->google_tag_manager_id)
        <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $settings->google_tag_manager_id }}');</script>
    @endif
    @if ($tracks && $settings->google_analytics_id)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $settings->google_analytics_id }}"></script>
        <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $settings->google_analytics_id }}');</script>
    @endif
    @if ($tracks && $settings->meta_pixel_id)
        <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','{{ $settings->meta_pixel_id }}');fbq('track','PageView');</script>
    @endif
@elseif ($place === 'head-end')
    @if ($tracks && filled($settings->head_code))
        {!! $settings->head_code !!}
    @endif
@elseif ($place === 'body-start')
    @if ($tracks && $settings->google_tag_manager_id)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $settings->google_tag_manager_id }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @if ($tracks && $settings->meta_pixel_id)
        <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ $settings->meta_pixel_id }}&amp;ev=PageView&amp;noscript=1"></noscript>
    @endif
@elseif ($place === 'body-end')
    @if ($tracks && filled($settings->footer_code))
        {!! $settings->footer_code !!}
    @endif
@endif
