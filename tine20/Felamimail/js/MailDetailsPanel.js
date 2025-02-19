/*
 * Tine 2.0
 *
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2017-2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

Ext.ns('Tine.Felamimail');
/**
 * @param config
 * @constructor
 */
Tine.Felamimail.MailDetailsPanel = function(config) {
    Ext.apply(this, config);
    Tine.Felamimail.MailDetailsPanel.superclass.constructor.call(this);
};

/**
 * @namespace   Tine.Felamimail
 * @class       Tine.Felamimail.MailDetailsPanel
 * @extends     Ext.Panel
 *
 * TODO         replace telephone numbers in emails with 'call contact' link
 * TODO         make only text body scrollable (headers should be always visible)
 * TODO         show image attachments inline
 * TODO         add 'download all' button
 * TODO         'from' to contact: check for duplicates
 */
Ext.extend(Tine.Felamimail.MailDetailsPanel, Ext.Panel, {

    /**
     * layout stuff
     */
    layout: 'vbox',
    layoutConfig: {
        align:'stretch'
    },
    border: false,

    record: null,
    app: null,
    i18n: null,

    // if this is given, we load the record from a node
    nodeRecord: null,

    // if this is true, add top toolbar with actions to open mail in message display dialog
    hasTopToolbar: true,

    initComponent: function () {
        this.app = Tine.Tinebase.appMgr.get('Felamimail');
        this.i18n = this.app.i18n;
        this.messageRecordPanel = new Ext.Panel({
            border: false,
            autoScroll: true,
            flex: 1
        });
        this.items = [
            this.messageRecordPanel
        ];

        this.initTemplate();

        if (this.hasTopToolbar) {
            this.initTopToolbar();
        }

        Tine.Felamimail.MailDetailsPanel.superclass.initComponent.call(this);
    },

    /**
     * init top toolbar for opening mails in fmail
     */
   initTopToolbar: function() {
        this.action_openInFmail = new Ext.Action({
            text: this.app.i18n._('Open in Felamimail'),
            minWidth: 70,
            scope: this,
            handler: this.onOpenInFmail,
            iconCls: this.app.appName + 'IconCls'
        });

       this.tbar = new Ext.Toolbar({
           items: [
               '->',
               this.action_openInFmail
           ]
       });
   },

    /**
     * open in Felamimail MessageDisplayDialog
     */
    onOpenInFmail: function() {
        if (this.nodeRecord) {
            // prepare message for forwarding in Tine.Felamimail.MessageEditDialog.handleAttachmentsOfExistingMessage
            this.record.set('from_node', this.nodeRecord.data);
        }

        Tine.Felamimail.MessageDisplayDialog.openWindow({
            record: this.record,
            // remove delete + save actions as this makes no sense if opened from another app
            hasDeleteAction: false,
            hasDownloadAction: false
        });
    },

    /**
     * add on click event after render
     * @private
     */
    afterRender: function () {
        Tine.Felamimail.MailDetailsPanel.superclass.afterRender.apply(this, arguments);
        this.body.on('click', this.onClick, this);
        if (this.nodeRecord) {
            this.loadRecord();
        }
    },

    getTemplateBody: function () {
        return this.messageRecordPanel.body;
    },

    getMessageRecordPanel: function() {
        return this.messageRecordPanel;
    },

    /**
     * fills this fields with the corresponding message data
     *
     * @param {Tine.Tinebase.data.Record|Object} record
     */
    loadRecord: function (record) {
        if (record) {
            this.record = record;
            this.tpl.overwrite(this.messageRecordPanel.body, record.data);
            this.doLayout();

        } else if (this.nodeRecord) {
            Tine.Felamimail.messageBackend.getMessageFromNode(this.nodeRecord, {
                success: function(response) {
                    this.record = Tine.Felamimail.messageBackend.recordReader({responseText: Ext.util.JSON.encode(response.data)});
                    this.tpl.overwrite(this.messageRecordPanel.body, this.record.data);
                },
                failure: function (exception) {
                    Tine.log.debug(exception);
                    // @todo add loadMask? move loadMask from GridDetailsPanel here?
                    // this.getLoadMask().hide();
                    // if (exception.code == 404) {
                    this.tpl.overwrite(this.messageRecordPanel.body, {msg: this.app.i18n._('Message not available.')});
                    // } else {
                    //     // @todo handle exception?
                    // }
                },
                scope: this
            });
        }
    },

    /**
     * init single message template (this.tpl)
     * @private
     */
    initTemplate: function() {

        this.tpl = new Ext.XTemplate(
            '<div class="preview-panel-felamimail">',
            '<div class="preview-panel-felamimail-headers">',
            '<b>' + this.i18n._('Subject') + ':</b> {[this.encode(values.subject)]}<br/>',
            '<b>' + this.i18n._('From') + ':</b>',
            ' {[this.showFrom(values.from_email, values.from_name, "' + this.i18n._('Add') + '", "'
            + this.i18n._('Add contact to addressbook') + '")]}<br/>',
            '<b>' + this.i18n._('Date') + ':</b> {[this.showDate(values.sent, values)]}',
            '{[this.showRecipients(values.headers)]}',
            '{[this.showHeaders("' + this.i18n._('Show or hide header information') + '")]}',
            '</div>',
            '<div class="preview-panel-felamimail-attachments">{[this.showAttachments(values.attachments, values)]}</div>',
            '<div class="preview-panel-felamimail-filelocations">{[this.showFileLocations(values)]}</div>',
            '<div class="preview-panel-felamimail-preparedPart"></div>',
            '<div class="preview-panel-felamimail-body">{[this.showBody(values.body, values)]}</div>',
            '</div>',{
                app: this.app,
                panel: this,
                encode: function(value) {
                    if (value) {
                        var encoded = Ext.util.Format.htmlEncode(value);
                        encoded = Ext.util.Format.nl2br(encoded);
                        // it should be enough to replace only 2 or more spaces
                        encoded = encoded.replace(/ /g, '&nbsp;');

                        return encoded;
                    } else {
                        return '';
                    }
                },

                showDate: function (sent, recordData) {
                    var date = sent
                        ? (Ext.isDate(sent) ? sent : Date.parseDate(sent, Date.patterns.ISO8601Long))
                        : Date.parseDate(recordData.received, Date.patterns.ISO8601Long);
                    return date ? date.format('l') + ', ' + Tine.Tinebase.common.dateTimeRenderer(date) : '';
                },

                showFrom: function(email, name, addText, qtip) {
                    if (! name) {
                        return '';
                    }

                    var result = this.encode(name + ' <' + email + '>');

                    // add link with 'add to contacts'
                    var id = Ext.id() + ':' + email;

                    var nameSplit = name.match(/^"*([^,^ ]+)(,*) *(.+)/i);
                    var firstname = (nameSplit && nameSplit[1]) ? nameSplit[1] : '';
                    var lastname = (nameSplit && nameSplit[3]) ? nameSplit[3] : '';
                    if (nameSplit && nameSplit[2] == ',') {
                        firstname = lastname;
                        lastname = nameSplit[1];
                    }

                    id += Ext.util.Format.htmlEncode(':' + Ext.util.Format.trim(name));
                    result = '<a id="' + id + '" class="tinebase-email-link">' + result + '</a>'
                    
                    return result;
                },

                showBody: function(body, messageData) {
                    body = body || '';
                    if (body) {
                        var account = this.app.getActiveAccount();
                        if (account && (account.get('display_format') == 'plain' ||
                                (account.get('display_format') == 'content_type' && messageData.body_content_type == 'text/plain'))
                        ) {
                            var width = this.panel.body.getWidth()-25,
                                height = this.panel.body.getHeight()-90,
                                id = Ext.id();

                            if (height < 0) {
                                // sometimes the height is negative, fix this here
                                height = 500;
                            }

                            body = '<textarea ' +
                                'style="width: ' + width + 'px; height: ' + height + 'px; " ' +
                                'autocomplete="off" id="' + id + '" name="body" class="x-form-textarea x-form-field x-ux-display-background-border" readonly="" >' +
                                body + '</textarea>';
                        } else if (messageData.body_content_type != 'text/html' || messageData.body_content_type_of_body_property_of_this_record == 'text/plain') {
                            // message content is text and account format non-text
                            body = Ext.util.Format.nl2br(body);
                        } else {
                            Tine.Tinebase.common.linkifyText(body, function(linkified) {
                                var bodyEl = this.getMessageRecordPanel().getEl().query('div[class=preview-panel-felamimail-body]')[0];
                                Ext.fly(bodyEl).update(linkified);
                            }, this.panel);
                        }
                    }
                    return body;
                },

                showHeaders: function(qtip) {
                    var result = ' <span ext:qtip="' + Tine.Tinebase.common.doubleEncode(qtip) + '" id="' + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>';
                    return result;
                },

                showRecipients: function(value) {
                    if (value) {
                        var i18n = Tine.Tinebase.appMgr.get('Felamimail').i18n,
                            result = '';
                        for (header in value) {
                            if (value.hasOwnProperty(header) && (header == 'to' || header == 'cc' || header == 'bcc')) {
                                result += '<br/><b>' + i18n._hidden(Ext.util.Format.capitalize(header)) + ':</b> '
                                    + Ext.util.Format.htmlEncode(value[header]);
                            }
                        }
                        return result;
                    } else {
                        return '';
                    }
                },

                showAttachments: function(attachments, messageData) {
                    const idPrefix = Ext.id();
                    const attachmentsStr = this.app.i18n._('Attachments');

                    let result = (attachments.length > 0) ? `<span id=${idPrefix}:all class="tinebase-download-link tinebase-download-all"><b>${attachmentsStr}:</b><div class="tinebase-download-link-wait"></div></span>` : '';

                    for (var i=0, id, cls; i < attachments.length; i++) {
                        result += `<span id="${idPrefix}:${i}" class="tinebase-download-link">`
                            + '<i>' + attachments[i].filename + '</i>'
                            + ' (' + Ext.util.Format.fileSize(attachments[i].size) + ')<div class="tinebase-download-link-wait"></div></span> ';
                    }

                    return result;
                },

                showFileLocations: function(messageData) {
                    let fileLocations = _.get(messageData, 'fileLocations', []);

                    if (fileLocations.length) {
                        let app = Tine.Tinebase.appMgr.get('Felamimail');
                        let text = app.formatMessage('{locationCount, plural, one {This message is filed at the following location} other {This message is filed at the following locations}}: {locationsHtml}', {
                            locationCount: fileLocations.length,
                            locationsHtml: Tine.Felamimail.MessageFileAction.getFileLocationText(fileLocations, ', ')
                        });

                        return text;
                    } else {
                        return '';
                    }
                }
            });
    },

    /**
     * on click for attachment download / compose dlg / edit contact dlg
     *
     * @param {} e
     * @private
     */
    onClick: function(e) {
        var selectors = [
            'span[class^=tinebase-download-link]',
            'a[class=tinebase-email-link]',
            'span[class=tinebase-showheaders-link]',
            'a[href^=#]'
        ];

        // find the correct target
        for (var i = 0, target = null, selector = ''; i < selectors.length; i++) {
            target = e.getTarget(selectors[i]);
            if (target) {
                selector = selectors[i];
                break;
            }
        }

        Tine.log.debug('Tine.Felamimail.GridDetailsPanel::onClick found target:"' + selector + '".');

        switch (selector) {
            case 'span[class^=tinebase-download-link]':
                var idx = target.id.split(':')[1],
                    attachments = idx !== 'all' ? [this.record.get('attachments')[idx]] : this.record.get('attachments'),
                    sourceModel = this.nodeRecord || this.record.get('from_node') ?
                        'Filemanager_Model_Node' : 'Felamimail_Model_Message';

                if (! this.record.bodyIsFetched()) {
                    // sometimes there is bad timing and we do not have the attachments available -> refetch body
                    // @todo make this work again - move Tine.Felamimail.GridDetailsPanel.refetchBody here?
                    // this.refetchBody(this.record, this.onClick.createDelegate(this, [e]));
                    return;
                }

                // remove part id if set (that is the case in message/rfc822 attachments)
                const messageId = (this.record.id.match(/_/)) ? this.record.id.split('_')[0] : this.record.id;

                const menu = Ext.create({
                    xtype: 'menu',
                    plugins: [{
                        ptype: 'ux.itemregistry',
                        key:   'Tine.Felamimail.MailDetailPanel.AttachmentMenu'
                    }],
                    items: [{
                            text: this.app.i18n._('Open'),
                            iconCls: 'action_preview',
                            hidden: attachments.length !== 1 || _.get(attachments, '[0]content-type') !== 'message/rfc822',
                            handler: () => {
                                Tine.Felamimail.MessageDisplayDialog.openWindow({
                                    record: new Tine.Felamimail.Model.Message({
                                        id: messageId + '_' + attachments[0].partId
                                    })
                                });
                            }
                        }, {
                            text: this.app.i18n._('Preview'),
                            iconCls: 'action_preview',
                            hidden: !attachments.length || !Tine.Tinebase.appMgr.isEnabled('Filemanager')
                                || (_.get(attachments, '[0]content-type') === 'message/rfc822' && attachments.length === 1),
                            handler: () => {
                                Tine.Filemanager.QuickLookPanel.openWindow({
                                    record: this.record,
                                    initialApp: this.app,
                                    handleAttachments: this.quicklookHandleAttachments,
                                    initialAttachmentIdx: +idx
                                });
                            }
                        }, {
                            xtype: 'menuseparator',
                            hidden: attachments.length !== 1 || _.get(attachments, '[0]content-type') !== 'message/rfc822'
                        }, {
                            text: this.app.i18n._('Save As'),
                            menu: [{
                                text: this.app.i18n._('File (in Filemanager) ...'),
                                hidden: ! Tine.Tinebase.common.hasRight('run', 'Filemanager'),
                                handler: () => {
                                    var filePickerDialog = new Tine.Filemanager.FilePickerDialog({
                                        constraint: 'folder',
                                        singleSelect: true,
                                        requiredGrants: ['addGrant']
                                    });

                                    filePickerDialog.on('selected', async (nodes) => {
                                        await this.attachmentAnnimation(target, async () => {
                                            const locations = [{
                                                type: 'node',
                                                model: 'Filemanager_Model_Node',
                                                record_id: _.get(nodes[0], 'nodeRecord.data', nodes[0]),
                                            }];

                                            await Tine.Felamimail.fileAttachments(messageId, locations, attachments, sourceModel);
                                        });

                                        const msg = this.app.formatMessage('{attachmentCount, plural, one {Attachment was saved} other {# Attachments where saved}}',
                                            {attachmentCount: attachments.length });
                                        Ext.ux.MessageBox.msg(this.app.formatMessage('Success'), msg);
                                    });
                                    filePickerDialog.openWindow();
                                }
                            }, {
                                text: this.app.i18n._('Attachment (of Record)'),
                                menu:_.reduce(Tine.Tinebase.data.RecordMgr.items, (menu, model) => {
                                    if (model.hasField('attachments') && model.getMeta('appName') !== 'Felamimail') {
                                        menu.push({
                                            text: model.getRecordName() + ' ...',
                                            iconCls: model.getIconCls(),
                                            handler: () => {
                                                var pickerDialog = Tine.WindowFactory.getWindow({
                                                    layout: 'fit',
                                                    width: 250,
                                                    height: 100,
                                                    padding: '5px',
                                                    modal: true,
                                                    title: this.app.i18n._('Save as Record Attachment'),
                                                    items: new Tine.Tinebase.dialog.Dialog({
                                                        listeners: {
                                                            apply: async (fileTarget) => {
                                                                await this.attachmentAnnimation(target, async () => {
                                                                    await Tine.Felamimail.fileAttachments(messageId, [fileTarget], attachments, sourceModel);
                                                                });

                                                                const msg = this.app.formatMessage('{attachmentCount, plural, one {Attachment was saved} other {# Attachments where saved}}',
                                                                    {attachmentCount: attachments.length });
                                                                Ext.ux.MessageBox.msg(this.app.formatMessage('Success'), msg);
                                                            }
                                                        },
                                                        getEventData: function(eventName) {
                                                            if (eventName === 'apply') {
                                                                var attachRecord = this.getForm().findField('attachRecord').selectedRecord;
                                                                return {
                                                                    type: 'attachment',
                                                                    model: model.getPhpClassName(),
                                                                    record_id: attachRecord.data,
                                                                };
                                                            }
                                                        },
                                                        items: Tine.widgets.form.RecordPickerManager.get(model.getMeta('appName'), model.getMeta('modelName'), {
                                                            fieldLabel: model.getRecordName(),
                                                            name: 'attachRecord'
                                                        })
                                                    })
                                                });
                                            }
                                        });
                                    }
                                    return menu;
                                }, [])
                            }]
                        }, {
                            xtype: 'menuseparator',
                            hidden: !Tine.Tinebase.configManager.get('downloadsAllowed')
                        }, {
                            text: this.app.i18n._('Download'),
                            iconCls: 'action_download',
                            hidden: !Tine.Tinebase.configManager.get('downloadsAllowed'),
                            handler: () => {
                                this.attachmentAnnimation(target, async () => {
                                    return Ext.ux.file.Download.start({
                                        params: {
                                            requestType: 'HTTP',
                                            method: 'Felamimail.downloadAttachments',
                                            id: messageId,
                                            partIds: _.map(attachments, 'partId'),
                                            model: sourceModel
                                        }
                                    });
                                });
                            }
                        }
                    ]
                });
                
                const actionUpdater = new Tine.widgets.ActionUpdater({
                    recordClass: Tine.Felamimail.Model.Attachment,
                    evalGrants: false,
                    actions: menu
                });
                actionUpdater.updateActions(attachments.map((attachmentData) => {
                    return Tine.Tinebase.data.Record.setFromJson(Object.assign(attachmentData, {
                        id: `${messageId}:${attachmentData.partId}`,
                        messageId
                    }), Tine.Felamimail.Model.Attachment);
                }), messageId);

                menu.showAt(e.getXY());
                break;
            case 'span[class=tinebase-showheaders-link]':
                // show headers

                var parts = target.id.split(':');
                var targetId = parts[0];
                var action = parts[1];

                var html = '';
                if (action == 'show') {
                    var recordHeaders = this.record.get('headers');

                    for (header in recordHeaders) {
                        if (recordHeaders.hasOwnProperty(header) && (header != 'to' || header != 'cc' || header != 'bcc')) {
                            html += '<br/><b>' + header + ':</b> '
                                + Ext.util.Format.htmlEncode(recordHeaders[header]);
                        }
                    }

                    target.id = targetId + ':' + 'hide';

                } else {
                    html = ' <span ext:qtip="' + Ext.util.Format.htmlEncode(this.i18n._('Show or hide header information')) + '" id="'
                        + Ext.id() + ':show" class="tinebase-showheaders-link">[...]</span>'
                }

                target.innerHTML = html;

                break;
            case 'a[href^=#]':
                e.stopEvent();
                var anchor = this.getEl().query('#' + target.href.replace(/.*#/, ''));
                if (anchor.length) {
                    var scrollEl = Ext.fly(anchor[0]).findParent('.x-panel-body');
                    if (scrollEl) {
                        var box = Ext.fly(anchor[0]).getBox();
                        // TODO improve accuracy of scrolling
                        scrollEl.scrollTop = box.y - 180;
                    }
                }
                break;
        }
    },

    quicklookHandleAttachments: async function() {
        this.cardPanel.layout.setActiveItem(0); // wait cycle
        if (this.record.constructor.hasField('attachments')) {
            // new mail -> fetch body with fmail attachments
            const body = await Tine.Felamimail.getMessage(this.record.id);
            this.attachments = _.map(body.attachments, (attachmentData) => {
                return Tine.Tinebase.data.Record.setFromJson(Object.assign(attachmentData, {
                    id: `${this.record.id}:${attachmentData.partId}`,
                    messageId: this.record.id
                }), Tine.Felamimail.Model.Attachment);
            });
            this.record.set('attachments', body.attachments);
            this.record = this.attachments[_.isNaN(this.initialAttachmentIdx) ? 0 : this.initialAttachmentIdx];
        }

        if (this.record.constructor !== Tine.Tinebase.Model.Tree_Node) {
            // convert fmail attachment to attachmentCache attachment
            const attachmentCache = await Tine.Felamimail.getAttachmentCache(['Felamimail_Model_Message', this.record.get('messageId'), this.record.get('partId')].join(':'), true);
            this.record = this.attachments[this.attachments.indexOf(this.record)] = new Tine.Tinebase.Model.Tree_Node(attachmentCache.attachments[0]);
        }
    },

    attachmentAnnimation: async function (target, workload) {
        Ext.fly(target).addClass('tinebase-download-link-anim');
        try {
            await workload()
        } finally {
            Ext.fly(target).removeClass('tinebase-download-link-anim');
        }

    },
    
    /**
     * process spam strategy and refresh grid panel
     *
     * @param option
     */
    processSpamStrategy: async function (option) {
        this.spamToolbar.hide();
        this.messageRecordPanel.doLayout();
        await this.app.getMainScreen().getCenterPanel().processSpamStrategy(option);
    },

    /**
     * show spam toolbar
     *
     * @param record
     */
    showSpamToolbar: function (record) {
        
        if (!this.spamToolbar) {
            this.spamToolbar = new Ext.Toolbar({
                items: [{
                        xtype: 'tbtext',
                        text: this.app.i18n._('This message is probably SPAM. Please help to train your anti-SPAM system with a decision: "Yes, it is SPAM" or "No, it is not"')
                    },
                    '->',
                    {
                        iconCls: 'action_about',
                        handler: () => {
                            Ext.Msg.alert(
                                this.app.i18n._('Confirm SPAM Suspicion'),
                                Tine.Tinebase.configManager.get('spamInfoDialogContent', 'Felamimail')
                            );
                        }
                    }, {
                        xtype: 'tbspacer', width: 20
                    }, {
                        iconCls: 'felamimail-action-spam',
                        text: this.app.i18n._('Yes, it is SPAM'),
                        handler: this.processSpamStrategy.bind(this, 'spam'),
                    }, {
                        iconCls: 'felamimail-action-ham',
                        text: this.app.i18n._('No, it is not'),
                        handler: this.processSpamStrategy.bind(this, 'ham'),
                    }]
            })

            this.messageRecordPanel.add(this.spamToolbar);
        }
        
        if(record.get('is_spam_suspicions')) {
            const account = Tine.Tinebase.appMgr.get('Felamimail').getAccountStore().getById(record.get('account_id'));
            const folder = this.app.getFolderStore().getById(record.get('folder_id'));

            if (folder && account) {
                this.spamToolbar.show();
            }
        } else {
            this.spamToolbar.hide();
        }
        
        this.messageRecordPanel.doLayout();
    },
});

Ext.reg('felamimaildetailspanel', Tine.Felamimail.MailDetailsPanel);
