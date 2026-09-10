import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-requisition',
  templateUrl: './requisition.component.html',
  styleUrls: ['./requisition.component.css']
})
export class RequisitionComponent implements OnInit {
 
  isView = false;
  results;

  selectedResult = [];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getPendingDispensings();
  }

  getPendingDispensings() {
    this.service.get('production/lot/dispensing.php?type=getPendingDispensing').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  sendRequisition(){
    let temp={};
    temp['materials']=this.selectedResult['raw_materials'];
    temp['product_code']=this.selectedResult['product_code'];
    temp['plan_no']=this.selectedResult['plan_no'];
    temp['batch_size']=this.selectedResult['batch_size'];
    temp['bom_no']=this.selectedResult['bom_no'];
    temp['company_unit']=this.selectedResult['company_unit'];
    temp['batch_no']=this.selectedResult['batch_no'];
    temp['id']=this.selectedResult['id'];
    this.service.post('production/lot/dispensing.php?type=sendDispensingRequest',JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        alertify.success('Requistion Send Successfully!');
        this.isView = false;
        this.getPendingDispensings();
      }else{
        alertify.error('Failed an error occured,Please try again!');
      }
    });
  }

}
