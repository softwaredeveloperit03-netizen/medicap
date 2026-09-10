import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-receiving',
  templateUrl: './receiving.component.html',
  styleUrls: ['./receiving.component.css']
})
export class ReceivingComponent implements OnInit {
  results;
  isView=false;
  selectedResult=[];
  selectedMaterial=[];
  isShow=false;


  constructor(private service:DataAccessService ) {    }

  ngOnInit(): void {
    this.getDispensingReceivings();
  }

  getDispensingReceivings(){
    // this.service.get('production/plant5/dispensing.php?type=getPackingDispensingReceivings').subscribe(response=>{
    this.service.get('packing/dispensing.php?type=getPackingDispensingReceivings').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    let material=this.selectedResult['materials'];
    this.selectedMaterial=material[index];
    this.isShow=true;
    this.get_int_sift();
  }

  int_sifters;
  get_int_sift() {
    this.service.get('production/product.php?type=get_savebmr_sift_pk_recieve&b_id='+this.selectedResult['b_id']+'&batch_plan_id='+this.selectedResult['b_id']+'&work_id='+this.selectedResult['a_id']).subscribe(response => {
      this.int_sifters = response;
    });
  }

  selected_sifter=[];
  add(index){
    this.selected_sifter=this.int_sifters[index]
  

    this.isView = true;
    this.isShow = false;  
  }

  show(index){
    let material=this.selectedResult['materials'];
    this.selectedMaterial=material[index];
    this.isShow=true;
  }
  
  receive(){
    this.service.get('packing/dispensing.php?type=receivePackingMaterial_sp&id='+this.selectedResult['id']+'&document_no='+this.selectedResult['document_no']+'&request_date='+this.selectedResult['request_date']+'&request_time='+this.selectedResult['request_time']+'&sift_id='+this.selected_sifter['id']).subscribe(response=>{
      if(response['status']='success'){
        alertify.success('Material Receive Successfuly');
        this.isView=false;
        this.getDispensingReceivings();
      }else{
        alertify.error('some error Ocuured!');
      }
    });
  }

  download(){
    
  }

  downloadRecord(){
    
  }

}
