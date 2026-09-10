import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {
  state;
  selectedstate = [];
  isDomastic = false;

  agents;
  refered_by = 'Direct Customer';
  client_type = 'Distributor';
  order_category = 'DOMESTIC';
  gst_type = 'IGST';

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
    this.getStates();
  }

  getStates() {
    this.service.get('common.php?type=getStates').subscribe(response => {
      this.state = response;
    });
  }

  getStateData(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedstate = this.state[index];
    }
  }

  private isFilled(value: any): boolean {
    return value !== null && value !== undefined && String(value).trim() !== '';
  }

  save(data) {
    const v = data && data.value ? data.value : {};
    const missing: string[] = [];

    if (!this.isFilled(v.LglNm)) { missing.push('Legal Name'); }
    if (!this.isFilled(v.person)) { missing.push('Contact Person Name'); }
    if (!this.isFilled(v.Trdnm)) { missing.push('Trade Name'); }
    if (!this.isFilled(v.state_code)) { missing.push('State'); }
    if (!this.isFilled(v.type)) { missing.push('Type'); }
    if (!this.isFilled(v.address)) { missing.push('Address1'); }
    if (!this.isFilled(v.pincode)) { missing.push('Postal Code'); }
    if (!this.isFilled(v.contact_no)) { missing.push('Contact No'); }
    if (!this.isFilled(v.email)) { missing.push('Email'); }
    if (!this.isFilled(v.transport_for)) { missing.push('Transport For'); }

    if (v.type === 'DOMESTIC') {
      if (!this.isFilled(v.gst_type)) { missing.push('GST Type'); }
      if (!this.isFilled(v.gst_no)) { missing.push('GST No'); }
    }
    if (v.type === 'EXPORT') {
      if (!this.isFilled(v.country)) { missing.push('Country'); }
    }

    if (missing.length) {
      alert('Please fill required fields: ' + missing.join(', '));
      return;
    }

    const payload = Object.assign({}, v, {
      company: v.LglNm || v.Trdnm || '',
      gst_type: v.type === 'DOMESTIC' ? (v.gst_type || '') : '',
      gst_no: v.type === 'DOMESTIC' ? (v.gst_no || '') : '',
      country: v.type === 'EXPORT' ? (v.country || '') : ''
    });

    this.service.post('marketing/transporter.php?type=saveTransporter', JSON.stringify(payload))
      .subscribe(response => {
        if (response && response['status'] === 'success') {
          alert('save successfully');
          data.reset();
          this.order_category = 'DOMESTIC';
          this.gst_type = 'IGST';
          this.router.navigate(['/admin/transporter/approval']);
        } else {
          alert(response && response['status'] ? response['status'] : 'Please try again');
        }
      }, () => {
        alert('An error occurred while saving');
      });
  }
}
