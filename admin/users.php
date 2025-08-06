<?php require_once 'includes/header.php'; ?>
<div class="page-header"><h1>Manage Users</h1></div>
<div class="card">
    <h2>Registered Users</h2>
    <div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Coins</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $result = $conn->query("SELECT id, name, email, coins FROM users ORDER BY id DESC");
            if ($result->num_rows > 0):
                while($row = $result->fetch_assoc()):
            ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><i class="fas fa-coins"></i> <?php echo $row['coins']; ?></td>
                <td>
                    <button class="btn btn-primary view-user-btn" data-userid="<?php echo $row['id']; ?>">
                        <i class="fas fa-eye"></i> View More
                    </button>
                </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5" style="text-align:center;">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>
<?php require_once 'includes/footer.php'; ?>