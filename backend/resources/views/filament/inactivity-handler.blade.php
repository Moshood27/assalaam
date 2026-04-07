<script>
    (function() {
        let timeout;
        const idleTime = 15 * 60 * 1000; // 15 minutes

        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                // When the timer hits, reload the page.
                // If the session has expired on the server (which should happen at 15m),
                // the user will be redirected to the login page.
                window.location.reload();
            }, idleTime + 1000); // 1s buffer
        }

        // Listen for user interactions
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt =>
            document.addEventListener(evt, resetTimer, true)
        );

        resetTimer();
    })();
</script>
