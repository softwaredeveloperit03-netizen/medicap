import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;
@Component({
  selector: 'app-approval',
  templateUrl: './approval.component.html',
  styleUrls: ['./approval.component.css']
})
export class ApprovalComponent implements OnInit {
  constructor(private service: DataAccessService) { }


  
  ngOnInit() {
    this.getPendingIndends();
  }
  
 
  results;
  searchQuery = '';
  loading = false;
  getPendingIndends() {
    this.service.get('purchase/indent.php?type=getCheckedIndendsRMPM&For=GEN').subscribe((response: any) => {
      this.results = Array.isArray(response) ? response : [];
    }, () => {
      this.results = [];
    });
  }

  formatStatus(status: string): string {
    const s = String(status || '').trim();
    const u = s.toUpperCase();
    if (u === 'PENDING' || u === 'APPROVE' || u === 'TO_PLANTHEAD' || u === 'TO_HOD' || u === 'TO_HOD_RMPM' || u === 'TO_STORE_HEAD') {
      return 'Pending';
    }
    return s || '-';
  }

  selectedResult = [];
  materials = [];
  order_type = '';
  purchase_type = '';
  isView = false;

  view(data) {
    this.selectedResult = data;
    this.materials = this.selectedResult['materials'];
    this.order_type = this.selectedResult['order_type'];
    this.purchase_type = this.selectedResult['purchase_type'];
    this.isView = true;
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
