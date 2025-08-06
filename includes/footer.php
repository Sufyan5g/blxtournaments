    </main>
    <footer>
        <!-- Aap yahan footer content daal sakte hain -->
    </footer>

    <!-- Yeh line JavaScript ko batayegi ki user login hai ya nahi -->
    <script>
        const isUserLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    </script>
    
    <!-- Yahan se hamari main JS file load hogi -->
    <script src="/assets/js/script.js"></script>
</body>
</html>