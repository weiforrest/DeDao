<?php
include ("getDedao.php");

$run = new GetDedao();
$page = $_GET['page'];
$selfName = $_SERVER["SCRIPT_NAME"];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <title>得到音频</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="weiforrest">

    <!-- Site CSS -->
    <link href="http://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">

    <!-- Favicons -->
    <link rel="shortcut icon" href="http://static.bootcss.com/www/assets/ico/favicon.png">
</head>
<body>
    <div class="container">
        <?php
        $errors = $run->GetError()->fetchAll();
        if(!empty($errors)) : ?>
        <div class="alert alert-danger">
            <?php foreach($errors as $row) : ?>
            <p>.$row['time'].'  '. $row['number'].'  '.$row['notice'].</p>;
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <table class="table table-hover">
            <thead>
                <tr><th>#</th><th>标题</th><th>时长</th><th>下载</th></tr>
            </thead>
            <tbody>
            <?php foreach($run->GetEntries($page) as $row) : ?>
                    <tr <?= $row["is_book"] ? 'class="info"':''?>><th><?=$row['number']?></th>
                    <th><a href="<?=GetDedao::getUrl.$row['number']?>"><?=$row['title']?></a></th>
                    <th><?=$row['time']?></th>
                    <th><a href="<?=$row['audio']?>"><span class="glyphicon glyphicon-save"></span></a></th>
                    </tr>
            <?php endforeach; ?>
            </tbody>
            </table>
            <ul class="pagination">
            <?php
                $pageRanger = $run->GetPageRanger();
                if(!$page && $page > $pageRanger) {
                    $page = 1;
                }

                $begin = $page - GetDedao::pageSize/2;
                if($begin < 1) {
                    $end = $page + abs($begin) + GetDedao::pageSize/2;
                    $begin = 1;
                } else {
                    $end = $page + GetDedao::pageSize/2;
                    echo '<li><a href="'.$selfName.'?page=1">
                        <span class="glyphicon glyphicon glyphicon-fast-backward"></span></a></li>';
                    echo '<li><span>...</span></li>';
                }
                $endFlag = true;
                if($end > $pageRanger) {
                    $end = $pageRanger;
                    $endFlag = false;
                }

                for($i=$begin; $i <= $end; $i++) {
                    if($i == $page) {
                        echo '<li class="active">';
                    }else {
                        echo '<li>';
                    }
                    print '<a href="'.$selfName.'?page='.$i.'">'.$i.'</a></li>';
                }
                if($endFlag) {
                    echo '<li><span>...</span></li>';
                    echo '<li><a href="'.$selfName.'?page='.$pageRanger.'">
                        <span class="glyphicon glyphicon glyphicon-fast-forward"></span></a></li>';
                }
            ?>
            </ul>
    </div>
</body>
</html>
