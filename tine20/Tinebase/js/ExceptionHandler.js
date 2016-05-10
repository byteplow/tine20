/*
 * Tine 2.0
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2009-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine', 'Tine.Tinebase');

/**
 * @namespace Tine.Tinebase
 * @class Tine.Tinebase.ExceptionHandler
 * @singleton
 * 
 * IE NOTE: http://msdn.microsoft.com/library/default.asp?url=/library/en-us/dnscrpt/html/WebErrors2.asp
 *    "A common problem that bites many developers occurs when their onerror handler is not 
 *     called because they have script debugging enabled for Internet Explorer."
 *
 * central class for exception handling
 */
Tine.Tinebase.ExceptionHandler = function() {
    
    /**
     * handle window errors
     * 
     * NOTE: this isn't working cross browser and reports generated by this
     *       are useless most times as we don't have the trace and user tend to 
     *       not describe the problem. Moreover, in most cases, js exceptions are 
     *       not a problem for the UI anyway.
     *       
     *       Therefore we don't show the error reporting dialog anymore for js
     *       exceptions, but let the exception bubble, so browser specific error
     *       analysis is possible
     */
    var onWindowError = function() {
        
        var error = getNormalisedError.apply(this, arguments);
        
        var traceHtml = '<table>';
        for (p in error) {
            if (error.hasOwnProperty(p)) {
                traceHtml += '<tr><td><b>' + p + '</b></td><td>' + error[p] + '</td></tr>'
            }
        }
        traceHtml += '</table>';
        
         // check for special cases we don't want to handle
        if (traceHtml.match(/versioncheck/)) {
            return true;
        }
        // we don't wanna know fancy FF3.5 crome bugs
        if (traceHtml.match(/chrome/)) {
            return true;
        }
        // don't show openlayers error (occurs if window with contact is closed too fast)
        // TODO better fix this error ...
        if (traceHtml.match(/OpenLayers\.js/) && traceHtml.match(/element is null/)) {
            return true;
        }
        
        // let exception bubble to browser
        return false;
    };
    
    /**
     * @todo   make this working in safari
     * @return {string}
     */
    var getNormalisedError = function() {
        var error = {
            name       : 'unknown error',
            message    : 'unknown',
            code       : 'unknown',
            description: 'unknown',
            url        : 'unknown',
            line       : 'unknown'
        };
        
        // NOTE: Arguments is not always a real Array
        var args = [];
        for (var i=0; i<arguments.length; i++) {
            args[i] = arguments[i];
        }
        
        if (args[0] instanceof Error) { // Error object thrown in try...catch
            error.name        = args[0].name;
            error.message     = args[0].message;
            error.code        = args[0].number & 0xFFFF; //Apply binary arithmetic for IE number, firefox returns message string in element array element 0
            error.description = args[0].description;
            
        } else if ((args.length == 3) && (typeof(args[2]) == "number")) { // Check the signature for a match with an unhandled exception
            error.name    = 'catchable exception';
            error.message = args[0];
            error.url     = args[1];
            error.line    = args[2];
        } else {
            error.message     = "An unknown JS error has occured.";
            error.description = 'The following information may be useful:' + "\n";
            for (var x = 0; x < args.length; x++) {
                try {
                    error.description += (Ext.encode(args[x]) + "\n");
                } catch (e) {
                    error.description += 'Could not encode error args: ' + e + "\n";
                }
            }
        }
        return error;
    };
    
    /**
     * generic request exception handling
     * 
     * NOTE: status codes 9xx are reserved for applications and must not be handled here!
     * 
     * @param {exception|Object} exception
     * @param {Function} callback
     * @param {Object}   callbackScope
     * @param {Function} callbackOnOk
     * @param {Object}   callbackOnOkScope
     */
    var handleRequestException = function(exception, callback, callbackScope, callbackOnOk, callbackOnOkScope) {
         if (! exception.code && exception.responseText) {
            // we need to decode the exception first
            var response = Ext.util.JSON.decode(exception.responseText);
            exception = response.data;
        }
        
        if (exception.appName != 'Tinebase' && Tine.Tinebase.ExceptionHandlerRegistry.has(exception.appName)) {
            // the registered function must return true to don't work on this generically
            if (Tine.Tinebase.ExceptionHandlerRegistry.get(exception.appName)(exception, callback, callbackScope, callbackOnOk, callbackOnOkScope) === true) {
                return;
            }
        } 
        
        var defaults = {
            buttons: Ext.Msg.OK,
            icon: Ext.MessageBox.WARNING,
            fn: callback,
            scope: callbackScope
        };
        
        Tine.log.debug('Tine.Tinebase.ExceptionHandler::handleRequestException -> Exception:');
        Tine.log.debug(exception);
        
        // TODO find a generic way for this, some kind of registry for each app to register sensitive information
        var request = (exception.request && Ext.isString(exception.request)) ? Ext.util.JSON.decode(exception.request) : null;
        if (request && request.method === 'Felamimail.saveMessage') {
            request.params.recordData.body = null;
            exception.request =  Ext.util.JSON.encode(request);
            Tine.log.debug(exception);
        }
    
        if (! callback) callback = Ext.emptyFn;
        if (! callbackScope) callbackScope = this;
        
        switch (exception.code) {
            // not authorised
            case 401:
                Tine.Tinebase.registry.remove('currentAccount');

                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Authorisation Required'),
                    msg: i18n._('Your session timed out. You need to login again.'),
                    fn: function() {

                        /*
                        // NOTE: this should be a password only longing box
                        //       as we can't handle user changes here!
                        Tine.Tinebase.tineInit.showLoginBox(function(response) {
                            // arg: we need a full account in response here
                            Tine.Tinebase.registry.set('currentAccount',...)
                            // should we retry last action with correct callbacks?
                            Ext.MessageBox.hide();
                        }, this);
                        return;
                        */

                        if (! window.isMainWindow) {
                            Ext.ux.PopupWindow.close();
                            return;
                        }
                        var redirect = (Tine.Tinebase.registry.get('redirectUrl'));
                        if (redirect && redirect != '') {
                            window.location = Tine.Tinebase.registry.get('redirectUrl');
                        } else {
                            Tine.Tinebase.common.reload({});
                        }
                    }
                }));
                break;
            
            // insufficient rights
            case 403:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Insufficient Rights'),
                    msg: i18n._('Sorry, you are not permitted to perform this action'),
                    icon: Ext.MessageBox.ERROR
                }));
                break;
            
            // not found
            case 404:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Not Found'),
                    msg: i18n._('Sorry, your request could not be completed because the required data could not be found. In most cases this means that someone already deleted the data. Please refresh your current view.'),
                    icon: Ext.MessageBox.ERROR
                }));
                break;
            
            // concurrency conflict
            case 409:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Concurrent Updates'),
                    msg: i18n._('Someone else saved this record while you where editing the data. You need to reload and make your changes again.')
                }));
                break;
            
            // Service Unavailable!
            // Use this error code for generic problems like misconfig we don't want to see bugreports for
            case 503:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Service Unavailable'),
                    msg: i18n._('The server is currently unable to handle the request due to a temporary overloading, maintenance or misconfiguration of the server. Please try again or contact your administrator.')
                }));
                break;
                
            // invalid record exception
            case 505:
                var message = exception.message ? '<br /><b>' + i18n._('Server Message:') + '</b><br />' + exception.message : '';
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Invalid Data'),
                    msg: i18n._('Your input data is not valid. Please provide valid data.') + message
                }));
                break;
            // server communication loss
            case 510:
                // NOTE: when communication is lost, we can't create a nice ext window.
                // NOTE: - reloads/redirects cancel all open xhr requests from the browser side
                //       - we need some way to distinguish server/client connection losses
                //       - the extjs xhr abstraction has no such feature
                //       - so we defer the alert. In case of reload/redirect the deferd fn dosn't get executed
                //         if the new contet/html arrives before the defer time is over.
                //       - this might not always be the case due to network, service or session problems
                (function() {alert(i18n._('Connection lost, please check your network!'))}).defer(1000);
                break;
                
            // transaction aborted / timeout
            case 520:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Timeout'),
                    msg: i18n._('Sorry, some timeout occured while processing your request. Please reload your browser, try again or contact your administrator.')
                }));
                
                break;
                
            // empty response
            case 540:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('No Response'),
                    msg: i18n._('Sorry, the Server did not respond any data. Please reload your browser, try again or contact your administrator.')
                }));
                break;
            
            // memory exhausted
            case 550: 
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Out of Resources'),
                    msg: i18n._('Sorry, the Server stated a "memory exhausted" condition. Please contact your administrator.')
                }));
                break;
                
            // generic error with message generated on the server
                
            case 600:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._(exception.title),
                    msg: i18n._(exception.message)
                }));
                break;
                
            // user in no role
            case 610:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('No Role Memberships'),
                    msg: i18n._('Your user account has no role memberships. Please contact your administrator.')
                }));
                break;
                
            // Tinebase_Exception_InvalidRelationConstraints
            case 912: 
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._(exception.title),
                    msg: i18n._(exception.message)
                }));
                break;
                
            // lost/insufficent permissions for api call or bad api call
            case -32601:
                Ext.MessageBox.show(Ext.apply(defaults, {
                    title: i18n._('Method Not Found / Insufficent Permissions'),
                    msg: i18n._('You tried to access a function that is not available. Please reload your browser, try again or contact your administrator.')
                }));
                break;
            
            // generic failure -> notify developers
            default:
                var windowHeight = 400;
                if (Ext.getBody().getHeight(true) * 0.7 < windowHeight) {
                    windowHeight = Ext.getBody().getHeight(true) * 0.7;
                }
                
                if (! Tine.Tinebase.exceptionDlg) {
                    Tine.Tinebase.exceptionDlg = new Tine.Tinebase.ExceptionDialog({
                        height: windowHeight,
                        exception: exception,
                        listeners: {
                            close: function() {
                                Tine.Tinebase.exceptionDlg = null;
                            }
                        }
                    });
                    Tine.Tinebase.exceptionDlg.show();
                }
                break;
        }
        
        if (Tine.Tinebase.configManager.get('automaticBugreports') && ! Tine.Tinebase.exceptionDlg) {
            Tine.log.debug('Tine.Tinebase.ExceptionHandler::handleRequestException -> Activate non-interacive exception dialog.');
            Tine.Tinebase.exceptionDlg = new Tine.Tinebase.ExceptionDialog({
                exception: exception,
                nonInteractive: true,
                listeners: {
                    close: function() {
                        Tine.Tinebase.exceptionDlg = null;
                    }
                }
            });
            Tine.Tinebase.exceptionDlg.show();
        }
    };
    
    // init window error handler
    window.onerror = !window.onerror ? 
        onWindowError :
        window.onerror.createSequence(onWindowError);
        
    return {
        handleRequestException: handleRequestException
    };
}();
