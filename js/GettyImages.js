/*
* Version: 1.0
*/

var GettyImages = GettyImages || {};

GettyImages.SearchItems = function() {
    var $overlay = $('#overlay');
    var $search_results = $('#searchresults');
    var $search_items = $search_results.children().find('#item');

    this.LoadOnReady = function(saveUrl) {
        $search_items.find('img').addClass('pointer');
        $search_items.each(function() {
            var $search_item = $(this);
            var imageId = $search_item.attr('name');
            var $search_image = $search_item.find('img');
            var $download_button = $search_item.find('#downloadbutton');
            var clickDownloadImage = function(event) {
                DownloadImage(imageId, saveUrl);
                event.preventDefault();
            };
            $download_button.on('click', clickDownloadImage);
            $search_image.on('click', clickDownloadImage);
        });
        $search_items.find('img').on('load', function() {
            var $item = $(this).parent();
            var $imgW = $(this).width();
            var $caption = $item.find('#caption');
            var $capSize = $caption.text().length;
            if ($imgW > 200) {
                $item.find('div').css('width', $imgW);
                $item.find('button').css('width', $imgW);
            }
            if ($capSize < 99) {
                $caption.css('text-align','center');
            }
        }).error(function() {
            var src= $(this).attr('src');
            var baksrc= $(this).attr('baksrc');
            $(this).attr('src', baksrc );
            $(this).attr('orgsrc', src );
        });
    }; // end LoadOnReady

    function DownloadImage(imageId, saveUrl) {
        if ($overlay.is(':visible')) { return; }
        $search_items.find('#downloadbutton').removeClass('pointer');
        $search_items.find('img').removeClass('pointer');
        var imageFn = 'getty_'+imageId+'.jpg';
        var imageUri = 'imgs/'+imageFn;
        var overlay_html = ''+
            '<p id="overlay_msg" style="float:left;">DOWNLOADING PLEASE WAIT.</p>'+
            '<p id="overlay_close_link" class="hidden" style="float:right;">'+
            '<button id="overlayclose">X</button></p><hr />'+
        '';
        $overlay.empty().removeClass('hidden');
        $overlay.html(overlay_html);
        var overlay_dotting = new GettyImages.AnimatedDots($('#overlay_msg'), 8);
        overlay_dotting.Start();

        var dataObj = {};
        dataObj.image_id = imageId;
        var nlObj = jQuery.get(saveUrl, dataObj, function(data) {
            console.log(data);
        })
        .done(function(data) {
            console.log('done');
            if ((data.status == '200') && (data.file_saved)) {
                $('#overlay_msg').empty().html('DONE');
                //$overlay.append(''+JSON.stringify(data));
                $overlay.append(''+
                    '<p>Downloaded from GettyImages.com Successfully!</p>'+
                    '<a href="'+imageUri+'" download='+imageFn+'>save image</a> or '+
                    '<a href="image.php?image_id='+imageId+'" target="_blank">see image</a>');
                $overlay.append('<ol>');
                $overlay.append('<li><span>id</span>: '+data.image.id+'</li>');
                $overlay.append('<li><span>title</span>: '+data.image.title+'</li>');
                $overlay.append('<li><span>caption</span>: '+data.image.caption+'</li>');
                $overlay.append('<li><span>max width</span>: '+data.image.max_dimensions.width+'</li>');
                $overlay.append('<li><span>max height</span>: '+data.image.max_dimensions.height+'</li>');
                $overlay.append('</ol>');
            } else {
                $('#overlay_msg').html('ERROR');
                $overlay.append('<p>There was a problem saving the file.</p>');
            }
        })
        .fail(function() {
            console.log( "fail" );
            $('#overlay_msg').html('ERROR');
            $overlay.append('<p>GettyImages API call failed.</p>');
        })
        .always(function() {
            overlay_dotting.Stop();
            var overlay_bottom_html = ''+
                '<hr /><p id="closewindow"><a href="#" '+
                ' onClick="(new GettyImages.SearchItems).CloseOverlay(); '+
                ' return false;">close window</a></p>'+
            '';
            $('#overlay_close_link').removeClass('hidden');
            $overlay.append(overlay_bottom_html);
        }, "json");

        $overlay.find('#overlayclose').on('click', function(event) {
            CloseOverlay();
            event.preventDefault();
        });

    }; // end DownloadImage

    function CloseOverlay() {
        $overlay.empty().addClass('hidden');
        $search_items.find('#downloadbutton').addClass('pointer');
        $search_items.find('img').addClass('pointer');
        return false;
    }; // end CloseOverlay
    this.CloseOverlay = CloseOverlay;

}; // end SearchItems



GettyImages.ImageTool = function() {
    var $the_window = $(window);
    var $crop_image_box = $('#cropimagebox');
    var $select_toolbar = $crop_image_box.find('#selecttoolbar');
    var $select_button = $select_toolbar.find('#selectbutton');
    var $crop_toolbar = $crop_image_box.find('#croptoolbar');
    var $crop_target = $crop_image_box.find('#croptarget');
    var $img_loading_msg = $crop_image_box.find('#imgloadingmsg');
    var $cropped_results = $('#croppedresults');
    var $cropped_target = $cropped_results.find('#croppedtarget');
    var jcrop_api = null;
    var crop = {
        x: $('#x'),
        y: $('#y'),
        w: $('#w'),
        h: $('#h'),
        img_w: $('#img_w'),
        img_h: $('#img_h'),
    };
    var max_w = null;
    var thisUrl = window.location.href;
    var img_loading_msg_dotting = new GettyImages.AnimatedDots($img_loading_msg, 6);

    this.LoadOnReady = function(imagetoolUrl) {

        // init display
        $crop_toolbar.hide();
        $select_toolbar.hide();
        $cropped_results.hide();
        $crop_target.hide();
        $img_loading_msg.html("Please wait image loading.");
        img_loading_msg_dotting.Start();

        // get getty image data
        dataObj = getQueryArgs();
        var nlObj = jQuery.get(imagetoolUrl, dataObj, function(data) {
            $crop_target.find('img').attr('src', data.crop_target_src);
            $cropped_target.find('img').attr('src', data.cropped_src);
            $cropped_target.find('a').attr('href', data.cropped_src);
            $cropped_target.find('a').attr('download', data.cropped_fn);
        })
        .done(function(data) {

            // no data
            if (Boolean(data.error)) {
                showImageProblemMsg();
                return;
            }

            // cropped image to display
            if (Boolean(data.have_crop)) {
                $crop_image_box.hide();
                $cropped_results.show();
                return;
            }

            // crop target to display
            $crop_target.show();
            setImageDefaultSize();
            $crop_target.find('img').on('load', function() {
                setImageActualSize();
                max_w = $crop_target.find('img').width();
                setImageDefaultSize();
                img_loading_msg_dotting.Stop();
                $img_loading_msg.empty();
                $crop_target.show();
                $select_toolbar.show();
            }).on('error', function() {
                showImageProblemMsg();
                return;
            });

            // bind crop target events
            var selectCropClick = function(event) {
                selectCrop();
                event.preventDefault();
            };
            $crop_target.find('img').on('click', selectCropClick);
            $select_button.on('click', selectCropClick);

            var releaseCropClick = function(event) {
                releaseCrop();
                event.preventDefault();
            };
            $crop_toolbar.find('#cropcancelbutton').on('click', releaseCropClick);
            $the_window.resize(releaseCropClick);

            changeImageSize('#imgdown', 0.95);
            changeImageSize('#imgup', 1.05);

            var setImageActualSizeClick = function(event) {
                setImageActualSize();
                event.preventDefault();
            };
            $select_toolbar.find('#imgactual').on('click', setImageActualSize);

            var setImageDefaultSizeClick = function(event) {
                setImageDefaultSize();
                event.preventDefault();
            };
            $select_toolbar.find('#imgdefault').on('click', setImageDefaultSize);

        })
        .fail(function(data) {
            showImageProblemMsg();
        })
        .always(function(data) {
            console.log(data);
        }, "json");

    }; // end LoadOnReady

    function getQueryArgs() {
        var queryArgs = {};
        KeysVals = thisUrl.split(/[\?&]+/);
        for (i = 0; i < KeysVals.length; i++) {
            KeyVal = KeysVals[i].split("=");
            queryArgs[KeyVal[0]] = KeyVal[1];
        }
        return queryArgs;
    }; // end getQueryArgs

    function setImageDimentions(w, h) {
        var $img = $('#croptarget').find('img');
        $img.width(w).height(h);
        var w = $img.width();
        var h = $img.height();
        $select_toolbar.find('#imgdimentions').empty()
            .html('width:'+ w +' height:'+ h );
    }; // end setImageDimentions

    function changeImageSize(tag, change) {
        var timer = null;
        $select_toolbar.find(tag).on('mousedown', function () {
            timer = resizeImageLoop(change);
        }).on('mouseup', function () {
            window.clearInterval(timer);
        }).on('mouseout', function () {
            window.clearInterval(timer);
        });
    }; // end changeImageSize

    function resizeImageLoop(change) {
        resizeImage(change);
        return setInterval(function () {
            resizeImage(change);
        }, 300);
    }; // end resizeImageLoop

    function resizeImage(change) {
        var $img = $crop_target.find('img');
        var orig_w = $img.width();
        var orig_h = $img.height();
        var w = parseInt(orig_w) * change;
        var h = parseInt(orig_h) * change;
        var min_w = 200;
        var milliseconds = (new Date).getTime();
        if (w < min_w) {
            var tag = 'imgdimentionsmsg' + milliseconds;
            var msg = 'Minimum width is '+ min_w;
            setImageDimentions(min_w, 'auto');
            setDimentionsErrMsg(tag, msg);
            return;
        }
        if (w > max_w) {
            var tag = 'imgdimentionsmsg' + milliseconds;
            var msg = 'Maximum width is ' + max_w;
            setImageDimentions('auto', 'auto');
            setDimentionsErrMsg(tag, msg);
            return;
        }
        setImageDimentions(w, h);
    }; // end resizeImage

    function setDimentionsErrMsg(tag, msg) {
            var html = ''+ 
                '<span id="' + tag +
                '" style="color:red;font-weight:bold;">'+ 
                 msg + '</span>'+
            '';
            $select_toolbar.find('#imgdimentions').append(html);
            setInterval(function () {
                $select_toolbar.find('#'+tag).remove();
            }, 2400);
    }; // setDimentionsErrMsg

    function setImageActualSize() {
        setImageDimentions('auto', 'auto');
    }; // end setImageActualSize

    function setImageDefaultSize() {
        setImageDimentions('auto', 'auto');
        $crop_target.addClass('fitscreen');
        $img = $crop_target.find('img');
        setImageDimentions($img.width(), $img.height());
        $crop_target.removeClass('fitscreen');
    }; // end setImageDefaultSize

    function selectCrop() {
        if (!jcrop_api) {
            $select_toolbar.hide();
            $crop_toolbar.show();
            $crop_target.find('img').Jcrop({
                setSelect: [0, 0, 200, 200],
                onSelect: saveCoords,
                onChange: saveCoords,
            }, function () {
                jcrop_api = this;
            });
        }
    }; // end selectCrop

    function releaseCrop() {
        if (jcrop_api) {
            jcrop_api.destroy();
        }
        jcrop_api = null;
        $crop_toolbar.hide();
        $select_toolbar.show();
    }; // end releaseCrop

    function saveCoords(c) {
        var $img = $crop_target.find('img');
        crop.x.val(c.x);
        crop.y.val(c.y);
        crop.w.val(c.w);
        crop.h.val(c.h);
        crop.img_w.val($img.width());
        crop.img_h.val($img.height());
        $crop_toolbar.find('#imgdimentions').html(
            'width:' + c.w + ' height:' + c.h +
            ' x:' + c.x + ' y:' + c.y);
    }; // end saveCoords

    function showImageProblemMsg() {
        $crop_target.hide();
        img_loading_msg_dotting.Stop();
        $img_loading_msg.empty()
            .html("There was a problem.")
            .css('font-weight','bold');
    }; // end showImageProblemMsg

};  // end ImageTool

GettyImages.AnimatedDots = function($el, max) {
    var speed = 500;
    var c = '<span id="dot">.</span>';
    var count = 0;
    var timer = null;
    var $dots = null;

    this.Start = function() {
        timer = setInterval(doDotting, speed / 2);
    }; // end Start

    this.Stop = function() {
        clearInterval(timer);
        removeDotting();
    }; // end Stop

    function doDotting() {
        count++;
        if (count > max) {
            count = 1;
            removeDotting();
        }
        if (count === 1) {
            $el.append('<span id="dots"></span>');
            $dots = $el.find('#dots');
        }
        $dots.append(c);
        $dots.find('#dot').fadeIn(speed);
    }; // end doDotting

    function removeDotting() {
        if ($dots) {
            $dots.remove();
        }
    }; // end rmoveDotting

}; // end AnimatedDots

