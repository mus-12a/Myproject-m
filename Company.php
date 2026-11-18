<?php

session_start();

require_once 'config/db.php';

// البحث عن الشركات
$search = trim($_GET['search'] ?? '');

$page = max(1, (int)($_GET['page'] ?? 1));

$limit = 9;
$offset = ($page - 1) * $limit;

// شروط البحث
$where = "";
$params = [];

if ($search !== '') {

    $where = "WHERE c.company_name LIKE ?
              OR c.description LIKE ?
              OR c.address LIKE ?";

    $params = [
        "%$search%",
        "%$search%",
        "%$search%"
    ];
}

// حساب عدد الشركات
$countSQL = "SELECT COUNT(*)
             FROM companies c
             $where";

$countStmt = $conn->prepare($countSQL);
$countStmt->execute($params);

$totalCompanies = (int)$countStmt->fetchColumn();

$totalPages = max(1, ceil($totalCompanies / $limit));

// جلب الشركات وعدد الوظائف لكل شركة
$sql = "SELECT
            c.company_id,
            c.company_name,
            c.description,
            c.address,
            c.logo,
            c.website,

            (
                SELECT COUNT(*)
                FROM jobs j
                WHERE j.company_id = c.company_id
                AND j.status = 'active'
                AND (j.deadline IS NULL OR j.deadline >= CURDATE())
            ) AS jobs_count

        FROM companies c

        $where

        ORDER BY c.company_id DESC

        LIMIT $limit OFFSET $offset";

$stmt = $conn->prepare($sql);
$stmt->execute($params);

$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// حماية عرض البيانات
function e($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>الشركات | منصة فرصتي</title>

<style>

/* الإعدادات العامة */

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary: #087f5b;
    --dark: #064e3b;
    --light: #e5f5ed;
    --background: #f5f8f6;
}

body {
    font-family: Tahoma, Arial, sans-serif;
    background: var(--background);
    color: #333;
    line-height: 1.7;
}

/* القائمة الرئيسية */

header {
    background: white;
    padding: 15px 7%;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    font-size: 28px;
    color: var(--primary);
    font-weight: bold;
}

.logo span {
    display: block;
    font-size: 12px;
    color: #777;
    font-weight: normal;
}

nav {
    display: flex;
    gap: 22px;
}

nav a {
    text-decoration: none;
    color: #444;
    font-weight: bold;
    font-size: 14px;
}

nav a:hover,
nav a.active {
    color: var(--primary);
}

.nav-buttons {
    display: flex;
    gap: 10px;
}

.nav-buttons a {
    text-decoration: none;
    padding: 9px 15px;
    border-radius: 6px;
    font-size: 13px;
}

.login {
    background: var(--dark);
    color: white;
}

.register {
    border: 1px solid var(--primary);
    color: var(--primary);
}

.menu-btn {
    display: none;
    border: none;
    background: none;
    font-size: 27px;
    cursor: pointer;
}

/* رأس الصفحة */

.hero {
    background: linear-gradient(
        135deg,
        #064e3b,
        #0d9488
    );

    color: white;
    text-align: center;
    padding: 65px 20px;
}

.hero h1 {
    font-size: 36px;
    margin-bottom: 12px;
}

.hero p {
    font-size: 16px;
}

/* البحث */

.search-section {
    max-width: 950px;
    margin: -30px auto 45px;
    padding: 0 20px;
    position: relative;
}

.search-box {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.08);

    display: flex;
    gap: 12px;
}

.search-box input {
    flex: 1;
    padding: 14px;
    border: 1px solid #ddd;
    border-radius: 7px;
    outline: none;
    font-size: 14px;
}

.search-box input:focus {
    border-color: var(--primary);
}

.search-box button {
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 7px;
    padding: 12px 25px;
    cursor: pointer;
    font-weight: bold;
}

/* المحتوى */

.container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
}

.section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.section-title h2 {
    color: var(--dark);
    font-size: 25px;
}

.count {
    background: var(--light);
    color: var(--primary);
    padding: 7px 15px;
    border-radius: 20px;
    font-size: 13px;
}

/* بطاقات الشركات */

.companies-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 23px;
}

.company-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    border: 1px solid #eee;
    text-align: center;
    transition: 0.3s;
}

.company-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: var(--primary);
}

/* شعار الشركة */

.company-logo {
    width: 85px;
    height: 85px;
    margin: 0 auto 18px;
    border-radius: 50%;
    background: var(--light);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.company-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.logo-placeholder {
    font-size: 35px;
    color: var(--primary);
}

/* بيانات الشركة */

.company-card h3 {
    color: var(--dark);
    font-size: 20px;
    margin-bottom: 10px;
}

.company-description {
    color: #777;
    font-size: 13px;
    min-height: 65px;
    margin-bottom: 15px;
}

.company-info {
    border-top: 1px solid #eee;
    padding-top: 15px;
    text-align: right;
}

.company-info p {
    font-size: 13px;
    color: #666;
    margin-bottom: 9px;
}

.jobs-badge {
    display: inline-block;
    background: var(--light);
    color: var(--primary);
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    margin-top: 12px;
}

/* الأزرار */

.company-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.company-buttons a {
    flex: 1;
    padding: 10px 5px;
    border-radius: 7px;
    text-decoration: none;
    font-size: 12px;
    font-weight: bold;
}

.jobs-btn {
    background: var(--primary);
    color: white;
}

.website-btn {
    border: 1px solid var(--primary);
    color: var(--primary);
}

/* لا توجد شركات */

.empty {
    background: white;
    padding: 60px 20px;
    text-align: center;
    border-radius: 12px;
    grid-column: 1 / -1;
}

.empty h3 {
    color: var(--dark);
}

/* ترقيم الصفحات */

.pagination {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin: 40px 0;
}

.pagination a {
    text-decoration: none;
    background: white;
    color: var(--primary);
    border: 1px solid #ddd;
    padding: 9px 15px;
    border-radius: 6px;
}

.pagination a.current {
    background: var(--primary);
    color: white;
}

/* الفوتر */

footer {
    background: #073b30;
    color: white;
    text-align: center;
    padding: 35px 20px;
    margin-top: 50px;
}

/* التجاوب مع الهاتف */

@media(max-width: 950px) {

    .companies-grid {
        grid-template-columns: repeat(2, 1fr);
    }

}

@media(max-width: 650px) {

    .navbar {
        flex-wrap: wrap;
    }

    .menu-btn {
        display: block;
    }

    nav {
        display: none;
        flex-direction: column;
        width: 100%;
        padding: 20px;
    }

    nav.show {
        display: flex;
    }

    .nav-buttons {
        margin-top: 15px;
    }

    .companies-grid {
        grid-template-columns: 1fr;
    }

    .search-box {
        flex-direction: column;
    }

    .hero h1 {
        font-size: 27px;
    }

}

</style>

</head>

<body>

<!-- القائمة الرئيسية -->

<header>

<div class="navbar">

<div class="logo">

فرصتي

<span>منصة التوظيف السودانية</span>

</div>

<button class="menu-btn" onclick="toggleMenu()">
☰
</button>

<nav id="navbar">

<a href="index.php">الرئيسية</a>

<a href="jobs.php">الوظائف</a>

<a href="companies.php" class="active">الشركات</a>

<a href="index.php#steps">كيف تعمل المنصة؟</a>

<a href="index.php#about">من نحن</a>

<a href="index.php#contact">اتصل بنا</a>

</nav>

<div class="nav-buttons">

<?php if (!empty($_SESSION['user_id'])): ?>

<a href="profile.php" class="login">حسابي</a>

<?php else: ?>

<a href="login.php" class="login">
تسجيل الدخول
</a>

<a href="register.php" class="register">
إنشاء حساب
</a>

<?php endif; ?>

</div>

</div>

</header>


<!-- رأس الصفحة -->

<section class="hero">

<h1>الشركات والمؤسسات</h1>

<p>
اكتشف الشركات السودانية وتعرف على فرص العمل المتاحة لديها
</p>

</section>


<!-- البحث عن الشركات -->

<section class="search-section">

<form method="GET" action="companies.php"
class="search-box">

<input
type="search"
name="search"
placeholder="ابحث عن اسم الشركة أو نشاطها أو عنوانها..."
value="<?= e($search) ?>"
>

<button type="submit">
بحث عن شركة
</button>

</form>

</section>


<!-- عرض الشركات -->

<main class="container">

<div class="section-title">

<h2>الشركات المسجلة في فرصتي</h2>

<span class="count">

عدد الشركات: <?= $totalCompanies ?>

</span>

</div>


<div class="companies-grid">

<?php if (count($companies) > 0): ?>

<?php foreach ($companies as $company): ?>

<article class="company-card">

<!-- شعار الشركة -->

<div class="company-logo">

<?php if (!empty($company['logo'])): ?>

<img
src="<?= e($company['logo']) ?>"
alt="شعار الشركة"
onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
>

<span class="logo-placeholder"
style="display:none">🏢</span>

<?php else: ?>

<span class="logo-placeholder">🏢</span>

<?php endif; ?>

</div>


<!-- اسم الشركة -->

<h3>
<?= e($company['company_name']) ?>
</h3>


<!-- وصف الشركة -->

<p class="company-description">

<?= e(mb_strimwidth(
    $company['description'] ?? 'لا يوجد وصف متاح للشركة',
    0,
    120,
    '...'
)) ?>

</p>


<!-- بيانات الشركة -->

<div class="company-info">

<p>
📍 العنوان:
<?= e($company['address'] ?: 'غير محدد') ?>
</p>

<p>
🌐 الموقع الإلكتروني:
<?= !empty($company['website'])
    ? 'متاح'
    : 'غير متوفر' ?>
</p>

</div>


<!-- عدد الوظائف -->

<span class="jobs-badge">

💼 <?= (int)$company['jobs_count'] ?>
وظيفة متاحة

</span>


<!-- الأزرار -->

<div class="company-buttons">

<a
href="jobs.php?company_id=<?= (int)$company['company_id'] ?>"
class="jobs-btn">

عرض الوظائف

</a>


<?php

$website = trim($company['website'] ?? '');

if ($website !== '') {

    if (!preg_match('~^https?://~i', $website)) {
        $website = 'https://' . $website;
    }

    $host = parse_url($website, PHP_URL_HOST);

    $validWebsite = $host &&
        filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

} else {
    $validWebsite = false;
}

?>

<?php if ($validWebsite): ?>

<a
href="<?= e($website) ?>"
target="_blank"
rel="noopener noreferrer"
class="website-btn">

زيارة الموقع

</a>

<?php else: ?>

<a href="#"
class="website-btn"
onclick="alert('الموقع الإلكتروني غير متوفر'); return false;">

الموقع غير متوفر

</a>

<?php endif; ?>

</div>

</article>

<?php endforeach; ?>

<?php else: ?>

<div class="empty">

<h3>لا توجد شركات مسجلة حالياً</h3>

<p>
لم يتم العثور على شركات تطابق عملية البحث.
</p>

</div>

<?php endif; ?>

</div>


<!-- ترقيم الصفحات -->

<?php if ($totalPages > 1): ?>

<div class="pagination">

<?php for ($i = 1; $i <= $totalPages; $i++): ?>

<?php

$query = $_GET;
$query['page'] = $i;

$url = 'companies.php?' . http_build_query($query);

?>

<a
href="<?= e($url) ?>"
class="<?= $page == $i ? 'current' : '' ?>">

<?= $i ?>

</a>

<?php endfor; ?>

</div>

<?php endif; ?>

</main>


<!-- الفوتر -->

<footer>

<h3>منصة فرصتي للتوظيف</h3>

<p>
نربط الشركات بخريجي الجامعات السودانية.
</p>

<p>
جميع الحقوق محفوظة © 2026
</p>

</footer>


<script>

function toggleMenu() {

    document.getElementById('navbar')
    .classList.toggle('show');

}

</script>

</body>
</html>
