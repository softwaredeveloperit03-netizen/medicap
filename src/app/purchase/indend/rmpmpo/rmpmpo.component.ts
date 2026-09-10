import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-rmpmpo',
  templateUrl: './rmpmpo.component.html',
  styleUrls: ['./rmpmpo.component.css', '../../shared/purchase-vapp-host.css']
})
export class RmpmpoComponent implements OnInit {


  constructor(private service: DataAccessService) { }


  
  ngOnInit() {
    this.getPendingIndends();
  }
  
 
  results;
  searchQuery = '';
  loading = false;
  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getCheckedIndendsRMPM&For=RMPM').subscribe((response: any) => {
      this.results = response;
    });
  }

  formatStatus(status: string): string {
    if (!status) {
      return '-';
    }
    const u = String(status).trim().toUpperCase();
    if (u === 'PENDING' || u === 'APPROVE' || u === 'TO_PLANTHEAD' || u === 'TO_HOD' || u === 'TO_HOD_RMPM' || u === 'TO_STORE_HEAD') {
      return 'Pending';
    }
    return status;
  }

  selectedResult = [];
  materials = [];
  order_type = '';
  purchase_type = '';
  isView = false;

  /** Requirement date from raw indent form (indent-level or first material). */
  get requirementDate(): string | null {
    const d = this.selectedResult?.['requirement'] ?? this.materials?.[0]?.['requirement'];
    return d != null && d !== '' ? d : null;
  }

  prNotes = '';

 view(data) {
  this.selectedResult = data;
  this.materials = this.selectedResult['materials'] || [];
  this.prNotes = String(this.selectedResult['pr_notes'] || this.materials[0]?.pr_notes || '').trim();

  this.order_type = this.selectedResult['order_type'];
  this.purchase_type = this.selectedResult['purchase_type'];
  this.isView = true;

  // If only 1 material, auto-select its vendor
  if (this.materials.length === 1) {
    this.selectQuotation(0, this.materials[0]['vendors']);
    return;
  }

  // If multiple materials → loop through them
  for (let i = 0; i < this.materials.length; i++) {
    this.selectQuotation(i, this.materials[i]['vendors']);
  }
}


  
  quotationOptionText(vendor: any, material?: any): string {
    const name = vendor?.vendor_name || '';
    const amt = vendor?.quotation_amt ?? '';
    const curr = vendor?.currency || '';
    const per = vendor?.quotation_per;
    const packSize = vendor?.pack_size;
    const packUnit = vendor?.pack_unit || material?.unit || '';
    let perUnit = '';
    if (packSize && packUnit) {
      perUnit = String(packSize) + ' ' + String(packUnit);
    } else if (per && packUnit && String(per).trim() !== String(packUnit).trim()) {
      perUnit = String(per) + ' ' + String(packUnit);
    } else {
      perUnit = [per, packUnit].filter((v) => v != null && String(v).trim() !== '').join(' ');
    }
    const mid = [amt, curr].filter((v) => v !== '' && v != null).join(' ');
    if (!name) {
      return mid && perUnit ? `(${mid} / ${perUnit})` : `(${mid || perUnit})`;
    }
    return perUnit ? `${name} (${mid} / ${perUnit})` : `${name} (${mid})`;
  }

  selectQuotation(index,data) {
    index = index - 1;

    
    let vendors = data['vendors'];
    let vendor = vendors[index];

    data['quotation_amt'] = vendor['quotation_amt'];
    data['quotation_per'] = vendor['quotation_per'];
    data['quotation_no'] = vendor['quotation_no'];
    data['pack_size'] = vendor['pack_size'];
    data['vendor_no'] = vendor['vendor_no'];
    data['gst_per'] = vendor['gst_per'];
    data['currency'] = vendor['currency'];

     
    this.calculation(data);
     
  }

 
 
  approveIndend(status) {
  
    let final_request_object = [];

    for (let i = 0; i < this.materials.length; i++) {
      if (this.materials[i]['check']) {
        let existingObj = final_request_object.find(obj => obj.material_type === this.materials[i]['material_type']);
        if (!existingObj) {
          // Create a new object and push it to the final_request_object if it doesn't exist
          let obj = { "material_type": this.materials[i]['material_type'], materials: [] };
          final_request_object.push(obj);
          existingObj = obj; // Update existingObj to the new object
        }
      }
    }

    if(final_request_object.length == 0){
      alertify.error('Failed: Please select material to proceed!');
      return;
    }

    if (final_request_object[0]) {
      final_request_object[0]['notes'] = this.prNotes || '';
    }

    for (let i = 0; i < this.materials.length; i++) {
      let material = this.materials[i];
      if (material['check']) {
      
          if (material['order_qty'] == '' || material['order_qty'] == '0') {
            alertify.error('Failed: Please enter order qty!');
            return;
          }
          if (material['gross_total'] == '' || material['gross_total'] == '0') {
            alertify.error('Failed: Please select Vendor!');
            return;
          }
          if (Number.isNaN(material['gst_total'])) {
            alertify.error('Failed: Please select GST!');
            return;
          }


        for (let k = 0; k < final_request_object.length; k++) {
          if (final_request_object[k]['material_type'] == material['material_type']) {
            final_request_object[k]['materials'].push(material);
          }
        }
      }
    }
 
    this.service.post('purchase/indent.php?type=approveIndend&status=' + status, JSON.stringify(final_request_object)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record updated successfully');
        this.isView = false;
        this.getPendingIndends();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });

  }

 
  

 
 
  calculation(data) {

    if(Number(data['order_qty']) > Number(data['req_qty'])){
      alertify.error('Order Qty Cant Be Greater Than Required Qty. !!!!!');
      data['order_qty'] = data['req_qty'];
      return;
    }

  
    data['gross_total'] = +data['order_qty'] * +data['quotation_amt'];
    data['gst_total'] = +data['gross_total'] * (+data['gst_per'] * 1 / 100);
    data['net_total'] = +data['gross_total'] + +data['gst_total'] * 1;
 
    console.log(data)
  }

  

 
  allIndent =  false;

  checkIndent(){

    if(this.allIndent){
      for (let i = 0; i < this.materials.length; i++) {
        this.materials[i].check = true;
      } 
    }else{
      for (let i = 0; i < this.materials.length; i++) {
        this.materials[i].check = false;
      } 
    }
  
  }

 

}
