@if(isset($socials) && $socials->count() > 0)
<button class="social-bar-open" id="socialBarOpen" aria-label="open socials">
    <i class="fas fa-chevron-left"></i>
</button>
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
