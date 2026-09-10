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

  selectedResult = [];
  units;

  isChange = false;
  vendor_unit = '';

  selectedLocation = [];

  remark = '';
  constructor(private service: DataAccessService) { }

  ngOnInit(): void {
    this.getPendingChallans();
  }

  getPendingChallans(){
    this.service.get('store/challan.php?type=getPendingChallans').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.getVendorUnits();
  }

  selectLocation(index) {
    index = index - 1;
    if (index !== -1) {
      this.selectedLocation = this.units[index];
    }
  }

  getVendorUnits() {
    this.service.get('purchase/vendor.php?type=getVendorUnit&vendor_no=' + this.selectedResult['vendor_no']).subscribe(response => {
      this.units = response;
    });
  }

  updateChallan(status) {
    this.selectedResult['remark'] = this.remark;
    this.service.post('store/challan.php?type=updateChallan&status=' + status + '&id=' + this.selectedResult['id'] + '&po_no=' + this.selectedResult['po_no'] + '&vendor_unit=' + this.vendor_unit, JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        this.remark = '';
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingChallans();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  calculation(index) {
    let materials = this.selectedResult['materials'];
    let selectedMaterial = materials[index];
    selectedMaterial['gross_total'] = +selectedMaterial['qty'] * +selectedMaterial['received_rate'];
    selectedMaterial['gst_total'] = (+selectedMaterial['gross_total'] * +selectedMaterial['gst']) / 100;
    selectedMaterial['net_total'] = +selectedMaterial['gross_total'] + +selectedMaterial['gst_total'];

    selectedMaterial['gross_total'] = +parseFloat(selectedMaterial['gross_total']).toFixed(2);
    selectedMaterial['gst_total'] = +parseFloat(selectedMaterial['gst_total']).toFixed(2);
    selectedMaterial['net_total'] = +parseFloat(selectedMaterial['net_total']).toFixed(2);

    selectedMaterial['diff'] = +parseFloat((+selectedMaterial['received_rate'] - +selectedMaterial['rate']) + "").toFixed(2);

    materials[index] = selectedMaterial;
    this.selectedResult['materials'] = materials;

    let gross_total = 0;
    let gst_total = 0;
    let net_total = 0;
    for (let i = 0; i < materials.length; i++) {
      let material = materials[i];
      gross_total += +material['gross_total'];
      gst_total += +material['gst_total'];
      net_total += +material['net_total'];
    }
    this.selectedResult['gross_total'] = gross_total;
    this.selectedResult['gst_total'] = gst_total;
    this.selectedResult['net_total'] = net_total;
  }

}
