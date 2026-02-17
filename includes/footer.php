            </div><!-- .content-wrapper -->
        </main><!-- .main-content -->
    </div><!-- .layout-container -->

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar) {
                sidebar.classList.toggle('collapsed');
            }
        }
        
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        }
        
        // Close user menu when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            if (dropdown && !event.target.closest('.user-menu')) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
