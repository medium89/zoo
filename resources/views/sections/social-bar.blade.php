@if(isset($socials) && $socials->count() > 0)
<div class="social-bar" id="socialBar">
    <button class="social-bar-toggle" id="socialBarToggle" aria-label="toggle socials">
        <i class="fas fa-chevron-right"></i>
    </button>
    <ul class="social-bar-icons">
        @foreach($socials as $social)
            <li>
                <a href="{{ $social->link }}" target="_blank" rel="noopener">
                    <i class="{{ $social->icon }}"></i>
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endif
