@if(isset($socials) && $socials->where('active', true)->count() > 0)
<button class="social-bar-open visible" id="socialBarOpen" aria-label="open socials">
    <i class="fas fa-chevron-left"></i>
</button>
<div class="social-bar collapsed" id="socialBar">
    <button class="social-bar-toggle" id="socialBarToggle" aria-label="toggle socials">
        <i class="fas fa-chevron-right"></i>
    </button>
    <ul class="social-bar-icons">
        @foreach($socials->where('active', true) as $social)
            <li>
                <a href="{{ $social->link }}" target="_blank" rel="noopener">
                    <i class="{{ $social->icon }}"></i>
                </a>
            </li>
        @endforeach
    </ul>
</div>
@endif
