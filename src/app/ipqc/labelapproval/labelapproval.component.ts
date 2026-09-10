 
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-labelapproval',
  templateUrl: './labelapproval.component.html',
  styleUrls: ['./labelapproval.component.css'] 
})
export class LabelapprovalComponent implements OnInit {

  constructor(private service: DataAccessService ) {
   
  }
  results;
  selectedResult = [];
  isShow = false;
  isView = false;
  int_sifters;
  ngOnInit(): void {
    this.getInprocessBatches();
  }
  getInprocessBatches() {
    //this.service.get('store/dispensing.php?type=get_Dispensing_Requests_For_Production_Activity_Formulation&material_type=Raw Material').subscribe(response => {
    this.service.get('production/product.php?type=getReadyBatchPlans_pk_sp_Label&material_type=Packing Material').subscribe(response => {
      this.results = response;
    });
  }
  
  getBmrCheckList(product_code, index) {
    this.selectedResult = this.results[index];  
      this.isShow = true;
      this.get_int_sift();
    }
    get_int_sift() {
      this.service.get('production/product.php?type=get_savebmr_sift_pk_inprocess_pk&work_order_no='+this.selectedResult['work_order_no']+'&batch_plan_id='+this.selectedResult['batch_plan_id']+'&work_id='+this.selectedResult['a_id']).subscribe(response => {
        this.int_sifters = response;
      });
    }

    ViewLabel(url) {
      url = this.service.url + '../../upload/production_label/' + url;
      window.open(url, '_blank');
    }


    update(status,id) {
 
  
      this.service.get('production/product.php?type=update_label_ipqc_status&id='+id+'&status='+status+'&dept=ipqc').subscribe(response => {
        if (response['status'] === 'success') {
        alertify.success('Successfull');
        this.isView = false;
        this.isShow = true;
        this.get_int_sift()
   
   
      } else {
        alertify.error('Failed: An error occured, Please try again!');
      }
    });
    
    }



  }


