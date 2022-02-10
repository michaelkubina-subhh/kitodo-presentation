'use strict';

/**
 * (c) Kitodo. Key to digital objects e.V. <contact@kitodo.org>
 *
 * This file is part of the Kitodo and TYPO3 projects.
 *
 * @license GNU General Public License version 3 or later.
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

// Internet Explorer does not support String.prototype.endsWith
if (String.prototype.endsWith === undefined) {
    String.prototype.endsWith = function(searchString, length) {
        if (searchString === null || searchString === '' || length !== null && searchString.length > length || searchString.length > this.length) {
            return false;
        }
        length = length === null || length > this.length || length <= 0 ? this.length : length;
        var substr = this.substr(0, length);
        return substr.lastIndexOf(searchString) === length - searchString.length;
    };
}

/**
 * Base namespace for utility functions used by the dlf module.
 *
 * @const
 */
var dlfUtils;
dlfUtils = dlfUtils || {};

/**
 * @type {{ZOOMIFY: string}}
 */
dlfUtils.CUSTOM_MIMETYPE = {
    IIIF: 'application/vnd.kitodo.iiif',
    IIP: 'application/vnd.netfpx',
    ZOOMIFY: 'application/vnd.kitodo.zoomify'
};

/**
 * @type {number}
 */
dlfUtils.RUNNING_INDEX = 99999999;

/**
 * Clone OpenLayers layer for dlfViewer (only properties used there are
 * considered).
 *
 * @param {ol.layer.Layer} layer
 * @returns {ol.layer.Layer}
 */
dlfUtils.cloneOlLayer = function (layer) {
    // Get a fresh instance of layer's class (ol.layer.Tile or ol.layer.Image)
    var LayerClass = layer.constructor;

    return new LayerClass({
        source: layer.getSource()
    });
};

/**
 * @param imageSourceObjs
 * @param {string} opt_origin
 * @return {Array.<ol.layer.Layer>}
 */
dlfUtils.createOlLayers = function (imageSourceObjs, opt_origin) {

    var origin = opt_origin !== undefined ? opt_origin : null,
        widthSum = 0,
        offsetWidth = 0,
        layers = [];

    imageSourceObjs.forEach(function (imageSourceObj) {
        if (widthSum > 0) {
            // set offset width in case of multiple images
            offsetWidth = widthSum;
        }

        //
        // Create layer
        //
        var extent = [offsetWidth, -imageSourceObj.height, imageSourceObj.width + offsetWidth, 0],
            layer = void 0;

        // OL's Zoomify source also supports IIP; we just need to make sure
        // the url is a proper template.
        if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY
            || imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP
        ) {
            // create zoomify layer
            var url = imageSourceObj.src;

            if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP
                && url.indexOf('JTL') === -1
            ) {
                url += '&JTL={z},{tileIndex}';
            }

            layer = new ol.layer.Tile({
                source: new ol.source.Zoomify({
                    url: url,
                    size: [imageSourceObj.width, imageSourceObj.height],
                    crossOrigin: origin,
                    extent: extent,
                    zDirection: -1
                })
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF) {
            var options = $.extend({
                projection: new ol.proj.Projection({
                    code: 'kitodo-image',
                    units: 'pixels',
                    extent: extent
                }),
                crossOrigin: origin,
                extent: extent,
                zDirection: -1
            }, imageSourceObj.iiifSourceOptions);

            layer = new ol.layer.Tile({
                source: new ol.source.IIIF(options)
            });
        } else {

            // create static image source
            layer = new ol.layer.Image({
                source: new ol.source.ImageStatic({
                    url: imageSourceObj.src,
                    projection: new ol.proj.Projection({
                        code: 'kitodo-image',
                        units: 'pixels',
                        extent: extent
                    }),
                    imageExtent: extent,
                    crossOrigin: origin
                })
            });
        }
        layers.push(layer);

        // add to cumulative width
        widthSum += imageSourceObj.width;
    });

    return layers;
};

/**
 * @param {Array.<{src: *, width: *, height: *}>} images
 * @return {ol.View}
 */
dlfUtils.createOlView = function (images) {

    //
    // Calculate map extent
    //
    var maxLonX = images.reduce(function (prev, curr) {
        return prev + curr.width;
    }, 0),
        maxLatY = images.reduce(function (prev, curr) {
        return Math.max(prev, curr.height);
    }, 0),
        extent = [0, -maxLatY, maxLonX, 0];

    // globally define max zoom
    window.DLF_MAX_ZOOM = 8;

    // define map projection
    var proj = new ol.proj.Projection({
        code: 'kitodo-image',
        units: 'pixels',
        extent: extent
    });

    // define view
    var viewParams = {
        projection: proj,
        center: ol.extent.getCenter(extent),
        zoom: 1,
        maxZoom: window.DLF_MAX_ZOOM,
        extent,
        constrainOnlyCenter: true,
        constrainRotation: false
    };

    return new ol.View(viewParams);
};

/**
 * Returns true if the specified value is not undefined
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.exists = function (val) {
    return val !== undefined;
};

/**
 * Fetch image data for given image sources.
 *
 * @param {Array.<{url: *, mimetype: *}>} imageSourceObjs
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchImageData = function (imageSourceObjs) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    /**
     * This holds information about the loading state of the images
     * @type {Array.<number>}
     */
    var imageSourceData = [],
        loadCount = 0,
        finishLoading = function finishLoading() {
        loadCount += 1;

        if (loadCount === imageSourceObjs.length) {
            deferredResponse.resolve(imageSourceData);
        }
    };

    imageSourceObjs.forEach(function (imageSourceObj, index) {
        if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.ZOOMIFY) {
            dlfUtils.fetchZoomifyData(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIIF) {
            dlfUtils.getIIIFResource(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                      finishLoading();
            });
        } else if (imageSourceObj.mimetype === dlfUtils.CUSTOM_MIMETYPE.IIP) {
            dlfUtils.fetchIIPData(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
            });
        } else {
            // In the worse case expect static image file
            dlfUtils.fetchStaticImageData(imageSourceObj)
                .done(function (imageSourceDataObj) {
                    imageSourceData[index] = imageSourceDataObj;
                    finishLoading();
            });
        }
    });

    return deferredResponse;
};


/**
 * Fetches the image data for static images source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchStaticImageData = function (imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    // Create new Image object.
    var image = new Image();

    // Register onload handler.
    image.onload = function () {

        var imageDataObj = {
            src: this.src,
            mimetype: imageSourceObj.mimetype,
            width: this.width,
            height: this.height
        };

        deferredResponse.resolve(imageDataObj);
    };

    // Initialize image loading.
    image.src = imageSourceObj.url;

    return deferredResponse;
};

/**
 * @param imageSourceObj
 * @returns {JQueryStatic.Deferred}
 */
dlfUtils.getIIIFResource = function getIIIFResource(imageSourceObj) {

    var deferredResponse = new $.Deferred();
    var type = 'GET';
    $.ajax({
        url: dlfViewerSource.IIIF.getMetdadataURL(imageSourceObj.url),
        type,
        dataType: 'json'
    }).done(cb);

    function cb(data) {
        var format = new ol.format.IIIFInfo(data);
        var options = format.getTileSourceOptions();

        if (options === undefined || options.version === undefined) {
            deferredResponse.reject();
        } else {
            deferredResponse.resolve({
                mimetype: dlfUtils.CUSTOM_MIMETYPE.IIIF,
                width: options.size[0],
                height: options.size[1],
                iiifSourceOptions: options
            });
        }
    }

    return deferredResponse;
};

/**
 * Fetches the image data for iip images source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchIIPData = function (imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    $.ajax({
        url: dlfViewerSource.IIP.getMetdadataURL(imageSourceObj.url) //'http://localhost:8000/fcgi-bin/iipsrv.fcgi?FIF=F4713/HD7.tif&obj=IIP,1.0&obj=Max-size&obj=Tile-size&obj=Resolution-number',
    }).done(cb);
    function cb(response, type) {
        if (type !== 'success') throw new Error('Problems while fetching ImageProperties.xml');

        var imageDataObj = $.extend({
            src: imageSourceObj.url,
            mimetype: imageSourceObj.mimetype
        }, dlfViewerSource.IIP.parseMetadata(response));

        deferredResponse.resolve(imageDataObj);
    }

    return deferredResponse;
};

/**
 * Fetch image data for zoomify source.
 *
 * @param {{url: *, mimetype: *}} imageSourceObj
 * @return {JQueryStatic.Deferred}
 */
dlfUtils.fetchZoomifyData = function (imageSourceObj) {

    // use deferred for async behavior
    var deferredResponse = new $.Deferred();

    $.ajax({
        url: imageSourceObj.url
    }).done(cb);
    function cb(response, type) {
        if (type !== 'success') {
            throw new Error('Problems while fetching ImageProperties.xml');
        }

        var properties = $(response).find('IMAGE_PROPERTIES');

        var imageDataObj = {
            src: imageSourceObj.url.substring(0, imageSourceObj.url.lastIndexOf("/") + 1),
            width: parseInt(properties.attr('WIDTH')),
            height: parseInt(properties.attr('HEIGHT')),
            tilesize: parseInt(properties.attr('TILESIZE')),
            mimetype: imageSourceObj.mimetype
        };

        deferredResponse.resolve(imageDataObj);
    }

    return deferredResponse;
};

/**
 * @param {string} name Name of the cookie
 * @return {string|null} Value of the cookie
 * @TODO replace unescape function
 */
dlfUtils.getCookie = function (name) {

    var results = document.cookie.match("(^|;) ?" + name + "=([^;]*)(;|$)");

    if (results) {

        return decodeURI(results[2]);
    } else {

        return null;
    }
};

/**
 * Returns url parameters
 * @returns {Object|undefined}
 */
dlfUtils.getUrlParams = function () {
    if (Object.prototype.hasOwnProperty.call(location, 'search')) {
        var search = decodeURIComponent(location.search).slice(1).split('&'),
            params = {};

        search.forEach(function (item) {
            var s = item.split('=');
            params[s[0]] = s[1];
        });

        return params;
    }
    return undefined;
};

/**
 * Returns true if the specified value is null.
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.isNull = function (val) {
    return val === null;
};

/**
 * Returns true if the specified value is null, empty or undefined.
 * @param {?} val
 * @return {boolean}
 */
dlfUtils.isNullEmptyUndefinedOrNoNumber = function (val) {
    return val === null || val === undefined || val === '' || isNaN(val);
};

/**
 * Checks if {@link obj} is a valid object describing the location of a
 * fulltext (@see PageView::getFulltext in PageView.php).
 *
 * @param {any} obj The object to test.
 * @return {boolean}
 */
dlfUtils.isFulltextDescriptor = function (obj) {
    return (
        typeof obj === 'object'
        && obj !== null
        && 'url' in obj
        && obj.url !== ''
    );
};

/**
 * @param {Element} element
 * @return {Object}
 */
dlfUtils.parseDataDic = function (element) {
    var dataDicString = $(element).attr('data-dic'),
        dataDicRecords = dataDicString.split(';'),
        dataDic = {};

    for (var i = 0, ii = dataDicRecords.length; i < ii; i++) {
        var key = dataDicRecords[i].split(':')[0],
            value = dataDicRecords[i].split(':')[1];
        dataDic[key] = value;
    }

    return dataDic;
};

/**
 * Set a cookie value
 *
 * @param {string} name The key of the value
 * @param {?} value The value to save
 */
dlfUtils.setCookie = function (name, value) {

    document.cookie = name + "=" + decodeURI(value) + "; path=/";
};

/**
 * Scales down the given features geometries. as a further improvement this function
 * adds a unique id to every feature
 * @param {Array.<ol.Feature>} features
 * @param {Object} imageObj
 * @param {number} width
 * @param {number} height
 * @param {number=} opt_offset
 * @deprecated
 * @return {Array.<ol.Feature>}
 */
dlfUtils.scaleToImageSize = function (features, imageObj, width, height, opt_offset) {

    // update size / scale settings of imageObj
    var image = void 0;
    if (width && height) {

        image = {
            'width': width,
            'height': height,
            'scale': imageObj.width / width
        };
    }

    if (image === undefined) return [];

    var scale = image.scale,
        offset = opt_offset !== undefined ? opt_offset : 0;

    // do rescaling and set a id
    for (var i in features) {

        var oldCoordinates = features[i].getGeometry().getCoordinates()[0],
            newCoordinates = [];

        for (var j = 0; j < oldCoordinates.length; j++) {
            newCoordinates.push(
              [offset + scale * oldCoordinates[j][0], 0 - scale * oldCoordinates[j][1]]);
        }

        features[i].setGeometry(new ol.geom.Polygon([newCoordinates]));

        // set index
        dlfUtils.RUNNING_INDEX += 1;
        features[i].setId('' + dlfUtils.RUNNING_INDEX);
    }

    return features;
};

/**
 * Search a feature collection for a feature with the given coordinates
 * @param {Array.<ol.Feature>} featureCollection
 * @param {string} coordinates
 * @return {Array.<ol.Feature>|undefined}
 */
dlfUtils.searchFeatureCollectionForCoordinates = function (featureCollection, coordinates) {
    var features = [];
    featureCollection.forEach(function (ft) {
        if (ft.get('fulltext') !== undefined) {
            if (ft.getId() === coordinates) {
                features.push(ft);
            }
        }
    });
    return features.length > 0 ? features : undefined;
};
