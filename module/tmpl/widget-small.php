<?php
/**
 * AAJ Module - Template widget-small (compact, ideal for sidebar)
 *
 * @var array   $items
 * @var \Aaj\Component\Aaj\Administrator\Helper\RenderHelper $renderer
 * @var \Joomla\Registry\Registry $params
 */

defined('_JEXEC') or die;

use Joomla\CMS\Uri\Uri;

if (empty($items)) {
    return;
}

$doc = \Joomla\CMS\Factory::getApplication()->getDocument();
$doc->addStyleSheet(Uri::root() . 'media/com_aaj/css/main.css');
?>

<div class="aaj aaj--widget-small">
    <?php foreach ($items as $i => $product) :
        $renderer->item      = $product;
        $renderer->itemIndex = $i;
    ?>
    <div class="<?php echo $renderer->getProductContainerClasses('aaj-product aaj-product--widget-small'); ?>" <?php echo $renderer->getProductContainerData(); ?>>

        <div class="aaj-product__thumb">
            <a href="<?php echo $renderer->getProductUrl(); ?>" target="_blank" rel="nofollow noopener sponsored">
                <img class="aaj-product__image"
                     src="<?php echo $renderer->getProductImage('small'); ?>"
                     alt="<?php echo $renderer->getProductImageAlt(); ?>" />
            </a>
        </div>

        <div class="aaj-product__content">
            <a class="aaj-product__title" href="<?php echo $renderer->getProductUrl(); ?>" target="_blank" rel="nofollow noopener sponsored">
                <?php echo $renderer->getProductTitle(60); ?>
            </a>
            <?php if ($renderer->showAdvertisedPrice()) : ?>
            <div class="aaj-product__pricing">
                <span class="aaj-product__price aaj-product__price--current"><?php echo $renderer->getProductPricing(); ?></span>
                <?php if ($renderer->productIsOnSale()) : ?>
                <span class="aaj-product__price aaj-product__price--saved"><?php echo $renderer->getSavedText(); ?></span>
                <?php endif; ?>
                <?php echo $renderer->getProductCheckPrime(); ?>
            </div>
            <?php endif; ?>
        </div>

    </div>
    <?php endforeach; ?>
</div>
