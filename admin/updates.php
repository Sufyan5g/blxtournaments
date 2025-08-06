<?php
require_once 'includes/header.php'; // Includes db.php and session check

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_update'])) {
    // Ab content HTML format mein aayega, isliye sanitize_input use nahi karenge, TinyMCE khud handle karega
    $title = strip_tags($_POST['title']); // Title se HTML tags hata dein
    $content = $_POST['content']; // Content ko as-is save karein

    $stmt = $conn->prepare("INSERT INTO updates (title, content, target_user_id) VALUES (?, ?, NULL)");
    $stmt->bind_param("ss", $title, $content);
    if ($stmt->execute()) {
        $message = "Update sent successfully!";
    } else {
        $error = "Error: Failed to send update.";
    }
    $stmt->close();
}
?>

<!-- TinyMCE CDN (ye zaroori hai) -->
<script src="https://cdn.tiny.cloud/1/upx863ectsi68efs6u05i4ihj7muru47cvqzlbm7ugix87vp/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* Modal CSS */
.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
.modal-content { background-color: #2c3e50; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; color: white; }
.close-btn { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
#user-search-input { width: calc(100% - 20px); padding: 10px; margin-bottom: 15px; }
#user-search-results { max-height: 300px; overflow-y: auto; }
.user-result-item { padding: 10px; border-bottom: 1px solid #444; cursor: pointer; display: flex; align-items: center; gap: 10px; }
.user-result-item:hover { background-color: #34495e; }
.user-result-item img { width: 40px; height: 40px; border-radius: 50%; }
</style>

<div class="page-header"><h1>Send Updates / Notifications</h1></div>

<div class="card">
    <h2>Create New Update</h2>
    
    <?php if ($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

    <form action="updates.php" method="POST">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" required>
        </div>
        <div class="form-group">
            <label for="content">Content / Message</label>
            <!-- Original textarea ko is id="content" ke saath rakhein -->
            <textarea name="content" id="content"></textarea>
        </div>
        <button type="submit" name="create_update" class="btn btn-primary">Send Update</button>
    </form>
</div>

<!-- Player Search Modal -->
<div id="playerModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">×</span>
        <h2>Add Player to Post</h2>
        <input type="text" id="user-search-input" placeholder="Search by Name or UID...">
        <div id="user-search-results"></div>
    </div>
</div>

<script>
// TinyMCE Initialization Script (Yeh waisa hi rahega)
tinymce.init({
    selector: 'textarea#content',
    plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
    toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat | addplayer', // Custom button
    skin: 'oxide-dark',
    content_css: 'dark',
    height: 400,
    setup: function(editor) {
        editor.ui.registry.addButton('addplayer', {
            text: 'Add Player',
            icon: 'user',
            tooltip: 'Search and embed a player profile',
            onAction: function() {
                document.getElementById('playerModal').style.display = 'block';
                document.getElementById('user-search-input').focus();
            }
        });
    }
});

// === MODAL AUR SEARCH KA BEHTAR LOGIC YAHAN HAI ===
const modal = document.getElementById('playerModal');
const closeBtn = document.querySelector('#playerModal .close-btn');
const searchInput = document.getElementById('user-search-input');
const searchResults = document.getElementById('user-search-results');
let searchTimeout;

closeBtn.onclick = () => {
    modal.style.display = 'none';
    searchResults.innerHTML = ''; // Reset results on close
    searchInput.value = '';     // Reset input on close
};

window.onclick = (event) => {
    if (event.target == modal) {
        modal.style.display = 'none';
    }
};

searchInput.addEventListener('keyup', function() {
    clearTimeout(searchTimeout); // Purana search request cancel karein agar user tezi se type kar raha hai
    let query = this.value.trim();

    if (query.length < 2) {
        searchResults.innerHTML = '<p style="text-align:center; color:#aaa;">Type at least 2 characters...</p>';
        return;
    }

    // "Loading..." ka message dikhayein
    searchResults.innerHTML = '<p style="text-align:center; color:#fff;">Searching...</p>';
    
    // Thora sa wait karein (300ms) taake har key press par request na jaye
    searchTimeout = setTimeout(() => {
        // === YEH SAB SE ZAROORI LINE HAI: PATH KO THEEK KIYA GAYA HAI ===
        // Hum absolute path use kar rahe hain ('/admin/...') jo hamesha kaam karega
        fetch(`/admin/get_user_list.php?search=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok.');
                }
                return response.json();
            })
            .then(data => {
                searchResults.innerHTML = ''; // Pehle wale results saaf karein
                if (data.length > 0) {
                    data.forEach(user => {
                        const userDiv = document.createElement('div');
                        userDiv.className = 'user-result-item';
                        
                        // Default avatar agar user ka avatar na ho
                        const avatarSrc = user.avatar_url ? `/${user.avatar_url}` : '/path/to/default-avatar.png';

                        userDiv.innerHTML = `<img src="${avatarSrc}" alt="avatar"> <span>${user.name} (UID: ${user.uid})</span>`;
                        
                        // Jab user par click ho
                        userDiv.onclick = function() {
                            // === YEH DEKHEIN: HUM EDITOR MEIN EK SPECIAL CODE DAAL RAHE HAIN ===
                            const userShortcode = `<p>[user id="${user.id}"]</p>`; // p tag ke andar daal rahe hain taake alag line mein aaye
                            tinymce.activeEditor.execCommand('mceInsertContent', false, userShortcode);
                            
                            // Modal band kar dein
                            modal.style.display = 'none';
                        };
                        searchResults.appendChild(userDiv);
                    });
                } else {
                    searchResults.innerHTML = '<p style="text-align:center; color:#e74c3c;">No user found.</p>';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                searchResults.innerHTML = '<p style="text-align:center; color:#e74c3c;">Error fetching users. Check console (F12).</p>';
            });
    }, 300);
});
</script>

<?php require_once 'includes/footer.php'; ?>