@extends('frontend.layouts.master')
        @section('meta_title', $page->translation->seo_title ?? $page->translation->name)
        @section('meta_description', $page->translation->seo_description ?? $page->translation->name)
        @section('body_class', $page?->fixed_header ? 'wsus_fixed_header' : '')
        @section('contents')
        <div id="inwf" data-background="{{ url('/') }}/resources/assets/images/others/breadcrumb.webp" class="breadcrumb__area breadcrumb__bg"><div class="container"><div class="row"><div class="col-12"><div class="breadcrumb__content"><h3 class="title">سياسة الخصوصية</h3><nav class="breadcrumb"><span property="itemListElement" typeof="ListItem"><a href="/">Home</a></span><span class="breadcrumb-separator"><i class="fas fa-angle-right"></i></span><span property="itemListElement" typeof="ListItem"><a>سياسة الخصوصية</a></span></nav></div></div></div></div><div class="breadcrumb__shape-wrap"><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape01.svg" alt="img" class="alltuchtopdown"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape02.svg" alt="img" data-aos="fade-right" data-aos-delay="300"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape03.svg" alt="img" data-aos="fade-up" data-aos-delay="400"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape04.svg" alt="img" data-aos="fade-down-left" data-aos-delay="400"/><img src="{{ url('/') }}/frontend/img/others/breadcrumb_shape05.svg" alt="img" data-aos="fade-left" data-aos-delay="400"/></div></div><section class="section-py-120"><div class=" container"><div id="i7el"><p>مرحبًا بك في RevisionHubKenya! نحن نقدر خصوصيتك ونلتزم بحماية معلوماتك الشخصية. توضح سياسة الخصوصية هذه كيفية جمع بياناتك واستخدامها وحمايتها عند زيارتك لموقعنا الإلكتروني.</p>
<p>المعلومات التي نجمعها</p>
<p>المعلومات الشخصية: عندما تقوم بالتسجيل على موقعنا، أو الاشتراك في نشرتنا الإخبارية، أو ملء نموذج، قد نقوم بجمع معلومات شخصية مثل اسمك وعنوان بريدك الإلكتروني ورقم هاتفك وأي تفاصيل أخرى تقدمها.</p>
<p>المعلومات غير الشخصية: قد نقوم بجمع معلومات غير شخصية عن الزوار عندما يتفاعلون مع موقعنا. وقد يشمل ذلك اسم المتصفح، ونوع الكمبيوتر، والمعلومات الفنية حول وسائل اتصال المستخدمين بموقعنا، مثل نظام التشغيل ومقدمي خدمات الإنترنت المستخدمة وغيرها من المعلومات المشابهة.</p>
<p>ملفات تعريف الارتباط وتقنيات التتبع: قد يستخدم موقعنا "ملفات تعريف الارتباط" لتحسين تجربة المستخدم. تقوم متصفحات الويب الخاصة بالمستخدمين بوضع ملفات تعريف الارتباط على محرك الأقراص الثابتة الخاص بهم لأغراض حفظ السجلات وفي بعض الأحيان لتتبع المعلومات الخاصة بهم. يمكن للمستخدمين اختيار ضبط متصفح الويب الخاص بهم لرفض ملفات تعريف الارتباط أو لتنبيههم عند إرسال ملفات تعريف الارتباط. إذا فعلوا ذلك، لاحظ أن بعض أجزاء الموقع قد لا تعمل بشكل صحيح.</p>
<p>كيف نستخدم المعلومات التي تم جمعها</p>
<p>يجوز لـ RevisionHubKenya جمع واستخدام المعلومات الشخصية للمستخدمين للأغراض التالية:</p>
<p>لتحسين خدمة العملاء: تساعدنا المعلومات التي تقدمها على الاستجابة لطلبات خدمة العملاء واحتياجات الدعم الخاصة بك بشكل أكثر كفاءة.</p>
<p>لتخصيص تجربة المستخدم: قد نستخدم المعلومات بشكل إجمالي لفهم كيفية استخدام مستخدمينا كمجموعة للخدمات والموارد المقدمة على موقعنا.</p>
<p>لتحسين موقعنا: قد نستخدم التعليقات التي تقدمها لتحسين منتجاتنا وخدماتنا.</p>
<p>لمعالجة المدفوعات: يجوز لنا استخدام المعلومات التي يقدمها المستخدمون عن أنفسهم عند تقديم الطلب فقط لتقديم الخدمة لهذا الطلب. نحن لا نشارك هذه المعلومات مع أطراف خارجية إلا بالقدر اللازم لتقديم الخدمة.</p>
<p>لإرسال رسائل بريد إلكتروني دورية: قد نستخدم عنوان البريد الإلكتروني لإرسال معلومات المستخدم والتحديثات المتعلقة بطلبه. ويمكن استخدامه أيضًا للرد على استفساراتهم و/أو أسئلتهم و/أو طلباتهم الأخرى.</p>
<p>كيف نحمي معلوماتك</p>
<p>نحن نعتمد ممارسات مناسبة لجمع البيانات وتخزينها ومعالجتها وتدابير أمنية للحماية من الوصول غير المصرح به أو التغيير أو الكشف أو إتلاف معلوماتك الشخصية واسم المستخدم وكلمة المرور ومعلومات المعاملات والبيانات المخزنة على موقعنا.</p>
</div></div></section>
        @endsection