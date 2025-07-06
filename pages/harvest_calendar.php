<?php
$page_title = "Harvest Calendar";
require_once '../includes/config.php';

// Get current month if not specified
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$region = isset($_GET['region']) ? sanitize_input($_GET['region']) : '';

// Get all harvest seasons for the current month
$sql = "SELECT h.*, f.name as fruit_name, f.image, c.name as category_name 
        FROM harvest_seasons h
        JOIN fruits f ON h.fruit_id = f.fruit_id
        LEFT JOIN categories c ON f.category_id = c.category_id
        WHERE (MONTH(h.start_date) <= $month AND MONTH(h.end_date) >= $month)";

if (!empty($region)) {
    $sql .= " AND h.region LIKE '%$region%'";
}

$sql .= " ORDER BY f.name ASC";

$result = $conn->query($sql);
$harvests = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $harvests[] = $row;
    }
}

// Get distinct regions for filtering
$regions_sql = "SELECT DISTINCT region FROM harvest_seasons WHERE region != '' ORDER BY region ASC";
$regions_result = $conn->query($regions_sql);
$regions = [];

if ($regions_result && $regions_result->num_rows > 0) {
    while ($row = $regions_result->fetch_assoc()) {
        $regions[] = $row['region'];
    }
}

// Month names
$months = [
    1 => 'January',
    2 => 'February',
    3 => 'March',
    4 => 'April',
    5 => 'May',
    6 => 'June',
    7 => 'July',
    8 => 'August',
    9 => 'September',
    10 => 'October',
    11 => 'November',
    12 => 'December'
];
?>

<?php include('../includes/header.php'); ?>

<section class="calendar-section">
    <div class="container">
        <h2>Harvest Calendar</h2>
        <p class="section-desc">Discover what fruits are in season each month and plan your harvesting activities.</p>
        
        <!-- Month and Region Selection -->
        <div class="calendar-filter">
            <form action="" method="GET">
                <div class="filter-group">
                    <label for="month">Select Month:</label>
                    <select name="month" id="month" onchange="this.form.submit()">
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?php echo $num; ?>" <?php echo ($month == $num) ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="region">Filter by Region:</label>
                    <select name="region" id="region" onchange="this.form.submit()">
                        <option value="">All Regions</option>
                        <?php foreach ($regions as $r): ?>
                            <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ($region == $r) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($r); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <!-- Calendar Display -->
        <div class="calendar-display">
            <h3><?php echo $months[$month]; ?> Harvests</h3>
            
            <?php if (empty($harvests)): ?>
                <div class="no-harvests">
                    <p>No fruits are typically harvested during this month in the selected region.</p>
                    <p>Try selecting a different month or region.</p>
                </div>
            <?php else: ?>
                <div class="harvest-grid">
                    <?php foreach ($harvests as $harvest): ?>
                        <div class="harvest-card">                            <div class="harvest-image">
                                <?php if (!empty($harvest['image'])): 
                                    // Check if the path already includes the base URL
                                    if (strpos($harvest['image'], 'http') === 0) {
                                        $image_url = $harvest['image'];
                                    } else {
                                        // Check if image exists in direct path
                                        if (file_exists(__DIR__ . '/../' . $harvest['image'])) {
                                            $image_url = SITE_URL . '/' . $harvest['image'];
                                        } else {
                                            // Try alternative paths
                                            if (file_exists(__DIR__ . '/../img/' . basename($harvest['image']))) {
                                                $image_url = SITE_URL . '/img/' . basename($harvest['image']);
                                            } else {
                                                $image_url = SITE_URL . '/images/fruits/' . basename($harvest['image']);
                                            }
                                        }
                                    }
                                ?>
                                    <img src="<?php echo $image_url; ?>" alt="<?php echo htmlspecialchars($harvest['fruit_name']); ?>" onerror="this.src='<?php echo SITE_URL; ?>/img/placeholder.jpg'">
                                <?php else: ?>
                                    <img src="<?php echo SITE_URL; ?>/img/placeholder.jpg" alt="<?php echo htmlspecialchars($harvest['fruit_name']); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="harvest-info">
                                <h4><?php echo htmlspecialchars($harvest['fruit_name']); ?></h4>
                                
                                <div class="harvest-period">
                                    <span><i class="far fa-calendar-alt"></i> Harvest Period:</span>
                                    <p><?php echo date('M d', strtotime($harvest['start_date'])); ?> - <?php echo date('M d', strtotime($harvest['end_date'])); ?></p>
                                </div>
                                
                                <?php if (!empty($harvest['region'])): ?>
                                    <div class="harvest-region">
                                        <span><i class="fas fa-map-marker-alt"></i> Region:</span>
                                        <p><?php echo htmlspecialchars($harvest['region']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($harvest['notes'])): ?>
                                    <div class="harvest-notes">
                                        <span><i class="far fa-sticky-note"></i> Notes:</span>
                                        <p><?php echo htmlspecialchars($harvest['notes']); ?></p>
                                    </div>
                                <?php endif; ?>
                                
                                <a href="fruit_details.php?id=<?php echo $harvest['fruit_id']; ?>" class="btn view-fruit-btn">View Fruit Details</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Harvesting Tips -->
        <div class="harvesting-tips-calendar">
            <h3>Harvesting Tips for <?php echo $months[$month]; ?></h3>
            
            <div class="tips-content">
                <?php
                // Generate some generic harvesting tips based on the month
                $tips = [];
                switch ($month) {
                    case 12:
                    case 1:
                    case 2:
                        // Winter
                        $tips = [
                            "Store harvested winter fruits in a cool, dry place.",
                            "Protect remaining fruits from frost with covers.",
                            "Check stored fruits regularly for signs of spoilage."
                        ];
                        break;
                    case 3:
                    case 4:
                    case 5:
                        // Spring
                        $tips = [
                            "Begin harvesting early-season fruits as they ripen.",
                            "Watch for pests that emerge in spring.",
                            "Harvest in the cool morning hours for best flavor and longevity."
                        ];
                        break;
                    case 6:
                    case 7:
                    case 8:
                        // Summer
                        $tips = [
                            "Harvest frequently as summer fruits ripen quickly.",
                            "Handle soft fruits gently to prevent bruising.",
                            "Use harvested fruits promptly or preserve them by freezing or canning."
                        ];
                        break;
                    case 9:
                    case 10:
                    case 11:
                        // Fall
                        $tips = [
                            "Harvest tree fruits before the first frost.",
                            "Store fall fruits in a cool, dry place for longer shelf life.",
                            "Check for ripeness by gently twisting the fruit - ripe ones will detach easily."
                        ];
                        break;
                }
                ?>
                
                <ul class="tips-list">
                    <?php foreach ($tips as $tip): ?>
                        <li><?php echo $tip; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<style>
    .calendar-section {
        padding: 60px 0;
    }
    
    .section-desc {
        text-align: center;
        margin-bottom: 40px;
        color: #666;
        font-size: 1.1rem;
    }
    
    /* Calendar Filter */
    .calendar-filter {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-bottom: 40px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 200px;
    }
    
    .filter-group label {
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .filter-group select {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    
    /* Calendar Display */
    .calendar-display {
        margin-bottom: 50px;
    }
    
    .calendar-display h3 {
        text-align: center;
        margin-bottom: 30px;
        color: #2E7D32;
        font-size: 1.8rem;
    }
    
    .no-harvests {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .harvest-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 30px;
    }
    
    .harvest-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }
    
    .harvest-image {
        height: 200px;
    }
    
    .harvest-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .harvest-info {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .harvest-info h4 {
        margin-bottom: 15px;
        color: #2E7D32;
        font-size: 1.4rem;
    }
    
    .harvest-period, .harvest-region, .harvest-notes {
        margin-bottom: 15px;
    }
    
    .harvest-period span, .harvest-region span, .harvest-notes span {
        font-weight: 500;
        color: #333;
        display: block;
        margin-bottom: 5px;
    }
    
    .harvest-period i, .harvest-region i, .harvest-notes i {
        margin-right: 5px;
        color: #4CAF50;
    }
    
    .harvest-period p, .harvest-region p, .harvest-notes p {
        color: #666;
        margin: 0;
    }
    
    .view-fruit-btn {
        margin-top: auto;
        text-align: center;
    }
    
    /* Harvesting Tips */
    .harvesting-tips-calendar {
        background: #f1f8e9;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .harvesting-tips-calendar h3 {
        text-align: center;
        margin-bottom: 20px;
        color: #2E7D32;
    }
    
    .tips-list {
        list-style-type: none;
        padding: 0;
    }
    
    .tips-list li {
        position: relative;
        padding: 10px 0 10px 30px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .tips-list li:last-child {
        border-bottom: none;
    }
    
    .tips-list li:before {
        content: '\f058';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        left: 0;
        color: #4CAF50;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .calendar-filter {
            flex-direction: column;
            gap: 15px;
        }
        
        .filter-group {
            width: 100%;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 