/*
 * Tine 2.0
 *
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */
Ext.ns('Tine.Filemanager');

Tine.Filemanager.GrantsPanel = Ext.extend(Ext.Panel, {

    /**
     * @cfg {Tine.widgets.dialog.EditDialog}
     */
    editDialog: null,

    /**
     * @cfg {Tine.Tinebase.Application} app
     */
    app: null,

    requiredGrant: 'editGrant',
    layout: 'fit',
    border: false,

    initComponent: function() {
        this.app = this.app || Tine.Tinebase.appMgr.get('Filemanager');
        this.recordClass = Tine.Filemanager.Model.Node;
        this.title = this.title || this.app.i18n._('Grants');

        this.editDialog.on('load', this.onRecordLoad, this);
        this.editDialog.on('recordUpdate', this.onRecordUpdate, this);

        this.hasOwnGrantsCheckbox = new Ext.form.Checkbox({
            readOnly: true,
            boxLabel: this.app.i18n._('This folder has its own grants'),
            listeners: {scope: this, check: this.onOwnGrantsCheck}
        });
        this.hasOwnRightsDescription = new Ext.form.Label({
            text: this.app.i18n._("Grants of a folder also apply recursively to all sub folders unless they have their own grants.")
        });
        this.pinProtectionCheckbox = new Ext.form.Checkbox({
            readOnly: true,
            hidden: ! Tine.Tinebase.areaLocks.getLocks(Tine.Tinebase.areaLocks.dataSafeAreaName).length,
            boxLabel: this.app.i18n._('This folder is part of the data safe')
        });
        this.pinProtectionDescription = new Ext.form.Label({
            text: this.app.i18n._("If the data safe is activated, this folder and its contents can only be accessed when the data safe is open.")
        });
        this.grantsGrid = new Tine.widgets.container.GrantsGrid({
            app: this.app,
            alwaysShowAdminGrant: true,
            readOnly: true,
            flex: 1,
            grantContainer: {
                application_id: this.app.id,
                model: 'Filemanager_Model_Node',
            },
        });

        this.items = [{
            layout: 'vbox',
            align: 'stretch',
            pack: 'start',
            border: false,
            items: [{
                layout: 'form',
                frame: true,
                hideLabels: true,
                width: '100%',
                items: [
                    this.hasOwnGrantsCheckbox,
                    this.hasOwnRightsDescription,
                    this.pinProtectionCheckbox,
                    this.pinProtectionDescription
                ]},
                this.grantsGrid
            ]
        }];

        this.supr().initComponent.call(this);
    },

    onOwnGrantsCheck: function(cb, checked) {
        this.grantsGrid.setReadOnly(!checked);
    },

    onRecordLoad: function(editDialog, record, ticketFn) {
        var _ = window.lodash,
            path = record.get('path'),
            evalGrants = editDialog.evalGrants,
            hasOwnGrants = record.get('acl_node') == record.id,
            hasRequiredGrant = !evalGrants || _.get(record, record.constructor.getMeta('grantsPath') + '.' + this.requiredGrant),
            ownGrantsReadOnly = record.get('type') != 'folder' ||
                !lodash.get(record, 'data.account_grants.adminGrant', false) ||
                path.match(/^\/personal(\/[^/]+){0,2}\/$/) ||
                path.match(/^\/shared(\/[^/]+){0,1}\/$/);
        
        const pinProtectionReadOnly = record.get('type') !== 'folder' ||!record.data?.account_grants?.adminGrant;
        
        this.hasOwnGrantsCheckbox.setValue(hasOwnGrants);
        this.hasOwnGrantsCheckbox.setReadOnly(ownGrantsReadOnly);
        this.pinProtectionCheckbox.setValue(record.get('pin_protected_node') ? true : false);
        this.pinProtectionCheckbox.setReadOnly(pinProtectionReadOnly);

        this.grantsGrid.useGrant('admin', !!String(record.get('path')).match(/^\/shared/));
        this.grantsGrid.getStore().loadData({results: record.data.grants});

        this.setReadOnly(!hasRequiredGrant);
        this.grantsGrid.setReadOnly(!hasOwnGrants || !hasRequiredGrant);
    },

    // grants-grid only - checkboxes have own state
    setReadOnly: function(readOnly) {
        this.readOnly = readOnly;
        this.grantsGrid.setReadOnly(readOnly);
    },

    onRecordUpdate: function(editDialog, record) {
        var acl_node = this.hasOwnGrantsCheckbox.getValue() ? record.id : null,
            grants = [],
            pin_protected_node = this.pinProtectionCheckbox.getValue() ? true : false;

        this.grantsGrid.getStore().each(function(r) {grants.push(r.data)});

        record.set('acl_node', acl_node);
        record.set('grants', grants);
        record.set('pin_protected_node', pin_protected_node ? acl_node : null);
    }
});
