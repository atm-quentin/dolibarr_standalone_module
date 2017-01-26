/* 
 * appThirdParty.js contain all function has been in app.js which refer to ThirdParty
 */

/*
 * method which create the list of Third.
 * add a badge which permitted to differentiate type of thirdParty
 */
function refreshThirdpartyList(TItem)
{
    var x = 0;
    $('#thirdparty-list ul').empty();
    for (var i in TItem)
    {
        var $li = $('<li class="list-group-item"><a data-toggle="tab" href="#thirdparty-card" onclick="javascript:showItem(\'thirdparty\', ' + TItem[i].id + ', showThirdparty)">' + TItem[i].name + '</a></li>');
        if (TItem[i].client == 1)
            $li.append('<span class="badge client">C</span>');
        if (TItem[i].fournisseur == 1)
            $li.append('<span class="badge fournisseur">F</span>');
        $('#thirdparty-list ul').append($li);

        if (x > 20)
            return;
        else
            x++;
    }

    addEventListenerOnItemLink();
}

/*
 * call to function setItemInHTML in app.js
 * call to differente function to refresh the document list associed to this thirdParty
 */
function showThirdparty(item)
{
    setItemInHTML($('#thirdparty-card'), item);
    refreshAssociateProposalList($('#thirdparty-card .doc_associate_proposals'), item.TProposal);
    refreshAssociateOrderList($('#thirdparty-card .doc_associate_orders'), item.TOrder);
    refreshAssociateBillList($('#thirdparty-card .doc_associate_bills'), item.TBill);

    addEventListenerOnItemLink();
    $('a#last-thirdparty').html(item.name).closest('li').removeClass('hidden');
}

function editThirdparty(item)
{
    var $container = $('#thirdparty-card-edit');
    $container.children('input[name=id]').val(item.id_dolibarr);

    for (var x in item)
    {
        $container.find('[name=' + x + ']').val(item[x]);
    }
}