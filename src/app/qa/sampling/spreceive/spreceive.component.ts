import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-receive',
  templateUrl: './spreceive.component.html', 
  styleUrls: ['./spreceive.component.css']
})
export class SpreceiveComponent implements OnInit {
  results;
  selectedResult=[];

    isView=false;
  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingQAIntimations();
  }

  getPendingQAIntimations(){
    this.service.get('ipqc/finish.php?type=getPendingQAIntimations_saipro').subscribe(response=>{
      this.results=response;
    });
  }

   
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  }

  receiveIntimation(){
    this.service.get('ipqc/finish.php?type=receiveIntimation&id='+this.selectedResult['id']).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data successfuly receive');
        this.getPendingQAIntimations();
        this.isView=false;
      }else{
        alertify('Some error occured!');
      }
    });
  }

}
