
/* jFeed : $ feed parser plugin
 * Copyright (C) 2007 Jean-François Hovinne - http://www.hovinne.com/
 * Dual licensed under the MIT (MIT-license.txt)
 * and GPL (GPL-license.txt) licenses.
 */

$.getFeed = function(options) {

    options = $.extend({

        url: null,
        data: null,
        cache: true,
        success: null,
        failure: null,
        error: null,
        global: true

    }, options);

    if (options.url) {
        
        if ($.isFunction(options.failure) && $.type(options.error)==='null') {
          // Handle legacy failure option
          options.error = function(xhr, msg, e){
            options.failure(msg, e);
          }
        } else if ($.type(options.failure) === $.type(options.error) === 'null') {
          // Default error behavior if failure & error both unspecified
          options.error = function(xhr, msg, e){
            window.console&&console.log('getFeed failed to load feed', xhr, msg, e);
          }
        }

        return $.ajax({
            type: 'GET',
            url: options.url,
            data: options.data,
            cache: options.cache,
            success: function(xml) {
                var feed = new JFeed(xml);
                if ($.isFunction(options.success)) options.success(feed);
            },
            error: options.error,
            global: options.global
        });
    }
};

function JFeed(xml) {
    if (xml) this.parse(xml);
}
;

JFeed.prototype = {

    type: '',
    version: '',
    title: '',
    link: '',
    description: '',
    parse: function(xml) {

        if ($('channel', xml).length == 1) {

            this.type = 'rss';
            var feedClass = new JRss(xml);

        } else if ($('feed', xml).length == 1) {

            this.type = 'atom';
            var feedClass = new JAtom(xml);
        }

        if (feedClass) $.extend(this, feedClass);
    }
};

function JFeedItem() {};

JFeedItem.prototype = {

    title: '',
    link: '',
    category: '',
    description: '',
    updated: '',
    id: ''
};

function JAtom(xml) {
    this._parse(xml);
};

JAtom.prototype = {
    
    _parse: function(xml) {
    
        var channel = $('feed', xml).eq(0);

        this.version = '1.0';
        this.title = $(channel).find('title:first').text();
        this.link = $(channel).find('link:first').attr('href');
        this.description = $(channel).find('subtitle:first').text();
        this.language = $(channel).attr('xml:lang');
        this.updated = $(channel).find('updated:first').text();

        this.items = new Array();
        
        var feed = this;
        
        $('entry', xml).each( function() {
        
            var item = new JFeedItem();
            
            item.title = $(this).find('title').eq(0).text();
            item.link = $(this).find('link').eq(0).attr('href');
            item.category = $(this).find('category').eq(0).text();
            item.description = $(this).find('content').eq(0).text();
            item.updated = $(this).find('updated').eq(0).text();
            item.id = $(this).find('id').eq(0).text();
            
            feed.items.push(item);
        });
    }
};

function JRss(xml) {
    this._parse(xml);
};

JRss.prototype  = {
    
    _parse: function(xml) {
    
        if($('rss', xml).length == 0) this.version = '1.0';
        else this.version = $('rss', xml).eq(0).attr('version');

        var channel = $('channel', xml).eq(0);
    
        this.title = $(channel).find('title:first').text();
        this.link = $(channel).find('link:first').text();
        this.description = $(channel).find('description:first').text();
        this.language = $(channel).find('language:first').text();
        this.updated = $(channel).find('lastBuildDate:first').text();

        this.items = new Array();
        
        var feed = this;
        
        $('item', xml).each( function() {
        
            var item = new JFeedItem();
           
            item.title = $(this).find('title').eq(0).text();
            item.link = $(this).find('link').eq(0).text();
            item.category = $(this).find('category').eq(0).text();
            
            //  need to look at description a few ways
            var d = '';
            d = $(this).find('description').eq(0).text();
            if (d.length == 0) {
                // fix for webkit browsers
                d = $(this).find('content\\:encoded').eq(0).text();
            }
            if (d.length == 0) {
                d =  $(this).find('encoded').eq(0).text();
            }
            item.description = d;
            item.updated = $(this).find('pubDate').eq(0).text();

            /* create nice date */
            var date = new Date(item.updated);
            var months = Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
            item.nicedate = date.getDate() + " " + months[date.getMonth()] + " " + date.getFullYear(); 
 
            item.id = $(this).find('guid').eq(0).text();

            feed.items.push(item);
        });
    }
};