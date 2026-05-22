/**
 * CAMS - Password Toggle
 * Uses two pre-rendered icon spans to avoid Lucide SVG-replacement issues.
 * pw-eye-off = shown when password is hidden (default)
 * pw-eye     = shown when password is visible
 */
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const eyeOff = btn.querySelector('.pw-eye-off');
    const eyeOn  = btn.querySelector('.pw-eye');

    if (input.type === 'password') {
        // Reveal
        input.type = 'text';
        eyeOff.style.display = 'none';
        eyeOn.style.display  = 'inline-flex';
    } else {
        // Hide
        input.type = 'password';
        eyeOff.style.display = 'inline-flex';
        eyeOn.style.display  = 'none';
    }
}
