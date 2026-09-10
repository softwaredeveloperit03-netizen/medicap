import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-issuance',
  templateUrl: './issuance.component.html',
  styleUrls: ['./issuance.component.css']
})
export class IssuanceComponent implements OnInit {

  results;
  batch_no='';
  selectedResult=[];
  isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingBatches();
  }

  getPendingBatches(){
    this.service.get('qa/issuance.php?type=getBatchesForApproval').subscribe(response=>{
      this.results=response;
    });
  }

  viewBatch(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  Update(){
    let temp = this.selectedResult;
    temp['batch_no'] = this.batch_no;
    this.service.post('production/plant9/manufacturing.php?type=startProduction&id='+this.selectedResult['id']+ '&batch_no='+this.batch_no,JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.isView=false;
        this.getPendingBatches();
      }else{
        alertify.error('some error occured!');
      }
    });
  }

  calculate() {
    let materials = this.selectedResult['raw_materials'];
    for (let i = 0; i< materials.length; i++) {
      let material = materials[i];
      if (+material['recovery_stock'] < +material['recovery_qty']) {
        material['recovery_qty'] = material['recovery_stock'];
      }
      material['dispensing_qty'] = +material['qty'] - +material['recovery_qty'];
      materials[i] = material;
    }
    this.selectedResult['raw_materials'] = materials;
  }

}
