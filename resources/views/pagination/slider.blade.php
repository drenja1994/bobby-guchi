<?php
$presenter = new Illuminate\Pagination\BootstrapPresenter($paginator);
?>

<?php if ($paginator->getLastPage() > 1): ?>
    <div class="pagination">
        <ul>
            <?php echo getPrevious($paginator->getCurrentPage(), $paginator->getUrl( $paginator->getCurrentPage()-1 ) ) ?>
            <?php echo $presenter->getPageRange(1, $paginator->getLastPage() ); ?>
            <?php echo getNext($paginator->getCurrentPage(), $paginator->getLastPage(), $paginator->getUrl( $paginator->getCurrentPage()+1 ) )  ?>
        </ul>
    </div>
<?php endif; ?>

<?php
function getPrevious($currentPage, $url)
{
    if ($currentPage <= 1)
        return '<li class="previous disabled"><span class="icon-chevron-left"></span></li>';
    else
       return '<li class="previous"><a class="icon-chevron-left" href="'.$url.'"></a></li>';
}

function getNext($currentPage, $lastPage, $url)
{
    if ($currentPage >= $lastPage)
        return '<li class="next disabled"><span class="icon-chevron-right"></span></li>';
    else
        return '<li class="next"><a class="icon-chevron-right" href="'.$url.'"></a></li>';
}
?>