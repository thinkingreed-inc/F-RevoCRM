/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

Vtiger.Class('Vtiger_ListSidebar_Js',{
    // イベント名は assets/react-web-components/src/components/ListFavorites/events.ts とそろえる
    FAVORITES_CHANGED_EVENT : 'frevo:list-favorites-changed',
    FAVORITES_RENDERED_EVENT : 'frevo:list-favorites-rendered'
},{
    
    
    registerFilterSeach : function () {
        var self = this;
        var filters = jQuery('#module-filters');
        filters.find('.search-list').on('keyup',function(e){
            var element = jQuery(e.currentTarget);
            var val = element.val().toLowerCase();
            filters.find('.toggleFilterSize').removeClass('hide');
            jQuery('li.listViewFilter').each(function(){
                var filterEle = jQuery(this);
                var filterName = filterEle.find('a.filterName').html();
                var listsMenu = filterEle.closest('ul.lists-menu');
                if(typeof filterName != 'undefined') {
                    filterName = filterName.toLowerCase();
                    if(filterName.indexOf(val) === -1){
                        filterEle.addClass('filter-search-hide').removeClass('filter-search-show');    
                        if(listsMenu.find('li.listViewFilter').filter(':visible').length == 0) {
                            listsMenu.closest('.list-group').addClass('hide');
                        }
                        if(jQuery('#module-filters').find('ul.lists-menu li').filter(':visible').length == 0) {
                            jQuery('#module-filters').find('.noLists').removeClass('hide');
                        }
                    }else{
                        if(val) {
                            listsMenu.closest('.list-group').find('.toggleFilterSize').addClass('hide');
                        }
                        filterEle.removeClass('filter-search-hide').addClass('filter-search-show');
                        listsMenu.closest('.list-group').removeClass('hide');
                        jQuery('#module-filters').find('.noLists').addClass('hide');
                    }
                }
            });
        })
    },
    
	registerFilters: function() {
		var self = this;
        var filters = jQuery('.module-filters').not('.module-extensions');
        var scrollContainers = self.getFixedHeightScrollContainers(filters);
        // applying scroll to filters, tags & extensions
        jQuery.each(scrollContainers,function(key,scroll){
            var scroll = jQuery(scroll);
            var listcontentHeight = scroll.find(".list-menu-content").height();
            scroll.css("height",listcontentHeight);
            scroll.perfectScrollbar({});
        })
        
        this.registerFilterSeach();
        filters.on('click','.listViewFilter', function(e){
			e.preventDefault();
            var targetElement = jQuery(e.target);
            if(targetElement.is('.dropdown-toggle') || targetElement.closest('ul').hasClass('dropdown-menu') ) return;
            var element = jQuery(e.currentTarget);
            var el = jQuery('a[data-filter-id]',element);
            self.getParentInstance().resetData();
            self.unMarkAllFilters();
            self.unMarkAllTags();
            el.closest('li').add(filters.find('li.listViewFilter').has('a.filterName[data-filter-id="' + el.data('filter-id') + '"]')).addClass('active');
            self.getParentInstance().filterClick = true;
            self.getParentInstance().loadFilter(el.data('filter-id'), {'page' : ''});
            var selectedFilter = element.find('a.filterName').andSelf().filter('a.filterName');
			var filtername =selectedFilter.text();
            var listName = jQuery('<a></a>').attr('href', selectedFilter.attr('href')).html('&nbsp;&nbsp;' + filtername);
			var navi = jQuery('.module-action-content').find('.filter-name');
            navi.find('a').remove();
            navi.append(listName);
        });
        
        jQuery('#createFilter').on('click',function(e){
            var element = jQuery(e.currentTarget);
            element.trigger('post.CreateFilter.click',{'url':element.data('url')});
        });
        
        filters.on('click','li.editFilter,li.duplicateFilter',function(e){
            var element = jQuery(e.currentTarget);
            if(typeof element.data('url') == "undefined") return;
            element.trigger('post.CreateFilter.click',{'url':element.data('url')});
        });
        
        filters.on('click','li.deleteFilter',function(e){
            var element = jQuery(e.currentTarget);
            if(typeof element.data('url') == "undefined") return;
            element.trigger('post.DeleteFilter.click',{'url':element.data('url')});
        });
        
        filters.on('click','li.toggleDefault',function(e){
            var element = jQuery(e.currentTarget);
            element.trigger('post.ToggleDefault.click',{'url':element.data('url')});
        });
        
        filters.on('post.DeletedFilter',function(e){
            var element = jQuery(e.target);
            var popoverId = element.closest('.popover').attr('id');
            var ele = jQuery('.list-group' ).find("[aria-describedby='" + popoverId + "']");
            // お気に入り欄の行は React が描き直す
            jQuery('.list-group').not('#favoriteList').find('li.listViewFilter')
                .has('[rel="popover"][data-filter-id="' + ele.attr('data-filter-id') + '"]').remove();
            element.closest('.popover').remove();
            self.notifyFavoritesChanged();
        });

        filters.on('click','.favoriteListStar',function(e){
            e.preventDefault();
            e.stopPropagation();
            var star = jQuery(e.currentTarget);
            if(star.data('saving')) return;
            var favorite = !star.hasClass('active');
            jQuery('.activePopover').popover('hide').removeClass('rotate').removeClass('activePopover');
            star.data('saving', true);
            jQuery.ajax({
                type : 'POST',
                url : 'index.php',
                dataType : 'json',
                data : {
                    module : 'CustomView',
                    api : 'SaveFavorite',
                    sourceModule : star.data('source-module'),
                    record : star.data('filter-id'),
                    favorite : favorite ? 1 : 0
                }
            }).done(function(data){
                if(!data || data.error || !data.favorites) {
                    self.showFavoriteError(data);
                    return;
                }
                self.notifyFavoritesChanged(data.favorites);
            }).fail(function(xhr){
                self.showFavoriteError(xhr.responseJSON);
            }).always(function(){
                star.data('saving', false);
            });
        });

        jQuery(document).on(Vtiger_ListSidebar_Js.FAVORITES_CHANGED_EVENT, function(e){
            var detail = e.originalEvent && e.originalEvent.detail;
            if(detail && detail.favorites) {
                self.syncFavoriteStars(jQuery.map(detail.favorites, function(favorite){ return favorite.id; }));
            }
        });
        
        filters.on('post.ToggleDefault.saved',function(e,params){
            var filterId = jQuery(e.target).attr('data-filter-id');
            self.setFilterDefaultState(filterId, params && String(params.isdefault) === '1');
        });
        
        filters.find('.toggleFilterSize').on('click',function(e){
            var currentTarget = jQuery(e.currentTarget);
            currentTarget.closest('.list-group').find('li.filterHidden').toggleClass('hide');
            if(currentTarget.closest('.list-group').find('li.filterHidden').hasClass('hide')) {
                currentTarget.html(currentTarget.data('moreText'));
            }else{
                currentTarget.html(currentTarget.data('lessText'));
            }
        })
        
        app.event.on('ListViewFilterLoaded', function(event, container, params) {
			// TODO - Update pagination...
		});
	},
    
    loadListView : function(viewId, params){
        this.getParentInstance().resetData();
        this.getParentInstance().loadFilter(viewId, params);
    },
    
    unMarkAllFilters : function() {
        jQuery('.listViewFilter').removeClass('active');
    },
    
    unMarkAllTags : function() {
        var container = jQuery('#listViewTagContainer');
        container.find('.tag').removeClass('active').find('i.activeToggleIcon').removeClass('fa-circle-o').addClass('fa-circle');
    },
    
    notifyFavoritesChanged : function(favorites) {
        var detail = favorites ? {favorites : favorites} : {};
        document.dispatchEvent(new CustomEvent(Vtiger_ListSidebar_Js.FAVORITES_CHANGED_EVENT, {detail : detail}));
    },

    showFavoriteError : function(data) {
        var message = data && data.error && data.error.message;
        app.helper.showErrorNotification({'message' : message || app.vtranslate('JS_ERROR')});
    },

    syncFavoriteStars : function(favoriteIds) {
        jQuery('#module-filters .favoriteListStar').each(function(){
            var star = jQuery(this);
            var favorite = jQuery.inArray(Number(star.attr('data-filter-id')), favoriteIds) !== -1;
            star.toggleClass('fa-star active', favorite).toggleClass('fa-star-o', !favorite)
                .attr('title', app.vtranslate(favorite ? 'JS_REMOVE_FROM_FAVORITES' : 'JS_ADD_TO_FAVORITES'));
        });
    },

    registerPopOverContent: function () {
        var self = this;
        var element = jQuery(".list-group").not('#favoriteList');

        jQuery.each(element.find('[rel="popover"]'), function (i, ele) {
            self.registerFilterPopover(ele);

            jQuery('html').on('click', function (e) {
                var elements = jQuery('.activePopover');
                if(elements.length <= 0 ){
                    return;
                } else if ($(e.target).data('toggle') !== 'popover' && $(e.target).parents('[data-toggle="popover"]').length === 0
                        && $(e.target).parents('.popover.in').length === 0) {
                    elements.popover('hide').removeClass('rotate').removeClass("activePopover");
                }
            });

            jQuery('.js-popover-container').not('#favoriteList .js-popover-container').on('click', self.onPopoverContainerClick);
        });

        self.refreshFavoriteRows();
        jQuery(document).on(Vtiger_ListSidebar_Js.FAVORITES_RENDERED_EVENT, function () {
            self.refreshFavoriteRows();
        });
    },

    refreshFavoriteRows : function() {
        var self = this;
        var others = jQuery('#module-filters .list-group').not('#favoriteList');
        jQuery('#favoriteList li.listViewFilter').each(function(){
            var li = jQuery(this);
            var ele = li.find('[rel="popover"]');
            var filterId = ele.attr('data-filter-id');
            li.toggleClass('active', others.find('li.listViewFilter.active').has('a.filterName[data-filter-id="' + filterId + '"]').length > 0);
            if(ele.data('bs.popover')) return;
            var source = others.find('[rel="popover"][data-filter-id="' + filterId + '"]').first();
            if(!source.length) return;
            jQuery.each(source[0].attributes, function(i, attr){
                if(attr.name.indexOf('data-') === 0 || attr.name === 'toggleclass') {
                    ele.attr(attr.name, attr.value);
                }
            });
            self.registerFilterPopover(ele[0]);
            ele.closest('.js-popover-container').on('click', self.onPopoverContainerClick);
        });
        var search = jQuery('#module-filters .search-list');
        if(search.val()) {
            search.trigger('keyup');
        }
    },

    onPopoverContainerClick : function(e) {
        var currentElement = jQuery(e.currentTarget).find('[data-toggle]');
        if(jQuery('.popover').hasClass('in')) {
            currentElement.addClass('rotate');
            currentElement.addClass('activePopover');
        }else {
            currentElement.removeClass('rotate');
            currentElement.removeClass('activePopover');
        }
        if (jQuery('.popover', '#module-filters').length > 1) {
            var popoverId = jQuery('.popover', '#module-filters').attr('id');
            var ele = jQuery('.list-group').find("[aria-describedby='" + popoverId + "']");
            ele.removeClass('rotate');
            jQuery('.popover', '#module-filters').first().popover('hide');
        }
        e.stopPropagation();
    },

    // デフォルトはユーザー・モジュールごとに 1 つなので、設定したら他のリストは外れる
    setFilterDefaultState : function(filterId, isDefault) {
        jQuery('#module-filters .list-group [rel="popover"][data-filter-id]').each(function(){
            var ele = jQuery(this);
            var isTarget = ele.attr('data-filter-id') == filterId;
            if(!isTarget && !isDefault) return;
            var value = isTarget && isDefault;
            ele.attr('data-is-default', value ? '1' : '')
                .attr('toggleClass', 'fa ' + (value ? 'fa-check-square-o' : 'fa-square-o'));
            var popover = ele.data('bs.popover');
            if(popover && popover.$tip) {
                var toggleEle = popover.$tip.find('.toggleDefault');
                toggleEle.attr('data-is-default', value ? '1' : '').data('isDefault', value);
                toggleEle.find('i').attr('class', ele.attr('toggleClass'));
            }
        });
    },

    registerFilterPopover : function(ele) {
            var self = this;
            var options = {
                html: true,
                placement: 'left',
                template: '<div class="popover" style="top: 0; position:absolute; z-index:0; margin-top:5px"><div class="popover-content"></div></div>',
                content: function () {
                    return self.buildFilterPopoverContent(this);
                },
                container: '#module-filters',
                sanitize: false
            };

            jQuery(ele).popover(options);
            // Bootstrap 3 は hide でクリック状態を戻さず、次の ▼ で開かないため戻す
            jQuery(ele).on('hidden.bs.popover', function () {
                var popover = jQuery(this).data('bs.popover');
                if (popover && popover.inState) {
                    popover.inState.click = false;
                }
            });
    },

    buildFilterPopoverContent : function(ele) {
            var contentEle = jQuery('#filterActionPopoverHtml').clone();
            contentEle.find('.listmenu').removeClass('hide');
            var editEle = contentEle.find('.editFilter');
            var deleteEle = contentEle.find('.deleteFilter');
            var duplEle = contentEle.find('.duplicateFilter');
            var toggleEle = contentEle.find('.toggleDefault');
    
            editEle.attr('data-url', jQuery(ele).data('editurl'));
            deleteEle.attr('data-url', jQuery(ele).data('deleteurl'));
            duplEle.attr('data-url', jQuery(ele).data('default'));
            toggleEle.attr('data-url', jQuery(ele).data('defaulttoggle'));
            // data() は最初の値をキャッシュするため attr() で読む
            toggleEle.attr('data-is-default', jQuery(ele).attr('data-is-default'));
            toggleEle.attr('data-filter-id', jQuery(ele).data('filter-id'));
            contentEle.find('.toggleDefault i').attr('class', jQuery(ele).attr('toggleClass'));
             editEle.attr('data-id', jQuery(ele).data('id'));
            deleteEle.attr('data-id', jQuery(ele).data('id'));

            if (!jQuery(ele).data('editable')) {
                contentEle.find('.editFilter').remove();
            } else {
                contentEle.find('.editFilter').removeClass('disabled');
            }
            if (!jQuery(ele).data('deletable')) {
                contentEle.find('.deleteFilter').remove();
            } else {
                contentEle.find('.deleteFilter').removeClass('disabled');
            }
            return contentEle.html();
    },
    
    
    registerTagClick : function() {
        var self = this;
        var container = jQuery('#listViewTagContainer');
        container.on('click', '.tag', function(e) {
            var eventTriggerSourceElement = jQuery(e.target);
            //if edit icon is clicked then we dont have to load the tag
            if(eventTriggerSourceElement.is('.editTag')) {
                return;
            }
            var element = jQuery(e.currentTarget);
            var tagId = element.data('id');
            var viewId = container.data('viewId');
            
            self.unMarkAllFilters();
            self.unMarkAllTags();
            element.addClass('active');
            element.find('i.activeToggleIcon').removeClass('fa-circle').addClass('fa-circle-o');
            var listSearchParams = new Array();
            listSearchParams[0] = new Array();
            var tagSearchParams = new Array();
            tagSearchParams.push('tags');
            tagSearchParams.push('e');
            tagSearchParams.push(tagId);
            listSearchParams[0].push(tagSearchParams);
            var params = {};
            params.search_params = ''; 
            params.tag_params = JSON.stringify(listSearchParams);
            params.tag = tagId;
            params.page = '';
            self.loadListView(viewId, params);
        });
        
        container.on('click', '.moreTags', function(e){
            container.find('.moreListTags').removeClass('hide');
            jQuery(e.currentTarget).addClass('hide');
        });
    },
    getFixedHeightScrollContainers : function(filters) {
        return filters.find(".scrollContainer").filter(function(){
            return jQuery(this).closest('.sidebar-menu-fit').length === 0;
        });
    },

    registerEvents : function() {
        var self = this;
        this.registerFilters();
        this.registerTagClick();
        this.registerPopOverContent();
//        var listInstance = new Vtiger_List_Js();
//        listInstance.registerDynamicDropdownPosition("lists-menu", "list-menu-content");

        app.event.on('Vtiger.Post.MenuToggle', function() {
            if(!jQuery('.sidebar-essentials').hasClass('hide')) {
                var filters = jQuery('.module-filters').not('.module-extensions');
                var scrollContainers = self.getFixedHeightScrollContainers(filters);
                jQuery.each(scrollContainers,function(key,scroll){
                    var scroll = jQuery(scroll);
                    var listcontentHeight = scroll.find(".list-menu-content").height();
                    scroll.css("height",listcontentHeight);
                    scroll.perfectScrollbar('update');
                });
            }
        });
    }
});
