<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
?>

<?php /*build installment tables as json object*/ ?>
<script type="text/javascript">
    window.payfull.taksitTable = [];
    <?php foreach($banks as $bank) : ?>
    window.payfull.taksitTable.push({
        name: "<?php echo $bank['Bank']; ?>",
        gateway: "<?php echo $bank['Gateway']; ?>",
        has3d: "<?php echo $bank['Has3D']; ?>",
        installments: [
            <?php $first = true; ?>
            <?php foreach($banks['Items'] as $item) : ?>
                <?php $first ? '' : ','; ?>
                {count: <?php echo $item['Count']; ?>, fee: "<?php echo $item['Commission']; ?>"}
                <?php $first = false; ?>
            <?php endforeach; ?>
        ]
    });
    <?php endforeach; ?>
</script>