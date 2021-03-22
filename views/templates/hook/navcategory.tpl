{$data|@var_dump}
{block name="in_wrapper_top"}
    {if isset($data)}
        {if $type == 0}
            <div id="category-seo" class="container">

            {assign var='currentCat' value=Category::getInstance($category.id)}
            {assign var='parentsCat' value=$currentCat->getParentsCategories($language.id)}
            {* Récupération du type de média de la catégorie courante *}
            {assign var='maillageArray' value=[]}{assign var='mediaTypeCat' value=$parentsCat[$parentsCat|@count - 2]}
            {assign var='mediaTypeCatFull' value=Category::getInstance($mediaTypeCat.id_category)}

            {* Affichage du maillage *}
            {if $data|@count > 1 || $data[0]['id_category']!= '2'}
                <h2 class="maillage_header" id="maillage_{$mediaTypeCat.id_category}">
                    {$mediaTypeCat.name}
                </h2>
                <div id="accordion">
                {foreach from=$title item=label key=level}
                    {foreach from=$main item=state key=exist}
                        {if $level == $exist}
                            <div class="card">
                                <div class="card-header" id="heading{$level}">
                                    <h5 class="mb-0">
                                        <button class="btn btn-link" data-toggle="collapse" data-target="#collapse{$level}" aria-expanded="true" aria-controls="collapse{$level}">
                                            {$label}
                                        </button>
                                    </h5>
                                </div>
                                <div id="collapse{$level}" class="collapse show" aria-labelledby="heading{$level}" data-parent="#accordion" >
                                    {foreach from=$data item=categorie key=key}
                                        {if $categorie.level_depth == $level}
                                            <a class="card-body" href="{$link->getCategoryLink({$categorie.id_category})}">
                                                {$categorie.name}
                                            </a>
                                        {/if}
                                    {/foreach}
                                </div>
                            </div>
                        {/if}
                    {/foreach}
                {/foreach}
            {/if}
            </div>
        {elseif $type == 1}
            {if $data|@count > 1}
                {foreach from=$data item=list key=key}
                    <div class="card">
                        <div class="card-header" id="heading{$key}">
                            <h5 class="mb-0">
                                <button class="btn btn-link" data-toggle="collapse" data-target="#collapse{$key}" aria-expanded="true" aria-controls="collapse{$key}">
                                    {$title[$key]}
                                </button>
                            </h5>
                        </div>
                        <div id="collapse{$key}" class="collapse show" aria-labelledby="heading{$key}" data-parent="#accordion" >
                            {foreach from=$list item=category key=id}
                                <a class="card-body" href="{$link->getCategoryLink({$category.id_category})}">
                                    {$category.name}
                                </a>
                            {/foreach}
                        </div>
                    </div>
                {/foreach}
            {/if}
        {/if}
    {/if}

{literal}
    <script>
var translation = {
"maillage_834": "{/literal}{l s="maillage_834"}{literal}",
"maillage_834_1": "{/literal}{l s="maillage_834_1"}{literal}",
"maillage_834_2": "{/literal}{l s="maillage_834_2"}{literal}",
"maillage_834_3": "{/literal}{l s="maillage_834_3"}{literal}",
"maillage_834_4": "{/literal}{l s="maillage_834_4"}{literal}",
"maillage_834_5": "{/literal}{l s="maillage_834_5"}{literal}",
"maillage_837": "{/literal}{l s="maillage_837"}{literal}",
"maillage_837_1": "{/literal}{l s="maillage_837_1"}{literal}",
"maillage_837_2": "{/literal}{l s="maillage_837_2"}{literal}",
"maillage_837_3": "{/literal}{l s="maillage_837_3"}{literal}",
"maillage_837_4": "{/literal}{l s="maillage_837_4"}{literal}",
"maillage_837_5": "{/literal}{l s="maillage_837_5"}{literal}",
"maillage_95": "{/literal}{l s="maillage_95"}{literal}",
"maillage_95_1": "{/literal}{l s="maillage_95_1"}{literal}",
"maillage_95_2": "{/literal}{l s="maillage_95_2"}{literal}",
"maillage_94": "{/literal}{l s="maillage_94"}{literal}",
"maillage_94_1": "{/literal}{l s="maillage_94_1"}{literal}",
"maillage_94_2": "{/literal}{l s="maillage_94_2"}{literal}",
"maillage_94_3": "{/literal}{l s="maillage_94_3"}{literal}",
"maillage_46": "{/literal}{l s="maillage_46"}{literal}",
"maillage_46_1": "{/literal}{l s="maillage_46_1"}{literal}",
"maillage_46_2": "{/literal}{l s="maillage_46_2"}{literal}",
}
    </script>
{/literal}
{/block}