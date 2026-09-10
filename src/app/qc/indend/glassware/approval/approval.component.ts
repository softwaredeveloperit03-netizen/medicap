import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  isView = false;
  results;
  selectedResult: [];

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getCheckedIndends();
  }

  getCheckedIndends() {
    this.service.get('purchase/indend/glassware.php?type=getCheckedIndends').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  actionIndend(value, index) {
    let materials = this.selectedResult['materials'];
    materials[index].status = value;
    this.selectedResult['materials'] = materials;
  }

  approveIndend(status) {
    if (this.selectedResult['quotation_no'] == '') {
      alertify.error('Select Quotation!');
      return;
    }
    if (this.selectedResult['order_qty'] == null) {
      alertify.error('Order Qty is Required');
      return;
    }
    if (this.selectedResult['gst'] == null) {
      alertify.error('GST is Required');
      return;
    }
    this.service.post('purchase/indend/glassware.php?type=approveIndend&status=' + status, JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getCheckedIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  selectQuotation(index) {
    let vendors = this.selectedResult['vendors'];
    this.selectedResult['vendor_no'] = vendors[index].vendor_no;
    this.selectedResult['quotation_no'] = vendors[index].quotation_no;
    this.selectedResult['quotation_amt'] = vendors[index].quotation_amt;
    this.selectedResult['quotation_per'] = vendors[index].quotation_per;
    this.calculation();
  }

  calculation() {
    this.selectedResult['gross_total']= +this.selectedResult['order_qty'] * +this.selectedResult['quotation_amt'] ;
    this.selectedResult['gst_total'] = +this.selectedResult['gross_total'] * +this.selectedResult['gst'] / 100;
    this.selectedResult['net_total'] = +this.selectedResult['gross_total'] + +this.selectedResult['gst_total'];
  }




}

