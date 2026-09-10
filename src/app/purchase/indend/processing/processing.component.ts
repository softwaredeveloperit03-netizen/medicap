import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-processing',
  templateUrl: './processing.component.html',
  styleUrls: ['./processing.component.css']
})
export class ProcessingComponent implements OnInit {

  /** 'RMPM' for Raw/Packing, 'GEN' for General. */
  forType = 'RMPM';
  results: any;
  searchQuery = '';
  loading = false;

  selectedResult: any = [];
  materials: any[] = [];
  order_type = '';
  purchase_type = '';
  isView = false;
  allIndent = false;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingIndends(this.forType);
  }

  getPendingIndends(forType: string) {
    this.forType = forType;
    this.isView = false;
    this.loading = true;
    this.service.get('purchase/requisition_processing.php?type=getCheckedRequisitions&For=' + this.forType)
      .subscribe((response: any) => {
        this.results = response;
        this.loading = false;
      }, () => {
        this.results = [];
        this.loading = false;
      });
  }

  formatStatus(status: string): string {
    if (!status) {
      return '-';
    }
    if (status === 'pending' || status === 'approve') {
      return 'Approve';
    }
    return status;
  }

  /** Requirement date from raw indent form (indent-level or first material). */
  get requirementDate(): string | null {
    const d = this.selectedResult?.['requirement'] ?? this.materials?.[0]?.['requirement'];
    return d != null && d !== '' ? d : null;
  }

  view(data: any) {
    this.selectedResult = data;
    this.materials = this.selectedResult['materials'];
    this.order_type = this.selectedResult['order_type'];
    this.purchase_type = this.selectedResult['purchase_type'];
    this.isView = true;

    if (this.materials.length === 1) {
      this.selectQuotation(0, this.materials[0]['vendors']);
      return;
    }
    for (let i = 0; i < this.materials.length; i++) {
      this.selectQuotation(i, this.materials[i]['vendors']);
    }
  }

  selectQuotation(index: any, data: any) {
    index = index - 1;
    const vendors = data['vendors'];
    const vendor = vendors[index];

    data['quotation_amt'] = vendor['quotation_amt'];
    data['quotation_per'] = vendor['quotation_per'];
    data['quotation_no'] = vendor['quotation_no'];
    data['pack_size'] = vendor['pack_size'];
    data['vendor_no'] = vendor['vendor_no'];
    data['gst_per'] = vendor['gst_per'];
    data['currency'] = vendor['currency'];

    this.calculation(data);
  }

  calculation(data: any) {
    if (Number(data['order_qty']) > Number(data['req_qty'])) {
      alertify.error('Order Qty Cant Be Greater Than Required Qty. !!!!!');
      data['order_qty'] = data['req_qty'];
      return;
    }
    data['gross_total'] = +data['order_qty'] * +data['quotation_amt'];
    data['gst_total'] = +data['gross_total'] * (+data['gst_per'] * 1 / 100);
    data['net_total'] = +data['gross_total'] + +data['gst_total'] * 1;
  }

  checkIndent() {
    for (let i = 0; i < this.materials.length; i++) {
      this.materials[i].check = this.allIndent;
    }
  }

  approveIndend(status: string) {
    const final_request_object: any[] = [];

    for (let i = 0; i < this.materials.length; i++) {
      if (this.materials[i]['check']) {
        const existingObj = final_request_object.find(obj => obj.material_type === this.materials[i]['material_type']);
        if (!existingObj) {
          final_request_object.push({ 'material_type': this.materials[i]['material_type'], materials: [] });
        }
      }
    }

    if (final_request_object.length === 0) {
      alertify.error('Failed: Please select material to proceed!');
      return;
    }

    for (let i = 0; i < this.materials.length; i++) {
      const material = this.materials[i];
      if (material['check']) {
        if (material['order_qty'] === '' || material['order_qty'] === '0') {
          alertify.error('Failed: Please enter order qty!');
          return;
        }
        if (material['gross_total'] === '' || material['gross_total'] === '0') {
          alertify.error('Failed: Please select Vendor!');
          return;
        }
        if (Number.isNaN(material['gst_total'])) {
          alertify.error('Failed: Please select GST!');
          return;
        }
        for (let k = 0; k < final_request_object.length; k++) {
          if (final_request_object[k]['material_type'] === material['material_type']) {
            final_request_object[k]['materials'].push(material);
          }
        }
      }
    }

    this.service.post('purchase/requisition_processing.php?type=approveRequisition&status=' + status, JSON.stringify(final_request_object))
      .subscribe((response: any) => {
        if (response['status'] === 'success') {
          alertify.success('Record updated successfully');
          this.isView = false;
          this.getPendingIndends(this.forType);
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

}
