import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-awaiting',
  templateUrl: './awaiting.component.html',
  styleUrls: ['./awaiting.component.css']
})
export class AwaitingComponent implements OnInit {
  results;
  selectedResult=[];
  isView=false;

  remark='';

  constructor(private service:DataAccessService) { }

  ngOnInit() {
    this.getPendingTestings();
  }

  getPendingTestings(){
    this.service.get('ipqc/finish.php?type=getPendingTestings').subscribe(response=>{
      this.results=response;
    });
  }

   
  view(index){
    this.selectedResult=this.results[index];
    this.isView=true;
  
  }

 

  save(){
    let temp=this.selectedResult;
    temp['remark']=this.remark;
    this.service.post('ipqc/finish.php?type=saveTestingReport',JSON.stringify(temp)).subscribe(response=>{
      if(response['status']=='success'){
        alertify.success('data save successfuly');
        this.getPendingTestings();
        this.isView=false;
      }else{
        alertify.error('some error occured!please try again');
      }
    });
  }

  
}

