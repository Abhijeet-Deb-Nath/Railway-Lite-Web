    </div>
    <script>
        // Auto-resize textareas
        function autoResize(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }
        
        // Initialize all query textareas
        document.addEventListener('DOMContentLoaded', function() {
            const queryBoxes = document.querySelectorAll('.query-box');
            queryBoxes.forEach(box => {
                autoResize(box);
            });
        });
    </script>
</body>
</html>
