(function(){

    if(!jQuery){
        console.error('wp page rank plugin scripts cant be loaded without jquery');
        return;
    }
    jQuery(document).ready(function($){

        //add url script
        $('#wp-page-rank-add-url').off('click').on('click',function(){
            var table_row = '<tr valign="top">'+
            '<td><input required pattern="^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?" type="url" name="wp_page_rank_urls_name[]" value="" /></td>'+
            '<td><button type="button" id="wp-page-rank-delete-url">Delete Url</button></td>'+
            '</tr>';
            $('#wp-page-rank-url-table').append(table_row);
        });

        //delete url script
        $(document).off('click','#wp-page-rank-delete-url').on('click', '#wp-page-rank-delete-url', function(){
             $(this).closest('tr').remove();
        });

    });

}());
