<?php
/**
 * AAJ Module - Template horizontal
 *
 * @var array   $items    AajProduct[]
 * @var \Aaj\Component\Aaj\Administrator\Helper\RenderHelper $renderer
 * @var \Joomla\Registry\Registry $params
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

if (empty($items)) {
    return;
}

// Enqueue assets
$doc = \Joomla\CMS\Factory::getApplication()->getDocument();
$doc->addStyleSheet(Uri::root() . 'media/com_aaj/css/main.css');
?>

<div class="aaj aaj--module">
    <?php foreach ($items as $i => $product) :
        $renderer->item      = $product;
        $renderer->itemIndex = $i;
    ?>
    <div class="<?php echo $renderer->getProductContainerClasses('aaj-product aaj-product--horizontal'); ?>" <?php echo $renderer->getProductContainerData(); ?>>

        <?php echo $renderer->getProductRibbons(); ?>

        <div class="aaj-product__thumb">
            <a href="<?php echo $renderer->getProductUrl(); ?>" target="_blank" rel="nofollow noopener sponsored">
                <img class="aaj-product__image"
                     src="<?php echo $renderer->getProductImage('medium'); ?>"
                     alt="<?php echo $renderer->getProductImageAlt(); ?>" />
            </a>
            <?php if ($renderer->getProductRating()) : ?>
            <div class="aaj-product__rating">
                <?php echo $renderer->getProductStarRating(); ?>
                <?php if ($renderer->getProductReviews()) : ?>
                <div class="aaj-product__reviews"><?php echo $renderer->getProductReviews(); ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="aaj-product__content">
            <a class="aaj-product__title" href="<?php echo $renderer->getProductUrl(); ?>" target="_blank" rel="nofollow noopener sponsored">
                <?php echo $renderer->getProductTitle(); ?>
            </a>
            <div class="aaj-product__description">
                <?php echo $renderer->getProductDescription(); ?>
            </div>
        </div>

        <div class="aaj-product__footer">
            <div class="aaj-product__pricing">
                <?php if ($renderer->productIsOnSale()) : ?>
                    <span class="aaj-product__price aaj-product__price--old"><?php echo $renderer->getProductPricing('old'); ?></span>
                    <span class="aaj-product__price aaj-product__price--saved"><?php echo $renderer->getSavedText(); ?></span>
                <?php endif; ?>
                <?php if ($renderer->showAdvertisedPrice()) : ?>
                    <span class="aaj-product__price aaj-product__price--current"><?php echo $renderer->getProductPricing(); ?></span>
                <?php endif; ?>
                <?php echo $renderer->getProductCheckPrime(); ?>
            </div>
            <?php echo $renderer->getButton('detail'); ?>
        </div>

    </div>
    <?php endforeach; ?>
</div>
