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
    this.service.get('production/lot/dispensing.php?type=getDispensingReceivings').subscribe(response=>{
      this.results=response;
    });
  }

  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  show(index){
    let material=this.selectedResult['materials'];
    this.selectedMaterial=material[index];
    this.isShow=true;
  }
  receive(){
    this.service.get('production/lot/dispensing.php?type=receiveMaterial&id='+this.selectedResult['id']+'&document_no='+this.selectedResult['document_no']+'&request_date='+this.selectedResult['request_date']+'&request_time='+this.selectedResult['request_time']).subscribe(response=>{
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
