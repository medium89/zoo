@extends('layouts.app')
@section('content')
    @include('sections.header')
    @include('sections.social-bar')
    @include('sections.slider')
    @include('sections.about')
    @include('sections.advantages')
    @include('sections.services')
    @include('sections.gallery')
    @include('sections.contacts')
    @include('sections.footer')

    {{-- Быстрый контакт: плавающая плашка + модалка --}}
    @php($recaptchaKey = config('services.recaptcha.site_key'))
    <div id="quickContactBadge" class="quick-contact-badge">
        <i class="fa fa-phone me-2"></i>
        <span>Связаться со мной</span>
        <button type="button" class="qc-badge-close" aria-label="Скрыть">&times;</button>
    </div>
    <div id="quickContactModal" class="quick-contact-modal">
        <div class="qc-backdrop"></div>
        <div class="qc-dialog">
            <button type="button" class="qc-close" aria-label="Закрыть">&times;</button>
            <h4 class="mb-3">Связаться со мной</h4>
            <form class="contact-form" action="{{ route('feedback.store') }}" method="POST">
                @csrf
                <div class="form-group">
                    <input type="text" name="name" placeholder="Ваше имя" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="+7(999)999-99-99" required>
                </div>
                <div class="form-group">
                    <textarea name="message" placeholder="Опишите необходимую услугу, животное, даты и адрес" rows="3" required></textarea>
                </div>
                @if($recaptchaKey)
                    <div class="form-group d-flex justify-content-center" style="margin-top:10px;">
                        <div class="g-recaptcha" data-sitekey="{{ $recaptchaKey }}"></div>
                    </div>
                @endif
                <div class="form-group">
                    <button type="submit" class="submit-btn w-100">
                        <i class="fas fa-paper-plane"></i>
                        Отправить заявку
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection 

<style>
    .quick-contact-badge{
        position: fixed;
        left: 18px;
        top: 110px;
        background: #7b5bdb;
        color: #fff;
        border: 2px solid #fff;
        border-radius: 14px;
        padding: 12px 46px 12px 16px;
        box-shadow: 0 12px 28px rgba(0,0,0,0.2);
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        opacity: 0;
        transform: translateY(-12px);
        transition: opacity 0.35s ease, transform 0.35s ease;
        z-index: 1045;
        font-weight: 700;
        position: fixed;
    }
    .quick-contact-badge.shown{
        opacity: 1;
        transform: translateY(0);
    }
    .quick-contact-badge.animate{
        animation: qc-pulse 1s ease-in-out 0s 3, qc-scale 1s ease-in-out 0s 3;
    }
    @keyframes qc-pulse{
        0% { box-shadow: 0 0 0 0 rgba(123,91,219,0.5); }
        70% { box-shadow: 0 0 0 12px rgba(123,91,219,0); }
        100% { box-shadow: 0 0 0 0 rgba(123,91,219,0); }
    }
    @keyframes qc-scale{
        0% { transform: translateY(0) scale(1); }
        50% { transform: translateY(0) scale(1.04); }
        100% { transform: translateY(0) scale(1); }
    }
    .quick-contact-modal{
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
    }
    .qc-badge-close{
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255,255,255,0.18);
        border: 1px solid rgba(255,255,255,0.6);
        color: #fff;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s ease, transform 0.2s ease;
    }
    .qc-badge-close:hover{
        background: rgba(255,255,255,0.3);
        transform: translateY(-50%) scale(1.05);
    }
    .quick-contact-modal.open{
        display: flex;
    }
    .qc-backdrop{
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.45);
        backdrop-filter: blur(2px);
    }
    .qc-dialog{
        position: relative;
        background: #fff;
        border-radius: 16px;
        padding: 22px;
        max-width: 420px;
        width: 90%;
        box-shadow: 0 18px 38px rgba(0,0,0,0.22);
        z-index: 1;
    }
    .qc-close{
        position: absolute;
        top: 10px;
        right: 12px;
        background: transparent;
        border: none;
        font-size: 26px;
        line-height: 1;
        cursor: pointer;
        color: #6c757d;
    }
    @media (max-width: 575.98px){
        .quick-contact-badge{
            left: 12px;
            top: 12px;
            justify-content: center;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', ()=>{
    const badge = document.getElementById('quickContactBadge');
    const modal = document.getElementById('quickContactModal');
    if(!badge || !modal) return;

    const openModal = ()=> modal.classList.add('open');
    const closeModal = ()=> modal.classList.remove('open');

    setTimeout(()=>{
        badge.classList.add('shown','animate');
        setTimeout(()=>badge.classList.remove('animate'), 3200);
    }, 5000);

    badge.addEventListener('click', (e)=>{
        if (e.target.classList.contains('qc-badge-close')) return;
        openModal();
    });
    badge.querySelector('.qc-badge-close')?.addEventListener('click', (e)=>{
        e.stopPropagation();
        badge.classList.remove('shown','animate');
    });
    modal.querySelector('.qc-backdrop').addEventListener('click', closeModal);
    modal.querySelector('.qc-close').addEventListener('click', closeModal);
});
</script>
