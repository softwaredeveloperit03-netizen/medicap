import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import {
  SO_FORM_ID, SO_API, MEDICAP_FROM, defaultSampleLines, parseSoResponse
} from '../so.constants';
declare let alertify: any;

@Component({
  selector: 'app-so-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  readonly formId = SO_FORM_ID;
  readonly fromInfo = MEDICAP_FROM;

  order_no = '';
  saving = false;
  submitting = false;
  loadingClients = false;
  loadingVendors = false;
  clients: any[] = [];
  vendors: any[] = [];
  selectedClientCode = '';
  selectedVendorNo = '';
  lines = defaultSampleLines(4);

  form: any = {
    ship_to: '',
    ship_to_client_code: '',
    po_number: '',
    ship_to_address: '',
    from_tel: '',
    from_fax: '',
    quotation_ref: '',
    tests_required: '',
    shipping_vendor: '',
    shipping_vendor_no: ''
  };

  constructor(public service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getNextOrderNo();
    this.loadClients();
    this.loadVendors();
  }

  getNextOrderNo() {
    this.service.get(SO_API + 'type=getNextOrderNo').subscribe((res: any) => {
      this.order_no = res.order_no || '';
    });
  }

  loadClients() {
    this.loadingClients = true;
    this.service.get(SO_API + 'type=getClients').subscribe((res: any) => {
      this.clients = Array.isArray(res) ? res : [];
      this.loadingClients = false;
    }, () => { this.clients = []; this.loadingClients = false; });
  }

  loadVendors() {
    this.loadingVendors = true;
    this.service.get(SO_API + 'type=getVendors').subscribe((res: any) => {
      this.vendors = Array.isArray(res) ? res : [];
      this.loadingVendors = false;
    }, () => { this.vendors = []; this.loadingVendors = false; });
  }

  onClientSelect(clientCode: string) {
    this.selectedClientCode = clientCode || '';
    const client = this.clients.find(c => String(c.client_code) === String(clientCode));
    if (!client) {
      return;
    }
    this.form.ship_to_client_code = client.client_code || '';
    this.form.ship_to = client.display_name || client.TrdNm || client.LglNm || '';
    this.form.ship_to_address = client.formatted_address || client.address || '';
  }

  onVendorSelect(vendorNo: string) {
    this.selectedVendorNo = vendorNo || '';
    const vendor = this.vendors.find(v => String(v.vendor_no) === String(vendorNo));
    if (!vendor) {
      return;
    }
    this.form.shipping_vendor_no = vendor.vendor_no || '';
    this.form.shipping_vendor = vendor.vendor_name || '';
  }

  clientLabel(c: any): string {
    const name = c.display_name || c.TrdNm || c.LglNm || '';
    return c.client_code ? `${name} (${c.client_code})` : name;
  }

  addRow() {
    this.lines.push({ lab_sample_no: '', quantity: '', lot_batch_no: '', description: '' });
  }

  buildPayload() {
    return {
      order_no: this.order_no,
      ...this.form,
      lines: this.lines
    };
  }

  hasValidLines(): boolean {
    return this.lines.some(l =>
      (l.lab_sample_no || '').trim() || (l.quantity || '').trim() ||
      (l.lot_batch_no || '').trim() || (l.description || '').trim()
    );
  }

  saveDraft() {
    if (!this.hasValidLines()) {
      alertify.error('Add at least one sample line.');
      return;
    }
    this.saving = true;
    this.service.postTextResponse(SO_API + 'type=saveOrder', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.saving = false;
        const res = parseSoResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Draft saved.');
          this.router.navigate(['/qc/shipping-order/view', res.id]);
        } else {
          alertify.error(res?.message || res?.status || 'Save failed');
        }
      }, () => { this.saving = false; alertify.error('Save failed'); });
  }

  submit() {
    if (!this.form.ship_to || !this.form.po_number) {
      alertify.error('Ship To and P.O.# are required.');
      return;
    }
    if (!this.hasValidLines()) {
      alertify.error('Add at least one sample line.');
      return;
    }
    this.submitting = true;
    this.service.postTextResponse(SO_API + 'type=submitOrder', JSON.stringify(this.buildPayload()))
      .subscribe((raw: string) => {
        this.submitting = false;
        const res = parseSoResponse(raw);
        if (res?.status === 'success') {
          alertify.success('Shipping order submitted — pending Checked By.');
          this.router.navigate(['/qc/shipping-order/view', res.id]);
        } else {
          alertify.error(res?.message || res?.status || 'Submit failed');
        }
      }, () => { this.submitting = false; alertify.error('Submit failed'); });
  }
}
