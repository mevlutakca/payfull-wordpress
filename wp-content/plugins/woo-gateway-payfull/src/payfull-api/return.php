<?php


?>

<h1>Hi, I am merchant web server....</h1>

<?php
    $color = isset($_POST['status']) ? null : '#fff';
    if(!$color) {
        $color = $_POST['status']=='ok' ? '#DCFFC3' : '#FFC3C3';
    }
?>
<div style="padding:20px;margin:20px; border:2px solid #555;min-height:30px;background:<?php echo $color;?>">
    <div style="padding:20px;margin:20px; border:2px solid #555;background:#FFF5BB">
        <h3>The status of payment is set randomlly</h3>
        <p>
            So, sometimes you will see <span style="color:green">green</span> screen
            and you will see <span style="color:red">red</span> screen in other times
        </p>
    </div>
    <pre>
        <?php print_r($_POST); ?>
    </pre>
</div>
<script>
    // if (window!=top) {
    //     top.location.replace("http://google.com");
    // }
</script>
