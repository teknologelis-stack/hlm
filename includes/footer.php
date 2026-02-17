            </div><!-- .content-wrapper -->
        </main><!-- .main-content -->
    </div><!-- .layout-container -->

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        }
        
        function toggleUserMenu() {
            document.getElementById('userDropdown').classList.toggle('show');
        }
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.user-menu')) {
                document.getElementById('userDropdown').classList.remove('show');
            }
        });
    </script>
</body>
</html>
