/*
 * Tine 2.0
 * Sales combo box and store
 * 
 * @package     Sales
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

Ext.ns('Tine.Sales');

/**
 * Address selection combo box
 * 
 * @namespace   Tine.Sales
 * @class       Tine.Sales.AddressSearchCombo
 * @extends     Ext.form.ComboBox
 * 
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Alexander Stintzing <a.stintzing@metaways.de>
 * @copyright   Copyright (c) 2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @param       {Object} config
 * @constructor
 * Create a new Tine.Sales.AddressSearchCombo
 */
Tine.Sales.AddressSearchCombo = Ext.extend(Tine.Tinebase.widgets.form.RecordPickerComboBox, {
    
    allowBlank: false,
    itemSelector: 'div.search-item',
    minListWidth: 200,
    sortBy: 'locality',
    disabled: true,
    
    //private
    initComponent: function(){
        this.recordClass = Tine.Sales.Model.Address;
        this.recordProxy = Tine.Sales.addressBackend;
        this.initTemplate();

        Tine.Sales.AddressSearchCombo.superclass.initComponent.call(this);
    },

    checkState: function(editDialog, record) {
        const customer_id = this.editDialog.record.get('customer_id');
        this.setDisabled(!customer_id);
        if (! customer_id) {
            this.clearValue();
        } else {
            const mc = editDialog?.recordClass?.getModelConfiguration();
            const type = _.get(mc, `fields${this.fieldName}.config.type`, 'billing');
            this.lastQuery = null;            
            this.additionalFilters = [
                {field: 'customer_id', operator: 'equals', value: customer_id}
            ];
            if (type === 'postal') {
                this.additionalFilters.push({field: 'type', operator: 'equals', value: type });
            } else {
                this.additionalFilters.push({field: 'type', operator: 'not', value: type === 'billing' ? 'delivery' : 'billing' });
            }
        }
    },
    
    /**
     * init template
     * @private
     */
    initTemplate: function() {
        if (! this.tpl) {
            this.tpl = new Ext.XTemplate(
                '<tpl for="."><div class="search-item">',
                    '<table cellspacing="0" cellpadding="2" border="0" style="font-size: 11px;" width="100%">',
                        '<tr>',
                            '<td style="height:16px">{[this.encode(values)]}</td>',
                        '</tr>',
                    '</table>',
                '</div></tpl>',
                {
                    encode: function(values) { return Ext.util.Format.htmlEncode(values.fulltext) }
                }
            );
        }
    }
});

Tine.widgets.form.RecordPickerManager.register('Sales', 'Address', Tine.Sales.AddressSearchCombo);
Tine.widgets.form.RecordPickerManager.register('Sales', 'Document_Address', Tine.Sales.AddressSearchCombo);
