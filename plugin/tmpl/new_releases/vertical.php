<?php
/**
 * AAJ - Template produit vertical (box)
 *
 * @var \Aaj\Component\Aaj\Administrator\Helper\RenderHelper $r
 */

defined('_JEXEC') or die;
?>
<div class="<?php echo $r->getProductContainerClasses('aaj-product aaj-product--vertical'); ?>" <?php echo $r->getProductContainerData(); ?>>

    <?php echo $r->getProductRibbons(); ?>

    <div class="aaj-product__thumb">
        <a class="aaj-product__image-link"
           href="<?php echo $r->getProductUrl(); ?>"
           title="<?php echo $r->getProductLinkTitle(); ?>"
           rel="nofollow noopener sponsored"
           target="_blank">
            <img class="aaj-product__image"
                 src="<?php echo $r->getProductImage('large'); ?>"
                 alt="<?php echo $r->getProductImageAlt(); ?>" />
        </a>
    </div>

    <div class="aaj-product__content">
        <a class="aaj-product__title"
           href="<?php echo $r->getProductUrl(); ?>"
           title="<?php echo $r->getProductLinkTitle(); ?>"
           rel="nofollow noopener sponsored"
           target="_blank">
            <?php echo $r->getProductTitle(); ?>
        </a>

        <?php if ($r->getProductRating()) : ?>
        <div class="aaj-product__rating">
            <?php echo $r->getProductStarRating(); ?>
            <?php if ($r->getProductReviews()) : ?>
            <span class="aaj-product__reviews">(<?php echo $r->getProductReviews(); ?> avis)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="aaj-product__description">
            <?php echo $r->getProductDescription(); ?>
        </div>
    </div>

    <div class="aaj-product__footer">
        <div class="aaj-product__pricing">
            <?php if ($r->productIsOnSale()) : ?>
                <span class="aaj-product__price aaj-product__price--old"><?php echo $r->getProductPricing('old'); ?></span>
                <span class="aaj-product__price aaj-product__price--saved"><?php echo $r->getSavedText(); ?></span>
            <?php endif; ?>

            <?php if ($r->showAdvertisedPrice()) : ?>
                <span class="aaj-product__price aaj-product__price--current"><?php echo $r->getProductPricing(); ?></span>
            <?php endif; ?>

            <?php echo $r->getProductCheckPrime(); ?>
        </div>

        <?php echo $r->getButton('detail'); ?>
        <?php echo $r->getButton('buy'); ?>
    </div>

</div>
