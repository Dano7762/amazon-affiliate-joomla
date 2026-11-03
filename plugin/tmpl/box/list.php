<?php
/**
 * AAJ - Template liste compact (box)
 *
 * @var \Aaj\Component\Aaj\Administrator\Helper\RenderHelper $r
 */

defined('_JEXEC') or die;
?>
<div class="<?php echo $r->getProductContainerClasses('aaj-product aaj-product--list'); ?>" <?php echo $r->getProductContainerData(); ?>>

    <div class="aaj-product__thumb">
        <a class="aaj-product__image-link"
           href="<?php echo $r->getProductUrl(); ?>"
           rel="nofollow noopener sponsored"
           target="_blank">
            <img class="aaj-product__image"
                 src="<?php echo $r->getProductImage('small'); ?>"
                 alt="<?php echo $r->getProductImageAlt(); ?>" />
        </a>
    </div>

    <div class="aaj-product__content">
        <a class="aaj-product__title"
           href="<?php echo $r->getProductUrl(); ?>"
           rel="nofollow noopener sponsored"
           target="_blank">
            <?php echo $r->getProductTitle(); ?>
        </a>

        <div class="aaj-product__pricing aaj-product__pricing--inline">
            <?php if ($r->showAdvertisedPrice()) : ?>
                <span class="aaj-product__price aaj-product__price--current"><?php echo $r->getProductPricing(); ?></span>
            <?php endif; ?>
            <?php if ($r->productIsOnSale()) : ?>
                <span class="aaj-product__price aaj-product__price--saved"><?php echo $r->getSavedText(); ?></span>
            <?php endif; ?>
            <?php echo $r->getProductCheckPrime(); ?>
        </div>
    </div>

    <div class="aaj-product__actions">
        <?php echo $r->getButton('detail'); ?>
    </div>

</div>
